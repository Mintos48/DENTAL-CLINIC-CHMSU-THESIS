<?php
/**
 * Referral Controller - Handle Patient Referrals Between Branches
 */

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/config/session.php';

class ReferralController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getConnection();
    }
    
    /**
     * Create a new patient referral from existing appointment
     */
    public function createReferralFromAppointment() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(400);
            return ['success' => false, 'message' => 'Invalid request method'];
        }
        
        try {
            // Debug logging
            error_log("createReferralFromAppointment: Starting method");
            
            if (!function_exists('getSessionUserId')) {
                error_log("createReferralFromAppointment: getSessionUserId function not found");
                http_response_code(500);
                return ['success' => false, 'message' => 'Session functions not available'];
            }
            
            $referring_staff_id = getSessionUserId();
            $from_branch_id = getSessionBranchId();
            
            error_log("createReferralFromAppointment: staff_id={$referring_staff_id}, branch_id={$from_branch_id}");
            
            if (!$referring_staff_id || !$from_branch_id) {
                http_response_code(400);
                return ['success' => false, 'message' => 'Staff session not found'];
            }
            
            // Check if user is staff or admin
            $role = getSessionRole();
            if (!in_array($role, [ROLE_STAFF, ROLE_ADMIN])) {
                http_response_code(403);
                return ['success' => false, 'message' => 'Unauthorized access'];
            }
            
            $appointment_id = $_POST['appointment_id'] ?? 0;
            $to_branch_id = $_POST['to_branch_id'] ?? 0;
            $reason = $_POST['reason'] ?? '';
            $priority = $_POST['priority'] ?? 'normal';
            $new_treatment_type_id = $_POST['treatment_type_id'] ?? null; // Optional new treatment
            
            // Map priority to urgency for database schema
            $urgency_map = [
                'low' => 'routine',
                'normal' => 'routine', 
                'high' => 'urgent',
                'urgent' => 'urgent',
                'emergency' => 'emergency'
            ];
            $urgency = $urgency_map[$priority] ?? 'routine';
            
            if (!$appointment_id || !$to_branch_id || empty($reason)) {
                http_response_code(400);
                return ['success' => false, 'message' => 'Missing required fields'];
            }
            
            if ($from_branch_id == $to_branch_id) {
                http_response_code(400);
                return ['success' => false, 'message' => 'Cannot refer to the same branch'];
            }
            
            // Get appointment details
            $appointmentStmt = $this->db->prepare(
                "SELECT a.*, u.name as patient_name, u.email as patient_email, 
                        tt.name as treatment_name, tt.duration_minutes
                 FROM appointments a
                 JOIN users u ON a.patient_id = u.id
                 LEFT JOIN treatment_types tt ON a.treatment_type_id = tt.id
                 WHERE a.id = ? AND a.branch_id = ? AND a.status IN ('pending', 'approved')"
            );
            $appointmentStmt->bind_param("ii", $appointment_id, $from_branch_id);
            $appointmentStmt->execute();
            $appointmentResult = $appointmentStmt->get_result();
            
            if ($appointmentResult->num_rows === 0) {
                return ['success' => false, 'message' => 'Appointment not found or cannot be referred'];
            }
            
            $appointment = $appointmentResult->fetch_assoc();
            
            // Determine which treatment to check for availability
            $treatment_to_check = $new_treatment_type_id ?: $appointment['treatment_type_id'];
            
            // Debug logging
            error_log("DEBUG: new_treatment_type_id = " . ($new_treatment_type_id ?: 'null'));
            error_log("DEBUG: appointment treatment_type_id = " . ($appointment['treatment_type_id'] ?: 'null'));
            error_log("DEBUG: treatment_to_check = " . ($treatment_to_check ?: 'null'));
            error_log("DEBUG: to_branch_id = " . $to_branch_id);
            
            // Only check treatment availability if a specific treatment is requested
            if ($treatment_to_check) {
                // Check if treatment is available at target branch
                $treatmentStmt = $this->db->prepare(
                    "SELECT bs.*, tt.name as treatment_name, tt.duration_minutes
                     FROM branch_services bs
                     JOIN treatment_types tt ON bs.treatment_type_id = tt.id
                     WHERE bs.branch_id = ? AND bs.treatment_type_id = ? AND bs.is_available = 1"
                );
                $treatmentStmt->bind_param("ii", $to_branch_id, $treatment_to_check);
                $treatmentStmt->execute();
                $treatmentResult = $treatmentStmt->get_result();
                
                error_log("DEBUG: Treatment availability query result rows = " . $treatmentResult->num_rows);
                
                if ($treatmentResult->num_rows === 0) {
                    error_log("DEBUG: Treatment $treatment_to_check not available at branch $to_branch_id");
                    return ['success' => false, 'message' => 'Treatment not available at target branch'];
                }
                
                $service = $treatmentResult->fetch_assoc();
                error_log("DEBUG: Found treatment: " . $service['treatment_name']);
            } else {
                // No specific treatment requested - general referral
                $service = ['treatment_name' => 'General Consultation'];
                error_log("DEBUG: No specific treatment - general referral");
            }
            
            // Start transaction
            $this->db->begin_transaction();
            
            try {
                // Create referral record
                $referralStmt = $this->db->prepare(
                    "INSERT INTO patient_referrals 
                     (patient_id, from_staff_id, from_branch_id, to_branch_id, 
                      treatment_type_id, original_appointment_id, reason, clinical_notes, status, urgency) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending_patient_approval', ?)"
                );
                
                $notes = "Referred from appointment on " . $appointment['appointment_date'] . 
                        " at " . $appointment['appointment_time'] . 
                        ". Original treatment: " . $appointment['treatment_name'];
                
                if ($treatment_to_check && $new_treatment_type_id) {
                    $notes .= ". Requested treatment: " . $service['treatment_name'];
                }
                
                $notes .= ". Original appointment ID: " . $appointment_id;
                
                $referralStmt->bind_param(
                    "iiiiiiiss",
                    $appointment['patient_id'],
                    $referring_staff_id,
                    $from_branch_id,
                    $to_branch_id,
                    $treatment_to_check, // Use the determined treatment (could be null for general referral)
                    $appointment_id,     // Store original appointment ID
                    $reason,
                    $notes,
                    $urgency
                );
                
                if (!$referralStmt->execute()) {
                    throw new Exception("Failed to create referral");
                }
                
                $referral_id = $this->db->insert_id;
                
                // Update original appointment status to 'referred'
                $updateStmt = $this->db->prepare(
                    "UPDATE appointments 
                     SET status = 'referred', 
                         notes = CONCAT(COALESCE(notes, ''), 
                                       CASE WHEN notes IS NULL OR notes = '' THEN '' ELSE '\n' END,
                                       'REFERRED: Patient referred to another branch for specialized treatment.')
                     WHERE id = ?"
                );
                $updateStmt->bind_param("i", $appointment_id);
                
                if (!$updateStmt->execute()) {
                    throw new Exception("Failed to update appointment status");
                }
                
                // Log the activity
                $this->logActivity(
                    $referring_staff_id, 
                    'create_referral_from_appointment',
                    "Referred appointment #{$appointment_id} for patient {$appointment['patient_name']} to branch {$to_branch_id}"
                );
                
                $this->db->commit();
                
                return [
                    'success' => true, 
                    'message' => 'Appointment successfully converted to referral',
                    'referral_id' => $referral_id,
                    'appointment_id' => $appointment_id
                ];
                
            } catch (Exception $e) {
                $this->db->rollback();
                throw $e;
            }
            
        } catch (Exception $e) {
            error_log("Error creating referral from appointment: " . $e->getMessage());
            http_response_code(500);
            return ['success' => false, 'message' => 'Failed to create referral: ' . $e->getMessage()];
        }
    }
    
    /**
     * Create a new patient referral
     */
    public function createReferral() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['success' => false, 'message' => 'Invalid request method'];
        }
        
        try {
            $referring_staff_id = getSessionUserId();
            $from_branch_id = getSessionBranchId();
            
            if (!$referring_staff_id || !$from_branch_id) {
                return ['success' => false, 'message' => 'Staff session not found'];
            }
            
            // Check if user is staff or admin
            $role = getSessionRole();
            if (!in_array($role, [ROLE_STAFF, ROLE_ADMIN])) {
                return ['success' => false, 'message' => 'Unauthorized access'];
            }
            
            $patient_id = $_POST['patient_id'] ?? 0;
            $to_branch_id = $_POST['to_branch_id'] ?? 0;
            $treatment_type_id = $_POST['treatment_type_id'] ?? 0; // Optional now
            $reason = $_POST['reason'] ?? '';
            $notes = $_POST['notes'] ?? '';
            $priority = $_POST['priority'] ?? 'normal';
            $preferred_time = $_POST['preferred_time'] ?? null;
            
            // Map priority to urgency for database schema
            $urgency_map = [
                'low' => 'routine',
                'normal' => 'routine', 
                'high' => 'urgent',
                'urgent' => 'urgent',
                'emergency' => 'emergency'
            ];
            $urgency = $urgency_map[$priority] ?? 'routine';
            
            if (!$patient_id || !$to_branch_id || empty($reason)) {
                return ['success' => false, 'message' => 'Missing required fields'];
            }
            
            if ($from_branch_id == $to_branch_id) {
                return ['success' => false, 'message' => 'Cannot refer to the same branch'];
            }
            
            // Verify patient exists and belongs to the current branch
            $patientStmt = $this->db->prepare(
                "SELECT u.id, u.name, u.email FROM users u 
                 WHERE u.id = ? AND u.branch_id = ? AND u.role = 'patient'"
            );
            $patientStmt->bind_param("ii", $patient_id, $from_branch_id);
            $patientStmt->execute();
            $patientResult = $patientStmt->get_result();
            
            if ($patientResult->num_rows === 0) {
                return ['success' => false, 'message' => 'Patient not found or not in your branch'];
            }
            
            $patient = $patientResult->fetch_assoc();
            
            // Verify target branch exists
            $branchStmt = $this->db->prepare(
                "SELECT b.name as branch_name FROM branches b WHERE b.id = ?"
            );
            $branchStmt->bind_param("i", $to_branch_id);
            $branchStmt->execute();
            $branchResult = $branchStmt->get_result();
            
            if ($branchResult->num_rows === 0) {
                return ['success' => false, 'message' => 'Target branch not found'];
            }
            
            $branch = $branchResult->fetch_assoc();
            
            // If treatment_type_id is provided, verify it's available at target branch
            if ($treatment_type_id) {
                $branchServiceStmt = $this->db->prepare(
                    "SELECT bs.*, tt.name as treatment_name 
                     FROM branch_services bs
                     JOIN treatment_types tt ON bs.treatment_type_id = tt.id
                     WHERE bs.branch_id = ? AND bs.treatment_type_id = ? AND bs.is_available = 1"
                );
                $branchServiceStmt->bind_param("ii", $to_branch_id, $treatment_type_id);
                $branchServiceStmt->execute();
                $serviceResult = $branchServiceStmt->get_result();
                
                if ($serviceResult->num_rows === 0) {
                    return ['success' => false, 'message' => 'Selected treatment is not available at the target branch'];
                }
                
                $service = $serviceResult->fetch_assoc();
            } else {
                $service = ['treatment_name' => 'General consultation/treatment'];
            }
            
            // Create referral
            $stmt = $this->db->prepare(
                "INSERT INTO patient_referrals 
                 (patient_id, from_staff_id, from_branch_id, to_branch_id, 
                  reason, clinical_notes, urgency, preferred_time, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending_patient_approval')"
            );
            
            // Add treatment info to notes
            $fullNotes = "Treatment requested: " . $service['treatment_name'];
            if (!empty($notes)) {
                $fullNotes .= ". Additional notes: " . $notes;
            }
            
            $stmt->bind_param(
                "iiiissss",
                $patient_id, $referring_staff_id, $from_branch_id, $to_branch_id,
                $reason, $fullNotes, $urgency, $preferred_time
            );
            
            if ($stmt->execute()) {
                $referral_id = $this->db->insert_id;
                
                // Log activity
                $this->logActivity(
                    $referring_staff_id,
                    'referral_created',
                    "Created referral #$referral_id for patient {$patient['name']} to {$service['branch_name']} for {$service['treatment_name']}"
                );
                
                return [
                    'success' => true,
                    'message' => "Referral created successfully. Patient {$patient['name']} has been referred to {$service['branch_name']} for {$service['treatment_name']}.",
                    'referral_id' => $referral_id
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to create referral'];
            }
            
        } catch (Exception $e) {
            error_log("ReferralController::createReferral - " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create referral: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get pending referrals for a branch (incoming referrals)
     */
    public function getPendingReferrals() {
        error_log("=== DEBUG: getPendingReferrals called ===");
        
        try {
            $branch_id = getSessionBranchId();
            $role = getSessionRole();
            $user_id = getSessionUserId();
            
            error_log("=== DEBUG: Staff user_id: " . $user_id . ", branch_id: " . $branch_id . ", role: " . $role . " ===");
            
            if (!in_array($role, [ROLE_STAFF, ROLE_ADMIN])) {
                error_log("=== DEBUG: Unauthorized access, role: " . $role . " ===");
                return ['success' => false, 'message' => 'Unauthorized access'];
            }
            
            $query = "SELECT pr.*, 
                        p.name as patient_name, p.email as patient_email, p.phone as patient_phone,
                        fb.name as from_branch_name,
                        rs.name as referring_staff_name,
                        tt.name as treatment_name
                 FROM patient_referrals pr
                 JOIN users p ON pr.patient_id = p.id
                 JOIN branches fb ON pr.from_branch_id = fb.id
                 LEFT JOIN users rs ON pr.from_staff_id = rs.id
                 LEFT JOIN treatment_types tt ON pr.treatment_type_id = tt.id
                 WHERE pr.to_branch_id = ? AND pr.status = 'patient_approved'
                 ORDER BY pr.patient_approved_at DESC, pr.created_at DESC";
            
            error_log("=== DEBUG: Query: " . $query . " ===");
            error_log("=== DEBUG: Searching for referrals to branch_id: " . $branch_id . " ===");
            
            $stmt = $this->db->prepare($query);
            if (!$stmt) {
                throw new Exception("Failed to prepare query: " . $this->db->error);
            }
            
            $stmt->bind_param("i", $branch_id);
            if (!$stmt->execute()) {
                throw new Exception("Failed to execute query: " . $stmt->error);
            }
            
            $result = $stmt->get_result();
            error_log("=== DEBUG: Found " . $result->num_rows . " patient-approved referrals ===");
            
            $referrals = [];
            while ($row = $result->fetch_assoc()) {
                error_log("=== DEBUG: Found referral: ID=" . $row['id'] . ", status=" . $row['status'] . ", to_branch=" . $row['to_branch_id'] . " ===");
                $referrals[] = $row;
            }
            
            // Debug: Also check ALL referrals in the database
            $debugStmt = $this->db->prepare("SELECT id, status, to_branch_id, from_branch_id, patient_id FROM patient_referrals ORDER BY id DESC LIMIT 10");
            $debugStmt->execute();
            $debugResult = $debugStmt->get_result();
            error_log("=== DEBUG: All recent referrals in database: ===");
            while ($debugRow = $debugResult->fetch_assoc()) {
                error_log("=== DEBUG: Referral ID=" . $debugRow['id'] . ", status=" . $debugRow['status'] . ", from_branch=" . $debugRow['from_branch_id'] . ", to_branch=" . $debugRow['to_branch_id'] . ", patient=" . $debugRow['patient_id'] . " ===");
            }
            
            return [
                'success' => true,
                'referrals' => $referrals,
                'count' => count($referrals)
            ];
            
        } catch (Exception $e) {
            error_log("=== DEBUG: Exception in getPendingReferrals: " . $e->getMessage() . " ===");
            error_log("=== DEBUG: Exception trace: " . $e->getTraceAsString() . " ===");
            return [
                'success' => false, 
                'message' => 'Failed to load referrals',
                'debug' => $e->getMessage() // Show debug info for now
            ];
        }
    }
    
    /**
     * Get sent referrals from current branch (outgoing referrals)
     */
    public function getSentReferrals() {
        try {
            $branch_id = getSessionBranchId();
            $role = getSessionRole();
            
            if (!in_array($role, [ROLE_STAFF, ROLE_ADMIN])) {
                return ['success' => false, 'message' => 'Unauthorized access'];
            }
            
            $stmt = $this->db->prepare(
                "SELECT pr.*, 
                        p.name as patient_name, p.email as patient_email,
                        tb.name as to_branch_name,
                        ra.name as accepting_staff_name,
                        tt.name as treatment_name
                 FROM patient_referrals pr
                 JOIN users p ON pr.patient_id = p.id
                 JOIN branches tb ON pr.to_branch_id = tb.id
                 LEFT JOIN users ra ON pr.responding_staff_id = ra.id
                 LEFT JOIN treatment_types tt ON pr.treatment_type_id = tt.id
                 WHERE pr.from_branch_id = ?
                 ORDER BY pr.created_at DESC"
            );
            
            $stmt->bind_param("i", $branch_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $referrals = [];
            while ($row = $result->fetch_assoc()) {
                $referrals[] = $row;
            }
            
            return [
                'success' => true,
                'referrals' => $referrals,
                'count' => count($referrals)
            ];
            
        } catch (Exception $e) {
            error_log("ReferralController::getSentReferrals - " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to load sent referrals'];
        }
    }
    
    /**
     * Patient approves a referral - moves status to 'patient_approved'
     */
    public function approveReferralByPatient() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['success' => false, 'message' => 'Invalid request method'];
        }
        
        try {
            $patient_id = getSessionUserId();
            $role = getSessionRole();
            
            if ($role !== ROLE_PATIENT) {
                return ['success' => false, 'message' => 'Access restricted to patients'];
            }
            
            $referral_id = $_POST['referral_id'] ?? 0;
            $patient_notes = $_POST['patient_notes'] ?? '';
            
            if (!$referral_id) {
                return ['success' => false, 'message' => 'Referral ID is required'];
            }
            
            // Begin transaction
            $this->db->begin_transaction();
            
            try {
                // Verify the referral belongs to this patient and is awaiting their approval
                $referralStmt = $this->db->prepare(
                    "SELECT pr.*, fb.name as from_branch_name, tb.name as to_branch_name, tt.name as treatment_name
                     FROM patient_referrals pr
                     LEFT JOIN branches fb ON pr.from_branch_id = fb.id
                     LEFT JOIN branches tb ON pr.to_branch_id = tb.id
                     LEFT JOIN treatment_types tt ON pr.treatment_type_id = tt.id
                     WHERE pr.id = ? AND pr.patient_id = ? AND pr.status = 'pending_patient_approval'"
                );
                $referralStmt->bind_param("ii", $referral_id, $patient_id);
                $referralStmt->execute();
                $referralResult = $referralStmt->get_result();
                
                if ($referralResult->num_rows === 0) {
                    throw new Exception('Referral not found or not awaiting your approval');
                }
                
                $referral = $referralResult->fetch_assoc();
                
                // Update referral status to patient_approved
                $updateStmt = $this->db->prepare(
                    "UPDATE patient_referrals 
                     SET status = 'patient_approved', 
                         patient_approved_at = NOW(),
                         patient_response_notes = ?,
                         updated_at = NOW()
                     WHERE id = ?"
                );
                $updateStmt->bind_param("si", $patient_notes, $referral_id);
                
                if (!$updateStmt->execute()) {
                    throw new Exception('Failed to approve referral');
                }

                // Update appointment history if there's an original appointment
                if (!empty($referral['original_appointment_id'])) {
                    $this->updateAppointmentHistory(
                        $referral['original_appointment_id'],
                        $patient_id,
                        'referral_approved_by_patient',
                        "Patient approved referral to {$referral['to_branch_name']} for {$referral['treatment_name']}. Notes: " . ($patient_notes ?: 'None provided')
                    );
                }

                $this->db->commit();                // Log activity
                $this->logActivity(
                    $patient_id,
                    'referral_approved_by_patient',
                    "Patient approved referral #$referral_id to {$referral['to_branch_name']}"
                );
                
                return [
                    'success' => true,
                    'message' => "Referral approved successfully! Your referral to {$referral['to_branch_name']} is now pending their approval.",
                    'referral_id' => $referral_id,
                    'next_step' => 'pending_branch_approval'
                ];
                
            } catch (Exception $e) {
                $this->db->rollback();
                throw $e;
            }
            
        } catch (Exception $e) {
            error_log("ReferralController::approveReferralByPatient - " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to approve referral: ' . $e->getMessage()];
        }
    }
    
    /**
     * Patient rejects a referral - moves status to 'patient_rejected'
     */
    public function rejectReferralByPatient() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['success' => false, 'message' => 'Invalid request method'];
        }
        
        try {
            $patient_id = getSessionUserId();
            $role = getSessionRole();
            
            if ($role !== ROLE_PATIENT) {
                return ['success' => false, 'message' => 'Access restricted to patients'];
            }
            
            $referral_id = $_POST['referral_id'] ?? 0;
            $rejection_reason = $_POST['rejection_reason'] ?? '';
            
            // Debug logging to help identify the issue
            error_log("=== DEBUG: rejectReferralByPatient - POST data: " . print_r($_POST, true) . " ===");
            error_log("=== DEBUG: rejectReferralByPatient - referral_id: " . $referral_id . " ===");
            error_log("=== DEBUG: rejectReferralByPatient - rejection_reason: '" . $rejection_reason . "' ===");
            
            if (!$referral_id || empty($rejection_reason)) {
                $error_details = [];
                if (!$referral_id) {
                    $error_details[] = "Referral ID is missing or invalid (received: " . var_export($referral_id, true) . ")";
                }
                if (empty($rejection_reason)) {
                    $error_details[] = "Rejection reason is empty (received: '" . $rejection_reason . "')";
                }
                return ['success' => false, 'message' => 'Referral ID and rejection reason are required. Details: ' . implode(', ', $error_details)];
            }
            
            // Begin transaction
            $this->db->begin_transaction();
            
            try {
                // Verify the referral belongs to this patient and is awaiting their approval
                $referralStmt = $this->db->prepare(
                    "SELECT pr.*, fb.name as from_branch_name, tb.name as to_branch_name, tt.name as treatment_name
                     FROM patient_referrals pr
                     LEFT JOIN branches fb ON pr.from_branch_id = fb.id
                     LEFT JOIN branches tb ON pr.to_branch_id = tb.id
                     LEFT JOIN treatment_types tt ON pr.treatment_type_id = tt.id
                     WHERE pr.id = ? AND pr.patient_id = ? AND pr.status = 'pending_patient_approval'"
                );
                $referralStmt->bind_param("ii", $referral_id, $patient_id);
                $referralStmt->execute();
                $referralResult = $referralStmt->get_result();
                
                if ($referralResult->num_rows === 0) {
                    throw new Exception('Referral not found or not awaiting your approval');
                }
                
                $referral = $referralResult->fetch_assoc();
                
                // Update referral status to patient_rejected
                $updateStmt = $this->db->prepare(
                    "UPDATE patient_referrals 
                     SET status = 'patient_rejected', 
                         patient_rejected_at = NOW(),
                         patient_response_notes = ?,
                         updated_at = NOW()
                     WHERE id = ?"
                );
                $updateStmt->bind_param("si", $rejection_reason, $referral_id);
                
                if (!$updateStmt->execute()) {
                    throw new Exception('Failed to reject referral');
                }
                
                // Reset the original appointment status back to its previous state
                // since the patient rejected the referral
                if ($referral['original_appointment_id']) {
                    $resetAppointmentStmt = $this->db->prepare(
                        "UPDATE appointments 
                         SET status = 'pending',
                             notes = CONCAT(COALESCE(notes, ''), '\n\nREFERRAL REJECTED: Patient declined referral to {$referral['to_branch_name']}. Appointment restored to pending status.')
                         WHERE id = ?"
                    );
                    $resetAppointmentStmt->bind_param("i", $referral['original_appointment_id']);
                    $resetAppointmentStmt->execute();

                    // Update appointment history for the original appointment
                    $this->updateAppointmentHistory(
                        $referral['original_appointment_id'],
                        $patient_id,
                        'referral_rejected_by_patient',
                        "Patient declined referral to {$referral['to_branch_name']}. Reason: $rejection_reason. Appointment restored to pending status."
                    );
                }

                $this->db->commit();                // Log activity
                $this->logActivity(
                    $patient_id,
                    'referral_rejected_by_patient',
                    "Patient rejected referral #$referral_id to {$referral['to_branch_name']}. Reason: $rejection_reason"
                );
                
                return [
                    'success' => true,
                    'message' => "Referral declined. Your original appointment has been restored and your referring clinic will explore other options.",
                    'referral_id' => $referral_id,
                    'next_step' => 'appointment_restored'
                ];
                
            } catch (Exception $e) {
                $this->db->rollback();
                throw $e;
            }
            
        } catch (Exception $e) {
            error_log("ReferralController::rejectReferralByPatient - " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to reject referral: ' . $e->getMessage()];
        }
    }
    
    /**
     * Accept a patient referral
     */
    public function acceptReferral() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['success' => false, 'message' => 'Invalid request method'];
        }
        
        try {
            $staff_id = getSessionUserId();
            $branch_id = getSessionBranchId();
            $role = getSessionRole();
            
            if (!in_array($role, [ROLE_STAFF, ROLE_ADMIN])) {
                return ['success' => false, 'message' => 'Unauthorized access'];
            }
            
            $referral_id = $_POST['referral_id'] ?? 0;
            $appointment_date = $_POST['appointment_date'] ?? '';
            $appointment_time = $_POST['appointment_time'] ?? '';
            $notes = $_POST['notes'] ?? '';
            
            if (!$referral_id || empty($appointment_date) || empty($appointment_time)) {
                return ['success' => false, 'message' => 'Missing required fields'];
            }
            
            // Begin transaction
            $this->db->begin_transaction();
            
            try {
                // Get referral details
                $referralStmt = $this->db->prepare(
                    "SELECT pr.*, p.name as patient_name, p.branch_id as patient_branch,
                     fb.name as from_branch_name, tb.name as to_branch_name
                     FROM patient_referrals pr
                     JOIN users p ON pr.patient_id = p.id
                     LEFT JOIN branches fb ON pr.from_branch_id = fb.id
                     LEFT JOIN branches tb ON pr.to_branch_id = tb.id
                     WHERE pr.id = ? AND pr.to_branch_id = ? AND pr.status = 'patient_approved'"
                );
                $referralStmt->bind_param("ii", $referral_id, $branch_id);
                $referralStmt->execute();
                $referralResult = $referralStmt->get_result();
                
                if ($referralResult->num_rows === 0) {
                    throw new Exception('Referral not found or already processed');
                }
                
                $referral = $referralResult->fetch_assoc();
                
                // Default duration for referral appointments (can be adjusted by staff)
                $default_duration = 60; // 1 hour default
                
                // Calculate end time
                $start_datetime = new DateTime("$appointment_date $appointment_time");
                $end_datetime = clone $start_datetime;
                $end_datetime->add(new DateInterval("PT{$default_duration}M"));
                $end_time = $end_datetime->format('H:i');
                
                // Check for time conflicts
                $conflictStmt = $this->db->prepare(
                    "SELECT COUNT(*) as conflict_count FROM appointments 
                     WHERE branch_id = ? AND appointment_date = ? 
                     AND status NOT IN ('cancelled', 'rejected', 'referred')
                     AND (
                         (appointment_time < ? AND end_time > ?) OR
                         (appointment_time < ? AND end_time > ?) OR
                         (appointment_time >= ? AND appointment_time < ?)
                     )"
                );
                
                $conflictStmt->bind_param(
                    "isssssss",
                    $branch_id, $appointment_date,
                    $end_time, $appointment_time,
                    $end_time, $appointment_time,
                    $appointment_time, $end_time
                );
                
                $conflictStmt->execute();
                $conflictResult = $conflictStmt->get_result();
                $conflict_count = $conflictResult->fetch_assoc()['conflict_count'];
                
                if ($conflict_count > 0) {
                    throw new Exception('Time slot conflicts with existing appointments');
                }
                
                // NOTE: Patient's branch_id remains unchanged - referrals are temporary consultations
                // The patient stays registered to their original branch
                
                // Create appointment at receiving branch
                $appointmentStmt = $this->db->prepare(
                    "INSERT INTO appointments 
                     (patient_id, staff_id, branch_id, treatment_type_id, appointment_date, appointment_time, end_time,
                      duration_minutes, status, notes)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'approved', ?)"
                );
                
                $appointment_notes = "Referral appointment. " . ($notes ? $notes : "From referral: " . $referral['reason']);
                
                // Use the treatment type from the referral, or default to null for general consultation
                $treatment_type_for_appointment = $referral['treatment_type_id'];
                
                $appointmentStmt->bind_param(
                    "iiissssis",
                    $referral['patient_id'], $staff_id, $branch_id, $treatment_type_for_appointment,
                    $appointment_date, $appointment_time, $end_time,
                    $default_duration, $appointment_notes
                );
                
                if (!$appointmentStmt->execute()) {
                    throw new Exception('Failed to create appointment');
                }
                
                $appointment_id = $this->db->insert_id;
                
                // Record appointment history for the new referral appointment
                $this->recordAppointmentHistory(
                    $appointment_id, 
                    $referral['patient_id'], 
                    'created', 
                    "Appointment created from referral #$referral_id",
                    $staff_id,
                    $referral['reason']
                );
                
                // Create time blocks
                $this->createTimeBlocks($branch_id, $appointment_date, $appointment_time, $end_time, $appointment_id, $staff_id);
                
                // Mark original appointment as referred (if exists)
                $this->markOriginalAppointmentAsReferred($referral['patient_id'], $referral['from_branch_id'], $referral_id);
                
                // Update referral status
                $updateReferralStmt = $this->db->prepare(
                    "UPDATE patient_referrals 
                     SET status = 'accepted', responding_staff_id = ?, responded_at = NOW(), new_appointment_id = ?
                     WHERE id = ?"
                );
                $updateReferralStmt->bind_param("iii", $staff_id, $appointment_id, $referral_id);
                
                if (!$updateReferralStmt->execute()) {
                    throw new Exception('Failed to update referral status');
                }

                // Update appointment history for the original appointment (if exists)
                if (!empty($referral['original_appointment_id'])) {
                    $this->updateAppointmentHistory(
                        $referral['original_appointment_id'],
                        $referral['patient_id'],
                        'referral_accepted',
                        "Referral to {$referral['to_branch_name']} was accepted. New appointment #$appointment_id scheduled for $appointment_date at $appointment_time"
                    );
                }

                // Commit the transaction
                $this->db->commit();
                
                // Log activity
                $this->logActivity(
                    $staff_id,
                    'referral_accepted',
                    "Accepted referral #$referral_id for patient {$referral['patient_name']} - Appointment #$appointment_id created"
                );
                
                return [
                    'success' => true,
                    'message' => "Referral accepted successfully. Appointment created for {$referral['patient_name']} on " . date('M j, Y', strtotime($appointment_date)) . " at " . date('g:i A', strtotime($appointment_time)),
                    'appointment_id' => $appointment_id
                ];
                
            } catch (Exception $e) {
                $this->db->rollback();
                throw $e;
            }
            
        } catch (Exception $e) {
            error_log("ReferralController::acceptReferral - " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to accept referral: ' . $e->getMessage()];
        }
    }
    
    /**
     * Reject a patient referral
     */
    public function rejectReferral() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['success' => false, 'message' => 'Invalid request method'];
        }
        
        try {
            $staff_id = getSessionUserId();
            $branch_id = getSessionBranchId();
            $role = getSessionRole();
            
            if (!in_array($role, [ROLE_STAFF, ROLE_ADMIN])) {
                return ['success' => false, 'message' => 'Unauthorized access'];
            }
            
            $referral_id = $_POST['referral_id'] ?? 0;
            $rejection_reason = $_POST['rejection_reason'] ?? '';
            
            // Debug logging to help identify the issue
            error_log("=== DEBUG: rejectReferral (staff) - POST data: " . print_r($_POST, true) . " ===");
            error_log("=== DEBUG: rejectReferral (staff) - referral_id: " . $referral_id . " ===");
            error_log("=== DEBUG: rejectReferral (staff) - rejection_reason: '" . $rejection_reason . "' ===");
            
            if (!$referral_id || empty($rejection_reason)) {
                $error_details = [];
                if (!$referral_id) {
                    $error_details[] = "Referral ID is missing or invalid (received: " . var_export($referral_id, true) . ")";
                }
                if (empty($rejection_reason)) {
                    $error_details[] = "Rejection reason is empty (received: '" . $rejection_reason . "')";
                }
                return ['success' => false, 'message' => 'Referral ID and rejection reason are required. Details: ' . implode(', ', $error_details)];
            }
            
            // Update referral status
            $stmt = $this->db->prepare(
                "UPDATE patient_referrals 
                 SET status = 'rejected', response_notes = CONCAT(COALESCE(response_notes, ''), '\n\nRejection Reason: ', ?), responding_staff_id = ?, responded_at = NOW()
                 WHERE id = ? AND to_branch_id = ? AND status = 'patient_approved'"
            );
            $stmt->bind_param("siii", $rejection_reason, $staff_id, $referral_id, $branch_id);
            
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $this->logActivity(
                    $staff_id,
                    'referral_rejected',
                    "Rejected referral #$referral_id. Reason: $rejection_reason"
                );
                
                return [
                    'success' => true,
                    'message' => 'Referral rejected successfully'
                ];
            } else {
                return ['success' => false, 'message' => 'Referral not found or already processed'];
            }
            
        } catch (Exception $e) {
            error_log("ReferralController::rejectReferral - " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to reject referral'];
        }
    }
    
    /**
     * Get available branches for referral (excluding current branch)
     */
    public function getAvailableBranches() {
        try {
            $current_branch_id = getSessionBranchId();
            
            $stmt = $this->db->prepare(
                "SELECT id, name, location FROM branches 
                 WHERE id != ? AND status = 'active'
                 ORDER BY name"
            );
            $stmt->bind_param("i", $current_branch_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $branches = [];
            while ($row = $result->fetch_assoc()) {
                $branches[] = $row;
            }
            
            return [
                'success' => true,
                'branches' => $branches
            ];
            
        } catch (Exception $e) {
            error_log("ReferralController::getAvailableBranches - " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to load branches'];
        }
    }
    
    /**
     * Get available treatments for a specific branch
     */
    public function getBranchTreatments() {
        try {
            $branch_id = $_GET['branch_id'] ?? 0;
            
            if (!$branch_id) {
                return ['success' => false, 'message' => 'Branch ID is required'];
            }
            
            $stmt = $this->db->prepare(
                "SELECT tt.id, tt.name, tt.description, tt.duration_minutes, bs.price
                 FROM treatment_types tt
                 JOIN branch_services bs ON tt.id = bs.treatment_type_id
                 WHERE bs.branch_id = ? AND bs.is_available = 1 AND tt.is_active = 1
                 ORDER BY tt.name"
            );
            $stmt->bind_param("i", $branch_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $treatments = [];
            while ($row = $result->fetch_assoc()) {
                $treatments[] = $row;
            }
            
            return [
                'success' => true,
                'treatments' => $treatments
            ];
            
        } catch (Exception $e) {
            error_log("ReferralController::getBranchTreatments - " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to load treatments'];
        }
    }
    
    /**
     * Get patients from current branch for referral
     */
    public function getBranchPatients() {
        try {
            $branch_id = getSessionBranchId();
            
            $stmt = $this->db->prepare(
                "SELECT id, name, email, phone FROM users 
                 WHERE branch_id = ? AND role = 'patient' AND status = 'active'
                 ORDER BY name"
            );
            $stmt->bind_param("i", $branch_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $patients = [];
            while ($row = $result->fetch_assoc()) {
                $patients[] = $row;
            }
            
            return [
                'success' => true,
                'patients' => $patients
            ];
            
        } catch (Exception $e) {
            error_log("ReferralController::getBranchPatients - " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to load patients'];
        }
    }
    
    /**
     * Mark original appointment as referred
     */
    private function markOriginalAppointmentAsReferred($patient_id, $from_branch_id, $referral_id) {
        // First get the appointment ID that will be updated
        $get_stmt = $this->db->prepare(
            "SELECT id FROM appointments 
             WHERE patient_id = ? AND branch_id = ? AND status IN ('pending', 'approved')
             ORDER BY created_at DESC LIMIT 1"
        );
        $get_stmt->bind_param("ii", $patient_id, $from_branch_id);
        $get_stmt->execute();
        $result = $get_stmt->get_result();
        
        if ($appointment = $result->fetch_assoc()) {
            $appointment_id = $appointment['id'];
            
            // Update the appointment status
            $stmt = $this->db->prepare(
                "UPDATE appointments 
                 SET status = 'referred', notes = CONCAT(COALESCE(notes, ''), '\n\nPatient referred to another branch via referral #', ?)
                 WHERE id = ?"
            );
            $stmt->bind_param("ii", $referral_id, $appointment_id);
            $stmt->execute();
            
            // Record appointment history
            $referring_staff_id = getSessionUserId();
            $this->recordAppointmentHistory(
                $appointment_id, 
                $patient_id, 
                'referred', 
                "Patient referred to another branch via referral #$referral_id",
                $referring_staff_id,
                "Referral to different branch for specialized care"
            );
        }
    }
    
    /**
     * Create time blocks for appointment
     */
    private function createTimeBlocks($branch_id, $appointment_date, $start_time, $end_time, $appointment_id, $blocked_by) {
        $blockStmt = $this->db->prepare(
            "INSERT INTO appointment_time_blocks 
             (branch_id, appointment_date, start_time, end_time, appointment_id, staff_id, is_blocked, block_reason) 
             VALUES (?, ?, ?, ?, ?, ?, TRUE, 'Auto-blocked for referred patient appointment')"
        );
        
        $blockStmt->bind_param("issiii", $branch_id, $appointment_date, $start_time, $end_time, $appointment_id, $blocked_by);
        
        if (!$blockStmt->execute()) {
            throw new Exception("Failed to create time blocks: " . $this->db->error);
        }
    }
    
    /**
     * Log activity
     */
    private function logActivity($user_id, $action, $description) {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO system_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)"
            );
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $stmt->bind_param("isss", $user_id, $action, $description, $ip_address);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("Failed to log activity: " . $e->getMessage());
        }
    }
    
    /**
     * Get patient's current referral status
     */
    public function getPatientReferralStatus() {
        error_log("=== DEBUG: getPatientReferralStatus method called ===");
        
        try {
            $patient_id = getSessionUserId();
            error_log("=== DEBUG: Patient ID from session: " . ($patient_id ?: 'NULL') . " ===");
            
            if (!$patient_id) {
                error_log("=== DEBUG: No patient ID found in session ===");
                return ['success' => false, 'message' => 'Patient session not found'];
            }
            
            // Check if user is a patient
            $role = getSessionRole();
            error_log("=== DEBUG: User role: " . ($role ?: 'NULL') . " ===");
            
            if ($role !== ROLE_PATIENT) {
                error_log("=== DEBUG: User is not a patient, role: " . $role . " ===");
                return ['success' => false, 'message' => 'Access restricted to patients'];
            }
            
            // Get the most recent ACTIVE referral (not completed or cancelled) for this patient
            $query = "
                SELECT 
                    pr.*,
                    pr.clinical_notes as notes,
                    from_branch.name as from_branch_name,
                    to_branch.name as to_branch_name,
                    tt.name as treatment_name,
                    
                    -- Original appointment pricing information
                    orig_appt.id as original_appointment_id,
                    orig_tt.name as original_treatment_name,
                    orig_bs.price as original_treatment_price,
                    orig_branch.name as original_branch_name,
                    
                    -- New appointment pricing information
                    new_appt.id as new_appointment_id,
                    new_bs.price as new_treatment_price,
                    
                    CASE 
                        WHEN pr.responded_at IS NOT NULL THEN a.appointment_date
                        ELSE NULL
                    END as appointment_date,
                    CASE 
                        WHEN pr.responded_at IS NOT NULL THEN a.appointment_time
                        ELSE NULL
                    END as appointment_time,
                    CASE 
                        WHEN pr.status = 'completed' THEN pr.completed_at
                        ELSE pr.updated_at
                    END as completion_date,
                    CASE 
                        WHEN pr.status = 'rejected' THEN pr.response_notes
                        ELSE NULL
                    END as rejection_reason,
                    CASE 
                        WHEN pr.status = 'cancelled' THEN pr.cancellation_reason
                        ELSE NULL
                    END as cancellation_reason
                FROM patient_referrals pr
                LEFT JOIN branches from_branch ON pr.from_branch_id = from_branch.id
                LEFT JOIN branches to_branch ON pr.to_branch_id = to_branch.id
                LEFT JOIN treatment_types tt ON pr.treatment_type_id = tt.id
                
                -- Original appointment information
                LEFT JOIN appointments orig_appt ON pr.original_appointment_id = orig_appt.id
                LEFT JOIN treatment_types orig_tt ON orig_appt.treatment_type_id = orig_tt.id
                LEFT JOIN branches orig_branch ON orig_appt.branch_id = orig_branch.id
                LEFT JOIN branch_services orig_bs ON (orig_appt.branch_id = orig_bs.branch_id AND orig_appt.treatment_type_id = orig_bs.treatment_type_id)
                
                -- New appointment information
                LEFT JOIN appointments new_appt ON pr.new_appointment_id = new_appt.id
                LEFT JOIN branch_services new_bs ON (new_appt.branch_id = new_bs.branch_id AND new_appt.treatment_type_id = new_bs.treatment_type_id)
                
                LEFT JOIN appointments a ON pr.new_appointment_id = a.id
                WHERE pr.patient_id = ? 
                AND pr.status IN ('pending_patient_approval', 'patient_approved', 'patient_rejected', 'pending', 'accepted', 'rejected', 'cancelled', 'completed')
                AND (pr.patient_hidden_at IS NULL)
                ORDER BY 
                    CASE 
                        WHEN pr.status IN ('pending_patient_approval', 'patient_approved', 'accepted') THEN 0
                        WHEN pr.status IN ('patient_rejected', 'rejected', 'cancelled') THEN 1
                        WHEN pr.status = 'completed' THEN 2
                    END,
                    pr.created_at DESC
                LIMIT 1";
            
            error_log("=== DEBUG: Executing query for patient_id: " . $patient_id . " ===");
            error_log("=== DEBUG: SQL Query: " . $query . " ===");
            
            $stmt = $this->db->prepare($query);
            if (!$stmt) {
                throw new Exception("Failed to prepare query: " . $this->db->error);
            }
            
            $stmt->bind_param('i', $patient_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to execute query: " . $stmt->error);
            }
            
            $result = $stmt->get_result();
            error_log("=== DEBUG: Query executed successfully, rows found: " . $result->num_rows . " ===");
            
            if ($referral = $result->fetch_assoc()) {
                error_log("=== DEBUG: Referral found: " . print_r($referral, true) . " ===");
                return [
                    'success' => true,
                    'hasReferral' => true,
                    'referral' => $referral
                ];
            } else {
                error_log("=== DEBUG: No referral found for patient_id: " . $patient_id . " ===");
                return [
                    'success' => true,
                    'hasReferral' => false,
                    'message' => 'No referrals found'
                ];
            }
            
        } catch (Exception $e) {
            error_log("=== DEBUG: Exception in getPatientReferralStatus: " . $e->getMessage() . " ===");
            error_log("=== DEBUG: Exception trace: " . $e->getTraceAsString() . " ===");
            return [
                'success' => false, 
                'message' => 'Database error occurred',
                'debug' => $e->getMessage() // Show debug info for now
            ];
        }
    }
    
    /**
     * Complete a referral when treatment is finished at referred branch
     */
    public function completeReferral() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['success' => false, 'message' => 'Invalid request method'];
        }
        
        try {
            $staff_id = getSessionUserId();
            $branch_id = getSessionBranchId();
            $role = getSessionRole();
            
            if (!in_array($role, [ROLE_STAFF, ROLE_ADMIN])) {
                return ['success' => false, 'message' => 'Unauthorized access'];
            }
            
            $referral_id = $_POST['referral_id'] ?? 0;
            $appointment_id = $_POST['appointment_id'] ?? 0;
            $completion_notes = $_POST['completion_notes'] ?? '';
            
            if (!$referral_id) {
                return ['success' => false, 'message' => 'Referral ID is required'];
            }
            
            // Begin transaction
            $this->db->begin_transaction();
            
            try {
                // Verify the referral belongs to this branch and is accepted
                $referralStmt = $this->db->prepare(
                    "SELECT pr.*, p.name as patient_name
                     FROM patient_referrals pr
                     JOIN users p ON pr.patient_id = p.id
                     WHERE pr.id = ? AND pr.to_branch_id = ? AND pr.status = 'accepted'"
                );
                $referralStmt->bind_param("ii", $referral_id, $branch_id);
                $referralStmt->execute();
                $referralResult = $referralStmt->get_result();
                
                if ($referralResult->num_rows === 0) {
                    throw new Exception('Referral not found or not in accepted status');
                }
                
                $referral = $referralResult->fetch_assoc();
                
                // Mark the appointment as completed if appointment_id is provided
                if ($appointment_id) {
                    error_log("DEBUG completeReferral: appointment_id = $appointment_id, branch_id = $branch_id");
                    
                    // IMPORTANT: Record appointment history BEFORE updating status to preserve state
                    $this->recordAppointmentHistory(
                        $appointment_id, 
                        $referral['patient_id'], 
                        'completed', 
                        "Referral treatment completed. " . $completion_notes,
                        $staff_id,
                        "Referral #$referral_id completed at receiving branch"
                    );
                    
                    // Now update the appointment status
                    $updateAppointmentStmt = $this->db->prepare(
                        "UPDATE appointments 
                         SET status = 'completed', 
                             notes = CONCAT(COALESCE(notes, ''), '\n\nReferral treatment completed. ', ?)
                         WHERE id = ? AND branch_id = ?"
                    );
                    $updateAppointmentStmt->bind_param("sii", $completion_notes, $appointment_id, $branch_id);
                    
                    if (!$updateAppointmentStmt->execute()) {
                        error_log("DEBUG completeReferral: Failed to execute update statement: " . $this->db->error);
                        throw new Exception('Failed to update appointment status');
                    }
                    
                    $affected_rows = $updateAppointmentStmt->affected_rows;
                    error_log("DEBUG completeReferral: Update affected $affected_rows rows");
                    
                    if ($affected_rows === 0) {
                        error_log("DEBUG completeReferral: No rows updated - appointment $appointment_id not found in branch $branch_id");
                        // Let's check if the appointment exists at all
                        $checkStmt = $this->db->prepare("SELECT id, branch_id, status FROM appointments WHERE id = ?");
                        $checkStmt->bind_param("i", $appointment_id);
                        $checkStmt->execute();
                        $checkResult = $checkStmt->get_result();
                        if ($checkRow = $checkResult->fetch_assoc()) {
                            error_log("DEBUG completeReferral: Appointment exists: id={$checkRow['id']}, branch_id={$checkRow['branch_id']}, status={$checkRow['status']}");
                        } else {
                            error_log("DEBUG completeReferral: Appointment $appointment_id does not exist");
                        }
                    }
                } else {
                    error_log("DEBUG completeReferral: appointment_id not provided in POST data");
                }
                
                // Update referral status to completed
                $updateReferralStmt = $this->db->prepare(
                    "UPDATE patient_referrals 
                     SET status = 'completed', 
                         completed_at = NOW(),
                         completion_notes = ?,
                         completing_staff_id = ?
                     WHERE id = ?"
                );
                $updateReferralStmt->bind_param("sii", $completion_notes, $staff_id, $referral_id);
                
                if (!$updateReferralStmt->execute()) {
                    throw new Exception('Failed to update referral status');
                }
                
                $this->db->commit();
                
                // Log activity
                $this->logActivity(
                    $staff_id,
                    'referral_completed',
                    "Completed referral #$referral_id for patient {$referral['patient_name']} - Treatment finished"
                );
                
                return [
                    'success' => true,
                    'message' => "Referral completed successfully. Patient {$referral['patient_name']} treatment has been marked as completed.",
                    'referral_id' => $referral_id
                ];
                
            } catch (Exception $e) {
                $this->db->rollback();
                throw $e;
            }
            
        } catch (Exception $e) {
            error_log("ReferralController::completeReferral - " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to complete referral: ' . $e->getMessage()];
        }
    }
    
    /**
     * Cancel a referral 
     */
    public function cancelReferral() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['success' => false, 'message' => 'Invalid request method'];
        }
        
        try {
            $staff_id = getSessionUserId();
            $branch_id = getSessionBranchId();
            $role = getSessionRole();
            
            if (!in_array($role, [ROLE_STAFF, ROLE_ADMIN])) {
                return ['success' => false, 'message' => 'Unauthorized access'];
            }
            
            $referral_id = $_POST['referral_id'] ?? 0;
            $cancellation_reason = $_POST['cancellation_reason'] ?? '';
            
            if (!$referral_id || empty($cancellation_reason)) {
                return ['success' => false, 'message' => 'Referral ID and cancellation reason are required'];
            }
            
            // Begin transaction
            $this->db->begin_transaction();
            
            try {
                // Verify the referral exists and can be cancelled
                $referralStmt = $this->db->prepare(
                    "SELECT pr.*, p.name as patient_name
                     FROM patient_referrals pr
                     JOIN users p ON pr.patient_id = p.id
                     WHERE pr.id = ? AND (pr.from_branch_id = ? OR pr.to_branch_id = ?) 
                     AND pr.status IN ('pending', 'accepted')"
                );
                $referralStmt->bind_param("iii", $referral_id, $branch_id, $branch_id);
                $referralStmt->execute();
                $referralResult = $referralStmt->get_result();
                
                if ($referralResult->num_rows === 0) {
                    throw new Exception('Referral not found or cannot be cancelled');
                }
                
                $referral = $referralResult->fetch_assoc();
                
                // Cancel any associated appointment if exists
                if ($referral['new_appointment_id']) {
                    $cancelAppointmentStmt = $this->db->prepare(
                        "UPDATE appointments 
                         SET status = 'cancelled', 
                             notes = CONCAT(COALESCE(notes, ''), '\n\nReferral cancelled: ', ?)
                         WHERE id = ?"
                    );
                    $cancelAppointmentStmt->bind_param("si", $cancellation_reason, $referral['new_appointment_id']);
                    $cancelAppointmentStmt->execute();
                }
                
                // Update referral status to cancelled
                $updateReferralStmt = $this->db->prepare(
                    "UPDATE patient_referrals 
                     SET status = 'cancelled', 
                         cancelled_at = NOW(),
                         cancellation_reason = ?,
                         cancelling_staff_id = ?
                     WHERE id = ?"
                );
                $updateReferralStmt->bind_param("sii", $cancellation_reason, $staff_id, $referral_id);
                
                if (!$updateReferralStmt->execute()) {
                    throw new Exception('Failed to update referral status');
                }
                
                $this->db->commit();
                
                // Log activity
                $this->logActivity(
                    $staff_id,
                    'referral_cancelled',
                    "Cancelled referral #$referral_id for patient {$referral['patient_name']} - Reason: $cancellation_reason"
                );
                
                return [
                    'success' => true,
                    'message' => "Referral cancelled successfully.",
                    'referral_id' => $referral_id
                ];
                
            } catch (Exception $e) {
                $this->db->rollback();
                throw $e;
            }
            
        } catch (Exception $e) {
            error_log("ReferralController::cancelReferral - " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to cancel referral: ' . $e->getMessage()];
        }
    }
    
    /**
     * Clear completed referral from patient view (hide it)
     */
    public function hideCompletedReferral() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['success' => false, 'message' => 'Invalid request method'];
        }
        
        try {
            $patient_id = getSessionUserId();
            $role = getSessionRole();
            
            if ($role !== ROLE_PATIENT) {
                return ['success' => false, 'message' => 'Access restricted to patients'];
            }
            
            $referral_id = $_POST['referral_id'] ?? 0;
            
            if (!$referral_id) {
                return ['success' => false, 'message' => 'Referral ID is required'];
            }
            
            // Update referral to mark it as hidden for patient
            $stmt = $this->db->prepare(
                "UPDATE patient_referrals 
                 SET patient_hidden = 1, patient_hidden_at = NOW()
                 WHERE id = ? AND patient_id = ? AND status = 'completed'"
            );
            $stmt->bind_param("ii", $referral_id, $patient_id);
            
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                return [
                    'success' => true,
                    'message' => 'Referral status hidden successfully'
                ];
            } else {
                return ['success' => false, 'message' => 'Referral not found or cannot be hidden'];
            }
            
        } catch (Exception $e) {
            error_log("ReferralController::hideCompletedReferral - " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to hide referral'];
        }
    }
    
    /**
     * Get new referrals for notification system
     */
    public function getNewReferrals() {
        error_log("=== DEBUG: getNewReferrals called ===");
        
        try {
            $branch_id = getSessionBranchId();
            $role = getSessionRole();
            
            error_log("=== DEBUG: getNewReferrals - branch_id: " . $branch_id . ", role: " . $role . " ===");
            
            if (!in_array($role, [ROLE_STAFF, ROLE_ADMIN])) {
                error_log("=== DEBUG: getNewReferrals - Unauthorized access ===");
                return ['success' => false, 'message' => 'Unauthorized access'];
            }
            
            // Get timestamp parameter (milliseconds since epoch)
            $since_timestamp = $_GET['since'] ?? 0;
            
            // Convert milliseconds to seconds and then to MySQL datetime
            $since_datetime = date('Y-m-d H:i:s', intval($since_timestamp / 1000));
            
            error_log("=== DEBUG: getNewReferrals - since_timestamp: " . $since_timestamp . ", since_datetime: " . $since_datetime . " ===");
            
            $query = "SELECT pr.*, 
                        pr.clinical_notes as notes,
                        p.name as patient_name, p.email as patient_email,
                        fb.name as from_branch_name,
                        rs.name as referring_staff_name,
                        tt.name as treatment_name,
                        UNIX_TIMESTAMP(pr.created_at) * 1000 as created_timestamp
                 FROM patient_referrals pr
                 JOIN users p ON pr.patient_id = p.id
                 JOIN branches fb ON pr.from_branch_id = fb.id
                 LEFT JOIN users rs ON pr.from_staff_id = rs.id
                 LEFT JOIN treatment_types tt ON pr.treatment_type_id = tt.id
                 WHERE pr.to_branch_id = ? 
                 AND pr.status = 'patient_approved'
                 AND pr.updated_at >= ?
                 ORDER BY pr.patient_approved_at DESC
                 LIMIT 10";
            
            error_log("=== DEBUG: getNewReferrals - Query: " . $query . " ===");
            
            $stmt = $this->db->prepare($query);
            if (!$stmt) {
                throw new Exception("Failed to prepare query: " . $this->db->error);
            }
            
            $stmt->bind_param("is", $branch_id, $since_datetime);
            if (!$stmt->execute()) {
                throw new Exception("Failed to execute query: " . $stmt->error);
            }
            
            $result = $stmt->get_result();
            error_log("=== DEBUG: getNewReferrals - Found " . $result->num_rows . " new referrals ===");
            
            $referrals = [];
            while ($row = $result->fetch_assoc()) {
                error_log("=== DEBUG: getNewReferrals - Referral ID: " . $row['id'] . ", status: " . $row['status'] . " ===");
                $referrals[] = $row;
            }
            
            return [
                'success' => true,
                'referrals' => $referrals,
                'count' => count($referrals)
            ];
            
        } catch (Exception $e) {
            error_log("=== DEBUG: getNewReferrals Exception: " . $e->getMessage() . " ===");
            error_log("=== DEBUG: getNewReferrals Trace: " . $e->getTraceAsString() . " ===");
            return [
                'success' => false, 
                'message' => 'Failed to load new referrals',
                'debug' => $e->getMessage() // Show debug info for now
            ];
        }
    }
    
    /**
     * Get patient referral updates for polling - returns updates since given timestamp
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
            
            $sql = "SELECT pr.*, 
                           p.name as patient_name, 
                           fb.name as from_branch_name,
                           tb.name as to_branch_name,
                           rs.name as referring_staff_name,
                           tt.name as treatment_name,
                           pr.referral_date, pr.updated_at
                    FROM patient_referrals pr
                    JOIN users p ON pr.patient_id = p.id
                    JOIN branches fb ON pr.from_branch_id = fb.id
                    JOIN branches tb ON pr.to_branch_id = tb.id
                    JOIN users rs ON pr.from_staff_id = rs.id
                    LEFT JOIN treatment_types tt ON pr.treatment_type_id = tt.id
                    WHERE pr.patient_id = ? AND pr.updated_at > ?
                    ORDER BY pr.updated_at DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("is", $user_id, $since_datetime);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $referrals = array();
            while ($row = $result->fetch_assoc()) {
                $referrals[] = $row;
            }
            
            return array(
                'success' => true,
                'referrals' => $referrals,
                'count' => count($referrals)
            );
            
        } catch (Exception $e) {
            error_log("Get patient referral updates error: " . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Failed to check for patient referral updates: ' . $e->getMessage(),
                'referrals' => array()
            );
        }
    }
    
    /**
     * Get detailed information about a specific referral
     */
    public function getReferralDetails() {
        try {
            $branch_id = getSessionBranchId();
            $role = getSessionRole();
            
            if (!in_array($role, [ROLE_STAFF, ROLE_ADMIN])) {
                return ['success' => false, 'message' => 'Unauthorized access'];
            }
            
            $referral_id = $_GET['referral_id'] ?? 0;
            
            if (!$referral_id) {
                return ['success' => false, 'message' => 'Referral ID is required'];
            }
            
            $stmt = $this->db->prepare(
                "SELECT pr.*, 
                        p.name as patient_name, p.email as patient_email, p.phone as patient_phone,
                        fb.name as from_branch_name, fb.location as from_branch_location,
                        tb.name as to_branch_name, tb.location as to_branch_location,
                        rs.name as referring_staff_name,
                        resp_staff.name as responding_staff_name,
                        tt.name as treatment_name, tt.description as treatment_description,
                        tt.duration_minutes as treatment_duration,
                        a.appointment_date, a.appointment_time, a.end_time,
                        a.status as appointment_status
                 FROM patient_referrals pr
                 JOIN users p ON pr.patient_id = p.id
                 JOIN branches fb ON pr.from_branch_id = fb.id
                 JOIN branches tb ON pr.to_branch_id = tb.id
                 JOIN users rs ON pr.from_staff_id = rs.id
                 LEFT JOIN users resp_staff ON pr.responding_staff_id = resp_staff.id
                 LEFT JOIN treatment_types tt ON pr.treatment_type_id = tt.id
                 LEFT JOIN appointments a ON pr.new_appointment_id = a.id
                 WHERE pr.id = ? AND (pr.from_branch_id = ? OR pr.to_branch_id = ?)"
            );
            
            $stmt->bind_param("iii", $referral_id, $branch_id, $branch_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($referral = $result->fetch_assoc()) {
                return [
                    'success' => true,
                    'referral' => $referral
                ];
            } else {
                return ['success' => false, 'message' => 'Referral not found or access denied'];
            }
            
        } catch (Exception $e) {
            error_log("ReferralController::getReferralDetails - " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to load referral details'];
        }
    }
    
    /**
     * Record appointment history for tracking appointment journey
     * FIXED: Now captures state at time of event, not current state
     */
    private function recordAppointmentHistory($appointment_id, $patient_id, $event_type, $event_description = null, $referring_staff_id = null, $referral_reason = null, $historical_data = null) {
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
                // Use historical data if provided, otherwise use current appointment data
                $data_to_record = $historical_data ?: $appointment;
                
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
                
                // Insert history record with preserved data
                $history_stmt = $this->db->prepare("
                    INSERT INTO appointment_history (
                        appointment_id, patient_id, sequence_number, event_type, event_description,
                        branch_id, branch_name, treatment_id, treatment_name, treatment_price,
                        appointment_date, appointment_time, referring_staff_id, referring_staff_name, referral_reason
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                // Assign values to variables for bind_param (required for pass-by-reference)
                $record_branch_id = $data_to_record['branch_id'] ?? $appointment['branch_id'];
                $record_branch_name = $data_to_record['branch_name'] ?? $appointment['branch_name'];
                $record_treatment_id = $data_to_record['treatment_type_id'] ?? $appointment['treatment_type_id'];
                $record_treatment_name = $data_to_record['treatment_name'] ?? $appointment['treatment_name'];
                $record_treatment_price = $data_to_record['treatment_price'] ?? $appointment['treatment_price'];
                $record_appointment_date = $data_to_record['appointment_date'] ?? $appointment['appointment_date'];
                $record_appointment_time = $data_to_record['appointment_time'] ?? $appointment['appointment_time'];
                $record_referring_staff_name = $appointment['referring_staff_name'];
                
                $history_stmt->bind_param(
                    "iiisssissssisss",
                    $appointment_id,
                    $patient_id, 
                    $sequence_number,
                    $event_type,
                    $event_description,
                    $record_branch_id,
                    $record_branch_name,
                    $record_treatment_id, // Maps to treatment_id column in table
                    $record_treatment_name,
                    $record_treatment_price,
                    $record_appointment_date,
                    $record_appointment_time,
                    $referring_staff_id,
                    $record_referring_staff_name,
                    $referral_reason
                );
                
                if ($history_stmt->execute()) {
                    error_log("Appointment history recorded in ReferralController: appointment_id=$appointment_id, event_type=$event_type, sequence=$sequence_number");
                    return true;
                } else {
                    error_log("Failed to record appointment history in ReferralController: " . $this->db->error);
                    return false;
                }
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Record appointment history error in ReferralController: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update appointment history with referral-related events
     */
    private function updateAppointmentHistory($appointment_id, $patient_id, $action, $message) {
        try {
            // Get appointment details for history context
            $stmt = $this->db->prepare("
                SELECT a.*, 
                       p.name as patient_name, p.email as patient_email,
                       b.name as branch_name, 
                       tt.name as treatment_type
                FROM appointments a
                LEFT JOIN users p ON a.patient_id = p.id
                LEFT JOIN branches b ON a.branch_id = b.id
                LEFT JOIN treatment_types tt ON a.treatment_type_id = tt.id
                WHERE a.id = ?
            ");
            $stmt->bind_param("i", $appointment_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($appointment = $result->fetch_assoc()) {
                // Insert into appointment_history using the correct schema
                $history_stmt = $this->db->prepare("
                    INSERT INTO appointment_history (
                        appointment_id, patient_name, patient_email, action, 
                        branch_name, treatment_type, appointment_date, appointment_time, 
                        changed_by_id, message
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $patient_name = $appointment['patient_name'];
                $patient_email = $appointment['patient_email'] ?: '';
                $branch_name = $appointment['branch_name'];
                $treatment_type = $appointment['treatment_type'];
                $appointment_date = $appointment['appointment_date'];
                $appointment_time = $appointment['appointment_time'];
                
                $history_stmt->bind_param(
                    "isssssssss",
                    $appointment_id,
                    $patient_name,
                    $patient_email,
                    $action,
                    $branch_name,
                    $treatment_type,
                    $appointment_date,
                    $appointment_time,
                    $patient_id,
                    $message
                );
                
                if ($history_stmt->execute()) {
                    error_log("Appointment history updated: appointment_id=$appointment_id, action=$action");
                    return true;
                } else {
                    error_log("Failed to update appointment history: " . $this->db->error);
                    return false;
                }
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Update appointment history error: " . $e->getMessage());
            return false;
        }
    }
}

// Handle API requests
if (php_sapi_name() !== 'cli') {
    // Set JSON header immediately to ensure all responses are JSON
    header('Content-Type: application/json');
    
    // Suppress PHP warnings/notices that could interfere with JSON response
    $original_error_reporting = error_reporting();
    error_reporting(E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR);
    
    // Capture any output and prevent it from interfering with JSON response
    ob_start();
    
    try {
        // Check if user is logged in
        if (!isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Please login to access this resource']);
            exit;
        }
        
        // Check for action in both GET and POST parameters
        $action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');
        error_log("=== DEBUG: Action received: " . ($action ?: 'EMPTY') . " ===");
        error_log("=== DEBUG: GET params: " . print_r($_GET, true) . " ===");
        
        $controller = new ReferralController();
        $response = [];
    
    switch ($action) {
        case 'test':
            error_log("=== DEBUG: Test endpoint called ===");
            http_response_code(200);
            $response = [
                'success' => true, 
                'message' => 'ReferralController endpoint is working',
                'timestamp' => date('Y-m-d H:i:s'),
                'user_id' => getSessionUserId(),
                'user_role' => getSessionRole()
            ];
            break;
            
        case 'createReferral':
            $response = $controller->createReferral();
            break;
            
        case 'createReferralFromAppointment':
            error_log("ReferralController: createReferralFromAppointment action called");
            error_log("POST data: " . print_r($_POST, true));
            $response = $controller->createReferralFromAppointment();
            error_log("Response: " . print_r($response, true));
            break;
            
        case 'getPendingReferrals':
            $response = $controller->getPendingReferrals();
            break;
            
        case 'getSentReferrals':
            $response = $controller->getSentReferrals();
            break;
            
        case 'acceptReferral':
            $response = $controller->acceptReferral();
            break;
            
        case 'rejectReferral':
            $response = $controller->rejectReferral();
            break;
            
        case 'getAvailableBranches':
            $response = $controller->getAvailableBranches();
            break;
            
        case 'getBranchTreatments':
            $response = $controller->getBranchTreatments();
            break;
            
        case 'getBranchPatients':
            $response = $controller->getBranchPatients();
            break;
            
        case 'getPatientReferralStatus':
            error_log("=== DEBUG: getPatientReferralStatus case matched ===");
            $response = $controller->getPatientReferralStatus();
            error_log("=== DEBUG: getPatientReferralStatus response: " . print_r($response, true) . " ===");
            break;
            
        case 'getPatientUpdates':
            $response = $controller->getPatientUpdates();
            break;
            
        case 'getNewReferrals':
            $response = $controller->getNewReferrals();
            break;
            
        case 'getReferralDetails':
            $response = $controller->getReferralDetails();
            break;
            
        case 'completeReferral':
            $response = $controller->completeReferral();
            break;
            
        case 'cancelReferral':
            $response = $controller->cancelReferral();
            break;
            
        case 'hideCompletedReferral':
            $response = $controller->hideCompletedReferral();
            break;
            
        case 'approveReferralByPatient':
            $response = $controller->approveReferralByPatient();
            break;
            
        case 'rejectReferralByPatient':
            $response = $controller->rejectReferralByPatient();
            break;
            
        default:
            http_response_code(400);
            $response = ['success' => false, 'message' => 'Invalid action'];
            break;
    }
    
    echo json_encode($response);
    
    } catch (Exception $e) {
        // Clean any unwanted output before sending error response
        if (ob_get_level()) {
            ob_clean();
        }
        
        // Restore original error reporting
        if (isset($original_error_reporting)) {
            error_reporting($original_error_reporting);
        }
        
        // Handle any PHP errors or exceptions
        error_log("ReferralController Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(array(
            'success' => false, 
            'message' => 'Server error occurred while processing request',
            'debug' => $e->getMessage() // Show debug info for now
        ));
    }
    
    // Clean the output buffer and restore error reporting
    if (ob_get_level()) {
        ob_end_flush();
    }
    
    // Restore original error reporting
    error_reporting($original_error_reporting);
    exit;
}
?>