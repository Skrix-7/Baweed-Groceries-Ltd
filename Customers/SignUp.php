<?php
session_start();
include "../dbConnector.local.php";
?>

<!DOCTYPE html>

<head>
    <title>Sign Up</title>
    <link rel='icon' type='image/x-icon' href='/LogoImages/favicon.ico'>

    <style>

        body {
            display: flex;
            justify-content: center;
            align-items: center;

            background: linear-gradient(to right, #363333, #2f2b2b);
            height: 100vh;

            font-family: Arial, sans-serif;
        }

        .signUpBar {
            display: flex;
            align-items: column;
            justify-content: center;
        }

        .signUpBox {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;

            width: 320px;
            border-radius: 12px;
            background-color: #f0f0f0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }

        .signUpText {
            font-size: 19px;
            text-align: center;
            font-family: Arial, sans-serif;
            font-weight: bold;
        }

        .entryFields {
            display: flex;
            flex-direction: column;
            width: 100%;
            gap: 5px;
        }

        .entryFields input {
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #dcdcdc;
            width: 100%;
            box-sizing: border-box;
            font-size: 14px;
            transition: 0.2s ease;
        }

        .entryFields input:focus {
            outline: none;
            border-color: #2d7ef7;
            box-shadow: 0 0 0 2px rgba(45,126,247,0.15);
        }

        .formButton {
            width: 100%;
            height: 38px;

            border: none;
            border-radius: 8px;

            font-size: 14px;
            font-family: "Segoe UI", Arial, sans-serif;
            font-weight: 600;
            letter-spacing: 0.3px;

            color: white;
            cursor: pointer;
            transition: 0.25s ease;
        }

        .formButton:active {
            transform: scale(0.98);
        }

        .secondaryButton {
            background-color: #4a4a4a;
            margin-top: 12px;
        }

        .secondaryButton:hover {
            filter: brightness(1.15);
        }

        .signUpButton {
            background-color: #2d7ef7;
            margin-top: 10px;
        }

        .signUpButton:hover {
            filter: brightness(0.9);
        }

        .responseMessage {
            margin-top: 8px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .responseMessage p {
            margin: 0;
            color: #d93025;
            font-size: 13px;
            font-weight: 600;
            text-align: center;
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        .responseMessage p.show {
            opacity: 1;
        }

        .skipLink {
            margin-top: 6px;
            font-size: 12px;
            color: #6b6b6b;
            text-decoration: none;
            opacity: 0.7;
            transition: 0.2s ease;
        }

        .skipLink:hover {
            opacity: 1;
            text-decoration: underline;
        }

    </style>

</head>

<body>

    <div class="signUpBar">
        <div class="signUpBox">

            <div class="signUpText">
                <p> Enter a Username & Password: </p>
            </div>

            <div class="entryFields">
                <label for="username"></label>
                <input type="text" id="username" name="username" placeholder="Enter a Username...">

                <label for="password"></label>
                <input type="password" id="password" name="password" placeholder="Enter a Password...">
            
                <label for="email"></label>
                <input type="text" id="email" name="email" placeholder="Enter Your Email...">

                <label for="phoneNumber"></label>
                <input type="text" id="phoneNumber" name="phoneNumber" placeholder="Enter Your Phone Number...">

                <label for="cardNumber"></label>
                <input type="text" id="cardNumber" name="cardNumber" placeholder="Enter Your Card Number...">

                <label for="cardPin"></label>
                <input type="text" id="cardPin" name="cardPin" placeholder="Enter Your Card Pin...">

                <label for="address"></label>
                <input type="text" id="address" name="address" placeholder="Enter Your Address...">
            </div>
            
            <button type="button" class="formButton secondaryButton" onclick="togglePassword()">Show Password</button>
            <button class="formButton signUpButton" onclick="signUp()">Sign Up</button>

            <a href="/StoreHomePage.php" class="skipLink">Skip</a>

            <div class="responseMessage">
                <p id="response"> </p>
            </div>

        </div>
    </div>

    <script>

        //Making the password viewable to the user
        function togglePassword() {

            //Retriving the users password entry
            var passwordField=document.getElementById('password');
            var button=event.target;

            //Makes the password hidden
            if (passwordField.type=="password") {
                passwordField.type="text";
                button.textContent="Hide Password";
            }

            //Swaps it to viewable
            else {
                passwordField.type="password";
                button.textContent="Show Password";
            }
        }

        //Handles entry validation and database management
        function signUp() {

            //Tests if all fields are valid
            var isValid=true;
            const response = document.getElementById("response");
            response.textContent = "";
            response.classList.remove("show");

            //Ensures Username is not Ridiculously Long
            var username = document.getElementById("username").value.trim();
            if (username.length >40) {
                response.textContent = "Username Too Long.";
                response.classList.add("show");
                isValid=false;
            }

            //Ensures the password is a secure length
            var password = document.getElementById("password").value.trim();
            if (password.length >16 || password.length <8) {
                response.textContent = "Password Must Be Between 8 and 16 Characters Long.";
                response.classList.add("show");
                isValid=false;
            }

            //Email Validation
            var email = document.getElementById("email").value.trim();
            if (validateEmail(email)==false) {
                response.textContent = "Invalid Email.";
                response.classList.add("show");
                isValid=false;
            }

            //Ensures the phone number is a number 11 characters long
            var phoneNum = document.getElementById("phoneNumber").value.trim();
            if (isNaN(phoneNum) || phoneNum.length!==11) {
                response.textContent = "Phone Number Must Be a Number 11 Characters Long.";
                response.classList.add("show");
                isValid=false;
            }

            //Ensures the address isnt longer than the db can handle
            var address = document.getElementById("address").value.trim();
            if (address.length >40) {
                response.textContent = "Address Must Be Less Than 40 Characters Long.";
                response.classList.add("show");
                isValid=false;
            }

            //Ensures the card number is valid
            var cardNum = document.getElementById("cardNumber").value.trim();
            if (isNaN(cardNum) || cardNum.length!==16) {
                response.textContent = "Card Number Must Be a Number 16 Characters Long.";
                response.classList.add("show");
                isValid=false;
            }

            //Ensures the pin number
            var cardPin = document.getElementById("cardPin").value.trim();
            if (isNaN(cardPin) || cardPin.length!==4) {
                response.textContent = "Pin Number Must Be a Number 4 Characters Long.";
                response.classList.add("show");
                isValid=false;
            }
            
            //Empty Field Checking
            if (username=="" || password=="" || email=="" || phoneNum=="" || cardNum=="" || cardPin=="" || address=="") {
                response.textContent = "Field cannot be empty.";
                response.classList.add("show");
                isValid=false;
            }

            //If all are valid then it redirects them
            if (isValid==true) {
                storeDetails(username, password, email, phoneNum, cardNum, cardPin, address);
            }
        }

        //Ensures the email the user enters is valid
        function validateEmail(email) {

            //Checks if the email has a space, @ and a correct domain name
            if (email.includes(" ")) return false;

            if (email.includes("@") && (email.includes(".com") || email.includes(".co.uk") || email.includes(".org"))) {
                return true;
            }

            else {
                return false;
            }
        }

        //This puts the users details into the sql database
        function storeDetails(username, password, email, phoneNum, cardNum, cardPin, address) {

            cardPin = Number(cardPin);

            console.log(username, password, email, phoneNum, cardNum, cardPin, address);

            fetch("StoreUser.php", {
                method: "POST",
                headers: {"Content-Type": "application/x-www-form-urlencoded"},
                body: `username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}&email=${encodeURIComponent(email)}&phoneNumber=${encodeURIComponent(phoneNum)}&cardNumber=${encodeURIComponent(cardNum)}&cardPin=${encodeURIComponent(cardPin)}&address=${encodeURIComponent(address)}`
            })
            .then(response => {
                console.log("Fetch status:", response.status);
                console.log("Content-Type:", response.headers.get("content-type"));
                return response.text();
            })
            .then(data => {
                console.log("Raw response from server:", data);
                
                const trimmed = (data || "").trim();
                
                if (trimmed === "success") {
                    console.log("Success detected – redirecting");
                    window.location.href = "/MainPages/StoreHomePage.php";   // make sure this path is correct!
                } else {
                    let displayMsg = trimmed;
                    if (trimmed.includes("<!DOCTYPE") || trimmed.includes("<html")) {
                        displayMsg = "Server sent back a whole webpage instead of 'success' → likely wrong URL or PHP redirect leak";
                    } else if (!trimmed) {
                        displayMsg = "Empty response (possible silent redirect or no output)";
                    }
                    document.getElementById("response").textContent = displayMsg;
                    document.getElementById("response").classList.add("show");
                }
            })
            .catch(err => {
                console.error("Fetch error:", err);
                document.getElementById("response").textContent = "Fetch failed: " + err.message;
                document.getElementById("response").classList.add("show");
            });
        }

    </script>

</body>
</html>