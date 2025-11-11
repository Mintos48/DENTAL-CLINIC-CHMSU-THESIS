<?php
// Use absolute paths
require_once __DIR__ . '/../src/config/constants.php';
require_once __DIR__ . '/../src/config/session.php';
require_once __DIR__ . '/../src/controllers/DentistController.php';

// Set headers
header('Content-Type: application/json');

try {
    echo "Testing DentistController...\n";
    
    // Check if class exists
    if (class_exists('DentistController')) {
        echo "✓ DentistController class exists\n";
        
        $controller = new DentistController();
        echo "✓ Controller instantiated successfully\n";
        
        // Check if method exists
        if (method_exists($controller, 'getComprehensiveAnalytics')) {
            echo "✓ getComprehensiveAnalytics method exists\n";
        } else {
            echo "✗ getComprehensiveAnalytics method NOT found\n";
        }
        
        // List all methods
        $methods = get_class_methods($controller);
        echo "\nAvailable methods:\n";
        foreach ($methods as $method) {
            echo "- $method\n";
        }
        
    } else {
        echo "✗ DentistController class NOT found\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>