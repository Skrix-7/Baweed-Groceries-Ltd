<?php
session_start();
include "../dbConnector.local.php";

header('Content-Type: text/plain; charset=utf-8');

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo "Method Not Allowed";
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    http_response_code(400);
    echo "missing_fields";
    exit;
}

$stmt = $conn->prepare(
    "SELECT customerID, FullName, Password 
     FROM customers 
     WHERE FullName = ? 
     LIMIT 1"
);

if (!$stmt) {
    http_response_code(500);
    echo "prepare_failed";
    exit;
}

$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(401);
    echo "userNotFound";
    exit;
}

$user = $result->fetch_assoc();

// TODO: use password_verify() — plain text passwords are dangerous
if ($user['Password'] !== $password) {
    http_response_code(401);
    echo "incorrectPassword";
    exit;
}

// Login success
session_regenerate_id(true);
$_SESSION['customerID']   = $user['customerID'];
$_SESSION['customerName'] = $user['FullName'];

http_response_code(200);
echo "success";
// You can also do: header("Location: /BaweedGroceries/MainPages/StoreHomePage.php");
exit;