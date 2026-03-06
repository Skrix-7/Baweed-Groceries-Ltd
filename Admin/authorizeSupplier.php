<?php
session_start();
include "../dbConnector.local.php";

//Prevents regular users using it
if (!isset($_SESSION['adminID'])) {
    header("Location: adminLogin.php");
    exit;
}

$successMessage = "";
$errorMessage   = "";

//Only allows POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $phoneNumber = trim($_POST['phoneNumber'] ?? '');

    //Server side validation
    if (empty($fullname) || empty($email) || empty($password) || empty($address) || empty($phoneNumber)) {
        $errorMessage = "All fields must be filled in.";

    } elseif (strlen($fullname) > 100) {
        $errorMessage = "Full name must be under 100 characters.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
        $errorMessage = "Please enter a valid email address.";

    } elseif (strlen($password) < 8 || strlen($password) > 50) {
        $errorMessage = "Password must be between 8 and 50 characters.";

    } elseif (!preg_match('/^\d{11}$/', $phoneNumber)) {
        $errorMessage = "Phone number must be exactly 11 digits.";

    } elseif (strlen($address) > 255) {
        $errorMessage = "Address must be under 255 characters.";

    } else {

        //Check if email is already registered
        $stmt = $conn->prepare("SELECT supplierID FROM suppliers WHERE Email = ?");
        $stmt->bind_param("s", $email);

        //Executes and gets the results of the statement
        $stmt->execute();
        $stmt->store_result();

        //If there is a result denies creation
        if ($stmt->num_rows > 0) {
            $errorMessage = "A supplier with this email already exists.";
            $stmt->close();
        } 
        
        //If user doesnt exist it creates them
        else {

            $stmt->close();

            //Insert statement for the database
            $stmt = $conn->prepare("
                INSERT INTO suppliers (Fullname, Email, Password, Address, PhoneNumber)
                VALUES (?, ?, ?, ?, ?)
            ");

            //Binds the users entrys to the statement
            $stmt->bind_param("sssss", $fullname, $email, $password, $address, $phoneNumber);

            //If the statement was succesfully executed then the user is created
            if ($stmt->execute()) {
                $successMessage = "Supplier '{$fullname}' has been successfully authorized.";
            } 
            
            //Error message if creation fails
            else {
                $errorMessage = "Failed to create supplier. Please try again.";
            }

            //Closes the connection
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Authorize Supplier</title>
    <link rel='icon' type='image/x-icon' href='../Images/LogoImages/favicon.ico'>

    <style>

        body {
            display: flex;
            justify-content: center;
            align-items: center;

            background: linear-gradient(to right, #363333, #2f2b2b);
            min-height: 100vh;

            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
        }

        .formBox {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 24px 20px;

            width: 340px;
            border-radius: 12px;
            background-color: #f0f0f0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }

        .formTitle {
            font-size: 19px;
            text-align: center;
            font-family: Arial, sans-serif;
            font-weight: bold;
            margin-bottom: 4px;
        }

        .formSubtitle {
            font-size: 12px;
            color: #777;
            text-align: center;
            margin-bottom: 14px;
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

        .submitButton {
            background-color: #f5a623;
            margin-top: 10px;
        }

        .submitButton:hover {
            filter: brightness(0.9);
        }

        .responseMessage {
            margin-top: 10px;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 18px;
        }

        .responseMessage p {
            margin: 0;
            font-size: 13px;
            font-weight: 600;
            text-align: center;
        }

        .errorText  { color: #d93025; }
        .successText { color: #1e8c3a; }

        .backLink {
            margin-top: 10px;
            font-size: 12px;
            color: #6b6b6b;
            text-decoration: none;
            opacity: 0.7;
            transition: 0.2s ease;
        }

        .backLink:hover {
            opacity: 1;
            text-decoration: underline;
        }

        .successView {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
            padding: 10px 0;
            text-align: center;
            width: 100%;
        }

        .successHeading {
            font-size: 17px;
            font-weight: bold;
            color: #1e8c3a;
            margin: 0;
        }

        .successDetail {
            font-size: 13px;
            color: #555;
            margin: 0;
            word-break: break-word;
        }

    </style>
</head>

<body>

    <div class="formBox">

        <?php if ($successMessage): ?>

            <div class="successView">

                <div class="successHeading">Supplier Authorized!</div>
                <div class="successDetail"><?= htmlspecialchars($successMessage) ?></div>

                <button class="formButton submitButton" onclick="window.location.href='authorizeSupplier.php'">Add Another Supplier</button>

                <a href="adminHomePage.php" class="backLink">← Back to Dashboard</a>

            </div>

        <?php else: ?>

            <div class="formTitle"><p>Authorize New Supplier</p></div>
            <div class="formSubtitle">Creates a supplier login account</div>

            <?php if ($errorMessage): ?>

                <div class="responseMessage">
                    <p class="errorText"><?= htmlspecialchars($errorMessage) ?></p>
                </div>

            <?php endif; ?>

            <div class="entryFields">

                <input type="text"     id="fullname"    placeholder="Full Name..."          maxlength="100">
                <input type="text"     id="email"       placeholder="Email Address..."      maxlength="100">
                <input type="password" id="password"    placeholder="Set a Password..."     maxlength="50">
                <input type="text"     id="address"     placeholder="Business Address..."   maxlength="255">
                <input type="text"     id="phoneNumber" placeholder="Phone Number (11 digits)..." maxlength="11">

            </div>

            <button type="button" class="formButton secondaryButton" onclick="togglePassword()">Show Password</button>
            <button type="button" class="formButton submitButton"    onclick="submitForm()">Authorize Supplier</button>

            <a href="adminHomePage.php" class="backLink">← Back to Dashboard</a>

            <div class="responseMessage">
                <p class="errorText" id="jsError"></p>
            </div>

        <?php endif; ?>

    </div>

    <script>

        //Toggle password visibility
        function togglePassword() {

            var field  = document.getElementById('password');
            var button = event.target;

            //This reveals the password
            if (field.type === "password") {
                field.type = "text";
                button.textContent = "Hide Password";
            } 
            
            //This hides the password
            else {
                field.type = "password";
                button.textContent = "Show Password";
            }
        }

        //Client side validation
        function submitForm() {

            const errorEl = document.getElementById("jsError");
            errorEl.textContent = "";

            //Getting the results from the entry fields
            const fullname = document.getElementById("fullname").value.trim();
            const email = document.getElementById("email").value.trim();
            const password = document.getElementById("password").value.trim();
            const address = document.getElementById("address").value.trim();
            const phoneNumber = document.getElementById("phoneNumber").value.trim();

            // Empty check
            if (!fullname || !email || !password || !address || !phoneNumber) {
                errorEl.textContent = "All fields must be filled in.";
                return;
            }

            //Full name length
            if (fullname.length > 100) {
                errorEl.textContent = "Full name must be under 100 characters.";
                return;
            }

            //Basic email check
            if (!email.includes("@") || (!email.includes(".com") && !email.includes(".co.uk") && !email.includes(".org"))) {
                errorEl.textContent = "Please enter a valid email address.";
                return;
            }

            //Password length
            if (password.length < 8 || password.length > 50) {
                errorEl.textContent = "Password must be between 8 and 50 characters.";
                return;
            }

            //Phone number, 11 digits only
            if (!/^\d{11}$/.test(phoneNumber)) {
                errorEl.textContent = "Phone number must be exactly 11 digits.";
                return;
            }

            //Address length
            if (address.length > 255) {
                errorEl.textContent = "Address must be under 255 characters.";
                return;
            }

            //Builds and submits form
            const form = document.createElement("form");
            form.method = "POST";
            form.action = "";

            //The fields ids
            const fields = { fullname, email, password, address, phoneNumber };

            //Gets the entryes for each field
            Object.entries(fields).forEach(([name, value]) => {
                const input   = document.createElement("input");
                input.type    = "hidden";
                input.name    = name;
                input.value   = value;
                form.appendChild(input);
            });

            //Submits the form
            document.body.appendChild(form);
            form.submit();
        }

    </script>

</body>
</html>