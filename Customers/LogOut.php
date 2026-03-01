<?php
session_start();

//Ensuers it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
    exit;
}

//Destroy PHP session, clearing all session data
$_SESSION = [];
session_destroy();

//Clears the session cookies if they exist
if (ini_get("session.use_cookies")) {

    //Get current session cookie parameters
    $params = session_get_cookie_params();

    //Deletes the session cookie
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

//Returns success to the store home page
echo json_encode(["status" => "success"]);
exit;