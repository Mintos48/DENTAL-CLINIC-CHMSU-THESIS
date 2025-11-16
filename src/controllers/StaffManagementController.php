<?php
/**
 * StaffManagementController - Handles staff management for dentists
 */

if (!defined('API_ENDPOINT')) {
    define('API_ENDPOINT', true);
}

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/config/session.php';

class StaffManagementController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getConnection();
    }
    
    /**
     * Get all staff members in the dentist's branch
     */
    public function getBranchStaff() {
        try {
            if (!isLoggedIn() || !in_array(getSessionRole(), [ROLE_DENTIST, ROLE_ADMIN])) {
                return ['success' => false, 'message' => 'Unauthorized access'];
            }
            
            $stmt = $this->db->prepare("
                SELECT 
                    u.id, u.name, u.email, u.phone, u.role, u.status, u.last_login,
                    u.created_at,
                    GROUP_CONCAT(DISTINCT tt.name) as specialization
                FROM users u
                LEFT JOIN staff_specializations sp ON u.id = sp.staff_id
                LEFT JOIN treatment_types tt ON sp.treatment_type_id = tt.id
                WHERE u.role IN ('staff', 'dentist') AND u.branch_id = ?
                GROUP BY u.id
                ORDER BY u.name ASC
            ");
            
            $branch_id = getSessionBranchId();
            $stmt->bind_param('i', $branch_id);
            $stmt->execute();
            $staff = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            // Process the data
            foreach ($staff as &$member) {
                $member['specialization'] = $member['specialization'] ?: '';
                // Convert status to is_active boolean for frontend compatibility
                $member['is_active'] = ($member['status'] === 'active');
            }
            
            return [
                'success' => true,
                'staff' => $staff
            ];
            foreach ($staff as &$member) {
                $stmt = $this->db->prepare("
                    SELECT day_of_week, start_time, end_time, is_active
                    FROM staff_schedules 
                    WHERE staff_id = ? 
                    ORDER BY 
                        CASE day_of_week 
                            WHEN 'monday' THEN 1
                            WHEN 'tuesday' THEN 2
                            WHEN 'wednesday' THEN 3
                            WHEN 'thursday' THEN 4
                            WHEN 'friday' THEN 5
                            WHEN 'saturday' THEN 6
                            WHEN 'sunday' THEN 7
                        END
                ");
                $stmt->bind_param('i', $member['id']);
                $stmt->execute();
                $member['schedules'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            }
            
            return [
                'success' => true,
                'staff' => $staff
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error fetching staff: ' . $e->getMessage()];
        }
    }
    
    /**
     * Update staff member information
     */
    public function updateStaffMember() {
        try {
            if (!isLoggedIn() || !in_array(getSessionRole(), [ROLE_DENTIST, ROLE_ADMIN])) {
                return ['success' => false, 'message' => 'Unauthorized access'];
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $staff_id = $input['staff_id'] ?? '';
            $name = trim($input['name'] ?? '');
            $email = trim($input['email'] ?? '');
            $phone = trim($input['phone'] ?? '');
            $address = trim($input['address'] ?? '');
            $emergency_contact_name = trim($input['emergency_contact_name'] ?? '');
            $emergency_contact_phone = trim($input['emergency_contact_phone'] ?? '');
            $specializations = $input['specializations'] ?? [];
            $schedules = $input['schedules'] ?? [];
            
            if (empty($staff_id) || empty($name) || empty($email)) {
                return ['success' => false, 'message' => 'Staff ID, name and email are required'];
            }
            
            // Verify staff member exists and belongs to user's branch
            $stmt = $this->db->prepare("
                SELECT id FROM users 
                WHERE id = ? AND role = 'staff' AND branch_id = ?
            ");
            $branch_id = getSessionBranchId();
            $stmt->bind_param('ii', $staff_id, $branch_id);
            $stmt->execute();
            $staff = $stmt->get_result()->fetch_assoc();
            
            if (!$staff) {
                return ['success' => false, 'message' => 'Staff member not found in your branch'];
            }
            
            // Check email uniqueness (excluding current staff member)
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->bind_param('si', $email, $staff_id);
            $stmt->execute();
            if ($stmt->get_result()->fetch_assoc()) {
                return ['success' => false, 'message' => 'Email already exists'];
            }
            
            $this->db->begin_transaction();
            
            // Update staff information
            $stmt = $this->db->prepare("
                UPDATE users 
                SET name = ?, email = ?, phone = ?, address = ?, 
                    emergency_contact_name = ?, emergency_contact_phone = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->bind_param('ssssssi', $name, $email, $phone, $address, $emergency_contact_name, $emergency_contact_phone, $staff_id);
            $stmt->execute();
            
            // Update specializations
            if (isset($input['specializations'])) {
                // Remove existing specializations
                $stmt = $this->db->prepare("DELETE FROM staff_specializations WHERE staff_id = ?");
                $stmt->bind_param('i', $staff_id);
                $stmt->execute();
                
                // Add new specializations
                if (!empty($specializations)) {
                    $stmt = $this->db->prepare("INSERT INTO staff_specializations (staff_id, treatment_type_id) VALUES (?, ?)");
                    foreach ($specializations as $treatment_type_id) {
                        $stmt->bind_param('ii', $staff_id, $treatment_type_id);
                        $stmt->execute();
                    }
                }
            }
            
            // Update schedules
            if (isset($input['schedules'])) {
                // Remove existing schedules
                $stmt = $this->db->prepare("DELETE FROM staff_schedules WHERE staff_id = ?");
                $stmt->bind_param('i', $staff_id);
                $stmt->execute();
                
                // Add new schedules
                if (!empty($schedules)) {
                    $stmt = $this->db->prepare("
                        INSERT INTO staff_schedules (staff_id, day_of_week, start_time, end_time, is_active) 
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    foreach ($schedules as $schedule) {
                        $is_active = $schedule['is_active'] ?? true;
                        $stmt->bind_param('isssi', 
                            $staff_id, 
                            $schedule['day_of_week'], 
                            $schedule['start_time'], 
                            $schedule['end_time'], 
                            $is_active
                        );
                        $stmt->execute();
                    }
                }
            }
            
            $this->db->commit();
            
            return ['success' => true, 'message' => 'Staff member updated successfully'];
            
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => 'Error updating staff member: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get available treatment types for specializations
     */
    public function getTreatmentTypes() {
        try {
            if (!isLoggedIn()) {
                return ['success' => false, 'message' => 'Unauthorized access'];
            }
            
            $stmt = $this->db->prepare("
                SELECT id, name, category_id, base_price, duration_minutes
                FROM treatment_types 
                WHERE is_active = 1 
                ORDER BY name ASC
            ");
            $stmt->execute();
            $treatments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            return [
                'success' => true,
                'treatments' => $treatments
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error fetching treatment types: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get staff performance metrics
     */
    public function getStaffPerformance() {
        try {
            if (!isLoggedIn() || !in_array(getSessionRole(), [ROLE_DENTIST, ROLE_ADMIN])) {
                return ['success' => false, 'message' => 'Unauthorized access'];
            }
            
            $period = $_GET['period'] ?? 'month'; // month, quarter, year
            
            $date_filter = '';
            switch ($period) {
                case 'quarter':
                    $date_filter = "AND a.appointment_date >= DATE_SUB(CURRENT_DATE, INTERVAL 3 MONTH)";
                    break;
                case 'year':
                    $date_filter = "AND a.appointment_date >= DATE_SUB(CURRENT_DATE, INTERVAL 1 YEAR)";
                    break;
                default: // month
                    $date_filter = "AND a.appointment_date >= DATE_SUB(CURRENT_DATE, INTERVAL 1 MONTH)";
            }
            
            $stmt = $this->db->prepare("
                SELECT 
                    u.id, u.name,
                    COUNT(DISTINCT a.id) as total_appointments,
                    COUNT(DISTINCT CASE WHEN a.status = 'completed' THEN a.id END) as completed_appointments,
                    COUNT(DISTINCT CASE WHEN a.status = 'cancelled' THEN a.id END) as cancelled_appointments,
                    COUNT(DISTINCT CASE WHEN a.status = 'no_show' THEN a.id END) as no_show_appointments,
                    AVG(CASE WHEN a.status = 'completed' THEN a.actual_cost END) as avg_treatment_cost,
                    SUM(CASE WHEN a.status = 'completed' THEN a.actual_cost ELSE 0 END) as total_revenue,
                    COUNT(DISTINCT p.id) as prescriptions_given
                FROM users u
                LEFT JOIN appointments a ON u.id = a.staff_id AND a.branch_id = ? $date_filter
                LEFT JOIN prescriptions p ON a.id = p.appointment_id AND p.dentist_id = u.id
                WHERE u.role = 'staff' AND u.branch_id = ?
                GROUP BY u.id, u.name
                ORDER BY completed_appointments DESC
            ");
            
            $branch_id = getSessionBranchId();
            $stmt->bind_param('ii', $branch_id, $branch_id);
            $stmt->execute();
            $performance = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            return [
                'success' => true,
                'performance' => $performance,
                'period' => $period
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error fetching staff performance: ' . $e->getMessage()];
        }
    }
    
    /**
     * Create new staff member
     */
    public function createStaffMember() {
        try {
            if (!isLoggedIn() || !in_array(getSessionRole(), [ROLE_DENTIST, ROLE_ADMIN])) {
                return ['success' => false, 'message' => 'Unauthorized access'];
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            $name = trim($data['name'] ?? '');
            $email = trim($data['email'] ?? '');
            $phone = trim($data['phone'] ?? '');
            $role = trim($data['role'] ?? 'staff');
            $specialization = trim($data['specialization'] ?? '');
            $password = $data['password'] ?? '';
            
            // Validation
            if (empty($name) || empty($email) || empty($password)) {
                return ['success' => false, 'message' => 'Name, email, and password are required'];
            }
            
            if (strlen($password) < 6) {
                return ['success' => false, 'message' => 'Password must be at least 6 characters'];
            }
            
            // Check if email already exists
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            if ($stmt->get_result()->fetch_assoc()) {
                return ['success' => false, 'message' => 'Email already exists'];
            }
            
            // Create user account
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $branch_id = getSessionBranchId();
            
            $stmt = $this->db->prepare("
                INSERT INTO users (name, email, phone, role, password, branch_id, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())
            ");
            $stmt->bind_param('sssssi', $name, $email, $phone, $role, $password_hash, $branch_id);
            $stmt->execute();
            
            $staff_id = $this->db->insert_id;
            
            // Add specialization if provided
            if (!empty($specialization)) {
                // Get treatment type ID for specialization
                $stmt = $this->db->prepare("SELECT id FROM treatment_types WHERE name = ? LIMIT 1");
                $stmt->bind_param('s', $specialization);
                $stmt->execute();
                $treatment_type = $stmt->get_result()->fetch_assoc();
                
                if ($treatment_type) {
                    $stmt = $this->db->prepare("INSERT INTO staff_specializations (staff_id, treatment_type_id) VALUES (?, ?)");
                    $stmt->bind_param('ii', $staff_id, $treatment_type['id']);
                    $stmt->execute();
                }
            }
            
            return ['success' => true, 'message' => 'Staff member created successfully', 'staff_id' => $staff_id];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error creating staff member: ' . $e->getMessage()];
        }
    }
    
    /**
     * Delete staff member
     */
    public function deleteStaffMember() {
        try {
            if (!isLoggedIn() || !in_array(getSessionRole(), [ROLE_DENTIST, ROLE_ADMIN])) {
                return ['success' => false, 'message' => 'Unauthorized access'];
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            $staff_id = intval($data['staff_id'] ?? 0);
            
            if (!$staff_id) {
                return ['success' => false, 'message' => 'Staff ID is required'];
            }
            
            $branch_id = getSessionBranchId();
            
            // Verify staff belongs to this branch
            $stmt = $this->db->prepare("SELECT id, name FROM users WHERE id = ? AND branch_id = ? AND role IN ('staff', 'dentist')");
            $branch_id = getSessionBranchId();
            $stmt->bind_param('ii', $staff_id, $branch_id);
            $stmt->execute();
            $staff = $stmt->get_result()->fetch_assoc();
            
            if (!$staff) {
                return ['success' => false, 'message' => 'Staff member not found or unauthorized'];
            }
            
            // Check for dependencies
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM appointments WHERE staff_id = ?");
            $stmt->bind_param('i', $staff_id);
            $stmt->execute();
            $appointment_count = $stmt->get_result()->fetch_assoc()['count'];
            
            if ($appointment_count > 0) {
                // Soft delete - deactivate instead of hard delete
                $stmt = $this->db->prepare("UPDATE users SET status = 'inactive' WHERE id = ?");
                $stmt->bind_param('i', $staff_id);
                $stmt->execute();
                
                return ['success' => true, 'message' => 'Staff member deactivated (has appointment history)'];
            } else {
                // Hard delete - no appointment history
                
                // Delete related records first
                $stmt = $this->db->prepare("DELETE FROM staff_specializations WHERE staff_id = ?");
                $stmt->bind_param('i', $staff_id);
                $stmt->execute();
                
                $stmt = $this->db->prepare("DELETE FROM staff_schedules WHERE staff_id = ?");
                $stmt->bind_param('i', $staff_id);
                $stmt->execute();
                
                // Delete user account
                $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
                $stmt->bind_param('i', $staff_id);
                $stmt->execute();
                
                return ['success' => true, 'message' => 'Staff member deleted successfully'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error deleting staff member: ' . $e->getMessage()];
        }
    }
    
    /**
     * Toggle staff member active/inactive status
     */
    public function toggleStatus() {
        try {
            if (!isLoggedIn() || !in_array(getSessionRole(), [ROLE_DENTIST, ROLE_ADMIN])) {
                return ['success' => false, 'message' => 'Unauthorized access'];
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            $staff_id = intval($data['staff_id'] ?? 0);
            $is_active = intval($data['is_active'] ?? 0);
            
            if (!$staff_id) {
                return ['success' => false, 'message' => 'Staff ID is required'];
            }
            
            $branch_id = getSessionBranchId();
            
            // Verify staff belongs to this branch
            $stmt = $this->db->prepare("SELECT id, name FROM users WHERE id = ? AND branch_id = ? AND role IN ('staff', 'dentist')");
            $stmt->bind_param('ii', $staff_id, $branch_id);
            $stmt->execute();
            $staff = $stmt->get_result()->fetch_assoc();
            
            if (!$staff) {
                return ['success' => false, 'message' => 'Staff member not found or unauthorized'];
            }
            
            // Update status
            $new_status = $is_active ? 'active' : 'inactive';
            $stmt = $this->db->prepare("UPDATE users SET status = ? WHERE id = ?");
            $stmt->bind_param('si', $new_status, $staff_id);
            $stmt->execute();
            
            return ['success' => true, 'message' => 'Staff status updated successfully'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error updating staff status: ' . $e->getMessage()];
        }
    }
}

// API handler - only run if accessed directly, not when included by other files
if (php_sapi_name() !== 'cli' && !defined('STAFF_MANAGEMENT_INCLUDED')) {
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    $ctrl = new StaffManagementController();
    $resp = array('success'=>false,'message'=>'Invalid action');
    
    if ($action === 'getBranchStaff') $resp = $ctrl->getBranchStaff();
    elseif ($action === 'updateStaffMember') $resp = $ctrl->updateStaffMember();
    elseif ($action === 'createStaffMember') $resp = $ctrl->createStaffMember();
    elseif ($action === 'deleteStaffMember') $resp = $ctrl->deleteStaffMember();
    elseif ($action === 'getTreatmentTypes') $resp = $ctrl->getTreatmentTypes();
    elseif ($action === 'getStaffPerformance') $resp = $ctrl->getStaffPerformance();
    elseif ($action === 'toggleStatus') $resp = $ctrl->toggleStatus();
    
    header('Content-Type: application/json');
    echo json_encode($resp);
    exit;
}
?>