<?php

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Disposition, Content-Type, Content-Length, Accept-Encoding");
header("Content-type:application/json");

// Include database connection
include_once('../config/database.php');


// Load subscription data from JSON file in the home directory
$subscriptionsJson = file_get_contents('./payments/webhook.json');


$input = json_decode(file_get_contents('php://input'), true);

// Function to sanitize input
function sanitizeInput($conn, $input) {
    if (is_array($input)) {
        foreach ($input as $key => $value) {
            $input[$key] = mysqli_real_escape_string($conn, $value);
        }
    } else {
        $input = mysqli_real_escape_string($conn, $input);
    }
    return $input;
}

// Function to send an email
function sendEmail($to, $subject, $message, $headers) {
    // Send email
    mail($to, $subject, $message, $headers);
}

// Function to insert team members
function insertTeamMembers($conn, $teamId, $teamMembers, $sanitizedTeamName, $teamType, $dateTime) {
    foreach ($teamMembers as $member) {
        $sanitizedMember = sanitizeInput($conn, $member);
        $sqlInsertTeamMember = "INSERT INTO team_members (team_id, fullname, email, username, phoneNumber, role, uid, KC_id, refresh_token) 
                                VALUES ('$teamId', '{$sanitizedMember['fullname']}', '{$sanitizedMember['email']}', 
                                '{$sanitizedMember['username']}', '{$sanitizedMember['phoneNumber']}', 
                                '{$sanitizedMember['role']}', '{$sanitizedMember['uid']}',
                                '{$sanitizedMember['KC_id']}', '{$sanitizedMember['refreshToken']}')";
        $conn->query($sqlInsertTeamMember);
        
        // Send email to the added team member
        $emailPayload = [
            'recipientEmail' => $sanitizedMember['email'],
            'subject' => 'You have been added to a team',
            'message' => "Hello {$sanitizedMember['fullname']},\n\nYou have been added to a new team '{$sanitizedTeamName}'.\n\nRegards,\nProlificme Management Team"
        ];

        sendEmail(
            $emailPayload['recipientEmail'],
            $emailPayload['subject'],
            $emailPayload['message'],
            'From: reminder@prolificme.com'
        );
    }
}


// Extract team and team members data
$transactionId = $input['transaction_id'];
$customerId = $input['customerId'];
$teamName = $input['teamName'];
$teamType = $input['teamType'];
$teamMembers = $input['memberList'];
$dateTime = $input['dateTime'];


// Validate team name
if (!empty($transactionId) && !empty($customerId) && !empty($teamName) && !empty($dateTime) && !empty($teamType) && !empty($teamMembers)) {
    
    // Check if the Customer ID Exists
    // Decode the JSON data into an associative array
    $data = json_decode($subscriptionsJson, true);
    
    // Variable to hold the last matching result
    $matchingResult = null;
    
    // Loop through the data to find matches based on criteria
    foreach ($data as $item) {
        
        // Check if the criteria match
        if ($item['data']['customer']['id'] == $customerId) {
                
            // Update the last matching result
            $matchingResult = $item;
            
        }
    }
    
    // Output the last matching result
    if ($matchingResult === null) {
        
        http_response_code(400);
        echo json_encode(["error" => "No matching result found for customer ID: $customerId"]);
        exit;
        
    }
    
    
    // Check if the transaction ID has already been used
    $sqlCheckIsUsed = "SELECT is_used FROM teams WHERE transaction_id = '$transactionId'";
    $result = $conn->query($sqlCheckIsUsed);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if ($row['is_used']) {
            http_response_code(400);
            echo json_encode(["error" => "Transaction ID has already been used"]);
            exit; // Stop further execution
        }
    }
    
    
    // Sanitize input data
    $sanitizedTransactionId = sanitizeInput($conn, $transactionId);
    $sanitizedCustomerId = sanitizeInput($conn, $customerId);
    $sanitizedTeamName = sanitizeInput($conn, $teamName);
    $sanitizedDateTime = sanitizeInput($conn, $dateTime);
    $sanitizedTeamType = sanitizeInput($conn, $teamType);

    // Insert team data into the database
    $teamMembersJSON = json_encode($teamMembers); // Convert array to JSON string
    $sqlInsertTeam = "INSERT INTO teams (transaction_id, customerId, team_name, team_type, team_members, dateTime) VALUES ('$sanitizedTransactionId', '$sanitizedCustomerId', '$sanitizedTeamName', '$sanitizedTeamType', '$teamMembersJSON', '$sanitizedDateTime')";

    if ($conn->query($sqlInsertTeam) === TRUE) {
        $teamId = $conn->insert_id;

        // Insert team members into the database
        insertTeamMembers($conn, $teamId, $teamMembers, $sanitizedTeamName, $teamType, $dateTime);
        
        // Update the is_used flag for the transaction ID
        $sqlUpdateIsUsed = "UPDATE teams SET is_used = TRUE WHERE transaction_id = '$transactionId'";
        $conn->query($sqlUpdateIsUsed);

        echo json_encode(array("Team and its members inserted successfully"));
    } else {
        http_response_code(400);
        echo json_encode(array("error" => "Error inserting team: " . $conn->error));
        exit;
    }
} else {
    http_response_code(400);
    echo json_encode(["error" => "Invalid team data"]);
    exit;
}

$conn->close();
?>
