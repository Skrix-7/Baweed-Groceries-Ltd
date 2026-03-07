<?php
session_start();

//Only admins can access this page
if (!isset($_SESSION['adminID'])) {
    header("Location: adminLogin.php");
    exit;
}

$adminUsername = htmlspecialchars($_SESSION['adminUser'] ?? 'Admin');
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
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
            background: linear-gradient(to right, #db8e08, #c77e09);
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
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 50px 28px;
            gap: 16px;
        }

        .mainText {
            font-size: 22px;
            font-weight: 700;
            color: #2a2a2a;
            margin-bottom: 10px;
            text-align: center;
        }

        .featureGrid {
            display: flex;
            flex-direction: column;
            gap: 16px;
            width: 100%;
            max-width: 520px;
        }

        .featureBtn {
            width: 100%;
            padding: 18px 24px;
            border: none;
            border-radius: 12px;
            font-family: "Segoe UI", Arial, sans-serif;
            font-size: 16px;
            font-weight: 600;
            color: white;
            cursor: pointer;
            text-align: left;
            display: flex;
            align-items: center;
            gap: 14px;
            box-shadow: 0 5px 14px rgba(0,0,0,0.15);
            transition: all 0.25s ease;
            background: linear-gradient(to right, #db8e08, #c77e09);
        }

        .featureBtn:hover {
            transform: translateY(-3px);
            filter: brightness(1.07);
            box-shadow: 0 10px 22px rgba(0,0,0,0.22);
        }

        .featureBtn:active {
            transform: translateY(-1px);
            filter: brightness(0.97);
        }

        .featureIcon {
            font-size: 22px;
            flex-shrink: 0;
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

        .footer p {
            margin: 0;
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

                <h1>Admin Dashboard</h1>

            </div>

            <div class="bannerRight">

                <p>Status: Admin</p>
                <p>Welcome: <?= $adminUsername ?></p>

                <div class="bannerButtons">
                    <button onclick="logOut()" class="shopButton logOutButton">Log Out</button>
                </div>

            </div>
        </div>

        <div class="content">

            <div class="mainText">What would you like to manage?</div>

            <div class="featureGrid">

                <button class="featureBtn" onclick="window.location.href='authorizeSupplier.php'">
                    <span class="featureIcon"></span>
                    Authorize New Supplier
                </button>

                <button class="featureBtn" onclick="window.location.href='inventoryManagement.php'">
                    <span class="featureIcon"></span>
                    Inventory Management
                </button>

                <button class="featureBtn" onclick="window.location.href='listingManagement.php'">
                    <span class="featureIcon"></span>
                    Listing Management
                </button>

                <button class="featureBtn" onclick="window.location.href='monthlySalesReport.php'">
                    <span class="featureIcon"></span>
                    Monthly Sales Report
                </button>

            </div>

        </div>

        <div class="footer">
            <p>© 2026 Baweed Groceries Ltd. All Rights Reserved.</p>
        </div>

    </div>

    <script>

        //Logs the admin out and redirects to the welcome page
        function logOut() {

            //Clears local and server storage
            sessionStorage.clear();
            localStorage.clear();

            //POST request to admin log out.php
            fetch("adminLogOut.php", {
                method: "POST"
            })

            //Gets the resonse
            .then(response => response.json())
            .then(data => {

                //If log out was successful than they are returned to the welcome page
                if (data.status === "success") {
                    window.location.href = "../MainPages/WelcomePage.html";
                } 
                
                //Otherwise the error message is displayed
                else {
                    console.error("Logout failed:", data.message);
                }
            })

            //Catches any errors
            .catch(err => console.error("Logout error:", err));
        }

    </script>

</body>
</html>