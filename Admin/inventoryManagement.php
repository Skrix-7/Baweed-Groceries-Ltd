<?php
session_start();
include "../dbConnector.local.php";

//Only allows admin access
if (!isset($_SESSION['adminID'])) {
    header("Location: adminLogin.php");
    exit;
}

//Retrieves current date
$monthStart = date('Y-m-01 00:00:00');
$monthEnd   = date('Y-m-t 23:59:59');
$monthLabel = date('F Y');

//Gets the products sold this month
$outgoingMap = [];
$stmt = $conn->prepare("
    SELECT t.productID, p.Name, SUM(t.Quantity) AS totalOut
    FROM transactions t
    INNER JOIN products p ON t.productID = p.productID
    WHERE t.PurchaseDate BETWEEN ? AND ?
    GROUP BY t.productID, p.Name
    ORDER BY p.Name ASC
");
$stmt->bind_param("ss", $monthStart, $monthEnd);

//Executes statement and gets the results
$stmt->execute();
$result = $stmt->get_result();

//Adds the results to a 2d array
while ($row = $result->fetch_assoc()) {
    $outgoingMap[$row['productID']] = [
        'name' => $row['Name'],
        'out'  => (int)$row['totalOut'],
    ];
}
$stmt->close();

//Retrieves products incoming this month
$incomingMap = [];
$result = $conn->query("
    SELECT p.productID, SUM(l.Quantity) AS totalIn
    FROM listings l
    INNER JOIN products p ON l.productID = p.productID
    GROUP BY p.productID
");

//Adds them to a 2d array
while ($row = $result->fetch_assoc()) {
    $incomingMap[$row['productID']] = (int)$row['totalIn'];
}

$allProducts = [];

//Gets the product types
$result = $conn->query("SELECT productID, Name FROM products ORDER BY Name ASC");

//Adds the products to a list
while ($row = $result->fetch_assoc()) {

    $pid  = $row['productID'];
    $name = $row['Name'];

    //Mapping all the arrays togehter
    $in  = $incomingMap[$pid]  ?? 0;
    $out = isset($outgoingMap[$pid]) ? $outgoingMap[$pid]['out'] : 0;
    $net = $in - $out;

    $allProducts[] = [
        'productID' => $pid,
        'name'      => $name,
        'in'        => $in,
        'out'       => $out,
        'net'       => $net,
    ];
}

//Gets the total amount incoming, outgoing and net flow
$totalIn  = array_sum(array_column($allProducts, 'in'));
$totalOut = array_sum(array_column($allProducts, 'out'));
$totalNet = $totalIn - $totalOut;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Inventory Management</title>
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
            background: linear-gradient(to right, #b37200, #8a5500);
            color: white;
        }

        .bannerLeft {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .shopBanner img { transition: 0.25s ease; cursor: pointer; }
        .shopBanner img:hover { transform: scale(1.06); }

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
            text-align: center;
            gap: 6px;
        }

        .bannerRight p { margin: 0; font-size: 14px; font-weight: 500; }

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
            padding: 0 14px;
            font-size: 13px;
            font-weight: 600;
            color: white;
            cursor: pointer;
            box-shadow: 0 4px 8px rgba(0,0,0,0.25);
            transition: all 0.25s ease;
            white-space: nowrap;
        }

        .shopButton:hover {
            transform: translateY(-3px);
            filter: brightness(1.08);
            box-shadow: 0 8px 18px rgba(0,0,0,0.35);
        }

        .logOutButton    { background: linear-gradient(to right, #e74c3c, #c0392b); }
        .dashboardButton { background: linear-gradient(to right, #555, #333); }

        .content {
            flex: 1;
            padding: 32px 40px;
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .pageHeader {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
        }

        .pageTitle {
            font-size: 22px;
            font-weight: 700;
            color: #2a2a2a;
        }

        .monthBadge {
            background: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        .summaryRow {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }

        .summaryCard {
            background: white;
            border-radius: 12px;
            padding: 20px 22px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.07);
            display: flex;
            flex-direction: column;
            gap: 6px;
            border-top: 4px solid transparent;
        }

        .summaryCard.incoming { border-color: #28a745; }
        .summaryCard.outgoing { border-color: #dc3545; }
        .summaryCard.net      { border-color: #f5a623; }

        .summaryLabel {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            color: #999;
        }

        .summaryValue {
            font-size: 32px;
            font-weight: 700;
            color: #2a2a2a;
        }

        .summaryCard.incoming .summaryValue { color: #1e7e34; }
        .summaryCard.outgoing .summaryValue { color: #bd2130; }
        .summaryCard.net      .summaryValue { color: #b37200; }

        .summaryDesc {
            font-size: 12px;
            color: #aaa;
        }

        .tableWrap {
            background: white;
            border-radius: 14px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.07);
            overflow: hidden;
        }

        .tableHeader {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px 22px 14px;
            border-bottom: 1px solid #f0f0f0;
            flex-wrap: wrap;
            gap: 10px;
        }

        .tableTitle {
            font-size: 16px;
            font-weight: 700;
            color: #2a2a2a;
        }

        .searchInput {
            padding: 7px 14px;
            border: 1px solid #ddd;
            border-radius: 20px;
            font-size: 13px;
            font-family: "Segoe UI", Arial, sans-serif;
            width: 200px;
            transition: 0.2s ease;
            outline: none;
        }

        .searchInput:focus {
            border-color: #f5a623;
            box-shadow: 0 0 0 2px rgba(245,166,35,0.18);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead th {
            background: #fafafa;
            padding: 12px 22px;
            text-align: left;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.7px;
            text-transform: uppercase;
            color: #999;
            border-bottom: 1px solid #efefef;
            cursor: pointer;
            user-select: none;
            white-space: nowrap;
        }

        thead th:hover { color: #b37200; }

        thead th .sortIcon {
            display: inline-block;
            margin-left: 4px;
            opacity: 0.4;
            font-size: 10px;
        }

        thead th.sorted .sortIcon { opacity: 1; color: #f5a623; }

        tbody tr {
            border-bottom: 1px solid #f5f5f5;
            transition: background 0.15s ease;
        }

        tbody tr:last-child { border-bottom: none; }
        tbody tr:hover      { background: #fffbf2; }

        tbody td {
            padding: 13px 22px;
            font-size: 14px;
            color: #333;
        }

        .productName { font-weight: 600; }

        .pill {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            min-width: 40px;
            text-align: center;
        }

        .pillIn  { background: #e8f5e9; color: #1e7e34; }
        .pillOut { background: #ffebee; color: #bd2130; }

        .netPositive { color: #1e7e34; font-weight: 700; }
        .netNegative { color: #bd2130; font-weight: 700; }
        .netZero     { color: #aaa;    font-weight: 600; }

        .noResults {
            text-align: center;
            padding: 40px;
            color: #bbb;
            font-size: 14px;
        }

        .footer {
            background-color: #1e1e1e;
            color: #ccc;
            text-align: center;
            padding: 20px 0;
            font-size: 13px;
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

            <h1>Inventory Management</h1>

        </div>

        <div class="bannerRight">

            <p>Admin: <?= htmlspecialchars($_SESSION['adminUser'] ?? 'Admin') ?></p>

            <div class="bannerButtons">
                <button onclick="window.location.href='adminHomePage.php'" class="shopButton dashboardButton">← Dashboard</button>
                <button onclick="logOut()" class="shopButton logOutButton">Log Out</button>
            </div>

        </div>
    </div>

    <div class="content">

        <div class="pageHeader">
            <div class="pageTitle">Monthly Stock Flow</div>
            <div class="monthBadge"><?= $monthLabel ?></div>
        </div>

        <div class="summaryRow">

            <div class="summaryCard incoming">
                <div class="summaryLabel">Total Incoming</div>
                <div class="summaryValue"><?= number_format($totalIn) ?></div>
                <div class="summaryDesc">Units currently in stock</div>
            </div>

            <div class="summaryCard outgoing">
                <div class="summaryLabel">Total Outgoing</div>
                <div class="summaryValue"><?= number_format($totalOut) ?></div>
                <div class="summaryDesc">Units sold this month</div>
            </div>

            <div class="summaryCard net">
                <div class="summaryLabel">Net Flow</div>
                <div class="summaryValue"><?= ($totalNet >= 0 ? '+' : '') . number_format($totalNet) ?></div>
                <div class="summaryDesc">Incoming minus outgoing</div>
            </div>

        </div>

        <div class="tableWrap">

            <div class="tableHeader">
                <div class="tableTitle">Product Breakdown</div>
                <input class="searchInput" type="text" placeholder="Filter products…" oninput="filterTable(this.value)" id="filterInput">
            </div>

            <table id="inventoryTable">

                <thead>
                    <tr>
                        <th onclick="sortTable(0)" id="th-0">Product <span class="sortIcon">↕</span></th>
                        <th onclick="sortTable(1)" id="th-1">Incoming <span class="sortIcon">↕</span></th>
                        <th onclick="sortTable(2)" id="th-2">Outgoing <span class="sortIcon">↕</span></th>
                        <th onclick="sortTable(3)" id="th-3">Net Flow <span class="sortIcon">↕</span></th>
                    </tr>
                </thead>

                <tbody id="tableBody">

                    <?php foreach ($allProducts as $p): ?>

                        <?php
                            $netClass = 'netZero';
                            if ($p['net'] > 0) $netClass = 'netPositive';
                            if ($p['net'] < 0) $netClass = 'netNegative';
                            $netDisplay = ($p['net'] > 0 ? '+' : '') . number_format($p['net']);
                        ?>

                        <tr>
                            <td class="productName"><?= htmlspecialchars($p['name']) ?></td>
                            <td><span class="pill pillIn"><?= number_format($p['in']) ?></span></td>
                            <td><span class="pill pillOut"><?= number_format($p['out']) ?></span></td>
                            <td class="<?= $netClass ?>"><?= $netDisplay ?></td>
                        </tr>

                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="noResults" id="noResults" style="display:none;">
                No products match your search.
            </div>

        </div>

    </div>

    <div class="footer">
        <p>© 2026 Baweed Groceries Ltd. All Rights Reserved.</p>
    </div>

</div>

    <script>

        //This allows the table to be filtered
        function filterTable(query) {

            //Accessing the elements as variables
            const rows = document.querySelectorAll('#tableBody tr');
            const q = query.toLowerCase().trim();
            let visible = 0;

            //Loops through each row and lowercases the contents
            rows.forEach(row => {
                const name = row.cells[0].textContent.toLowerCase();

                //If the row the user is looking for is there it is displayed
                if (name.includes(q)) {
                    row.style.display = '';
                    visible++;
                } 
                
                //Otherwise it says none
                else {
                    row.style.display = 'none';
                }
            });

            document.getElementById('noResults').style.display = visible === 0 ? 'block' : 'none';
        }

        //Sorts the columns variables
        let sortCol = -1;
        let sortAsc = true;

        //This sorts the table based on its fields
        function sortTable(colIndex) {

            const tbody = document.getElementById('tableBody');
            const rows  = Array.from(tbody.querySelectorAll('tr'));

            //Allows it to be changed between desc and asc
            if (sortCol === colIndex) {
                sortAsc = !sortAsc;
            } 
            
            else {
                sortCol = colIndex;
                sortAsc = true;
            }

            //This updates the headers
            document.querySelectorAll('thead th').forEach((th, i) => {
                th.classList.remove('sorted');
                th.querySelector('.sortIcon').textContent = '↕';
            });

            const activeTh = document.getElementById(`th-${colIndex}`);

            //Arrows allowing it to be flipped between asc and desc
            activeTh.classList.add('sorted');
            activeTh.querySelector('.sortIcon').textContent = sortAsc ? '↑' : '↓';

            //This sorts them
            rows.sort((a, b) => {

                let aVal = a.cells[colIndex].textContent.trim().replace(/[+,]/g, '');
                let bVal = b.cells[colIndex].textContent.trim().replace(/[+,]/g, '');

                //Sorting the number colums
                if (colIndex > 0) {
                    return sortAsc ? parseFloat(aVal) - parseFloat(bVal) : parseFloat(bVal) - parseFloat(aVal);
                }

                //Sorting the text columns
                return sortAsc ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
            });

            rows.forEach(row => tbody.appendChild(row));
        }

        //This logs the user out
        function logOut() {

            //Clears server and client side storage
            sessionStorage.clear();
            localStorage.clear();

            //Sends a POST request to adminLogOut.php
            fetch("adminLogOut.php", { method: "POST" })

                //Gets the response and analyses it
                .then(r => r.json())
                .then(data => {

                    //If the log out was successful then the user is returned to the welcome page
                    if (data.status === "success") {
                        window.location.href = "../MainPages/WelcomePage.html";
                    }
                });
        }

    </script>
    
</body>
</html>