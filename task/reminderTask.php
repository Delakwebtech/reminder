<?php

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Disposition, Content-Type, Content-Length, Accept-Encoding");
header("Content-type:application/json");

use GuzzleHttp\Client;

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/common.php';


class TaskReminder
{
    private $client;
    private $conn; 
    
    public function __construct($conn)
    {
        $this->client = new Client();
        $this->conn = $conn; 
    }
    
    
    public function processTasks($row) {
            
        $taskName = $row['task_name'];
        $channel = $row['channel'];
        $dueDate = $row['dueDate'];
        $subject = $row['subject'];
        $counter = $row['reminder_counter'];
        $locale = 'en_US';
        $numberFormatter = new NumberFormatter($locale, NumberFormatter::ORDINAL);
        $reminderCounter = $numberFormatter->format($counter);

        
        $accessToken = refreshAccessToken();
        
        // Calculate the $hourstoDelivery
        $future_time = new DateTime($dueDate); 
        $current_time = new DateTime();
        $diff = $future_time->diff($current_time);
        $hourstoDelivery = $diff->h + ($diff->days * 24);
        
        // Set the timezone to "Africa/Lagos"
        // date_default_timezone_set('Africa/Lagos');
        
        // Retrieve admin emails from the database
        $adminQuery = "SELECT admins FROM tasks";
        $adminResult = $this->conn->query($adminQuery);
        
        // $adminStmt = $adminResult->fetch_all(MYSQLI_ASSOC);
        if ($adminResult->num_rows > 0) {
            while ($row = $adminResult->fetch_assoc()) {
                // Assuming the field contains JSON data
                $adminDetails = json_decode($row['admins'], true);
            }
        }
        
        
        $adminEmail = array_map(function ($admin) {
            return ['email' => $admin['email'], 'name' => $admin['fullname']];
        }, $adminDetails);
        
        $names = array_column($adminEmail, 'name');
        
        $allAdminNames = implode(', ', $names);
        

           
        // Retrieve recipient emails and names from the database
        $recipientQuery = "SELECT task_members FROM tasks";
        $recipientResult = $this->conn->query($recipientQuery);
        
        // 
        if ($recipientResult->num_rows > 0) {
            while ($row = $recipientResult->fetch_assoc()) {
                // Assuming the field contains JSON data
                $recipientDetails = json_decode($row['task_members'], true);
            }
        }
        
        
        $adminRecipients = array_map(function ($recipient) {
            return $recipient['fullname'];
        }, $recipientDetails);
        
        $allNamesString = implode(', ', $adminRecipients);
        
        if (($channel === 'Email')) {
                    
            foreach ($recipientDetails as $recipient) {
                // Send email to recipients using the recipient template
                $recipientTemplate = file_get_contents(__DIR__ . '/template/recipientEmail.html');
                if ($recipientTemplate === false) {
                    throw new CustomException('Recipient email template not found');
                }
                            
                $recipientTemplate = str_replace('{{ name }}', $recipient['fullname'], $recipientTemplate);
                $recipientTemplate = str_replace('{{ taskName }}', $taskName, $recipientTemplate);
                $recipientTemplate = str_replace('{{ hourstoDelivery }}', $hourstoDelivery, $recipientTemplate);
                            
                $emailPayload = [
                    'sender' => [
                        'name' => 'Delak Support',
                        'email' => 'delakwebtech20@gmail.com',
                    ],
                    'to' => [
                        [
                            'email' => $recipient['email'],
                            'name' => $recipient['fullname'],
                        ]
                    ],
                    'cc' => $adminEmail,
                    'subject' => "$reminderCounter Reminder: $taskName",
                    'htmlContent' => generateEmailContent($recipientTemplate, $recipient['fullname'], $future_time->format('Y-m-d H:i:s'), $hourstoDelivery),
                ];
            
                sendEmail($this->client, $emailPayload);
                        
            }
                
        }
    }
}


// Include database connection
include_once('../config/database.php');

$sql = "SELECT * FROM tasks WHERE status != 'Approved' AND sub_status = 'successful'";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $reminderFreq = $row['reminder_Freq'];
        $lastReminderTime = new DateTime($row['start_date']);
        $current_time = new DateTime();
        
        // Check if it's time for the reminder for this particular task
        if ($current_time >= $lastReminderTime) {
            $taskReminder = new TaskReminder($conn);
            $taskReminder->processTasks($row);
            
            
            // Update the next reminder time for this specific task
            $taskID = $row['task_id'];
            $nextReminderTime = (clone $current_time)->add(new DateInterval("PT{$reminderFreq}H"));
            
            $increaseReminderCounter = $row['reminder_counter'] + 1;
            
            $updateReminderTime = $conn->prepare("UPDATE tasks SET start_date = ?, reminder_counter = ? WHERE task_id = ?");
            $updateReminderTime->bind_param("ssi", $nextReminderTime->format("Y-m-d H:i:s"), $increaseReminderCounter, $taskID);
            $updateReminderTime->execute();
            $updateReminderTime->close();
        }
    }
}

$now = new DateTime();
error_log("Another process already running at " . $now->format('Y-m-d H:i:s') . PHP_EOL, 3, "/home/prolificme/logs/tasks.log");

// Close the database connection
$conn->close();
?>