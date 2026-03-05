<?php
session_start();

//Clear admin session data
$_SESSION = [];
session_destroy();

//Return JSON so the JS fetch handler can redirect cleanly
header('Content-Type: application/json');
echo json_encode(['status' => 'success']);