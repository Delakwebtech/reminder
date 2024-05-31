<?php

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Disposition, Content-Type, Content-Length, Accept-Encoding");
header("Content-type:application/json");


// Include database connection
include_once('config/database.php');

$request_method = $_SERVER['REQUEST_METHOD'];
$request_uri = strtok($_SERVER['REQUEST_URI'], '?');
$endpoint = rtrim($request_uri, '/');

// Function to include file based on endpoint and method
function includeFile($file) {
    if (file_exists($file)) {
        include($file);
    } else {
        echo json_encode("File not found: $file");
    }
}


// Validating $endpoint and $request_method
if (!isset($endpoint) || !isset($request_method)) {
    echo json_encode(['error' => 'Route not found', 
    'resp' => isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '']);
    exit; 
}

// Validating the request method
$valid_request_methods = ['POST', 'GET', 'PUT', 'DELETE'];
if (!in_array($request_method, $valid_request_methods)) {
    echo json_encode(['error' => 'Invalid request method', 
    'resp' => isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '']);
    exit; 
}


// Routes and actions based on endpoint and method
switch ("$endpoint:$request_method") {
    // User endpoints
    case '/index.php/user/register:POST':
        includeFile('user/register.php');
        break;
    case '/index.php/user/login:POST':
        includeFile('user/login.php');
        break;
    case '/index.php/user/forgotpassword:POST':
        includeFile('user/forgotpassword.php');
        break;
    case '/index.php/user/user/otpvalidation:POST':
        includeFile('user/otpvalidation.php');
        break;
    case '/index.php/user/read:POST':
        includeFile('user/read.php');
        break;

    // Team endpoints
    case '/prolificme/index.php/team/create:POST':
        includeFile('team/create.php');
        break;
    case '/prolificme/index.php/team/delete:DELETE':
        includeFile('team/delete.php');
        break;
    case '/prolificme/index.php/team/delete_member:DELETE':
        includeFile('team/delete_member.php');
        break;
    case '/prolificme/index.php/team/read:GET':
        includeFile('team/read.php');
        break;
    case '/prolificme/index.php/team/update_member:PUT':
        includeFile('team/update_member.php');
        break;
    case '/prolificme/index.php/team/update:PUT':
        includeFile('team/update.php');
        break;

    // Task endpoints
    case '/prolificme/index.php/task/create:POST':
        includeFile('task/create.php');
        break;
    case '/prolificme/index.php/task/delete:DELETE':
        includeFile('task/delete.php');
        break;
    case '/prolificme/index.php/task/read:GET':
        includeFile('task/read.php');
        break;
    case '/prolificme/index.php/task/update:PUT':
        includeFile('task/update.php');
        break;

    // Token endpoints
    case '/prolificme/index.php/token/create:POST':
        includeFile('token/create.php');
        break;
    case '/prolificme/index.php/token/delete:DELETE':
        includeFile('token/delete.php');
        break;
    case '/prolificme/index.php/token/read:GET':
        includeFile('token/read.php');
        break;
    case '/prolificme/index.php/token/update:PUT':
        includeFile('token/update.php');
        break;

    // Invalid endpoint
    default:
        echo json_encode(['error' => 'Invalid endpoint', 
        'resp' => isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '']);
        break;
}

$conn->close();

?>
