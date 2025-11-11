<?php
/**
 * Prescription API Endpoint
 * Handles prescription management operations
 */

// Include dependencies first
define('PRESCRIPTION_CONTROLLER_INCLUDED', true);
require_once '../../src/config/constants.php';
require_once '../../src/config/session.php';
require_once '../../src/controllers/PrescriptionController.php';

// Set error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
$request_method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($request_method === 'OPTIONS') {
    exit(0);
}

try {
    // Check authentication
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
        exit();
    }

    // Check role permissions
    $role = getSessionRole();
    if (!in_array($role, [ROLE_DENTIST, ROLE_STAFF, ROLE_ADMIN])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit();
    }

    // Get action
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    if (empty($action)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No action specified']);
        exit();
    }

    // Create controller and handle action
    $controller = new PrescriptionController();
    
    switch ($action) {
        case 'createPrescription':
            $result = $controller->createPrescription();
            echo json_encode($result);
            break;
            
        case 'getPrescriptions':
            $result = $controller->getPrescriptions();
            echo json_encode($result);
            break;
            
        case 'getAppointmentsNeedingPrescriptions':
            $result = $controller->getAppointmentsNeedingPrescriptions();
            echo json_encode($result);
            break;
            
        case 'updatePrescription':
            $result = $controller->updatePrescription();
            echo json_encode($result);
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Unknown action: ' . $action]);
            break;
    }
    
} catch (Exception $e) {
    error_log("Prescription API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>