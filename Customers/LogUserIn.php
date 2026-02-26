<?php
session_start();
include "../dbConnector.local.php";

//Only allow POST requests
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    echo "not_post";
    exit;
}

//Retrieve username and password from POST data, or set to null if not provided
$username = $_POST['username'] ?? null;
$password = $_POST['password'] ?? null;

//Query database for username only
$stmt = $conn->prepare("SELECT customerID, FullName, Password FROM customers WHERE FullName=? LIMIT 1");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

//If the search finds no users with the username it responds with userNotFound and exits
if ($result->num_rows === 0) {
    echo "userNotFound";
    exit;
}

//Fetch the user data from the result
$user = $result->fetch_assoc();

//Compares the password entered to the password of the user in the database.
if ($user['Password'] !== $password) {
    echo "incorrectPassword";
    exit;
}

//Login successful, store session
$_SESSION['customerID'] = $user['customerID'];
$_SESSION['FullName'] = $user['FullName'];

//Tells the login page it was successful so it can redirect the user to the store home page
echo "success";

//Close the database connection
$stmt->close();
$conn->close();
