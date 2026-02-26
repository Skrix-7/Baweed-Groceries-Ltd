<?php
session_start();
include("../dbConnector.local.php");
?>

<!DOCTYPE html>
<head>
    <title>My Basket</title>
    <link rel='icon' type='image/x-icon' href='/Images/LogoImages/favicon.ico'>
        
    <style>

    html, body {
        height: 100%;
        margin: 0;
        font-family: "Segoe UI", Arial, sans-serif;
        background: #eceaea;
    }

    .pageWrapper {
        min-height: 100%;
        display: flex;
        flex-direction: column;
    }

    .homePageBanner {
        background: linear-gradient(120deg, #18b650, #0f8f3d, #19a34a, #0d7f36);
        background-size: 300% 300%;
        animation: bannerShift 12s ease infinite;

        height: 110px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0 40px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.18);
        position: relative;    
    }

    @keyframes bannerShift {
        0%   { background-position: 0% 50%; }
        50%  { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }

    .headersDiv h1 {
        color: white;
        margin: 0;
        font-size: 26px;
        letter-spacing: 0.5px;
    }

    .headersDiv {
        position: absolute;
        left: 50%;
        transform: translateX(-50%);
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

    .basketContainer {
        display: flex;
        align-items: flex-start;
        gap: 30px;
    }

    .basketItems {
        width: 520px;
        display: flex;
        flex-direction: column;
        gap: 14px;
        min-width: 420px;
        transform: translateX(25px); 
    }

    .basketItem {
        background: #f7f7f7;
        border-radius: 10px;
        padding: 14px 18px;
        display: flex;
        justify-content: space-between;
        box-shadow: 0 3px 8px rgba(0,0,0,0.08);
    }

    .itemName { font-size: 15px; }
    .itemPrice { font-weight: 600; }

    .priceSummary {
        width: 260px;
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin-left: auto;
        transform: translateX(-40px);   
    }

    .priceBox {
        background: #f7f7f7;
        border-radius: 10px;
        padding: 16px;
        box-shadow: 0 3px 8px rgba(0,0,0,0.08);
    }

    .priceRow {
        display: flex;
        justify-content: space-between;
        margin-bottom: 6px;
    }

    .totalPrice {
        border-top: 1px solid #ccc;
        padding-top: 8px;
        font-weight: bold;
        font-size: 17px;
    }

    .keepShoppingButton {
        display: block;
        width: fit-content;
        margin: 0 auto 8px auto;
        padding: 10px 18px;

        background-color: #2d7ef7;
        color: white;
        text-decoration: none;
        font-weight: 600;
        font-size: 14px;

        border-radius: 8px;
        transition: 0.25s ease;
        box-shadow: 0 3px 6px rgba(0,0,0,0.2);
    }

    .keepShoppingButton:hover {
        transform: translateY(-2px);
        filter: brightness(1.05);
    }

    .checkoutButton {
        display: block;
        width: fit-content;
        margin: 14px auto 0 auto;  
        margin-top: -2px;
        padding: 10px 18px;

        background-color: #28a745;
        color: white;
        text-decoration: none;
        font-weight: 600;
        font-size: 14px;

        border-radius: 8px;
        transition: 0.25s ease;
        box-shadow: 0 3px 6px rgba(0,0,0,0.2);
    }

    .checkoutButton:hover {
        filter: brightness(1.05);
        transform: translateY(-2px);
    }

    .footer {
        background-color: #1e1e1e;
        color: #ccc;
        text-align: center;
        padding: 18px 10px;
    }

    .footerLinks {
        margin-bottom: 8px;
    }

    .footerLinks a {
        color: #e6e6e6;
        text-decoration: none;
        margin: 0 14px;
        font-size: 14px;
    }

    .footerLinks a:hover {
        text-decoration: underline;
    }

    .footer p {
        margin: 0;
        font-size: 12px;
    }

    .sectionTitle {
        font-size: 20px;
        font-weight: 700;
        margin-bottom: 14px;
        color: #333;
    }

    .summaryTitle {
        font-size: 18px;
        font-weight: 700;
        margin-bottom: 10px;
        color: #333;
        margin-left: 4px;
    }           

    </style>
</head>

<body>

<div class="pageWrapper">
    <div class="homePageBanner">
        <a href="./WelcomePage.html" class="linkImage">
            <img src="../Images/LogoImages/baweedGroceriesLogo.png">
        </a>

        <div class="headersDiv">
            <h1>My Basket</h1>
        </div>

    </div>

    <div class="content">
        <div class="basketContainer">

            <div class="basketItems">
                <div class="sectionTitle">Items In Your Basket</div>

                <div class="basketItem">
                    <span class="itemName">Apples × 2</span>
                    <span class="itemPrice">£4.80</span>
                </div>

                <div class="basketItem">
                    <span class="itemName">Milk × 1</span>
                    <span class="itemPrice">£1.30</span>
                </div>

                <div class="basketItem">
                    <span class="itemName">Bread × 3</span>
                    <span class="itemPrice">£3.60</span>
                </div>
            </div>

            <div class="priceSummary">

                <div class="summaryTitle">Price Summary</div>

                <div class="priceBox">
                    <div class="priceRow"><span>Apples (2 × £2.40)</span><span>£4.80</span></div>
                    <div class="priceRow"><span>Milk (1 × £1.30)</span><span>£1.30</span></div>
                    <div class="priceRow"><span>Bread (3 × £1.20)</span><span>£3.60</span></div>

                    <div class="priceRow totalPrice">
                        <span>Total</span>
                        <span>£9.70</span>
                    </div>
                </div>

                <a href="./StoreHomePage.php" class="keepShoppingButton">Keep Shopping</a>
                <a href="#" class="checkoutButton">Checkout</a>

            </div>

        </div>
    </div>

    <div class="footer">
        <div class="footerLinks">
            <a href="./WelcomePage.html">Welcome Page</a>
            <a href="./StoreHomePage.php">Store Page</a>
        </div>
        <p>© 2026 Baweed Groceries Ltd. All Rights Reserved.</p>
    </div>

</div>

</body>
</html>