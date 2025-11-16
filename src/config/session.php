<?php
/**
 * Session Configuration and Management
 */

// Include constants
require_once __DIR__ . '/constants.php';

// Start session only if not already started and not disabled
if (!defined('NO_SESSION') && session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && 
           isset($_SESSION['role']) && 
           isset($_SESSION['last_activity']);
}

/**
 * Check session timeout
 */
function checkSessionTimeout() {
    if (isLoggedIn()) {
        $current_time = time();
        $last_activity = $_SESSION['last_activity'];
        
        if (($current_time - $last_activity) > SESSION_TIMEOUT) {
            destroySession();
            return false;
        }
        
        // Update last activity time
        $_SESSION['last_activity'] = $current_time;
        return true;
    }
    
    return false;
}

/**
 * Create user session
 */
function createSession($user_id, $branch_id, $role, $email, $full_name) {
    $_SESSION['user_id'] = $user_id;
    $_SESSION['branch_id'] = $branch_id;
    $_SESSION['role'] = $role;
    $_SESSION['email'] = $email;
    $_SESSION['full_name'] = $full_name;
    $_SESSION['last_activity'] = time();
}

/**
 * Destroy session
 */
function destroySession() {
    $_SESSION = [];
    session_destroy();
}

/**
 * Get current user ID
 */
function getCurrentUserId() {
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
}

/**
 * Get current branch ID
 */
function getCurrentBranchId() {
    return isset($_SESSION['branch_id']) ? $_SESSION['branch_id'] : null;
}

/**
 * Get current user role
 */
function getCurrentUserRole() {
    return isset($_SESSION['role']) ? $_SESSION['role'] : null;
}

/**
 * Get current user info
 */
function getCurrentUser() {
    if (isLoggedIn()) {
        return [
            'user_id' => $_SESSION['user_id'],
            'branch_id' => $_SESSION['branch_id'],
            'role' => $_SESSION['role'],
            'email' => $_SESSION['email'],
            'full_name' => $_SESSION['full_name']
        ];
    }
    return null;
}

/**
 * Session getter functions (aliases for dashboard compatibility)
 */
function getSessionUserId() {
    return getCurrentUserId();
}

function getSessionBranchId() {
    return getCurrentBranchId();
}

function getSessionRole() {
    return getCurrentUserRole();
}

function getSessionEmail() {
    return isset($_SESSION['email']) ? $_SESSION['email'] : '';
}

function getSessionName() {
    return isset($_SESSION['full_name']) ? $_SESSION['full_name'] : '';
}

/**
 * Check if user has specific role
 */
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

/**
 * Require login middleware
 */
function requireLogin() {
    if (!isLoggedIn() || !checkSessionTimeout()) {
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit();
    }
}

/**
 * Require specific role middleware
 */
function requireRole($role) {
    requireLogin();
    
    if (!hasRole($role)) {
        http_response_code(403);
        die(ERROR_UNAUTHORIZED);
    }
}

// Check session timeout on page load (skip for API endpoints)
if (!defined('API_ENDPOINT') && isLoggedIn()) {
    if (!checkSessionTimeout()) {
        $_SESSION['timeout_message'] = ERROR_SESSION_EXPIRED;
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit();
    }
}
?>
