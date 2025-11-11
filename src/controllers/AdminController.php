<?php
/**
 * Admin Controller - System Administration Functions
 */

// Suppress warnings for clean JSON output
error_reporting(E_ERROR | E_PARSE);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/constants.php';

class AdminController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getConnection();
    }
    
    /**
     * Get system-wide statistics
     */
    public function getSystemStats() {
        try {
            $stats = array();
            
            // Total users
            $sql = "SELECT COUNT(*) as count FROM users";
            $result = $this->db->query($sql);
            if ($result) {
                $stats['total_users'] = $result->fetch_assoc()['count'];
            } else {
                $stats['total_users'] = 0;
            }
            
            // Total branches (from configuration)
            $stats['total_branches'] = count(BRANCHES);
            
            // Total appointments (check if table exists)
            $sql = "SELECT COUNT(*) as count FROM appointments";
            $result = $this->db->query($sql);
            if ($result) {
                $stats['total_appointments'] = $result->fetch_assoc()['count'];
            } else {
                $stats['total_appointments'] = 0;
            }
            
            // Pending appointments
            $sql = "SELECT COUNT(*) as count FROM appointments WHERE status = 'pending'";
            $result = $this->db->query($sql);
            if ($result) {
                $stats['pending_appointments'] = $result->fetch_assoc()['count'];
            } else {
                $stats['pending_appointments'] = 0;
            }
            
            // System alerts (active logs from last 24 hours)
            $sql = "SELECT COUNT(*) as count FROM system_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
            $result = $this->db->query($sql);
            if ($result) {
                $stats['system_alerts'] = $result->fetch_assoc()['count'];
            } else {
                $stats['system_alerts'] = 0;
            }
            
            // Additional stats
            // Today's appointments
            $sql = "SELECT COUNT(*) as count FROM appointments WHERE DATE(appointment_date) = CURDATE()";
            $result = $this->db->query($sql);
            if ($result) {
                $stats['todays_appointments'] = $result->fetch_assoc()['count'];
            } else {
                $stats['todays_appointments'] = 0;
            }
            
            // This week's appointments
            $sql = "SELECT COUNT(*) as count FROM appointments WHERE YEARWEEK(appointment_date) = YEARWEEK(CURDATE())";
            $result = $this->db->query($sql);
            if ($result) {
                $stats['week_appointments'] = $result->fetch_assoc()['count'];
            } else {
                $stats['week_appointments'] = 0;
            }
            
            return array('success' => true, 'stats' => $stats);
            
        } catch (Exception $e) {
            error_log("Get system stats error: " . $e->getMessage());
            return array('success' => false, 'message' => 'Failed to load system statistics: ' . $e->getMessage());
        }
    }
    
    /**
     * Get all users with branch information
     */
    public function getAllUsers() {
        try {
            $sql = "SELECT u.*, b.name as branch_name 
                    FROM users u 
                    LEFT JOIN branches b ON u.branch_id = b.id 
                    ORDER BY u.created_at DESC";
            
            $result = $this->db->query($sql);
            
            $users = array();
            while ($row = $result->fetch_assoc()) {
                // Check which name column exists and use it
                $fullName = '';
                if (isset($row['full_name'])) {
                    $fullName = $row['full_name'];
                } elseif (isset($row['name'])) {
                    $fullName = $row['name'];
                } else {
                    // Fallback: use email prefix if no name field
                    $fullName = explode('@', $row['email'])[0];
                }
                
                // Split name into first and last name for display
                $nameParts = explode(' ', $fullName, 2);
                $firstName = $nameParts[0];
                $lastName = isset($nameParts[1]) ? $nameParts[1] : '';
                
                $users[] = array(
                    'id' => $row['id'],
                    'name' => $fullName,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $row['email'],
                    'phone' => $row['phone'] ?? '',
                    'role' => $row['role'],
                    'branch_name' => $row['branch_name'] ?? 'Unknown Branch',
                    'created_at' => $row['created_at'],
                    'last_login' => '' // No last_login field in current schema
                );
            }
            
            return array('success' => true, 'users' => $users);
            
        } catch (Exception $e) {
            error_log("Get all users error: " . $e->getMessage());
            return array('success' => false, 'message' => 'Failed to load users');
        }
    }
    
    /**
     * Get all branches with statistics
     */
    public function getAllBranches() {
        try {
            $sql = "SELECT b.*, 
                           COUNT(DISTINCT u.id) as user_count,
                           COUNT(CASE WHEN a.status = 'pending' THEN 1 END) as pending_count,
                           COUNT(a.id) as total_appointments
                    FROM branches b 
                    LEFT JOIN users u ON b.id = u.branch_id 
                    LEFT JOIN appointments a ON b.id = a.branch_id 
                    GROUP BY b.id 
                    ORDER BY b.name";
            
            $result = $this->db->query($sql);
            
            $branches = array();
            while ($row = $result->fetch_assoc()) {
                $branches[] = array(
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'address' => $row['address'] ?? 'N/A',
                    'phone' => $row['phone'] ?? 'N/A',
                    'email' => $row['email'] ?? 'N/A',
                    'user_count' => $row['user_count'],
                    'pending_count' => $row['pending_count'],
                    'total_appointments' => $row['total_appointments']
                );
            }
            
            return array('success' => true, 'branches' => $branches);
            
        } catch (Exception $e) {
            error_log("Get all branches error: " . $e->getMessage());
            return array('success' => false, 'message' => 'Failed to load branches');
        }
    }
    
    /**
     * Get recent system logs
     */
    public function getSystemLogs($limit = 50) {
        try {
            $limit = intval($limit);
            if ($limit <= 0) $limit = 50;
            
            $sql = "SELECT l.*, u.name, u.email 
                    FROM system_logs l 
                    LEFT JOIN users u ON l.user_id = u.id 
                    ORDER BY l.created_at DESC 
                    LIMIT ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $logs = array();
            while ($row = $result->fetch_assoc()) {
                $logs[] = array(
                    'id' => $row['id'],
                    'user_name' => $row['name'] ?? 'Unknown User',
                    'user_email' => $row['email'] ?? '',
                    'action' => $row['action'] ?? 'unknown',
                    'description' => $row['description'] ?? 'No description',
                    'ip_address' => $row['ip_address'] ?? 'N/A',
                    'created_at' => $row['created_at']
                );
            }
            
            return array('success' => true, 'logs' => $logs);
            
        } catch (Exception $e) {
            error_log("Get system logs error: " . $e->getMessage());
            return array('success' => false, 'message' => 'Failed to load system logs');
        }
    }
    
    /**
     * Get all appointments across all branches for admin overview
     */
    public function getAllAppointments($filters = array()) {
        try {
            $where_conditions = array();
            $params = array();
            $param_types = '';
            
            // Base SQL with actual branches table
            $sql = "SELECT a.*, 
                           u.name as patient_name, u.email as patient_email,
                           b.name as branch_name
                    FROM appointments a 
                    LEFT JOIN users u ON a.patient_id = u.id
                    LEFT JOIN branches b ON a.branch_id = b.id";
            
            // Apply filters
            if (!empty($filters['date'])) {
                $where_conditions[] = "DATE(a.appointment_date) = ?";
                $params[] = $filters['date'];
                $param_types .= 's';
            }
            
            if (!empty($filters['status'])) {
                $where_conditions[] = "a.status = ?";
                $params[] = $filters['status'];
                $param_types .= 's';
            }
            
            if (!empty($filters['branch_id'])) {
                $where_conditions[] = "a.branch_id = ?";
                $params[] = $filters['branch_id'];
                $param_types .= 'i';
            }
            
            // Add WHERE clause if we have conditions
            if (!empty($where_conditions)) {
                $sql .= " WHERE " . implode(' AND ', $where_conditions);
            }
            
            $sql .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC LIMIT 50";
            
            if (!empty($params)) {
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param($param_types, ...$params);
                $stmt->execute();
                $result = $stmt->get_result();
            } else {
                $result = $this->db->query($sql);
            }
            
            $appointments = array();
            while ($row = $result->fetch_assoc()) {
                $appointments[] = array(
                    'id' => $row['id'],
                    'patient_name' => $row['patient_name'],
                    'patient_email' => $row['patient_email'],
                    'appointment_date' => $row['appointment_date'],
                    'appointment_time' => $row['appointment_time'],
                    'status' => $row['status'],
                    'branch_name' => $row['branch_name'] ?? 'Unknown Branch',
                    'notes' => $row['notes'] ?? '',
                    'created_at' => $row['created_at']
                );
            }
            
            return array('success' => true, 'appointments' => $appointments);
            
        } catch (Exception $e) {
            error_log("Get all appointments error: " . $e->getMessage());
            return array('success' => false, 'message' => 'Failed to load appointments');
        }
    }
    
    /**
     * Get analytics data for reports
     */
    public function getAnalyticsData($start_date = null, $end_date = null) {
        try {
            $analytics = array();
            $where_clause = "";
            $params = array();
            
            if ($start_date && $end_date) {
                $where_clause = "WHERE DATE(appointment_date) BETWEEN ? AND ?";
                $params = array($start_date, $end_date);
            }
            
            // Today's appointments
            $sql = "SELECT COUNT(*) as count FROM appointments WHERE DATE(appointment_date) = CURDATE()";
            $result = $this->db->query($sql);
            $analytics['todays_appointments'] = $result->fetch_assoc()['count'];
            
            // This week's appointments
            $sql = "SELECT COUNT(*) as count FROM appointments WHERE YEARWEEK(appointment_date) = YEARWEEK(CURDATE())";
            $result = $this->db->query($sql);
            $analytics['week_appointments'] = $result->fetch_assoc()['count'];
            
            // This month's appointments
            $sql = "SELECT COUNT(*) as count FROM appointments WHERE YEAR(appointment_date) = YEAR(CURDATE()) AND MONTH(appointment_date) = MONTH(CURDATE())";
            $result = $this->db->query($sql);
            $analytics['month_appointments'] = $result->fetch_assoc()['count'];
            
            // Completion rate
            $sql = "SELECT 
                        COUNT(*) as total,
                        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed
                    FROM appointments 
                    WHERE DATE(appointment_date) <= CURDATE()";
            $result = $this->db->query($sql);
            $completion_data = $result->fetch_assoc();
            $analytics['completion_rate'] = $completion_data['total'] > 0 ? 
                round(($completion_data['completed'] / $completion_data['total']) * 100) : 0;
            
            // Branch performance
            $sql = "SELECT b.name, 
                           COUNT(a.id) as total_appointments,
                           COUNT(CASE WHEN a.status = 'completed' THEN 1 END) as completed_appointments
                    FROM branches b 
                    LEFT JOIN appointments a ON b.id = a.branch_id 
                    GROUP BY b.id, b.name 
                    ORDER BY total_appointments DESC";
            $result = $this->db->query($sql);
            
            $branch_performance = array();
            while ($row = $result->fetch_assoc()) {
                $completion_rate = $row['total_appointments'] > 0 ? 
                    round(($row['completed_appointments'] / $row['total_appointments']) * 100) : 0;
                    
                $branch_performance[] = array(
                    'branch_name' => $row['name'],
                    'total_appointments' => $row['total_appointments'],
                    'completed_appointments' => $row['completed_appointments'],
                    'completion_rate' => $completion_rate
                );
            }
            $analytics['branch_performance'] = $branch_performance;
            
            // Monthly trend (last 6 months)
            $sql = "SELECT 
                        YEAR(appointment_date) as year,
                        MONTH(appointment_date) as month,
                        COUNT(*) as count
                    FROM appointments 
                    WHERE appointment_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                    GROUP BY YEAR(appointment_date), MONTH(appointment_date)
                    ORDER BY year, month";
            $result = $this->db->query($sql);
            
            $monthly_trend = array();
            while ($row = $result->fetch_assoc()) {
                $monthly_trend[] = array(
                    'month' => $row['year'] . '-' . sprintf('%02d', $row['month']),
                    'count' => $row['count']
                );
            }
            $analytics['monthly_trend'] = $monthly_trend;
            
            return array('success' => true, 'analytics' => $analytics);
            
        } catch (Exception $e) {
            error_log("Get analytics data error: " . $e->getMessage());
            return array('success' => false, 'message' => 'Failed to load analytics data');
        }
    }
    
    /**
     * Generate report data based on type and date range
     */
    public function generateReport($report_type, $start_date, $end_date) {
        try {
            $report_data = array();
            
            switch ($report_type) {
                case 'appointments':
                    $sql = "SELECT a.*, 
                                   u.first_name, u.last_name, u.email as patient_email,
                                   b.name as branch_name
                            FROM appointments a 
                            LEFT JOIN users u ON a.user_id = u.id 
                            LEFT JOIN branches b ON a.branch_id = b.id
                            WHERE DATE(a.appointment_date) BETWEEN ? AND ?
                            ORDER BY a.appointment_date, a.appointment_time";
                    break;
                    
                case 'users':
                    $sql = "SELECT u.*, b.name as branch_name,
                                   COUNT(a.id) as appointment_count
                            FROM users u 
                            LEFT JOIN branches b ON u.branch_id = b.id 
                            LEFT JOIN appointments a ON u.id = a.user_id AND DATE(a.created_at) BETWEEN ? AND ?
                            WHERE DATE(u.created_at) BETWEEN ? AND ?
                            GROUP BY u.id
                            ORDER BY u.created_at DESC";
                    break;
                    
                case 'branches':
                    $sql = "SELECT b.*,
                                   COUNT(DISTINCT u.id) as user_count,
                                   COUNT(a.id) as appointment_count,
                                   COUNT(CASE WHEN a.status = 'completed' THEN 1 END) as completed_count
                            FROM branches b 
                            LEFT JOIN users u ON b.id = u.branch_id 
                            LEFT JOIN appointments a ON b.id = a.branch_id AND DATE(a.appointment_date) BETWEEN ? AND ?
                            GROUP BY b.id 
                            ORDER BY appointment_count DESC";
                    break;
                    
                case 'system':
                    $sql = "SELECT DATE(created_at) as log_date,
                                   action,
                                   COUNT(*) as action_count
                            FROM system_logs 
                            WHERE DATE(created_at) BETWEEN ? AND ?
                            GROUP BY DATE(created_at), action
                            ORDER BY log_date DESC, action_count DESC";
                    break;
                    
                default:
                    return array('success' => false, 'message' => 'Invalid report type');
            }
            
            $stmt = $this->db->prepare($sql);
            if ($report_type === 'users') {
                $stmt->bind_param("ssss", $start_date, $end_date, $start_date, $end_date);
            } else {
                $stmt->bind_param("ss", $start_date, $end_date);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            
            $data = array();
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            
            $report_data = array(
                'type' => $report_type,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'generated_at' => date('Y-m-d H:i:s'),
                'total_records' => count($data),
                'data' => $data
            );
            
            return array('success' => true, 'report' => $report_data);
            
        } catch (Exception $e) {
            error_log("Generate report error: " . $e->getMessage());
            return array('success' => false, 'message' => 'Failed to generate report');
        }
    }

    /**
     * Add new user
     */
    public function addUser() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return array('success' => false, 'message' => 'Invalid request method');
        }
        
        try {
            // Get JSON input
            $json_input = file_get_contents('php://input');
            error_log("AddUser JSON input: " . $json_input); // Debug log
            
            $input = json_decode($json_input, true);
            
            // Debug log for parsed data
            error_log("AddUser parsed input: " . print_r($input, true));
            
            $first_name = $input['first_name'] ?? '';
            $last_name = $input['last_name'] ?? '';
            $email = $input['email'] ?? '';
            $phone = $input['phone'] ?? '';
            $password = $input['password'] ?? '';
            $role = $input['role'] ?? '';
            $branch_id = $input['branch_id'] ?? '';
            
            // Debug log for individual fields
            error_log("AddUser fields - first_name: '$first_name', last_name: '$last_name', email: '$email', role: '$role', branch_id: '$branch_id'");
            
            // Combine first and last name
            $full_name = trim($first_name . ' ' . $last_name);
            
            // Validation
            if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($role)) {
                return array('success' => false, 'message' => 'All required fields must be filled');
            }
            
            if (!in_array($role, ['admin', 'staff', 'patient'])) {
                return array('success' => false, 'message' => 'Invalid role specified');
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return array('success' => false, 'message' => 'Invalid email format');
            }
            
            // Check if email already exists
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                return array('success' => false, 'message' => 'Email already exists');
            }
            
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user
            // Convert branch_id to integer or NULL if empty
            if (!empty($branch_id)) {
                $branch_id_int = intval($branch_id);
                $sql = "INSERT INTO users (name, email, phone, password, role, branch_id) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param("sssssi", $full_name, $email, $phone, $hashed_password, $role, $branch_id_int);
            } else {
                // If branch_id is empty, insert NULL
                $sql = "INSERT INTO users (name, email, phone, password, role, branch_id) VALUES (?, ?, ?, ?, ?, NULL)";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param("sssss", $full_name, $email, $phone, $hashed_password, $role);
            }
            
            if ($stmt->execute()) {
                $user_id = $this->db->insert_id;
                $admin_id = getSessionUserId();
                $this->logActivity($admin_id, 'user_created', "Created new user: $full_name ($email) with role: $role");
                
                return array('success' => true, 'message' => 'User created successfully', 'user_id' => $user_id);
            } else {
                return array('success' => false, 'message' => 'Failed to create user');
            }
            
        } catch (Exception $e) {
            error_log("Add user error: " . $e->getMessage());
            return array('success' => false, 'message' => 'Failed to create user');
        }
    }
    
    /**
     * Update user
     */
    public function updateUser() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return array('success' => false, 'message' => 'Invalid request method');
        }
        
        try {
            $user_id = $_POST['user_id'] ?? '';
            $first_name = $_POST['first_name'] ?? '';
            $last_name = $_POST['last_name'] ?? '';
            $email = $_POST['email'] ?? '';
            $role = $_POST['role'] ?? '';
            $branch_id = $_POST['branch_id'] ?? '';
            
            if (empty($user_id) || empty($first_name) || empty($last_name) || empty($email) || empty($role)) {
                return array('success' => false, 'message' => 'All required fields must be filled');
            }
            
            // Update user
            // Convert branch_id to integer or NULL if empty
            if (!empty($branch_id)) {
                $branch_id_int = intval($branch_id);
                $sql = "UPDATE users SET first_name = ?, last_name = ?, email = ?, role = ?, branch_id = ? WHERE id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param("ssssii", $first_name, $last_name, $email, $role, $branch_id_int, $user_id);
            } else {
                // If branch_id is empty, set to NULL
                $sql = "UPDATE users SET first_name = ?, last_name = ?, email = ?, role = ?, branch_id = NULL WHERE id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param("ssssi", $first_name, $last_name, $email, $role, $user_id);
            }
            
            if ($stmt->execute()) {
                $admin_id = getSessionUserId();
                $this->logActivity($admin_id, 'user_updated', "Updated user: $first_name $last_name (ID: $user_id)");
                
                return array('success' => true, 'message' => 'User updated successfully');
            } else {
                return array('success' => false, 'message' => 'Failed to update user');
            }
            
        } catch (Exception $e) {
            error_log("Update user error: " . $e->getMessage());
            return array('success' => false, 'message' => 'Failed to update user');
        }
    }
    
    /**
     * Delete user
     */
    public function deleteUser() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return array('success' => false, 'message' => 'Invalid request method');
        }
        
        try {
            $user_id = $_POST['user_id'] ?? '';
            
            if (empty($user_id)) {
                return array('success' => false, 'message' => 'User ID is required');
            }
            
            // Get user info before deletion
            $stmt = $this->db->prepare("SELECT first_name, last_name, email FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            
            if (!$user) {
                return array('success' => false, 'message' => 'User not found');
            }
            
            // Delete user (this will cascade to appointments if foreign keys are set up)
            $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            
            if ($stmt->execute()) {
                $admin_id = getSessionUserId();
                $this->logActivity($admin_id, 'user_deleted', "Deleted user: {$user['first_name']} {$user['last_name']} ({$user['email']})");
                
                return array('success' => true, 'message' => 'User deleted successfully');
            } else {
                return array('success' => false, 'message' => 'Failed to delete user');
            }
            
        } catch (Exception $e) {
            error_log("Delete user error: " . $e->getMessage());
            return array('success' => false, 'message' => 'Failed to delete user');
        }
    }
    
    /**
     * Approve appointment (admin action)
     */
    public function approveAppointment() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return array('success' => false, 'message' => 'Invalid request method');
        }
        
        try {
            // Get JSON input
            $input = json_decode(file_get_contents('php://input'), true);
            $appointment_id = $input['appointment_id'] ?? '';
            
            if (empty($appointment_id)) {
                return array('success' => false, 'message' => 'Appointment ID is required');
            }
            
            // First check if appointment exists and is pending
            $checkStmt = $this->db->prepare("SELECT id, status, patient_id, appointment_date, appointment_time FROM appointments WHERE id = ?");
            $checkStmt->bind_param("i", $appointment_id);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            
            if ($result->num_rows === 0) {
                return array('success' => false, 'message' => 'Appointment not found');
            }
            
            $appointment = $result->fetch_assoc();
            
            if ($appointment['status'] !== 'pending') {
                return array('success' => false, 'message' => 'Appointment is not pending approval');
            }
            
            // Update appointment status
            $stmt = $this->db->prepare("UPDATE appointments SET status = 'approved', updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("i", $appointment_id);
            
            if ($stmt->execute()) {
                $admin_id = getSessionUserId();
                $this->logActivity(
                    $admin_id, 
                    'appointment_approved', 
                    "Approved appointment ID: $appointment_id for patient ID: {$appointment['patient_id']} on {$appointment['appointment_date']} at {$appointment['appointment_time']}"
                );
                
                return array(
                    'success' => true, 
                    'message' => 'Appointment approved successfully',
                    'appointment_id' => $appointment_id,
                    'new_status' => 'approved'
                );
            } else {
                return array('success' => false, 'message' => 'Failed to update appointment status');
            }
            
        } catch (Exception $e) {
            error_log("Approve appointment error: " . $e->getMessage());
            return array('success' => false, 'message' => 'Failed to approve appointment: ' . $e->getMessage());
        }
    }

    /**
     * Log user activity
     */
    private function logActivity($user_id, $action, $description, $entity_type = null, $entity_id = null) {
        try {
            // Get client IP address
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } elseif (isset($_SERVER['HTTP_X_REAL_IP'])) {
                $ip_address = $_SERVER['HTTP_X_REAL_IP'];
            }
            
            $stmt = $this->db->prepare("INSERT INTO system_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $user_id, $action, $description, $ip_address);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("Log activity error: " . $e->getMessage());
        }
    }
}

// Handle API requests
if (php_sapi_name() !== 'cli') {
    // Check if user is logged in and is admin
    if (!isLoggedIn() || getSessionRole() !== ROLE_ADMIN) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(array('success' => false, 'message' => 'Access denied. Admin privileges required.'));
        exit;
    }
    
    // Get action from GET or POST data
    $action = '';
    if (isset($_GET['action'])) {
        $action = $_GET['action'];
    } else {
        // Check POST data for JSON requests
        $input = json_decode(file_get_contents('php://input'), true);
        if (isset($input['action'])) {
            $action = $input['action'];
        }
    }
    
    // Log the request for debugging
    error_log("AdminController: Action = '$action', Method = " . $_SERVER['REQUEST_METHOD']);
    
    $controller = new AdminController();
    $response = array();
    
    switch ($action) {
        case 'getSystemStats':
            $response = $controller->getSystemStats();
            break;
        case 'getAllUsers':
            $response = $controller->getAllUsers();
            break;
        case 'getAllBranches':
            $response = $controller->getAllBranches();
            break;
        case 'getSystemLogs':
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
            $response = $controller->getSystemLogs($limit);
            break;
        case 'getAllAppointments':
            $filters = array();
            if (isset($_GET['date'])) $filters['date'] = $_GET['date'];
            if (isset($_GET['status'])) $filters['status'] = $_GET['status'];
            if (isset($_GET['branch_id'])) $filters['branch_id'] = intval($_GET['branch_id']);
            $response = $controller->getAllAppointments($filters);
            break;
        case 'getAnalyticsData':
            $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : null;
            $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : null;
            $response = $controller->getAnalyticsData($start_date, $end_date);
            break;
        case 'generateReport':
            $report_type = isset($_GET['report_type']) ? $_GET['report_type'] : '';
            $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
            $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
            
            if (empty($report_type) || empty($start_date) || empty($end_date)) {
                $response = array('success' => false, 'message' => 'Missing required parameters');
            } else {
                $response = $controller->generateReport($report_type, $start_date, $end_date);
            }
            break;
        case 'addUser':
            $response = $controller->addUser();
            break;
        case 'updateUser':
            $response = $controller->updateUser();
            break;
        case 'deleteUser':
            $response = $controller->deleteUser();
            break;
        case 'approveAppointment':
            $response = $controller->approveAppointment();
            break;
        case 'exportUsers':
            $users = $controller->getAllUsers();
            $response = array(
                'success' => true,
                'users' => $users['users'] ?? []
            );
            break;
        default:
            http_response_code(400);
            $response = array(
                'success' => false, 
                'message' => 'Invalid action: ' . $action,
                'debug' => array(
                    'received_action' => $action,
                    'request_method' => $_SERVER['REQUEST_METHOD'],
                    'get_params' => $_GET,
                    'has_post_data' => !empty(file_get_contents('php://input'))
                )
            );
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>