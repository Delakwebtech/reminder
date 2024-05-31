<?php

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Disposition, Content-Type, Content-Length, Accept-Encoding");
header("Content-type:application/json");

// Include database connection
include_once('../config/database.php');

 // Include the authentication script
include('auth.php');

// Define a key
const KEY = 'emcifilorp';

// Get the token from the request header
$token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

if ($token) {
    // Vefity token
    $user_id = Token::Verify($token, KEY);
    if ($user_id !== null) {
        $sql = "SELECT * FROM tasks";
        $result = $conn->query($sql);

        $rows = array();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Check if the user is among the task members before displaying the task
                $taskMembers = json_decode($row['task_members'], true);
                $memberIds = array_column($taskMembers, 'uid');
                if (in_array($user_id, $memberIds)) {
                    $rows[] = $row;
                }
            }
            if (!empty($rows)) {
                echo json_encode($rows);
            } else {
                // http_response_code(400);
                echo json_encode([]);
            }
        } else {
            // http_response_code(400);
            echo json_encode([]);
        }
    } else {
        http_response_code(401); 
        echo json_encode(["error" => "Unauthorized access"]);
    }
} else {
    http_response_code(401); 
    echo json_encode(["error" => "Unauthorized access"]);
}

$conn->close();
?>
