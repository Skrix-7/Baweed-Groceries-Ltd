<?php
session_start();
include "../dbConnector.local.php";

//Only allows admin access
if (!isset($_SESSION['adminID'])) {
    header("Location: adminLogin.php");
    exit;
}

//Retrives current date
$monthStart = date('Y-m-01 00:00:00');
$monthEnd   = date('Y-m-t 23:59:59');
$monthLabel = date('F Y');

//Gets the sales figures per product
$productSales = [];
$stmt = $conn->prepare("
    SELECT p.productID, p.Name,
           SUM(t.Quantity)   AS totalUnits,
           SUM(t.TotalPrice) AS totalRevenue
    FROM transactions t
    INNER JOIN products p ON t.productID = p.productID
    WHERE t.PurchaseDate BETWEEN ? AND ?
    GROUP BY t.productID, p.Name
    ORDER BY totalRevenue DESC
");
$stmt->bind_param("ss", $monthStart, $monthEnd);

//Executes query and gets the results
$stmt->execute();
$result = $stmt->get_result();

//Maps the results to product sales
while ($row = $result->fetch_assoc()) {
    $productSales[] = [
        'productID' => $row['productID'],
        'name'      => $row['Name'],
        'units'     => (int)$row['totalUnits'],
        'revenue'   => (float)$row['totalRevenue'],
    ];
}
$stmt->close();

//Gets the payment method figures
$paymentBreakdown = [];
$stmt = $conn->prepare("
    SELECT PaymentMethod,
           COUNT(DISTINCT transactionID) AS txCount,
           SUM(Quantity)                 AS units,
           SUM(TotalPrice)               AS revenue
    FROM transactions
    WHERE PurchaseDate BETWEEN ? AND ?
    GROUP BY PaymentMethod
");
$stmt->bind_param("ss", $monthStart, $monthEnd);

//Executes query and gets the results
$stmt->execute();
$result = $stmt->get_result();

//Adds the results to the array
while ($row = $result->fetch_assoc()) {
    $paymentBreakdown[] = $row;
}
$stmt->close();

//Gets the total figures for units sold, product types sold and revenue
$totalUnits   = array_sum(array_column($productSales, 'units'));
$totalRevenue = array_sum(array_column($productSales, 'revenue'));
$totalProducts = count($productSales);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Monthly Sales Report</title>
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

        .summaryCard.revenue  { border-color: #f5a623; }
        .summaryCard.units    { border-color: #2d7ef7; }
        .summaryCard.products { border-color: #28a745; }

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
        }

        .summaryCard.revenue  .summaryValue { color: #b37200; }
        .summaryCard.units    .summaryValue { color: #1c5ed6; }
        .summaryCard.products .summaryValue { color: #1e7e34; }

        .summaryDesc { font-size: 12px; color: #aaa; }

        .paymentRow {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 14px;
        }

        .paymentCard {
            background: white;
            border-radius: 12px;
            padding: 16px 20px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.07);
            display: flex;
            flex-direction: column;
            gap: 4px;
            border-left: 4px solid #ddd;
        }

        .paymentCard.online    { border-color: #2d7ef7; }
        .paymentCard.in_person { border-color: #28a745; }

        .paymentMethod {
            font-size: 13px;
            font-weight: 700;
            text-transform: capitalize;
            color: #444;
            letter-spacing: 0.3px;
        }

        .paymentRevenue {
            font-size: 22px;
            font-weight: 700;
            color: #2a2a2a;
        }

        .paymentMeta {
            font-size: 12px;
            color: #aaa;
        }

        .sectionTitle {
            font-size: 15px;
            font-weight: 700;
            color: #555;
            text-transform: uppercase;
            letter-spacing: 0.7px;
            margin-bottom: -8px;
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

        .productNameCell { font-weight: 600; }

        .pill {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            min-width: 40px;
            text-align: center;
        }

        .pillUnits   { background: #e3f0ff; color: #1c5ed6; }
        .pillRevenue { background: #fff3cd; color: #856404; }

        .barWrap {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .bar {
            height: 8px;
            border-radius: 4px;
            background: linear-gradient(to right, #f5a623, #c4841a);
            min-width: 2px;
            transition: width 0.3s ease;
        }

        .noResults {
            text-align: center;
            padding: 40px;
            color: #bbb;
            font-size: 14px;
        }

        .emptyState {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 60px 20px;
            color: #bbb;
            gap: 10px;
            font-size: 15px;
        }

        .emptyState span { font-size: 48px; }

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

            <h1>Monthly Sales Report</h1>

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
            <div class="pageTitle">Sales Overview</div>
            <div class="monthBadge"><?= $monthLabel ?></div>
        </div>

        <div class="summaryRow">

            <div class="summaryCard revenue">
                <div class="summaryLabel">Total Revenue</div>
                <div class="summaryValue">£<?= number_format($totalRevenue, 2) ?></div>
                <div class="summaryDesc">Gross income this month</div>
            </div>

            <div class="summaryCard units">
                <div class="summaryLabel">Total Units Sold</div>
                <div class="summaryValue"><?= number_format($totalUnits) ?></div>
                <div class="summaryDesc">Items sold this month</div>
            </div>

            <div class="summaryCard products">
                <div class="summaryLabel">Products Sold</div>
                <div class="summaryValue"><?= $totalProducts ?></div>
                <div class="summaryDesc">Distinct product types sold</div>
            </div>

        </div>

        <?php if (!empty($paymentBreakdown)): ?>

            <div class="sectionTitle">By Payment Method</div>

            <div class="paymentRow">
                <?php foreach ($paymentBreakdown as $pm): ?>

                    <?php $label   = str_replace('_', ' ', $pm['PaymentMethod']);
                          $cssKey  = strtolower($pm['PaymentMethod']); ?>

                    <div class="paymentCard <?= htmlspecialchars($cssKey) ?>">

                        <div class="paymentMethod"><?= htmlspecialchars(ucwords($label)) ?></div>
                        <div class="paymentRevenue">£<?= number_format((float)$pm['revenue'], 2) ?></div>
                        
                        <div class="paymentMeta">
                            <?= number_format((int)$pm['units']) ?> units &nbsp;·&nbsp;
                            <?= number_format((int)$pm['txCount']) ?> transaction<?= $pm['txCount'] != 1 ? 's' : '' ?>
                        </div>

                    </div>
                <?php endforeach; ?>

            </div>
        <?php endif; ?>

        <div class="sectionTitle">Product Breakdown</div>

        <div class="tableWrap">
            <div class="tableHeader">

                <div class="tableTitle">All Products
                    <span style="font-size:12px; color:#aaa; font-weight:400; margin-left:8px;"> sorted by revenue </span>
                </div>

                <input class="searchInput" type="text" placeholder="Filter products…" oninput="filterTable(this.value)">

            </div>

            <?php if (empty($productSales)): ?>

                <div class="emptyState">
                    <p>No sales recorded for <?= $monthLabel ?>.</p>
                </div>

            <?php else: ?>

                <table id="salesTable">

                    <thead>
                        <tr>
                            <th onclick="sortTable(0)" id="th-0">Product      <span class="sortIcon">↕</span></th>
                            <th onclick="sortTable(1)" id="th-1">Units Sold   <span class="sortIcon">↕</span></th>
                            <th onclick="sortTable(2)" id="th-2">Revenue      <span class="sortIcon">↕</span></th>
                            <th onclick="sortTable(3)" id="th-3">% of Revenue <span class="sortIcon">↕</span></th>
                        </tr>
                    </thead>

                    <tbody id="tableBody">

                        <?php foreach ($productSales as $p): ?>

                            <?php
                                $pct    = $totalRevenue > 0 ? round(($p['revenue'] / $totalRevenue) * 100, 1) : 0;
                                $barPct = $totalRevenue > 0 ? ($p['revenue'] / $totalRevenue) * 100 : 0;
                            ?>

                            <tr>
                                <td class="productNameCell"><?= htmlspecialchars($p['name']) ?></td>
                                <td><span class="pill pillUnits"><?= number_format($p['units']) ?></span></td>
                                <td><span class="pill pillRevenue">£<?= number_format($p['revenue'], 2) ?></span></td>
                                <td>

                                    <div class="barWrap">
                                        <div class="bar" style="width: <?= min($barPct, 100) ?>%"></div>
                                        <span style="font-size:13px; color:#888; white-space:nowrap;"><?= $pct ?>%</span>
                                    </div>

                                </td>
                            </tr>

                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="noResults" id="noResults" style="display:none;">
                    No products match your search.
                </div>

            <?php endif; ?>
        </div>
    </div>

    <div class="footer">
        <p>© 2026 Baweed Groceries Ltd. All Rights Reserved.</p>
    </div>

</div>

    <script>

        //Allows admins to search for specific products
        function filterTable(query) {

            const rows    = document.querySelectorAll('#tableBody tr');
            const q       = query.toLowerCase().trim();
            let   visible = 0;

            //Iterates through the rows to look for users item
            rows.forEach(row => {

                const name = row.cells[0].textContent.toLowerCase();

                //If they find the users item then it is made visible
                if (name.includes(q)) {
                    row.style.display = '';
                    visible++;
                } 
                
                //Otherwise it displays none
                else {
                    row.style.display = 'none';
                }
            });

            document.getElementById('noResults').style.display = visible === 0 ? 'block' : 'none';
        }

        //Variables for sorting table
        let sortCol = -1;
        let sortAsc = true;

        //This sorts the tables
        function sortTable(colIndex) {

            //Accessing the elements
            const tbody = document.getElementById('tableBody');
            const rows  = Array.from(tbody.querySelectorAll('tr'));

            //Switches between desc and asc order
            if (sortCol === colIndex) {
                sortAsc = !sortAsc;
            } 
            
            else {
                sortCol = colIndex;
                sortAsc = true;
            }

            //Icon for switching between unsorted and sorted
            document.querySelectorAll('thead th').forEach(th => {
                th.classList.remove('sorted');
                th.querySelector('.sortIcon').textContent = '↕';
            });

            //The icon for switching from asc to desc
            const activeTh = document.getElementById(`th-${colIndex}`);
            activeTh.classList.add('sorted');
            activeTh.querySelector('.sortIcon').textContent = sortAsc ? '↑' : '↓';

            //This sorts the table
            rows.sort((a, b) => {

                //This removes any special characters that may interfere with sorting
                let aVal = a.cells[colIndex].textContent.trim().replace(/[£,%]/g, '').replace(/,/g, '');
                let bVal = b.cells[colIndex].textContent.trim().replace(/[£,%]/g, '').replace(/,/g, '');

                //Sorts the number columns
                if (colIndex === 0) {
                    return sortAsc ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
                }

                //This sorts the string columns
                return sortAsc ? parseFloat(aVal) - parseFloat(bVal) : parseFloat(bVal) - parseFloat(aVal);
            });

            //Adds the row to the table
            rows.forEach(row => tbody.appendChild(row));
        }

        //This logs the admin out
        function logOut() {

            //Clears client and server storage
            sessionStorage.clear();
            localStorage.clear();

            //Sends a POST request to adminLogOut.php
            fetch("adminLogOut.php", { method: "POST" })

                //Gets its response 
                .then(r => r.json())
                .then(data => {

                    //If it was successful then the admin is returned to the welcome page        
                    if (data.status === "success") {
                        window.location.href = "../MainPages/WelcomePage.html";
                    }
                });
        }

    </script>

</body>
</html>