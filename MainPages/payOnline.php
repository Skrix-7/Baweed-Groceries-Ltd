<?php
session_start();
include "../dbConnector.local.php";

//If the user is logged in use customerID, if not use sessionID
$isLoggedIn   = isset($_SESSION['customerID']);
$customerID   = $isLoggedIn ? $_SESSION['customerID'] : null;
$identifierField = $isLoggedIn ? "customerID" : "sessionID";
$identifierValue = $isLoggedIn ? $customerID : session_id();

$showSuccess  = false;
$errorMessage = "";

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
        $guestCard    = trim($_POST['guestCardNumber']  ?? '');
        $guestPin     = trim($_POST['guestPin']         ?? '');

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

    //Fetch basket contents with stock levels
    $stmt = $conn->prepare("
        SELECT b.quantity, b.listingID, l.Price, l.Quantity AS stock
        FROM basket b
        INNER JOIN listings l ON b.listingID = l.listingID
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
        $showSuccess  = false;
    }

    //Commit the order inside a transaction
    if ($showSuccess) {

        $conn->begin_transaction();
        try {

            $now = date('Y-m-d H:i:s');

            $stmt = $conn->prepare("
                INSERT INTO transactions
                (customerID, sessionID, listingID, Quantity, TotalPrice, PurchaseDate, PaymentMethod)
                VALUES (?, ?, ?, ?, ?, ?, 'online')
            ");

            foreach ($basketItems as $item) {

                $lineTotal = $item['Price'] * $item['quantity'];

                if ($isLoggedIn) {
                    $nullSession = null;
                    $stmt->bind_param("isiids", $customerID, $nullSession, $item['listingID'], $item['quantity'], $lineTotal, $now);
                } else {
                    $nullCustomer = null;
                    $stmt->bind_param("isiids", $nullCustomer, $identifierValue, $item['listingID'], $item['quantity'], $lineTotal, $now);
                }

                $stmt->execute();
            }

            $stmt->close();

            //Updates stock
            $stmt = $conn->prepare("UPDATE listings SET Quantity = Quantity - ? WHERE listingID = ?");
            foreach ($basketItems as $item) {
                $stmt->bind_param("ii", $item['quantity'], $item['listingID']);
                $stmt->execute();
            }
            $stmt->close();

            //Clears basket
            $stmt = $conn->prepare("DELETE FROM basket WHERE $identifierField = ?");
            $stmt->bind_param("s", $identifierValue);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
            $showSuccess = true;

        } 
        
        //Catches any errors
        catch (Exception $e) {
            $conn->rollback();
            $errorMessage = "Order could not be processed. Please try again.";
            $showSuccess  = false;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Order Processing (Online)</title>
    <link rel='icon' type='image/x-icon' href='../Images/LogoImages/favicon.ico'>

    <style>

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(to right, #363333, #2f2b2b);
            height: 100vh;
            margin: 0;
            font-family: Arial, sans-serif;
        }

        .container {
            width: 340px;
            padding: 30px;
            border-radius: 12px;
            background-color: #f0f0f0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            text-align: center;
        }

        .title {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #333;
        }

        .success-message {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 16px;
            border-radius: 8px;
            margin: 20px 0;
            font-size: 17px;
            font-weight: 600;
        }

        .error-message {
            background: #ffebee;
            color: #c62828;
            padding: 12px;
            border-radius: 8px;
            margin: 15px 0;
            font-size: 14px;
        }

        .entryFields {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin: 20px 0;
        }

        .entryFields input {
            padding: 12px;
            border-radius: 6px;
            border: 1px solid #dcdcdc;
            font-size: 15px;
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
            padding: 14px;
            background-color: #2d7ef7;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
        }

        .confirmBtn:hover {
            filter: brightness(0.9);
        }

        .continueBtn {
            width: 100%;
            padding: 14px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 15px;
            transition: 0.2s;
        }

        .continueBtn:hover {
            filter: brightness(0.9);
        }

        .backLink {
            margin-top: 20px;
            font-size: 14px;
        }

        .backLink a {
            color: #2d7ef7;
            text-decoration: none;
        }

        .backLink a:hover {
            text-decoration: underline;
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

            <p style="margin: 20px 0; color: #555;">

                <?php if ($isLoggedIn): ?>
                    Thank you for your order!<br>
                    Your items will be dispatched shortly.
                <?php else: ?>
                    Thank you! Your order is confirmed.<br>
                    Your items will be dispatched to your address.
                <?php endif; ?>

            </p>

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

                <p style="color:#555; margin-bottom: 10px;">Please confirm your card PIN to complete payment.</p>

                <form method="post" action="">

                    <div class="entryFields">
                        <input type="password" name="userPin" placeholder="Enter Your PIN" pattern="\d{4}" maxlength="4" required>
                    </div>

                    <button type="submit" class="confirmBtn">Confirm &amp; Pay</button>

                </form>

            <?php else: ?>

                <form method="post" action="">

                    <div class="entryFields">
                        <input type="text"     name="guestAddress"    placeholder="Enter Your Delivery Address" required>
                        <input type="text"     name="guestCardNumber" placeholder="Enter Your Card Number" pattern="\d{16}" maxlength="16" required>
                        <input type="password" name="guestPin"        placeholder="Enter Your PIN Number" pattern="\d{4}" maxlength="4" required>
                    </div>

                    <button type="submit" class="confirmBtn">Confirm &amp; Pay</button>

                </form>

            <?php endif; ?>

            <div class="backLink">
                <a href="Checkout.php">Return to Checkout</a>
            </div>

        <?php endif; ?>

    </div>
</body>
</html>