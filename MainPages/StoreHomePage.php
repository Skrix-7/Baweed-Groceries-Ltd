<?php
session_start();
include "../dbConnector.local.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Shop</title>
    <link rel='icon' type='image/x-icon' href='../Images/LogoImages/favicon.ico'>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>

        body {
            margin: 0;
            font-family: "Segoe UI", Arial, sans-serif;

            background: linear-gradient(135deg, #555555, #474747, #292929);

            display: flex;
            justify-content: center;
            align-items: flex-start;  
            min-height: 100vh;
            max-width: 100%;
            box-sizing: border-box;
        }

        .mainDiv {
            background-color: #f5f7fa;
            width: 94vw;
            max-width: 2400px;
            min-height: calc(100vh - 32px);
            margin-top: 16px;

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

            padding: 24px 44px;

            background: linear-gradient(to right, #1c4693, #14356f);
            color: white;
        }

        .bannerLeft {
            display: flex;
            align-items: center;
            gap: 28px;
        }

        .shopBanner img {
            transition: 0.25s ease;
            cursor: pointer;
            width: 220px;
            height: auto;
        }

        .shopBanner img:hover {
            transform: scale(1.06);
        }

        .shopBanner h1 {
            font-size: 36px;
            font-weight: 600;
            margin: 0;
            letter-spacing: 0.5px;
        }

        .bannerRight {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 24px;
        }

        .bannerRightInfo {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;

            text-align: center;
            gap: 10px;
        }

        .bannerRight p {
            margin: 0;
            font-size: 20px;
            font-weight: 500;
        }

        .search {
            display: flex;
            justify-content: center;
            margin: 20px 0 16px 0; 
        }

        .searchDiv {
            position: relative;
            width: 600px;
        }
        
        .searchDiv input {
            width: 100%;
            padding: 14px 52px 14px 20px;
            border-radius: 30px;
            border: 1px solid #cfcfcf;
            font-size: 20px;
            box-sizing: border-box;
            transition: 0.2s ease;
        }

        .searchDiv input:focus {
            outline: none;
            border-color: #2d7ef7;
            box-shadow: 0 0 0 3px rgba(45,126,247,0.15);
        }

        .searchDiv button {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: none;
            font-size: 24px;
            cursor: pointer;
            opacity: 0.75;
        }

        .searchDiv button:hover {
            opacity: 1;
        }

        .productMainDiv {
            display: flex;
            justify-content: center;
            margin-bottom: 32px;
            margin-top: 16px;
            flex: 1;
        }

        .productsDiv {
            background-color: white;
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 5px 14px rgba(0,0,0,0.08);
            width: 100%;
            max-width: 2200px; 
            box-sizing: border-box;
        }

        .mainText {
            font-size: 26px;
            font-weight: 600;
            margin-bottom: 24px;
            color: #2a2a2a;
        }

        .products {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
        }

        .productCard {
            background: #ffffff;
            border-radius: 14px;
            padding: 16px;
            text-align: center;
            border: 1px solid #e2e2e2;
            transition: 0.2s ease;
            overflow: hidden;
            font-size: 22px;
            cursor: pointer;
        }

        .productCard:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.15);
        }

        .footer {
            margin-top: auto;

            background-color: #1e1e1e;    
            color: #e4e4e4;

            text-align: center;
            padding: 24px 0;
            font-size: 18px;
            letter-spacing: 0.3px;

            border-top: 1px solid #3d3d3d; 
            box-shadow: 0 -3px 10px rgba(0,0,0,0.25); 
        }

        .footer p {
            color: #ccc;                     
            font-size: 18px;
            text-align: center;
            letter-spacing: 0.5px;
            margin: 0;
        }

        .bannerButtons {
            margin-top: 10px;
            display: flex;
            gap: 20px;             
            justify-content: center;
            align-items: center;
        }

        .shopButton {
            display: inline-flex;
            justify-content: center;
            align-items: center;

            background: linear-gradient(to right, #21f367, #17b851);
            border-radius: 10px;
            border: none;
            height: 48px;
            width: 140px;

            font-size: 20px;
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

        .basketButton {
            padding: 14px 28px;

            background: linear-gradient(135deg, #ffb347, #ff7b00);
            border: none;
            border-radius: 12px;

            color: white;
            font-size: 20px;
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

        .productImg {
            width: 100%;
            height: 220px;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 14px;
        }

        .logOutDiv {
            position: absolute;
            top: 18px;
            right: 28px;
        }

    </style>

</head>
<body>

    <div class="mainDiv">
        <div class="shopBanner">

            <div class="bannerLeft">
                <a href="WelcomePage.html"><img src="../Images/LogoImages/baweedGroceriesLogo.png" width="180"></a>
                <h1>Store</h1>
            </div>

            <div class="bannerRight">

                <button class="basketButton" onclick="window.location.href='Basket.php'">Basket</button>

                <div class="bannerRightInfo">

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

            <div class="logOutDiv"></div>

        </div>

        <div class="search">

            <div class="searchDiv">
                <input type="text" placeholder="Search..." id="searchInput">
                <button type="button" onclick="performSearch()" id="searchButton">🔍</button>
            </div>

        </div>

        <div class="productMainDiv">
            
            <div class="productsDiv">
                <div class="mainText"></div>
                <div class="products"></div>
            </div>

        </div>

        <div class="footer">
            <p>© 2026 Baweed Groceries Ltd. All Rights Reserved.</p>
        </div>

    </div>

    <script>

        //This is a list of all product types with their corresponding image and webpage links. 
        const products = [
            {
                name: "Broccoli",
                image: "../Images/ProductImages/broccoli.avif",
                link: "../Listings/Broccoli.php"
            },
            {
                name: "Carrots",
                image: "../Images/ProductImages/carrot.avif",
                link: "../Listings/Carrot.php"
            },
            {
                name: "Cucumbers",
                image: "../Images/ProductImages/cucumber.avif",
                link: "../Listings/Cucumber.php"
            },
            {
                name: "Garlic",
                image: "../Images/ProductImages/garlic.avif",
                link: "../Listings/Garlic.php"
            },
            {
                name: "Onions",
                image: "../Images/ProductImages/onion.avif",
                link: "../Listings/Onion.php"
            },
            {
                name: "Lettuce",
                image: "../Images/ProductImages/lettuce.avif",
                link: "../Listings/Lettuce.php"
            },
            {
                name: "Peppers",
                image: "../Images/ProductImages/pepper.avif",
                link: "../Listings/Pepper.php"
            },
            {
                name: "Tomatoes",
                image: "../Images/ProductImages/tomato.avif",
                link: "../Listings/Tomato.php"
            },
            {
                name: "Potatoes",
                image: "../Images/ProductImages/potato.avif",
                link: "../Listings/Potato.php"
            }
        ];

        //This binds the enter key to the search bar for easier searching
        var input = document.getElementById("searchInput");

        input.addEventListener("keypress", function(event) {
            if (event.key === "Enter") {
                event.preventDefault();
                performSearch();
            }
        });

        //This displays all the products onto the page
        function displayProducts(productList) {

            //This clears the products container before displaying the new products to prevent duplicates when searching
            const container = document.querySelector(".products");
            container.innerHTML = "";

            //This for loops through each product
            productList.forEach(product => {

                //Creating a new card icon for each product
                const card = document.createElement("div");
                card.className = "productCard";

                //This adds the product image and name to the card
                card.innerHTML = `
                    <img class="productImg" src="${product.image}" alt="${product.name}">
                    <div class="productName">${product.name}</div>
                `;

                //This adds a click event listener to the card that redirects the user to the product's webpage when clicked
                card.addEventListener("click", () => {
                    window.location.href = product.link;
                });

                //This adds the card to the products container
                container.appendChild(card);
            });
        }

        //This is where the database is searched for the users food item
        function performSearch() {

            //Gets the search input
            const query = document.getElementById("searchInput").value.trim().toLowerCase();

            //If the search is empty, display all products and clear the heading
            if (query === "") {
                displayProducts(products);
                document.querySelector(".mainText").textContent = "";
                return;
            }

            //Filters the products array to only include products whose name contains the query
            const filtered = products.filter(product =>
                product.name.toLowerCase().includes(query)
            );

            //Displays the filtered products
            displayProducts(filtered);

            //Updates the heading to reflect the search results
            const rawQuery = document.getElementById("searchInput").value.trim();
            if (filtered.length === 0) {
                document.querySelector(".mainText").textContent = `No results found for "${rawQuery}"`;
            } 
            
            //If it finds results then it is displayed
            else {
                document.querySelector(".mainText").textContent = `Results for "${rawQuery}"`;
            }
        }

        //This logs the user in
        function logIn() {
           window.location.href="../Customers/LogIn.php";
        }

        //This signs the user up
        function signUp() {
            window.location.href="../Customers/SignUp.php";
        }

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

            //Catches any errors
            .catch(err => console.error("Error logging out:", err));
        }

        //This runs the display products function on page load to show all products by default
        displayProducts(products);

    </script>
</body>
</html>