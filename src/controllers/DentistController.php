<?php
/**
 * DentistController - provides lightweight analytics and appointment endpoints for dentist UI
 * This controller intentionally reuses existing DB structures and session helpers.
 */
if (!defined('API_ENDPOINT')) {
    define('API_ENDPOINT', true);
}
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/config/session.php';
define('APPOINTMENT_CONTROLLER_INCLUDED', true); // Prevent AppointmentController's API handler from running
require_once __DIR__ . '/AppointmentController.php';

class DentistController {
    private $db;
    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Return summary analytics for the dentist (branch scoped)
     */
    public function getAnalyticsData() {
        try {
            $role = getSessionRole();
            $branch_id = getSessionBranchId();

            // If admin, optionally allow branch override via GET
            if ($role === ROLE_ADMIN && isset($_GET['branch_id'])) {
                $branch_id = intval($_GET['branch_id']);
            }

            // Default date ranges
            $today = date('Y-m-d');
            $month_start = date('Y-m-01');

            // Revenue for this month (paid invoices)
            $sql = "SELECT IFNULL(SUM(total_amount),0) as revenue_month FROM invoices WHERE branch_id = ? AND status IN ('paid','partial') AND invoice_date BETWEEN ? AND ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('iss', $branch_id, $month_start, $today);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            $revenue_month = floatval($res['revenue_month'] ?? 0);

            // Appointments today counts
            $sql2 = "SELECT status, COUNT(*) as c FROM (
                        SELECT status FROM appointments WHERE branch_id = ? AND appointment_date = ?
                        UNION ALL
                        SELECT status FROM walk_in_appointments WHERE branch_id = ? AND appointment_date = ?
                      ) t GROUP BY status";
            $stmt2 = $this->db->prepare($sql2);
            $stmt2->bind_param('isis', $branch_id, $today, $branch_id, $today);
            $stmt2->execute();
            $r2 = $stmt2->get_result();
            $today_total = 0; $pending = 0; $approved = 0; $completed = 0;
            while ($row = $r2->fetch_assoc()){
                $today_total += intval($row['c']);
                if ($row['status']=='pending') $pending = intval($row['c']);
                if ($row['status']=='approved') $approved = intval($row['c']);
                if ($row['status']=='completed') $completed = intval($row['c']);
            }

            // Next available slots summary (simple): next 3 pending appointments
            $sql3 = "(SELECT id, appointment_date, appointment_time, status, (SELECT name FROM users u WHERE u.id = a.patient_id) as patient_name, (SELECT name FROM treatment_types tt WHERE tt.id = a.treatment_type_id) as treatment_name FROM appointments a WHERE a.branch_id = ? AND a.status = 'approved' AND a.appointment_date >= ? ORDER BY a.appointment_date ASC, a.appointment_time ASC LIMIT 3)
                     UNION ALL
                     (SELECT id, appointment_date, appointment_time, status, patient_name, (SELECT name FROM treatment_types tt WHERE tt.id = w.treatment_type_id) as treatment_name FROM walk_in_appointments w WHERE w.branch_id = ? AND w.status = 'approved' AND w.appointment_date >= ? ORDER BY w.appointment_date ASC, w.appointment_time ASC LIMIT 3)";
            $stmt3 = $this->db->prepare($sql3);
            $stmt3->bind_param('isis', $branch_id, $today, $branch_id, $today);
            // Fallback: if prepare fails (some SQLite/MySQL differences), just return empty
            $next_slots = [];
            if ($stmt3 && $stmt3->execute()) {
                $r3 = $stmt3->get_result();
                while ($row = $r3->fetch_assoc()) {
                    $next_slots[] = $row['appointment_date'] . ' ' . $row['appointment_time'] . ' — ' . ($row['patient_name'] ?? '') . ' (' . ($row['treatment_name'] ?? '') . ')';
                }
            }

            return array('success'=>true,'analytics'=>array(
                'revenue_month'=>$revenue_month,
                'today_total'=>$today_total,
                'pending'=>$pending,
                'approved'=>$approved,
                'completed'=>$completed,
                'next_slots'=>implode('; ', $next_slots)
            ));
        } catch (Exception $e) {
            return array('success'=>false,'message'=>'Failed to load analytics: '.$e->getMessage());
        }
    }

    /**
     * Return pending appointments for dentist to act on
     */
    public function getPendingAppointments() {
        try {
            $role = getSessionRole();
            $branch_id = getSessionBranchId();
            if ($role === ROLE_ADMIN && isset($_GET['branch_id'])) $branch_id = intval($_GET['branch_id']);

            $date = isset($_GET['date']) ? $_GET['date'] : '';

            $params = [];
            $types = '';
            $where = " WHERE (a.status = 'pending') AND a.branch_id = ?";
            $params[] = $branch_id; $types .= 'i';
            if (!empty($date)) { $where .= " AND a.appointment_date = ?"; $params[] = $date; $types .= 's'; }

            // Regular appointments
            $sql = "SELECT a.id, a.appointment_date, a.appointment_time, a.status, u.name as patient_name, tt.name as treatment_name FROM appointments a LEFT JOIN users u ON a.patient_id = u.id LEFT JOIN treatment_types tt ON a.treatment_type_id = tt.id" . $where . " ORDER BY a.appointment_date, a.appointment_time";
            $stmt = $this->db->prepare($sql);
            if ($stmt) {
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $res = $stmt->get_result();
                $appointments = $res->fetch_all(MYSQLI_ASSOC);
            } else {
                $appointments = [];
            }

            // Walk-in pending
            $sql2 = "SELECT w.id, w.appointment_date, w.appointment_time, w.status, w.patient_name, (SELECT name FROM treatment_types tt WHERE tt.id = w.treatment_type_id) as treatment_name FROM walk_in_appointments w WHERE w.status = 'pending' AND w.branch_id = ?";
            $p2 = [$branch_id]; $t2 = 'i';
            if (!empty($date)) { $sql2 .= ' AND w.appointment_date = ?'; $p2[] = $date; $t2 .= 's'; }
            $sql2 .= ' ORDER BY w.appointment_date, w.appointment_time';
            $stmt2 = $this->db->prepare($sql2);
            if ($stmt2) { $stmt2->bind_param($t2, ...$p2); $stmt2->execute(); $r2 = $stmt2->get_result(); $walkins = $r2->fetch_all(MYSQLI_ASSOC); } else { $walkins = []; }

            $all = array_merge($appointments, $walkins);
            return array('success'=>true,'appointments'=>$all);
        } catch (Exception $e) {
            return array('success'=>false,'message'=>'Failed to load pending appointments: '.$e->getMessage());
        }
    }

    /**
     * Enhanced comprehensive analytics for the entire branch
     */
    public function getComprehensiveAnalytics() {
        try {
            $role = getSessionRole();
            $branch_id = getSessionBranchId();

            if ($role === ROLE_ADMIN && isset($_GET['branch_id'])) {
                $branch_id = intval($_GET['branch_id']);
            }

            $today = date('Y-m-d');
            $month_start = date('Y-m-01');
            $analytics = [];

            // Branch Revenue for this month
            $sql = "SELECT IFNULL(SUM(total_amount),0) as revenue FROM invoices WHERE branch_id = ? AND status IN ('paid','partial') AND invoice_date BETWEEN ? AND ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('iss', $branch_id, $month_start, $today);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            $analytics['branch_revenue_month'] = floatval($res['revenue'] ?? 0);

            // Total patients in branch
            $sql = "SELECT COUNT(DISTINCT patient_id) as count FROM appointments WHERE branch_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $branch_id);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            $analytics['total_patients'] = intval($res['count'] ?? 0);

            // Today's appointments breakdown
            $sql = "SELECT status, COUNT(*) as count FROM appointments WHERE branch_id = ? AND appointment_date = ? GROUP BY status";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('is', $branch_id, $today);
            $stmt->execute();
            $result = $stmt->get_result();
            $analytics['today_pending'] = 0;
            $analytics['today_approved'] = 0;
            $analytics['today_completed'] = 0;
            while ($row = $result->fetch_assoc()) {
                $analytics['today_' . $row['status']] = intval($row['count']);
            }

            // Pending approvals count
            $sql = "SELECT COUNT(*) as count FROM appointments WHERE branch_id = ? AND status = 'pending'";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $branch_id);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            $analytics['pending_count'] = intval($res['count'] ?? 0);

            // Prescriptions this month
            $sql = "SELECT COUNT(*) as count FROM prescriptions p 
                    JOIN appointments a ON p.appointment_id = a.id 
                    WHERE a.branch_id = ? AND p.created_at >= ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('is', $branch_id, $month_start);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            $analytics['prescriptions_month'] = intval($res['count'] ?? 0);

            // Staff count
            $sql = "SELECT COUNT(*) as count FROM users WHERE branch_id = ? AND role IN ('staff', 'dentist') AND status = 'active'";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $branch_id);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            $analytics['staff_count'] = intval($res['count'] ?? 0);

            // Recent patients (last 10)
            $sql = "SELECT DISTINCT u.name as patient_name, 
                           MAX(a.appointment_date) as last_visit, 
                           MAX(a.status) as status 
                    FROM appointments a 
                    JOIN users u ON a.patient_id = u.id 
                    WHERE a.branch_id = ? 
                    GROUP BY a.patient_id 
                    ORDER BY last_visit DESC LIMIT 10";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $branch_id);
            $stmt->execute();
            $analytics['recent_patients'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            // Popular treatments
            $sql = "SELECT tt.name as treatment_name, 
                           COUNT(*) as count,
                           IFNULL(SUM(i.total_amount), 0) as revenue
                    FROM appointments a 
                    LEFT JOIN treatment_types tt ON a.treatment_type_id = tt.id
                    LEFT JOIN invoices i ON a.id = i.appointment_id
                    WHERE a.branch_id = ? AND a.appointment_date >= ?
                    GROUP BY a.treatment_type_id 
                    ORDER BY count DESC LIMIT 5";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('is', $branch_id, $month_start);
            $stmt->execute();
            $analytics['popular_treatments'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            // Schedule overview (upcoming appointments)
            $sql = "SELECT a.appointment_date, a.appointment_time, u.name as patient_name, 
                           tt.name as treatment_name, a.status
                    FROM appointments a
                    LEFT JOIN users u ON a.patient_id = u.id
                    LEFT JOIN treatment_types tt ON a.treatment_type_id = tt.id
                    WHERE a.branch_id = ? AND a.appointment_date >= ? AND a.status IN ('pending', 'approved')
                    ORDER BY a.appointment_date ASC, a.appointment_time ASC LIMIT 10";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('is', $branch_id, $today);
            $stmt->execute();
            $analytics['schedule_overview'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            return array('success'=>true, 'analytics'=>$analytics);

        } catch (Exception $e) {
            return array('success'=>false, 'message'=>'Failed to load comprehensive analytics: '.$e->getMessage());
        }
    }

    /**
     * Get all appointments for a specific date (branch-wide)
     */
    public function getDailyAppointments() {
        try {
            $role = getSessionRole();
            $branch_id = getSessionBranchId();
            $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

            if ($role === ROLE_ADMIN && isset($_GET['branch_id'])) {
                $branch_id = intval($_GET['branch_id']);
            }

            // Show all branch appointments regardless of role (dentist acts as branch admin)
            $sql = "SELECT a.id, a.appointment_date, a.appointment_time, a.status, 
                           u.name as patient_name, u.phone as patient_phone,
                           tt.name as treatment_name,
                           s.name as staff_name,
                           (SELECT COUNT(*) FROM prescriptions p WHERE p.appointment_id = a.id) > 0 as has_prescription
                    FROM appointments a
                    LEFT JOIN users u ON a.patient_id = u.id
                    LEFT JOIN treatment_types tt ON a.treatment_type_id = tt.id
                    LEFT JOIN users s ON a.staff_id = s.id
                    WHERE a.branch_id = ? AND a.appointment_date = ?
                    ORDER BY a.appointment_time ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('is', $branch_id, $date);
            $stmt->execute();
            $appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            return array('success'=>true, 'appointments'=>$appointments);

        } catch (Exception $e) {
            return array('success'=>false, 'message'=>'Failed to load daily appointments: '.$e->getMessage());
        }
    }

    /**
     * Get branch's upcoming schedule (all staff appointments)
     */
    public function getMySchedule() {
        try {
            $branch_id = getSessionBranchId();
            $today = date('Y-m-d');

            // Show all branch appointments, not just dentist's personal schedule
            $sql = "SELECT a.appointment_date, a.appointment_time, u.name as patient_name, 
                           tt.name as treatment_name, a.status, s.name as staff_name
                    FROM appointments a
                    LEFT JOIN users u ON a.patient_id = u.id
                    LEFT JOIN treatment_types tt ON a.treatment_type_id = tt.id
                    LEFT JOIN users s ON a.staff_id = s.id
                    WHERE a.branch_id = ? AND a.appointment_date >= ? AND a.status IN ('pending', 'approved')
                    ORDER BY a.appointment_date ASC, a.appointment_time ASC LIMIT 15";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('is', $branch_id, $today);
            $stmt->execute();
            $schedule = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            return array('success'=>true, 'schedule'=>$schedule);

        } catch (Exception $e) {
            return array('success'=>false, 'message'=>'Failed to load schedule: '.$e->getMessage());
        }
    }

    /**
     * Get appointment details
     */
    public function getAppointmentDetails() {
        try {
            $appointment_id = isset($_GET['appointment_id']) ? intval($_GET['appointment_id']) : 0;
            $branch_id = getSessionBranchId();

            $sql = "SELECT a.*, u.name as patient_name, u.phone as patient_phone, u.email as patient_email,
                           tt.name as treatment_name,
                           (SELECT COUNT(*) FROM prescriptions p WHERE p.appointment_id = a.id) > 0 as prescription
                    FROM appointments a
                    LEFT JOIN users u ON a.patient_id = u.id
                    LEFT JOIN treatment_types tt ON a.treatment_type_id = tt.id
                    WHERE a.id = ? AND a.branch_id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('ii', $appointment_id, $branch_id);
            $stmt->execute();
            $appointment = $stmt->get_result()->fetch_assoc();

            if (!$appointment) {
                return array('success'=>false, 'message'=>'Appointment not found');
            }

            return array('success'=>true, 'appointment'=>$appointment);

        } catch (Exception $e) {
            return array('success'=>false, 'message'=>'Failed to load appointment details: '.$e->getMessage());
        }
    }

    /**
     * Send invoice with prescription via email
     */
    public function sendInvoiceWithPrescription() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $appointment_id = intval($data['appointment_id'] ?? 0);
            
            if (empty($appointment_id)) {
                http_response_code(400);
                return array('success' => false, 'message' => 'Appointment ID is required');
            }
            
            // Use the AppointmentController's email function
            require_once __DIR__ . '/AppointmentController.php';
            $appointmentController = new AppointmentController();
            
            // Call the private method through reflection or make it public
            // For now, let's just call the existing completion email directly
            $result = $this->sendCompletionEmail($appointment_id);
            
            if ($result) {
                http_response_code(200);
                return array('success' => true, 'message' => '✅ Invoice and prescription sent successfully via email!');
            } else {
                // Email failed - return the actual error
                http_response_code(200); // Return 200 instead of 500 to avoid JSON parse errors
                return array(
                    'success' => false, 
                    'message' => '⚠️ Failed to send email. Please check server logs for details.',
                    'emailConfigRequired' => true
                );
            }
            
        } catch (Exception $e) {
            error_log("DentistController::sendInvoiceWithPrescription Error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            http_response_code(200); // Return 200 to ensure JSON is parsed
            return array(
                'success' => false, 
                'message' => 'Error: ' . $e->getMessage(),
                'technicalDetails' => 'Check server error logs for more information'
            );
        }
    }
    
    /**
     * Send completion email with prescription and invoice
     */
    private function sendCompletionEmail($appointment_id) {
        try {
            // Get appointment details with patient email
            $stmt = $this->db->prepare("
                SELECT a.*, u.name as patient_name, u.email as patient_email, 
                       b.name as branch_name, b.email as branch_email,
                       tt.name as treatment_name
                FROM appointments a
                JOIN users u ON a.patient_id = u.id
                JOIN branches b ON a.branch_id = b.id
                LEFT JOIN treatment_types tt ON a.treatment_type_id = tt.id
                WHERE a.id = ?
            ");
            $stmt->bind_param('i', $appointment_id);
            $stmt->execute();
            $appointment = $stmt->get_result()->fetch_assoc();
            
            if (!$appointment || empty($appointment['patient_email'])) {
                error_log("Cannot send email: appointment not found or no patient email");
                return false;
            }
            
            // Get prescription details
            $stmt = $this->db->prepare("
                SELECT p.*, pm.medication_name, pm.dosage, pm.frequency, pm.duration, pm.instructions as med_instructions
                FROM prescriptions p
                LEFT JOIN prescription_medications pm ON p.id = pm.prescription_id
                WHERE p.appointment_id = ?
            ");
            $stmt->bind_param('i', $appointment_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $prescription = null;
            $medications = array();
            while ($row = $result->fetch_assoc()) {
                if (!$prescription) {
                    $prescription = array(
                        'diagnosis' => $row['diagnosis'],
                        'instructions' => $row['instructions'],
                        'created_at' => $row['created_at']
                    );
                }
                if ($row['medication_name']) {
                    $medications[] = array(
                        'name' => $row['medication_name'],
                        'dosage' => $row['dosage'],
                        'frequency' => $row['frequency'],
                        'duration' => $row['duration'],
                        'instructions' => $row['med_instructions']
                    );
                }
            }
            
            // Build email HTML
            $html = $this->buildPrescriptionEmail($appointment, $prescription, $medications);
            
            // Use the same email method as AuthController (which is working!)
            try {
                require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
                
                $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                
                // Load SMTP configuration from sendmail-config.ini (same as verification emails)
                $configFile = dirname(__DIR__, 2) . '/sendmail-config.ini';
                
                if (!file_exists($configFile)) {
                    error_log("SMTP configuration file not found: $configFile");
                    return false;
                }
                
                $config = parse_ini_file($configFile, true);
                if ($config === false) {
                    error_log("Failed to parse SMTP configuration file");
                    return false;
                }
                
                // Configure PHPMailer with SMTP settings
                $mail->isSMTP();
                $mail->Host = $config['sendmail']['smtp_server'] ?? 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = $config['sendmail']['auth_username'] ?? '';
                $mail->Password = $config['sendmail']['auth_password'] ?? '';
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = $config['sendmail']['smtp_port'] ?? 587;
                $mail->CharSet = 'UTF-8';
                
                // Sender info
                $fromEmail = $config['sendmail']['force_sender'] ?? 'noreply@dentalclinic.com';
                $fromName = $appointment['branch_name'];
                
                $mail->setFrom($fromEmail, $fromName);
                $mail->addAddress($appointment['patient_email'], $appointment['patient_name']);
                $mail->addReplyTo($appointment['branch_email'] ?: $fromEmail, $appointment['branch_name']);
                
                // Email content
                $mail->isHTML(true);
                $mail->Subject = "Appointment Completed - " . $appointment['branch_name'];
                $mail->Body = $html;
                $mail->AltBody = strip_tags($html);
                
                // Send email
                $result = $mail->send();
                
                if ($result) {
                    error_log("Email with prescription sent successfully to {$appointment['patient_email']} for appointment {$appointment_id}");
                    return true;
                } else {
                    error_log("Failed to send email to {$appointment['patient_email']} for appointment {$appointment_id}");
                    return false;
                }
                
            } catch (Exception $e) {
                error_log("Error sending completion email: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                return false;
            }
            
        } catch (Exception $e) {
            error_log("Error in sendCompletionEmail: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Build HTML email with prescription details
     */
    private function buildPrescriptionEmail($appointment, $prescription, $medications) {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #054A91 0%, #3E7CB1 100%); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f9fafb; padding: 30px; }
                .section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
                .section h3 { color: #054A91; margin-top: 0; border-bottom: 2px solid #054A91; padding-bottom: 10px; }
                table { width: 100%; border-collapse: collapse; margin: 15px 0; }
                th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb; }
                th { background: #f3f4f6; font-weight: 600; color: #374151; }
                .medication-item { background: #f0f9ff; padding: 15px; margin: 10px 0; border-left: 4px solid #3b82f6; border-radius: 4px; }
                .footer { text-align: center; padding: 20px; color: #6b7280; font-size: 14px; }
                .badge { display: inline-block; padding: 6px 12px; border-radius: 12px; font-size: 13px; font-weight: 600; }
                .badge-success { background: #d1fae5; color: #065f46; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1 style="margin:0;">🦷 ' . htmlspecialchars($appointment['branch_name']) . '</h1>
                    <p style="margin:10px 0 0 0;">Appointment Completion Summary</p>
                </div>
                
                <div class="content">
                    <p>Dear <strong>' . htmlspecialchars($appointment['patient_name']) . '</strong>,</p>
                    
                    <p>Thank you for your visit. Your appointment has been completed successfully.</p>
                    
                    <div class="section">
                        <h3>📅 Appointment Details</h3>
                        <table>
                            <tr><td><strong>Date:</strong></td><td>' . date('F d, Y', strtotime($appointment['appointment_date'])) . '</td></tr>
                            <tr><td><strong>Time:</strong></td><td>' . date('h:i A', strtotime($appointment['appointment_time'])) . '</td></tr>
                            <tr><td><strong>Treatment:</strong></td><td>' . htmlspecialchars($appointment['treatment_name'] ? $appointment['treatment_name'] : 'General Consultation') . '</td></tr>
                            <tr><td><strong>Status:</strong></td><td><span class="badge badge-success">Completed</span></td></tr>
                        </table>
                    </div>';
        
        if ($prescription) {
            $html .= '
                    <div class="section">
                        <h3>💊 Prescription</h3>
                        <p><strong>Date Prescribed:</strong> ' . date('F d, Y', strtotime($prescription['created_at'])) . '</p>
                        <p><strong>Diagnosis:</strong> ' . htmlspecialchars($prescription['diagnosis']) . '</p>
                        <p><strong>General Instructions:</strong> ' . nl2br(htmlspecialchars($prescription['instructions'] ? $prescription['instructions'] : 'Follow medication schedule as prescribed')) . '</p>
                        
                        <h4 style="color:#1f2937;margin-top:20px;">Prescribed Medications:</h4>';
            
            if (!empty($medications)) {
                foreach ($medications as $med) {
                    $html .= '
                        <div class="medication-item">
                            <strong style="font-size:16px;color:#1e40af;">💊 ' . htmlspecialchars($med['name']) . '</strong>
                            <table style="margin-top:10px;background:white;">
                                <tr><td style="width:40%;"><strong>Dosage:</strong></td><td>' . htmlspecialchars($med['dosage']) . '</td></tr>
                                <tr><td><strong>Frequency:</strong></td><td>' . htmlspecialchars($med['frequency']) . '</td></tr>
                                <tr><td><strong>Duration:</strong></td><td>' . htmlspecialchars($med['duration']) . '</td></tr>
                                ' . ($med['instructions'] ? '<tr><td><strong>Instructions:</strong></td><td>' . htmlspecialchars($med['instructions']) . '</td></tr>' : '') . '
                            </table>
                        </div>';
                }
            } else {
                $html .= '<p><em>No medications prescribed.</em></p>';
            }
            
            $html .= '
                        <div style="background:#fef3c7;border-left:4px solid #f59e0b;padding:15px;margin-top:20px;border-radius:4px;">
                            <strong>⚠️ Important Reminders:</strong>
                            <ul style="margin:10px 0 0 0;padding-left:20px;">
                                <li>Take all medications as prescribed</li>
                                <li>Complete the full course of treatment</li>
                                <li>Contact us immediately if you experience any adverse reactions</li>
                                <li>Keep this prescription for your records</li>
                            </ul>
                        </div>
                    </div>';
        }
        
        $html .= '
                    <div class="section">
                        <h3>📞 Need Help?</h3>
                        <p>If you have any questions or concerns about your prescription or treatment, please don\'t hesitate to contact us.</p>
                        <p><strong>Email:</strong> ' . htmlspecialchars($appointment['branch_email'] ? $appointment['branch_email'] : 'info@dentalclinic.com') . '</p>
                    </div>
                    
                    <p style="margin-top:30px;">Thank you for choosing <strong>' . htmlspecialchars($appointment['branch_name']) . '</strong>!</p>
                </div>
                
                <div class="footer">
                    <p>This is an automated message. Please do not reply to this email.</p>
                    <p>&copy; ' . date('Y') . ' ' . htmlspecialchars($appointment['branch_name']) . '. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>';
        
        return $html;
    }

    /**
     * Generate email content with invoice and prescription
     */
    private function generateEmailContent($appointment, $invoice, $prescription_data) {
        $html = "
        <h2>Appointment Completion - " . getBranchName($appointment['branch_id']) . "</h2>
        
        <p>Dear " . htmlspecialchars($appointment['patient_name']) . ",</p>
        
        <p>Thank you for your visit on " . $appointment['appointment_date'] . ". Please find your invoice and prescription details below.</p>
        ";

        // Invoice section
        if ($invoice) {
            $html .= "
            <h3>Invoice Details</h3>
            <table border='1' style='border-collapse: collapse; width: 100%;'>
                <tr><td><strong>Invoice Number:</strong></td><td>" . $invoice['invoice_number'] . "</td></tr>
                <tr><td><strong>Date:</strong></td><td>" . $invoice['invoice_date'] . "</td></tr>
                <tr><td><strong>Total Amount:</strong></td><td>₱" . number_format($invoice['total_amount'], 2) . "</td></tr>
                <tr><td><strong>Status:</strong></td><td>" . ucfirst($invoice['status']) . "</td></tr>
            </table>
            ";
        }

        // Prescription section
        if (!empty($prescription_data)) {
            $prescription = $prescription_data[0];
            $html .= "
            <h3>Prescription</h3>
            <p><strong>Diagnosis:</strong> " . htmlspecialchars($prescription['diagnosis']) . "</p>
            <p><strong>Instructions:</strong> " . htmlspecialchars($prescription['instructions']) . "</p>
            
            <h4>Medications:</h4>
            <table border='1' style='border-collapse: collapse; width: 100%;'>
                <tr><th>Medication</th><th>Dosage</th><th>Frequency</th><th>Duration</th><th>Instructions</th></tr>
            ";

            foreach ($prescription_data as $med) {
                if ($med['medication_name']) {
                    $html .= "<tr>
                        <td>" . htmlspecialchars($med['medication_name']) . "</td>
                        <td>" . htmlspecialchars($med['dosage']) . "</td>
                        <td>" . htmlspecialchars($med['frequency']) . "</td>
                        <td>" . htmlspecialchars($med['duration']) . "</td>
                        <td>" . htmlspecialchars($med['med_instructions']) . "</td>
                    </tr>";
                }
            }

            $html .= "</table>";
        }

        $html .= "
        <p>If you have any questions or concerns, please don't hesitate to contact us.</p>
        
        <p>Best regards,<br>
        " . getBranchName($appointment['branch_id']) . "</p>
        ";

        return $html;
    }

    /**
     * Get current dentist and clinic credentials
     */
    public function getCurrentCredentials() {
        try {
            $dentist_id = getSessionUserId();
            $branch_id = getSessionBranchId();

            $credentials = [];

            // Get dentist credentials
            $sql = "SELECT * FROM dentist_credentials WHERE dentist_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $dentist_id);
            $stmt->execute();
            $dentist_creds = $stmt->get_result()->fetch_assoc();
            if ($dentist_creds) {
                $credentials['dentist'] = $dentist_creds;
            }

            // Get clinic credentials
            $sql = "SELECT * FROM clinic_credentials WHERE branch_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $branch_id);
            $stmt->execute();
            $clinic_creds = $stmt->get_result()->fetch_assoc();
            if ($clinic_creds) {
                $credentials['clinic'] = $clinic_creds;
            }

            return array('success'=>true, 'credentials'=>$credentials);

        } catch (Exception $e) {
            return array('success'=>false, 'message'=>'Failed to load credentials: '.$e->getMessage());
        }
    }

    /**
     * Save dentist credentials
     */
    public function saveDentistCredentials() {
        try {
            $dentist_id = getSessionUserId();
            
            $license_number = $_POST['license_number'] ?? '';
            $specialization = $_POST['specialization'] ?? '';
            $experience_years = intval($_POST['experience_years'] ?? 0);
            $education = $_POST['education'] ?? '';
            $professional_bio = $_POST['professional_bio'] ?? '';

            // Handle file upload
            $license_file = null;
            if (isset($_FILES['license_file']) && $_FILES['license_file']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = dirname(dirname(dirname(__FILE__))) . '/uploads/credentials/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_extension = strtolower(pathinfo($_FILES['license_file']['name'], PATHINFO_EXTENSION));
                $license_file = 'dentist_' . $dentist_id . '_license.' . $file_extension;
                move_uploaded_file($_FILES['license_file']['tmp_name'], $upload_dir . $license_file);
                $license_file = 'uploads/credentials/' . $license_file;
            }

            // Check if record exists
            $sql = "SELECT id FROM dentist_credentials WHERE dentist_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $dentist_id);
            $stmt->execute();
            $exists = $stmt->get_result()->fetch_assoc();

            if ($exists) {
                // Update
                $sql = "UPDATE dentist_credentials SET 
                        license_number = ?, specialization = ?, experience_years = ?, education = ?, professional_bio = ?";
                $params = [$license_number, $specialization, $experience_years, $education, $professional_bio];
                $types = 'ssiss';
                
                if ($license_file) {
                    $sql .= ", license_file = ?";
                    $params[] = $license_file;
                    $types .= 's';
                }
                
                $sql .= " WHERE dentist_id = ?";
                $params[] = $dentist_id;
                $types .= 'i';
                
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param($types, ...$params);
            } else {
                // Insert
                $sql = "INSERT INTO dentist_credentials (dentist_id, license_number, specialization, experience_years, education, professional_bio, license_file) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param('ississs', $dentist_id, $license_number, $specialization, $experience_years, $education, $professional_bio, $license_file);
            }

            $stmt->execute();
            return array('success'=>true, 'message'=>'Dentist credentials saved successfully');

        } catch (Exception $e) {
            return array('success'=>false, 'message'=>'Failed to save credentials: '.$e->getMessage());
        }
    }

    /**
     * Save clinic credentials
     */
    public function saveClinicCredentials() {
        try {
            $branch_id = getSessionBranchId();
            
            $clinic_license = $_POST['clinic_license'] ?? '';
            $business_permit = $_POST['business_permit'] ?? '';
            $accreditations = $_POST['accreditations'] ?? '';
            $established_year = intval($_POST['established_year'] ?? 0);
            $services_offered = $_POST['services_offered'] ?? '';

            // Get existing data first
            $sql = "SELECT id, clinic_photos, certifications FROM clinic_credentials WHERE branch_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $branch_id);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();
            
            // Start with existing files
            $clinic_photos = $existing && $existing['clinic_photos'] ? json_decode($existing['clinic_photos'], true) : [];
            $certifications = $existing && $existing['certifications'] ? json_decode($existing['certifications'], true) : [];

            // Handle clinic photos uploads
            if (isset($_FILES['clinic_photos']) && is_array($_FILES['clinic_photos']['tmp_name'])) {
                $upload_dir = dirname(dirname(dirname(__FILE__))) . '/uploads/clinic_photos/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                foreach ($_FILES['clinic_photos']['tmp_name'] as $key => $tmp_name) {
                    if ($_FILES['clinic_photos']['error'][$key] === UPLOAD_ERR_OK) {
                        $file_extension = strtolower(pathinfo($_FILES['clinic_photos']['name'][$key], PATHINFO_EXTENSION));
                        $file_name = 'clinic_' . $branch_id . '_' . time() . '_' . $key . '.' . $file_extension;
                        move_uploaded_file($tmp_name, $upload_dir . $file_name);
                        $clinic_photos[] = 'uploads/clinic_photos/' . $file_name;
                    }
                }
            }

            // Handle certifications uploads
            if (isset($_FILES['clinic_certifications']) && is_array($_FILES['clinic_certifications']['tmp_name'])) {
                $upload_dir = dirname(dirname(dirname(__FILE__))) . '/uploads/credentials/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                foreach ($_FILES['clinic_certifications']['tmp_name'] as $key => $tmp_name) {
                    if ($_FILES['clinic_certifications']['error'][$key] === UPLOAD_ERR_OK) {
                        $file_extension = strtolower(pathinfo($_FILES['clinic_certifications']['name'][$key], PATHINFO_EXTENSION));
                        $file_name = 'cert_' . $branch_id . '_' . time() . '_' . $key . '.' . $file_extension;
                        move_uploaded_file($tmp_name, $upload_dir . $file_name);
                        $certifications[] = 'uploads/credentials/' . $file_name;
                    }
                }
            }

            $clinic_photos_json = json_encode($clinic_photos);
            $certifications_json = json_encode($certifications);

            if ($existing) {
                // Update
                $sql = "UPDATE clinic_credentials SET 
                        clinic_license = ?, business_permit = ?, accreditations = ?, 
                        established_year = ?, services_offered = ?, clinic_photos = ?, certifications = ?
                        WHERE branch_id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param('ssssissi', $clinic_license, $business_permit, $accreditations, $established_year, $services_offered, $clinic_photos_json, $certifications_json, $branch_id);
            } else {
                // Insert
                $sql = "INSERT INTO clinic_credentials (branch_id, clinic_license, business_permit, accreditations, established_year, services_offered, clinic_photos, certifications) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param('isssssss', $branch_id, $clinic_license, $business_permit, $accreditations, $established_year, $services_offered, $clinic_photos_json, $certifications_json);
            }

            $stmt->execute();
            return array('success'=>true, 'message'=>'Clinic credentials saved successfully');

        } catch (Exception $e) {
            return array('success'=>false, 'message'=>'Failed to save clinic credentials: '.$e->getMessage());
        }
    }

    /**
     * Proxy to AppointmentController->updateStatus for consistent business logic
     */
    public function updateStatus() {
        // Delegate to AppointmentController which already contains business rules
        $appointmentController = new AppointmentController();
        $result = $appointmentController->updateStatus();
        return $result;
    }

    /**
     * Get all treatments/services for the current branch
     */
    public function getBranchTreatments() {
        try {
            $branch_id = getSessionBranchId();
            
            $sql = "SELECT 
                        bs.id,
                        bs.branch_id,
                        bs.treatment_type_id,
                        bs.price,
                        bs.estimated_duration_minutes as duration,
                        bs.is_available as is_active,
                        bs.special_instructions as description,
                        tt.name,
                        tt.description as treatment_description,
                        tc.name as category
                    FROM branch_services bs
                    INNER JOIN treatment_types tt ON bs.treatment_type_id = tt.id
                    LEFT JOIN treatment_categories tc ON tt.category_id = tc.id
                    WHERE bs.branch_id = ? 
                    ORDER BY tt.name ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $branch_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $treatments = [];
            while ($row = $result->fetch_assoc()) {
                // Combine descriptions
                $description = $row['special_instructions'] ?: $row['treatment_description'];
                $row['description'] = $description;
                $treatments[] = $row;
            }
            
            return ['success' => true, 'treatments' => $treatments];
            
        } catch (Exception $e) {
            error_log("getBranchTreatments Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to load treatments: ' . $e->getMessage()];
        }
    }

    /**
     * Get all treatments (for dropdowns) - returns active treatments
     */
    public function getTreatments() {
        try {
            $branch_id = getSessionBranchId();
            
            $sql = "SELECT 
                        bs.id,
                        tt.name,
                        bs.price,
                        bs.estimated_duration_minutes as duration,
                        tc.name as category
                    FROM branch_services bs
                    INNER JOIN treatment_types tt ON bs.treatment_type_id = tt.id
                    LEFT JOIN treatment_categories tc ON tt.category_id = tc.id
                    WHERE bs.branch_id = ? AND bs.is_available = 1 
                    ORDER BY tt.name ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $branch_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $treatments = [];
            while ($row = $result->fetch_assoc()) {
                $treatments[] = $row;
            }
            
            return ['success' => true, 'treatments' => $treatments];
            
        } catch (Exception $e) {
            error_log("getTreatments Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to load treatments'];
        }
    }

    /**
     * Create a new treatment/service
     */
    public function createTreatment() {
        try {
            $branch_id = getSessionBranchId();
            
            // Get JSON input
            $input = json_decode(file_get_contents('php://input'), true);
            
            $name = $input['name'] ?? '';
            $description = $input['description'] ?? '';
            $price = floatval($input['price'] ?? 0);
            $duration = intval($input['duration'] ?? 30);
            $category = $input['category'] ?? '';
            $is_active = isset($input['is_active']) ? intval($input['is_active']) : 1;
            
            if (empty($name)) {
                return ['success' => false, 'message' => 'Treatment name is required'];
            }
            
            if ($price <= 0) {
                return ['success' => false, 'message' => 'Price must be greater than 0'];
            }
            
            // First, find or create the category
            $category_id = null;
            if (!empty($category)) {
                $cat_sql = "SELECT id FROM treatment_categories WHERE name = ?";
                $cat_stmt = $this->db->prepare($cat_sql);
                $cat_stmt->bind_param('s', $category);
                $cat_stmt->execute();
                $cat_result = $cat_stmt->get_result()->fetch_assoc();
                
                if ($cat_result) {
                    $category_id = $cat_result['id'];
                } else {
                    // Create new category
                    $insert_cat = "INSERT INTO treatment_categories (name, description) VALUES (?, ?)";
                    $insert_stmt = $this->db->prepare($insert_cat);
                    $insert_stmt->bind_param('ss', $category, $category);
                    $insert_stmt->execute();
                    $category_id = $this->db->insert_id;
                }
            }
            
            // Create the treatment type
            $tt_sql = "INSERT INTO treatment_types (category_id, name, description, duration_minutes, base_price, is_active) 
                      VALUES (?, ?, ?, ?, ?, 1)";
            $tt_stmt = $this->db->prepare($tt_sql);
            $tt_stmt->bind_param('issid', $category_id, $name, $description, $duration, $price);
            $tt_stmt->execute();
            $treatment_type_id = $this->db->insert_id;
            
            // Create the branch service link
            $bs_sql = "INSERT INTO branch_services (branch_id, treatment_type_id, price, estimated_duration_minutes, is_available, special_instructions) 
                      VALUES (?, ?, ?, ?, ?, ?)";
            $bs_stmt = $this->db->prepare($bs_sql);
            $bs_stmt->bind_param('iidiis', $branch_id, $treatment_type_id, $price, $duration, $is_active, $description);
            $bs_stmt->execute();
            
            return ['success' => true, 'message' => 'Treatment created successfully', 'id' => $this->db->insert_id];
            
        } catch (Exception $e) {
            error_log("createTreatment Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create treatment: ' . $e->getMessage()];
        }
    }

    /**
     * Update an existing treatment/service
     */
    public function updateTreatment() {
        try {
            $branch_id = getSessionBranchId();
            
            // Get JSON input
            $input = json_decode(file_get_contents('php://input'), true);
            
            $treatment_id = intval($input['treatment_id'] ?? 0);
            $name = $input['name'] ?? '';
            $description = $input['description'] ?? '';
            $price = floatval($input['price'] ?? 0);
            $duration = intval($input['duration'] ?? 30);
            $category = $input['category'] ?? '';
            $is_active = isset($input['is_active']) ? intval($input['is_active']) : 1;
            
            if ($treatment_id <= 0) {
                return ['success' => false, 'message' => 'Invalid treatment ID'];
            }
            
            if (empty($name)) {
                return ['success' => false, 'message' => 'Treatment name is required'];
            }
            
            if ($price <= 0) {
                return ['success' => false, 'message' => 'Price must be greater than 0'];
            }
            
            // Verify treatment belongs to this branch and get treatment_type_id
            $check_sql = "SELECT treatment_type_id FROM branch_services WHERE id = ? AND branch_id = ?";
            $check_stmt = $this->db->prepare($check_sql);
            $check_stmt->bind_param('ii', $treatment_id, $branch_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result()->fetch_assoc();
            
            if (!$check_result) {
                return ['success' => false, 'message' => 'Treatment not found or access denied'];
            }
            
            $treatment_type_id = $check_result['treatment_type_id'];
            
            // Find or create category
            $category_id = null;
            if (!empty($category)) {
                $cat_sql = "SELECT id FROM treatment_categories WHERE name = ?";
                $cat_stmt = $this->db->prepare($cat_sql);
                $cat_stmt->bind_param('s', $category);
                $cat_stmt->execute();
                $cat_result = $cat_stmt->get_result()->fetch_assoc();
                
                if ($cat_result) {
                    $category_id = $cat_result['id'];
                } else {
                    $insert_cat = "INSERT INTO treatment_categories (name, description) VALUES (?, ?)";
                    $insert_stmt = $this->db->prepare($insert_cat);
                    $insert_stmt->bind_param('ss', $category, $category);
                    $insert_stmt->execute();
                    $category_id = $this->db->insert_id;
                }
            }
            
            // Update treatment_types
            $tt_sql = "UPDATE treatment_types SET category_id = ?, name = ?, description = ?, duration_minutes = ?, base_price = ? 
                      WHERE id = ?";
            $tt_stmt = $this->db->prepare($tt_sql);
            $tt_stmt->bind_param('issidi', $category_id, $name, $description, $duration, $price, $treatment_type_id);
            $tt_stmt->execute();
            
            // Update branch_services
            $bs_sql = "UPDATE branch_services SET price = ?, estimated_duration_minutes = ?, is_available = ?, special_instructions = ? 
                      WHERE id = ? AND branch_id = ?";
            $bs_stmt = $this->db->prepare($bs_sql);
            $bs_stmt->bind_param('diisii', $price, $duration, $is_active, $description, $treatment_id, $branch_id);
            $bs_stmt->execute();
            
            return ['success' => true, 'message' => 'Treatment updated successfully'];
            
        } catch (Exception $e) {
            error_log("updateTreatment Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update treatment: ' . $e->getMessage()];
        }
    }

    /**
     * Toggle treatment active status
     */
    public function toggleTreatmentStatus() {
        try {
            $branch_id = getSessionBranchId();
            
            // Get JSON input
            $input = json_decode(file_get_contents('php://input'), true);
            
            $treatment_id = intval($input['treatment_id'] ?? 0);
            $is_active = isset($input['is_active']) ? intval($input['is_active']) : 0;
            
            if ($treatment_id <= 0) {
                return ['success' => false, 'message' => 'Invalid treatment ID'];
            }
            
            // Verify treatment belongs to this branch
            $check_sql = "SELECT id FROM branch_services WHERE id = ? AND branch_id = ?";
            $check_stmt = $this->db->prepare($check_sql);
            $check_stmt->bind_param('ii', $treatment_id, $branch_id);
            $check_stmt->execute();
            $exists = $check_stmt->get_result()->fetch_assoc();
            
            if (!$exists) {
                return ['success' => false, 'message' => 'Treatment not found or access denied'];
            }
            
            // Update is_available in branch_services
            $sql = "UPDATE branch_services SET is_available = ? WHERE id = ? AND branch_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('iii', $is_active, $treatment_id, $branch_id);
            $stmt->execute();
            
            $status_text = $is_active ? 'activated' : 'deactivated';
            return ['success' => true, 'message' => 'Treatment ' . $status_text . ' successfully'];
            
        } catch (Exception $e) {
            error_log("toggleTreatmentStatus Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update status: ' . $e->getMessage()];
        }
    }

    /**
     * Delete a treatment/service
     */
    public function deleteTreatment() {
        try {
            $branch_id = getSessionBranchId();
            
            // Get JSON input
            $input = json_decode(file_get_contents('php://input'), true);
            
            $treatment_id = intval($input['treatment_id'] ?? 0);
            
            if ($treatment_id <= 0) {
                return ['success' => false, 'message' => 'Invalid treatment ID'];
            }
            
            // Verify treatment belongs to this branch and get treatment_type_id
            $check_sql = "SELECT treatment_type_id FROM branch_services WHERE id = ? AND branch_id = ?";
            $check_stmt = $this->db->prepare($check_sql);
            $check_stmt->bind_param('ii', $treatment_id, $branch_id);
            $check_stmt->execute();
            $exists = $check_stmt->get_result()->fetch_assoc();
            
            if (!$exists) {
                return ['success' => false, 'message' => 'Treatment not found or access denied'];
            }
            
            $treatment_type_id = $exists['treatment_type_id'];
            
            // Check if treatment is used in appointments (check by treatment_type_id since that's what appointments reference)
            $usage_sql = "SELECT 
                            (SELECT COUNT(*) FROM appointments WHERE treatment_id = ?) +
                            (SELECT COUNT(*) FROM walk_in_appointments WHERE treatment_id = ?) as total_count";
            $usage_stmt = $this->db->prepare($usage_sql);
            $usage_stmt->bind_param('ii', $treatment_type_id, $treatment_type_id);
            $usage_stmt->execute();
            $usage_result = $usage_stmt->get_result()->fetch_assoc();
            
            $total_usage = intval($usage_result['total_count'] ?? 0);
            
            if ($total_usage > 0) {
                // Instead of deleting, deactivate it
                $sql = "UPDATE branch_services SET is_available = 0 WHERE id = ? AND branch_id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param('ii', $treatment_id, $branch_id);
                $stmt->execute();
                
                return ['success' => true, 'message' => 'Treatment is used in ' . $total_usage . ' appointment(s). It has been deactivated instead of deleted.'];
            } else {
                // Safe to delete the branch_services entry (treatment_type remains for historical data)
                $sql = "DELETE FROM branch_services WHERE id = ? AND branch_id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param('ii', $treatment_id, $branch_id);
                $stmt->execute();
                
                return ['success' => true, 'message' => 'Treatment deleted successfully'];
            }
            
        } catch (Exception $e) {
            error_log("deleteTreatment Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to delete treatment: ' . $e->getMessage()];
        }
    }
}

// API handler - only run if accessed directly, not when included by another file
if (php_sapi_name() !== 'cli' && !defined('DENTIST_API_INCLUDED')) {
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    $ctrl = new DentistController();
    $resp = array('success'=>false,'message'=>'Invalid action');
    
    switch ($action) {
        case 'getAnalyticsData':
            $resp = $ctrl->getAnalyticsData();
            break;
        case 'getComprehensiveAnalytics':
            $resp = $ctrl->getComprehensiveAnalytics();
            break;
        case 'getPendingAppointments':
            $resp = $ctrl->getPendingAppointments();
            break;
        case 'getDailyAppointments':
            $resp = $ctrl->getDailyAppointments();
            break;
        case 'getMySchedule':
            $resp = $ctrl->getMySchedule();
            break;
        case 'getAppointmentDetails':
            $resp = $ctrl->getAppointmentDetails();
            break;
        case 'updateStatus':
            $resp = $ctrl->updateStatus();
            break;
        case 'sendInvoiceWithPrescription':
            $resp = $ctrl->sendInvoiceWithPrescription();
            break;
        case 'getCurrentCredentials':
            $resp = $ctrl->getCurrentCredentials();
            break;
        case 'saveDentistCredentials':
            $resp = $ctrl->saveDentistCredentials();
            break;
        case 'saveClinicCredentials':
            $resp = $ctrl->saveClinicCredentials();
            break;
    }
    
    header('Content-Type: application/json');
    echo json_encode($resp);
    exit;
}
