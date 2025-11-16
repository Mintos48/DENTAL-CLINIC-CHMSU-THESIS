<?php
/**
 * Dentist API Endpoint
 * Handles dentist-specific operations by delegating to DentistController
 */

// Include dependencies first
define('DENTIST_API_INCLUDED', true); // Prevent DentistController's built-in API handler
require_once '../../src/config/constants.php';
require_once '../../src/config/session.php';
require_once '../../src/controllers/DentistController.php';

// Set error handling
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable HTML error display
ini_set('log_errors', 1); // Enable error logging
ini_set('html_errors', 0); // Disable HTML formatting in errors

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
    if ($role !== ROLE_DENTIST && $role !== ROLE_STAFF && $role !== ROLE_ADMIN) {
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
    $controller = new DentistController();
    
    switch ($action) {
        // Existing actions
        case 'getAnalyticsData':
            $result = $controller->getAnalyticsData();
            echo json_encode($result);
            break;
            
        case 'getPendingAppointments':
            $result = $controller->getPendingAppointments();
            echo json_encode($result);
            break;
            
        case 'updateStatus':
            $result = $controller->updateStatus();
            echo json_encode($result);
            break;
            
        // Enhanced Analytics Actions
        case 'getComprehensiveAnalytics':
            $result = $controller->getComprehensiveAnalytics();
            echo json_encode($result);
            break;
            
        // Appointment Management Actions
        case 'getDailyAppointments':
            $result = $controller->getDailyAppointments();
            echo json_encode($result);
            break;
            
        case 'getMySchedule':
            $result = $controller->getMySchedule();
            echo json_encode($result);
            break;
            
        case 'getAppointmentDetails':
            $result = $controller->getAppointmentDetails();
            echo json_encode($result);
            break;
            
        // Prescription and Email Actions
        case 'sendInvoiceWithPrescription':
            try {
                $result = $controller->sendInvoiceWithPrescription();
                echo json_encode($result);
            } catch (Throwable $e) {
                error_log("sendInvoiceWithPrescription fatal error: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            break;
            
        // Credentials Management Actions
        case 'getCurrentCredentials':
            $result = $controller->getCurrentCredentials();
            echo json_encode($result);
            break;
            
        case 'saveDentistCredentials':
            $result = $controller->saveDentistCredentials();
            echo json_encode($result);
            break;
            
        case 'saveClinicCredentials':
            $result = $controller->saveClinicCredentials();
            echo json_encode($result);
            break;
            
        // Treatment Management Actions
        case 'getBranchTreatments':
            $result = $controller->getBranchTreatments();
            echo json_encode($result);
            break;
            
        case 'createTreatment':
            $result = $controller->createTreatment();
            echo json_encode($result);
            break;
            
        case 'updateTreatment':
            $result = $controller->updateTreatment();
            echo json_encode($result);
            break;
            
        case 'toggleTreatmentStatus':
            $result = $controller->toggleTreatmentStatus();
            echo json_encode($result);
            break;
            
        case 'deleteTreatment':
            $result = $controller->deleteTreatment();
            echo json_encode($result);
            break;
            
        case 'getTreatments':
            $result = $controller->getTreatments();
            echo json_encode($result);
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Unknown action: ' . $action]);
            break;
    }
    
} catch (Exception $e) {
    error_log("Dentist API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>