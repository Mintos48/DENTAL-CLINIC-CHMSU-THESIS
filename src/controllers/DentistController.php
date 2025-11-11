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
     * Get all appointments for a specific date
     */
    public function getDailyAppointments() {
        try {
            $role = getSessionRole();
            $branch_id = getSessionBranchId();
            $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

            if ($role === ROLE_ADMIN && isset($_GET['branch_id'])) {
                $branch_id = intval($_GET['branch_id']);
            }

            $sql = "SELECT a.id, a.appointment_date, a.appointment_time, a.status, 
                           u.name as patient_name, u.phone as patient_phone,
                           tt.name as treatment_name,
                           (SELECT COUNT(*) FROM prescriptions p WHERE p.appointment_id = a.id) > 0 as has_prescription
                    FROM appointments a
                    LEFT JOIN users u ON a.patient_id = u.id
                    LEFT JOIN treatment_types tt ON a.treatment_type_id = tt.id
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
     * Get dentist's upcoming schedule
     */
    public function getMySchedule() {
        try {
            $dentist_id = getSessionUserId();
            $branch_id = getSessionBranchId();
            $today = date('Y-m-d');

            $sql = "SELECT a.appointment_date, a.appointment_time, u.name as patient_name, 
                           tt.name as treatment_name, a.status
                    FROM appointments a
                    LEFT JOIN users u ON a.patient_id = u.id
                    LEFT JOIN treatment_types tt ON a.treatment_type_id = tt.id
                    WHERE a.dentist_id = ? AND a.appointment_date >= ? AND a.status IN ('pending', 'approved')
                    ORDER BY a.appointment_date ASC, a.appointment_time ASC LIMIT 15";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('is', $dentist_id, $today);
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
            $branch_id = getSessionBranchId();

            // Get appointment and patient details
            $sql = "SELECT a.*, u.name as patient_name, u.email as patient_email
                    FROM appointments a
                    LEFT JOIN users u ON a.patient_id = u.id
                    WHERE a.id = ? AND a.branch_id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('ii', $appointment_id, $branch_id);
            $stmt->execute();
            $appointment = $stmt->get_result()->fetch_assoc();

            if (!$appointment || !$appointment['patient_email']) {
                return array('success'=>false, 'message'=>'Patient email not found');
            }

            // Get invoice
            $sql = "SELECT * FROM invoices WHERE appointment_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $appointment_id);
            $stmt->execute();
            $invoice = $stmt->get_result()->fetch_assoc();

            // Get prescription if exists
            $sql = "SELECT p.*, pm.medication_name, pm.dosage, pm.frequency, pm.duration, pm.instructions as med_instructions
                    FROM prescriptions p
                    LEFT JOIN prescription_medications pm ON p.id = pm.prescription_id
                    WHERE p.appointment_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $appointment_id);
            $stmt->execute();
            $prescription_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            // Prepare email content
            require_once dirname(__DIR__) . '/services/EmailService.php';
            $emailService = new EmailService();

            $subject = "Your Appointment Invoice and Prescription - " . getBranchName($branch_id);
            $body = $this->generateEmailContent($appointment, $invoice, $prescription_data);

            $result = $emailService->sendEmail($appointment['patient_email'], $subject, $body);

            if ($result['success']) {
                // Log email sent
                $sql = "INSERT INTO email_logs (appointment_id, recipient_email, subject, status, sent_at) VALUES (?, ?, ?, 'sent', NOW())";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param('iss', $appointment_id, $appointment['patient_email'], $subject);
                $stmt->execute();

                return array('success'=>true, 'message'=>'Invoice and prescription sent successfully');
            } else {
                return array('success'=>false, 'message'=>'Failed to send email: ' . $result['message']);
            }

        } catch (Exception $e) {
            return array('success'=>false, 'message'=>'Failed to send email: '.$e->getMessage());
        }
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

            // Handle file uploads - simplified for now, store file names
            $clinic_photos = [];
            $certifications = [];

            if (isset($_FILES['clinic_photos'])) {
                $upload_dir = dirname(dirname(dirname(__FILE__))) . '/uploads/clinic_photos/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                foreach ($_FILES['clinic_photos']['tmp_name'] as $key => $tmp_name) {
                    if ($_FILES['clinic_photos']['error'][$key] === UPLOAD_ERR_OK) {
                        $file_name = 'clinic_' . $branch_id . '_' . time() . '_' . $key . '.jpg';
                        move_uploaded_file($tmp_name, $upload_dir . $file_name);
                        $clinic_photos[] = 'uploads/clinic_photos/' . $file_name;
                    }
                }
            }

            // Check if record exists
            $sql = "SELECT id FROM clinic_credentials WHERE branch_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $branch_id);
            $stmt->execute();
            $exists = $stmt->get_result()->fetch_assoc();

            $clinic_photos_json = json_encode($clinic_photos);
            $certifications_json = json_encode($certifications);

            if ($exists) {
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
