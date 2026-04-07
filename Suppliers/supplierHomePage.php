<?php
session_start();
include("../dbConnector.local.php");

//Ensures only suppliers can access this page
if (!isset($_SESSION['supplierID'])) {
    header("Location: supplierLogInPage.php");
    exit;
}

//Sets the suppliers id from the session stored supplier id
$supplierID = (int)$_SESSION['supplierID'];
$supplierName = htmlspecialchars($_SESSION['supplierName'] ?? 'Supplier');

//Query to display the suppliers balance
$balanceRow = $conn->query("SELECT Balance FROM suppliers WHERE supplierID = $supplierID")->fetch_assoc();
$supplierBalance = number_format((float)($balanceRow['Balance'] ?? 0), 2);

//Checks if the server receives a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    //Sets the content to a JSON to return data to the JS
    header('Content-Type: application/json');
    $action = $_POST['action'];

    //If the user wants to create a listing does this
    if ($action === 'createListing') {

        //Gets the listing values from the POST request
        $productID = (int)($_POST['productID'] ?? 0);
        $price = (float)($_POST['price'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 0);

        //Server side validation
        if ($productID <= 0 || $price <= 0 || $quantity <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid values.']);
            exit;
        }

        //Query to check if the user already has a listing
        $check = $conn->prepare("SELECT listingID FROM listings WHERE supplierID = ?");
        $check->bind_param("i", $supplierID);

        //Executing the query and storing the results
        $check->execute();
        $check->store_result();

        //If there is a result then the JSON returns an error message
        if ($check->num_rows > 0) {
            $check->close();
            echo json_encode(['status' => 'error', 'message' => 'You already have a listing.']);
            exit;
        }
        $check->close();

        //Otherwise, creates a query to create a new listing with the users values
        $stmt = $conn->prepare("INSERT INTO listings (productID, supplierID, Price, Quantity, ListingDate) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("iidi", $productID, $supplierID, $price, $quantity);

        //Executing the results, getting its listing id and then closing the statement
        $stmt->execute();
        $newID = $conn->insert_id;
        $stmt->close();

        //Returns success then exits the server sided script
        echo json_encode(['status' => 'success', 'listingID' => $newID]);
        exit;
    }

    //If the user wants to update their listing then it does this
    if ($action === 'updateListing') {

        //Gets the users listing values from the POST request
        $listingID = (int)($_POST['listingID'] ?? 0);
        $price = (float)($_POST['price'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 0);

        //Server side validation
        if ($listingID <= 0 || $price <= 0 || $quantity < 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid values.']);
            exit;
        }

        //Query to update the users listing with the new values
        $stmt = $conn->prepare("UPDATE listings SET Price = ?, Quantity = ? WHERE listingID = ? AND supplierID = ?");
        $stmt->bind_param("diii", $price, $quantity, $listingID, $supplierID);

        //Executing the query then closing the statement
        $stmt->execute();
        $stmt->close();

        //Returns success then exits the script
        echo json_encode(['status' => 'success']);
        exit;
    }

    //If the user wants to delete their listing then it does this
    if ($action === 'deleteListing') {

        //Query to delete the users listing
        $stmt = $conn->prepare("DELETE FROM listings WHERE supplierID = ?");
        $stmt->bind_param("i", $supplierID);

        //Executing the query then closing the statement
        $stmt->execute();
        $stmt->close();

        //Returns success then exits the script
        echo json_encode(['status' => 'success']);
        exit;
    }

    //If the user wants to create a sales report then it does this
    if ($action === 'salesReport') {

        //Gets the current month and creating variables for the start and end of the month
        $monthStart = date('Y-m-01 00:00:00');
        $monthEnd = date('Y-m-t 23:59:59');
        $monthLabel = date('F Y');

        //Query to get the users listings, and the sales info for it
        $listingRow = null;
        $stmt = $conn->prepare("
            SELECT l.listingID, l.Price, l.Quantity, p.Name
            FROM listings l
            INNER JOIN products p ON l.productID = p.productID
            WHERE l.supplierID = ?
        ");

        //Binding the suppliers id to the query then executing it
        $stmt->bind_param("i", $supplierID);
        $stmt->execute();

        //Getting the results and storing them within an array for analysis, then closing the statement
        $result = $stmt->get_result();
        $listingRow = $result->fetch_assoc();
        $stmt->close();

        //Variables to store the total sales data
        $unitsSold = 0;
        $revenue = 0.0;

        //If the user has sales data then it does this
        if ($listingRow) {

            //Query to get the amount sold and how much they made from their listing
            $lid = (int)$listingRow['listingID'];
            $stmt = $conn->prepare("
                SELECT SUM(Quantity) AS units, SUM(TotalPrice) AS revenue
                FROM transactions
                WHERE listingID = ? AND PurchaseDate BETWEEN ? AND ?
            ");

            //Binding the listing id, and the time period for the monthly report and executing it
            $stmt->bind_param("iss", $lid, $monthStart, $monthEnd);
            $stmt->execute();

            //Getting the results, storing them within an array, then closing the statement
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();

            //Gets the total revenue and units sold
            $unitsSold = (int)($row['units'] ?? 0);
            $revenue = (float)($row['revenue'] ?? 0);
        }

        //Sends back a success message with the corresponding data for the report then exits the script
        echo json_encode([
            'status' => 'success',
            'monthLabel' => $monthLabel,
            'productName' => $listingRow['Name'] ?? 'N/A',
            'listed' => $listingRow['Quantity'] ?? 0,
            'price' => $listingRow['Price'] ?? 0,
            'unitsSold' => $unitsSold,
            'revenue' => $revenue,
        ]);
        exit;
    }

    //If the user wants to view their receipts then it does this
    if ($action === 'supplierReceipts') {

        //Query to get the required sales data and binding the suppliers id to it
        $stmt = $conn->prepare("
            SELECT
                r.receiptID,
                r.createdAt,
                t.transactionID,
                t.Quantity,
                t.TotalPrice,
                t.PurchaseDate,
                t.PaymentMethod,
                p.Name AS productName
            FROM receipts r
            INNER JOIN transactions t ON r.transactionID = t.transactionID
            INNER JOIN listings l ON t.listingID = l.listingID
            INNER JOIN products p ON l.productID = p.productID
            WHERE r.receiptType = 'SUPPLIER'
            AND l.supplierID = ?
            ORDER BY t.PurchaseDate DESC
            LIMIT 50
        ");
        $stmt->bind_param("i", $supplierID);

        //Executing the query and storing the results
        $stmt->execute();
        $result = $stmt->get_result();

        //Storing the results within an array then closing the statement
        $receipts = [];
        while ($row = $result->fetch_assoc()) {
            $receipts[] = $row;
        }
        $stmt->close();

        //Returns a success JSON message, then closes the script
        echo json_encode(['status' => 'success', 'receipts' => $receipts]);
        exit;
    }

    //Otherwise, if the action is unknown a error message is returned.
    echo json_encode(['status' => 'error', 'message' => 'Unknown action.']);
    exit;
}

//Query to get the users listing and the product name for it, then binding the suppliers id to it
$myListing = null;
$stmt = $conn->prepare("
    SELECT l.listingID, l.Price, l.Quantity, l.productID, p.Name AS productName
    FROM listings l
    INNER JOIN products p ON l.productID = p.productID
    WHERE l.supplierID = ?
");
$stmt->bind_param("i", $supplierID);

//eXEcuting the query and getting the results
$stmt->execute();
$result = $stmt->get_result();

//Stores the results in an array and closes the statement
$myListing = $result->fetch_assoc();
$stmt->close();

//An array to store all the products
$allProducts = [];

//Query that iterates through all products within the database and stores them into the array
$result = $conn->query("SELECT productID, Name FROM products ORDER BY Name ASC");
while ($row = $result->fetch_assoc()) {
    $allProducts[] = $row;
}

//An array to store all the products
$marketListings = [];

//Query that iterates through all products within the database and stores them into the array
$stmt = $conn->prepare("
    SELECT l.listingID, l.Price, l.Quantity, p.Name AS productName, s.Fullname AS supplierName
    FROM listings l
    INNER JOIN products p ON l.productID = p.productID
    INNER JOIN suppliers s ON l.supplierID = s.supplierID
    WHERE l.supplierID != ?
    ORDER BY p.Name ASC, l.Price ASC
");

//Binding the users id to the query and executing it
$stmt->bind_param("i", $supplierID);
$stmt->execute();

//Getting the results and storing them within an array for display, then closing the statement
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $marketListings[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Supplier Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel='icon' type='image/x-icon' href='../Images/LogoImages/favicon.ico'>

    <style>

        html {
            font-size: calc(12px + 0.35vw);
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
            width: 92%;
            max-width: 1800px;
            min-height: 900px;

            margin-top: calc(10px + 0.4vw);
            margin-bottom: calc(10px + 0.4vw);
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

            padding: calc(12px + 0.3vw) calc(24px + 0.5vw);
            background: linear-gradient(to right, #c0392b, #96281b);
            color: white;
        }

        .bannerLeft {
            display: flex;
            align-items: center;
            gap: calc(14px + 0.3vw);
        }

        .shopBanner img {
            width: calc(140px + 3vw);
            height: auto;

            transition: 0.25s ease;
            cursor: pointer;
        }

        .shopBanner img:hover {
            transform: scale(1.06);
        }

        .shopBanner h1 {
            font-size: calc(18px + 0.5vw);
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

            gap: calc(4px + 0.1vw);
        }

        .bannerRight p {
            margin: 0;
            font-size: calc(12px + 0.2vw);
            font-weight: 500;
        }

        .bannerButtons {
            margin-top: calc(4px + 0.1vw);
            display: flex;
            gap: calc(10px + 0.2vw);

            justify-content: center;
            align-items: center;
        }

        .shopButton {
            display: inline-flex;
            justify-content: center;
            align-items: center;

            border-radius: 8px;
            border: none;

            height: calc(28px + 0.3vw);
            width: calc(80px + 1.5vw);
            font-size: calc(12px + 0.2vw);
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
            padding: calc(24px + 0.5vw) calc(30px + 0.8vw);
            display: flex;

            flex-direction: column;
            gap: calc(20px + 0.4vw);
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

            padding: calc(14px + 0.2vw) calc(20px + 0.3vw) calc(12px + 0.2vw);
            border-bottom: 1px solid #f0f0f0;
            flex-wrap: wrap;
            gap: 10px;
        }

        .sectionTitle {
            font-size: calc(14px + 0.2vw);
            font-weight: 700;
            color: #2a2a2a;
        }

        .sectionBody {
            padding: calc(18px + 0.4vw) calc(20px + 0.3vw);
        }

        .actionBtn {
            display: inline-flex;
            align-items: center;
            justify-content: center;

            border: none;
            border-radius: 8px;

            padding: 0 calc(14px + 0.3vw);
            height: calc(30px + 0.3vw);
            font-size: calc(11px + 0.2vw);

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

        .btnRed { background: linear-gradient(to right, #c0392b, #96281b); }
        .btnGrey { background: linear-gradient(to right, #636e72, #4a4a4a); }
        .btnGreen { background: linear-gradient(to right, #27ae60, #1e8449); }

        .btnReport { background: linear-gradient(to right, #8e44ad, #6c3483); }
        .btnReceipts { background: linear-gradient(to right, #1c4693, #14356f); }

        .formRow {
            display: flex;
            gap: calc(12px + 0.2vw);
            flex-wrap: wrap;
            align-items: flex-end;
        }

        .formGroup {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .formGroup label {
            font-size: calc(10px + 0.15vw);
            font-weight: 700;
            color: #888;

            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .formGroup select,
        .formGroup input {
            padding: calc(6px + 0.15vw) calc(10px + 0.2vw);
            border: 1px solid #ddd;
            border-radius: 8px;

            font-size: calc(12px + 0.2vw);
            font-family: "Segoe UI", Arial, sans-serif;
            outline: none;

            transition: 0.2s ease;
            min-width: calc(120px + 2vw);
        }

        .formGroup select:focus,
        .formGroup input:focus {
            border-color: #c0392b;
            box-shadow: 0 0 0 2px rgba(192,57,43,0.15);
        }

        .listingDisplay {
            display: flex;
            align-items: center;

            gap: calc(16px + 0.4vw);
            flex-wrap: wrap;
        }

        .listingInfo {
            flex: 1;
            display: flex;

            gap: calc(18px + 0.5vw);
            flex-wrap: wrap;
        }

        .listingField {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .listingFieldLabel {
            font-size: calc(9px + 0.15vw);
            font-weight: 700;

            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #aaa;
        }

        .listingFieldValue {
            font-size: calc(15px + 0.3vw);
            font-weight: 700;
            color: #2a2a2a;
        }

        .listingActions {
            display: flex;
            gap: calc(8px + 0.2vw);
            flex-wrap: wrap;
        }

        .editRow {
            display: flex;
            gap: calc(12px + 0.2vw);
            flex-wrap: wrap;

            align-items: flex-end;
            margin-top: calc(14px + 0.2vw);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead th {
            background: #fafafa;
            padding: calc(9px + 0.2vw) calc(16px + 0.4vw);

            text-align: left;
            font-size: calc(10px + 0.15vw);

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
        tbody tr:hover { background: #fff5f5; }

        tbody td {
            padding: calc(10px + 0.2vw) calc(16px + 0.4vw);
            font-size: calc(12px + 0.2vw);
            color: #333;
        }

        .pill {
            display: inline-block;
            padding: calc(2px + 0.1vw) calc(8px + 0.2vw);
            border-radius: 20px;

            font-size: calc(11px + 0.15vw);
            font-weight: 600;
            text-align: center;
        }

        .pillPrice { background: #fdecea; color: #c0392b; }
        .pillStock { background: #eafaf1; color: #1e8449; }

        .pillLow { background: #fef9e7; color: #b7950b; }
        .pillOut { background: #f9ebea; color: #922b21; }

        .emptyState {
            text-align: center;
            padding: calc(28px + 0.5vw);

            color: #bbb;
            font-size: calc(12px + 0.2vw);
        }

        .responseMsg {
            font-size: calc(11px + 0.15vw);
            font-weight: 600;
            margin-top: 10px;
            height: 18px;
        }

        .msgSuccess { color: #1e8449; }
        .msgError { color: #c0392b; }

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

            width: calc(360px + 8vw);
            max-width: 92vw;

            box-shadow: 0 16px 48px rgba(0,0,0,0.35);
            overflow: hidden;
            animation: popIn 0.2s ease;
        }

        @keyframes popIn {
            from { transform: scale(0.93); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        .popupHeader {
            background: linear-gradient(to right, #c0392b, #96281b);
            color: white;
            padding: calc(14px + 0.3vw) calc(20px + 0.3vw);

            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .popupHeader h2 {
            margin: 0;
            font-size: calc(15px + 0.3vw);
            font-weight: 700;
        }

        .popupClose {
            background: none;
            border: none;
            color: white;
            font-size: calc(18px + 0.3vw);

            cursor: pointer;
            line-height: 1;
            padding: 0;

            opacity: 0.8;
            transition: opacity 0.2s;
        }

        .popupClose:hover { opacity: 1; }

        .popupBody {
            padding: calc(22px + 0.4vw) calc(20px + 0.3vw);
            display: flex;

            flex-direction: column;
            gap: calc(14px + 0.3vw);
        }

        .popupMonth {
            text-align: center;
            font-size: calc(11px + 0.2vw);
            font-weight: 700;

            color: #aaa;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .reportGrid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: calc(10px + 0.3vw);
        }

        .reportCard {
            background: #f8f9fa;
            border-radius: 10px;
            padding: calc(12px + 0.3vw);

            display: flex;
            flex-direction: column;
            gap: 4px;
            border-top: 3px solid #c0392b;
        }

        .reportCardLabel {
            font-size: calc(9px + 0.15vw);
            font-weight: 700;

            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #aaa;
        }

        .reportCardValue {
            font-size: calc(20px + 0.5vw);
            font-weight: 700;
            color: #2a2a2a;
        }

        .reportCardSub {
            font-size: calc(10px + 0.15vw);
            color: #bbb;
        }

        .popupNoListing {
            text-align: center;
            color: #aaa;

            font-size: calc(12px + 0.2vw);
            padding: 20px 0;
        }

        .receiptsPopupBox {
            background: white;
            border-radius: 16px;
            width: calc(480px + 10vw);

            max-width: 94vw;
            max-height: 88vh;
            overflow-y: auto;
            
            box-shadow: 0 16px 48px rgba(0,0,0,0.35);
            overflow: hidden;
            animation: popIn 0.2s ease;

            display: flex;
            flex-direction: column;
        }

        .receiptsPopupHeader {
            background: linear-gradient(to right, #1c4693, #14356f);
            color: white;

            padding: calc(14px + 0.3vw) calc(20px + 0.3vw);
            display: flex;

            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
        }

        .receiptsPopupHeader h2 {
            margin: 0;
            font-size: calc(15px + 0.3vw);
            font-weight: 700;
        }

        .receiptsPopupBody {
            padding: calc(18px + 0.3vw) calc(20px + 0.3vw);
            overflow-y: auto;
            flex: 1;
        }

        .receiptBadge {
            display: inline-block;
            background: #fff8e6;
            color: #854F0B;

            border-radius: 20px;
            padding: 3px 14px;
            font-size: calc(10px + 0.1vw);
            font-weight: 700;

            margin-bottom: 14px;
            border: 1px solid #EF9F27;
            letter-spacing: 0.4px;
        }

        .receiptTable {
            width: 100%;
            border-collapse: collapse;

            font-size: calc(11px + 0.15vw);
            margin-bottom: 16px;
        }

        .receiptTable thead th {
            background: #fafafa;
            padding: calc(8px + 0.1vw) calc(10px + 0.15vw);
            text-align: left;

            font-size: calc(9px + 0.1vw);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #999;

            border-bottom: 1px solid #eee;
            font-weight: 700;
        }

        .receiptTable tbody td {
            padding: calc(9px + 0.1vw) calc(10px + 0.15vw);
            border-bottom: 1px solid #f5f5f5;
            color: #333;
        }

        .receiptTable tbody tr:last-child td {
            border-bottom: none;
        }

        .receiptTable tbody tr:hover {
            background: #f0f4ff;
        }

        .receiptTotal {
            text-align: right;
            font-size: calc(14px + 0.2vw);
            font-weight: 700;

            color: #2a2a2a;
            padding-top: 12px;
            border-top: 2px solid #eee;
        }

        .receiptIDTag {
            font-size: calc(10px + 0.1vw);
            color: #aaa;
        }

        .creditedAmount {
            color: #1e8449;
            font-weight: 700;
        }

        .footer {
            background-color: #1e1e1e;
            color: #ccc;
            text-align: center;

            padding: calc(16px + 0.3vw) 0;
            font-size: calc(11px + 0.15vw);
            letter-spacing: 0.3px;

            border-top: 1px solid #3d3d3d;
            box-shadow: 0 -3px 10px rgba(0,0,0,0.25);
        }

        .footer p { margin: 0; }

        .salesButtons {
            display:flex; 
            gap:calc(8px + 0.2vw); 
            flex-wrap:wrap;
        }

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
                <p>Balance: £<?= $supplierBalance ?></p>

                <div class="bannerButtons">
                    <button onclick="logOut()" class="shopButton logOutButton">Log Out</button>
                </div>

            </div>
        </div>

        <div class="content">
            <div class="sectionCard">
                <div class="sectionHeader">

                    <div class="sectionTitle">My Listing</div>

                    <div class="salesButtons">
                        <button class="actionBtn btnReceipts" onclick="openReceipts()">My Receipts</button>
                        <button class="actionBtn btnReport" onclick="openReport()">Monthly Sales Report</button>
                    </div>

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
                                    <button class="actionBtn btnRed" onclick="deleteListing()">Remove</button>
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
                                    <button class="actionBtn btnGrey" onclick="cancelEdit()">Cancel</button>

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
                                    if ($qty === 0) { $stockClass = 'pillOut'; $stockLabel = 'Out of Stock'; }
                                    elseif ($qty < 10) { $stockClass = 'pillLow'; $stockLabel = $qty . ' left'; }
                                    else { $stockClass = 'pillStock'; $stockLabel = $qty . ' units'; }
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
                <button class="popupClose" onclick="closeReport()">X</button>
            </div>

            <div class="popupBody" id="reportBody">
                <div style="text-align:center; color:#aaa; padding:20px;">Loading…</div>
            </div>

        </div>
    </div>

    <div class="popupOverlay" id="receiptsOverlay" onclick="closeReceiptsOnOverlay(event)">

        <div class="receiptsPopupBox">

            <div class="receiptsPopupHeader">
                <h2>My Receipts</h2>
                <button class="popupClose" onclick="closeReceipts()">X</button>
            </div>

            <div class="receiptsPopupBody" id="receiptsBody">
                <div style="text-align:center; color:#aaa; padding:20px;">Loading…</div>
            </div>

        </div>
    </div>

    <script>

        //Changes the edit button if the user clicks it
        function showEdit() {
            document.getElementById('editRow').style.display = 'block';
        }

        //Cancels the editing mode
        function cancelEdit() {
            document.getElementById('editRow').style.display = 'none';
            setMsg('', '');
        }

        //Sets the message text and color for the listing form
        function setMsg(text, type) {
            const element = document.getElementById('listingMsg');
            element.textContent = text;
            element.className = 'responseMsg ' + type;
        }

        //Function to handle creating new listings
        function createListing() {

            //Gets the user listing values
            const productID = document.getElementById('newProduct').value;
            const price = document.getElementById('newPrice').value.trim();
            const qty = document.getElementById('newQty').value.trim();

            //Server side validation
            if (!productID || !price || !qty) {
                setMsg('Please fill in all fields.', 'msgError');
                return;
            }

            //Ensures price is a number
            if (isNaN(price)) {
                setMsg('Price must be a valid number.', 'msgError');
                return;
            }

            //Ensures quantity doesnt exceed 8
            if (qty.length > 8) {
                setMsg('Quantity must be less than 8 digits.', 'msgError');
                return;
            }

            //Splits the price at the decimal point
            const parts = price.split(".");

            //Ensures theres no more than 2 decimal points
            if (parts.length > 2) {
                setMsg('Invalid price format.', 'msgError');
                return;
            }

            //Accessing the integer and decimal part of the price
            const integerPart = parts[0];
            const decimalPart = parts[1] || "";

            //Ensures theres no more than 2 dp of the price
            if (decimalPart.length > 2) {
                setMsg('Price can have at most 2 decimal places.', 'msgError');
                return;
            }

            //Ensures theres no more than 8 digits to the left of the decimal place
            if (integerPart.length > 8) {
                setMsg('Price is cannot be bigger than 99999999.99.', 'msgError');
                return;
            }

            //Creating a form to send to the server
            const fd = new FormData();
            fd.append('action', 'createListing');
            fd.append('productID', productID);
            fd.append('price', price);
            fd.append('quantity', qty);

            //Sending the request to the server through a POST request
            fetch('', { method: 'POST', body: fd })

                //Gets the response and analyses it
                .then(r => r.json())
                .then(data => {

                    //If the creation was successful then the page is reloaded to update the new listing
                    if (data.status === 'success') {
                        location.reload();
                    } 
                    
                    //Otherwise an error message is shown to the user with the reason for failure if provided
                    else {
                        setMsg(data.message || 'Error creating listing.', 'msgError');
                    }
                })

                //Catches any errors
                .catch(() => setMsg('Network error.', 'msgError'));
        }

        //Function to handle saving listing edits
        function saveListing() {

            //Gets the users new listing values
            const price = document.getElementById('editPrice').value.trim();
            const qty = document.getElementById('editQty').value.trim();
            const listingID = document.getElementById('editRow').dataset.listingId;

            //Server side validation
            if (price === '' || qty === '') {
                setMsg('Please fill in all fields.');
                return;
            }

            if (isNaN(price)) {
                setMsg('Price must be a valid number', 'msgError');
                return;
            }

            if (qty.length > 8) {
                setMsg('Quantity must be less than 8 digits.', 'msgError');
                return;
            }

            //Value to split the price in 2 elements of an array
            const parts = price.split(".");

            // Prevent multiple decimal points
            if (parts.length > 2) {
                setMsg('Invalid price format', 'msgError');
                return;
            }

            //Getting the integer and decimal part
            const integerPart = parts[0];
            const decimalPart = parts[1] || "";

            //Ensures theres no more than 2 dp of the number
            if (decimalPart.length > 2) {
                setMsg('Price can have at most 2 decimal places', 'msgError');
                return;
            }

            //Ensures theres no more than 8 digits to the left of the decimal place
            if (integerPart.length > 8) {
                setMsg('Price is cannot be bigger than 99999999.99', 'msgError');
                return;
            }

            //Building a form for the serv
            const fd = new FormData();
            fd.append('action', 'updateListing');
            fd.append('listingID', listingID);
            fd.append('price', price);
            fd.append('quantity', qty);

            //Sending the form to the server through a POST requsest
            fetch('', { method: 'POST', body: fd })

                //Gets the response and analyses it
                .then(r => r.json())
                .then(data => {

                    //If the update was successful then the listing details are updated
                    if (data.status === 'success') {

                        //Updating the listing details with the new values and hiding the edit row
                        document.getElementById('dispPrice').textContent = '£' + parseFloat(price).toFixed(2);
                        document.getElementById('dispQty').textContent = parseInt(qty) + ' units';
                        document.getElementById('editRow').style.display = 'none';

                        //Success message shown to the user
                        setMsg('Listing updated successfully.', 'msgSuccess');
                    } 
                    
                    //Otherwise an error message is shown to the user
                    else {
                        setMsg(data.message || 'Error updating listing.', 'msgError');
                    }
                })

                //Catches any errors
                .catch(() => setMsg('Network error.', 'msgError'));
        }

        //Function that handles deleting the listing
        function deleteListing() {

            //Asks for confirmation before deleting the listing
            if (!confirm('Are you sure you want to remove your listing?')) return;

            //Builds a form to send to the server
            const fd = new FormData();
            fd.append('action', 'deleteListing');

            //Sends a request to the server then analyses its response
            fetch('', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {

                    //If the request was successful then the page is reloaded to display the changes
                    if (data.status === 'success') {
                        location.reload();
                    } 
                    
                    //Otherwise an error message is displayed to the user
                    else {
                        setMsg(data.message || 'Error removing listing.', 'msgError');
                    }
                })

                //Catches any errors
                .catch(() => setMsg('Network error.', 'msgError'));
        }

        //Function that opens the report popup
        function openReport() {

            //Creates the inital report popup
            document.getElementById('reportOverlay').classList.add('active');
            document.getElementById('reportBody').innerHTML = '<div style="text-align:center;color:#aaa;padding:20px;">Loading…</div>';

            //Builds a form to send to the server
            const fd = new FormData();
            fd.append('action', 'salesReport');

            //Sends a request to the server then analyses its response
            fetch('', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {

                    //If the request failed then an error message is displayed
                    if (data.status !== 'success') {
                        document.getElementById('reportBody').innerHTML = '<div class="popupNoListing">Could not load report.</div>';
                        return;
                    }

                    //HTML to display the report data
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

                //Catches any errors
                .catch(() => {
                    document.getElementById('reportBody').innerHTML = '<div class="popupNoListing">Network error.</div>';
                });
        }

        //Closes the report popup
        function closeReport() {
            document.getElementById('reportOverlay').classList.remove('active');
        }

        //Closes the report if they click off of it
        function closeReportOnOverlay(event) {
            if (event.target === document.getElementById('reportOverlay')) {
                closeReport();
            }
        }

        //Function that opens the receipt popup
        function openReceipts() {

            //Creates the receipt popup box
            document.getElementById('receiptsOverlay').classList.add('active');
            document.getElementById('receiptsBody').innerHTML = '<div style="text-align:center;color:#aaa;padding:20px;">Loading…</div>';

            //Builds a form to send to the server
            const fd = new FormData();
            fd.append('action', 'supplierReceipts');

            //Sends a request to the server then anaylses its response
            fetch('', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {

                    //If the request was not successful then an error message is shown to the user
                    if (data.status !== 'success') {
                        document.getElementById('receiptsBody').innerHTML = '<div class="popupNoListing">Could not load receipts.</div>';
                        return;
                    }

                    //If the receipt array is empty then a message is shown to the user
                    if (data.receipts.length === 0) {
                        document.getElementById('receiptsBody').innerHTML = '<div class="popupNoListing">No receipts found for your listing.</div>';
                        return;
                    }

                    //Variables to handle the data from the server
                    let grandTotal = 0;
                    let rows = '';

                    //Adding the data from the server to the receipt
                    data.receipts.forEach(r => {

                        //Calculates the total price of the transactions
                        const lineTotal = parseFloat(r.TotalPrice);
                        grandTotal += lineTotal;

                        //Gets the date of the transaction and formats it to a readable format
                        const date = new Date(r.PurchaseDate).toLocaleString('en-GB', {
                            day: '2-digit', month: '2-digit', year: 'numeric',
                            hour: '2-digit', minute: '2-digit'
                        });

                        //Adding a row to the receipt for each transaction with the data from the server
                        rows += `
                            <tr>
                                <td>${date}</td>
                                <td>${r.productName}</td>
                                <td>${r.Quantity}</td>
                                <td><span class="creditedAmount">+£${lineTotal.toFixed(2)}</span></td>
                                <td>${r.PaymentMethod.replace('_', ' ')}</td>
                                <td class="receiptIDTag">#${r.receiptID}</td>
                            </tr>
                        `;
                    });

                    //Html to add the receipts table to the popup body with the data from the server
                    document.getElementById('receiptsBody').innerHTML = `
                        <div class="receiptBadge">SUPPLIER RECEIPT</div>
                        <table class="receiptTable">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Product</th>
                                    <th>Qty</th>
                                    <th>Credited</th>
                                    <th>Payment</th>
                                    <th>Receipt ID</th>
                                </tr>
                            </thead>
                            <tbody>${rows}</tbody>
                        </table>
                        <div class="receiptTotal">Total Credited: £${grandTotal.toFixed(2)}</div>
                    `;
                })

                //Catches any errors
                .catch(() => {
                    document.getElementById('receiptsBody').innerHTML = '<div class="popupNoListing">Network error.</div>';
                });
        }

        //Closes the receipt if they click the close button
        function closeReceipts() {
            document.getElementById('receiptsOverlay').classList.remove('active');
        }

        //Closes the receipts popup if they click off of it
        function closeReceiptsOnOverlay(event) {
            if (event.target === document.getElementById('receiptsOverlay')) {
                closeReceipts();
            }
        }

        //Handles logging the user out
        function logOut() {

            //Clearing session and server storage of the user
            sessionStorage.clear();
            localStorage.clear();

            //Sends request to the server
            fetch("supplierLogOut.php", { method: "POST" })

                //Anaylses the response then sends them to the welcome page
                .then(r => r.json())
                .then(() => {
                    window.location.href = "../MainPages/WelcomePage.html";
                })

                //Catches any erros and still sends them to the home page regardless
                .catch(() => {
                    window.location.href = "../MainPages/WelcomePage.html";
                });
        }

    </script>

</body>
</html>