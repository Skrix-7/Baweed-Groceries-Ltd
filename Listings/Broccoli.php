<?php
session_start();
include "../dbConnector.local.php";

//These are the attributes for this product page, different in every product page
$productID = 1;               
$productName = "Broccoli";       
$productImage = "../Images/ProductImages/Broccoli.avif";

//This is a query to recieve all the listings for this product and the suppliers that are selling it, ordered by price (lowest first)
$listings = [];
if ($stmt = $conn->prepare("
    SELECT l.listingID, l.Price, l.Quantity, s.Fullname
    FROM listings l
    LEFT JOIN suppliers s ON l.supplierID = s.supplierID
    WHERE l.productID = ?
    ORDER BY l.Price ASC
")) {

    //Binding the productID parameter to the query and executing it
    $stmt->bind_param("i", $productID);
    $stmt->execute();

    //Getting the result and storing it in the $listings array
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $listings[] = $row;
    }

    //Closing the statement
    $stmt->close();
} 

//If there was an error preparing the statemen
else {
    error_log("Database prepare error: " . $conn->error);
}
?>

<!DOCTYPE html>
<head>
    <title>Broccoli Listings</title>
    <link rel="icon" type="image/x-icon" href="../Images/LogoImages/favicon.ico">

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Segoe UI", Arial, sans-serif;
            background: linear-gradient(135deg, #555555, #474747, #292929);
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
        }

        .mainDiv {
            background-color: #f5f7fa;
            width: 88%;
            min-height: 900px;
            margin-top: 12.5px;
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
            position: relative;
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
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            gap: 6px;
        }

        .bannerRight p {
            margin: 0;
            font-size: 14px;
            font-weight: 500;
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

        .logInButton  { background: linear-gradient(to right, #2d7ef7, #1c5ed6); }
        .signUpButton { background: linear-gradient(to right, #21f367, #17b851); }
        .logOutButton { background: linear-gradient(to right, #e74c3c, #c0392b); }

        .shopButton:hover {
            transform: translateY(-3px);
            filter: brightness(1.08);
            box-shadow: 0 8px 18px rgba(0,0,0,0.35);
        }

        .basketButtonDiv {
            margin-right: -700px;
        }

        .basketButton {
            padding: 8px 16px;
            background: linear-gradient(135deg, #ffb347, #ff7b00);
            border: none;
            border-radius: 10px;
            color: white;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 6px 14px rgba(0,0,0,0.25);
            transition: all 0.25s ease;
        }

        .basketButton:hover {
            transform: translateY(-3px);
            filter: brightness(1.1);
            box-shadow: 0 10px 20px rgba(0,0,0,0.35);
        }

        .returnButton {
            align-self: center;
            margin: 16px 0 12px;
            padding: 12px 40px;
            width: calc(100% - 48px);
            max-width: 1100px;
            background: linear-gradient(135deg, #ffb347, #ff7b00);
            border: none;
            border-radius: 10px;
            color: white;
            font-size: 17px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 6px 14px rgba(0,0,0,0.25);
            transition: all 0.25s ease;
        }

        .returnButton:hover {
            transform: translateY(-3px);
            filter: brightness(1.1);
            box-shadow: 0 10px 20px rgba(0,0,0,0.35);
        }

        .productMainDiv {
            display: flex;
            justify-content: center;
            margin: 20px 0 35px;
        }

        .productContentDiv {
            background-color: white;
            border-radius: 14px;
            padding: 28px;
            box-shadow: 0 5px 14px rgba(0,0,0,0.08);
            width: 100%;
            max-width: 1200px;
        }

        .productHeader {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #2a2a2a;
        }

        .productSubText {
            font-size: 15px;
            color: #555;
            margin-bottom: 24px;
        }

        .productLayout {
            display: flex;
            gap: 40px;
            flex-wrap: wrap;
        }

        .productImage {
            flex: 0 0 380px;
        }

        .productImage img {
            width: 100%;
            height: auto;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
            object-fit: contain;
        }

        .productInfo {
            flex: 1;
            min-width: 380px;
        }

        .sellerList {
            margin-top: 12px;
        }

        .sellerItem {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 20px;
            background: #f9f9f9;
            border: 1px solid #e2e2e2;
            border-radius: 10px;
            margin-bottom: 14px;
            transition: 0.2s ease;
        }

        .sellerItem:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .sellerName {
            font-weight: 600;
            font-size: 16px;
            color: #222;
        }

        .sellerPriceQty {
            font-size: 15px;
            color: #444;
        }

        .addBasketButton {
            padding: 9px 18px;
            background: linear-gradient(135deg, #ffb347, #ff7b00);
            border: none;
            border-radius: 8px;
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

        .footer p {
            color: #ccc;
            margin: 0;
        }

        @media (max-width: 980px) {
            .productLayout {
                flex-direction: column;
                align-items: center;
            }
            .productImage {
                max-width: 500px;
            }
            .returnButton {
                padding: 12px 32px;
                min-width: 240px;
            }
        }

    </style>
</head>

<body>
    <div class="mainDiv">
        <div class="shopBanner">

            <div class="bannerLeft">
                <a href="Shop.php">
                    <img src="../Images/LogoImages/baweedGroceriesLogo.png" width="180" alt="Logo">
                </a>
                <h1>Store</h1>
            </div>

            <div class="basketButtonDiv">
                <button class="basketButton" onclick="window.location.href='../Basket/Basket.php'">Basket</button>
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

        <div class="productMainDiv">
            <div class="productContentDiv">

                <div class="productHeader"><?= htmlspecialchars($productName) ?></div>
                <div class="productSubText">Choose a seller from the list below:</div>

                <div class="productLayout">

                    <div class="productImage">
                        <img src="<?= $productImage ?>" alt="<?= htmlspecialchars($productName) ?>">
                    </div>

                    <div class="productInfo">
                        <div class="sellerList">

                            <?php if (!empty($listings)): ?>
                                <?php foreach ($listings as $listing): ?>

                                    <div class="sellerItem">

                                        <div class="sellerName">
                                            <?= htmlspecialchars($listing['Fullname'] ?? 'Supplier ' . $listing['listingID']) ?>
                                        </div>

                                        <div class="sellerPriceQty">
                                            £<?= number_format($listing['Price'], 2) ?> • Qty: <?= $listing['Quantity'] ?>
                                        </div>

                                        <button class="addBasketButton" onclick="addToBasket(<?= $listing['listingID'] ?>)">
                                            Add to Basket
                                        </button>

                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>

                                <p style="color:#777; font-style:italic; padding: 20px 0;">
                                    No listings available for this product.
                                </p>
                    
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer">
            <p>© 2026 Baweed Groceries Ltd. All Rights Reserved.</p>
        </div>

    </div>

    <script>

        //Redirected to the login page
        function logIn() { 
            window.location.href = "../Customers/LogIn.php"; 
        }

        //Redirected to the sign up page
        function signUp() { 
            window.location.href = "../Customers/SignUp.php"; 
        }

        //Logs the user out
        function logOut() {

            //Clears any stored session or local data on the client side
            sessionStorage.clear();
            localStorage.clear();

            //Sends a POST request to the LogOut.php script to destroy the session on the server side
            fetch("../Customers/LogOut.php", { method: "POST" })

                //Checks the response from the server and reloads the page to update the UI
                .then(response => response.json())
                .then(data => {

                    //If the logout was successful, reload the page to update the UI. Otherwise, log an error message.
                    if (data.status === "success") {
                        window.location.reload();
                    } 
                    
                    //If the logout failed, log the error message returned from the server
                    else {
                        console.error("Logout failed:", data.message);
                    }
                })

                //Catches any errors
                .catch(err => console.error("Error logging out:", err));
        }

        //Redirects the user back to the main store page
        function returnToStore() {
            window.location.href = "../MainPages/StoreHomePage.php";
        }

    </script>

</body>
</html>