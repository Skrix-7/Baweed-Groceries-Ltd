<?php
session_start();
include "../dbConnector.local.php";
?>

<!DOCTYPE html>

<head>
    <title>Shop</title>
    <link rel='icon' type='image/x-icon' href='../Images/LogoImages/favicon.ico'>

    <style>

        body {
            margin: 0;
            font-family: "Segoe UI", Arial, sans-serif;

            background: linear-gradient(135deg, #2c2c2c, #1b1b1b);

            display: flex;
            justify-content: center;
            align-items: flex-start;  
            min-height: 100vh;
        }

        .mainDiv {
            background-color: #f5f7fa;
            width: 88%;
            min-height: 900px;
            margin-top:12.5px;

            border-radius: 18px;
            box-shadow: 0 12px 32px rgba(0,0,0,0.35);
            overflow: hidden;

            display: flex;
            flex-direction: column;
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

        .bannerRight {
            font-size: 14px;
            opacity: 0.9;
        }

        .search {
            display: flex;
            justify-content: center;
            margin: 18px 0 12px 0; 
        }

        .searchDiv {
            position: relative;
            width: 420px;
        }
        
        .searchDiv input {
            width: 100%;
            padding: 11px 42px 11px 14px;
            border-radius: 22px;
            border: 1px solid #cfcfcf;
            font-size: 14px;
            box-sizing: border-box;
            transition: 0.2s ease;
        }

        .searchDiv input:focus {
            outline: none;
            border-color: #2d7ef7;
            box-shadow: 0 0 0 2px rgba(45,126,247,0.15);
        }

        .searchDiv button {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: none;
            font-size: 16px;
            cursor: pointer;
            opacity: 0.75;
        }

        .searchDiv button:hover {
            opacity: 1;
        }

        .productMainDiv {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }

        .productsDiv {
            background-color: white;
            border-radius: 14px;
            padding: 25px;
            box-shadow: 0 5px 14px rgba(0,0,0,0.08);
        }

        .mainText {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 18px;
            color: #2a2a2a;
        }

        .products {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 18px;
        }

        .productCard {
            background: #ffffff;
            border-radius: 10px;
            padding: 12px;
            text-align: center;
            border: 1px solid #e2e2e2;
            transition: 0.2s ease;
        }

        .productCard:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }

        .productImg {
            width: 100%;
            height: 110px;
            background: #e6e6e6;
            border-radius: 8px;
            margin-bottom: 10px;
        }

        .productName {
            font-size: 14px;
            margin-bottom: 4px;
        }

        .productPrice {
            font-size: 13px;
            font-weight: bold;
            color: #1c4693;
        }

        .footer {
            margin-top: auto;

            background: #4a4a4a;      
            color: #e4e4e4;

            text-align: center;
            padding: 16px;
            font-size: 13px;
            letter-spacing: 0.3px;

            border-top: 1px solid #3d3d3d; 
            box-shadow: 0 -2px 8px rgba(0,0,0,0.25); 
        }
        
    </style>

</head>
<body>

    <div class="mainDiv">
        <div class="shopBanner">
            <div class="bannerLeft">

                <a href="WelcomePage.html">
                    <img src="../Images/LogoImages/baweedGroceriesLogo.png" width="180">
                </a>
                <h1>Store</h1>

            </div>

            <div class="bannerRight">
                Welcome to Baweed Groceries
            </div>
        </div>

        <div class="dealBanner">
        
        </div>

        <div class="search">
            <div class="searchDiv">
                <input type="text" placeholder="Search..." id="searchInput">
                <button type="button" onclick="performSearch()" id="searchButton">🔍</button>
            </div>
        </div>

        <div class="productMainDiv">
            <div class="productsDiv">
                <div class="mainText">

                </div>

                <div class="products">
                            
                </div>
            </div>
        </div>

        <div class="footer">
            <p>© 2026 Baweed Groceries Ltd. All Rights Reserved.</p>
        </div>

        </div>
    </div>

    <script>

        var input = document.getElementById("searchInput");
        input.addEventListener("keypress", function(event) {
        if (event.key === "Enter") {
            event.preventDefault();
            preformSearch
        }
        });

        /* To Do After DB Integration*/
        function preformSeach() {
        }

    </script>

</body>
</html>