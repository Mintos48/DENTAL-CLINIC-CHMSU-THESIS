<?php
require_once __DIR__ . '/../src/config/database.php';

try {
    $db = Database::getConnection();
    
    echo "Testing prescriptions table...\n";
    
    // Check if table exists and get structure
    $result = $db->query("DESCRIBE prescriptions");
    
    if ($result) {
        echo "Prescriptions table structure:\n";
        while ($row = $result->fetch_assoc()) {
            echo "- {$row['Field']} ({$row['Type']})\n";
        }
    } else {
        echo "Error: prescriptions table not found\n";
    }
    
    // Test a simple query
    echo "\nTesting simple query...\n";
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM prescriptions");
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    echo "Prescriptions count: " . $result['count'] . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>