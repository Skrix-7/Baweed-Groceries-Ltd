<?php
session_start();
include("../dbConnector.local.php");

//Redirect if already logged in as admin
if (isset($_SESSION['adminID'])) {
    header("Location: adminHomePage.php");
    exit;
}

$errorMessage = "";

//Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    //Retrieving the users entries
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    //If they entered nothing then the error message is displayed
    if (empty($username) || empty($password)) {
        $errorMessage = "Fields cannot be empty.";
    } 
    
    //Otherwise the login process beings
    else {

        //This is the SQL query to get the credentials of the admin
        $stmt = $conn->prepare("SELECT adminID, Password FROM admin WHERE Username = ?");
        $stmt->bind_param("s", $username);

        //This executes the query and gets the results
        $stmt->execute();
        $stmt->bind_result($adminID, $storedPassword);

        //Finalises the fetch and closes the connection
        $stmt->fetch();
        $stmt->close();

        //Compares entered password to stored password
        $passwordValid = false;
        if ($password === $storedPassword) {
            $passwordValid = true;
        }

        //If the comparison was correct then they are logged in
        if ($passwordValid) {
            $_SESSION['adminID']   = $adminID;
            $_SESSION['adminUser'] = $username;
            header("Location: adminHomePage.php");
            exit;
        } 
        
        //Otherwise they are told that the username/password was incorrect
        else {
            $errorMessage = "Invalid username or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Login Page</title>
    <link rel='icon' type='image/x-icon' href='../Images/LogoImages/favicon.ico'>

    <style>

        body {
            display: flex;
            justify-content: center;
            align-items: center;

            background: linear-gradient(to right, #363333, #2f2b2b);
            height: 100vh;

            font-family: Arial, sans-serif;
        }

        .loginBox {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;

            width: 320px;
            border-radius: 12px;
            background-color: #f0f0f0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }

        .loginText {
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
            border-color: #f5a623;
            box-shadow: 0 0 0 2px rgba(245,166,35,0.2);
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

        .loginButton {
            background-color: #f5a623;
            margin-top: 10px;
        }

        .loginButton:hover {
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

    <div class="loginBox">

        <div class="loginText">
            <p>Admin Login</p>
        </div>

        <div class="entryFields">

            <label for="username"></label>
            <input type="text" id="username" placeholder="Enter Your Username...">

            <label for="password"></label>
            <input type="password" id="password" placeholder="Enter Your Password...">

        </div>

        <button type="button" class="formButton secondaryButton" onclick="togglePassword()">Show Password</button>
        <button class="formButton loginButton" onclick="handleLogin()">Log In</button>

        <a href="../MainPages/WelcomePage.html" class="skipLink">← Return to Store</a>

        <div class="responseMessage">
            <p id="response"><?= htmlspecialchars($errorMessage) ?></p>
        </div>

    </div>

    <script>

        //Toggle password visibility
        function togglePassword() {

            //Gets the password entry field as a variable
            var passwordField = document.getElementById('password');
            var button = event.target;

            //If the password field type is password then it changes it to text, revealing it
            if (passwordField.type === "password") {
                passwordField.type = "text";
                button.textContent = "Hide Password";
            } 
            
            //If the password field type is on text, it changes it to password, hiding it
            else {
                passwordField.type = "password";
                button.textContent = "Show Password";
            }
        }

        //Validate then POST
        function handleLogin() {

            //Gets the response message as a variable
            const response = document.getElementById("response");
            response.textContent = "";

            //Retrieving users inputs
            var username = document.getElementById("username").value.trim();
            var password = document.getElementById("password").value.trim();

            //If the fields are empty then the user is told that they are
            if (username === "" || password === "") {
                response.textContent = "Fields cannot be empty.";
                return;
            }

            //Build and submit a hidden form so PHP receives the POST data
            const form = document.createElement("form");
            form.method = "POST";
            form.action = "";

            const uField   = document.createElement("input");
            uField.type    = "hidden";
            uField.name    = "username";
            uField.value   = username;

            const pField   = document.createElement("input");
            pField.type    = "hidden";
            pField.name    = "password";
            pField.value   = document.getElementById("password").value;

            form.appendChild(uField);
            form.appendChild(pField);
            document.body.appendChild(form);
            form.submit();
        }

    </script>

</body>
</html>