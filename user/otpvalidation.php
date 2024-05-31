<?php

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Disposition, Content-Type, Content-Length, Accept-Encoding");
header("Content-type:application/json");

// Include database connection
include_once('../config/database.php');

// Extract OTP and new password from JSON input
$input = json_decode(file_get_contents('php://input'), true);
$enteredOTP = $input['otp'];
$newPassword = $input['new_password'];

// Check if the OTP exists in the database
$sql = "SELECT email FROM password_reset_tokens WHERE token = '$enteredOTP'";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    // Token exists, proceed with password change
    $row = $result->fetch_assoc();
    $email = $row['email'];

    // Update the password in the database
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $updateSql = "UPDATE users SET password = '$hashedPassword' WHERE email = '$email'";

    if ($conn->query($updateSql) === TRUE) {
        // Password updated successfully, remove the used OTP
        $deleteSql = "DELETE FROM password_reset_tokens WHERE token = '$enteredOTP'";
        $conn->query($deleteSql);

        echo json_encode(["Password successfully updated."]);
    } else {
        http_response_code(401);
        echo json_encode(["error" => "Error updating password."]);
    }
} else {
    http_response_code(401);
    echo json_encode(["error" => "Invalid or expired OTP."]);
    exit;
}

$conn->close();
?>
