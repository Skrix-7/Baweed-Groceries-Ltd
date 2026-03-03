<?php
session_start();
include "../dbConnector.local.php";

//Gets the items listing ID
$listingID = intval($_POST['listingID']);

//If the user is logged in we use thier customer id, if they are a guest we use the session id to track their basket
$customerID = $_SESSION['customerID'] ?? null;
$sessionID  = session_id();

//Checks if the item is already in the users basket, if it is we update the quantity, if not we add a new entry to the basket
if ($customerID) {

    //This is the query to check the users basket
    $stmt = $conn->prepare("
        SELECT basketID, quantity
        FROM basket
        WHERE customerID = ? AND listingID = ?
    ");

    //Binds the parameters to the query
    $stmt->bind_param("ii", $customerID, $listingID);

} 

//If the user is not logged in we check the basket using the session id instead
else {
    
    //This is the query used to check the users basket using the session id
    $stmt = $conn->prepare("
        SELECT basketID, quantity
        FROM basket
        WHERE sessionID = ? AND listingID = ?
    ");

    //Binds the parameters to the query
    $stmt->bind_param("si", $sessionID, $listingID);
}

//Executes the query and gets the results
$stmt->execute();
$result = $stmt->get_result();

//If the item is already in the basket we update the quantity, if not we add a new entry to the basket
if ($row = $result->fetch_assoc()) {

    //If the item is already in the basket we update the quantity by adding 1 to the existing quantity
    $newQty = $row['quantity'] + 1;

    //This is the query used to update the quantity of the item in the basket
    $update = $conn->prepare(
        "UPDATE basket SET quantity=? WHERE basketID=?"
    );

    //Binds the parameters to the query and executes it
    $update->bind_param("ii", $newQty, $row['basketID']);
    $update->execute();

} 

//If the item is not in the basket we add a new entry to the basket with a quantity of 1
else {

    //If the user is logged in uses customer id
    if ($customerID) {

        //Here is the insert query used to add to the basket
        $insert = $conn->prepare("
            INSERT INTO basket (customerID, listingID, quantity)
            VALUES (?, ?, 1)
        ");

        //Binding the parameters to the query
        $insert->bind_param("ii", $customerID, $listingID);
    } 
    
    //If the user is not logged in we use the session id
    else {

        //This is the insert query used to add to the basket using the session id
        $insert = $conn->prepare("
            INSERT INTO basket (sessionID, listingID, quantity)
            VALUES (?, ?, 1)
        ");

        //Binding the parameters to the query
        $insert->bind_param("si", $sessionID, $listingID);
    }

    //Executes the insert query to add the item to the basket
    $insert->execute();
}

//Returns a success message as a JSON response
echo json_encode(["status"=>"success"]);