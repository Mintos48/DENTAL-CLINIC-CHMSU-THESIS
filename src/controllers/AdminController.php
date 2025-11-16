<?php
/**
 * Admin Controller - System Administration Functions
 * Production Version with Comprehensive Logging
 */

// Suppress warnings for clean JSON output
error_reporting(E_ERROR | E_PARSE);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../helpers/ActivityLogger.php';

class AdminController {
    private $db;
    private $userId;
    
    public function __construct() {
        $this->db = Database::getConnection();
        $this->userId = getSessionUserId();
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
            $sql = "SELECT b.id, b.name, b.code, b.location, b.phone, b.email, b.operating_hours, b.status, 
                           COUNT(DISTINCT u.id) as user_count,
                           COUNT(CASE WHEN a.status = 'pending' THEN 1 END) as pending_count,
                           COUNT(a.id) as total_appointments
                    FROM branches b 
                    LEFT JOIN users u ON b.id = u.branch_id 
                    LEFT JOIN appointments a ON b.id = a.branch_id 
                    GROUP BY b.id, b.name, b.code, b.location, b.phone, b.email, b.operating_hours, b.status 
                    ORDER BY b.name";
            
            $result = $this->db->query($sql);
            
            $branches = array();
            while ($row = $result->fetch_assoc()) {
                $branches[] = array(
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'code' => $row['code'] ?? '',
                    'location' => $row['location'] ?? 'N/A',
                    'phone' => $row['phone'] ?? 'N/A',
                    'email' => $row['email'] ?? 'N/A',
                    'operating_hours' => $row['operating_hours'] ?? 'N/A',
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
    public function getSystemLogs($limit = 50, $action_filter = null, $date_filter = null) {
        try {
            $limit = intval($limit);
            if ($limit <= 0) $limit = 50;
            if ($limit > 1000) $limit = 1000; // Max limit
            
            $where_conditions = array();
            $params = array();
            $param_types = '';
            
            // Build the base query - use LEFT JOIN to get logs even if user doesn't exist
            $sql = "SELECT l.id, l.user_id, l.action, l.description, l.ip_address, l.created_at,
                           COALESCE(u.name, 'System') as name, 
                           COALESCE(u.email, 'system@internal') as email
                    FROM system_logs l 
                    LEFT JOIN users u ON l.user_id = u.id";
            
            // Add action filter if provided
            if (!empty($action_filter)) {
                $where_conditions[] = "l.action = ?";
                $params[] = $action_filter;
                $param_types .= 's';
            }
            
            // Add date filter if provided
            if (!empty($date_filter)) {
                $where_conditions[] = "DATE(l.created_at) = ?";
                $params[] = $date_filter;
                $param_types .= 's';
            }
            
            // Add WHERE clause if we have conditions
            if (!empty($where_conditions)) {
                $sql .= " WHERE " . implode(' AND ', $where_conditions);
            }
            
            $sql .= " ORDER BY l.created_at DESC LIMIT ?";
            $params[] = $limit;
            $param_types .= 'i';
            
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                error_log("Prepare statement failed: " . $this->db->error);
                return array('success' => false, 'message' => 'Database error preparing query');
            }
            
            if (!empty($params)) {
                $stmt->bind_param($param_types, ...$params);
            }
            
            if (!$stmt->execute()) {
                error_log("Execute statement failed: " . $stmt->error);
                return array('success' => false, 'message' => 'Database error executing query');
            }
            
            $result = $stmt->get_result();
            
            $logs = array();
            while ($row = $result->fetch_assoc()) {
                $logs[] = array(
                    'id' => $row['id'],
                    'user_id' => $row['user_id'],
                    'user_name' => $row['name'],
                    'user_email' => $row['email'],
                    'action' => $row['action'],
                    'description' => $row['description'],
                    'ip_address' => $row['ip_address'],
                    'created_at' => $row['created_at']
                );
            }
            
            error_log("Retrieved " . count($logs) . " system logs");
            
            return array('success' => true, 'logs' => $logs, 'count' => count($logs));
            
        } catch (Exception $e) {
            error_log("Get system logs error: " . $e->getMessage());
            return array('success' => false, 'message' => 'Failed to load system logs: ' . $e->getMessage());
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
            
            // Base SQL with actual branches table and prescription check
            $sql = "SELECT a.*, 
                           u.name as patient_name, u.email as patient_email,
                           b.name as branch_name,
                           p.id as prescription_id,
                           CASE WHEN p.id IS NOT NULL THEN 1 ELSE 0 END as has_prescription
                    FROM appointments a 
                    LEFT JOIN users u ON a.patient_id = u.id
                    LEFT JOIN branches b ON a.branch_id = b.id
                    LEFT JOIN prescriptions p ON a.id = p.appointment_id";
            
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
                    'created_at' => $row['created_at'],
                    'has_prescription' => (int)$row['has_prescription'],
                    'prescription_id' => $row['prescription_id'] ?? null
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
            
            // New users this month
            $sql = "SELECT COUNT(*) as count FROM users WHERE YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())";
            $result = $this->db->query($sql);
            $analytics['new_users_month'] = $result->fetch_assoc()['count'];
            
            // Active users (logged in within last 30 days)
            $sql = "SELECT COUNT(*) as count FROM users WHERE last_login_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
            $result = $this->db->query($sql);
            $analytics['active_users'] = $result ? $result->fetch_assoc()['count'] : 0;
            
            // Patient growth (comparing last month to previous month)
            $sql = "SELECT 
                        (SELECT COUNT(*) FROM users WHERE role = 'patient' AND YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())) as current_month,
                        (SELECT COUNT(*) FROM users WHERE role = 'patient' AND YEAR(created_at) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND MONTH(created_at) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))) as previous_month";
            $result = $this->db->query($sql);
            $growth_data = $result->fetch_assoc();
            $analytics['patient_growth'] = $growth_data['previous_month'] > 0 ? 
                round((($growth_data['current_month'] - $growth_data['previous_month']) / $growth_data['previous_month']) * 100) : 0;
            
            // Average wait time (simplified calculation - days between booking and appointment)
            $sql = "SELECT AVG(DATEDIFF(appointment_date, created_at)) as avg_days 
                    FROM appointments 
                    WHERE appointment_date >= CURDATE() - INTERVAL 30 DAY";
            $result = $this->db->query($sql);
            $wait_data = $result->fetch_assoc();
            $analytics['avg_wait_time'] = $wait_data['avg_days'] ? round($wait_data['avg_days'], 1) . ' days' : '0 days';
            
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
                    $sql = "SELECT a.id, 
                                   a.appointment_date, 
                                   a.appointment_time, 
                                   a.status,
                                   u.name as patient_name, 
                                   u.email as patient_email,
                                   b.name as branch_name
                            FROM appointments a 
                            LEFT JOIN users u ON a.patient_id = u.id 
                            LEFT JOIN branches b ON a.branch_id = b.id
                            WHERE DATE(a.appointment_date) BETWEEN ? AND ?
                            ORDER BY a.appointment_date, a.appointment_time";
                    break;
                    
                case 'users':
                    $sql = "SELECT u.id, 
                                   u.name,
                                   u.email, 
                                   u.role, 
                                   DATE(u.created_at) as created_date,
                                   b.name as branch_name
                            FROM users u 
                            LEFT JOIN branches b ON u.branch_id = b.id 
                            WHERE DATE(u.created_at) BETWEEN ? AND ?
                            ORDER BY u.created_at DESC";
                    break;
                    
                case 'branches':
                    $sql = "SELECT b.id, 
                                   b.name, 
                                   b.location,
                                   b.address, 
                                   b.phone, 
                                   b.email,
                                   COUNT(DISTINCT u.id) as user_count,
                                   COUNT(a.id) as appointment_count,
                                   COUNT(CASE WHEN a.status = 'completed' THEN 1 END) as completed_count
                            FROM branches b 
                            LEFT JOIN users u ON b.id = u.branch_id 
                            LEFT JOIN appointments a ON b.id = a.branch_id 
                                AND DATE(a.appointment_date) BETWEEN ? AND ?
                            GROUP BY b.id, b.name, b.location, b.address, b.phone, b.email
                            ORDER BY appointment_count DESC";
                    break;
                    
                case 'system':
                    $sql = "SELECT DATE(created_at) as log_date,
                                   action,
                                   category,
                                   level,
                                   COUNT(*) as action_count
                            FROM system_logs 
                            WHERE DATE(created_at) BETWEEN ? AND ?
                            GROUP BY DATE(created_at), action, category, level
                            ORDER BY log_date DESC, action_count DESC";
                    break;
                    
                default:
                    return array('success' => false, 'message' => 'Invalid report type');
            }
            
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                error_log("Generate report SQL error: " . $this->db->error);
                return array('success' => false, 'message' => 'Database error: ' . $this->db->error);
            }
            
            $stmt->bind_param("ss", $start_date, $end_date);
            
            if (!$stmt->execute()) {
                error_log("Generate report execute error: " . $stmt->error);
                return array('success' => false, 'message' => 'Execute error: ' . $stmt->error);
            }
            
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
            return array('success' => false, 'message' => 'Failed to generate report: ' . $e->getMessage());
        }
    }
    
    /**
     * Export report as PDF
     */
    public function exportPDF($report_type, $start_date, $end_date) {
        try {
            // First generate the report data
            $result = $this->generateReport($report_type, $start_date, $end_date);
            
            if (!$result['success']) {
                // Send error as HTML since we can't return JSON in download context
                header('Content-Type: text/html; charset=utf-8');
                echo '<html><body>';
                echo '<h2>Error Generating Report</h2>';
                echo '<p>' . htmlspecialchars($result['message']) . '</p>';
                echo '<p><a href="javascript:history.back()">Go Back</a></p>';
                echo '</body></html>';
                exit;
            }
            
            $report = $result['report'];
            
            // Check if we have data
            if (empty($report['data'])) {
                header('Content-Type: text/html; charset=utf-8');
                echo '<html><body>';
                echo '<h2>No Data Found</h2>';
                echo '<p>No records found for the selected date range.</p>';
                echo '<p><a href="javascript:history.back()">Go Back</a></p>';
                echo '</body></html>';
                exit;
            }
            
            // Load TCPDF library
            if (!class_exists('TCPDF')) {
                require_once __DIR__ . '/../../vendor/tecnickcom/tcpdf/tcpdf.php';
            }
            
            // Create new PDF document with landscape orientation for better table display
            $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
            
            // Set document information
            $pdf->SetCreator('Dental Clinic Management System - CHMSU');
            $pdf->SetAuthor('System Administrator');
            $pdf->SetTitle(ucfirst(str_replace('_', ' ', $report_type)) . ' Report');
            $pdf->SetSubject('Dental Clinic Analytics Report');
            $pdf->SetKeywords('dental, clinic, report, analytics, ' . $report_type);
            
            // Set custom header and footer
            $pdf->setPrintHeader(true);
            $pdf->setPrintFooter(true);
            
            // Set header data - logo, title, string
            $pdf->SetHeaderData('', 0, 'DENTAL CLINIC MANAGEMENT SYSTEM', 'Carlos Hilado Memorial State University', array(5, 74, 145), array(0, 64, 128));
            $pdf->setHeaderFont(Array('helvetica', '', 10));
            $pdf->setFooterFont(Array('helvetica', '', 8));
            
            // Set footer data
            $pdf->setFooterData(array(0, 64, 0), array(0, 64, 128));
            
            // Set margins
            $pdf->SetMargins(15, 25, 15);
            $pdf->SetHeaderMargin(5);
            $pdf->SetFooterMargin(10);
            $pdf->SetAutoPageBreak(TRUE, 15);
            
            // Add a page
            $pdf->AddPage();
            
            // Professional header section with background
            $pdf->SetFillColor(5, 74, 145); // Primary brand color
            $pdf->Rect(15, 25, 267, 25, 'F');
            
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('helvetica', 'B', 20);
            $pdf->SetXY(15, 30);
            $pdf->Cell(267, 10, strtoupper(str_replace('_', ' ', $report_type)) . ' REPORT', 0, 1, 'C');
            
            $pdf->SetFont('helvetica', '', 11);
            $pdf->SetXY(15, 40);
            $pdf->Cell(267, 8, 'Comprehensive Analytics & Performance Report', 0, 1, 'C');
            
            // Reset text color
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Ln(8);
            
            // Report metadata section with professional styling
            $pdf->SetFillColor(240, 248, 255);
            $pdf->Rect(15, $pdf->GetY(), 267, 22, 'F');
            
            $pdf->SetFont('helvetica', 'B', 10);
            $y_pos = $pdf->GetY() + 3;
            
            // Left column
            $pdf->SetXY(20, $y_pos);
            $pdf->Cell(40, 6, '📅 Report Period:', 0, 0, 'L');
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(80, 6, date('M d, Y', strtotime($start_date)) . ' - ' . date('M d, Y', strtotime($end_date)), 0, 1, 'L');
            
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->SetXY(20, $y_pos + 7);
            $pdf->Cell(40, 6, '🕐 Generated On:', 0, 0, 'L');
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(80, 6, date('F d, Y - h:i A'), 0, 1, 'L');
            
            // Right column
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->SetXY(150, $y_pos);
            $pdf->Cell(40, 6, '📊 Total Records:', 0, 0, 'L');
            $pdf->SetFont('helvetica', '', 10);
            $pdf->SetTextColor(5, 74, 145);
            $pdf->Cell(80, 6, number_format($report['total_records']), 0, 1, 'L');
            
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetXY(150, $y_pos + 7);
            $pdf->Cell(40, 6, '👤 Generated By:', 0, 0, 'L');
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(80, 6, 'System Administrator', 0, 1, 'L');
            
            $pdf->Ln(10);
            
            // Table section
            if (!empty($report['data'])) {
                // Professional table styling
                $pdf->SetFont('helvetica', 'B', 9);
                
                // Get headers from first row
                $headers = array_keys($report['data'][0]);
                $num_cols = count($headers);
                $col_width = 255 / $num_cols; // Adjusted for landscape
                
                // Table header with gradient effect
                $pdf->SetFillColor(5, 74, 145);
                $pdf->SetTextColor(255, 255, 255);
                $pdf->SetDrawColor(5, 74, 145);
                $pdf->SetLineWidth(0.3);
                
                foreach ($headers as $header) {
                    $header_text = strtoupper(str_replace('_', ' ', $header));
                    $pdf->Cell($col_width, 9, $header_text, 1, 0, 'C', true);
                }
                $pdf->Ln();
                
                // Table data with alternating rows
                $pdf->SetFont('helvetica', '', 8);
                $pdf->SetDrawColor(200, 200, 200);
                $pdf->SetLineWidth(0.1);
                
                $row_count = 0;
                foreach ($report['data'] as $row) {
                    // Alternating row colors
                    if ($row_count % 2 == 0) {
                        $pdf->SetFillColor(255, 255, 255);
                    } else {
                        $pdf->SetFillColor(248, 250, 252);
                    }
                    $pdf->SetTextColor(0, 0, 0);
                    
                    foreach ($row as $value) {
                        // Sanitize and format value for PDF
                        $val = $value ?? '-';
                        if (is_string($val)) {
                            $val = mb_substr($val, 0, 45); // Limit length
                        }
                        $pdf->Cell($col_width, 7, $val, 1, 0, 'L', true);
                    }
                    $pdf->Ln();
                    $row_count++;
                }
                
                // Summary footer
                $pdf->Ln(5);
                $pdf->SetFont('helvetica', 'I', 8);
                $pdf->SetTextColor(100, 100, 100);
                $pdf->Cell(0, 6, 'End of Report - ' . $row_count . ' records displayed', 0, 1, 'C');
            }
            
            // Clean output buffer before sending PDF
            if (ob_get_level()) {
                ob_end_clean();
            }
            
            // Output PDF
            $filename = $report_type . '_report_' . date('Ymd_His') . '.pdf';
            $pdf->Output($filename, 'D'); // D = download
            exit;
            
        } catch (Exception $e) {
            error_log("Export PDF error: " . $e->getMessage());
            header('Content-Type: text/html; charset=utf-8');
            echo '<html><body>';
            echo '<h2>Error Exporting PDF</h2>';
            echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<p><a href="javascript:history.back()">Go Back</a></p>';
            echo '</body></html>';
            exit;
        }
    }
    
    /**
     * Export report as Excel (CSV format)
     */
    public function exportExcel($report_type, $start_date, $end_date) {
        try {
            // First generate the report data
            $result = $this->generateReport($report_type, $start_date, $end_date);
            
            if (!$result['success']) {
                // Send error as HTML since we can't return JSON in download context
                header('Content-Type: text/html; charset=utf-8');
                echo '<html><body>';
                echo '<h2>Error Generating Report</h2>';
                echo '<p>' . htmlspecialchars($result['message']) . '</p>';
                echo '<p><a href="javascript:history.back()">Go Back</a></p>';
                echo '</body></html>';
                exit;
            }
            
            $report = $result['report'];
            
            // Check if we have data
            if (empty($report['data'])) {
                header('Content-Type: text/html; charset=utf-8');
                echo '<html><body>';
                echo '<h2>No Data Found</h2>';
                echo '<p>No records found for the selected date range.</p>';
                echo '<p><a href="javascript:history.back()">Go Back</a></p>';
                echo '</body></html>';
                exit;
            }
            
            // Clean output buffer before sending CSV
            if (ob_get_level()) {
                ob_end_clean();
            }
            
            // Set headers for Excel-compatible CSV download with UTF-8 BOM
            $filename = ucfirst(str_replace('_', ' ', $report_type)) . '_Report_' . date('Ymd_His') . '.csv';
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: no-cache');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            
            // Create output stream
            $output = fopen('php://output', 'w');
            
            // Add UTF-8 BOM for proper Excel encoding
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Professional report header section
            fputcsv($output, array('═══════════════════════════════════════════════════════════════════════'));
            fputcsv($output, array('DENTAL CLINIC MANAGEMENT SYSTEM - CARLOS HILADO MEMORIAL STATE UNIVERSITY'));
            fputcsv($output, array('═══════════════════════════════════════════════════════════════════════'));
            fputcsv($output, array('')); // Empty row
            
            // Report title and metadata
            fputcsv($output, array('REPORT TYPE:', strtoupper(str_replace('_', ' ', $report_type)) . ' REPORT'));
            fputcsv($output, array('')); // Empty row
            
            // Report details section
            fputcsv($output, array('───────────────────────────────────────────────────────────────────────'));
            fputcsv($output, array('REPORT DETAILS'));
            fputcsv($output, array('───────────────────────────────────────────────────────────────────────'));
            fputcsv($output, array('Report Period:', date('F d, Y', strtotime($start_date)) . ' - ' . date('F d, Y', strtotime($end_date))));
            fputcsv($output, array('Generated On:', date('F d, Y - h:i A')));
            fputcsv($output, array('Generated By:', 'System Administrator'));
            fputcsv($output, array('Total Records:', number_format($report['total_records'])));
            fputcsv($output, array('Report Status:', 'Complete'));
            fputcsv($output, array('')); // Empty row
            
            // Data section
            if (!empty($report['data'])) {
                fputcsv($output, array('═══════════════════════════════════════════════════════════════════════'));
                fputcsv($output, array('REPORT DATA'));
                fputcsv($output, array('═══════════════════════════════════════════════════════════════════════'));
                fputcsv($output, array('')); // Empty row
                
                // Column headers - formatted professionally
                $headers = array_keys($report['data'][0]);
                $formatted_headers = array_map(function($h) {
                    return strtoupper(str_replace('_', ' ', $h));
                }, $headers);
                fputcsv($output, $formatted_headers);
                
                // Separator row
                $separator = array_fill(0, count($headers), '---');
                fputcsv($output, $separator);
                
                // Data rows with formatting
                foreach ($report['data'] as $row) {
                    $formatted_row = array_map(function($value) {
                        // Format empty values
                        if ($value === null || $value === '') {
                            return '—'; // Em dash for empty values
                        }
                        // Format dates if detected
                        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) {
                            return date('M d, Y', strtotime($value));
                        }
                        // Format numbers if needed
                        if (is_numeric($value) && strpos($value, '.') !== false) {
                            return number_format((float)$value, 2);
                        }
                        return $value;
                    }, $row);
                    fputcsv($output, $formatted_row);
                }
                
                // Footer section
                fputcsv($output, array('')); // Empty row
                fputcsv($output, array('───────────────────────────────────────────────────────────────────────'));
                fputcsv($output, array('END OF REPORT'));
                fputcsv($output, array('Total Records Displayed:', count($report['data'])));
                fputcsv($output, array('───────────────────────────────────────────────────────────────────────'));
                fputcsv($output, array('')); // Empty row
                fputcsv($output, array('© ' . date('Y') . ' Dental Clinic Management System. All rights reserved.'));
                fputcsv($output, array('Generated by CHMSU Dental Clinic Management System'));
            }
            
            fclose($output);
            exit;
            
        } catch (Exception $e) {
            error_log("Export Excel error: " . $e->getMessage());
            header('Content-Type: text/html; charset=utf-8');
            echo '<html><body>';
            echo '<h2>Error Exporting Excel</h2>';
            echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<p><a href="javascript:history.back()">Go Back</a></p>';
            echo '</body></html>';
            exit;
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
            $input = json_decode(file_get_contents('php://input'), true);
            $first_name = $input['first_name'] ?? '';
            $last_name = $input['last_name'] ?? '';
            $email = $input['email'] ?? '';
            $phone = $input['phone'] ?? '';
            $password = $input['password'] ?? '';
            $role = $input['role'] ?? '';
            $branch_id = $input['branch_id'] ?? '';
            
            // Combine first and last name
            $full_name = trim($first_name . ' ' . $last_name);
            
            // Validation
            if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($role)) {
                return array('success' => false, 'message' => 'All required fields must be filled');
            }
            
            if (!in_array($role, ['admin', 'staff', 'patient', 'dentist'])) {
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
            
            // Set default values for admin-created users
            $status = 'active'; // Admin-created users are active by default
            $email_verified = 1; // Admin-created users are pre-verified
            
            // Insert user with all required fields
            $sql = "INSERT INTO users (name, email, phone, password, role, branch_id, status, email_verified) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("sssssisi", $full_name, $email, $phone, $hashed_password, $role, $branch_id, $status, $email_verified);
            
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
            $sql = "UPDATE users SET first_name = ?, last_name = ?, email = ?, role = ?, branch_id = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("ssssii", $first_name, $last_name, $email, $role, $branch_id, $user_id);
            
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
     * Add new branch
     */
    public function addBranch() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return array('success' => false, 'message' => 'Invalid request method');
        }
        
        try {
            // Get JSON input
            $input = json_decode(file_get_contents('php://input'), true);
            $name = $input['name'] ?? '';
            $code = $input['code'] ?? '';
            $location = $input['location'] ?? '';
            $phone = $input['phone'] ?? '';
            $email = $input['email'] ?? '';
            $operating_hours = $input['operating_hours'] ?? '';
            
            // Validation
            if (empty($name) || empty($location)) {
                return array('success' => false, 'message' => 'Branch name and location are required');
            }
            
            // Check if branch code already exists (if provided)
            if (!empty($code)) {
                $stmt = $this->db->prepare("SELECT id FROM branches WHERE code = ?");
                $stmt->bind_param("s", $code);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    return array('success' => false, 'message' => 'Branch code already exists');
                }
            }
            
            // Check if branch name already exists
            $stmt = $this->db->prepare("SELECT id FROM branches WHERE name = ?");
            $stmt->bind_param("s", $name);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                return array('success' => false, 'message' => 'Branch name already exists');
            }
            
            // Insert branch
            $sql = "INSERT INTO branches (name, code, location, phone, email, operating_hours, status) 
                    VALUES (?, ?, ?, ?, ?, ?, 'active')";
            $stmt = $this->db->prepare($sql);
            
            $stmt->bind_param("ssssss", $name, $code, $location, $phone, $email, $operating_hours);
            
            if ($stmt->execute()) {
                $branch_id = $this->db->insert_id;
                $admin_id = getSessionUserId();
                $this->logActivity($admin_id, 'branch_created', "Created new branch: $name (Code: $code)");
                
                return array(
                    'success' => true, 
                    'message' => 'Branch created successfully', 
                    'branch_id' => $branch_id
                );
            } else {
                return array('success' => false, 'message' => 'Failed to create branch');
            }
            
        } catch (Exception $e) {
            error_log("Add branch error: " . $e->getMessage());
            return array('success' => false, 'message' => 'Failed to create branch: ' . $e->getMessage());
        }
    }
    
    /**
     * Update branch
     */
    public function updateBranch() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return array('success' => false, 'message' => 'Invalid request method');
        }
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $branch_id = $input['branch_id'] ?? '';
            $name = $input['name'] ?? '';
            $code = $input['code'] ?? '';
            $location = $input['location'] ?? '';
            $phone = $input['phone'] ?? '';
            $email = $input['email'] ?? '';
            $operating_hours = $input['operating_hours'] ?? '';
            $status = $input['status'] ?? 'active';
            
            if (empty($branch_id) || empty($name) || empty($location)) {
                return array('success' => false, 'message' => 'Branch ID, name, and location are required');
            }
            
            // Update branch
            $sql = "UPDATE branches 
                    SET name = ?, code = ?, location = ?, phone = ?, email = ?, operating_hours = ?, status = ? 
                    WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("sssssssi", $name, $code, $location, $phone, $email, $operating_hours, $status, $branch_id);
            
            if ($stmt->execute()) {
                $admin_id = getSessionUserId();
                $this->logActivity($admin_id, 'branch_updated', "Updated branch: $name (ID: $branch_id)");
                
                return array('success' => true, 'message' => 'Branch updated successfully');
            } else {
                return array('success' => false, 'message' => 'Failed to update branch');
            }
            
        } catch (Exception $e) {
            error_log("Update branch error: " . $e->getMessage());
            return array('success' => false, 'message' => 'Failed to update branch');
        }
    }
    
    /**
     * Delete branch
     */
    public function deleteBranch() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return array('success' => false, 'message' => 'Invalid request method');
        }
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $branch_id = $input['branch_id'] ?? '';
            
            if (empty($branch_id)) {
                return array('success' => false, 'message' => 'Branch ID is required');
            }
            
            // Check if branch has associated users
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM users WHERE branch_id = ?");
            $stmt->bind_param("i", $branch_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            
            if ($result['count'] > 0) {
                return array('success' => false, 'message' => 'Cannot delete branch with associated users');
            }
            
            // Get branch info before deletion
            $stmt = $this->db->prepare("SELECT name, code FROM branches WHERE id = ?");
            $stmt->bind_param("i", $branch_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $branch = $result->fetch_assoc();
            
            if (!$branch) {
                return array('success' => false, 'message' => 'Branch not found');
            }
            
            // Delete branch
            $stmt = $this->db->prepare("DELETE FROM branches WHERE id = ?");
            $stmt->bind_param("i", $branch_id);
            
            if ($stmt->execute()) {
                $admin_id = getSessionUserId();
                $this->logActivity($admin_id, 'branch_deleted', "Deleted branch: {$branch['name']} ({$branch['code']})");
                
                return array('success' => true, 'message' => 'Branch deleted successfully');
            } else {
                return array('success' => false, 'message' => 'Failed to delete branch');
            }
            
        } catch (Exception $e) {
            error_log("Delete branch error: " . $e->getMessage());
            return array('success' => false, 'message' => 'Failed to delete branch');
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
     * Mark appointment as completed (admin action)
     */
    public function markAppointmentCompleted() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return array('success' => false, 'message' => 'Invalid request method');
        }
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $appointment_id = $input['appointment_id'] ?? '';
            
            if (empty($appointment_id)) {
                return array('success' => false, 'message' => 'Appointment ID is required');
            }
            
            // Check if appointment exists
            $checkStmt = $this->db->prepare("SELECT id, status, patient_id, appointment_date, appointment_time FROM appointments WHERE id = ?");
            $checkStmt->bind_param("i", $appointment_id);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            
            if ($result->num_rows === 0) {
                return array('success' => false, 'message' => 'Appointment not found');
            }
            
            $appointment = $result->fetch_assoc();
            
            // Update appointment status to completed
            $stmt = $this->db->prepare("UPDATE appointments SET status = 'completed', updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("i", $appointment_id);
            
            if ($stmt->execute()) {
                $admin_id = getSessionUserId();
                $this->logActivity(
                    $admin_id, 
                    'appointment_completed', 
                    "Marked appointment ID: $appointment_id as completed for patient ID: {$appointment['patient_id']}"
                );
                
                return array(
                    'success' => true, 
                    'message' => 'Appointment marked as completed',
                    'appointment_id' => $appointment_id,
                    'new_status' => 'completed'
                );
            } else {
                return array('success' => false, 'message' => 'Failed to update appointment status');
            }
            
        } catch (Exception $e) {
            error_log("Mark appointment completed error: " . $e->getMessage());
            return array('success' => false, 'message' => 'Failed to mark appointment as completed: ' . $e->getMessage());
        }
    }

    /**
     * Save system settings
     */
    public function saveSettings() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return array('success' => false, 'message' => 'Invalid request method');
        }
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $settings = $input['settings'] ?? array();
            
            if (empty($settings)) {
                return array('success' => false, 'message' => 'No settings provided');
            }
            
            $this->db->begin_transaction();
            
            foreach ($settings as $key => $value) {
                $stmt = $this->db->prepare(
                    "INSERT INTO system_settings (setting_key, setting_value) 
                     VALUES (?, ?) 
                     ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()"
                );
                $stmt->bind_param("sss", $key, $value, $value);
                $stmt->execute();
            }
            
            $this->db->commit();
            
            $admin_id = getSessionUserId();
            $this->logActivity($admin_id, 'settings_updated', 'Updated system settings: ' . implode(', ', array_keys($settings)));
            
            return array('success' => true, 'message' => 'Settings saved successfully');
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Save settings error: " . $e->getMessage());
            return array('success' => false, 'message' => 'Failed to save settings');
        }
    }
    
    /**
     * Get system settings
     */
    public function getSettings() {
        try {
            $sql = "SELECT setting_key, setting_value FROM system_settings";
            $result = $this->db->query($sql);
            
            $settings = array();
            while ($row = $result->fetch_assoc()) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
            
            return array('success' => true, 'settings' => $settings);
            
        } catch (Exception $e) {
            error_log("Get settings error: " . $e->getMessage());
            return array('success' => false, 'message' => 'Failed to load settings');
        }
    }
    
    /**
     * Backup database
     */
    public function backupDatabase() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return array('success' => false, 'message' => 'Invalid request method');
        }
        
        try {
            $backup_dir = dirname(__DIR__, 2) . '/backups';
            if (!is_dir($backup_dir)) {
                if (!mkdir($backup_dir, 0755, true)) {
                    return array('success' => false, 'message' => 'Failed to create backup directory');
                }
            }
            
            $backup_file = $backup_dir . '/backup_' . date('Y-m-d_H-i-s') . '.sql';
            
            // Get all tables
            $tables = array();
            $result = $this->db->query("SHOW TABLES");
            while ($row = $result->fetch_array()) {
                $tables[] = $row[0];
            }
            
            $sql_dump = "-- Database Backup\n";
            $sql_dump .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
            $sql_dump .= "-- Database: " . DB_NAME . "\n\n";
            
            // Loop through tables
            foreach ($tables as $table) {
                // Get table structure
                $sql_dump .= "\n-- Table structure for table `$table`\n";
                $sql_dump .= "DROP TABLE IF EXISTS `$table`;\n";
                
                $create_table = $this->db->query("SHOW CREATE TABLE `$table`");
                $row = $create_table->fetch_assoc();
                $sql_dump .= $row['Create Table'] . ";\n\n";
                
                // Get table data
                $sql_dump .= "-- Dumping data for table `$table`\n";
                $rows = $this->db->query("SELECT * FROM `$table`");
                
                if ($rows && $rows->num_rows > 0) {
                    while ($row = $rows->fetch_assoc()) {
                        $sql_dump .= "INSERT INTO `$table` VALUES(";
                        $values = array();
                        foreach ($row as $value) {
                            if (is_null($value)) {
                                $values[] = 'NULL';
                            } else {
                                $escaped_value = $this->db->real_escape_string($value);
                                $values[] = "'" . $escaped_value . "'";
                            }
                        }
                        $sql_dump .= implode(',', $values) . ");\n";
                    }
                }
                $sql_dump .= "\n";
            }
            
            // Write to file
            if (file_put_contents($backup_file, $sql_dump) !== false) {
                $admin_id = getSessionUserId();
                $this->logActivity($admin_id, 'database_backup', 'Created database backup: ' . basename($backup_file));
                
                return array(
                    'success' => true, 
                    'message' => 'Database backup created successfully',
                    'backup_file' => basename($backup_file),
                    'file_size' => number_format(filesize($backup_file) / 1024, 2) . ' KB',
                    'tables_backed_up' => count($tables)
                );
            } else {
                return array('success' => false, 'message' => 'Failed to write backup file');
            }
            
        } catch (Exception $e) {
            error_log("Backup database error: " . $e->getMessage());
            return array('success' => false, 'message' => 'Failed to create backup: ' . $e->getMessage());
        }
    }
    
    /**
     * Get user activity summary
     */
    public function getUserActivitySummary($user_id) {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_actions,
                        COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 END) as last_24h,
                        COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as last_7days,
                        MAX(created_at) as last_activity
                    FROM system_logs 
                    WHERE user_id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            return array('success' => true, 'activity' => $result->fetch_assoc());
            
        } catch (Exception $e) {
            error_log("Get user activity summary error: " . $e->getMessage());
            return array('success' => false, 'message' => 'Failed to load activity summary');
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
            $action_filter = isset($_GET['log_action']) ? $_GET['log_action'] : null;
            $date_filter = isset($_GET['log_date']) ? $_GET['log_date'] : null;
            $response = $controller->getSystemLogs($limit, $action_filter, $date_filter);
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
        case 'exportPDF':
            $report_type = isset($_GET['report_type']) ? $_GET['report_type'] : '';
            $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
            $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
            
            if (empty($report_type) || empty($start_date) || empty($end_date)) {
                echo json_encode(array('success' => false, 'message' => 'Missing required parameters'));
            } else {
                $controller->exportPDF($report_type, $start_date, $end_date);
            }
            exit; // Important: exit after export to prevent JSON output
        case 'exportExcel':
            $report_type = isset($_GET['report_type']) ? $_GET['report_type'] : '';
            $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
            $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
            
            if (empty($report_type) || empty($start_date) || empty($end_date)) {
                echo json_encode(array('success' => false, 'message' => 'Missing required parameters'));
            } else {
                $controller->exportExcel($report_type, $start_date, $end_date);
            }
            exit; // Important: exit after export to prevent JSON output
        case 'addUser':
            $response = $controller->addUser();
            break;
        case 'updateUser':
            $response = $controller->updateUser();
            break;
        case 'deleteUser':
            $response = $controller->deleteUser();
            break;
        case 'addBranch':
            $response = $controller->addBranch();
            break;
        case 'updateBranch':
            $response = $controller->updateBranch();
            break;
        case 'deleteBranch':
            $response = $controller->deleteBranch();
            break;
        case 'approveAppointment':
            $response = $controller->approveAppointment();
            break;
        case 'markAppointmentCompleted':
            $response = $controller->markAppointmentCompleted();
            break;
        case 'exportUsers':
            $users = $controller->getAllUsers();
            $response = array(
                'success' => true,
                'users' => $users['users'] ?? []
            );
            break;
        case 'saveSettings':
            $response = $controller->saveSettings();
            break;
        case 'getSettings':
            $response = $controller->getSettings();
            break;
        case 'backupDatabase':
            $response = $controller->backupDatabase();
            break;
        case 'getUserActivitySummary':
            $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
            if ($user_id > 0) {
                $response = $controller->getUserActivitySummary($user_id);
            } else {
                $response = array('success' => false, 'message' => 'User ID required');
            }
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