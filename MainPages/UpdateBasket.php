<?php
session_start();
include("../dbConnector.local.php");

//Get the JSON data from the request body
$data = json_decode(file_get_contents('php://input'), true);

//This is the listing ID and new quantity sent from the frontend
$listingID = $data['listingID'];
$quantity = intval($data['quantity']);

//If the user is logged in uses customer id
if (isset($_SESSION['customerID'])) {
    $identifierField = "customerID";
    $identifierValue = $_SESSION['customerID'];
} 

//If there a guest account then they are identified by their session ID
else {
    $identifierField = "sessionID";
    $identifierValue = session_id();
}

//This is the query to get the maximum available stock of the item
$stmt = $conn->prepare("SELECT Quantity FROM listings WHERE listingID = ?");
$stmt->bind_param("i", $listingID);

//This executes the query and gets the results
$stmt->execute();
$stockResult = $stmt->get_result()->fetch_assoc();

//Retrieves stock and closes the statement
$maxStock = $stockResult['Quantity'] ?? 0;
$stmt->close();

//Ensure requested quantity does not exceed stock
if ($quantity > $maxStock) {
    echo json_encode([
        'status' => 'error',
        'message' => "Cannot exceed available stock of $maxStock."
    ]);
    exit;
}

//If the quantity is 0, remove the item from the basket. Otherwise, update or insert the basket entry.
if ($quantity === 0) {

    //This prepares the SQL statement to delete the basket entry for the given listing ID and user/session identifier
    $stmt = $conn->prepare("DELETE FROM basket WHERE listingID = ? AND $identifierField = ?");
    $stmt->bind_param("is", $listingID, $identifierValue);

    //This executes the query and then closes the statement
    $stmt->execute();
    $stmt->close();
} 

//If the quantity is greater than 0, update or insert the basket entry
else {

    //This is the SQL query to check if the item is already in the basket for the given listing ID and user/session identifier
    $stmt = $conn->prepare("SELECT quantity FROM basket WHERE listingID = ? AND $identifierField = ?");
    $stmt->bind_param("is", $listingID, $identifierValue);

    //This executes the query and gets the result
    $stmt->execute();
    $result = $stmt->get_result();

    //If the item is already in the basket, update the quantity. Otherwise, insert a new entry.
    if ($result->num_rows > 0) {

        //This is the query that updates the quantity of the item in the basket
        $stmt = $conn->prepare("UPDATE basket SET quantity = ? WHERE listingID = ? AND $identifierField = ?");
        $stmt->bind_param("iis", $quantity, $listingID, $identifierValue);

        //This executes the query and then closes the statement
        $stmt->execute();
        $stmt->close();
    } 
    
    //If the item is not already in the basket, insert a new entry with the given quantity
    else {

        //This is the query that inserts a new entry into the basket
        $stmt = $conn->prepare("INSERT INTO basket ($identifierField, listingID, quantity) VALUES (?, ?, ?)");
        $stmt->bind_param("sii", $identifierValue, $listingID, $quantity);

        //This executes the query and then closes the statement
        $stmt->execute();
        $stmt->close();
    }
}

//This is where the contents of the basket are fetched for price summary
$stmt = $conn->prepare("
    SELECT b.quantity, l.Quantity, l.Price, p.Name
    FROM basket b
    INNER JOIN listings l ON b.listingID = l.listingID
    INNER JOIN products p ON l.productID = p.productID
    WHERE $identifierField = ?
");
$stmt->bind_param("s", $identifierValue);

//Executes query and gets results
$stmt->execute();
$result = $stmt->get_result();

//Puts the results in the basket items array
$basketItems = [];
while ($row = $result->fetch_assoc()) {
    $basketItems[] = $row;
}

//This query retrieves the total price of the items
$stmt = $conn->prepare("
    SELECT SUM(b.quantity * l.Price) AS totalPrice
    FROM basket b
    INNER JOIN listings l ON b.listingID = l.listingID
    WHERE $identifierField = ?
");

//Binding the user/session identifier to the query
$stmt->bind_param("s", $identifierValue);

//This executes the query and gets the results
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

//This returns success and the total price of the items in the basket and its contents
$totalPrice = $result['totalPrice'] ?? 0;
echo json_encode([
    'status' => 'success',
    'totalPrice' => $totalPrice,
    'basketItems' => $basketItems
]);