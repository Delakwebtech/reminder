<?php

use GuzzleHttp\Client;

require __DIR__ . '/vendor/autoload.php';

// Define CustomEmailException class
class CustomEmailException extends Exception {}

$input = json_decode(file_get_contents('php://input'), true);

// Function to sanitize input
if (!function_exists('sanitizeInput')) {
    function sanitizeInput($conn, $input) {
        if (is_array($input)) {
            foreach ($input as $key => $value) {
                if (is_array($value)) {
                    $input[$key] = sanitizeInput($conn, $value); // Recursively handle arrays
                } else {
                    $input[$key] = mysqli_real_escape_string($conn, $value);
                }
            }
        } else {
            $input = mysqli_real_escape_string($conn, $input);
        }
        return $input;
    }
}


// sendEmail function
if (!function_exists('sendEmail')) {
    function sendEmail($client, $payload) {
        
        $headers = [
            'Content-Type' => 'application/json',
            'api-key' => getenv('BREVO_KEY'),
        ];        
    
        try {
            $response = $client->post('https://api.brevo.com/v3/smtp/email', [
                'headers' => $headers,
                'json' => $payload,
            ]);
    
            // $data = json_decode($response->getBody(), true);
            // echo json_encode($data); // JSON encode the response
            
        } catch (Exception $e) {
            error_log('Email sending failed: ' . $e->getMessage());
            // Handle the error or rethrow a custom exception
            throw new CustomEmailException('Email sending failed');
        }
    }
}

if (!function_exists('generateEmailContent')) {
    function generateEmailContent($template, $recipientName, $dueDate, $hourstoDelivery) {
        // Generate email content based on the template, recipientName, dueDate, and hourstoDelivery
        // Replace placeholders in the template with data
        $placeholders = ['{{ name }}', '{{ dueDateTime }}', '{{ hourstoDelivery }}'];
        $data = [$recipientName, $dueDate, $hourstoDelivery];
        $emailContent = str_replace($placeholders, $data, $template);
    
        return $emailContent;
    
    }
}   
    
    
    
    