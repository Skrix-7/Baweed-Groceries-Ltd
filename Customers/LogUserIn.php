<?php
session_start();
include "../dbConnector.local.php";

header('Content-Type: text/plain; charset=utf-8');

//Ensures it will only respond to POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo "Method Not Allowed";
    exit;
}

//Retrieves the username and password from the POST data
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

//Ensures that both fields are provided
if ($username === '' || $password === '') {
    http_response_code(400);
    echo "missing_fields";
    exit;
}

//Prepares the SQL statement to find the user by username
$stmt = $conn->prepare("SELECT customerID, FullName, Password FROM customers WHERE FullName = ? LIMIT 1");

//If the statement preparation fails, return an error
if (!$stmt) {
    http_response_code(500);
    echo "prepareError";
    exit;
}

//This binds the username to the SQL statement and executes it
$stmt->bind_param("s", $username);
$stmt->execute();

//Retrieves the result of the query
$result = $stmt->get_result();

//If no user is found with the given username, return an error
if ($result->num_rows === 0) {
    http_response_code(401);
    echo "userNotFound";
    exit;
}

//Fetches the user data as an associative array
$user = $result->fetch_assoc();

//Compares the user password to the one they entered. If it doesn't match, return an error
if ($user['Password'] !== $password) {
    http_response_code(401);
    echo "incorrectPassword";
    exit;
}

//If the password is correct it regenerates the session id
session_regenerate_id(true);

//Binding the users id to the session variable
$_SESSION['customerID']   = $user['customerID'];

http_response_code(200);
echo "success";
exit;