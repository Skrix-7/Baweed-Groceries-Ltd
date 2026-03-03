<?php
session_start();
include "../dbConnector.local.php";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Broccoli Listings</title>
    <link rel='icon' type='image/x-icon' href='../Images/LogoImages/favicon.ico'>

    <style>

        body {
            margin: 0;
            font-family: "Segoe UI", Arial, sans-serif;
            background: linear-gradient(135deg, #555555, #474747, #292929);
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
            padding-bottom: 0;
        }

        .mainDiv {
            background-color: #f5f7fa;
            width: 88%;
            min-height: 100vh; 
            margin-top:12.5px;
            border-radius: 18px;
            box-shadow: 0 12px 32px rgba(0,0,0,0.35);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            padding-bottom: 0;
        }

        .shopBanner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 28px;
            background: linear-gradient(to right, #1c4693, #14356f);
            color: white;
        }

        .bannerLeft {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .shopBanner img {
            transition: 0.25s ease;
            cursor: pointer;
        }

        .shopBanner img:hover {
            transform: scale(1.06);
        }

        .shopBanner h1 {
            font-size: 26px;
            font-weight: 600;
            margin: 0;
            letter-spacing: 0.5px;
        }

        .bannerRight p {
            margin: 0;
            font-size: 14px;
            font-weight: 500;
            text-align: center;
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
            transition: all 0.25s ease;
        }

        .logInButton { background: linear-gradient(to right, #2d7ef7, #1c5ed6); }
        .signUpButton { background: linear-gradient(to right, #21f367, #17b851); }
        .logOutButton { background: linear-gradient(to right, #e74c3c, #c0392b); }

        .shopButton:hover {
            transform: translateY(-3px);
            filter: brightness(1.08);
        }

        .productDisplay {
            display: flex;
            gap: 40px;
            margin: 30px;
            flex-wrap: wrap;
        }

        .productImage {
            flex: 1;
            min-width: 250px;
        }

        .productImage img {
            width: 100%;
            border-radius: 12px;
            object-fit: cover;
        }

        .productInfo {
            flex: 2;
            min-width: 250px;
        }

        .productInfo h2 {
            font-size: 28px;
            margin-bottom: 12px;
        }

        .sellerList {
            margin-top: 20px;
            border-top: 1px solid #ccc;
            padding-top: 16px;
        }

        .sellerItem {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }

        .sellerItem div {
            font-size: 16px;
        }

        .addBasketButton {
            padding: 6px 12px;
            border-radius: 8px;
            border: none;
            background: linear-gradient(135deg,#ffb347,#ff7b00);
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.25s ease;
        }

        .addBasketButton:hover {
            transform: translateY(-2px);
            filter: brightness(1.1);
        }

        .footer {
            margin-top: auto;
            background-color: #1e1e1e;    
            color: #e4e4e4;
            text-align: center;
            padding: 20px 0;
            font-size: 13px;
            letter-spacing: 0.3px;
            border-top: 1px solid #3d3d3d; 
            box-shadow: 0 -3px 10px rgba(0,0,0,0.25); 
        }

        .returnButton {
            margin: 20px 30px 0 30px;
            padding: 10px 20px;
            background: linear-gradient(135deg,#ffb347,#ff7b00);
            border: none;
            border-radius: 10px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.25s ease;
        }

        .returnButton:hover {
            transform: translateY(-2px);
            filter: brightness(1.1);
        }

    </style>
</head>

<body>

    <div class="mainDiv">

        <div class="shopBanner">

            <div class="bannerLeft">
                <a href="Shop.php">
                    <img src="../Images/LogoImages/baweedGroceriesLogo.png" width="180">
                </a>
                <h1>Store</h1>
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

        <button class="returnButton" onclick="returnToStore()">← Return to Store</button>

        <div class="productDisplay">

            <div class="productImage">
                <img src="../Images/ProductImages/Broccoli.avif" alt="Broccoli">
            </div>

            <div class="productInfo">

                <h2>Broccoli</h2>
                <p>Choose a seller from the list below:</p>

                <div class="sellerList">

                    <div class="sellerItem">
                        <div>Seller A</div>
                        <div>£1.20 - Qty: 50</div>
                        <button class="addBasketButton">Add to Basket</button>
                    </div>

                    <div class="sellerItem">
                        <div>Seller B</div>
                        <div>£1.10 - Qty: 32</div>
                        <button class="addBasketButton">Add to Basket</button>
                    </div>

                    <div class="sellerItem">
                        <div>Seller C</div>
                        <div>£1.35 - Qty: 80</div>
                        <button class="addBasketButton">Add to Basket</button>
                    </div>

                </div>
            </div>
        </div>

        <div class="footer">
            <p>© 2026 Baweed Groceries Ltd. All Rights Reserved.</p>
        </div>

    </div>

    <script>

        //This takes the user to the login page
        function logIn() { 
            window.location.href="../Customers/LogIn.php"; 
        }

        //This takes the user to the sign up page
        function signUp() { 
            window.location.href="../Customers/SignUp.php"; 
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

            //Catches any errors
            .catch(err => console.error("Error logging out:", err));
        }

        //This takes the user back to the store page
        function returnToStore() {
            window.location.href = "../MainPages/StoreHomePage.php";
        }

    </script>
</body>
</html>