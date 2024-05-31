<?php

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Disposition, Content-Type, Content-Length, Accept-Encoding");
header("Content-type:application/json");

use GuzzleHttp\Client;

require __DIR__ . '/vendor/autoload.php';

// Include database connection
include_once('../config/database.php');
include('common.php');

// Create Guzzle client
$client = new Client();

// Load subscription data from JSON file in the home directory
$subscriptionsJson = file_get_contents('./payments/webhook.json');

if (
    isset($input['customerId']) &&
    isset($input['taskName']) &&
    isset($input['admin']) &&
    isset($input['recipient']) &&
    isset($input['workTeam']) &&
    isset($input['status']) && 
    isset($input['channel']) && 
    isset($input['dueDate']) && 
    isset($input['reminder']) && 
    isset($input['description']) &&
    isset($input['reminderFreq']) &&
    isset($input['creator']) &&
    isset($input['startDate'])
) {
    
    $customerId = $input['customerId'];
    $taskName = $input['taskName'];
    $adminEmails = $input['admin'];
    $recipientEmails = $input['recipient'];
    $workTeam = $input['workTeam'];
    $status = $input['status'];
    $channel = $input['channel'];
    $dueDate = $input['dueDate'];
    $reminder = $input['reminder'];
    $description = $input['description'];
    $reminderFreq = $input['reminderFreq'];
    $creator = $input['creator'];
    $startDate = $input['startDate'];

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
    if ($matchingResult === null  || $matchingResult['data']['status'] !== 'successful') {
        
        http_response_code(400);
        echo json_encode(["error" => "Subscription expired or no matching result found for customer ID: $customerId"]);
        exit;
        
    }
} else {
    http_response_code(400);
    echo json_encode(["error" => "Invalid Data"]);
    exit;
}


// Function to insert admin members
function insertTaskAdmins($conn, $taskId, $adminEmails) {
    foreach ($adminEmails as $admin) {
        $sanitizedAdmin = sanitizeInput($conn, $admin);
        $sqlInsertTaskAdmin = "INSERT INTO admin (task_id, fullname, email, role) 
                                VALUES ('$taskId', '{$sanitizedAdmin['fullname']}', '{$sanitizedAdmin['email']}',
                                    '{$sanitizedAdmin['role']}')";
        $conn->query($sqlInsertTaskAdmin);
    }
}


// Function to insert task members
function insertTaskMembers($conn, $taskId, $recipientEmails) {
    foreach ($recipientEmails as $member) {
        $sanitizedMember = sanitizeInput($conn, $member);
        $sqlInsertTaskMember = "INSERT INTO task_members (task_id, fullname, email, username, phoneNumber, role, uid) 
                                VALUES ('$taskId', '{$sanitizedMember['fullname']}', '{$sanitizedMember['email']}', 
                                '{$sanitizedMember['username']}', '{$sanitizedMember['phoneNumber']}', 
                                '{$sanitizedMember['role']}', '{$sanitizedMember['uid']}')";
        $conn->query($sqlInsertTaskMember);
    }
}


// Validate input data
if (!empty($customerId) && !empty($taskName) && !empty($adminEmails) && !empty($recipientEmails) && !empty($workTeam)
    && !empty($status) && !empty($channel) && !empty($dueDate) && !empty($reminder) && !empty($description) && 
    !empty($reminderFreq) && !empty($creator) && !empty($startDate)) {

    $taskMembersJSON = json_encode($recipientEmails);
    $taskAdminsJSON = json_encode($adminEmails);

    $sqlInsertTask = "INSERT INTO tasks (customer_id, task_name, task_members, admins, work_team, status, channel, 
                    dueDate, reminder, description, reminder_Freq, creator, start_date) 
            VALUES ('$customerId', '$taskName', '$taskMembersJSON', '$taskAdminsJSON', '$workTeam', '$status', 
                    '$channel', '$dueDate', '$reminder', '$description', '$reminderFreq', '$creator', '$startDate')";

    if ($conn->query($sqlInsertTask) === TRUE) {
        $taskId = $conn->insert_id;
        
        // Insert task members into the database
        insertTaskAdmins($conn, $taskId, $adminEmails, $taskAdminsJSON);

        // Insert task members into the database
        insertTaskMembers($conn, $taskId, $recipientEmails, $taskMembersJSON);

    } else {
        echo json_encode(["Error inserting task: " . $conn->error]);
    }
    
    echo json_encode(array("message" => "Task Created"));
} else {
    http_response_code(400);
    echo json_encode(["error" => "Task not Created"]);
    exit;
}


$conn->close();
?>