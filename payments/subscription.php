<?php 

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Disposition, Content-Type, Content-Length, Accept-Encoding");
header("Content-type:application/json");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI'] === '/prolificme/payments/subscription.php/subscription') {
    
    $jsonData = file_get_contents('webhook.json');

    $requestBody = file_get_contents('php://input');
    $subDetails = json_decode($requestBody, true);
            
    $email = $subDetails['email'];

    // Decode the JSON data into an associative array
    $data = json_decode($jsonData, true);
    
    // Variable to hold the last matching result
    $matchingResult = null;
    
    // Loop through the data to find matches based on criteria
    foreach ($data as $item) {
        
        // Check if the criteria match
        if ($item['data']['customer']['email'] == $email) {
                
            // Update the last matching result
            $matchingResult = $item;
            
        }
    }

    
    // Output the last matching result
    if ($matchingResult !== null) {
        
        // Encode the last matching result to JSON
        $jsonOutput = json_encode($matchingResult);
        
        // Output JSON data
        echo $jsonOutput;
        
    } else {
        http_response_code(400);
        echo json_encode(array("error" => "No matching payment details found"));
    }
}


?>