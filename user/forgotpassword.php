<?php

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Disposition, Content-Type, Content-Length, Accept-Encoding");
header("Content-type:application/json");

// Include database connection
include_once('../config/database.php');

// Extract email for password reset
$input = json_decode(file_get_contents('php://input'), true);
$email = $input['email'];

// Generate a unique token
$token = sprintf('%06d', mt_rand(0, 999999));

// Store the token in the database linked to the email
$sql = "INSERT INTO password_reset_tokens (email, token) VALUES ('$email', '$token')";

if ($conn->query($sql) === TRUE) {
    // Send an email with instructions and the token
    $to = $email;
    $subject = "Password Reset Instructions";
    $message = "Please use the following token to reset your password: $token";
    $headers = "From: reminder@prolificme.com"; 

    // Simulating sending an email
    if (mail($to, $subject, $message, $headers)) {
        echo json_encode(["Password reset instructions sent to your email."]);
    } else {
        http_response_code(401);
        echo json_encode(["error" => "Failed to send password reset instructions."]);
    }
} else {
    http_response_code(401);
    echo json_encode(["error" => "Error storing token in the database."]);
}

$conn->close();

?>
