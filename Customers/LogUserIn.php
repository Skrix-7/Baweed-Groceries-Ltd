<?php
session_start();
include "../dbConnector.local.php";

// Only allow POST
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    echo "not_post";
    exit;
}

// Grab POST data
$username = $_POST['username'] ?? null;
$password = $_POST['password'] ?? null;

if (!$username || !$password) {
    echo "empty_fields";
    exit;
}

// Query database for username only
$stmt = $conn->prepare("SELECT customerID, FullName, Password FROM customers WHERE FullName=? LIMIT 1");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "user_not_found";
    exit;
}

$user = $result->fetch_assoc();

// Check password (plain text for now)
if ($user['Password'] !== $password) {
    echo "wrong_password";
    exit;
}

// Login successful, store session
$_SESSION['customerID'] = $user['customerID'];
$_SESSION['FullName'] = $user['FullName'];

echo "success";

$stmt->close();
$conn->close();
