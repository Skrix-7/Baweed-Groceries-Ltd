<?php
session_start();
include "../dbConnector.local.php";

//Only allows admin access
if (!isset($_SESSION['adminID'])) {
    header("Location: adminLogin.php");
    exit;
}

//Return listings for a given productID
if (isset($_GET['getListings'])) {

    //This gets the products id and returns the listing for it
    $productID = intval($_GET['getListings']);
    $stmt = $conn->prepare("
        SELECT l.listingID, l.Price, l.Quantity, s.Fullname AS supplier FROM listings l
        LEFT JOIN suppliers s ON l.supplierID = s.supplierID
        WHERE l.productID = ? ORDER BY l.listingID ASC
    ");
    $stmt->bind_param("i", $productID);

    //Executes the query then gets the results
    $stmt->execute();
    $result = $stmt->get_result();

    //Stores listings within the array
    $listings = [];
    while ($row = $result->fetch_assoc()) {
        $listings[] = $row;
    }

    //Closes connection and sends the json back
    $stmt->close();
    header('Content-Type: application/json');
    echo json_encode($listings);
    exit;
}

//Update listing price
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'updatePrice') {

    //This is the variables for the product
    $listingID = intval($_POST['listingID']);
    $newPrice = floatval($_POST['price']);

    //Prevents 0/negative pries
    if ($newPrice <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Price must be greater than 0.']);
        exit;
    }

    //Update listing query
    $stmt = $conn->prepare("UPDATE listings SET Price = ? WHERE listingID = ?");
    $stmt->bind_param("di", $newPrice, $listingID);

    //Executes and closes statement
    $stmt->execute();
    $stmt->close();

    //Returns script
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success']);
    exit;
}

//Delete listing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'deleteListing') {

    //This is the listing id
    $listingID = intval($_POST['listingID']);

    //This is a query to delete the users listing
    $stmt = $conn->prepare("DELETE FROM listings WHERE listingID = ?");
    $stmt->bind_param("i", $listingID);

    //Exeutes and closes statement
    $stmt->execute();
    $stmt->close();

    //Returns script
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success']);
    exit;
}

//Gets the types of products
$products = [];
$result = $conn->query("SELECT productID, Name FROM products ORDER BY Name ASC");

//Stores the products in the products array
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Listing Management</title>
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
            width: 100%;
        }

        .mainDiv {
            background-color: #f5f7fa;
            width: 88%;
            min-height: calc(100vh - 24px);
            
            margin-top: 12px;
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

            padding: 20px 36px;
            background: linear-gradient(to right, #db8e08, #c77e09);
            color: white;
        }

        .bannerLeft {
            display: flex;
            align-items: center;
            gap: 22px;
        }

        .shopBanner img {
            transition: 0.25s ease;
            cursor: pointer;
        }

        .shopBanner img:hover {
            transform: scale(1.06);
        }

        .shopBanner h1 {
            font-size: 34px;
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
            gap: 8px;
        }

        .bannerRight p {
            margin: 0;
            font-size: 18px;
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

            height: 40px;
            padding: 0 18px;
            font-size: 15px;

            font-weight: 600;
            color: white;
            cursor: pointer;

            box-shadow: 0 4px 8px rgba(0,0,0,0.25);
            transition: all 0.25s ease;
            white-space: nowrap;
        }

        .logOutButton {
            background: linear-gradient(to right, #e74c3c, #c0392b);
        }

        .shopButton:hover {
            transform: translateY(-3px);
            filter: brightness(1.08);
            box-shadow: 0 8px 18px rgba(0,0,0,0.35);
        }

        .contentArea {
            display: flex;
            flex: 1;
            gap: 0;
        }

        .productPanel {
            width: 280px;
            min-width: 280px;
            background: #ffffff;

            border-right: 1px solid #e2e2e2;
            display: flex;

            flex-direction: column;
            padding: 22px 0;
        }

        .panelTitle {
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 0.8px;

            text-transform: uppercase;
            color: #999;
            padding: 0 20px 14px 20px;

            border-bottom: 1px solid #ebebeb;
            margin-bottom: 8px;
        }

        .productTypeBtn {
            display: flex;
            align-items: center;

            gap: 10px;
            padding: 13px 20px;

            border: none;
            background: none;

            font-family: "Segoe UI", Arial, sans-serif;
            font-size: 16px;
            font-weight: 500;

            color: #333;
            cursor: pointer;
            text-align: left;

            transition: 0.15s ease;
            border-left: 3px solid transparent;
        }

        .productTypeBtn:hover {
            background: #fdf3e3;
            color: #b37200;
        }

        .productTypeBtn.active {
            background: #fdf3e3;
            color: #b37200;

            font-weight: 700;
            border-left: 3px solid #f5a623;
        }

        .listingsPanel {
            flex: 1;
            padding: 32px 36px;

            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .listingsHeader {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .listingsTitle {
            font-size: 26px;
            font-weight: 700;
            color: #2a2a2a;
        }

        .listingsSubtitle {
            font-size: 15px;
            color: #999;
            margin-top: 2px;
        }

        .emptyState {
            display: flex;
            flex-direction: column;

            align-items: center;
            justify-content: center;
            flex: 1;

            color: #bbb;
            font-size: 17px;
            gap: 10px;
            padding: 60px 0;
        }

        .emptyState span {
            font-size: 52px;
        }

        .listingsGrid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 18px;
        }

        .listingCard {
            background: white;
            border-radius: 12px;
            padding: 22px;

            border: 1px solid #e8e8e8;
            box-shadow: 0 3px 8px rgba(0,0,0,0.06);

            display: flex;
            flex-direction: column;
            gap: 14px;
            transition: 0.2s ease;
        }

        .listingCard:hover {
            box-shadow: 0 6px 16px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }

        .listingCardTop {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .listingId {
            font-size: 13px;
            color: #bbb;
            font-weight: 600;

            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .listingSupplier {
            font-size: 15px;
            font-weight: 600;

            color: #444;
            margin-top: 2px;
        }

        .listingStock {
            font-size: 14px;
            color: #888;
        }

        .stockBadge {
            display: inline-block;
            padding: 4px 11px;

            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }

        .stockHigh { background: #e8f5e9; color: #2e7d32; }
        .stockMed { background: #fff8e1; color: #f57f17; }
        .stockLow { background: #ffebee; color: #c62828; }

        .priceRow {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .priceLabel {
            font-size: 13px;
            color: #999;
            font-weight: 600;

            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        .priceInput {
            flex: 1;
            padding: 9px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;

            font-size: 15px;
            font-family: "Segoe UI", Arial, sans-serif;

            transition: 0.2s ease;
            width: 0;
        }

        .priceInput:focus {
            outline: none;
            border-color: #f5a623;
            box-shadow: 0 0 0 2px rgba(245,166,35,0.18);
        }

        .saveBtn {
            padding: 9px 16px;

            background: linear-gradient(to right, #f5a623, #c4841a);
            color: white;
            border: none;

            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;

            cursor: pointer;
            transition: 0.2s ease;
            white-space: nowrap;
        }

        .saveBtn:hover {
            filter: brightness(1.08);
        }

        .deleteBtn {
            width: 100%;
            padding: 10px;

            background: none;
            border: 1px solid #f5c6c6;
            border-radius: 6px;

            color: #c62828;
            font-size: 14px;
            font-weight: 600;

            cursor: pointer;
            transition: 0.2s ease;
        }

        .deleteBtn:hover {
            background: #ffebee;
            border-color: #c62828;
        }

        .cardFeedback {
            font-size: 14px;
            font-weight: 600;

            text-align: center;
            min-height: 16px;
        }

        .feedbackOk { color: #2e7d32; }
        .feedbackErr { color: #c62828; }

        .spinner {
            display: flex;
            justify-content: center;
            align-items: center;

            padding: 60px 0;
            color: #bbb;
            font-size: 16px;
            gap: 10px;
        }

        .footer {
            background-color: #1e1e1e;
            color: #ccc;
            text-align: center;

            padding: 24px 0;
            font-size: 15px;

            border-top: 1px solid #3d3d3d;
            box-shadow: 0 -3px 10px rgba(0,0,0,0.25);
        }

        .footer p { margin: 0; }

    </style>
</head>

<body>

    <div class="mainDiv">

        <div class="shopBanner">

            <div class="bannerLeft">

                <a href="../MainPages/WelcomePage.html">
                    <img src="../Images/LogoImages/baweedGroceriesLogo.png" width="220">
                </a>

                <h1>Listing Management</h1>

            </div>

            <div class="bannerRight">

                <p>Admin: <?= htmlspecialchars($_SESSION['adminUser'] ?? 'Admin') ?></p>

                <div class="bannerButtons">
                    <button onclick="window.location.href='adminHomePage.php'" class="shopButton" style="background:linear-gradient(to right,#555,#333);">Dashboard</button>
                    <button onclick="logOut()" class="shopButton logOutButton">Log Out</button>
                </div>

            </div>
        </div>

        <div class="contentArea">
            <div class="productPanel">

                <div class="panelTitle">Product Types</div>

                <?php foreach ($products as $product): ?>

                    <button class="productTypeBtn" data-id="<?= $product['productID'] ?>" data-name="<?= htmlspecialchars($product['Name']) ?>" onclick="loadListings(this)">
                        <?= htmlspecialchars($product['Name']) ?>
                    </button>

                <?php endforeach; ?>
            </div>

            <div class="listingsPanel" id="listingsPanel">

                <div class="emptyState">
                    <p>Select a product type to view its listings</p>
                </div>

            </div>
        </div>

        <div class="footer">
            <p>© 2026 Baweed Groceries Ltd. All Rights Reserved.</p>
        </div>

    </div>

    <script>

        let activeProductID = null;
        let activeProductName = null;

        //Load listings for the clicked product type
        function loadListings(btn) {

            //Update active state on sidebar
            document.querySelectorAll('.productTypeBtn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            activeProductID = btn.dataset.id;
            activeProductName = btn.dataset.name;

            const panel = document.getElementById('listingsPanel');
            panel.innerHTML = '<div class="spinner">Loading listings…</div>';

            //Fetches a request from the server with the product id to get the listings for that product
            fetch(`listingManagement.php?getListings=${activeProductID}`)

                //Gets the response and analyses it
                .then(r => r.json())
                .then(listings => renderListings(listings))

                //Catches any errors
                .catch(() => {
                    panel.innerHTML = '<div class="emptyState"><span></span><p>Failed to load listings.</p></div>';
                });
        }

        //Render listing cards into the right panel
        function renderListings(listings) {

            const panel = document.getElementById('listingsPanel');
            panel.innerHTML = '';

            const header = document.createElement('div');
            header.className = 'listingsHeader';
            header.innerHTML = `
                <div>
                    <div class="listingsTitle">${activeProductName}</div>
                    <div class="listingsSubtitle">${listings.length} listing${listings.length !== 1 ? 's' : ''} found</div>
                </div>
            `;
            panel.appendChild(header);

            if (listings.length === 0) {
                const empty = document.createElement('div');
                empty.className = 'emptyState';
                empty.innerHTML = '<span></span><p>No listings for this product type.</p>';
                panel.appendChild(empty);
                return;
            }

            //Grid of cards
            const grid = document.createElement('div');
            grid.className = 'listingsGrid';

            listings.forEach(listing => {
                const stockNum = parseInt(listing.Quantity);
                let stockClass = 'stockHigh';
                if (stockNum === 0) stockClass = 'stockLow';
                else if (stockNum < 10) stockClass = 'stockMed';

                const card = document.createElement('div');
                card.className = 'listingCard';
                card.id = `card-${listing.listingID}`;
                card.innerHTML = `
                    <div class="listingCardTop">
                        <div>
                            <div class="listingId">Listing #${listing.listingID}</div>
                            <div class="listingSupplier">${listing.supplier ?? 'Unknown Supplier'}</div>
                        </div>
                        <span class="stockBadge ${stockClass}">${stockNum} in stock</span>
                    </div>

                    <div class="priceRow">
                        <span class="priceLabel">Price £</span>
                        <input
                            class="priceInput"
                            type="number"
                            step="0.01"
                            min="0.01"
                            value="${parseFloat(listing.Price).toFixed(2)}"
                            id="price-${listing.listingID}"
                        >
                        <button class="saveBtn" onclick="savePrice(${listing.listingID})">Save</button>
                    </div>

                    <button class="deleteBtn" onclick="deleteListing(${listing.listingID}, '${activeProductID}')">
                        Remove Listing
                    </button>

                    <div class="cardFeedback" id="feedback-${listing.listingID}"></div>
                `;

                grid.appendChild(card);
            });

            panel.appendChild(grid);
        }

        //Save updated price for a listing
        function savePrice(listingID) {

            //Accessing elements to be edited
            const input = document.getElementById(`price-${listingID}`);
            const feedback = document.getElementById(`feedback-${listingID}`);

            //Price validation variables
            const value = input.value.trim();
            const newPrice = parseFloat(value);

            //Client side price validation
            if (isNaN(newPrice) || newPrice <= 0) {
                showFeedback(feedback, 'Price must be greater than £0.00', false);
                return;
            }

            //Value to split the price in 2 elements of an array
            const parts = value.split(".");

            //Getting the integer and decimal part
            const integerPart = parts[0];
            const decimalPart = parts[1] || "";

            //Ensures theres no more than 2 dp of the number
            if (decimalPart.length > 2) {
                showFeedback(feedback, 'Price can have at most 2 decimal places', false);
                return;
            }

            //Ensures theres no more than 8 digits to the left of the decimal place
            if (integerPart.length > 8) {
                showFeedback(feedback, 'Price is too large', false);
                return;
            }

            const body = new URLSearchParams({ action: 'updatePrice', listingID, price: newPrice.toFixed(2) });

            //Sends POST request to the php server
            fetch('listingManagement.php', { method: 'POST', body })

                //Gets the response and analyses it
                .then(r => r.json())
                .then(data => {

                    //If update was successful then the user is told so
                    if (data.status === 'success') {
                        showFeedback(feedback, 'Price updated', true);
                        input.value = newPrice.toFixed(2);
                    } 
                    
                    //Otherwise they are told it failed
                    else {
                        showFeedback(feedback, data.message || 'Update failed', false);
                    }
                })

                //Catches any errors
                .catch(() => showFeedback(feedback, 'Network error', false));
        }

        //Delete a listing and remove its card
        function deleteListing(listingID, productID) {

            if (!confirm(`Remove listing #${listingID}? This cannot be undone.`)) return;

            const body = new URLSearchParams({ action: 'deleteListing', listingID });

            //Sends a POST request to the php server
            fetch('listingManagement.php', { method: 'POST', body })

                //Gets the response and analyses it
                .then(r => r.json())
                .then(data => {

                    //If the deletion was successful then the user is told so and the card is removed
                    if (data.status === 'success') {
                        const card = document.getElementById(`card-${listingID}`);

                        //Card styling
                        card.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                        card.style.opacity = '0';
                        card.style.transform = 'scale(0.95)';

                        //This changes the css/html for it after removal
                        setTimeout(() => {

                            card.remove();

                            //Accesses the elements
                            const remaining = document.querySelectorAll('.listingCard').length;
                            const sub = document.querySelector('.listingsSubtitle');

                            //If the subtitles exist then it displays the remaining
                            if (sub) sub.textContent = `${remaining} listing${remaining !== 1 ? 's' : ''} found`;

                            //If remmaining is 0 than the grid is completely removed
                            if (remaining === 0) {

                                //This accesses the grid and removes it
                                const grid = document.querySelector('.listingsGrid');
                                if (grid) grid.remove();

                                //Creating a new div for the empty page
                                const empty = document.createElement('div');
                                empty.className = 'emptyState';
                                empty.innerHTML = '<span></span><p>No listings for this product type.</p>';

                                document.getElementById('listingsPanel').appendChild(empty);
                            }
                        }, 300);
                    }
                })

                //This catches any errors
                .catch(() => alert('Failed to delete listing.'));
        }

        //Show feedback message on a card, fades after 3s
        function showFeedback(el, msg, success) {
            el.textContent = msg;
            el.className = `cardFeedback ${success ? 'feedbackOk' : 'feedbackErr'}`;
            if (success) {
                setTimeout(() => { el.textContent = ''; }, 3000);
            }
        }

        //Logs out admin
        function logOut() {

            //Clears server and client side storage
            sessionStorage.clear();
            localStorage.clear();

            //Sends a POST request to log the admin out
            fetch("adminLogOut.php", { method: "POST" })

                //Gets the response and then redireccts the user if it was successful
                .then(r => r.json())
                .then(data => {
                    if (data.status === "success") {
                        window.location.href = "../MainPages/WelcomePage.html";
                    }
                });
        }

    </script>

    </body>
</html>