<?php

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Disposition, Content-Type, Content-Length, Accept-Encoding");
header("Content-type:application/json");

// Include database connection
include_once('../config/database.php');

// Include authentication
require('auth.php'); 

// Extract input data
$input = json_decode(file_get_contents('php://input'), true);

$username = $input['username']; // The input field is 'username'

// Check if the username is provided
if (!$username) {
    http_response_code(400);
    echo json_encode(["error" => "Username is required"]);
    exit();
}

// Prepare and execute SQL query to retrieve user details by username
$sql_get_user = "SELECT id, CONCAT(first_name, ' ', last_name) AS Fullname, email, phone_number, username, KC_id, refresh_token FROM users WHERE username = ?";
$stmt_get_user = $conn->prepare($sql_get_user);
$stmt_get_user->bind_param("s", $username);
$stmt_get_user->execute();
$result = $stmt_get_user->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    // Return user details as JSON response
    echo json_encode($user);
} else {
    // Provide a message if the user is not found
    http_response_code(404);
    echo json_encode(["error" => "User not found"]);
}


$stmt_get_user->close();
$conn->close();
?>
