<?php
session_start();
include "../dbConnector.local.php";

//This ensures it will only act if it is a POST request
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    echo "not_post";
    exit;
}

//This assigns variable names to the data given to it from the javascript
$username = $_POST['username'] ?? null;
$password = $_POST['password'] ?? null;
$email    = $_POST['email'] ?? null;
$phone    = $_POST['phoneNumber'] ?? null;
$card     = $_POST['cardNumber'] ?? null;
$pin      = $_POST['cardPin'] ?? null;
$address  = $_POST['address'] ?? null;

//Converting the pin to a integer
$pinNum = intval($pin);
$phoneNum = $phone;
$cardNum = $card;

//Prepare SQL query
$stmt = $conn->prepare("INSERT INTO customers (FullName, Password, Email, PhoneNumber, CardNumber, Pin, Address) VALUES (?, ?, ?, ?, ?, ?, ?)");

//Ensures the statement was prepared correctly
if (!$stmt) {
    echo "prepare_failed: {$conn->error}";
    exit;
}

//Bind variables to the fields
$stmt->bind_param(
    "sssssss",
    $username,  
    $password,   
    $email,     
    $phoneNum,
    $cardNum,   
    $pinNum,     
    $address     
);

//Executing query
if ($stmt->execute()) {
    echo "success";
} else {
    echo "execute_failed: {$stmt->error}";
}

//Closing Connections
$stmt->close();
$conn->close();
