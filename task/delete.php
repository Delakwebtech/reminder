<?php

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Disposition, Content-Type, Content-Length, Accept-Encoding");
header("Content-type:application/json");

// Include database connection
include_once('../config/database.php');

$input = json_decode(file_get_contents('php://input'), true);

$id = $input['id'];

$sql = "DELETE FROM tasks WHERE task_id=$id";

if ($conn->query($sql) === TRUE) {
    echo json_encode(["Task deleted successfully"]);
} else {
    http_response_code(400);
    echo json_encode(["Error deleting task: " . $conn->error]);
}

$conn->close();
?>
