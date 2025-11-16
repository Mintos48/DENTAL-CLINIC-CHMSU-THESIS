<?php
/**
 * Appointment Controller - Database Enabled
 */

// Define as API endpoint to prevent session timeout redirects
if (!defined('API_ENDPOINT')) {
    define('API_ENDPOINT', true);
}

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/config/session.php';
require_once __DIR__ . '/InvoiceController.php';

class AppointmentController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getConnection();
    }
    
    /**
     * Get appointments for staff dashboard (includes both regular and walk-in appointments)
     */
    public function getAppointments() {
        try {
            $user_role = getSessionRole();
            $user_branch_id = getSessionBranchId();
            $user_id = getSessionUserId();
            
            // Get filter parameters
            $date_filter = isset($_GET['date']) ? $_GET['date'] : '';
            $status_filter = isset($_GET['status']) ? $_GET['status'] : '';
            $patient_search = isset($_GET['search']) ? trim($_GET['search']) : '';
            
            // Build role-based WHERE conditions for both queries
            $regular_where = "1=1";
            $walkin_where = "1=1";
            $params = array();
            $types = "";
            
            // Role-based filtering
            if ($user_role === 'staff') {
                $regular_where .= " AND a.branch_id = ?";
                $walkin_where .= " AND w.branch_id = ?";
                $params[] = $user_branch_id;
                $params[] = $user_branch_id;
                $types .= "ii";
            } elseif ($user_role === 'patient') {
                $regular_where .= " AND a.patient_id = ?";
                // Walk-in appointments don't have patient_id, so patients can't see them
                $walkin_where .= " AND 1=0"; // This will exclude all walk-in appointments for patients
                $params[] = $user_id;
                $types .= "i";
            }
            // Admin can see all appointments (no additional filters)
            
            // Apply date filter
            if (!empty($date_filter)) {
                $regular_where .= " AND a.appointment_date = ?";
                $walkin_where .= " AND w.appointment_date = ?";
                $params[] = $date_filter;
                $params[] = $date_filter;
                $types .= "ss";
            }
            
            // Apply status filter
            if (!empty($status_filter)) {
                $regular_where .= " AND a.status = ?";
                $walkin_where .= " AND w.status = ?";
                $params[] = $status_filter;
                $params[] = $status_filter;
                $types .= "ss";
            }
            
            // Apply patient search filter
            if (!empty($patient_search)) {
                $regular_where .= " AND u.name LIKE ?";
                $walkin_where .= " AND w.patient_name LIKE ?";
                $search_param = "%{$patient_search}%";
                $params[] = $search_param;
                $params[] = $search_param;
                $types .= "ss";
            }
            
            // Query for regular appointments
            $sql_regular = "SELECT a.id, a.appointment_date, a.appointment_time, a.status, a.notes, 
                           a.treatment_type_id, a.duration_minutes,
                           'registered' as patient_type,
                           u.name as patient_name,
                           u.phone,
                           u.email as patient_email,
                           NULL as patient_address,
                           NULL as patient_birthdate,
                           b.name as branch_name, s.name as staff_name,
                           tt.name as treatment_name,
                           pr.status as referral_status, pr.to_branch_id as referred_to_branch_id,
                           tb.name as referred_to_branch_name,
                           a.created_at, a.updated_at,
                           'regular' as appointment_source
                    FROM appointments a
                    LEFT JOIN users u ON a.patient_id = u.id
                    JOIN branches b ON a.branch_id = b.id
                    LEFT JOIN users s ON a.staff_id = s.id
                    LEFT JOIN treatment_types tt ON a.treatment_type_id = tt.id
                    LEFT JOIN patient_referrals pr ON a.id = pr.original_appointment_id
                    LEFT JOIN branches tb ON pr.to_branch_id = tb.id
                    WHERE {$regular_where}";
            
            // Query for walk-in appointments
            $sql_walkin = "SELECT w.id, w.appointment_date, w.appointment_time, w.status, w.notes, 
                          w.treatment_type_id, w.duration_minutes,
                          'walk_in' as patient_type,
                          w.patient_name,
                          w.patient_phone as phone,
                          w.patient_email,
                          w.patient_address,
                          w.patient_birthdate,
                          b.name as branch_name, s.name as staff_name,
                          tt.name as treatment_name,
                          NULL as referral_status, NULL as referred_to_branch_id,
                          NULL as referred_to_branch_name,
                          w.created_at, w.updated_at,
                          'walk_in' as appointment_source
                   FROM walk_in_appointments w
                   JOIN branches b ON w.branch_id = b.id
                   LEFT JOIN users s ON w.staff_id = s.id
                   LEFT JOIN treatment_types tt ON w.treatment_type_id = tt.id
                   WHERE {$walkin_where}";
            
            // Combine both queries with UNION
            $combined_sql = "({$sql_regular}) UNION ALL ({$sql_walkin}) ORDER BY created_at DESC";
            
            $stmt = $this->db->prepare($combined_sql);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            $appointments = array();
            while ($row = $result->fetch_assoc()) {
                // Add flags to identify appointment type
                $row['is_walk_in'] = ($row['appointment_source'] === 'walk_in');
                $row['walk_in_type'] = $row['is_walk_in'] ? 'dedicated_table' : null;
                $appointments[] = $row;
            }
            
            return array('success' => true, 'appointments' => $appointments);
            
        } catch (Exception $e) {
            return array('success' => false, 'message' => 'Failed to load appointments: ' . $e->getMessage());
        }
    }
    
    /**
     * Get walk-in appointments for staff dashboard (separate from regular appointments)
     */
    public function getWalkInAppointments() {
        try {
            $user_role = getSessionRole();
            $user_branch_id = getSessionBranchId();
            $user_id = getSessionUserId();
            
            // Only staff and admin can view walk-in appointments
            if ($user_role !== 'staff' && $user_role !== 'admin') {
                return array('success' => false, 'message' => 'Unauthorized access. Only staff can view walk-in appointments.');
            }
            
            // Get filter parameters
            $date_filter = isset($_GET['date']) ? $_GET['date'] : '';
            $status_filter = isset($_GET['status']) ? $_GET['status'] : '';
            $patient_search = isset($_GET['search']) ? trim($_GET['search']) : '';
            
            // Base query for walk-in appointments
            $sql = "SELECT w.id, w.appointment_date, w.appointment_time, w.status, w.notes, 
                           w.treatment_type_id, w.duration_minutes,
                           'walk_in' as patient_type,
                           w.patient_name,
                           w.patient_phone as phone,
                           w.patient_email as patient_email,
                           w.patient_address,
                           w.patient_birthdate,
                           b.name as branch_name, s.name as staff_name,
                           tt.name as treatment_name,
                           NULL as referral_status, NULL as referred_to_branch_id,
                           NULL as referred_to_branch_name,
                           w.created_at, w.updated_at,
                           'walk_in_dedicated' as appointment_source
                    FROM walk_in_appointments w
                    JOIN branches b ON w.branch_id = b.id
                    LEFT JOIN users s ON w.staff_id = s.id
                    LEFT JOIN treatment_types tt ON w.treatment_type_id = tt.id
                    WHERE 1=1";
            
            $params = array();
            $types = "";
            
            // Role-based filtering
            if ($user_role === 'staff') {
                $sql .= " AND w.branch_id = ?";
                $params[] = $user_branch_id;
                $types .= "i";
            }
            // Admin can see all walk-in appointments
            
            // Apply filters
            if (!empty($date_filter)) {
                $sql .= " AND w.appointment_date = ?";
                $params[] = $date_filter;
                $types .= "s";
            }
            
            if (!empty($status_filter)) {
                $sql .= " AND w.status = ?";
                $params[] = $status_filter;
                $types .= "s";
            }
            
            if (!empty($patient_search)) {
                $sql .= " AND w.patient_name LIKE ?";
                $params[] = "%{$patient_search}%";
                $types .= "s";
            }
            
            $sql .= " ORDER BY w.created_at DESC";
            
            $stmt = $this->db->prepare($sql);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            $appointments = array();
            while ($row = $result->fetch_assoc()) {
                // Add walk-in specific flags
                $row['is_walk_in'] = true;
                $row['walk_in_type'] = 'dedicated_table';
                $row['patient_id'] = null; // Walk-in patients don't have user accounts
                $appointments[] = $row;
            }
            
            return array('success' => true, 'appointments' => $appointments, 'total_count' => count($appointments));
            
        } catch (Exception $e) {
            return array('success' => false, 'message' => 'Failed to load walk-in appointments: ' . $e->getMessage());
        }
    }
    
    /**
     * Get pending appointments for staff dashboard
     */
    public function getPendingAppointments() {
        try {
            $user_role = getSessionRole();
            $user_branch_id = getSessionBranchId();
            
            // Query for regular appointments
            $sql_regular = "SELECT a.id, a.appointment_date, a.appointment_time, a.status, a.notes, 
                           a.treatment_type_id, a.duration_minutes,
                           'registered' as patient_type,
                           u.name as patient_name,
                           u.email as patient_email,
                           u.phone as patient_phone,
                           b.name as branch_name, s.name as staff_name,
                           tt.name as treatment_name,
                           a.created_at,
                           'regular' as appointment_source
                    FROM appointments a
                    LEFT JOIN users u ON a.patient_id = u.id
                    JOIN branches b ON a.branch_id = b.id
                    LEFT JOIN users s ON a.staff_id = s.id
                    LEFT JOIN treatment_types tt ON a.treatment_type_id = tt.id
                    WHERE a.status = 'pending'";
            
            // Query for walk-in appointments from new table
            $sql_walkin = "SELECT w.id, w.appointment_date, w.appointment_time, w.status, w.notes, 
                          w.treatment_type_id, w.duration_minutes, 
                          'walk_in' as patient_type,
                          w.patient_name,
                          w.patient_email,
                          w.patient_phone,
                          b.name as branch_name, s.name as staff_name,
                          tt.name as treatment_name,
                          w.created_at,
                          'walk_in_dedicated' as appointment_source
                   FROM walk_in_appointments w
                   JOIN branches b ON w.branch_id = b.id
                   LEFT JOIN users s ON w.staff_id = s.id
                   LEFT JOIN treatment_types tt ON w.treatment_type_id = tt.id
                   WHERE w.status = 'pending'";
            
            $params = array();
            $types = "";
            
            if ($user_role === 'staff') {
                $sql_regular .= " AND a.branch_id = ?";
                $sql_walkin .= " AND w.branch_id = ?";
                $params[] = $user_branch_id;
                $types .= "i";
            }
            
            // Combine queries with UNION
            $combined_sql = "(" . $sql_regular . ") UNION ALL (" . $sql_walkin . ") ORDER BY created_at DESC";
            
            $stmt = $this->db->prepare($combined_sql);
            if (!empty($params)) {
                // For UNION, we need to bind parameters for both queries
                if ($user_role === 'staff') {
                    $stmt->bind_param("ii", $user_branch_id, $user_branch_id);
                }
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            $appointments = array();
            while ($row = $result->fetch_assoc()) {
                // Add a flag to identify walk-in appointments
                if ($row['appointment_source'] === 'walk_in_dedicated') {
                    $row['is_walk_in'] = true;
                    $row['walk_in_type'] = 'dedicated_table';
                } else {
                    $row['is_walk_in'] = false;
                    $row['walk_in_type'] = null;
                }
                $appointments[] = $row;
            }
            
            return array('success' => true, 'appointments' => $appointments);
            
        } catch (Exception $e) {
            return array('success' => false, 'message' => 'Failed to load pending appointments');
        }
    }
    
    /**
     * Get pending walk-in appointments for staff dashboard (separate method)
     */
    public function getPendingWalkInAppointments() {
        try {
            $user_role = getSessionRole();
            $user_branch_id = getSessionBranchId();
            
            // Only staff and admin can view walk-in appointments
            if ($user_role !== 'staff' && $user_role !== 'admin') {
                return array('success' => false, 'message' => 'Unauthorized access. Only staff can view walk-in appointments.');
            }
            
            // Query for walk-in appointments only
            $sql = "SELECT w.id, w.appointment_date, w.appointment_time, w.status, w.notes, 
                          w.treatment_type_id, w.duration_minutes, 
                          'walk_in' as patient_type,
                          w.patient_name,
                          w.patient_email,
                          w.patient_phone,
                          w.patient_address,
                          w.patient_birthdate,
                          b.name as branch_name, s.name as staff_name,
                          tt.name as treatment_name,
                          w.created_at,
                          'walk_in_dedicated' as appointment_source
                   FROM walk_in_appointments w
                   JOIN branches b ON w.branch_id = b.id
                   LEFT JOIN users s ON w.staff_id = s.id
                   LEFT JOIN treatment_types tt ON w.treatment_type_id = tt.id
                   WHERE w.status = 'pending'";
            
            $params = array();
            $types = "";
            
            if ($user_role === 'staff') {
                $sql .= " AND w.branch_id = ?";
                $params[] = $user_branch_id;
                $types .= "i";
            }
            
            $sql .= " ORDER BY w.created_at DESC";
            
            $stmt = $this->db->prepare($sql);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            $appointments = array();
            while ($row = $result->fetch_assoc()) {
                // Add walk-in specific flags
                $row['is_walk_in'] = true;
                $row['walk_in_type'] = 'dedicated_table';
                $row['patient_id'] = null; // Walk-in patients don't have user accounts
                $appointments[] = $row;
            }
            
            return array('success' => true, 'appointments' => $appointments, 'total_count' => count($appointments));
            
        } catch (Exception $e) {
            return array('success' => false, 'message' => 'Failed to load pending walk-in appointments: ' . $e->getMessage());
        }
    }
    
    /**
     * Get all appointments (both regular and walk-in) - unified method with type parameter
     */
    public function getAllAppointments() {
        try {
            $user_role = getSessionRole();
            $user_branch_id = getSessionBranchId();
            $user_id = getSessionUserId();
            
            // Get type parameter to determine what to fetch
            $appointment_type = isset($_GET['type']) ? $_GET['type'] : 'all'; // 'regular', 'walk_in', or 'all'
            
            $all_appointments = array();
            
            // Fetch regular appointments
            if ($appointment_type === 'regular' || $appointment_type === 'all') {
                $regular_result = $this->getAppointments();
                if ($regular_result['success']) {
                    foreach ($regular_result['appointments'] as $appointment) {
                        $appointment['appointment_category'] = 'regular';
                        $all_appointments[] = $appointment;
                    }
                }
            }
            
            // Fetch walk-in appointments
            if ($appointment_type === 'walk_in' || $appointment_type === 'all') {
                $walkin_result = $this->getWalkInAppointments();
                if ($walkin_result['success']) {
                    foreach ($walkin_result['appointments'] as $appointment) {
                        $appointment['appointment_category'] = 'walk_in';
                        $all_appointments[] = $appointment;
                    }
                }
            }
            
            // Sort by creation date (most recent first)
            usort($all_appointments, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });
            
            return array(
                'success' => true, 
                'appointments' => $all_appointments,
                'total_count' => count($all_appointments),
                'type_requested' => $appointment_type
            );
            
        } catch (Exception $e) {
            return array('success' => false, 'message' => 'Failed to load appointments: ' . $e->getMessage());
        }
    }
    
    /**
     * Get appointment statistics including walk-in appointments
     */
    public function getStats() {
        try {
            $user_role = getSessionRole();
            $user_branch_id = getSessionBranchId();
            
            $stats = array();
            
            // Initialize all stats to 0 as default
            $stats['pending'] = 0;
            $stats['approved'] = 0;
            $stats['completed'] = 0;
            $stats['cancelled'] = 0;
            $stats['referred'] = 0;
            $stats['total'] = 0;
            $stats['pending_today'] = 0;
            $stats['approved_today'] = 0;
            $stats['completed_today'] = 0;
            $stats['total_today'] = 0;
            
            // Get today's date
            $today = date('Y-m-d');
            
            // Build base WHERE clause for role filtering
            $roleFilter = "";
            $roleParams = array();
            
            if ($user_role === 'staff' && $user_branch_id) {
                $roleFilter = "WHERE branch_id = ?";
                $roleParams = array($user_branch_id);
            } elseif ($user_role === 'patient') {
                $user_id = getSessionUserId();
                if ($user_id) {
                    $roleFilter = "WHERE patient_id = ?";
                    $roleParams = array($user_id);
                }
            }
            // Admin sees all appointments (no filter)
            
            // Get all status counts from both tables using UNION (now that collation is fixed)
            $statusSql = "SELECT status, COUNT(*) as count FROM (
                (SELECT status FROM appointments $roleFilter)
                UNION ALL
                (SELECT status FROM walk_in_appointments " . 
                ($user_role === 'staff' && $user_branch_id ? "WHERE branch_id = ?" : "") . ")
            ) as all_appointments 
            GROUP BY status";
            
            $stmt = $this->db->prepare($statusSql);
            
            // Bind parameters for both queries if needed
            if ($user_role === 'staff' && $user_branch_id) {
                $stmt->bind_param("ii", $user_branch_id, $user_branch_id);
            } elseif ($user_role === 'patient' && !empty($roleParams)) {
                $stmt->bind_param("i", $roleParams[0]);
            }
            
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $status = $row['status'];
                    $count = intval($row['count']);
                    $stats[$status] = $count;
                    $stats['total'] += $count;
                }
            } else {
                error_log("getStats - Status query failed: " . $this->db->error);
            }
            
            // Get today's status counts from both tables using UNION
            $todayFilter = $roleFilter ? $roleFilter . " AND appointment_date = ?" : "WHERE appointment_date = ?";
            $walkinTodayFilter = ($user_role === 'staff' && $user_branch_id) ? "WHERE branch_id = ? AND appointment_date = ?" : "WHERE appointment_date = ?";
            
            $todaySql = "SELECT status, COUNT(*) as count FROM (
                (SELECT status FROM appointments $todayFilter)
                UNION ALL
                (SELECT status FROM walk_in_appointments $walkinTodayFilter)
            ) as all_appointments 
            GROUP BY status";
            
            $stmt = $this->db->prepare($todaySql);
            
            if ($user_role === 'staff' && $user_branch_id) {
                $stmt->bind_param("isis", $user_branch_id, $today, $user_branch_id, $today);
            } elseif ($user_role === 'patient' && !empty($roleParams)) {
                $stmt->bind_param("iss", $roleParams[0], $today, $today);
            } else {
                $stmt->bind_param("ss", $today, $today);
            }
            
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $status = $row['status'];
                    $count = intval($row['count']);
                    $stats[$status . '_today'] = $count;
                    if (!isset($stats['total_today'])) {
                        $stats['total_today'] = 0;
                    }
                    $stats['total_today'] += $count;
                }
            } else {
                error_log("getStats - Today query failed: " . $this->db->error);
            }
            
            // Add additional useful stats
            $stats['user_role'] = $user_role;
            $stats['user_branch_id'] = $user_branch_id;
            $stats['today_date'] = $today;
            
            return array('success' => true, 'stats' => $stats);
            
        } catch (Exception $e) {
            return array(
                'success' => false, 
                'message' => 'Failed to load statistics: ' . $e->getMessage(),
                'stats' => array(
                    'pending' => 0,
                    'approved' => 0,
                    'completed' => 0,
                    'cancelled' => 0,
                    'referred' => 0,
                    'total' => 0,
                    'pending_today' => 0,
                    'approved_today' => 0,
                    'completed_today' => 0,
                    'total_today' => 0
                )
            );
        }
    }
    
    /**
     * Update appointment status (approve/reject/complete)
     */
    public function updateStatus() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return array('success' => false, 'message' => 'Invalid request method');
        }
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $appointment_id = isset($input['appointment_id']) ? intval($input['appointment_id']) : 0;
            $status = isset($input['status']) ? $input['status'] : '';
            $update_referral_status = isset($input['update_referral_status']) ? $input['update_referral_status'] : false;
            $completion_source = isset($input['completion_source']) ? $input['completion_source'] : 'unknown';
            $completion_notes = isset($input['completion_notes']) ? $input['completion_notes'] : '';
            
            // Get appointment type if specified
            $appointment_type = isset($input['type']) ? $input['type'] : '';
            
            // Also check POST data as fallback
            if (empty($appointment_id)) {
                $appointment_id = isset($_POST['appointment_id']) ? intval($_POST['appointment_id']) : 0;
                $status = isset($_POST['status']) ? $_POST['status'] : '';
                $appointment_type = isset($_POST['type']) ? $_POST['type'] : '';
                $update_referral_status = isset($_POST['update_referral_status']) ? $_POST['update_referral_status'] : false;
                $completion_source = isset($_POST['completion_source']) ? $_POST['completion_source'] : 'unknown';
                $completion_notes = isset($_POST['completion_notes']) ? $_POST['completion_notes'] : '';
            }
            
            if (empty($appointment_id) || empty($status)) {
                return array('success' => false, 'message' => 'Appointment ID and status are required');
            }
            
            // Validate status
            $valid_statuses = array('pending', 'approved', 'completed', 'cancelled', 'referred');
            if (!in_array($status, $valid_statuses)) {
                return array('success' => false, 'message' => 'Invalid status');
            }
            
            // If completing an appointment, check if prescription exists
            if ($status === 'completed') {
                $check_prescription_sql = "SELECT id FROM prescriptions WHERE appointment_id = ?";
                $check_stmt = $this->db->prepare($check_prescription_sql);
                $check_stmt->bind_param("i", $appointment_id);
                $check_stmt->execute();
                $prescription_result = $check_stmt->get_result();
                
                if ($prescription_result->num_rows === 0) {
                    return array('success' => false, 'message' => 'Cannot complete appointment without a prescription. Please create a prescription first.');
                }
            }
            
            // Check if user has permission
            $user_role = getSessionRole();
            $user_branch_id = getSessionBranchId();
            $user_id = getSessionUserId();
            
            if ($user_role !== 'admin' && $user_role !== 'staff' && $user_role !== 'dentist') {
                return array('success' => false, 'message' => 'Unauthorized access');
            }
            
            // Determine if this is a regular appointment or walk-in appointment
            $is_walk_in = false;
            $appointment_table = 'appointments';
            $appointment_data = null;
            
            // Check the correct table first based on type parameter
            if ($appointment_type === 'walk-in') {
                // Check walk-in appointments first when type is specified
                $check_walkin_sql = "SELECT branch_id, patient_name, 'walk_in' as appointment_type FROM walk_in_appointments WHERE id = ?";
                $check_walkin_stmt = $this->db->prepare($check_walkin_sql);
                $check_walkin_stmt->bind_param("i", $appointment_id);
                $check_walkin_stmt->execute();
                $check_walkin_result = $check_walkin_stmt->get_result();
                
                if ($check_walkin_result->num_rows > 0) {
                    $appointment_data = $check_walkin_result->fetch_assoc();
                    $appointment_table = 'walk_in_appointments';
                    $is_walk_in = true;
                } else {
                    return array('success' => false, 'message' => 'Walk-in appointment not found');
                }
            } else if ($appointment_type === 'regular') {
                // Check regular appointments first when type is specified
                $check_regular_sql = "SELECT branch_id, patient_id, 'regular' as appointment_type FROM appointments WHERE id = ?";
                $check_regular_stmt = $this->db->prepare($check_regular_sql);
                $check_regular_stmt->bind_param("i", $appointment_id);
                $check_regular_stmt->execute();
                $check_regular_result = $check_regular_stmt->get_result();
                
                if ($check_regular_result->num_rows > 0) {
                    $appointment_data = $check_regular_result->fetch_assoc();
                    $appointment_table = 'appointments';
                    $is_walk_in = false;
                } else {
                    return array('success' => false, 'message' => 'Regular appointment not found');
                }
            } else {
                // No type specified or unknown type - check both tables (original logic)
                // First check if it's a regular appointment
                $check_regular_sql = "SELECT branch_id, patient_id, 'regular' as appointment_type FROM appointments WHERE id = ?";
                $check_regular_stmt = $this->db->prepare($check_regular_sql);
                $check_regular_stmt->bind_param("i", $appointment_id);
                $check_regular_stmt->execute();
                $check_regular_result = $check_regular_stmt->get_result();
                
                if ($check_regular_result->num_rows > 0) {
                    $appointment_data = $check_regular_result->fetch_assoc();
                    $appointment_table = 'appointments';
                    $is_walk_in = false;
                } else {
                    // Check if it's a walk-in appointment
                    $check_walkin_sql = "SELECT branch_id, patient_name, 'walk_in' as appointment_type FROM walk_in_appointments WHERE id = ?";
                    $check_walkin_stmt = $this->db->prepare($check_walkin_sql);
                    $check_walkin_stmt->bind_param("i", $appointment_id);
                    $check_walkin_stmt->execute();
                    $check_walkin_result = $check_walkin_stmt->get_result();
                    
                    if ($check_walkin_result->num_rows > 0) {
                        $appointment_data = $check_walkin_result->fetch_assoc();
                        $appointment_table = 'walk_in_appointments';
                        $is_walk_in = true;
                    } else {
                        return array('success' => false, 'message' => 'Appointment not found in either regular or walk-in appointments');
                    }
                }
            }
            
            // For staff, check if appointment belongs to their branch
            if ($user_role === 'staff') {
                if ($appointment_data['branch_id'] != $user_branch_id) {
                    return array('success' => false, 'message' => 'You can only update appointments in your branch');
                }
            }
            
            // Start transaction for referral updates
            $this->db->begin_transaction();
            
            try {
                // Special handling for approving referral appointments (only for regular appointments)
                if ($status === 'approved' && !$is_walk_in) {
                    $this->handleReferralAppointmentApproval($appointment_id, $user_id);
                }
                
                // Special handling for completing referral appointments (only for regular appointments)
                if ($status === 'completed' && !$is_walk_in) {
                    $this->ensureReferralTreatmentDetails($appointment_id, $user_id);
                }
                
                // Update appointment status based on appointment type
                if ($is_walk_in) {
                    // Update walk-in appointment status
                    $sql = "UPDATE walk_in_appointments SET status = ?, staff_id = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
                    $stmt = $this->db->prepare($sql);
                    $stmt->bind_param("sii", $status, $user_id, $appointment_id);
                } else {
                    // Update regular appointment status
                    $sql = "UPDATE appointments SET status = ?, staff_id = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
                    $stmt = $this->db->prepare($sql);
                    $stmt->bind_param("sii", $status, $user_id, $appointment_id);
                }
                
                if (!$stmt->execute()) {
                    throw new Exception('Failed to update appointment status');
                }
                
                $referral_updated = false;
                $referral_details = null;
                
                // Enhanced referral handling: Always check for referrals when completing appointments
                if ($status === 'completed' && !$is_walk_in) {
                    // Check if this appointment is associated with a referral (check both directions)
                    $referral_check_sql = "SELECT pr.id as referral_id, pr.status as current_status, pr.patient_id, pr.to_branch_id, pr.from_branch_id,
                                          pr.new_appointment_id, pr.original_appointment_id,
                                          fb.name as from_branch_name, tb.name as to_branch_name, p.name as patient_name
                                          FROM patient_referrals pr 
                                          LEFT JOIN branches fb ON pr.from_branch_id = fb.id
                                          LEFT JOIN branches tb ON pr.to_branch_id = tb.id
                                          LEFT JOIN users p ON pr.patient_id = p.id
                                          WHERE pr.new_appointment_id = ? OR pr.original_appointment_id = ?";
                    $referral_stmt = $this->db->prepare($referral_check_sql);
                    $referral_stmt->bind_param("ii", $appointment_id, $appointment_id);
                    $referral_stmt->execute();
                    $referral_result = $referral_stmt->get_result();
                    
                    if ($referral_result->num_rows > 0) {
                        $referral = $referral_result->fetch_assoc();
                        $referral_details = $referral;
                        
                        // If this is the new_appointment_id being completed, update referral to completed
                        if ($referral['new_appointment_id'] == $appointment_id && $referral['current_status'] === 'accepted') {
                            // Use provided completion notes or default based on appointment type
                            $final_completion_notes = !empty($completion_notes) ? $completion_notes : 'Treatment completed successfully at referred branch';
                            
                            // Update referral status to completed with completion details
                            $update_referral_sql = "UPDATE patient_referrals 
                                                  SET status = 'completed', 
                                                      completed_at = CURRENT_TIMESTAMP,
                                                      completing_staff_id = ?,
                                                      completion_notes = ?,
                                                      updated_at = CURRENT_TIMESTAMP 
                                                  WHERE id = ?";
                            $update_referral_stmt = $this->db->prepare($update_referral_sql);
                            $update_referral_stmt->bind_param("isi", $user_id, $final_completion_notes, $referral['referral_id']);
                            
                            if (!$update_referral_stmt->execute()) {
                                throw new Exception('Failed to update referral status in patient_referrals table');
                            }
                            
                            $referral_updated = true;
                            
                            // Update appointment history for both appointments
                            if (!empty($referral['original_appointment_id'])) {
                                $this->recordAppointmentHistory(
                                    $referral['original_appointment_id'],
                                    $referral['patient_id'],
                                    'referral_completed',
                                    "Referral treatment completed at {$referral['to_branch_name']}. Completion notes: {$final_completion_notes}"
                                );
                            }
                            
                            // Log referral completion with detailed information
                            $source_info = !empty($completion_source) ? $completion_source : 'staff_dashboard';
                            $this->logActivity($user_id, 'REFERRAL_COMPLETED', 
                                "Completed referral appointment #{$appointment_id} for referral #{$referral['referral_id']}. " .
                                "Patient: {$referral['patient_name']}, From: {$referral['from_branch_name']} to {$referral['to_branch_name']}. " .
                                "Source: {$source_info}. Completion notes: {$final_completion_notes}");
                                
                        } else if ($referral['original_appointment_id'] == $appointment_id) {
                            // This is the original appointment being completed (shouldn't happen for referred appointments)
                            error_log("Original appointment {$appointment_id} marked as completed, but this should have been referred. Referral ID: {$referral['referral_id']}");
                        } else {
                            // Log if referral was found but not in accepted status
                            error_log("Referral found for appointment {$appointment_id} but status is '{$referral['current_status']}', expected 'accepted'. Skipping referral update.");
                        }
                    } else {
                        // This is a normal appointment completion - no referral involved
                        error_log("No referral found for appointment {$appointment_id} - this is a normal appointment completion.");
                    }
                }
                
                // Commit transaction
                $this->db->commit();
                
                // Send email with prescription and invoice when appointment is completed
                if ($status === 'completed' && !$is_walk_in) {
                    $this->sendCompletionEmailWithPrescriptionAndInvoice($appointment_id);
                }
                
                // Build success message
                $appointment_type_text = $is_walk_in ? 'Walk-in appointment' : 'Appointment';
                $success_message = "{$appointment_type_text} {$status} successfully";
                
                if ($status === 'completed' && $update_referral_status && !$is_walk_in) {
                    if ($referral_updated) {
                        $success_message = "Referral appointment completed successfully! " .
                                         "Both appointment status and patient_referrals table have been updated. " .
                                         "Patient has been notified of completion.";
                    } else {
                        $success_message = "Appointment completed successfully. " .
                                         "Note: No active referral found to update in patient_referrals table.";
                    }
                }
                
            } catch (Exception $e) {
                // Rollback transaction on error
                $this->db->rollback();
                throw $e;
            }
            
            // Post-commit operations - these run after the transaction is committed
            // If these fail, they should not affect the success of the status update
            try {
                // Generate invoice for completed appointments (after successful commit)
                if ($status === 'completed') {
                    if ($is_walk_in) {
                        // Generate invoice for walk-in appointment
                        $this->generateInvoiceForCompletedWalkInAppointment($appointment_id, $user_id);
                    } else {
                        // Generate invoice for regular appointment
                        $this->generateInvoiceForCompletedAppointment($appointment_id, $user_id);
                    }
                }
                
                // Log the action with appointment type
                $appointment_type_label = $is_walk_in ? 'walk-in appointment' : 'appointment';
                $patient_info = $is_walk_in ? $appointment_data['patient_name'] : "Patient ID: {$appointment_data['patient_id']}";
                $this->logActivity($user_id, 'APPOINTMENT_UPDATE', "Updated {$appointment_type_label} #{$appointment_id} status to {$status}. {$patient_info}");
                
                // Create notification entry based on appointment type
                if ($is_walk_in) {
                    $this->createWalkInAppointmentNotification($appointment_id, $status, $user_id);
                } else {
                    $this->createAppointmentNotification($appointment_id, $status, $user_id);
                }
            } catch (Exception $e) {
                // Log post-commit errors but don't fail the main operation
                error_log("Post-commit operation failed for appointment {$appointment_id}: " . $e->getMessage());
            }
            
            return array(
                'success' => true, 
                'message' => $success_message,
                'referral_updated' => $referral_updated,
                'referral_details' => $referral_details
            );
            
        } catch (Exception $e) {
            return array('success' => false, 'message' => 'Failed to update appointment status: ' . $e->getMessage());
        }
    }
    
    /**
     * Update appointment details (date, time, treatment, notes)
     * Only allows editing of pending appointments
     */
    public function updateAppointment() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return array('success' => false, 'message' => 'Invalid request method');
        }
        
        try {
            // Get form data
            $appointment_id = isset($_POST['appointment_id']) ? intval($_POST['appointment_id']) : 0;
            $patient_name = isset($_POST['patient_name']) ? trim($_POST['patient_name']) : '';
            $appointment_date = isset($_POST['appointment_date']) ? $_POST['appointment_date'] : '';
            $appointment_time = isset($_POST['appointment_time']) ? $_POST['appointment_time'] : '';
            $treatment_type_id = isset($_POST['treatment_type_id']) ? intval($_POST['treatment_type_id']) : 0;
            $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
            
            // Validation
            if (empty($appointment_id)) {
                return array('success' => false, 'message' => 'Appointment ID is required');
            }
            
            if (empty($patient_name)) {
                return array('success' => false, 'message' => 'Patient name is required');
            }
            
            if (strlen($patient_name) < 2) {
                return array('success' => false, 'message' => 'Patient name must be at least 2 characters long');
            }
            
            if (empty($appointment_date)) {
                return array('success' => false, 'message' => 'Appointment date is required');
            }
            
            if (empty($appointment_time)) {
                return array('success' => false, 'message' => 'Appointment time is required');
            }
            
            if (empty($treatment_type_id)) {
                return array('success' => false, 'message' => 'Treatment type is required');
            }
            
            // Validate date format and ensure it's not in the past
            $selected_date = DateTime::createFromFormat('Y-m-d', $appointment_date);
            if (!$selected_date) {
                return array('success' => false, 'message' => 'Invalid date format');
            }
            
            $today = new DateTime();
            $today->setTime(0, 0, 0);
            
            if ($selected_date < $today) {
                return array('success' => false, 'message' => 'Appointment date cannot be in the past');
            }
            
            // Check user permissions
            $user_role = getSessionRole();
            $user_branch_id = getSessionBranchId();
            $user_id = getSessionUserId();
            
            if ($user_role !== 'admin' && $user_role !== 'staff') {
                return array('success' => false, 'message' => 'Unauthorized access');
            }
            
            // Check if appointment exists and is pending
            $check_sql = "SELECT a.id, a.status, a.branch_id, a.patient_id, 'regular' as appointment_type
                         FROM appointments a WHERE a.id = ?
                         UNION
                         SELECT w.id, w.status, w.branch_id, NULL as patient_id, 'walk_in' as appointment_type
                         FROM walk_in_appointments w WHERE w.id = ?";
            $check_stmt = $this->db->prepare($check_sql);
            $check_stmt->bind_param("ii", $appointment_id, $appointment_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows === 0) {
                return array('success' => false, 'message' => 'Appointment not found');
            }
            
            $appointment = $check_result->fetch_assoc();
            $is_walk_in = ($appointment['appointment_type'] === 'walk_in');
            
            // Only allow editing of pending appointments
            if ($appointment['status'] !== 'pending') {
                return array('success' => false, 'message' => 'Only pending appointments can be edited');
            }
            
            // For staff, check if appointment belongs to their branch
            if ($user_role === 'staff' && $appointment['branch_id'] != $user_branch_id) {
                return array('success' => false, 'message' => 'You can only edit appointments in your branch');
            }
            
            // Validate treatment type exists for this branch
            $treatment_sql = "SELECT tt.id, tt.name, tt.duration_minutes, bs.price 
                             FROM treatment_types tt 
                             JOIN branch_services bs ON tt.id = bs.treatment_type_id 
                             WHERE tt.id = ? AND bs.branch_id = ? AND bs.is_available = 1";
            $treatment_stmt = $this->db->prepare($treatment_sql);
            $treatment_stmt->bind_param("ii", $treatment_type_id, $appointment['branch_id']);
            $treatment_stmt->execute();
            $treatment_result = $treatment_stmt->get_result();
            
            if ($treatment_result->num_rows === 0) {
                return array('success' => false, 'message' => 'Invalid treatment type for this branch');
            }
            
            $treatment = $treatment_result->fetch_assoc();
            
            // Check for time slot conflicts (excluding current appointment) - check both appointment types
            $conflict_sql = "SELECT 'regular' as type, id FROM appointments 
                           WHERE appointment_date = ? 
                           AND appointment_time = ? 
                           AND branch_id = ? 
                           AND id != ? 
                           AND status IN ('pending', 'approved')
                           UNION
                           SELECT 'walk_in' as type, id FROM walk_in_appointments 
                           WHERE appointment_date = ? 
                           AND appointment_time = ? 
                           AND branch_id = ? 
                           AND id != ? 
                           AND status IN ('pending', 'approved')";
            
            $conflict_stmt = $this->db->prepare($conflict_sql);
            if ($is_walk_in) {
                $zero_regular = 0;
                $conflict_stmt->bind_param("ssiiisii", 
                    $appointment_date, $appointment_time, $appointment['branch_id'], $zero_regular,
                    $appointment_date, $appointment_time, $appointment['branch_id'], $appointment_id
                );
            } else {
                $zero_walkin = 0;
                $conflict_stmt->bind_param("ssiiisii", 
                    $appointment_date, $appointment_time, $appointment['branch_id'], $appointment_id,
                    $appointment_date, $appointment_time, $appointment['branch_id'], $zero_walkin
                );
            }
            
            $conflict_stmt->execute();
            $conflict_result = $conflict_stmt->get_result();
            
            if ($conflict_result->num_rows > 0) {
                return array('success' => false, 'message' => 'This time slot is already booked');
            }
            
            // Start transaction
            $this->db->begin_transaction();
            
            try {
                // Update the appointment based on type
                if ($is_walk_in) {
                    // Update walk-in appointment (can update patient name directly)
                    $update_sql = "UPDATE walk_in_appointments 
                                 SET patient_name = ?,
                                     appointment_date = ?, 
                                     appointment_time = ?, 
                                     treatment_type_id = ?, 
                                     notes = ?, 
                                     updated_at = CURRENT_TIMESTAMP 
                                 WHERE id = ?";
                    $update_stmt = $this->db->prepare($update_sql);
                    $update_stmt->bind_param("sssisi", $patient_name, $appointment_date, $appointment_time, $treatment_type_id, $notes, $appointment_id);
                } else {
                    // Update regular appointment only (do not modify users table)
                    $update_sql = "UPDATE appointments 
                                 SET appointment_date = ?, 
                                     appointment_time = ?, 
                                     treatment_type_id = ?, 
                                     notes = ?, 
                                     updated_at = CURRENT_TIMESTAMP 
                                 WHERE id = ?";
                    $update_stmt = $this->db->prepare($update_sql);
                    $update_stmt->bind_param("ssisi", $appointment_date, $appointment_time, $treatment_type_id, $notes, $appointment_id);
                }
                
                if (!$update_stmt->execute()) {
                    $error_msg = $is_walk_in ? 'Failed to update walk-in appointment' : 'Failed to update appointment';
                    throw new Exception($error_msg);
                }
                
                // Log the activity
                $appointment_type_label = $is_walk_in ? 'walk-in appointment' : 'appointment';
                if ($is_walk_in) {
                    $patient_info = "Patient: {$patient_name}";
                } else {
                    $patient_info = "Patient ID: {$appointment['patient_id']} (name update skipped for user account protection)";
                }
                $this->logActivity($user_id, 'APPOINTMENT_EDITED', 
                    "Edited {$appointment_type_label} #{$appointment_id}. New date: {$appointment_date}, time: {$appointment_time}, treatment: {$treatment['name']}. {$patient_info}");
                
                // Create notification
                if ($is_walk_in) {
                    $this->createWalkInAppointmentNotification($appointment_id, 'updated', $user_id);
                } else {
                    $this->createAppointmentNotification($appointment_id, 'updated', $user_id);
                }
                
                // Commit transaction
                $this->db->commit();
                
                $success_message = 'Appointment updated successfully';
                if (!$is_walk_in) {
                    $success_message .= '. Note: Patient name was not updated to protect user account integrity.';
                }
                
                return array(
                    'success' => true, 
                    'message' => $success_message,
                    'appointment' => array(
                        'id' => $appointment_id,
                        'patient_name' => $is_walk_in ? $patient_name : 'Not updated for regular appointments',
                        'date' => $appointment_date,
                        'time' => $appointment_time,
                        'treatment' => $treatment['name'],
                        'notes' => $notes
                    )
                );
                
            } catch (Exception $e) {
                $this->db->rollback();
                throw $e;
            }
            
        } catch (Exception $e) {
            return array('success' => false, 'message' => 'Failed to update appointment: ' . $e->getMessage());
        }
    }
    
    /**
     * Handle referral appointment approval - update treatment details from original appointment
     */
    private function handleReferralAppointmentApproval($appointment_id, $user_id) {
        try {
            // Check if this appointment is a referred appointment
            $referral_check_sql = "SELECT pr.id as referral_id, pr.original_appointment_id, pr.patient_id,
                                          orig.treatment_type_id, orig.duration_minutes, orig.notes as original_notes,
                                          tt.name as treatment_name
                                   FROM patient_referrals pr 
                                   LEFT JOIN appointments orig ON pr.original_appointment_id = orig.id
                                   LEFT JOIN treatment_types tt ON orig.treatment_type_id = tt.id
                                   WHERE pr.new_appointment_id = ?";
            $referral_stmt = $this->db->prepare($referral_check_sql);
            $referral_stmt->bind_param("i", $appointment_id);
            $referral_stmt->execute();
            $referral_result = $referral_stmt->get_result();
            
            if ($referral_result->num_rows > 0) {
                $referral = $referral_result->fetch_assoc();
                
                // Update the referred appointment with original appointment's treatment details
                if ($referral['treatment_type_id']) {
                    $update_treatment_sql = "UPDATE appointments 
                                           SET treatment_type_id = ?, 
                                               duration_minutes = ?,
                                               notes = CASE 
                                                   WHEN notes IS NULL OR notes = '' THEN CONCAT('TREATMENT DETAILS: ', ?, ' (Duration: ', ?, ' min)')
                                                   WHEN notes NOT LIKE '%TREATMENT DETAILS:%' THEN CONCAT(notes, '\nTREATMENT DETAILS: ', ?, ' (Duration: ', ?, ' min)')
                                                   ELSE notes
                                               END
                                           WHERE id = ?";
                    $update_stmt = $this->db->prepare($update_treatment_sql);
                    $treatment_name = $referral['treatment_name'] ?: 'General Treatment';
                    $duration_minutes = $referral['duration_minutes'] ?: 30;
                    
                    $update_stmt->bind_param("iisisii", 
                        $referral['treatment_type_id'], 
                        $duration_minutes,
                        $treatment_name,
                        $duration_minutes,
                        $treatment_name,
                        $duration_minutes,
                        $appointment_id
                    );
                    
                    if (!$update_stmt->execute()) {
                        throw new Exception('Failed to update referral appointment treatment details');
                    }
                    
                    // Update the referral status to 'accepted' since the appointment is being approved
                    $update_referral_status_sql = "UPDATE patient_referrals 
                                                  SET status = 'accepted', 
                                                      responded_at = CURRENT_TIMESTAMP,
                                                      responding_staff_id = ?
                                                  WHERE id = ?";
                    $update_referral_stmt = $this->db->prepare($update_referral_status_sql);
                    $update_referral_stmt->bind_param("ii", $user_id, $referral['referral_id']);
                    
                    if (!$update_referral_stmt->execute()) {
                        throw new Exception('Failed to update referral status to accepted');
                    }
                    
                    // Log the referral acceptance
                    $this->logActivity($user_id, 'REFERRAL_ACCEPTED', 
                        "Approved referral appointment #{$appointment_id} with treatment details copied from original appointment #{$referral['original_appointment_id']}. " .
                        "Treatment: {$treatment_name}, Duration: {$duration_minutes} minutes");
                        
                    error_log("Referral appointment {$appointment_id} approved with treatment details from original appointment {$referral['original_appointment_id']}");
                }
            }
        } catch (Exception $e) {
            error_log("Error handling referral appointment approval: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Ensure referral appointment has treatment details before completion
     */
    private function ensureReferralTreatmentDetails($appointment_id, $user_id) {
        try {
            // First check if appointment already has treatment details
            $check_sql = "SELECT treatment_type_id, duration_minutes FROM appointments WHERE id = ?";
            $check_stmt = $this->db->prepare($check_sql);
            $check_stmt->bind_param("i", $appointment_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $appointment = $check_result->fetch_assoc();
                
                // If appointment already has treatment details, no need to copy
                if ($appointment['treatment_type_id'] && $appointment['duration_minutes']) {
                    return true;
                }
            }
            
            // If missing treatment details, try to copy from original appointment
            $referral_check_sql = "SELECT pr.id as referral_id, pr.original_appointment_id,
                                          orig.treatment_type_id, orig.duration_minutes,
                                          tt.name as treatment_name
                                   FROM patient_referrals pr 
                                   LEFT JOIN appointments orig ON pr.original_appointment_id = orig.id
                                   LEFT JOIN treatment_types tt ON orig.treatment_type_id = tt.id
                                   WHERE pr.new_appointment_id = ?";
            $referral_stmt = $this->db->prepare($referral_check_sql);
            $referral_stmt->bind_param("i", $appointment_id);
            $referral_stmt->execute();
            $referral_result = $referral_stmt->get_result();
            
            if ($referral_result->num_rows > 0) {
                $referral = $referral_result->fetch_assoc();
                
                if ($referral['treatment_type_id']) {
                    // Copy treatment details
                    $update_treatment_sql = "UPDATE appointments 
                                           SET treatment_type_id = ?, 
                                               duration_minutes = ?,
                                               notes = CASE 
                                                   WHEN notes IS NULL OR notes = '' THEN CONCAT('TREATMENT DETAILS: ', ?, ' (Duration: ', ?, ' min)')
                                                   WHEN notes NOT LIKE '%TREATMENT DETAILS:%' THEN CONCAT(notes, '\nTREATMENT DETAILS: ', ?, ' (Duration: ', ?, ' min)')
                                                   ELSE notes
                                               END
                                           WHERE id = ?";
                    $update_stmt = $this->db->prepare($update_treatment_sql);
                    $treatment_name = $referral['treatment_name'] ?: 'General Treatment';
                    $duration_minutes = $referral['duration_minutes'] ?: 30;
                    
                    $update_stmt->bind_param("iisisii", 
                        $referral['treatment_type_id'], 
                        $duration_minutes,
                        $treatment_name,
                        $duration_minutes,
                        $treatment_name,
                        $duration_minutes,
                        $appointment_id
                    );
                    
                    if ($update_stmt->execute()) {
                        error_log("Copied missing treatment details to referral appointment {$appointment_id} from original appointment {$referral['original_appointment_id']}");
                        return true;
                    }
                }
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error ensuring referral treatment details: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send email with prescription and invoice when appointment is completed
     */
    private function sendCompletionEmailWithPrescriptionAndInvoice($appointment_id) {
        try {
            // Get appointment details with patient email
            $stmt = $this->db->prepare("
                SELECT a.*, u.name as patient_name, u.email as patient_email, 
                       b.name as branch_name, b.email as branch_email
                FROM appointments a
                JOIN users u ON a.patient_id = u.id
                JOIN branches b ON a.branch_id = b.id
                WHERE a.id = ?
            ");
            $stmt->bind_param('i', $appointment_id);
            $stmt->execute();
            $appointment = $stmt->get_result()->fetch_assoc();
            
            if (!$appointment || empty($appointment['patient_email'])) {
                error_log("Cannot send completion email: appointment not found or no patient email");
                return false;
            }
            
            // Check if prescription exists
            $stmt = $this->db->prepare("SELECT id FROM prescriptions WHERE appointment_id = ?");
            $stmt->bind_param('i', $appointment_id);
            $stmt->execute();
            $prescription = $stmt->get_result()->fetch_assoc();
            
            if (!$prescription) {
                error_log("Cannot send completion email: no prescription found for appointment {$appointment_id}");
                return false;
            }
            
            // Generate invoice (if invoice system exists)
            // This would call InvoiceController to generate the invoice
            require_once __DIR__ . '/InvoiceController.php';
            $invoiceController = new InvoiceController();
            
            // Prepare email
            $to = $appointment['patient_email'];
            $subject = "Appointment Completed - " . $appointment['branch_name'];
            $message = "Dear " . $appointment['patient_name'] . ",\n\n";
            $message .= "Your dental appointment on " . $appointment['appointment_date'] . " at " . $appointment['appointment_time'] . " has been completed.\n\n";
            $message .= "Your prescription and invoice are attached to this email.\n\n";
            $message .= "Thank you for choosing " . $appointment['branch_name'] . ".\n\n";
            $message .= "Best regards,\n";
            $message .= $appointment['branch_name'];
            
            $headers = "From: " . ($appointment['branch_email'] ?: 'noreply@dentalclinic.com') . "\r\n";
            $headers .= "Reply-To: " . ($appointment['branch_email'] ?: 'noreply@dentalclinic.com') . "\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion();
            
            // Send email (basic version without attachments for now)
            // In production, you would generate PDF attachments for prescription and invoice
            mail($to, $subject, $message, $headers);
            
            error_log("Completion email sent to {$to} for appointment {$appointment_id}");
            return true;
            
        } catch (Exception $e) {
            error_log("Error sending completion email: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get appointment details (supports both regular and walk-in appointments)
     */
    public function getDetails() {
        try {
            $appointment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            $appointment_type = isset($_GET['type']) ? $_GET['type'] : 'auto'; // 'regular', 'walk_in', or 'auto'
            
            if (empty($appointment_id)) {
                return array('success' => false, 'message' => 'Appointment ID is required');
            }
            
            // If type is auto, determine the appointment type by checking both tables
            if ($appointment_type === 'auto') {
                // First check regular appointments
                $check_regular_sql = "SELECT 'regular' as type FROM appointments WHERE id = ?";
                $check_regular_stmt = $this->db->prepare($check_regular_sql);
                $check_regular_stmt->bind_param("i", $appointment_id);
                $check_regular_stmt->execute();
                $check_regular_result = $check_regular_stmt->get_result();
                
                if ($check_regular_result->num_rows > 0) {
                    $appointment_type = 'regular';
                } else {
                    // Check walk-in appointments
                    $check_walkin_sql = "SELECT 'walk_in' as type FROM walk_in_appointments WHERE id = ?";
                    $check_walkin_stmt = $this->db->prepare($check_walkin_sql);
                    $check_walkin_stmt->bind_param("i", $appointment_id);
                    $check_walkin_stmt->execute();
                    $check_walkin_result = $check_walkin_stmt->get_result();
                    
                    if ($check_walkin_result->num_rows > 0) {
                        $appointment_type = 'walk_in';
                    } else {
                        return array('success' => false, 'message' => 'Appointment not found');
                    }
                }
            }
            
            if ($appointment_type === 'walk_in') {
                // Get walk-in appointment details
                $sql = "SELECT w.*, 
                               w.patient_name, w.patient_email, w.patient_phone,
                               w.patient_address, w.patient_birthdate as date_of_birth,
                               b.name as branch_name, s.name as staff_name,
                               tt.name as treatment_name,
                               'walk_in' as appointment_source
                        FROM walk_in_appointments w
                        JOIN branches b ON w.branch_id = b.id
                        LEFT JOIN users s ON w.staff_id = s.id
                        LEFT JOIN treatment_types tt ON w.treatment_type_id = tt.id
                        WHERE w.id = ?";
            } else {
                // Get regular appointment details
                $sql = "SELECT a.*, u.name as patient_name, u.email as patient_email, u.phone as patient_phone,
                               u.address as patient_address, u.date_of_birth, u.gender,
                               b.name as branch_name, s.name as staff_name,
                               tt.name as treatment_name,
                               'regular' as appointment_source
                        FROM appointments a
                        JOIN users u ON a.patient_id = u.id
                        JOIN branches b ON a.branch_id = b.id
                        LEFT JOIN users s ON a.staff_id = s.id
                        LEFT JOIN treatment_types tt ON a.treatment_type_id = tt.id
                        WHERE a.id = ?";
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $appointment_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                return array('success' => false, 'message' => 'Appointment not found');
            }
            
            $appointment = $result->fetch_assoc();
            $appointment['is_walk_in'] = ($appointment_type === 'walk_in');
            
            return array('success' => true, 'appointment' => $appointment);
            
        } catch (Exception $e) {
            return array('success' => false, 'message' => 'Failed to load appointment details');
        }
    }
    
    /**
     * Get patient's own appointments
     */
    public function getPatientAppointments() {
        try {
            $user_id = getSessionUserId();
            
            if (!$user_id) {
                return array('success' => false, 'message' => 'User not found in session. Please login again.');
            }

            // Simplified query first to avoid complex JOIN errors
            $sql = "SELECT a.*, 
                           b.name as branch_name, b.location as branch_address,
                           tt.name as treatment_name, tt.duration_minutes,
                           tt.base_price as treatment_price
                    FROM appointments a 
                    LEFT JOIN branches b ON a.branch_id = b.id 
                    LEFT JOIN treatment_types tt ON a.treatment_type_id = tt.id
                    WHERE a.patient_id = ? 
                    ORDER BY a.created_at DESC";
            
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                error_log("getPatientAppointments - SQL prepare failed: " . $this->db->error);
                return array('success' => false, 'message' => 'Database query preparation failed: ' . $this->db->error);
            }
            
            $stmt->bind_param("i", $user_id);
            
            if (!$stmt->execute()) {
                error_log("getPatientAppointments - SQL execute failed: " . $stmt->error);
                return array('success' => false, 'message' => 'Database query execution failed: ' . $stmt->error);
            }
            
            $result = $stmt->get_result();
            
            $appointments = array();
            while ($row = $result->fetch_assoc()) {
                $appointments[] = array(
                    'id' => $row['id'],
                    'appointment_date' => $row['appointment_date'],
                    'appointment_time' => $row['appointment_time'],
                    'status' => $row['status'],
                    'notes' => $row['notes'] ?: '',
                    
                    'treatment_name' => $row['treatment_name'] ?: 'General Consultation',
                    'treatment_type_id' => $row['treatment_type_id'],
                    'duration_minutes' => $row['duration_minutes'] ?: 60,
                    'treatment_price' => $row['treatment_price'] ?: 0,
                    
                    'branch_id' => $row['branch_id'],
                    'branch_name' => $row['branch_name'] ?: 'Branch ' . ($row['branch_id'] ?? 'Unknown'),
                    'branch_address' => $row['branch_address'] ?? '',
                    'service_category' => 'General Dentistry',
                    'created_at' => $row['created_at'],
                    
                    // Simplified referral info - will be enhanced later if needed
                    'referral_status' => null,
                    'referral_reason' => null,
                    'referred_from_branch' => null,
                    'referred_to_branch' => null,
                    
                    // Enhanced appointment classification
                    'is_referral_from_appointment' => false, // Simplified for now
                    'is_referral_appointment' => false,      // Simplified for now
                    'referral_type' => null,                 // Simplified for now
                    
                    // Referral object for appointments involved in referrals
                    'referral' => null // Simplified for now
                );
            }
            
            // Debug: Log the number of appointments found
            error_log("getPatientAppointments - Found " . count($appointments) . " appointments for user " . $user_id);
            
            return array('success' => true, 'appointments' => $appointments);
            
        } catch (Exception $e) {
            error_log("Get patient appointments error: " . $e->getMessage());
            return array('success' => false, 'message' => 'Failed to load your appointments: ' . $e->getMessage());
        }
    }

    /**
     * Create walk-in appointment using walk_in_appointments table
     */
    public function createWalkInAppointment() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return array('success' => false, 'message' => 'Invalid request method');
        }
        
        try {
            $staff_user_id = getSessionUserId();
            $staff_branch_id = getSessionBranchId();
            
            if (!$staff_user_id || !$staff_branch_id) {
                return array('success' => false, 'message' => 'Staff session not found. Please login again.');
            }
            
            // Validate staff role
            $staff_role = getSessionRole();
            if ($staff_role !== 'staff' && $staff_role !== 'admin') {
                return array('success' => false, 'message' => 'Only staff members can register walk-in patients.');
            }
            
            // Get walk-in patient data
            $name = trim($_POST['name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $birthdate = $_POST['birthdate'] ?? '';
            $address = trim($_POST['address'] ?? '');
            $appointment_date = $_POST['appointment_date'] ?? '';
            $appointment_time = $_POST['appointment_time'] ?? '';
            $treatment_type_id = $_POST['treatment_type_id'] ?? '';
            $priority = $_POST['priority'] ?? 'normal';
            $notes = trim($_POST['notes'] ?? '');
            
            // Debug: Log what we received
            error_log("WALK-IN DEBUG: Received name: '" . $name . "'");
            error_log("WALK-IN DEBUG: Received phone: '" . $phone . "'");
            error_log("WALK-IN DEBUG: Received birthdate: '" . $birthdate . "'");
            error_log("WALK-IN DEBUG: Received address: '" . $address . "'");
            error_log("WALK-IN DEBUG: Staff user ID: " . $staff_user_id);
            error_log("WALK-IN DEBUG: Staff branch ID: " . $staff_branch_id);
            
            // Validate required fields
            if (empty($name) || empty($phone) || empty($appointment_date) || empty($appointment_time) || empty($treatment_type_id)) {
                return array('success' => false, 'message' => 'Patient name, phone, appointment date, time, and treatment are required.');
            }
            
            // Validate email format if provided
            if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return array('success' => false, 'message' => 'Please provide a valid email address.');
            }
            
            // Generate email if not provided
            if (empty($email)) {
                $email = 'walkin_' . time() . '@example.com';
            }
            
            // Begin transaction
            $this->db->begin_transaction();
            
            try {
                // Validate that the selected branch exists and is active
                $branchStmt = $this->db->prepare("SELECT name FROM branches WHERE id = ? AND status = 'active'");
                $branchStmt->bind_param("i", $staff_branch_id);
                $branchStmt->execute();
                $branchResult = $branchStmt->get_result();
                
                if ($branchResult->num_rows === 0) {
                    throw new Exception("Branch is not available for booking.");
                }
                
                $branch_info = $branchResult->fetch_assoc();
                $branch_name = $branch_info['name'];
                
                // Get treatment type details
                $treatmentStmt = $this->db->prepare("SELECT name, duration_minutes FROM treatment_types WHERE id = ? AND is_active = 1");
                $treatmentStmt->bind_param("i", $treatment_type_id);
                $treatmentStmt->execute();
                $treatmentResult = $treatmentStmt->get_result();
                
                if ($treatmentResult->num_rows === 0) {
                    throw new Exception("Invalid treatment type selected");
                }
                
                $treatment = $treatmentResult->fetch_assoc();
                $duration_minutes = $treatment['duration_minutes'];
                $treatment_name = $treatment['name'];
                
                // Calculate end time
                $start_datetime = new DateTime("$appointment_date $appointment_time");
                $end_datetime = clone $start_datetime;
                $end_datetime->add(new DateInterval("PT{$duration_minutes}M"));
                $end_time = $end_datetime->format('H:i');
                
                // Validate date (not in the past)
                $selected_date = strtotime($appointment_date);
                $today = strtotime(date('Y-m-d'));
                
                if ($selected_date < $today) {
                    throw new Exception("Cannot book appointments for past dates");
                }
                
                // Check for time conflicts with existing appointments (both regular and walk-in)
                $conflictStmt = $this->db->prepare(
                    "SELECT COUNT(*) as conflict_count FROM (
                        SELECT appointment_time, end_time FROM appointments 
                        WHERE branch_id = ? AND appointment_date = ? 
                        AND status NOT IN ('cancelled', 'rejected')
                        UNION ALL
                        SELECT appointment_time, end_time FROM walk_in_appointments 
                        WHERE branch_id = ? AND appointment_date = ? 
                        AND status NOT IN ('cancelled', 'rejected')
                    ) AS all_appointments
                    WHERE (
                        (appointment_time < ? AND end_time > ?) OR
                        (appointment_time < ? AND end_time > ?) OR
                        (appointment_time >= ? AND appointment_time < ?)
                    )"
                );
                
                $conflictStmt->bind_param(
                    "isisssssss",
                    $staff_branch_id, $appointment_date, $staff_branch_id, $appointment_date,
                    $end_time, $appointment_time,
                    $end_time, $appointment_time,
                    $appointment_time, $end_time
                );
                
                $conflictStmt->execute();
                $conflictResult = $conflictStmt->get_result();
                $conflict_count = $conflictResult->fetch_assoc()['conflict_count'];
                
                if ($conflict_count > 0) {
                    throw new Exception("This time slot conflicts with existing appointments. Please choose another time.");
                }
                
                // Add walk-in note prefix
                $walk_in_notes = "WALK-IN PATIENT (Registered by staff)\n";
                if ($priority !== 'normal') {
                    $walk_in_notes .= "Priority: " . ucfirst($priority) . "\n";
                }
                if (!empty($notes)) {
                    $walk_in_notes .= "Notes: " . $notes;
                }
                
                // Insert walk-in appointment into the new table
                $sql = "INSERT INTO walk_in_appointments (
                    patient_name, patient_phone, patient_email, patient_birthdate, patient_address,
                    staff_id, branch_id, appointment_date, appointment_time, end_time, 
                    treatment_type_id, duration_minutes, notes, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
                
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param("sssssiisssiis", $name, $phone, $email, $birthdate, $address, $staff_user_id, $staff_branch_id, $appointment_date, $appointment_time, $end_time, $treatment_type_id, $duration_minutes, $walk_in_notes);
                
                error_log("WALK-IN DEBUG: Creating walk-in appointment with name: " . $name . ", staff_id: " . $staff_user_id);
                
                if (!$stmt->execute()) {
                    throw new Exception("Failed to insert walk-in appointment: " . $this->db->error);
                }
                
                $appointment_id = $this->db->insert_id;
                
                // Verify what was actually saved to database
                $verify_sql = "SELECT w.id, w.patient_name, w.patient_phone, w.staff_id, w.status, s.name as staff_name 
                              FROM walk_in_appointments w 
                              LEFT JOIN users s ON w.staff_id = s.id 
                              WHERE w.id = ?";
                $verify_stmt = $this->db->prepare($verify_sql);
                $verify_stmt->bind_param("i", $appointment_id);
                $verify_stmt->execute();
                $verify_result = $verify_stmt->get_result();
                if ($verify_row = $verify_result->fetch_assoc()) {
                    error_log("WALK-IN VERIFICATION - Appointment #{$verify_row['id']}: Patient='{$verify_row['patient_name']}', Phone={$verify_row['patient_phone']}, Status={$verify_row['status']}, Staff ID={$verify_row['staff_id']}, Staff Name='{$verify_row['staff_name']}'");
                }
                
                // Commit transaction
                $this->db->commit();
                
                // Log the activity
                $this->logActivity($staff_user_id, 'walkin_appointment_created', 
                    "Created walk-in appointment #$appointment_id for patient $name ($treatment_name) on $appointment_date at $appointment_time");
                
                return array(
                    'success' => true, 
                    'message' => "Walk-in patient '$name' registered successfully! Appointment scheduled for " . 
                                date('M j, Y', strtotime($appointment_date)) . " at " . 
                                date('g:i A', strtotime($appointment_time)) . " for $treatment_name.",
                    'appointment_id' => $appointment_id,
                    'patient_name' => $name,
                    'appointment_date' => $appointment_date,
                    'appointment_time' => $appointment_time,
                    'treatment_name' => $treatment_name
                );
                
            } catch (Exception $e) {
                $this->db->rollback();
                throw $e;
            }
            
        } catch (Exception $e) {
            error_log("Create walk-in appointment error: " . $e->getMessage());
            return array('success' => false, 'message' => 'Failed to create walk-in appointment: ' . $e->getMessage());
        }
    }

    /**
     * Book a new appointment (for patients) with time blocking
     */
    public function book() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return array('success' => false, 'message' => 'Invalid request method');
        }
        
        try {
            $user_id = getSessionUserId();
            $session_branch_id = getSessionBranchId(); // Keep for validation if needed
            
            // Get branch_id from form data (clinic_id) - this allows cross-clinic booking
            $branch_id = isset($_POST['clinic_id']) ? intval($_POST['clinic_id']) : $session_branch_id;
            
            // Debug: Log session values and selected clinic
            error_log("Book appointment - User ID: " . ($user_id ? $user_id : 'null') . 
                     ", Session Branch ID: " . ($session_branch_id ? $session_branch_id : 'null') . 
                     ", Selected Clinic ID: " . $branch_id);
            
            if (!$user_id) {
                return array('success' => false, 'message' => 'User session not found. Please login again.');
            }
            
            if (!$branch_id) {
                return array('success' => false, 'message' => 'Please select a clinic for your appointment.');
            }
            
            // Validate that the selected branch exists and is active
            $branchStmt = $this->db->prepare("SELECT name FROM branches WHERE id = ? AND status = 'active'");
            $branchStmt->bind_param("i", $branch_id);
            $branchStmt->execute();
            $branchResult = $branchStmt->get_result();
            
            if ($branchResult->num_rows === 0) {
                return array('success' => false, 'message' => 'Selected clinic is not available for booking.');
            }
            
            $branch_info = $branchResult->fetch_assoc();
            $branch_name = $branch_info['name'];
            
            $appointment_date = $_POST['appointment_date'] ?? '';
            $appointment_time = $_POST['appointment_time'] ?? '';
            $treatment_type_id = $_POST['treatment_type_id'] ?? '';
            $notes = $_POST['notes'] ?? '';
            
            // Debug: Log form data and clinic selection
            error_log("Book appointment - Date: $appointment_date, Time: $appointment_time, Treatment: $treatment_type_id");
            error_log("Book appointment - Selected Clinic: $branch_name (ID: $branch_id), Notes: $notes");
            
            if (empty($appointment_date) || empty($appointment_time) || empty($treatment_type_id)) {
                return array('success' => false, 'message' => 'Appointment date, time, and treatment type are required');
            }
            
            // Get treatment type details
            $treatmentStmt = $this->db->prepare("SELECT name, duration_minutes FROM treatment_types WHERE id = ? AND is_active = 1");
            $treatmentStmt->bind_param("i", $treatment_type_id);
            $treatmentStmt->execute();
            $treatmentResult = $treatmentStmt->get_result();
            
            if ($treatmentResult->num_rows === 0) {
                return array('success' => false, 'message' => 'Invalid treatment type selected');
            }
            
            $treatment = $treatmentResult->fetch_assoc();
            $duration_minutes = $treatment['duration_minutes'];
            $treatment_name = $treatment['name'];
            
            // Calculate end time
            $start_datetime = new DateTime("$appointment_date $appointment_time");
            $end_datetime = clone $start_datetime;
            $end_datetime->add(new DateInterval("PT{$duration_minutes}M"));
            $end_time = $end_datetime->format('H:i');
            
            // Validate date (not in the past)
            $selected_date = strtotime($appointment_date);
            $today = strtotime(date('Y-m-d'));
            
            if ($selected_date < $today) {
                return array('success' => false, 'message' => 'Cannot book appointments for past dates');
            }
            
            // Begin transaction
            $this->db->begin_transaction();
            
            try {
                // Check for time conflicts with existing appointments
                $conflictStmt = $this->db->prepare(
                    "SELECT COUNT(*) as conflict_count FROM appointments 
                     WHERE branch_id = ? AND appointment_date = ? 
                     AND status NOT IN ('cancelled', 'rejected')
                     AND (
                         (appointment_time < ? AND end_time > ?) OR
                         (appointment_time < ? AND end_time > ?) OR
                         (appointment_time >= ? AND appointment_time < ?)
                     )"
                );
                
                $conflictStmt->bind_param(
                    "isssssss",
                    $branch_id, $appointment_date,
                    $end_time, $appointment_time,    // Existing appointment starts before our end and ends after our start
                    $end_time, $appointment_time,    // Same check
                    $appointment_time, $end_time     // Existing appointment is completely within our time
                );
                
                $conflictStmt->execute();
                $conflictResult = $conflictStmt->get_result();
                $conflict_count = $conflictResult->fetch_assoc()['conflict_count'];
                
                if ($conflict_count > 0) {
                    $this->db->rollback();
                    return array('success' => false, 'message' => 'This time slot conflicts with existing appointments. Please choose another time.');
                }
                
                // Check for time conflicts with existing time blocks
                $blockStmt = $this->db->prepare(
                    "SELECT COUNT(*) as block_count FROM appointment_time_blocks 
                     WHERE branch_id = ? AND appointment_date = ?
                     AND (
                         (start_time < ? AND end_time > ?) OR
                         (start_time < ? AND end_time > ?) OR
                         (start_time >= ? AND start_time < ?)
                     )"
                );
                
                $blockStmt->bind_param(
                    "isssssss",
                    $branch_id, $appointment_date,
                    $end_time, $appointment_time,
                    $end_time, $appointment_time,
                    $appointment_time, $end_time
                );
                
                $blockStmt->execute();
                $blockResult = $blockStmt->get_result();
                $block_count = $blockResult->fetch_assoc()['block_count'];
                
                if ($block_count > 0) {
                    $this->db->rollback();
                    return array('success' => false, 'message' => 'This time slot is blocked. Please choose another time.');
                }
                
                // Insert appointment
                $sql = "INSERT INTO appointments (patient_id, branch_id, appointment_date, appointment_time, end_time, treatment_type_id, duration_minutes, notes, status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param("iisssiss", $user_id, $branch_id, $appointment_date, $appointment_time, $end_time, $treatment_type_id, $duration_minutes, $notes);
                
                if (!$stmt->execute()) {
                    throw new Exception("Failed to insert appointment: " . $this->db->error);
                }
                
                $appointment_id = $this->db->insert_id;
                
                // Create time blocks for the appointment duration
                $this->createTimeBlocks($branch_id, $appointment_date, $appointment_time, $end_time, $appointment_id);
                
                // Record initial appointment history
                $event_description = "Initial appointment created: $treatment_name scheduled for $appointment_date at $appointment_time";
                $this->recordAppointmentHistory($appointment_id, $user_id, 'created', $event_description);
                
                // Commit transaction
                $this->db->commit();
                
                $this->logActivity($user_id, 'appointment_booked', "Booked $treatment_name appointment #$appointment_id for $appointment_date at $appointment_time (Duration: {$duration_minutes} minutes)");
                
                return array(
                    'success' => true, 
                    'message' => "Appointment booked successfully! Your {$treatment_name} appointment is scheduled for " . date('M j, Y', strtotime($appointment_date)) . " at " . date('g:i A', strtotime($appointment_time)) . " (Duration: " . $this->formatDuration($duration_minutes) . ")",
                    'appointment_id' => $appointment_id,
                    'duration_minutes' => $duration_minutes,
                    'end_time' => $end_time
                );
                
            } catch (Exception $e) {
                $this->db->rollback();
                throw $e;
            }
            
        } catch (Exception $e) {
            error_log("Book appointment error: " . $e->getMessage());
            return array('success' => false, 'message' => 'Failed to book appointment: ' . $e->getMessage());
        }
    }
    
    /**
     * Create time blocks for an appointment
     */
    private function createTimeBlocks($branch_id, $appointment_date, $start_time, $end_time, $appointment_id) {
        $blockStmt = $this->db->prepare(
            "INSERT INTO appointment_time_blocks (branch_id, appointment_date, start_time, end_time, appointment_id, is_blocked, block_reason) 
             VALUES (?, ?, ?, ?, ?, 1, 'Auto-blocked for appointment duration')"
        );
        
        $blockStmt->bind_param("issii", $branch_id, $appointment_date, $start_time, $end_time, $appointment_id);
        
        if (!$blockStmt->execute()) {
            throw new Exception("Failed to create time blocks: " . $this->db->error);
        }
    }
    
    /**
     * Format duration in a human-readable way
     */
    private function formatDuration($minutes) {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        
        if ($hours === 0) {
            return "{$mins} minutes";
        } else if ($mins === 0) {
            return "{$hours} hour" . ($hours > 1 ? 's' : '');
        } else {
            return "{$hours} hour" . ($hours > 1 ? 's' : '') . " {$mins} minutes";
        }
    }
    
    /**
     * Cancel an appointment (for patients) and remove time blocks
     */
    public function cancel() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return array('success' => false, 'message' => 'Invalid request method');
        }
        
        try {
            $user_id = getSessionUserId();
            
            // Handle JSON input for cancel requests
            $input = json_decode(file_get_contents('php://input'), true);
            $appointment_id = isset($input['appointment_id']) ? intval($input['appointment_id']) : 0;
            $reason = isset($input['reason']) ? $input['reason'] : '';
            
            // Fallback to POST data
            if (empty($appointment_id)) {
                $appointment_id = isset($_POST['appointment_id']) ? intval($_POST['appointment_id']) : 0;
                $reason = isset($_POST['reason']) ? $_POST['reason'] : '';
            }
            
            if (!$user_id) {
                return array('success' => false, 'message' => 'User session not found');
            }
            
            if (!$appointment_id) {
                return array('success' => false, 'message' => 'Appointment ID is required');
            }
            
            // Begin transaction
            $this->db->begin_transaction();
            
            try {
                // Verify appointment belongs to user
                $sql = "SELECT * FROM appointments WHERE id = ? AND patient_id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param("ii", $appointment_id, $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 0) {
                    $this->db->rollback();
                    return array('success' => false, 'message' => 'Appointment not found or access denied');
                }
                
                $appointment = $result->fetch_assoc();
                
                if ($appointment['status'] === 'cancelled') {
                    $this->db->rollback();
                    return array('success' => false, 'message' => 'Appointment is already cancelled');
                }
                
                if ($appointment['status'] === 'completed') {
                    $this->db->rollback();
                    return array('success' => false, 'message' => 'Cannot cancel completed appointment');
                }
                
                // Update appointment status
                $sql = "UPDATE appointments SET status = 'cancelled', cancellation_reason = ? WHERE id = ? AND patient_id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param("sii", $reason, $appointment_id, $user_id);
                
                if (!$stmt->execute()) {
                    throw new Exception("Failed to update appointment status");
                }
                
                // Remove time blocks associated with this appointment
                $blockSql = "DELETE FROM appointment_time_blocks WHERE appointment_id = ?";
                $blockStmt = $this->db->prepare($blockSql);
                $blockStmt->bind_param("i", $appointment_id);
                
                if (!$blockStmt->execute()) {
                    throw new Exception("Failed to remove time blocks");
                }
                
                // Commit transaction
                $this->db->commit();
                
                $this->logActivity($user_id, 'appointment_cancelled', "Cancelled appointment #$appointment_id. Reason: $reason");
                
                return array('success' => true, 'message' => 'Appointment cancelled successfully. Time slot is now available for other patients.');
                
            } catch (Exception $e) {
                $this->db->rollback();
                throw $e;
            }
            
        } catch (Exception $e) {
            error_log("Cancel appointment error: " . $e->getMessage());
            return array('success' => false, 'message' => 'Failed to cancel appointment: ' . $e->getMessage());
        }
    }

    /**
     * Get appointment history for tracking appointment journey
     */
    public function getAppointmentHistory() {
        try {
            $appointment_id = $_GET['appointment_id'] ?? 0;
            $user_id = getSessionUserId();
            $user_role = getSessionRole();
            
            if (!$appointment_id) {
                return array('success' => false, 'message' => 'Appointment ID is required');
            }
            
            // Verify user has permission to view this appointment history
            if ($user_role === 'patient') {
                // Patients can only view their own appointment history
                $verify_stmt = $this->db->prepare("SELECT patient_id FROM appointments WHERE id = ?");
                $verify_stmt->bind_param("i", $appointment_id);
                $verify_stmt->execute();
                $verify_result = $verify_stmt->get_result();
                
                if (!$verify_result || $verify_result->num_rows === 0) {
                    return array('success' => false, 'message' => 'Appointment not found');
                }
                
                $appointment_data = $verify_result->fetch_assoc();
                if ($appointment_data['patient_id'] !== $user_id) {
                    return array('success' => false, 'message' => 'Access denied');
                }
            }
            
            // Get appointment history
            $stmt = $this->db->prepare("
                SELECT 
                    ah.*,
                    cb.name as changed_by_name
                FROM appointment_history ah
                LEFT JOIN users cb ON ah.changed_by_id = cb.id
                WHERE ah.appointment_id = ?
                ORDER BY ah.created_at ASC
            ");
            $stmt->bind_param("i", $appointment_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $history = array();
            while ($row = $result->fetch_assoc()) {
                $history[] = $row;
            }
            
            return array(
                'success' => true, 
                'history' => $history,
                'appointment_id' => $appointment_id
            );
            
        } catch (Exception $e) {
            error_log("Get appointment history error: " . $e->getMessage());
            return array('success' => false, 'message' => 'Failed to retrieve appointment history');
        }
    }

    /**
     * Record appointment history for tracking appointment journey
     */
    private function recordAppointmentHistory($appointment_id, $patient_id, $event_type, $event_description = null, $referring_staff_id = null, $referral_reason = null) {
        try {
            // Get current appointment details
            $stmt = $this->db->prepare("
                SELECT a.*, 
                       b.name as branch_name, 
                       tt.name as treatment_name,
                       bs.price as treatment_price,
                       u.name as staff_name,
                       ru.name as referring_staff_name
                FROM appointments a
                LEFT JOIN branches b ON a.branch_id = b.id
                LEFT JOIN treatment_types tt ON a.treatment_type_id = tt.id
                LEFT JOIN branch_services bs ON a.branch_id = bs.branch_id AND a.treatment_type_id = bs.treatment_type_id
                LEFT JOIN users u ON a.staff_id = u.id
                LEFT JOIN users ru ON ? = ru.id
                WHERE a.id = ?
            ");
            $stmt->bind_param("ii", $referring_staff_id, $appointment_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($appointment = $result->fetch_assoc()) {
                // Get the next sequence number for this appointment
                $seq_stmt = $this->db->prepare("
                    SELECT COALESCE(MAX(sequence_number), 0) + 1 as next_sequence 
                    FROM appointment_history 
                    WHERE appointment_id = ?
                ");
                $seq_stmt->bind_param("i", $appointment_id);
                $seq_stmt->execute();
                $seq_result = $seq_stmt->get_result();
                $sequence_number = $seq_result->fetch_assoc()['next_sequence'];
                
                // Create user ID for created_by field
                $created_by = getSessionUserId() ?: $patient_id;
                
                // Insert history record
                $history_stmt = $this->db->prepare("
                    INSERT INTO appointment_history (
                        appointment_id, sequence_number, patient_name, patient_email, action, 
                        branch_name, treatment_type, appointment_date, appointment_time, 
                        changed_by_id, message
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                // Assign variables for bind_param
                $patient_name = isset($appointment['patient_name']) ? $appointment['patient_name'] : 'Unknown';
                $patient_email = isset($appointment['patient_email']) ? $appointment['patient_email'] : '';
                $branch_name = $appointment['branch_name'];
                $treatment_name = $appointment['treatment_name'];
                $appointment_date = $appointment['appointment_date'];
                $appointment_time = $appointment['appointment_time'];
                
                $history_stmt->bind_param(
                    "iisssssssis",
                    $appointment_id,
                    $sequence_number,
                    $patient_name,
                    $patient_email,
                    $event_type,
                    $branch_name,
                    $treatment_name,
                    $appointment_date,
                    $appointment_time,
                    $created_by,
                    $event_description
                );
                
                if ($history_stmt->execute()) {
                    error_log("Appointment history recorded: appointment_id=$appointment_id, event_type=$event_type, sequence=$sequence_number");
                    return true;
                } else {
                    error_log("Failed to record appointment history: " . $this->db->error);
                    return false;
                }
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Record appointment history error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Record appointment history for walk-in patients
     */
    private function recordAppointmentHistoryForWalkIn($appointment_id, $walk_in_patient_id, $event_type, $event_description = null, $referring_staff_id = null, $referral_reason = null) {
        try {
            // Get current appointment details with walk-in patient info
            $stmt = $this->db->prepare("
                SELECT a.*, 
                       b.name as branch_name, 
                       tt.name as treatment_name,
                       bs.price as treatment_price,
                       u.name as staff_name,
                       ru.name as referring_staff_name,
                       wip.name as patient_name,
                       wip.phone as patient_phone
                FROM appointments a
                LEFT JOIN branches b ON a.branch_id = b.id
                LEFT JOIN treatment_types tt ON a.treatment_type_id = tt.id
                LEFT JOIN branch_services bs ON a.branch_id = bs.branch_id AND a.treatment_type_id = bs.treatment_type_id
                LEFT JOIN users u ON a.staff_id = u.id
                LEFT JOIN users ru ON ? = ru.id
                LEFT JOIN walk_in_patients wip ON a.walk_in_patient_id = wip.id
                WHERE a.id = ? AND a.patient_type = 'walk_in'
            ");
            $stmt->bind_param("ii", $referring_staff_id, $appointment_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($appointment = $result->fetch_assoc()) {
                // Get the next sequence number for this appointment
                $seq_stmt = $this->db->prepare("
                    SELECT COALESCE(MAX(sequence_number), 0) + 1 as next_sequence 
                    FROM appointment_history 
                    WHERE appointment_id = ?
                ");
                $seq_stmt->bind_param("i", $appointment_id);
                $seq_stmt->execute();
                $seq_result = $seq_stmt->get_result();
                $sequence_number = $seq_result->fetch_assoc()['next_sequence'];
                
                // Create user ID for created_by field (use staff ID for walk-ins)
                $created_by = getSessionUserId() ?: $appointment['staff_id'];
                
                // Insert history record for walk-in patient
                $history_stmt = $this->db->prepare("
                    INSERT INTO appointment_history (
                        walkin_appointment_id, sequence_number, patient_name, patient_email, action, 
                        branch_name, treatment_type, appointment_date, appointment_time, 
                        changed_by_id, message
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                // Assign variables for bind_param
                $patient_name = $appointment['patient_name'];
                $patient_email = isset($appointment['patient_phone']) ? $appointment['patient_phone'] : ''; // using phone as email isn't available for walk-ins
                $branch_name = $appointment['branch_name'];
                $treatment_name = $appointment['treatment_name'];
                $appointment_date = $appointment['appointment_date'];
                $appointment_time = $appointment['appointment_time'];
                
                $history_stmt->bind_param(
                    "iisssssssiss",
                    $walk_in_patient_id,
                    $sequence_number,
                    $patient_name,
                    $patient_email,
                    $event_type,
                    $branch_name,
                    $treatment_name,
                    $appointment_date,
                    $appointment_time,
                    $created_by,
                    $event_description
                );
                
                if ($history_stmt->execute()) {
                    error_log("Walk-in appointment history recorded: appointment_id=$appointment_id, walk_in_patient_id=$walk_in_patient_id, event_type=$event_type, sequence=$sequence_number");
                    return true;
                } else {
                    error_log("Failed to record walk-in appointment history: " . $this->db->error);
                    return false;
                }
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Record walk-in appointment history error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate invoice for completed appointment
     */
    private function generateInvoiceForCompletedAppointment($appointment_id, $staff_id) {
        try {
            error_log("Generating invoice for completed appointment: $appointment_id by staff: $staff_id");
            
            $invoiceController = new InvoiceController();
            $result = $invoiceController->generateInvoiceForAppointment($appointment_id, $staff_id);
            
            if ($result['success']) {
                error_log("Invoice generated successfully: " . $result['invoice']['invoice_number']);
                
                // Log the invoice generation
                $this->logActivity(
                    $staff_id, 
                    'INVOICE_GENERATED', 
                    "Generated invoice {$result['invoice']['invoice_number']} for completed appointment #{$appointment_id}"
                );
            } else {
                error_log("Failed to generate invoice for appointment $appointment_id: " . $result['message']);
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Error in generateInvoiceForCompletedAppointment: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to generate invoice: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Generate invoice for completed walk-in appointment
     */
    private function generateInvoiceForCompletedWalkInAppointment($appointment_id, $staff_id) {
        try {
            error_log("Generating invoice for completed walk-in appointment: $appointment_id by staff: $staff_id");
            
            $invoiceController = new InvoiceController();
            $result = $invoiceController->generateInvoiceForWalkInAppointment($appointment_id, $staff_id);
            
            if ($result['success']) {
                error_log("Walk-in invoice generated successfully: " . $result['invoice']['invoice_number']);
                
                // Log the invoice generation
                $this->logActivity(
                    $staff_id, 
                    'WALK_IN_INVOICE_GENERATED', 
                    "Generated walk-in invoice {$result['invoice']['invoice_number']} for completed walk-in appointment #{$appointment_id}"
                );
            } else {
                error_log("Failed to generate walk-in invoice for appointment $appointment_id: " . $result['message']);
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Error in generateInvoiceForCompletedWalkInAppointment: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to generate walk-in invoice: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Log user activity
     */
    private function logActivity($user_id, $action, $description) {
        try {
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $stmt = $this->db->prepare("INSERT INTO system_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $user_id, $action, $description, $ip_address);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("Log activity error: " . $e->getMessage());
        }
    }

    /**
     * Create notification entry in appointment_history for patient notifications
     */
    private function createAppointmentNotification($appointment_id, $status, $staff_id) {
        try {
            // Get appointment details
            $apt_sql = "SELECT a.*, u.name as patient_name, tt.name as treatment_name, b.name as branch_name, s.name as staff_name
                       FROM appointments a
                       LEFT JOIN users u ON a.patient_id = u.id
                       LEFT JOIN treatment_types tt ON a.treatment_type_id = tt.id
                       LEFT JOIN branches b ON a.branch_id = b.id
                       LEFT JOIN users s ON ? = s.id
                       WHERE a.id = ?";
            $apt_stmt = $this->db->prepare($apt_sql);
            $apt_stmt->bind_param("ii", $staff_id, $appointment_id);
            $apt_stmt->execute();
            $apt_result = $apt_stmt->get_result();
            
            if ($apt_result->num_rows === 0) {
                return; // Appointment not found
            }
            
            $appointment = $apt_result->fetch_assoc();
            
            // Map status to event type and description
            $event_mapping = [
                'approved' => [
                    'event_type' => 'confirmed',
                    'description' => "Your {$appointment['treatment_name']} appointment has been confirmed by {$appointment['staff_name']}"
                ],
                'completed' => [
                    'event_type' => 'completed',
                    'description' => "Your {$appointment['treatment_name']} appointment has been completed successfully"
                ],
                'cancelled' => [
                    'event_type' => 'cancelled',
                    'description' => "Your {$appointment['treatment_name']} appointment has been cancelled by {$appointment['staff_name']}"
                ],
                'referred' => [
                    'event_type' => 'referred',
                    'description' => "Your {$appointment['treatment_name']} appointment has been referred to another branch"
                ]
            ];
            
            if (!isset($event_mapping[$status])) {
                return; // No notification needed for this status
            }
            
            $event_info = $event_mapping[$status];
            
            // Get next sequence number
            $seq_sql = "SELECT COALESCE(MAX(sequence_number), 0) + 1 as next_seq 
                       FROM appointment_history 
                       WHERE appointment_id = ?";
            $seq_stmt = $this->db->prepare($seq_sql);
            $seq_stmt->bind_param("i", $appointment_id);
            $seq_stmt->execute();
            $seq_result = $seq_stmt->get_result();
            $sequence_number = $seq_result->fetch_assoc()['next_seq'];
            
            // Insert notification entry
            $notification_sql = "INSERT INTO appointment_history (
                appointment_id, sequence_number, patient_name, patient_email, action, 
                branch_name, treatment_type, appointment_date, appointment_time, 
                changed_by_id, message, is_read
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $notification_stmt = $this->db->prepare($notification_sql);
            // Assign variables for bind_param
            $patient_name = isset($appointment['patient_name']) ? $appointment['patient_name'] : 'Unknown';
            $patient_email = isset($appointment['patient_email']) ? $appointment['patient_email'] : '';
            $branch_name = $appointment['branch_name'];
            $treatment_name = $appointment['treatment_name'];
            $appointment_date = $appointment['appointment_date'];
            $appointment_time = $appointment['appointment_time'];
            $event_description = $event_info['description'];
            $event_type = $event_info['event_type'];
            $is_read = 0; // false
            
            $notification_stmt->bind_param(
                "iisssssssisi",
                $appointment_id,
                $sequence_number,
                $patient_name,
                $patient_email,
                $event_type,
                $branch_name,
                $treatment_name,
                $appointment_date,
                $appointment_time,
                $staff_id,
                $event_description,
                $is_read
            );
            
            $notification_stmt->execute();
            
        } catch (Exception $e) {
            error_log("Create appointment notification error: " . $e->getMessage());
        }
    }

    /**
     * Get new appointments since a specific timestamp (for real-time notifications)
     */
    public function getNewAppointments() {
        try {
            $user_role = getSessionRole();
            $user_branch_id = getSessionBranchId();
            
            // Get timestamp from query parameter
            $since = isset($_GET['since']) ? intval($_GET['since']) : 0;
            $since_datetime = date('Y-m-d H:i:s', $since / 1000); // Convert from JS timestamp
            
            $sql = "SELECT a.id, a.appointment_date, a.appointment_time, a.status, a.notes, 
                           a.treatment_type_id, a.duration_minutes,
                           u.name as patient_name, u.email as patient_email, u.phone as patient_phone,
                           b.name as branch_name, s.name as staff_name,
                           tt.name as treatment_name,
                           a.created_at, a.updated_at
                    FROM appointments a
                    JOIN users u ON a.patient_id = u.id
                    JOIN branches b ON a.branch_id = b.id
                    LEFT JOIN users s ON a.staff_id = s.id
                    LEFT JOIN treatment_types tt ON a.treatment_type_id = tt.id
                    WHERE a.created_at > ? AND a.status = 'pending'";
            
            $params = array($since_datetime);
            $types = "s";
            
            // Role-based filtering
            if ($user_role === 'staff') {
                $sql .= " AND a.branch_id = ?";
                $params[] = $user_branch_id;
                $types .= "i";
            }
            // Admin can see all new appointments
            
            $sql .= " ORDER BY a.created_at DESC LIMIT 10";
            
            $stmt = $this->db->prepare($sql);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            $appointments = array();
            while ($row = $result->fetch_assoc()) {
                $appointments[] = $row;
            }
            
            return array(
                'success' => true,
                'appointments' => $appointments,
                'count' => count($appointments)
            );
            
        } catch (Exception $e) {
            error_log("Get new appointments error: " . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Failed to check for new appointments: ' . $e->getMessage(),
                'appointments' => array()
            );
        }
    }

    /**
     * Get patient appointment updates for polling - returns updates since given timestamp
     */
    public function getPatientUpdates() {
        try {
            $user_id = getSessionUserId();
            $user_role = getSessionRole();
            
            if (!$user_id) {
                return ['success' => false, 'message' => 'User not found in session. Please login again.'];
            }
            
            // Only allow patients to access this endpoint
            if ($user_role !== 'patient') {
                return ['success' => false, 'message' => 'Unauthorized access'];
            }
            
            // Get timestamp from query parameter
            $since = isset($_GET['since']) ? intval($_GET['since']) : 0;
            $since_datetime = date('Y-m-d H:i:s', $since / 1000); // Convert from JS timestamp
            
            $sql = "SELECT a.id, a.appointment_date, a.appointment_time, a.status, a.notes, 
                           a.treatment_type_id, a.duration_minutes,
                           b.name as branch_name, 
                           tt.name as treatment_name,
                           a.created_at, a.updated_at,
                           pr.status as referral_status,
                           pr.reason as referral_reason
                    FROM appointments a
                    JOIN branches b ON a.branch_id = b.id
                    LEFT JOIN treatment_types tt ON a.treatment_type_id = tt.id
                    LEFT JOIN patient_referrals pr ON a.id = pr.appointment_id
                    WHERE a.patient_id = ? AND a.updated_at > ?
                    ORDER BY a.updated_at DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("is", $user_id, $since_datetime);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $appointments = array();
            while ($row = $result->fetch_assoc()) {
                $appointments[] = $row;
            }
            
            return array(
                'success' => true,
                'appointments' => $appointments,
                'count' => count($appointments)
            );
            
        } catch (Exception $e) {
            error_log("Get patient updates error: " . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Failed to check for patient updates: ' . $e->getMessage(),
                'appointments' => array()
            );
        }
    }

    /**
     * Test database schema for appointment tracking
     */
    public function testDatabaseSchema() {
        try {
            // Check if appointment_history table exists
            $sql = "DESCRIBE appointment_history";
            $result = $this->db->query($sql);
            
            if (!$result) {
                return array(
                    'success' => false, 
                    'message' => 'appointment_history table does not exist'
                );
            }
            
            $schema = array();
            while ($row = $result->fetch_assoc()) {
                $schema[] = $row;
            }
            
            return array(
                'success' => true,
                'message' => 'Database schema is valid',
                'schema' => $schema
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => 'Database schema test failed: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Get complete patient journey (all appointments and history)
     */
    public function getPatientJourney() {
        $patient_id = $_GET['patient_id'] ?? null;
        
        if (!$patient_id) {
            return array('success' => false, 'message' => 'Patient ID is required');
        }
        
        try {
            // Get all appointments for the patient with basic details
            $sql = "SELECT a.id, a.appointment_date, a.appointment_time, a.status, a.notes,
                           a.created_at, a.updated_at,
                           b.name as branch_name,
                           tt.name as treatment_name, tt.price as treatment_price,
                           u.name as patient_name
                    FROM appointments a
                    LEFT JOIN branches b ON a.branch_id = b.id  
                    LEFT JOIN treatment_types tt ON a.treatment_type_id = tt.id
                    LEFT JOIN users u ON a.patient_id = u.id
                    WHERE a.patient_id = ?
                    ORDER BY a.created_at ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $patient_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $appointments = array();
            while ($row = $result->fetch_assoc()) {
                $appointments[] = $row;
            }
            
            return array(
                'success' => true,
                'appointments' => $appointments,
                'total_count' => count($appointments)
            );
            
        } catch (Exception $e) {
            error_log("Get patient journey error: " . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Failed to retrieve patient journey: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Get blocked time slots for a specific date
     */
    public function getBlockedTimes() {
        try {
            $date = isset($_GET['date']) ? $_GET['date'] : '';
            $user_branch_id = getSessionBranchId();
            
            if (empty($date)) {
                return array(
                    'success' => false,
                    'message' => 'Date parameter is required'
                );
            }
            
            if (!$user_branch_id) {
                return array(
                    'success' => false,
                    'message' => 'User branch not found'
                );
            }
            
            // Get blocked time slots for the specified date and branch
            $sql = "SELECT id, branch_id, appointment_date, start_time, end_time, 
                           is_blocked, block_reason, created_at, updated_at
                    FROM appointment_time_blocks 
                    WHERE branch_id = ? AND appointment_date = ? AND is_blocked = 1
                    ORDER BY start_time ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("is", $user_branch_id, $date);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $blocked_times = array();
            while ($row = $result->fetch_assoc()) {
                // Add formatted display information
                $start_time = new DateTime($row['start_time']);
                $end_time = new DateTime($row['end_time']);
                
                $row['formatted_start'] = $start_time->format('g:i A');
                $row['formatted_end'] = $end_time->format('g:i A');
                $row['time_range'] = $row['formatted_start'] . ' - ' . $row['formatted_end'];
                
                $blocked_times[] = $row;
            }
            
            return array(
                'success' => true,
                'blocked_times' => $blocked_times,
                'total_blocks' => count($blocked_times),
                'date' => $date,
                'branch_id' => $user_branch_id
            );
            
        } catch (Exception $e) {
            error_log("Get blocked times error: " . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Failed to retrieve blocked times: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Create notification for walk-in appointment status changes
     */
    private function createWalkInAppointmentNotification($appointment_id, $status, $staff_id) {
        try {
            // Get walk-in appointment details
            $apt_sql = "SELECT w.*, tt.name as treatment_name, b.name as branch_name, s.name as staff_name
                       FROM walk_in_appointments w
                       LEFT JOIN treatment_types tt ON w.treatment_type_id = tt.id
                       LEFT JOIN branches b ON w.branch_id = b.id
                       LEFT JOIN users s ON ? = s.id
                       WHERE w.id = ?";
            $apt_stmt = $this->db->prepare($apt_sql);
            $apt_stmt->bind_param("ii", $staff_id, $appointment_id);
            $apt_stmt->execute();
            $apt_result = $apt_stmt->get_result();
            
            if ($apt_result->num_rows === 0) {
                return; // Walk-in appointment not found
            }
            
            $appointment = $apt_result->fetch_assoc();
            
            // Map status to event type and description for walk-in appointments
            $event_mapping = [
                'approved' => [
                    'event_type' => 'confirmed',
                    'description' => "Walk-in {$appointment['treatment_name']} appointment for {$appointment['patient_name']} has been confirmed by {$appointment['staff_name']}"
                ],
                'completed' => [
                    'event_type' => 'completed',
                    'description' => "Walk-in {$appointment['treatment_name']} appointment for {$appointment['patient_name']} has been completed successfully"
                ],
                'cancelled' => [
                    'event_type' => 'cancelled',
                    'description' => "Walk-in {$appointment['treatment_name']} appointment for {$appointment['patient_name']} has been cancelled by {$appointment['staff_name']}"
                ]
            ];
            
            if (!isset($event_mapping[$status])) {
                return; // No notification needed for this status
            }
            
            $event_info = $event_mapping[$status];
            
            // For walk-in appointments, we'll log to a walk_in_appointment_history table if it exists,
            // or just log the activity for staff records since walk-in patients don't have user accounts
            $this->logActivity($staff_id, 'WALKIN_APPOINTMENT_UPDATE', 
                "Walk-in appointment #{$appointment_id} status updated to {$status}. " .
                "Patient: {$appointment['patient_name']}, Phone: {$appointment['patient_phone']}, " .
                "Treatment: {$appointment['treatment_name']}, Staff: {$appointment['staff_name']}");
            
        } catch (Exception $e) {
            error_log("Create walk-in appointment notification error: " . $e->getMessage());
        }
    }
}

// Handle API requests - only run if accessed directly, not when included by other files
if (php_sapi_name() !== 'cli' && !defined('APPOINTMENT_CONTROLLER_INCLUDED')) {
    // Ultimate duplicate prevention - check for any output already sent
    if (ob_get_contents() !== false && strlen(ob_get_contents()) > 0) {
        exit; // Some output already sent, prevent duplicate
    }
    
    // Prevent multiple executions using output buffering state
    if (defined('APPOINTMENT_CONTROLLER_LOCK')) {
        exit; // Already processing
    }
    define('APPOINTMENT_CONTROLLER_LOCK', true);
    
    // Clear any existing output and start fresh
    while (ob_get_level()) {
        ob_end_clean();
    }
    ob_start();
    
    // Set JSON header immediately
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
    
    try {
        // Check if user is logged in
        if (!isLoggedIn()) {
            ob_clean();
            http_response_code(401);
            echo json_encode(array('success' => false, 'message' => 'Please login to access this resource'));
            ob_end_flush();
            exit;
        }
        
        $action = isset($_GET['action']) ? $_GET['action'] : '';
        
        // Debug logging for action
        error_log("AppointmentController DEBUG: action = '" . $action . "'");
        error_log("AppointmentController DEBUG: GET = " . print_r($_GET, true));
        error_log("AppointmentController DEBUG: REQUEST_URI = " . $_SERVER['REQUEST_URI']);
        
        // Check if this is a POST request without action parameter (for booking)
        if (empty($action) && $_SERVER['REQUEST_METHOD'] === 'POST') {
            // Check for POST action parameter first (for walk-in appointments and cancellations)
            if (isset($_POST['action'])) {
                $action = $_POST['action'];
            }
            // Check if this is a booking request
            elseif (isset($_POST['appointment_date']) && isset($_POST['appointment_time'])) {
                $action = 'book';
            }
            // Check if this is a cancel request (from JSON body)
            else {
                $input = json_decode(file_get_contents('php://input'), true);
                if (isset($input['action']) && $input['action'] === 'cancel') {
                    $action = 'cancel';
                }
            }
        }
        
        $controller = new AppointmentController();
        $response = array();
        
        switch ($action) {
            case 'getPatientAppointments':
                $response = $controller->getPatientAppointments();
                break;
            case 'getAppointments':
                $response = $controller->getAppointments();
                break;
            case 'getWalkInAppointments':
                $response = $controller->getWalkInAppointments();
                break;
            case 'getPendingAppointments':
                $response = $controller->getPendingAppointments();
                break;
            case 'getPendingWalkInAppointments':
                $response = $controller->getPendingWalkInAppointments();
                break;
            case 'getAllAppointments':
                $response = $controller->getAllAppointments();
                break;
            case 'getStats':
                $response = $controller->getStats();
                break;
            case 'getNewAppointments':
                $response = $controller->getNewAppointments();
                break;
            case 'updateStatus':
                $response = $controller->updateStatus();
                break;
            case 'updateAppointment':
                $response = $controller->updateAppointment();
                break;
            case 'getDetails':
                $response = $controller->getDetails();
                break;
            case 'getPatientUpdates':
                $response = $controller->getPatientUpdates();
                break;
            case 'getAppointmentHistory':
                $response = $controller->getAppointmentHistory();
                break;
            case 'testSchema':
                $response = $controller->testDatabaseSchema();
                break;
            case 'getPatientJourney':
                $response = $controller->getPatientJourney();
                break;
            case 'getBlockedTimes':
                $response = $controller->getBlockedTimes();
                break;
            case 'debug':
                // Debug endpoint to check session and database
                $response = array(
                    'success' => true,
                    'debug_info' => array(
                        'isLoggedIn' => isLoggedIn(),
                        'sessionUserId' => getSessionUserId(),
                        'sessionBranchId' => getSessionBranchId(),
                        'sessionRole' => getSessionRole(),
                        'sessionData' => $_SESSION
                    )
                );
                break;
            case 'book':
                $response = $controller->book();
                break;
            case 'createWalkInAppointment':
                $response = $controller->createWalkInAppointment();
                break;
            case 'cancel':
                $response = $controller->cancel();
                break;
            default:
                http_response_code(400);
                $response = array('success' => false, 'message' => 'Invalid action');
        }
        
        // Clean any previous output and send response
        ob_clean();
        echo json_encode($response);
        ob_end_flush();
        
    } catch (Exception $e) {
        // Clean any unwanted output before sending error response
        ob_clean();
        error_log("AppointmentController Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(array(
            'success' => false, 
            'message' => 'Server error occurred while processing request'
        ));
        ob_end_flush();
    }
    
    exit;
}
?>