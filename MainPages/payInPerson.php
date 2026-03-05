<?php
session_start();
include "../dbConnector.local.php";

// If the user is logged in use customerID, if not use sessionID
$isLoggedIn = isset($_SESSION['customerID']);
$customerID = $isLoggedIn ? $_SESSION['customerID'] : null;
$identifierField = $isLoggedIn ? "customerID" : "sessionID";
$identifierValue = $isLoggedIn ? $customerID : session_id();

$showSuccess = false;
$errorMessage = "";

// Ensures only POST requests can happen for guest users
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isLoggedIn) {

    // Initial guest variables
    $guestAddress = trim($_POST['guestAddress'] ?? '');

    // Entry validation - address only for in-person payment
    if (strlen($guestAddress) < 5) {
        $errorMessage = "Please enter a valid delivery address.";
    } else {
        $showSuccess = true;
    }

// Payment is automatically processed for logged-in users
} elseif ($isLoggedIn) {
    $showSuccess = true;
}

// Processing orders
if ($showSuccess) {

    // Get the contents of the user's basket
    $stmt = $conn->prepare("
        SELECT b.quantity, b.listingID, l.Price, l.Quantity AS stock
        FROM basket b
        INNER JOIN listings l ON b.listingID = l.listingID
        WHERE b.$identifierField = ?
    ");
    $stmt->bind_param("s", $identifierValue);
    $stmt->execute();
    $result = $stmt->get_result();

    $basketItems = [];
    $totalPrice  = 0;

    while ($row = $result->fetch_assoc()) {

        // Check stock availability before processing
        if ($row['quantity'] > $row['stock']) {
            $errorMessage  = "Not enough stock available for one or more items.";
            $showSuccess   = false;
            break;
        }

        $basketItems[] = $row;
        $totalPrice   += $row['Price'] * $row['quantity'];
    }

    $stmt->close();

    // Handle empty basket
    if ($showSuccess && empty($basketItems)) {
        $errorMessage = "Your basket is empty.";
        $showSuccess  = false;
    }

    // Process the order
    if ($showSuccess) {

        $conn->begin_transaction();
        try {

            $now = date('Y-m-d H:i:s');

            // INSERT uses all 6 columns: customerID, sessionID, listingID, Quantity, TotalPrice, PurchaseDate
            // customerID is NULL for guests; sessionID is NULL for logged-in users
            $stmt = $conn->prepare("
                INSERT INTO transactions 
                (customerID, sessionID, listingID, Quantity, TotalPrice, PurchaseDate, PaymentMethod)
                VALUES (?, ?, ?, ?, ?, ?, 'in_person')
            ");

            foreach ($basketItems as $item) {

                $lineTotal = $item['Price'] * $item['quantity'];

                if ($isLoggedIn) {
                    // customerID = int, sessionID = NULL (pass null string placeholder)
                    $nullSession = null;
                    $stmt->bind_param("isiids", $customerID, $nullSession, $item['listingID'], $item['quantity'], $lineTotal, $now);
                } else {
                    // customerID = NULL, sessionID = string
                    $nullCustomer = null;
                    $stmt->bind_param("isiids", $nullCustomer, $identifierValue, $item['listingID'], $item['quantity'], $lineTotal, $now);
                }

                $stmt->execute();
            }

            $stmt->close();

            // Update stock levels
            $stmt = $conn->prepare("UPDATE listings SET Quantity = Quantity - ? WHERE listingID = ?");
            foreach ($basketItems as $item) {
                $stmt->bind_param("ii", $item['quantity'], $item['listingID']);
                $stmt->execute();
            }
            $stmt->close();

            // Clear user's basket
            $stmt = $conn->prepare("DELETE FROM basket WHERE $identifierField = ?");
            $stmt->bind_param("s", $identifierValue);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
            $showSuccess = true;

        } catch (Exception $e) {
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
    <title>Order Processing (In Person)</title>
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

        <div class="title">Pay In Person</div>

        <?php if ($showSuccess): ?>

            <div class="success-message">
                Payment Successful!<br>
                Your order has been placed.
            </div>

            <p style="margin: 20px 0; color: #555;">

                <?php if ($isLoggedIn): ?>
                    Thank you for your order! We'll prepare it for you.<br>
                    Pay when you collect your items.
                <?php else: ?>
                    Thank you! Your order is confirmed.<br>
                    Please pay when you collect your items.
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

            <?php if (!$isLoggedIn): ?>

                <form method="post" action="">

                    <div class="entryFields">
                        <input type="text" name="guestAddress" placeholder="Enter Your Delivery Address" required>
                    </div>

                    <button type="submit" class="confirmBtn">Confirm Details</button>

                </form>

            <?php else: ?>
                <p style="color:#c62828;">Something went wrong. Please go back and try again.</p>
            <?php endif; ?>

            <div class="backLink">
                <a href="Checkout.php">← Back to Checkout</a>
            </div>

        <?php endif; ?>
    </div>
</body>
</html>