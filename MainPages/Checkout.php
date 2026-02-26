<?php
session_start();
include("../dbConnector.local.php");
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
            right: 40px;
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
    </style>
</head>

<body>
    <div class="pageWrapper">

        <div class="homePageBanner">
            <a href="StoreHomePage.php" class="returnBtn">← Return to Store</a>
            <a href="./WelcomePage.html" class="linkImage">
                <img src="../Images/LogoImages/baweedGroceriesLogo.png" alt="Logo">
            </a>
            <div class="headersDiv">
                <h1>Checkout</h1>
            </div>
        </div>

        <div class="content">

            <div class="checkoutContainer">

                <div class="basketItems">
                    <div class="sectionTitle">Your Basket</div>

                    <div class="basketItem">
                        <span>Item Example x 1</span>
                        <span>£0.00</span>
                    </div>
                </div>

                <div class="rightSide">

                    <div class="priceBox">
                        <div class="sectionTitle">Order Summary</div>

                        <div class="priceRow totalPrice">
                            <span>Total</span>
                            <span>£0.00</span>
                        </div>
                    </div>

                    <form method="post" action="processCheckout.php" class="paymentBox">
                        <div class="sectionTitle">Payment Method</div>

                        <label class="payOption">
                            <input type="radio" name="payment" value="in_person" required>Pay In Person</label>

                        <label class="payOption">
                            <input type="radio" name="payment" value="online">Pay Online</label>

                        <button type="submit" class="confirmBtn" onclick="confirmPurchase()">Confirm Order</button>
                    </form>

                </div>
            </div>
        </div>

        <div class="footer">
            <p>© 2026 Baweed Groceries Ltd. All Rights Reserved.</p>
        </div>

    </div>

    <script>

        //This checks if their paying by card or in person
        function confirmPurchase() {

            //Gets which payment option the user selected
            const form = document.querySelector('form.paymentBox');

            form.addEventListener('submit', function(event) {
                event.preventDefault(); 

                //Get the selected payment option
                const selectedPayment = document.querySelector('input[name="payment"]:checked');

                //If the user is paying online they must enter their pin otherwise the order is confirmed.
                if (selectedPayment) {
                    console.log('Selected payment method:', selectedPayment.value);
                    
                } else {
                    alert('Please select a payment method.');
                }
            });

        }

        //This completes the transaction
        function completeTransaction() {

        }


    </script>

</body>
</html>
