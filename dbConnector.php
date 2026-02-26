<?php
$conn = new mysqli("localhost", "yourUsername", "yourPassword", "yourDatabaseName");

if ($conn->connect_error) {
    die("Connection Failed". $conn->connect_error);
}
?>