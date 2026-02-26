<?php
session_start();
include "dbConnector.local.php";
?>

<!DOCTYPE html>

<head>
    <title>Log In</title>
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
                <p> Enter Your Username & Password: </p>
            </div>

            <div class="entryFields">
                <label for="username"></label>
                <input type="text" id="username" name="username" placeholder="Enter Username...">

                <label for="password"></label>
                <input type="password" id="password" name="password" placeholder="Enter Password...">
            </div>
            
            <button type="button" class="formButton secondaryButton" onclick="togglePassword()">Show Password</button>
            <button class="formButton signUpButton" onclick="checkDetails()">Log In</button>

            <a href="/StoreHomePage.php" class="skipLink">Skip</a>

            <div class="responseMessage">
                <p id="response"></p>
            </div>

        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleButton = event.currentTarget;

            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                toggleButton.textContent = "Hide Password";
            } else {
                passwordInput.type = "password";
                toggleButton.textContent = "Show Password";
            }
        }

        function checkDetails() {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            const responseEl = document.getElementById("response");

            if (!username || !password) {
                responseEl.textContent = "All fields are required.";
                responseEl.classList.add("show");
                return;
            }

            fetch("/LogUserIn.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}`
            })
            .then(res => res.text())
            .then(data => {
                switch(data) {
                    case "success":
                        window.location.href = "/StoreHomePage.php";
                        break;
                    case "empty_fields":
                        responseEl.textContent = "All fields are required.";
                        responseEl.classList.add("show");
                        break;
                    case "user_not_found":
                        responseEl.textContent = "No account found with that username.";
                        responseEl.classList.add("show");
                        break;
                    case "wrong_password":
                        responseEl.textContent = "Incorrect password.";
                        responseEl.classList.add("show");
                        break;
                    default:
                        responseEl.textContent = "Server error: " + data;
                        responseEl.classList.add("show");
                }
            })
            .catch(err => {
                responseEl.textContent = "Server connection error.";
                responseEl.classList.add("show");
            });
        }

    </script>

</body>
</html>