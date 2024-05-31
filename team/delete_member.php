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

// Check if the user is authenticated
if ($user_id) {
    // Get input data (team_id and member_id to remove)
    $team_id = $_GET['team_id'] ?? '';
    $member_id = $_GET['member_id'] ?? '';

    if (!empty($team_id) && !empty($member_id)) {
        // Remove member from team
        $sqlRemoveMember = "DELETE FROM team_members WHERE team_id = '$team_id' AND member_id = '$member_id'";
        $resultRemoveMember = $conn->query($sqlRemoveMember);

        if ($resultRemoveMember) {
            // Remove member from all teams they belong to
            $sqlRemoveFromAllTeams = "DELETE FROM team_members WHERE member_id = '$member_id'";
            $resultRemoveFromAllTeams = $conn->query($sqlRemoveFromAllTeams);

            if ($resultRemoveFromAllTeams) {
                // Successfully removed member from the team and all teams they belong to
                echo json_encode(array("Member removed from team and all teams they belonged to."));
            } else {
                // Failed to remove member from all teams
                http_response_code(400);
                echo json_encode(array("error" => "Failed to remove member from all teams."));
            }
        } else {
            // Failed to remove member from the team
            http_response_code(400);
            echo json_encode(array("error" => "Failed to remove member from the team."));
        }
    } else {
        // Incomplete data provided
        http_response_code(400);
        echo json_encode(array("error" => "Incomplete data provided."));
    }
} else {
    // User is not authenticated or token is invalid
    http_response_code(401);
    echo json_encode(array("error" => "Unauthorized access."));
}

$conn->close();
?>
