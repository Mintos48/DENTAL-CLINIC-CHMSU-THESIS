<?php
/**
 * Database Migration Helper
 * Apply the dentist role migration and test data
 */

require_once '../../src/config/database.php';

echo "<h2>Database Migration Helper</h2>";

try {
    $db = Database::getConnection();
    
    // Check if dentist role exists
    echo "<h3>1. Checking current user roles...</h3>";
    $result = $db->query("SHOW COLUMNS FROM users LIKE 'role'");
    $role_column = $result->fetch_assoc();
    echo "<p>Current role column: " . $role_column['Type'] . "</p>";
    
    // Check if dentist role is already in the enum
    if (strpos($role_column['Type'], 'dentist') === false) {
        echo "<p style='color: orange;'>⚠️ Dentist role NOT found. Applying migration...</p>";
        
        // Apply the dentist role migration
        $sql = "ALTER TABLE users MODIFY COLUMN role ENUM('patient', 'staff', 'dentist', 'admin', 'super_admin') NOT NULL";
        if ($db->query($sql)) {
            echo "<p style='color: green;'>✅ Successfully added dentist role to database!</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to add dentist role: " . $db->error . "</p>";
        }
    } else {
        echo "<p style='color: green;'>✅ Dentist role already exists in database</p>";
    }
    
    // Check if dentist users exist
    echo "<h3>2. Checking for dentist users...</h3>";
    $result = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'dentist'");
    $dentist_count = $result->fetch_assoc()['count'];
    
    if ($dentist_count == 0) {
        echo "<p style='color: orange;'>⚠️ No dentist users found. Adding test dentist users...</p>";
        
        // Add dentist users
        $stmt = $db->prepare("INSERT INTO users (name, email, password, role, branch_id, phone, address, date_of_birth, gender, status, email_verified) VALUES (?, ?, ?, 'dentist', ?, ?, ?, ?, ?, 'active', TRUE)");
        
        $users = [
            ['Dr. Alex Thompson', 'alex.dentist@dentalclinic.com', '$2a$12$amvPWsaWtB9EmYlIKNUWKeL1u6j0v5Hvg4FLiY7sV0BIlVJd/Ti2e', 1, '555-4001', '501 Dental Professional Dr, Main Branch', '1983-06-20', 'male'],
            ['Dr. Maria Rodriguez', 'maria.dentist@dentalclinic.com', '$2a$12$amvPWsaWtB9EmYlIKNUWKeL1u6j0v5Hvg4FLiY7sV0BIlVJd/Ti2e', 2, '555-4002', '502 Northside Dental Plaza', '1986-12-15', 'female']
        ];
        
        foreach ($users as $user) {
            $stmt->bind_param('sssissss', ...$user);
            if ($stmt->execute()) {
                echo "<p style='color: green;'>✅ Added dentist user: " . $user[1] . "</p>";
            } else {
                echo "<p style='color: red;'>❌ Failed to add user " . $user[1] . ": " . $stmt->error . "</p>";
            }
        }
    } else {
        echo "<p style='color: green;'>✅ Found $dentist_count dentist users in database</p>";
        
        // List dentist users
        $result = $db->query("SELECT name, email FROM users WHERE role = 'dentist'");
        while ($row = $result->fetch_assoc()) {
            echo "<p>   - " . $row['name'] . " (" . $row['email'] . ")</p>";
        }
    }
    
    echo "<h3>3. Testing Instructions</h3>";
    echo "<ol>";
    echo "<li>Go to the login page</li>";
    echo "<li>Use email: <strong>alex.dentist@dentalclinic.com</strong></li>";
    echo "<li>Use password: <strong>password123</strong></li>";
    echo "<li>You should be redirected to the dentist dashboard</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3 { color: #333; }
p { margin: 5px 0; }
</style>