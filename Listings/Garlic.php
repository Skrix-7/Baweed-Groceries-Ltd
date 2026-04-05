<?php
session_start();
include "../dbConnector.local.php";

//These are the attributes for this product page, different in every product page
$productID = 8;               
$productName = "Garlic";       
$productImage = "../Images/ProductImages/Garlic.avif";

//This is a query to recieve all the listings for this product and the suppliers that are selling it 
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

//If there was an error preparing the statement
else {
    error_log("Database prepare error: " . $conn->error);
}
?>

<!DOCTYPE html>
<head>
    <title>Garlic Listings</title>
    <link rel="icon" type="image/x-icon" href="../Images/LogoImages/favicon.ico">

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html, body {
            width: 100%;
            height: 100%;
        }

        body {
            font-family: "Segoe UI", Arial, sans-serif;
            background: linear-gradient(135deg, #555555, #474747, #292929);
            min-height: 100vh;
            width: 100%;
        }

        .mainDiv {
            background-color: #f5f7fa;
            width: 100%;
            min-height: 100vh;
            margin: 0;
            border-radius: 0;
            box-shadow: none;
            display: flex;
            flex-direction: column;
            font-size: clamp(0.85rem, 1.1vw, 1.6rem);
        }

        .shopBanner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.15em 2.25em;
            background: linear-gradient(to right, #1c4693, #14356f);
            color: white;
            position: relative;
        }

        .bannerLeft {
            display: flex;
            align-items: center;
            gap: 1.1em;
        }

        .shopBanner img {
            transition: 0.25s ease;
            cursor: pointer;
            width: clamp(120px, 12vw, 220px);
            height: auto;
        }

        .shopBanner img:hover {
            transform: scale(1.06);
        }

        .shopBanner h1 {
            font-size: 1.75em;
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
            gap: 0.4em;
        }

        .bannerRight p {
            margin: 0;
            font-size: 0.875em;
            font-weight: 500;
        }

        .bannerButtons {
            margin-top: 0.35em;
            display: flex;
            gap: 0.875em;
            justify-content: center;
            align-items: center;
        }

        .shopButton {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            border-radius: 0.5em;
            border: none;
            height: 2.125em;
            width: 6.875em;
            font-size: 0.9375em;
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
            margin-right: 0;
        }

        .basketButton {
            padding: 0.5em 1.25em;
            background: linear-gradient(135deg, #ffb347, #ff7b00);
            border: none;
            border-radius: 0.625em;
            color: white;
            font-size: 0.9375em;
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
            margin: 1em 0 0.75em;
            padding: 0.75em 2.5em;
            width: calc(100% - 3em);
            background: linear-gradient(135deg, #ffb347, #ff7b00);
            border: none;
            border-radius: 0.625em;
            color: white;
            font-size: 1.0625em;
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
            margin: 1.25em 0 2.1875em;
            padding: 0 2.5em;
        }

        .productContentDiv {
            background-color: white;
            border-radius: 0.875em;
            padding: 2.25em;
            box-shadow: 0 5px 14px rgba(0,0,0,0.08);
            width: 100%;
        }

        .productHeader {
            font-size: 1.625em;
            font-weight: 600;
            margin-bottom: 0.5em;
            color: #2a2a2a;
        }

        .productSubText {
            font-size: 0.9375em;
            color: #555;
            margin-bottom: 1.5em;
        }

        .productLayout {
            display: flex;
            gap: 2.5em;
            flex-wrap: wrap;
        }

        .productImage {
            flex: 0 0 clamp(220px, 28vw, 420px);
        }

        .productImage .imageWrapper {
            width: 100%;
            height: clamp(160px, 18vw, 300px);
            border-radius: 0.75em;
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
            overflow: hidden;
        }

        .productImage .imageWrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center top;
        }

        .productInfo {
            flex: 1;
            min-width: 300px;
        }

        .sellerList {
            margin-top: 0.75em;
        }

        .sellerItem {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1em 1.25em;
            background: #f9f9f9;
            border: 1px solid #e2e2e2;
            border-radius: 0.625em;
            margin-bottom: 0.875em;
            transition: 0.2s ease;
        }

        .sellerItem:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .sellerName {
            font-weight: 600;
            font-size: 1em;
            color: #222;
        }

        .sellerPriceQty {
            font-size: 0.9375em;
            color: #444;
        }

        .addBasketButton {
            padding: 0.5625em 1.125em;
            background: linear-gradient(135deg, #ffb347, #ff7b00);
            border: none;
            border-radius: 0.5em;
            color: white;
            font-size: 0.875em;
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
            padding: 1.25em 0;
            font-size: 0.8125em;
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
                padding: 0.75em 2em;
                min-width: 240px;
            }
        }

        .toast {
            background-color: #2a2a2a;
            color: #fff;
            padding: 0.75em 1.25em;
            border-radius: 0.5em;
            font-size: 0.875em;
            font-weight: 500;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            opacity: 0;
            transform: translateX(100%);
            transition: transform 0.3s ease, opacity 0.3s ease;
            pointer-events: none;
        }

        .toast.show {
            opacity: 1;
            transform: translateX(0);
        }

        .toastContainer .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

    </style>
</head>

<body>
    <div class="mainDiv">
        <div class="shopBanner">
            <div class="bannerLeft">

                <a href="../MainPages/WelcomePage.html"><img src="../Images/LogoImages/baweedGroceriesLogo.png" alt="Logo"></a>
                <h1>Store</h1>

            </div>

            <div class="basketButtonDiv">
                <button class="basketButton" onclick="window.location.href='../MainPages/Basket.php'">Basket</button>
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

        <button class="returnButton" onclick="returnToStore()">Return to Store</button>

        <div class="productMainDiv">
            <div class="productContentDiv">

                <div class="productHeader"><?= htmlspecialchars($productName) ?></div>
                <div class="productSubText">Choose a seller from the list below:</div>

                <div class="productLayout">

                    <div class="productImage">
                        <div class="imageWrapper">
                            <img src="<?= $productImage ?>" alt="<?= htmlspecialchars($productName) ?>">
                        </div>
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

    <div id="toastContainer" class="toastContainer"></div>

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

        //This is where the item is added to the users basket
        function addToBasket(listingID) {

            //Sends a fetch request to add to basket.php
            fetch("AddToBasket.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: "listingID=" + listingID
            })

            //Checks the response for if it was a success or not
            .then(response => response.json())
            .then(data => {

                //If was succsessful it alerts the user that the item was added to the basket
                if (data.status === "success") {
                    showToast("Item added to basket!");
                } 
                
                //If there was an error it alerts the user that there was an error adding the item
                else {
                    showToast("Error adding item to basket.");
                }
            })

            //This catches any errors that occur
            .catch(error => console.error(error));
        }

        //This is where the user is alerted that the item was added to the basket
        function showToast(message) {

            //This creates a notification
            const container = document.getElementById('toastContainer');

            //This creates a toast element with the message
            const toast = document.createElement('div');
            toast.className = 'toast';
            toast.textContent = message;

            //Adds the toast to the container
            container.appendChild(toast);

            //Show the toast
            setTimeout(() => toast.classList.add('show'), 10);

            //Hide after 3 seconds
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => container.removeChild(toast), 300);
            }, 3000);
        }

    </script>

</body>
</html>