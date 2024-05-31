<?php

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Disposition, Content-Type, Content-Length, Accept-Encoding");
header("Content-type:application/json");

// Include database connection
include_once('../config/database.php');

$input = json_decode(file_get_contents('php://input'), true);
$id = $input['id'];

$sql = "DELETE FROM teams WHERE team_id=$id";

$response = new stdClass();

if ($conn->query($sql) === TRUE) {
    $response->success = true;
    $response->message = "Team deleted successfully";
} else {
    $response->success = false;
    $response->error = "Error deleting team: " . $conn->error;
}

echo json_encode($response);


$conn->close();
?>
