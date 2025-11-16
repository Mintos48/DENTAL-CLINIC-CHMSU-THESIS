<?php
/**
 * User Session API
 * Returns current user session data for dashboard and handles profile operations
 */

// Disable error output that could break JSON
error_reporting(0);
ini_set('display_errors', 0);

// Set JSON header immediately
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Simple session check like the original
if (!isset($_SESSION['user_id']) || !isset($_SESSION['full_name'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Not authenticated'
    ]);
    exit();
}

// Get the action from query parameter or JSON body, default to 'getUserInfo'
$action = $_GET['action'] ?? 'getUserInfo';

// For POST requests, check if action is in JSON body
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if ($input && isset($input['action'])) {
        $action = $input['action'];
    }
}

try {
    // Include database config
    require_once '../../src/config/database.php';
    $conn = Database::getConnection();
    
    switch ($action) {
        case 'getUserInfo':
            getUserInfo($conn);
            break;
        case 'getProfile':
            getProfile($conn);
            break;
        case 'updateProfile':
            updateProfile($conn);
            break;
        case 'changePassword':
            changePassword($conn);
            break;
        case 'getAppointmentStats':
            getAppointmentStats($conn);
            break;
        default:
            getUserInfo($conn);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

function getUserInfo($conn) {
    // Get user data from session (using correct session keys)
    $userId = $_SESSION['user_id'];
    $fullName = $_SESSION['full_name'];  // This is the correct session key
    $email = $_SESSION['email'];
    $role = $_SESSION['role'];
    $branchId = $_SESSION['branch_id'];
    
    // Default branch name
    $branchName = 'Selected Branch';
    $debug_info = [];
    
    // Try to get branch name from database if possible
    if ($branchId) {
        try {
            $debug_info['branch_id'] = $branchId;
            $debug_info['connection_status'] = ($conn && !$conn->connect_error) ? 'success' : 'failed';
            
            if ($conn && !$conn->connect_error) {
                $stmt = $conn->prepare("SELECT name FROM branches WHERE id = ? LIMIT 1");
                $debug_info['statement_prepared'] = ($stmt !== false);
                
                if ($stmt) {
                    $stmt->bind_param("i", $branchId);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $debug_info['rows_found'] = $result->num_rows;
                    
                    if ($result && $result->num_rows > 0) {
                        $branch = $result->fetch_assoc();
                        $debug_info['branch_data'] = $branch;
                        
                        if (isset($branch['name']) && !empty($branch['name'])) {
                            $branchName = $branch['name'];
                            $debug_info['branch_name_set'] = true;
                        }
                    }
                    $stmt->close();
                }
            }
        } catch (Exception $e) {
            $debug_info['error'] = $e->getMessage();
        }
    }
    
    // Generate initials safely
    $initials = 'P'; // Default
    if (!empty($fullName)) {
        $nameParts = explode(' ', trim($fullName));
        $initials = '';
        foreach ($nameParts as $part) {
            if (!empty($part)) {
                $initials .= strtoupper($part[0]);
                if (strlen($initials) >= 2) break;
            }
        }
        if (empty($initials)) {
            $initials = strtoupper($fullName[0]);
        }
    }
    
    // Return successful response
    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $userId,
            'name' => $fullName,
            'email' => $email,
            'role' => $role,
            'branch_id' => $branchId,
            'branch_name' => $branchName,
            'member_since' => date('Y-m-d'), // Default to today
            'last_login' => null,
            'current_session' => date('Y-m-d H:i:s'),
            'initials' => $initials
        ],
        'debug' => $debug_info  // Temporary debug info
    ]);
}

function getProfile($conn) {
    $userId = $_SESSION['user_id'];
    
    $stmt = $conn->prepare("
        SELECT id, name, email, phone, address, date_of_birth, gender, 
               emergency_contact_name, emergency_contact_phone, 
               receive_notifications, receive_email_reminders, receive_sms_reminders,
               created_at, last_login_at
        FROM users 
        WHERE id = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $profile = $result->fetch_assoc();
        echo json_encode([
            'success' => true,
            'profile' => $profile
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Profile not found'
        ]);
    }
    $stmt->close();
}

function updateProfile($conn) {
    $userId = $_SESSION['user_id'];
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Debug logging
    error_log("Profile update request - User ID: " . $userId);
    error_log("Profile update payload: " . json_encode($input));
    
    if (!$input) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid input data'
        ]);
        return;
    }
    
    // Validate required fields
    if (empty($input['name']) || empty($input['email'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Name and email are required'
        ]);
        return;
    }
    
    // Check if email is already taken by another user
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $input['email'], $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Email address is already in use by another account'
        ]);
        $stmt->close();
        return;
    }
    $stmt->close();
    
    // Update profile
    $stmt = $conn->prepare("
        UPDATE users 
        SET name = ?, email = ?, phone = ?, address = ?, date_of_birth = ?, 
            gender = ?, emergency_contact_name = ?, emergency_contact_phone = ?,
            receive_notifications = ?, receive_email_reminders = ?, receive_sms_reminders = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    
    // Prepare values for binding
    $name = $input['name'];
    $email = $input['email'];
    $phone = !empty($input['phone']) ? $input['phone'] : null;
    $address = !empty($input['address']) ? $input['address'] : null;
    $date_of_birth = !empty($input['date_of_birth']) ? $input['date_of_birth'] : null;
    $gender = !empty($input['gender']) ? $input['gender'] : null;
    $emergency_contact_name = !empty($input['emergency_contact_name']) ? $input['emergency_contact_name'] : null;
    $emergency_contact_phone = !empty($input['emergency_contact_phone']) ? $input['emergency_contact_phone'] : null;
    $receive_notifications = $input['receive_notifications'] ? 1 : 0;
    $receive_email_reminders = $input['receive_email_reminders'] ? 1 : 0;
    $receive_sms_reminders = $input['receive_sms_reminders'] ? 1 : 0;
    
    $stmt->bind_param(
        "ssssssssiiii",
        $name,
        $email,
        $phone,
        $address,
        $date_of_birth,
        $gender,
        $emergency_contact_name,
        $emergency_contact_phone,
        $receive_notifications,
        $receive_email_reminders,
        $receive_sms_reminders,
        $userId
    );
    
    if ($stmt->execute()) {
        // Update session variables
        $_SESSION['full_name'] = $input['name'];
        $_SESSION['email'] = $input['email'];
        
        echo json_encode([
            'success' => true,
            'message' => 'Profile updated successfully'
        ]);
    } else {
        // Log the actual error for debugging
        error_log("Profile update failed: " . $stmt->error);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update profile: ' . $stmt->error
        ]);
    }
    $stmt->close();
}

function changePassword($conn) {
    $userId = $_SESSION['user_id'];
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || empty($input['current_password']) || empty($input['new_password'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Current password and new password are required'
        ]);
        return;
    }
    
    // Verify current password
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'User not found'
        ]);
        $stmt->close();
        return;
    }
    
    $user = $result->fetch_assoc();
    $stmt->close();
    
    if (!password_verify($input['current_password'], $user['password'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Current password is incorrect'
        ]);
        return;
    }
    
    // Update password
    $newPasswordHash = password_hash($input['new_password'], PASSWORD_DEFAULT);
    $stmt = $conn->prepare("
        UPDATE users 
        SET password = ?, password_changed_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    $stmt->bind_param("si", $newPasswordHash, $userId);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Password changed successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to change password'
        ]);
    }
    $stmt->close();

}

function getAppointmentStats($conn) {
    $userId = $_SESSION['user_id'];
    
    // Get total appointments
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM appointments WHERE patient_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $total = $result->fetch_assoc()['total'];
    $stmt->close();
    
    // Get completed appointments
    $stmt = $conn->prepare("SELECT COUNT(*) as completed FROM appointments WHERE patient_id = ? AND status = 'completed'");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $completed = $result->fetch_assoc()['completed'];
    $stmt->close();
    
    // Get pending/upcoming appointments (approved, pending, checked_in, in_progress)
    $stmt = $conn->prepare("
        SELECT COUNT(*) as pending 
        FROM appointments 
        WHERE patient_id = ? 
        AND status IN ('pending', 'approved', 'checked_in', 'in_progress')
        AND appointment_date >= CURDATE()
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $pending = $result->fetch_assoc()['pending'];
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'stats' => [
            'total' => (int)$total,
            'completed' => (int)$completed,
            'pending' => (int)$pending
        ]
    ]);
}

?>