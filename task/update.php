<?php

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Disposition, Content-Type, Content-Length, Accept-Encoding");
header("Content-type:application/json");

// Include database connection
include_once('../config/database.php');

// Include the authentication functions
include('auth.php');

// Define a key
const KEY = 'emcifilorp';

// Get the token from the request header
$token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

// Verify token
$user_id = Token::Verify($token, KEY);

if ($user_id){
    $input = json_decode(file_get_contents('php://input'), true);

    $task_id = $input['task_id'];
    $status = $input['status'];
    
    $sql = "UPDATE tasks SET status='$status' WHERE task_id=$task_id";
    
    if ($conn->query($sql) === TRUE) {
        echo json_encode(["Task updated successfully"]);
    } else {
        http_response_code(400);
        echo json_encode(["Error updating task: " . $conn->error]);
    }
} else {
    http_response_code(401); 
    echo json_encode(["error" => "Unauthorized access"]);
}



$conn->close();
?>
