<?php

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Disposition, Content-Type, Content-Length, Accept-Encoding");
header("Content-type:application/json");

// Include database connection
include(__DIR__ . '/../config/database.php');

// Include authentication
require('auth.php'); 

// Extract input data
$input = json_decode(file_get_contents('php://input'), true);

$first_name = $input['firstname'];
$last_name = $input['lastname'];
$email = $input['email'];
$phonenumber = $input['phonenumber'];
$password = $input['password'];

// Hash password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    // Provide a specific error message for invalid email format
    http_response_code(400);
    echo json_encode(["error" => "Invalid email format"]);
    exit();
}

// Check if all required fields are present and not empty
if (!$first_name || !$last_name || !$email || !$phonenumber || !$password) {
    // Provide a specific error message for missing input data
    http_response_code(400);
    echo json_encode(["error" => "All fields are required"]);
    exit();
}

// Check if the email already exists in the database
$sql_check_email = "SELECT id FROM users WHERE email = ?";
$stmt_check_email = $conn->prepare($sql_check_email);
$stmt_check_email->bind_param("s", $email);
$stmt_check_email->execute();
$result = $stmt_check_email->get_result();
if ($result->num_rows > 0) {
    http_response_code(400);
    echo json_encode(["error" => "Email already exists"]);
    exit();
}

$stmt_check_email->close();

// Prepare and execute SQL query to insert user (using prepared statements)
$sql = "INSERT INTO users (first_name, last_name, email, phone_number, password) VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssss", $first_name, $last_name, $email, $phonenumber, $hashedPassword);

if ($stmt->execute()) {
    // Retrieve the newly inserted user's ID
    $user_id = $stmt->insert_id;

    // Define a key
    $key = 'emcifilorp';
        
    // Generate token
    $token = Token::Sign(['id' => $user_id], $key, 365*24*60*60);

    // Return the token as a response or handle it as needed
    echo json_encode(array("userId" => $user_id, "token" => $token, "message" => "User registered successfully"));
} else {
    // Provide a generic error message for registration failure
    http_response_code(500);
    echo json_encode(["error" => "User registration failed"]);
}

$stmt->close();
$conn->close();
?>
