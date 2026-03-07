<?php
session_start();
include("../dbConnector.local.php");

//Only suppliers can access this page
if (!isset($_SESSION['supplierID'])) {
    header("Location: supplierLogInPage.php");
    exit;
}

$supplierID   = (int)$_SESSION['supplierID'];
$supplierName = htmlspecialchars($_SESSION['supplierName'] ?? 'Supplier');

//Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    header('Content-Type: application/json');

    $action = $_POST['action'];

    //Create a new listing for this supplier
    if ($action === 'createListing') {

        //Gets the users inputs
        $productID = (int)($_POST['productID'] ?? 0);
        $price     = (float)($_POST['price']     ?? 0);
        $quantity  = (int)($_POST['quantity']   ?? 0);

        //Server Side validation
        if ($productID <= 0 || $price <= 0 || $quantity <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid values.']);
            exit;
        }

        //Check they don't already have a listing
        $check = $conn->prepare("SELECT listingID FROM listings WHERE supplierID = ?");
        $check->bind_param("i", $supplierID);

        //Executing query and getting results
        $check->execute();
        $check->store_result();

        //If there is a result deny access
        if ($check->num_rows > 0) {
            $check->close();
            echo json_encode(['status' => 'error', 'message' => 'You already have a listing.']);
            exit;
        }
        $check->close();

        //Otherwsie create the listing
        $stmt = $conn->prepare("INSERT INTO listings (productID, supplierID, Price, Quantity) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiid", $productID, $supplierID, $price, $quantity);

        //Execute it and get the listings id
        $stmt->execute();
        $newID = $conn->insert_id;
        $stmt->close();

        echo json_encode(['status' => 'success', 'listingID' => $newID]);
        exit;
    }

    //Update an existing listing
    if ($action === 'updateListing') {

        //Gets the users inputs
        $listingID = (int)($_POST['listingID'] ?? 0);
        $price     = (float)($_POST['price']    ?? 0);
        $quantity  = (int)($_POST['quantity'] ?? 0);

        //Server side validation
        if ($listingID <= 0 || $price <= 0 || $quantity < 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid values.']);
            exit;
        }

        //Ensure this listing belongs to this supplier before updating
        $stmt = $conn->prepare("UPDATE listings SET Price = ?, Quantity = ? WHERE listingID = ? AND supplierID = ?");
        $stmt->bind_param("diii", $price, $quantity, $listingID, $supplierID);

        //Executing the query and closing the connection
        $stmt->execute();
        $stmt->close();

        echo json_encode(['status' => 'success']);
        exit;
    }

    //Delete this suppliers listing
    if ($action === 'deleteListing') {

        //This is the query to delete their query
        $stmt = $conn->prepare("DELETE FROM listings WHERE supplierID = ?");
        $stmt->bind_param("i", $supplierID);

        //Executing query and closing connection
        $stmt->execute();
        $stmt->close();

        echo json_encode(['status' => 'success']);
        exit;
    }

    //Monthly sales report data for popup
    if ($action === 'salesReport') {

        //Getting the current date
        $monthStart = date('Y-m-01 00:00:00');
        $monthEnd   = date('Y-m-t 23:59:59');
        $monthLabel = date('F Y');

        //Get this supplier's listing details
        $listingRow = null;
        $stmt = $conn->prepare("
            SELECT l.listingID, l.Price, l.Quantity, p.Name
            FROM listings l
            INNER JOIN products p ON l.productID = p.productID
            WHERE l.supplierID = ?
        ");
        $stmt->bind_param("i", $supplierID);

        //Executing query and getting the results
        $stmt->execute();
        $result = $stmt->get_result();

        //Storing the results
        $listingRow = $result->fetch_assoc();
        $stmt->close();

        //Get sales this month for this supplier's listing
        $unitsSold = 0;
        $revenue   = 0.0;

        //If their is data then it gets the figures
        if ($listingRow) {
            $lid  = (int)$listingRow['listingID'];
            $stmt = $conn->prepare("
                SELECT SUM(Quantity) AS units, SUM(TotalPrice) AS revenue
                FROM transactions
                WHERE listingID = ? AND PurchaseDate BETWEEN ? AND ?
            ");

            //Binding the current date to its variables
            $stmt->bind_param("iss", $lid, $monthStart, $monthEnd);

            //Executing the query then getting the results
            $stmt->execute();
            $result = $stmt->get_result();

            //Fetching the results and storing them then closing the connection
            $row = $result->fetch_assoc();
            $stmt->close();

            //Getting the data from the query
            $unitsSold = (int)($row['units']   ?? 0);
            $revenue   = (float)($row['revenue'] ?? 0);
        }

        //Returns the data
        echo json_encode([
            'status'      => 'success',
            'monthLabel'  => $monthLabel,
            'productName' => $listingRow['Name']      ?? 'N/A',
            'listed'      => $listingRow['Quantity']  ?? 0,
            'price'       => $listingRow['Price']     ?? 0,
            'unitsSold'   => $unitsSold,
            'revenue'     => $revenue,
        ]);
        exit;
    }

    //If unsuccesful error message returned to the user
    echo json_encode(['status' => 'error', 'message' => 'Unknown action.']);
    exit;
}


//Get this suppliers  listing
$myListing = null;
$stmt = $conn->prepare("
    SELECT l.listingID, l.Price, l.Quantity, l.productID, p.Name AS productName
    FROM listings l
    INNER JOIN products p ON l.productID = p.productID
    WHERE l.supplierID = ?
");
$stmt->bind_param("i", $supplierID);

//Executing 
$stmt->execute();
$result = $stmt->get_result();

//Getting the data and storing it before closing the connection
$myListing = $result->fetch_assoc();
$stmt->close();

//Get all products for the create listing dropdown
$allProducts = [];
$result = $conn->query("SELECT productID, Name FROM products ORDER BY Name ASC");

//Places all the results in the array
while ($row = $result->fetch_assoc()) {
    $allProducts[] = $row;
}

//Get all other listings on the market
$marketListings = [];
$stmt = $conn->prepare("
    SELECT l.listingID, l.Price, l.Quantity, p.Name AS productName, s.Fullname AS supplierName
    FROM listings l
    INNER JOIN products  p ON l.productID  = p.productID
    INNER JOIN suppliers s ON l.supplierID = s.supplierID
    WHERE l.supplierID != ?
    ORDER BY p.Name ASC, l.Price ASC
");
$stmt->bind_param("i", $supplierID);

//Executing the query and getting the results
$stmt->execute();
$result = $stmt->get_result();

//Inserting the results in the array
while ($row = $result->fetch_assoc()) {
    $marketListings[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Supplier Dashboard</title>
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
            background: linear-gradient(to right, #c0392b, #96281b);
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
            font-size: 15px;
            font-weight: 600;
            color: white;
            cursor: pointer;
            box-shadow: 0 4px 8px rgba(0,0,0,0.25);
            transition: all 0.25s ease;
        }

        .logOutButton {
            background: linear-gradient(to right, #e74c3c, #c0392b);
        }

        .shopButton:hover {
            transform: translateY(-3px);
            filter: brightness(1.08);
            box-shadow: 0 8px 18px rgba(0,0,0,0.35);
        }

        .content {
            flex: 1;
            padding: 32px 40px;
            display: flex;
            flex-direction: column;
            gap: 28px;
        }

        .sectionCard {
            background: white;
            border-radius: 14px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.07);
            overflow: hidden;
        }

        .sectionHeader {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px 24px 14px;
            border-bottom: 1px solid #f0f0f0;
            flex-wrap: wrap;
            gap: 10px;
        }

        .sectionTitle {
            font-size: 16px;
            font-weight: 700;
            color: #2a2a2a;
        }

        .sectionBody {
            padding: 24px;
        }

        .actionBtn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            border-radius: 8px;
            padding: 0 18px;
            height: 34px;
            font-size: 13px;
            font-weight: 600;
            color: white;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 3px 8px rgba(0,0,0,0.15);
        }

        .actionBtn:hover {
            transform: translateY(-2px);
            filter: brightness(1.08);
            box-shadow: 0 6px 14px rgba(0,0,0,0.22);
        }

        .btnRed    { background: linear-gradient(to right, #c0392b, #96281b); }
        .btnGrey   { background: linear-gradient(to right, #636e72, #4a4a4a); }
        .btnGreen  { background: linear-gradient(to right, #27ae60, #1e8449); }
        .btnReport { background: linear-gradient(to right, #8e44ad, #6c3483); }

        .formRow {
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
            align-items: flex-end;
        }

        .formGroup {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .formGroup label {
            font-size: 12px;
            font-weight: 700;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .formGroup select,
        .formGroup input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            font-family: "Segoe UI", Arial, sans-serif;
            outline: none;
            transition: 0.2s ease;
            min-width: 140px;
        }

        .formGroup select:focus,
        .formGroup input:focus {
            border-color: #c0392b;
            box-shadow: 0 0 0 2px rgba(192,57,43,0.15);
        }

        .listingDisplay {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .listingInfo {
            flex: 1;
            display: flex;
            gap: 24px;
            flex-wrap: wrap;
        }

        .listingField {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .listingFieldLabel {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #aaa;
        }

        .listingFieldValue {
            font-size: 18px;
            font-weight: 700;
            color: #2a2a2a;
        }

        .listingActions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .editRow {
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
            align-items: flex-end;
            margin-top: 16px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead th {
            background: #fafafa;
            padding: 11px 20px;
            text-align: left;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.7px;
            text-transform: uppercase;
            color: #999;
            border-bottom: 1px solid #efefef;
        }

        tbody tr {
            border-bottom: 1px solid #f5f5f5;
            transition: background 0.15s ease;
        }

        tbody tr:last-child { border-bottom: none; }
        tbody tr:hover      { background: #fff5f5; }

        tbody td {
            padding: 12px 20px;
            font-size: 14px;
            color: #333;
        }

        .pill {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            text-align: center;
        }

        .pillPrice { background: #fdecea; color: #c0392b; }
        .pillStock { background: #eafaf1; color: #1e8449; }
        .pillLow   { background: #fef9e7; color: #b7950b; }
        .pillOut   { background: #f9ebea; color: #922b21; }

        .emptyState {
            text-align: center;
            padding: 36px;
            color: #bbb;
            font-size: 14px;
        }

        .responseMsg {
            font-size: 13px;
            font-weight: 600;
            margin-top: 10px;
            height: 18px;
        }

        .msgSuccess { color: #1e8449; }
        .msgError   { color: #c0392b; }

        .popupOverlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.55);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .popupOverlay.active {
            display: flex;
        }

        .popupBox {
            background: white;
            border-radius: 16px;
            width: 460px;
            max-width: 92vw;
            box-shadow: 0 16px 48px rgba(0,0,0,0.35);
            overflow: hidden;
            animation: popIn 0.2s ease;
        }

        @keyframes popIn {
            from { transform: scale(0.93); opacity: 0; }
            to   { transform: scale(1);    opacity: 1; }
        }

        .popupHeader {
            background: linear-gradient(to right, #c0392b, #96281b);
            color: white;
            padding: 18px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .popupHeader h2 {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
        }

        .popupClose {
            background: none;
            border: none;
            color: white;
            font-size: 22px;
            cursor: pointer;
            line-height: 1;
            padding: 0;
            opacity: 0.8;
            transition: opacity 0.2s;
        }

        .popupClose:hover { opacity: 1; }

        .popupBody {
            padding: 28px 24px;
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .popupMonth {
            text-align: center;
            font-size: 13px;
            font-weight: 700;
            color: #aaa;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .reportGrid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .reportCard {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 4px;
            border-top: 3px solid #c0392b;
        }

        .reportCardLabel {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #aaa;
        }

        .reportCardValue {
            font-size: 26px;
            font-weight: 700;
            color: #2a2a2a;
        }

        .reportCardSub {
            font-size: 12px;
            color: #bbb;
        }

        .popupNoListing {
            text-align: center;
            color: #aaa;
            font-size: 14px;
            padding: 20px 0;
        }

        .footer {
            background-color: #1e1e1e;
            color: #ccc;
            text-align: center;
            padding: 20px 0;
            font-size: 13px;
            letter-spacing: 0.3px;
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
                    <img src="../Images/LogoImages/baweedGroceriesLogo.png" width="180">
                </a>

                <h1>Supplier Dashboard</h1>

            </div>

            <div class="bannerRight">

                <p>Status: Supplier</p>

                <p>Welcome: <?= $supplierName ?></p>

                <div class="bannerButtons">
                    <button onclick="logOut()" class="shopButton logOutButton">Log Out</button>
                </div>

            </div>
        </div>

        <div class="content">
            <div class="sectionCard">

                <div class="sectionHeader">
                    <div class="sectionTitle">My Listing</div>
                    <button class="actionBtn btnReport" onclick="openReport()">Monthly Sales Report</button>
                </div>

                <div class="sectionBody">

                    <?php if ($myListing): ?>

                        <div id="listingView">
                            <div class="listingDisplay">
                                <div class="listingInfo">

                                    <div class="listingField">
                                        <div class="listingFieldLabel">Product</div>
                                        <div class="listingFieldValue" id="dispProduct"><?= htmlspecialchars($myListing['productName']) ?></div>
                                    </div>

                                    <div class="listingField">
                                        <div class="listingFieldLabel">Price</div>
                                        <div class="listingFieldValue" id="dispPrice">£<?= number_format((float)$myListing['Price'], 2) ?></div>
                                    </div>

                                    <div class="listingField">
                                        <div class="listingFieldLabel">Stock</div>
                                        <div class="listingFieldValue" id="dispQty"><?= (int)$myListing['Quantity'] ?> units</div>
                                    </div>

                                </div>

                                <div class="listingActions">
                                    <button class="actionBtn btnGrey" onclick="showEdit()">Edit</button>
                                    <button class="actionBtn btnRed"  onclick="deleteListing()">Remove</button>
                                </div>

                            </div>

                            <div id="editRow" data-listing-id="<?= (int)$myListing['listingID'] ?>" style="display:none;">

                                <div class="editRow">

                                    <div class="formGroup">
                                        <label>New Price (£)</label>
                                        <input type="number" id="editPrice" min="0.01" step="0.01" placeholder="0.00" value="<?= (float)$myListing['Price'] ?>">
                                    </div>

                                    <div class="formGroup">
                                        <label>New Stock</label>
                                        <input type="number" id="editQty" min="0" step="1" placeholder="0" value="<?= (int)$myListing['Quantity'] ?>">
                                    </div>

                                    <button class="actionBtn btnGreen" onclick="saveListing()">Save</button>
                                    <button class="actionBtn btnGrey"  onclick="cancelEdit()">Cancel</button>

                                </div>
                            </div>
                        </div>

                    <?php else: ?>

                        <div id="createForm">
                            <div class="formRow">

                                <div class="formGroup">

                                    <label>Product</label>

                                    <select id="newProduct">

                                        <option value="">Select a product…</option>

                                        <?php foreach ($allProducts as $p): ?>
                                            <option value="<?= $p['productID'] ?>"><?= htmlspecialchars($p['Name']) ?></option>
                                        <?php endforeach; ?>

                                    </select>
                                </div>

                                <div class="formGroup">
                                    <label>Price (£)</label>
                                    <input type="number" id="newPrice" min="0.01" step="0.01" placeholder="0.00">
                                </div>

                                <div class="formGroup">
                                    <label>Stock Quantity</label>
                                    <input type="number" id="newQty" min="1" step="1" placeholder="0">
                                </div>

                                <button class="actionBtn btnGreen" onclick="createListing()">Create Listing</button>

                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="responseMsg" id="listingMsg"></div>

                </div>
            </div>

            <div class="sectionCard">

                <div class="sectionHeader">
                    <div class="sectionTitle">Market Listings</div>
                    <span style="font-size:12px; color:#aaa;">All other suppliers</span>
                </div>

                <?php if (empty($marketListings)): ?>

                    <div class="emptyState">No other listings on the market.</div>

                <?php else: ?>

                    <table>

                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Supplier</th>
                                <th>Price</th>
                                <th>Stock</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ($marketListings as $ml): ?>

                                <?php
                                    $qty = (int)$ml['Quantity'];
                                    if ($qty === 0)      { $stockClass = 'pillOut'; $stockLabel = 'Out of Stock'; }
                                    elseif ($qty < 10)   { $stockClass = 'pillLow'; $stockLabel = $qty . ' left'; }
                                    else                 { $stockClass = 'pillStock'; $stockLabel = $qty . ' units'; }
                                ?>

                                <tr>
                                    <td><strong><?= htmlspecialchars($ml['productName']) ?></strong></td>
                                    <td><?= htmlspecialchars($ml['supplierName']) ?></td>
                                    <td><span class="pill pillPrice">£<?= number_format((float)$ml['Price'], 2) ?></span></td>
                                    <td><span class="pill <?= $stockClass ?>"><?= $stockLabel ?></span></td>
                                </tr>

                            <?php endforeach; ?>

                        </tbody>
                    </table>

                <?php endif; ?>

            </div>
        </div>

        <div class="footer">
            <p>© 2026 Baweed Groceries Ltd. All Rights Reserved.</p>
        </div>

    </div>

    <div class="popupOverlay" id="reportOverlay" onclick="closeReportOnOverlay(event)">

        <div class="popupBox">

            <div class="popupHeader">
                <h2>Monthly Sales Report</h2>
                <button class="popupClose" onclick="closeReport()">✕</button>
            </div>

            <div class="popupBody" id="reportBody">
                <div style="text-align:center; color:#aaa; padding:20px;">Loading…</div>
            </div>

        </div>
    </div>

    <script>

        //Toggle edit row visibility
        function showEdit() {
            document.getElementById('editRow').style.display = 'block';
        }

        //Hides the edit row
        function cancelEdit() {
            document.getElementById('editRow').style.display = 'none';
            setMsg('', '');
        }

        //Sets the response message below the listing section
        function setMsg(text, type) {
            const el = document.getElementById('listingMsg');
            el.textContent  = text;
            el.className    = 'responseMsg ' + type;
        }

        //Sends a create listing request to the server
        function createListing() {

            //Getting the inputs from the user
            const productID = document.getElementById('newProduct').value;
            const price = document.getElementById('newPrice').value;
            const qty = document.getElementById('newQty').value;

            //Client side validation
            if (!productID || !price || !qty) {
                setMsg('Please fill in all fields.', 'msgError');
                return;
            }

            //Creating the request for the POST request
            const fd = new FormData();
            fd.append('action', 'createListing');
            fd.append('productID', productID);
            fd.append('price', price);
            fd.append('quantity', qty);

            //Sends a POST request 
            fetch('', { method: 'POST', body: fd })

                //Gets response from server and then analyses it
                .then(r => r.json())
                .then(data => {

                    //If the create listing was succesful then the page is reloaded
                    if (data.status === 'success') {
                        location.reload();
                    } 
                    
                    //Otherwise an error message appears
                    else {
                        setMsg(data.message || 'Error creating listing.', 'msgError');
                    }
                })

                //Catches any errors
                .catch(() => setMsg('Network error.', 'msgError'));
        }

        //Sends a update listing request 
        function saveListing() {

            //Getting the users inputs
            const price = document.getElementById('editPrice').value;
            const qty = document.getElementById('editQty').value;
            const listingID = document.getElementById('editRow').dataset.listingId;

            //Client side validation
            if (!price || qty === '') {
                setMsg('Please fill in all fields.', 'msgError');
                return;
            }

            //Creating a form for the POST request
            const fd = new FormData();
            fd.append('action',    'updateListing');
            fd.append('listingID', listingID);
            fd.append('price',     price);
            fd.append('quantity',  qty);

            //Sends a POST request to the server
            fetch('', { method: 'POST', body: fd })

                //Gets the response and analyses it
                .then(r => r.json())
                .then(data => {

                    //If the update was successful then the update values are displayed
                    if (data.status === 'success') {

                        //Updating elements to the new values
                        document.getElementById('dispPrice').textContent = '£' + parseFloat(price).toFixed(2);
                        document.getElementById('dispQty').textContent   = parseInt(qty) + ' units';
                        document.getElementById('editRow').style.display = 'none';

                        //Success message
                        setMsg('Listing updated successfully.', 'msgSuccess');
                    } 
                    
                    //If it failed then the user is told so
                    else {
                        setMsg(data.message || 'Error updating listing.', 'msgError');
                    }
                })

                //Catches any errors
                .catch(() => setMsg('Network error.', 'msgError'));
        }

        //Sends a delete listing request to the server
        function deleteListing() {

            //Confirmation messaa=ge
            if (!confirm('Are you sure you want to remove your listing?')) return;

            //Creates a form for the POST
            const fd = new FormData();
            fd.append('action', 'deleteListing');

            //Fetches a POST request to the server
            fetch('', { method: 'POST', body: fd })

                //Gets the response and analyses it
                .then(r => r.json())
                .then(data => {

                    //If the deletion was successful the page is reloaded
                    if (data.status === 'success') {
                        location.reload();
                    } 
                    
                    //Otherwise the user is told it failed
                    else {
                        setMsg(data.message || 'Error removing listing.', 'msgError');
                    }
                })

                //Catches any errors
                .catch(() => setMsg('Network error.', 'msgError'));
        }

        //Opens the sales report popup and fetches the data
        function openReport() {

            //Accesses the report elements
            document.getElementById('reportOverlay').classList.add('active');
            document.getElementById('reportBody').innerHTML = '<div style="text-align:center;color:#aaa;padding:20px;">Loading…</div>';

            //Builds the form for the POST request
            const fd = new FormData();
            fd.append('action', 'salesReport');

            //Sends a POST request to the server
            fetch('', { method: 'POST', body: fd })

                //Gets the servers response
                .then(r => r.json())
                .then(data => {

                    //If it failed than the user is told it couldnt load it
                    if (data.status !== 'success') {
                        document.getElementById('reportBody').innerHTML = '<div class="popupNoListing">Could not load report.</div>';
                        return;
                    }

                    //Builds and injects the report HTML into the popup
                    document.getElementById('reportBody').innerHTML = `
                        <div class="popupMonth">${data.monthLabel}</div>

                        ${data.productName === 'N/A' ? '<div class="popupNoListing">You have no active listing this month.</div>' : `
                            <div class="reportGrid">
                                <div class="reportCard">
                                    <div class="reportCardLabel">Product</div>
                                    <div class="reportCardValue" style="font-size:17px;">${data.productName}</div>
                                    <div class="reportCardSub">Your listed product</div>
                                </div>
                                <div class="reportCard">
                                    <div class="reportCardLabel">Units in Stock</div>
                                    <div class="reportCardValue">${data.listed}</div>
                                    <div class="reportCardSub">Currently listed</div>
                                </div>
                                <div class="reportCard">
                                    <div class="reportCardLabel">Units Sold</div>
                                    <div class="reportCardValue">${data.unitsSold}</div>
                                    <div class="reportCardSub">This month</div>
                                </div>
                                <div class="reportCard">
                                    <div class="reportCardLabel">Revenue</div>
                                    <div class="reportCardValue">£${parseFloat(data.revenue).toFixed(2)}</div>
                                    <div class="reportCardSub">Gross income</div>
                                </div>
                            </div>
                        `}
                    `;
                })

                //This catches any errors
                .catch(() => {
                    document.getElementById('reportBody').innerHTML = '<div class="popupNoListing">Network error.</div>';
                });
        }

        //Closes the sales report popup
        function closeReport() {
            document.getElementById('reportOverlay').classList.remove('active');
        }

        //This closes the monthly sales report
        function closeReportOnOverlay(event) {

            //If the report is open, it closes it
            if (event.target === document.getElementById('reportOverlay')) {
                closeReport();
            }
        }

        //This logs the supplier out
        function logOut() {

            //Clears local and server storage
            sessionStorage.clear();
            localStorage.clear();

            //POST request to supplier log out
            fetch("supplierLogOut.php", { method: "POST" })

                //Gets the response
                .then(r => r.json())
                .then(() => {

                    //Redirects them to the welcome page
                    window.location.href = "../MainPages/WelcomePage.html";
                })

                //Redirects to home page even if error occurs 
                .catch(() => {
                    window.location.href = "../MainPages/WelcomePage.html";
                });
        }

    </script>

</body>
</html>