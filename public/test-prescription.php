<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../src/config/constants.php';
require_once __DIR__ . '/../src/config/session.php';
require_once __DIR__ . '/../src/controllers/PrescriptionController.php';

header('Content-Type: application/json');

try {
    echo "Testing prescription creation...\n";
    
    // Mock data for testing
    $testData = [
        'appointment_id' => 1,
        'diagnosis' => 'Test diagnosis',
        'instructions' => 'Test instructions',
        'medications' => [
            [
                'medication_name' => 'Test Med',
                'dosage' => '1 tablet',
                'frequency' => 'daily',
                'duration' => '7 days',
                'instructions' => 'Take with food'
            ]
        ]
    ];
    
    // Override the input
    $_POST = $testData;
    file_put_contents('php://input', json_encode($testData));
    
    $controller = new PrescriptionController();
    $result = $controller->createPrescription();
    
    echo json_encode($result, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
?>