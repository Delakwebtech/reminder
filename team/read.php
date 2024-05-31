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

// Function to fetch teams and team members for a user
function fetchUserTeams($conn, $userId) {
    if ($userId) {
        try {
            // Fetch teams the user belongs to
            $sqlFetchTeams = "SELECT * FROM teams WHERE team_id IN (
                SELECT team_id FROM team_members WHERE uid = ?
            )";

            $stmtFetchTeams = $conn->prepare($sqlFetchTeams);
            $stmtFetchTeams->bind_param("i", $userId);
            $stmtFetchTeams->execute();

            $resultTeams = $stmtFetchTeams->get_result();

            $teams = array();

            if ($resultTeams->num_rows > 0) {
                while ($row = $resultTeams->fetch_assoc()) {
                    $teamId = $row['team_id'];

                    // Fetch team members for each team
                    $sqlFetchTeamMembers = "SELECT * FROM team_members WHERE team_id = ?";
                    $stmtFetchTeamMembers = $conn->prepare($sqlFetchTeamMembers);
                    $stmtFetchTeamMembers->bind_param("i", $teamId);
                    $stmtFetchTeamMembers->execute();

                    $resultTeamMembers = $stmtFetchTeamMembers->get_result();

                    $members = array();
                    if ($resultTeamMembers->num_rows > 0) {
                        while ($member = $resultTeamMembers->fetch_assoc()) {
                            $members[] = $member;
                        }
                    }

                    $row['team_members'] = $members;
                    $teams[] = $row;
                }
            } else {
                // No teams found for the user
                return [];
            }

            return $teams;
        } catch (Exception $e) {
            // Handle exceptions (e.g., database errors)
            echo json_encode(array("error" => $e->getMessage()));
        }
    } else {
        // User is not authenticated or token is invalid
        http_response_code(401);
        echo json_encode(array("error" => "User is not authenticated or token is invalid"));
    }
}

// Fetch teams and team members for the authenticated user
$userTeams = fetchUserTeams($conn, $user_id);

// Output the user's teams and team members
header('Content-Type: application/json');
echo json_encode(!isset($userTeams['error']) ? $userTeams : array("error" => "No teams found for the user"));

$conn->close();
?>
