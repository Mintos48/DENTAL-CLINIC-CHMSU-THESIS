<?php
/**
 * Application Constants
 */

// Project root
define('ROOT_PATH', dirname(dirname(dirname(__FILE__))));
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('SRC_PATH', ROOT_PATH . '/src');

// URLs
define('BASE_URL', 'http://localhost:8080/dental-clinic-chmsu-thesis/public');

// User Roles
define('ROLE_PATIENT', 'patient');
define('ROLE_STAFF', 'staff');
define('ROLE_DENTIST', 'dentist');
define('ROLE_ADMIN', 'admin');

// Branch mapping
function getBranchName($branch_id) {
    $branches = [
        1 => 'Talisay Branch',
        2 => 'Silay Branch', 
        3 => 'Sarabia Branch'
    ];
    return isset($branches[$branch_id]) ? $branches[$branch_id] : 'Unknown Branch';
}

// Appointment Status
define('APPOINTMENT_PENDING', 'pending');
define('APPOINTMENT_APPROVED', 'approved');
define('APPOINTMENT_CANCELLED', 'cancelled');
define('APPOINTMENT_COMPLETED', 'completed');

// Session timeout (in seconds)
define('SESSION_TIMEOUT', 3600); // 1 hour

// Password hashing
define('PASSWORD_ALGO', PASSWORD_BCRYPT);
define('PASSWORD_OPTIONS', array('cost' => 12));

// Error messages
define('ERROR_INVALID_CREDENTIALS', 'Invalid email or password');
define('ERROR_BRANCH_NOT_FOUND', 'Branch not found');
define('ERROR_USER_EXISTS', 'User already exists');
define('ERROR_REGISTRATION_FAILED', 'Registration failed. Please try again.');
define('ERROR_SESSION_EXPIRED', 'Session expired. Please login again.');
define('ERROR_UNAUTHORIZED', 'You are not authorized to perform this action');

// Success messages
define('SUCCESS_LOGIN', 'Login successful');
define('SUCCESS_REGISTRATION', 'Registration successful. Please login.');
define('SUCCESS_APPOINTMENT_BOOKED', 'Appointment booked successfully');
define('SUCCESS_APPOINTMENT_APPROVED', 'Appointment approved successfully');
?>
