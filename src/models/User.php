<?php
/**
 * User Model
 */

require_once dirname(__DIR__) . '/config/database.php';

class User {
    private $db;
    
    public function __construct() {
        $this->db = Database::getConnection();
    }
    
    /**
     * Check if user exists by email and branch
     */
    public function userExists($email, $branch_id) {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ? AND branch_id = ?");
        $stmt->bind_param("si", $email, $branch_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }
    
    /**
     * Register new user
     */
    public function register($email, $password, $full_name, $branch_id, $role = ROLE_PATIENT) {
        // Hash password
        $password_hash = password_hash($password, PASSWORD_ALGO, PASSWORD_OPTIONS);
        
        $stmt = $this->db->prepare(
            "INSERT INTO users (email, password, full_name, branch_id, role, created_at) 
             VALUES (?, ?, ?, ?, ?, NOW())"
        );
        
        $stmt->bind_param("sssii", $email, $password_hash, $full_name, $branch_id, $role);
        
        if ($stmt->execute()) {
            return [
                'success' => true,
                'user_id' => $this->db->insert_id,
                'message' => SUCCESS_REGISTRATION
            ];
        } else {
            return [
                'success' => false,
                'message' => ERROR_REGISTRATION_FAILED
            ];
        }
    }
    
    /**
     * Login user
     */
    public function login($email, $password, $branch_id) {
        $stmt = $this->db->prepare(
            "SELECT id, email, password, full_name, role, branch_id 
             FROM users 
             WHERE email = ? AND branch_id = ?"
        );
        
        $stmt->bind_param("si", $email, $branch_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return [
                'success' => false,
                'message' => ERROR_INVALID_CREDENTIALS
            ];
        }
        
        $user = $result->fetch_assoc();
        
        // Verify password
        if (!password_verify($password, $user['password'])) {
            return [
                'success' => false,
                'message' => ERROR_INVALID_CREDENTIALS
            ];
        }
        
        return [
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'full_name' => $user['full_name'],
                'role' => $user['role'],
                'branch_id' => $user['branch_id']
            ],
            'message' => SUCCESS_LOGIN
        ];
    }
    
    /**
     * Get user by ID
     */
    public function getUserById($user_id) {
        $stmt = $this->db->prepare(
            "SELECT id, email, full_name, role, branch_id, created_at 
             FROM users 
             WHERE id = ?"
        );
        
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->num_rows > 0 ? $result->fetch_assoc() : null;
    }
    
    /**
     * Update user profile
     */
    public function updateProfile($user_id, $full_name, $phone = null) {
        $stmt = $this->db->prepare(
            "UPDATE users SET full_name = ?, phone = ?, updated_at = NOW() WHERE id = ?"
        );
        
        $stmt->bind_param("ssi", $full_name, $phone, $user_id);
        
        return $stmt->execute();
    }
    
    /**
     * Change password
     */
    public function changePassword($user_id, $old_password, $new_password) {
        // Get current password
        $user = $this->getUserById($user_id);
        
        if (!$user || !password_verify($old_password, $user['password'])) {
            return [
                'success' => false,
                'message' => 'Current password is incorrect'
            ];
        }
        
        $password_hash = password_hash($new_password, PASSWORD_ALGO, PASSWORD_OPTIONS);
        $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $password_hash, $user_id);
        
        if ($stmt->execute()) {
            return [
                'success' => true,
                'message' => 'Password changed successfully'
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Failed to change password'
        ];
    }
}
?>
