<?php
/**
 * Authentication API Endpoint
 * This file provides a web-accessible interface to the AuthController
 */

// Prevent any output before headers
ob_start();

// Get action first to determine if we need session
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Only disable session for specific actions that don't need it
$sessionDisabledActions = ['register', 'sendEmailVerification', 'verifyEmail', 'getBranches'];

if (in_array($action, $sessionDisabledActions)) {
    define('NO_SESSION', true);
}

// For login actions that need sessions
if (in_array($action, ['login', 'verifyLoginOTP']) && !defined('NO_SESSION')) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

define('API_ENDPOINT', true);
define('SKIP_AUTH_API_HANDLER', true); // Prevent AuthController from running its own API handler

// Set content type to JSON and enable CORS after session handling
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Error handling - suppress output for clean JSON
ini_set('display_errors', 0);
error_reporting(0);

// Include required files
require_once __DIR__ . '/../../src/controllers/AuthController.php';

try {
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    
    if (empty($action)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No action specified']);
        exit();
    }
    
    $controller = new AuthController();
    $response = [];
    
    switch ($action) {
        case 'register':
            $response = $controller->register();
            break;
            
        case 'login':
            $response = $controller->login();
            break;
            
        case 'logout':
            $response = $controller->logout();
            break;
            
        case 'sendEmailVerification':
            $response = $controller->sendEmailVerification();
            break;
            
        case 'verifyEmail':
            $response = $controller->verifyEmail();
            break;
            
        case 'getBranches':
            $response = $controller->getBranches();
            break;
            
        case 'verifyLoginOTP':
            $response = $controller->verifyLoginOTP();
            break;
            
        case 'resendLoginOTP':
            $response = $controller->resendLoginOTP();
            break;
            
        default:
            http_response_code(400);
            $response = ['success' => false, 'message' => 'Invalid action'];
            break;
    }
    
    // Ensure response is valid JSON
    if (!is_array($response)) {
        $response = ['success' => false, 'message' => 'Invalid response format'];
    }
    
    // Clear any buffered output and send clean JSON
    ob_clean();
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Auth API Error: " . $e->getMessage());
    http_response_code(500);
    
    // Clear any buffered output and send clean JSON
    ob_clean();
    echo json_encode([
        'success' => false, 
        'message' => 'Internal server error',
        'error' => $e->getMessage()
    ]);
}

// End output buffering
ob_end_flush();
?>