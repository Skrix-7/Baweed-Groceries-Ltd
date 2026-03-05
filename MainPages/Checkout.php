<?php
session_start();
include("../dbConnector.local.php");

//If the user is logged in use customer id
if (isset($_SESSION['customerID'])) {
    $identifierField = "customerID";
    $identifierValue = $_SESSION['customerID'];
} 

//If the user isnt logged in it uses the session id
else {
    $identifierField = "sessionID";
    $identifierValue = session_id();
}

//This is the query to get the items in the users basket
$query = "
    SELECT b.quantity, l.Price, p.Name, l.Quantity AS stock FROM basket b
    INNER JOIN listings l ON b.listingID = l.listingID
    INNER JOIN products p ON l.productID = p.productID
    WHERE b.$identifierField = ?
";

//These are the variables for the users basket
$basketItems = [];
$totalPrice  = 0.00;

//This prepares the statement
if ($stmt = $conn->prepare($query)) {
    $stmt->bind_param("s", $identifierValue);

    //This executes the query and gets the results
    $stmt->execute();
    $result = $stmt->get_result();

    //It then adds the items to the array and calculates the total price
    while ($row = $result->fetch_assoc()) {
        $basketItems[] = $row;
        $totalPrice += $row['Price'] * $row['quantity'];
    }

    //Closes the statement
    $stmt->close();
}
//This is here incase of an empty basket
$basketIsEmpty = empty($basketItems);

?>

<!DOCTYPE html>
<html>
<head>
    <title>Checkout</title>
    <link rel="icon" type="image/x-icon" href="/Images/LogoImages/favicon.ico">

    <style>
        html, body {
            height: 100%;
            margin: 0;
            font-family: "Segoe UI", Arial, sans-serif;
            background: #eceaea;
        }

        .pageWrapper {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .homePageBanner {
            background: linear-gradient(
                120deg,
                #18b650,
                #0f8f3d,
                #19a34a,
                #0d7f36
            );
            height: 110px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.18);
            position: relative;
        }

        .headersDiv {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
        }

        .headersDiv h1 {
            color: white;
            margin: 0;
            font-size: 26px;
        }

        .linkImage {
            position: absolute;
            left: 40px;
        }
        
        .linkImage img {
                width: 150px;
            transition: 0.3s ease;
        }

        .linkImage img:hover {
            transform: scale(1.08);
            filter: brightness(1.08);
        }

        .content {
            flex: 1;
            padding: 40px 60px;
        }

        .checkoutContainer {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 40px;
        }

        .basketItems {
            width: 520px;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .basketItem {
            background: #f7f7f7;
            border-radius: 10px;
            padding: 14px 18px;
            display: flex;
            justify-content: space-between;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.08);
        }

        .sectionTitle {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 14px;
            color: #333;
        }

        .rightSide {
            width: 360px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .priceBox,
        .paymentBox {
            background: #f7f7f7;
            border-radius: 10px;
            padding: 18px;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.08);
        }

        .priceRow {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .totalPrice {
            border-top: 1px solid #ccc;
            padding-top: 10px;
            font-weight: bold;
            font-size: 17px;
        }

        .payOption {
            display: block;
            margin-bottom: 10px;
            font-size: 15px;
        }

        .confirmBtn {
            display: block;
            margin-top: 15px;
            width: 100%;
            padding: 12px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.25s ease;
        }

        .confirmBtn:hover {
            transform: translateY(-3px);
            filter: brightness(1.1);
        }

        .returnBtn {
            position: absolute;
            left: 210px; 
            top: 50%;
            transform: translateY(-50%);

            padding: 10px 16px;
            background-color: #2d7ef7;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: 0.25s ease;
        }

        .returnBtn:hover {
            transform: translateY(-50%) translateY(-2px);
            filter: brightness(1.05);
        }

        .footer {
            background-color: #1e1e1e;
            color: #ccc;
            text-align: center;
            padding: 18px 10px;
        }

        .detailsEntryField {
           width:100%; 
           padding:10px; 
           margin:8px 0; 
           border-radius:6px; 
           border:1px solid #ccc;
        }

        .orderDetails {
            margin-top: 20px; 
            display: none;
        }

        .bannerRight {
            position: absolute;
            right: 40px;

            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;

            text-align: center;
            gap: 4px;
        }

        .bannerRight p {
            margin: 0;
            color: white;
            font-size: 14px;
        }

        .bannerButtons {
            margin-top: 5px;
            display: flex;
            gap: 14px;
            justify-content: center;
            align-items: center;
        }

        .shopButton {
            display: inline-flex;
            justify-content: center;
            align-items: center;

            border-radius: 8px;
            border: none;
            height: 30px;
            width: 100px;

            font-size: 16px;
            font-weight: 600;
            color: white;
            cursor: pointer;

            box-shadow: 0 4px 8px rgba(0,0,0,0.25);
            transition: all 0.25s ease;
        }

        .logInButton {
            background: linear-gradient(to right, #2d7ef7, #1c5ed6);
        }

        .signUpButton {
            background: linear-gradient(to right, #21f367, #17b851);
        }

        .logOutButton {
            background: linear-gradient(to right, #e74c3c, #c0392b);
        }

        .shopButton:hover {
            transform: translateY(-3px);
            filter: brightness(1.08);
        }

    </style>
</head>

<body>
    <div class="pageWrapper">

        <div class="homePageBanner">

            <a href="StoreHomePage.php" class="returnBtn">← Return to Store</a>
            <a href="./WelcomePage.html" class="linkImage"><img src="../Images/LogoImages/baweedGroceriesLogo.png" alt="Logo"></a>

            <div class="headersDiv">
                <h1>Checkout</h1>
            </div>

            <div class="bannerRight">

                <?php if (isset($_SESSION['customerID'])) { ?>

                    <p>Status: Logged In</p>
                    <p>Welcome to Baweed Groceries</p>

                    <div class="bannerButtons">
                        <button onclick="logOut()" class="shopButton logOutButton">Log Out</button>
                    </div>

                <?php } else { ?>

                    <p>Status: Logged Out</p>
                    <p>Welcome to Baweed Groceries</p>

                    <div class="bannerButtons">
                        <button onclick="signUp()" class="shopButton signUpButton">Sign Up</button>
                        <button onclick="logIn()" class="shopButton logInButton">Log In</button>
                    </div>

                <?php } ?>
            </div>
        </div>

        <div class="content">
            <div class="checkoutContainer">
                <div class="basketItems">

                    <div class="sectionTitle">Your Basket</div>

                    <?php if ($basketIsEmpty): ?>

                        <p style="color:#777; font-style:italic; padding: 20px 0;">
                            Your basket is empty. Please add items before checking out.
                        </p>

                    <?php else: ?>
                        <?php foreach ($basketItems as $item): ?>

                            <div class="basketItem">
                                <span><?= htmlspecialchars($item['Name']) ?> x <?= $item['quantity'] ?> </span>
                                <span>£<?= number_format($item['Price'] * $item['quantity'], 2) ?></span>
                            </div>

                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="rightSide">
                    <div class="priceBox">

                        <div class="sectionTitle">Order Summary</div>

                        <?php if (!$basketIsEmpty): ?>
                            <?php foreach ($basketItems as $item): ?>

                                <div class="priceRow">
                                    <span><?= htmlspecialchars($item['Name']) ?> x <?= $item['quantity'] ?></span>
                                    <span>£<?= number_format($item['Price'] * $item['quantity'], 2) ?></span>
                                </div>

                            <?php endforeach; ?>
                        <?php endif; ?>

                        <div class="priceRow totalPrice">
                            <span>Total</span>
                            <span>£<?= number_format($totalPrice, 2) ?></span>
                        </div>

                    </div>

                    <form id="checkoutForm" class="paymentBox" onsubmit="completeTransaction(event)">

                        <div class="sectionTitle">Payment Method</div>

                        <label class="payOption">
                            <input type="radio" name="payment" value="in_person"> Pay In Person
                        </label>

                        <label class="payOption">
                            <input type="radio" name="payment" value="online" required> Pay Online
                        </label>

                        <button type="submit" class="confirmBtn">Confirm Order</button>

                    </form>
                </div>
            </div>
        </div>

        <div class="footer">
            <p>© 2026 Baweed Groceries Ltd. All Rights Reserved.</p>
        </div>

    </div>

    <script>

        //This takes the user to their webpage based on payment method
        function completeTransaction(event) {

            //This prevents a refresh handling errors better
            event.preventDefault();

            //This gets the payment method of the user
            const selected = document.querySelector('input[name="payment"]:checked');

            //Alert the user if no payment method is selected
            if (!selected) {
                alert("Please select a payment method.");
                return;
            }

            //This returns the value
            const method = selected.value;

            //Takes the user to payInPerson if they choose to pay in person
            let target = "";
            if (method === "in_person") {
                target = "payInPerson.php";
            } 
            
            //This takes them to payOnline if they choose to pay online
            else if (method === "online") {
                target = "payOnline.php";
            } 
            
            //This is here for unexpected errors
            else {
                alert("Unknown payment method.");
                return;
            }

            //Navigate to the correct payment page
            window.location.href = target;
        }

        //This logs the user out
        function logOut() {

            //Clears browser and local storage
            sessionStorage.clear();
            localStorage.clear();

            //Send POST request to LogOut.php
            fetch("../Customers/LogOut.php", {
                method: "POST"
            })

            //Gets the response and checks its status
            .then(response => response.json())
            .then(data => {

                //If logout was successful, refresh the page to update the UI
                if (data.status === "success") {
                    window.location.reload();
                } 
                
                //If there was an error, log it to the console
                else {
                    console.error("Logout failed:", data.message);
                }
            })
        }

        //This logs the user in
        function logIn() {
           window.location.href="../Customers/LogIn.php";
        }

        //This signs the user up
        function signUp() {
            window.location.href="../Customers/SignUp.php";
        }

    </script>

</body>
</html>