<?php
session_start();
include "../dbConnector.local.php";

//If the user is logged in use customerID, if not use sessionID
$isLoggedIn = isset($_SESSION['customerID']);
$customerID = $isLoggedIn ? $_SESSION['customerID'] : null;
$identifierField = $isLoggedIn ? "customerID" : "sessionID";
$identifierValue = $isLoggedIn ? $customerID : session_id();

$showSuccess = false;
$errorMessage = "";
$receiptData = [];

//Only process on POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($isLoggedIn) {

        //Logged-in users only need to supply their PIN
        $enteredPin = trim($_POST['userPin'] ?? '');

        //Entry Validation
        if (!preg_match('/^\d{4}$/', $enteredPin)) {
            $errorMessage = "Please enter a valid 4-digit PIN.";
        } else {

            // erify the PIN against the stored value
            $stmt = $conn->prepare("SELECT Pin FROM customers WHERE customerID = ?");
            $stmt->bind_param("i", $customerID);

            //Executes and gets the result
            $stmt->execute();
            $stmt->bind_result($storedPin);

            //Finishing comparison
            $stmt->fetch();
            $stmt->close();

            //Compares stored pin to entered pin
            if ((string)$enteredPin !== (string)$storedPin) {
                $errorMessage = "Incorrect PIN. Please try again.";
            } 
            
            //If they entered the right pin processing begins
            else {
                $showSuccess = true;
            }
        }

    } 
    
    //If they are guest users then different entry fields are displayed
    else {

        //Guest users must supply address, card number and PIN
        $guestAddress = trim($_POST['guestAddress']     ?? '');
        $guestCard = trim($_POST['guestCardNumber']  ?? '');
        $guestPin = trim($_POST['guestPin']         ?? '');

        //Entry validation
        if (strlen($guestAddress) < 5 || !preg_match('/^\d{16}$/', $guestCard) || !preg_match('/^\d{4}$/', $guestPin)) {
            $errorMessage = "Please fill all fields correctly.";
        } 
        
        //If they enter valid details payment is processed
        else {
            $showSuccess = true;
        }
    }
}

//Process the order if validation passed
if ($showSuccess) {

    //Fetch basket contents with stock levels — also grab product name for the receipt
    $stmt = $conn->prepare("
        SELECT b.quantity, b.listingID, l.Price, l.Quantity AS stock, l.productID, p.Name AS productName
        FROM basket b
        INNER JOIN listings l ON b.listingID = l.listingID
        INNER JOIN products p ON l.productID  = p.productID
        WHERE b.$identifierField = ?
    ");
    $stmt->bind_param("s", $identifierValue);

    //Executing query and getting the results
    $stmt->execute();
    $result = $stmt->get_result();

    $basketItems = [];
    $totalPrice  = 0;

    //Ensuring there are no errors while it loops through the results
    while ($row = $result->fetch_assoc()) {

        //If quantity exceeds max stock then it prevents further errors
        if ($row['quantity'] > $row['stock']) {
            $errorMessage = "Not enough stock available for one or more items.";
            $showSuccess  = false;
            break;
        }

        //Adds valid rows to the array and calculates total price
        $basketItems[] = $row;
        $totalPrice   += $row['Price'] * $row['quantity'];
    }

    $stmt->close();

    //Catch empty basket
    if ($showSuccess && empty($basketItems)) {
        $errorMessage = "Your basket is empty.";
        $showSuccess = false;
    }

    //Commit the order inside a transaction
    if ($showSuccess) {

        $conn->begin_transaction();
        try {

            $now = date('Y-m-d H:i:s');

            $stmt = $conn->prepare("
                INSERT INTO transactions
                (customerID, sessionID, listingID, productID, Quantity, TotalPrice, PurchaseDate, PaymentMethod)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'online')
            ");

            //Stores each transaction ID so receipts can reference them
            $transactionIDs = [];

            foreach ($basketItems as $item) {

                $lineTotal = $item['Price'] * $item['quantity'];

                if ($isLoggedIn) {
                    $nullSession = null;
                    $stmt->bind_param("isiidds", $customerID, $nullSession, $item['listingID'], $item['productID'], $item['quantity'], $lineTotal, $now);
                } else {
                    $nullCustomer = null;
                    $stmt->bind_param("isiidds", $nullCustomer, $identifierValue, $item['listingID'], $item['productID'], $item['quantity'], $lineTotal, $now);
                }

                $stmt->execute();
                $transactionIDs[] = $conn->insert_id;
            }

            $stmt->close();

            //Updates stock
            $stmt = $conn->prepare("UPDATE listings SET Quantity = Quantity - ? WHERE listingID = ?");
            foreach ($basketItems as $item) {
                $stmt->bind_param("ii", $item['quantity'], $item['listingID']);
                $stmt->execute();
            }
            $stmt->close();

            //Credit each supplier's balance for their sold items
            $stmt = $conn->prepare("
                UPDATE suppliers s
                INNER JOIN listings l ON s.supplierID = l.supplierID
                SET s.Balance = s.Balance + ?
                WHERE l.listingID = ?
            ");

            //Looping through the contents of the basket and getting the total price for each line, then crediting the suppliers
            foreach ($basketItems as $item) {
                $lineTotal = $item['Price'] * $item['quantity'];
                $stmt->bind_param("di", $lineTotal, $item['listingID']);
                $stmt->execute();
            }

            //Closing the statement
            $stmt->close();

            //Create a customer and supplier receipt for every transaction
            $stmt = $conn->prepare("INSERT INTO receipts (transactionID, receiptType) VALUES (?, ?)");

            //Loops through each transaction, creating a customer receipt and supplier receipt for each, using the stored transaction IDs
            foreach ($transactionIDs as $tid) {
                $type = 'CUSTOMER';
                $stmt->bind_param("is", $tid, $type);
                $stmt->execute();
                $type = 'SUPPLIER';

                //Binding the parameters to the query and executing it
                $stmt->bind_param("is", $tid, $type);
                $stmt->execute();
            }

            //Closing the statement
            $stmt->close();

            //This is a query to clear the basket
            $stmt = $conn->prepare("DELETE FROM basket WHERE $identifierField = ?");
            $stmt->bind_param("s", $identifierValue);

            //Executing the query, then closes the statments
            $stmt->execute();
            $stmt->close();

            //Closing the connection and showing the payment was succesful
            $conn->commit();
            $showSuccess = true;

            //Build receipt data for the popup
            $receiptData = [
                'date' => date('d/m/Y H:i:s'),
                'paymentMethod' => 'Online',
                'items' => [],
                'totalPrice' => $totalPrice,
            ];

            //Loops through the basket items to build the receipt
            foreach ($basketItems as $idx => $item) {
                $receiptData['items'][] = [
                    'productName' => $item['productName'],
                    'quantity' => $item['quantity'],
                    'unitPrice' => $item['Price'],
                    'lineTotal' => $item['Price'] * $item['quantity'],
                    'transactionID' => $transactionIDs[$idx],
                ];
            }

        } 
        
        //Catches any errors
        catch (Exception $e) {
            $conn->rollback();
            $errorMessage = "Order could not be processed. Please try again.";
            $showSuccess = false;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Online Payment</title>
    <link rel='icon' type='image/x-icon' href='../Images/LogoImages/favicon.ico'>

    <style>

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(to right, #363333, #2f2b2b);
            min-height: 100vh;
            margin: 0;
            font-family: Arial, sans-serif;
        }

        .container {
            width: 480px;
            padding: 44px;
            border-radius: 14px;
            background-color: #f0f0f0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            text-align: center;
        }

        .title {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 24px;
            color: #333;
        }

        .success-message {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 22px;
            border-radius: 8px;
            margin: 24px 0;
            font-size: 22px;
            font-weight: 600;
        }

        .error-message {
            background: #ffebee;
            color: #c62828;
            padding: 16px;
            border-radius: 8px;
            margin: 18px 0;
            font-size: 18px;
        }

        .entryFields {
            display: flex;
            flex-direction: column;
            gap: 14px;
            margin: 24px 0;
        }

        .entryFields input {
            padding: 16px;
            border-radius: 6px;
            border: 1px solid #dcdcdc;
            font-size: 18px;
            width: 100%;
            box-sizing: border-box;
        }

        .entryFields input:focus {
            outline: none;
            border-color: #2d7ef7;
            box-shadow: 0 0 0 2px rgba(45,126,247,0.15);
        }

        .confirmBtn {
            width: 100%;
            padding: 18px;
            background-color: #2d7ef7;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 20px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
        }

        .confirmBtn:hover {
            filter: brightness(0.9);
        }

        .receiptBtn {
            width: 100%;
            padding: 18px;
            background-color: #2d7ef7;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 20px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
        }

        .receiptBtn:hover {
            filter: brightness(1.1);
        }

        .continueBtn {
            width: 100%;
            padding: 18px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 20px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 12px;
            transition: 0.2s;
        }

        .continueBtn:hover {
            filter: brightness(0.9);
        }

        .backLink {
            margin-top: 24px;
            font-size: 18px;
        }

        .backLink a {
            color: #2d7ef7;
            text-decoration: none;
        }

        .backLink a:hover {
            text-decoration: underline;
        }

        .popupOverlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.6);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .popupOverlay.active {
            display: flex;
        }

        .popupBox {
            background: white;
            border-radius: 14px;
            width: 500px;
            max-width: 94vw;
            max-height: 88vh;
            overflow-y: auto;
            box-shadow: 0 16px 48px rgba(0,0,0,0.4);
            animation: popIn 0.2s ease;
        }

        @keyframes popIn {
            from { transform: scale(0.93); opacity: 0; }
            to   { transform: scale(1);    opacity: 1; }
        }

        .popupHeader {
            background: linear-gradient(to right, #1c4693, #14356f);
            color: white;
            padding: 18px 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-radius: 14px 14px 0 0;
        }

        .popupHeader h2 {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
        }

        .popupClose {
            background: none;
            border: none;
            color: white;
            font-size: 22px;
            cursor: pointer;
            opacity: 0.8;
            transition: opacity 0.2s;
            line-height: 1;
        }

        .popupClose:hover { opacity: 1; }

        .popupBody {
            padding: 24px 22px;
        }

        .receiptMeta {
            font-size: 14px;
            color: #777;
            margin-bottom: 18px;
            text-align: left;
            line-height: 1.8;
        }

        .receiptTable {
            width: 100%;
            border-collapse: collapse;
            font-size: 15px;
            margin-bottom: 16px;
        }

        .receiptTable thead th {
            background: #f4f4f4;
            padding: 10px 12px;
            text-align: left;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #999;
            border-bottom: 1px solid #eee;
        }

        .receiptTable tbody td {
            padding: 10px 12px;
            border-bottom: 1px solid #f5f5f5;
            color: #333;
        }

        .receiptTable tbody tr:last-child td {
            border-bottom: none;
        }

        .receiptTotal {
            text-align: right;
            font-size: 18px;
            font-weight: 700;
            color: #2a2a2a;
            padding-top: 12px;
            border-top: 2px solid #eee;
        }

        .receiptBadge {
            display: inline-block;
            background: #eaf4fb;
            color: #1a6fa3;
            border-radius: 20px;
            padding: 3px 14px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 16px;
        }

    </style>
</head>

<body>

    <div class="container">

        <div class="title">Pay Online</div>

        <?php if ($showSuccess): ?>

            <div class="success-message">
                Payment Successful!<br>
                Your order has been placed.
            </div>

            <p style="margin: 24px 0; color: #555; font-size: 19px;">

                <?php if ($isLoggedIn): ?>
                    Thank you for your order!<br>
                    Your items will be dispatched shortly.
                <?php else: ?>
                    Thank you! Your order is confirmed.<br>
                    Your items will be dispatched to your address.
                <?php endif; ?>

            </p>

            <button class="receiptBtn" onclick="openReceipt()">Show Receipt</button>

            <a href="../MainPages/StoreHomePage.php">
                <button class="continueBtn">Continue Shopping</button>
            </a>

        <?php else: ?>
            <?php if ($errorMessage): ?>

                <div class="error-message">
                    <?= htmlspecialchars($errorMessage) ?>
                </div>

            <?php endif; ?>

            <?php if ($isLoggedIn): ?>

                <p style="color:#555; margin-bottom: 10px; font-size: 18px;">Please confirm your card PIN to complete payment.</p>

                <form method="post" action="">

                    <div class="entryFields">
                        <input type="password" name="userPin" placeholder="Enter Your PIN" pattern="\d{4}" maxlength="4" required>
                    </div>

                    <button type="submit" class="confirmBtn">Confirm &amp; Pay</button>

                </form>

            <?php else: ?>

                <form method="post" action="">

                    <div class="entryFields">
                        <input type="text" name="guestAddress" placeholder="Enter Your Delivery Address" required>
                        <input type="text" name="guestCardNumber" placeholder="Enter Your Card Number" pattern="\d{16}" maxlength="16" required>
                        <input type="password" name="guestPin" placeholder="Enter Your PIN Number" pattern="\d{4}" maxlength="4" required>
                    </div>

                    <button type="submit" class="confirmBtn">Confirm &amp; Pay</button>

                </form>

            <?php endif; ?>

            <div class="backLink">
                <a href="Checkout.php">Return to Checkout</a>
            </div>

        <?php endif; ?>

    </div>

    <?php if ($showSuccess && !empty($receiptData)): ?>

    <div class="popupOverlay" id="receiptOverlay" onclick="closeOnOverlay(event)">
        <div class="popupBox">

            <div class="popupHeader">
                <h2>Your Receipt</h2>
                <button class="popupClose" onclick="closeReceipt()">✕</button>
            </div>

            <div class="popupBody">

                <div class="receiptBadge">CUSTOMER RECEIPT</div>

                <div class="receiptMeta">
                    <strong>Date:</strong> <?= htmlspecialchars($receiptData['date']) ?><br>
                    <strong>Payment Method:</strong> <?= htmlspecialchars($receiptData['paymentMethod']) ?>
                </div>

                <table class="receiptTable">

                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Qty</th>
                            <th>Unit Price</th>
                            <th>Total</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($receiptData['items'] as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['productName']) ?></td>
                                <td><?= (int)$item['quantity'] ?></td>
                                <td>£<?= number_format((float)$item['unitPrice'], 2) ?></td>
                                <td>£<?= number_format((float)$item['lineTotal'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>

                </table>

                <div class="receiptTotal">
                    Order Total: £<?= number_format((float)$receiptData['totalPrice'], 2) ?>
                </div>

            </div>
        </div>
    </div>

    <script>

        //This opens the receipt
        function openReceipt() {
            document.getElementById('receiptOverlay').classList.add('active');
        }

        //This closes the receipt
        function closeReceipt() {
            document.getElementById('receiptOverlay').classList.remove('active');
        }

        //This allows the user to click outside the receipt box to close it
        function closeOnOverlay(event) {
            if (event.target === document.getElementById('receiptOverlay')) {
                closeReceipt();
            }
        }
    </script>
    <?php endif; ?>
</body>
</html>