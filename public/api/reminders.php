<?php
session_start();
require_once '../../src/config/database.php';
require_once '../../src/controllers/ReminderController.php';

// Set JSON response header
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get the database connection
$database = new Database();
$conn = $database->getConnection();

// Create reminder controller
$reminderController = new ReminderController($conn);

// Handle the request
try {
    $reminderController->handleRequest();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>