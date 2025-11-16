<?php
/**
 * Staff Management API Endpoint
 * Handles staff management operations for dentists
 */

// Include dependencies first
define('STAFF_MANAGEMENT_INCLUDED', true);
require_once '../../src/config/constants.php';
require_once '../../src/config/session.php';
require_once '../../src/controllers/StaffManagementController.php';

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

    // Check role permissions (only dentists and admins can manage staff)
    $role = getSessionRole();
    if (!in_array($role, [ROLE_DENTIST, ROLE_ADMIN])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied - dentist or admin role required']);
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
    $controller = new StaffManagementController();
    
    switch ($action) {
        case 'getBranchStaff':
            $result = $controller->getBranchStaff();
            echo json_encode($result);
            break;
            
        case 'createStaffMember':
            $result = $controller->createStaffMember();
            echo json_encode($result);
            break;
            
        case 'updateStaffMember':
            $result = $controller->updateStaffMember();
            echo json_encode($result);
            break;
            
        case 'toggleStatus':
            $result = $controller->toggleStatus();
            echo json_encode($result);
            break;
            
        case 'getTreatmentTypes':
            $result = $controller->getTreatmentTypes();
            echo json_encode($result);
            break;
            
        case 'getStaffPerformance':
            $result = $controller->getStaffPerformance();
            echo json_encode($result);
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Unknown action: ' . $action]);
            break;
    }
    
} catch (Exception $e) {
    error_log("Staff Management API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>