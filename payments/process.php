<?php

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Disposition, Content-Type, Content-Length, Accept-Encoding");
header("Content-type:application/json");

// Include database connection
include_once('../config/database.php');

require 'vendor/autoload.php';

use GuzzleHttp\Client;

// Define CustomEmailException class
class CustomEmailException extends Exception {}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI'] === '/prolificme/payments/process.php') {
    
    // Retrieve the POST data
    $request_body = file_get_contents('php://input');
    
    // Decode the JSON data
    $data = json_decode($request_body, true);
    
    // Check if the data is valid JSON
    if ($data !== null) {
        
        // Load existing data or initialize an empty array if the file doesn't exist yet
        $file_path = __DIR__ . '/webhook.json';
        $existing_data = file_exists($file_path) ? json_decode(file_get_contents($file_path), true) : [];
        
        // Add the new transaction data to the existing data array
        $existing_data[] = $data;
        
        // Write the updated data to the file
        file_put_contents($file_path, json_encode($existing_data, JSON_PRETTY_PRINT));
        
        
        // Extract necessary information
        $transactionId = $data['data']['id'];
        $amount = $data['data']['amount'];
        $customerId = $data['data']['customer']['id'];
        $email = $data['data']['customer']['email'];
        $name = $data['data']['customer']['name'];
        $status = $data['data']['status'];
        
        // Check if status is successful
        if ($status === 'successful') {
                
            $updateStatus = "UPDATE tasks SET sub_status = 'successful' WHERE customer_id = $customerId";
            
            $conn->query($updateStatus);
        
        } else if ($status !== 'successful') {
            
            $updateStatus = "UPDATE tasks SET sub_status = 'not successful' WHERE customer_id = $customerId";
            
            $conn->query($updateStatus);
            
        }
        
        // Send Receipt via Email
        $receiptTemplate = file_get_contents(__DIR__ . '/receipt_template.html');
        if ($receiptTemplate === false) {
            throw new CustomException('Recipient email template not found');
        }
                    
        $receiptTemplate = str_replace('{{ transactionId }}', $transactionId, $receiptTemplate);
        $receiptTemplate = str_replace('{{ customerid  }}', $customerId, $receiptTemplate);
        $receiptTemplate = str_replace('{{ amount }}', $amount, $receiptTemplate);
                    
        $emailPayload = [
            'sender' => [
                'name' => 'Prolificme Support',
                'email' => 'reminder@prolificme.com',
            ],
            'to' => [
                [
                    'email' => $email,
                    'name' => $name,
                ]
            ],
            'subject' => "Receipt for Transaction ID: $transactionId",
            'htmlContent' => generateEmailContent($receiptTemplate, $transactionId, $customerId, $amount),
        ];
    
        sendEmail($emailPayload);
        
        
    
        // Respond with success
        http_response_code(200);
        
    } else {
        
        // JSON decoding failed, handle error
        http_response_code(400);
        exit;
        
    }
    
}

function sendEmail($payload) {
    $headers = [
        'Content-Type' => 'application/json',
        'api-key' => getenv('BREVO_KEY'),
    ];

    try {
        
        $client = new Client();
        $response = $client->post('https://api.brevo.com/v3/smtp/email', [
            'headers' => $headers,
            'json' => $payload,
        ]);
        
    } catch (Exception $e) {
        error_log('Email sending failed: ' . $e->getMessage());
        // Handle the error or rethrow a custom exception
        throw new CustomEmailException('Email sending failed');
    }
    
    
    
}

function generateEmailContent($template, $transactionId, $customerId, $amount) {
    // Generate email content based on the template, recipientName, dueDate, and hourstoDelivery
    // Replace placeholders in the template with data
    $placeholders = ['{{ transactionId }}', '{{ customerid }}', '{{ amount }}'];
    $data = [$transactionId, $customerId, $amount];
    $emailContent = str_replace($placeholders, $data, $template);

    return $emailContent;

}


?>
