<?php

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Disposition, Content-Type, Content-Length, Accept-Encoding");
header("Content-type:application/json");

// Include database connection
include(__DIR__ . '/../config/database.php');

// Include authentication
require('auth.php');

// Extract login credentials
$input = json_decode(file_get_contents('php://input'), true);

$email = $input['email'];
$password = $input['password'];

// Your login validation logic and generation of tokens or session handling
$sql = "SELECT id, email, first_name, last_name, phone_number, password FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    // Handle SQL query preparation error
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $conn->error]);
    exit();
}

$stmt->bind_param("s", $email);
$result = $stmt->execute();

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result) {
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $storedPassword = $row['password'];

        // Verify the entered password with the stored hashed password
        if (password_verify($password, $storedPassword)) {
            $user_id = $row['id'];

            // Define a key
            $key = 'emcifilorp';

            // Concatenating First Name and Last Name to create Full Name
            $fullName = $row['first_name'] . ' ' . $row['last_name'];

            // Generate token
            $token = Token::Sign($user_id, $key, 365*24*60*60);

            // Fetching all user details id, fullname, email, among others
            $userDetails = array(
                "userId" => $user_id,
                "Email" => $row['email'],
                "Full Name" => $fullName,
                "Phone Number" => $row['phone_number'],
                "Token" => $token,
            );

            // Returning the user details as a response
            echo json_encode($userDetails);
        } else {
            // Error message for incorrect credentials
            http_response_code(401);
            echo json_encode(["error" => "Invalid email or password"]);
        }
    } else {
        // Error message for user not found
        http_response_code(401);
        echo json_encode(["error" => "Invalid email or password"]);
    }
} else {
    // Error message for query failure
    http_response_code(500);
    echo json_encode(["error" => "Internal Server Error"]);
}

$stmt->close();
$conn->close();
?>
