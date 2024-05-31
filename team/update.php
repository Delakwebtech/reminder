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

if ($user_id) {
    
    $inputData = json_decode(file_get_contents('php://input'), true);

    $team_id = $inputData['team_id'];
    $teamName = $inputData['teamName'];
    $newMembers = $inputData['memberList'];
    $dateTime = $inputData['dateTime'];
    
    
    // Function to insert team members
    foreach ($newMembers as $member) {
        // $sanitizedMember = sanitizeInput($conn, $member);
        $sqlInsertTeamMember = "INSERT INTO team_members (team_id, fullname, email, username, phoneNumber, role, uid, KC_id, refresh_token) 
                                VALUES ('$team_id', '{$member['fullname']}', '{$member['email']}', '{$member['username']}', '{$member['phoneNumber']}', '{$member['role']}', '{$member['uid']}',
                                    '{$member['KC_id']}', '{$member['refreshToken']}')";
        $conn->query($sqlInsertTeamMember);
            
        // Send email to the added team member
        $to = $member['email'];
        $subject = 'You have been added to a team';
        $message = 'Hello ' . $member['fullname'] . ', You have been added to the team ' . $teamName . '.';
        $headers = 'From: reminder@prolificme.com';
        
        // Send email
        mail($to, $subject, $message, $headers);
    }
    
    // Validate team data
    if (!empty($team_id) && !empty($teamName) && !empty($dateTime) && !empty($newMembers)) {
        // Fetch existing team data
        $sqlFetchTeam = "SELECT team_members FROM teams WHERE team_id = '$team_id'";
        $result = $conn->query($sqlFetchTeam);
    
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $existingMembers = json_decode($row['team_members'], true);
    
            // Merge new members with existing members
            $mergedMembers = array_merge($existingMembers, $newMembers);
            $teamMembersJSON = json_encode($mergedMembers); // Convert merged array to JSON string
    
            // Update team data in the database
            $sanitizedTeamName = mysqli_real_escape_string($conn, $teamName);
            $sanitizedDateTime = mysqli_real_escape_string($conn, $dateTime);
    
            $sqlUpdateTeam = "UPDATE teams 
                              SET team_name = '$sanitizedTeamName', team_members = '$teamMembersJSON', dateTime = '$sanitizedDateTime'
                              WHERE team_id = '$team_id'";
    
            if ($conn->query($sqlUpdateTeam) === TRUE) {
                echo json_encode(array("Team updated successfully"));
            } else {
                http_response_code(400);
                echo json_encode(array("error" => "Error updating team: " . $conn->error));
            }
        } else {
            echo json_encode(array("error" => "Team not found"));
        }
    } else {
        http_response_code(400);
        echo json_encode(["error" => "Invalid team data"]);
    }
    
} else {
    http_response_code(401); 
    echo json_encode(["error" => "Unauthorized access"]);
    exit;
}



$conn->close();
?>
