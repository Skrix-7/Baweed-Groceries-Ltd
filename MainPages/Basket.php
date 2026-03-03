<?php
session_start();
include("../dbConnector.local.php");

//If the user is logged in use customer id
if (isset($_SESSION['customerID'])) {

    //This is the query to get the users basket contents
    $query = "
        SELECT b.basketID, b.quantity, l.listingID, l.Price, p.Name, l.Quantity AS stock
        FROM basket b
        INNER JOIN listings l ON b.listingID = l.listingID
        INNER JOIN products p ON l.productID = p.productID
        WHERE b.customerID = ?
    ";

    //Use customer ID as the identifier
    $identifier = $_SESSION['customerID'];
} 

//If they arent logged in use session id to track their baskets contents
else {

    //Here is the query to get the users basket contents using session id
    $query = "
        SELECT b.basketID, b.quantity, l.listingID, l.Price, p.Name, l.Quantity AS stock
        FROM basket b
        INNER JOIN listings l ON b.listingID = l.listingID
        INNER JOIN products p ON l.productID = p.productID
        WHERE b.sessionID = ?
    ";

    //Use session ID as the identifier
    $identifier = session_id();
}

//Creates an array to hold the contents
$basketItems = [];

//Prepaes the statement
if ($stmt = $conn->prepare($query)) {

    //Binds the identifier parameter and executes the query
    $stmt->bind_param("s", $identifier);
    $stmt->execute();

    //Gets the result and fetches the data into the basketItems array
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $basketItems[] = $row;
    }

    //Closes the statement
    $stmt->close();
}

//Calculates total price
$totalPrice = 0;
foreach ($basketItems as $item) {
    $totalPrice += $item['Price'] * $item['quantity'];
}

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
        
        .bannerRight {
            position: absolute;
            right: 40px;

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
        
        .logOutDiv {
            position: absolute;
            top: 18px;
            right: 28px;
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

            background: linear-gradient(to right, #21f367, #17b851);
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
            box-shadow: 0 8px 18px rgba(0,0,0,0.35);
        }

        .basketItems p {
            color:#777; 
            font-style:italic; 
            padding: 20px 0;
        }

        .priceBox p {
            color:#777; 
            font-style:italic; 
            padding: 20px 0;
        }

        .basketItem {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #f7f7f7;
            border-radius: 10px;
            padding: 14px 18px;
            box-shadow: 0 3px 8px rgba(0,0,0,0.08);
        }

        .itemName {
            flex: 2;
            font-size: 15px;
            font-weight: 500;
            color: #333;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .basketItems {
            width: 520px;
            display: flex;
            flex-direction: column;
            gap: 14px;
            min-width: 420px;
            transform: translateX(25px); 
        }

        .quantityControl {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 6px;
        }

        .quantityControl button {
            width: 28px;
            height: 28px;
            font-weight: bold;
            font-size: 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: 0.2s;
            color: white;
        }

        .quantityControl .increaseBtn {
            background-color: #28a745; 
        }

        .quantityControl .decreaseBtn {
            background-color: #dc3545;
        }

        .quantityControl button:hover {
            filter: brightness(1.1);
        }

        .itemQuantity {
            width: 28px;
            text-align: center;
            font-weight: 500;
        }

        .itemPrice {
            flex: 1;
            text-align: right;
            font-weight: 600;
            font-size: 15px;
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
            <div class="basketContainer">
                <div class="basketItems">

                    <div class="sectionTitle">Items In Your Basket</div>

                    <?php if (!empty($basketItems)): ?>
                        <?php foreach ($basketItems as $item): ?>

                            <div class="basketItem">

                                <div class="itemName"><?= htmlspecialchars($item['Name']) ?></div>

                                <div class="quantityControl" data-listing-id="<?= $item['listingID'] ?>" data-price="<?= $item['Price'] ?>" data-stock="<?= $item['stock'] ?>">
                                    <button class="decreaseBtn">-</button>
                                    <span class="itemQuantity"><?= $item['stock'] ?></span>
                                    <button class="increaseBtn">+</button>
                                </div>

                                <div class="itemPrice">£<span class="itemTotalPrice"><?= number_format($item['Price'] * $item['quantity'], 2) ?></span></div>

                            </div>

                        <?php endforeach; ?>
                    <?php else: ?>

                        <p>Your basket is empty.</p>

                    <?php endif; ?>
                </div>

                <div class="priceSummary">

                    <div class="summaryTitle">Price Summary</div>

                    <div class="priceBox" id="priceSummaryBox">

                        <?php if (!empty($basketItems)): ?>
                                <?php foreach ($basketItems as $item): ?>

                                    <div class="priceRow">

                                        <span><?= htmlspecialchars($item['Name']) ?> (<?= $item['quantity'] ?> x £<?= number_format($item['Price'], 2) ?>)</span>
                                        <span>£<?= number_format($item['Price'] * $item['quantity'], 2) ?></span>

                                    </div>
                                <?php endforeach; ?>

                        <div class="priceRow totalPrice">
                            <span>Total:</span>
                            <span>£<?= number_format($totalPrice, 2) ?></span>
                        </div>

                        <?php else: ?>

                            <p>Your basket is empty.</p>

                        <?php endif; ?>
                    </div>

                    <a href="./StoreHomePage.php" class="keepShoppingButton">Keep Shopping</a>
                    <a href="./Checkout.php" class="checkoutButton">Checkout</a>
                
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

    <script>

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

        //This adds event listeners to the quantity control buttons for each item in the basket
        document.querySelectorAll('.quantityControl').forEach(control => {

            //This manages the quantity changes and updates the basket accordingly when the increase or decrease buttons are clicked
            const listingID = control.dataset.listingId;
            const price = parseFloat(control.dataset.price);
            const stock = parseInt(control.dataset.stock);

            //Get references to the quantity display and total price elements for this item
            const qtyDisplay = control.querySelector('.itemQuantity');
            const decreaseBtn = control.querySelector('.decreaseBtn');
            const increaseBtn = control.querySelector('.increaseBtn');
            const totalPriceElem = control.closest('.basketItem').querySelector('.itemTotalPrice');

            //Decrease quantity or remove item if quantity reaches 0
            decreaseBtn.addEventListener('click', () => {

                let qty = parseInt(qtyDisplay.textContent);

                //If quantity is greater than 1, decrease it
                if (qty > 1) {
                    qty--;
                    updateBasket(listingID, qty);
                } 
                
                //If quantity is 1, remove the item from the basket
                else if (qty === 1) {
                    qty = 0;
                    updateBasket(listingID, qty, true);
                }
            });

            //Increase quantity, but not beyond available stock
            increaseBtn.addEventListener('click', () => {

                //Get current quantity and check if it can be increased without exceeding stock
                let qty = parseInt(qtyDisplay.textContent);

                //If current quantity is less than stock, increase it
                if (qty < stock) {
                    qty++;
                    updateBasket(listingID, qty);
                } 
                
                //If increasing would exceed stock, show an alert
                else {
                    alert("Cannot exceed available stock!");
                }
            });

            //This updates the users basket in the database
            function updateBasket(listingID, qty, remove = false) {
            
                //This sends a POST request to update basket.php with the listing ID and new quantity
                fetch("UpdateBasket.php", {
                    method: "POST",
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ listingID, quantity: qty })
                })

                //This gets and analyzes the response from update basket
                .then(res => res.json())
                .then(data => {

                    //If the update was successful, update the UI accordingly
                    if (data.status === 'success') {
                        if (remove) {
                            control.closest('.basketItem').remove();
                        } 
                        
                        //If the item was just updated, change the quantity and total price display
                        else {
                            qtyDisplay.textContent = qty;
                            totalPriceElem.textContent = (qty * price).toFixed(2);
                        }

                        //This calls the function to update the total price display in the price summary
                        updateTotalPrice(data.totalPrice);

                        //This updates the list of items in basket at the price summary part
                        updatePriceSummary(data.basketItems);
                    } 
                    
                    //If there was an error updating the basket, show an alert with the error message
                    else {
                        alert("Error updating basket: " + data.message);
                    }
                });
            }
        });

        //This function updates the total price display in the price summary section
        function updateTotalPrice(newTotal) {
            document.querySelector('.totalPrice span:last-child').textContent = '£' + parseFloat(newTotal).toFixed(2);
        }

        //This updates the price summary section of the page
        function updatePriceSummary(basketItems) {

            //This is where the price summary contents are
            const summaryBox = document.getElementById('priceSummaryBox');
            summaryBox.innerHTML = ''; 

            //If the basket is empty they are told so
            if (basketItems.length === 0) {
                summaryBox.innerHTML = '<p>Your basket is empty.</p>';
                return;
            }

            //Otherwise the contents of the basket are added to the price summary section
            basketItems.forEach(item => {

                const row = document.createElement('div');

                row.className = 'priceRow';
                row.innerHTML = `
                    <span>${item.Name} (${item.quantity} x £${parseFloat(item.Price).toFixed(2)})</span>
                    <span>£${(item.Price * item.quantity).toFixed(2)}</span>
                `;

                //Adds the item to the summary box
                summaryBox.appendChild(row);
            });

            //Add total row at the end
            const totalRow = document.createElement('div');

            totalRow.className = 'priceRow totalPrice';
            totalRow.innerHTML = `<span>Total:</span><span>£${parseFloat(basketItems.reduce((acc, i) => acc + i.Price*i.quantity, 0)).toFixed(2)}</span>`;

            summaryBox.appendChild(totalRow);
        }

    </script>

</body>
</html>