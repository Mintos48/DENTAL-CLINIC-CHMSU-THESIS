<?php
/**
 * PrescriptionController - Handles prescription management for dentists
 */

if (!defined('API_ENDPOINT')) {
    define('API_ENDPOINT', true);
}

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/config/session.php';

class PrescriptionController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getConnection();
    }
    
    /**
     * Create a new prescription for an appointment
     */
    public function createPrescription() {
        try {
            // Verify user is dentist/staff/admin
            if (!isLoggedIn() || !in_array(getSessionRole(), [ROLE_DENTIST, ROLE_STAFF, ROLE_ADMIN])) {
                return ['success' => false, 'message' => 'Unauthorized access'];
            }
            
            // Get input data
            $input = json_decode(file_get_contents('php://input'), true);
            $appointment_id = $input['appointment_id'] ?? '';
            $diagnosis = $input['diagnosis'] ?? '';
            $instructions = $input['instructions'] ?? '';
            $medications = $input['medications'] ?? [];
            $follow_up_required = $input['follow_up_required'] ?? false;
            $follow_up_date = $input['follow_up_date'] ?? null;
            
            if (empty($appointment_id)) {
                return ['success' => false, 'message' => 'Appointment ID is required'];
            }
            
            // Verify appointment exists and belongs to user's branch
            $branch_id = getSessionBranchId();
            $stmt = $this->db->prepare("
                SELECT a.*, u.name as patient_name, u.email as patient_email 
                FROM appointments a 
                JOIN users u ON a.patient_id = u.id 
                WHERE a.id = ? AND a.branch_id = ? AND a.status IN ('approved', 'completed')
            ");
            $stmt->bind_param('ii', $appointment_id, $branch_id);
            $stmt->execute();
            $appointment = $stmt->get_result()->fetch_assoc();
            
            if (!$appointment) {
                return ['success' => false, 'message' => 'Appointment not found or not approved yet'];
            }
            
            // Check if prescription already exists
            $stmt = $this->db->prepare("SELECT id FROM prescriptions WHERE appointment_id = ?");
            $stmt->bind_param('i', $appointment_id);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();
            
            if ($existing) {
                return ['success' => false, 'message' => 'Prescription already exists for this appointment'];
            }
            
            $this->db->begin_transaction();
            
            // Create prescription
            $user_id = getSessionUserId();
            $branch_id = getSessionBranchId();
            
            $stmt = $this->db->prepare("
                INSERT INTO prescriptions 
                (appointment_id, patient_id, dentist_id, branch_id, diagnosis, instructions, follow_up_required, follow_up_date, status, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', ?)
            ");
            $stmt->bind_param('iiiiisisi', 
                $appointment_id, 
                $appointment['patient_id'], 
                $user_id, 
                $branch_id, 
                $diagnosis, 
                $instructions, 
                $follow_up_required, 
                $follow_up_date,
                $user_id
            );
            $stmt->execute();
            $prescription_id = $this->db->insert_id;
            
            // Add medications
            if (!empty($medications)) {
                $stmt = $this->db->prepare("
                    INSERT INTO prescription_medications 
                    (prescription_id, medication_name, dosage, form, frequency, duration, quantity, instructions, with_food, is_priority, sort_order) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                foreach ($medications as $index => $med) {
                    $medication_name = $med['medication_name'];
                    $dosage = $med['dosage'];
                    $form = $med['form'] ?? 'tablet';
                    $frequency = $med['frequency'];
                    $duration = $med['duration'];
                    $quantity = $med['quantity'] ?? '1';
                    $med_instructions = $med['instructions'] ?? '';
                    // Convert boolean to int, handle null
                    $with_food = isset($med['with_food']) ? (int)$med['with_food'] : 0;
                    $is_priority = isset($med['is_priority']) ? (int)$med['is_priority'] : 0;
                    $sort_order = $index + 1;
                    
                    $stmt->bind_param('isssssssiii',
                        $prescription_id,
                        $medication_name,
                        $dosage,
                        $form,
                        $frequency,
                        $duration,
                        $quantity,
                        $med_instructions,
                        $with_food,
                        $is_priority,
                        $sort_order
                    );
                    $stmt->execute();
                }
            }
            
            $this->db->commit();
            
            return [
                'success' => true, 
                'message' => 'Prescription created successfully',
                'prescription_id' => $prescription_id,
                'patient_name' => $appointment['patient_name']
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => 'Error creating prescription: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get prescriptions for dentist's appointments
     */
    public function getPrescriptions() {
        try {
            if (!isLoggedIn()) {
                return ['success' => false, 'message' => 'Unauthorized access'];
            }
            
            $user_role = getSessionRole();
            $user_id = getSessionUserId();
            $patient_id = $_GET['patient_id'] ?? '';
            $appointment_id = $_GET['appointment_id'] ?? '';
            
            $where_conditions = [];
            $params = [];
            $types = '';
            
            // Base query
            $sql = "
                SELECT 
                    p.id, p.uuid, p.appointment_id, p.diagnosis, p.instructions,
                    p.follow_up_required, p.follow_up_date, p.status, p.prescription_date,
                    u.name as patient_name, u.email as patient_email,
                    a.appointment_date, a.treatment_type_id,
                    tt.name as treatment_name,
                    dentist.name as dentist_name
                FROM prescriptions p
                JOIN users u ON p.patient_id = u.id
                JOIN appointments a ON p.appointment_id = a.id
                JOIN users dentist ON p.dentist_id = dentist.id
                LEFT JOIN treatment_types tt ON a.treatment_type_id = tt.id
            ";
            
            // Build WHERE conditions based on user role
            if ($user_role === ROLE_PATIENT) {
                // Patients can only view their own prescriptions
                $where_conditions[] = "p.patient_id = ?";
                $params[] = $user_id;
                $types .= 'i';
            } else {
                // Staff, dentist, admin - filter by branch
                $where_conditions[] = "p.branch_id = ?";
                $branch_id = getSessionBranchId();
                $params[] = $branch_id;
                $types .= 'i';
                
                if ($patient_id) {
                    $where_conditions[] = "p.patient_id = ?";
                    $params[] = $patient_id;
                    $types .= 'i';
                }
            }
            
            if ($appointment_id) {
                $where_conditions[] = "p.appointment_id = ?";
                $params[] = $appointment_id;
                $types .= 'i';
            }
            
            $sql .= " WHERE " . implode(' AND ', $where_conditions);
            $sql .= " ORDER BY p.prescription_date DESC, p.created_at DESC";
            
            $stmt = $this->db->prepare($sql);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $prescriptions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            // Get medications for each prescription
            foreach ($prescriptions as &$prescription) {
                $stmt = $this->db->prepare("
                    SELECT * FROM prescription_medications 
                    WHERE prescription_id = ? 
                    ORDER BY sort_order, medication_name
                ");
                $stmt->bind_param('i', $prescription['id']);
                $stmt->execute();
                $prescription['medications'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            }
            
            return [
                'success' => true,
                'prescriptions' => $prescriptions
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error fetching prescriptions: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get detailed prescription information by ID
     */
    public function getPrescriptionDetails() {
        try {
            if (!isLoggedIn() || !in_array(getSessionRole(), [ROLE_DENTIST, ROLE_STAFF, ROLE_ADMIN, ROLE_PATIENT])) {
                return ['success' => false, 'message' => 'Unauthorized access'];
            }
            
            $prescription_id = $_GET['prescription_id'] ?? $_GET['id'] ?? '';
            
            if (empty($prescription_id)) {
                return ['success' => false, 'message' => 'Prescription ID is required'];
            }
            
            // Get prescription details
            $stmt = $this->db->prepare("
                SELECT 
                    p.id, p.uuid, p.appointment_id, p.diagnosis, p.instructions,
                    p.follow_up_required, p.follow_up_date, p.status, p.prescription_date,
                    p.created_at, p.updated_at,
                    u.name as patient_name, u.email as patient_email, u.phone as patient_phone,
                    a.appointment_date, a.appointment_time, a.treatment_type_id,
                    tt.name as treatment_name,
                    dentist.name as dentist_name, dentist.email as dentist_email
                FROM prescriptions p
                JOIN users u ON p.patient_id = u.id
                JOIN appointments a ON p.appointment_id = a.id
                JOIN users dentist ON p.dentist_id = dentist.id
                LEFT JOIN treatment_types tt ON a.treatment_type_id = tt.id
                WHERE p.id = ? AND p.branch_id = ?
            ");
            
            $branch_id = getSessionBranchId();
            $stmt->bind_param('ii', $prescription_id, $branch_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                return ['success' => false, 'message' => 'Prescription not found'];
            }
            
            $prescription = $result->fetch_assoc();
            
            // Get medications for this prescription
            $stmt = $this->db->prepare("
                SELECT * FROM prescription_medications 
                WHERE prescription_id = ? 
                ORDER BY sort_order, medication_name
            ");
            $stmt->bind_param('i', $prescription_id);
            $stmt->execute();
            $medications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            return [
                'success' => true,
                'prescription' => $prescription,
                'medications' => $medications
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error fetching prescription details: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get appointments that need prescriptions (approved/completed appointments without prescriptions)
     */
    public function getAppointmentsNeedingPrescriptions() {
        try {
            if (!isLoggedIn() || !in_array(getSessionRole(), [ROLE_DENTIST, ROLE_STAFF, ROLE_ADMIN])) {
                return ['success' => false, 'message' => 'Unauthorized access'];
            }
            
            $stmt = $this->db->prepare("
                SELECT 
                    a.id, a.appointment_date, a.appointment_time, a.status,
                    u.name as patient_name, u.email as patient_email,
                    tt.name as treatment_name,
                    a.clinical_notes
                FROM appointments a
                JOIN users u ON a.patient_id = u.id
                LEFT JOIN treatment_types tt ON a.treatment_type_id = tt.id
                LEFT JOIN prescriptions p ON a.id = p.appointment_id
                WHERE a.branch_id = ? 
                AND a.status IN ('approved', 'completed', 'in_progress')
                AND p.id IS NULL
                ORDER BY a.appointment_date DESC, a.appointment_time DESC
                LIMIT 20
            ");
            
            $branch_id = getSessionBranchId();
            $stmt->bind_param('i', $branch_id);
            $stmt->execute();
            $appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            return [
                'success' => true,
                'appointments' => $appointments
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error fetching appointments: ' . $e->getMessage()];
        }
    }
    
    /**
     * Update prescription
     */
    public function updatePrescription() {
        try {
            if (!isLoggedIn() || !in_array(getSessionRole(), [ROLE_DENTIST, ROLE_STAFF, ROLE_ADMIN])) {
                return ['success' => false, 'message' => 'Unauthorized access'];
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $prescription_id = $input['prescription_id'] ?? '';
            $diagnosis = $input['diagnosis'] ?? '';
            $instructions = $input['instructions'] ?? '';
            $medications = $input['medications'] ?? [];
            $follow_up_required = $input['follow_up_required'] ?? false;
            $follow_up_date = $input['follow_up_date'] ?? null;
            
            // Verify prescription exists and belongs to user's branch
            $stmt = $this->db->prepare("
                SELECT id FROM prescriptions 
                WHERE id = ? AND branch_id = ?
            ");
            $branch_id = getSessionBranchId();
            $stmt->bind_param('ii', $prescription_id, $branch_id);
            $stmt->execute();
            $prescription = $stmt->get_result()->fetch_assoc();
            
            if (!$prescription) {
                return ['success' => false, 'message' => 'Prescription not found'];
            }
            
            $this->db->begin_transaction();
            
            // Update prescription - handle NULL follow_up_date
            if ($follow_up_date) {
                $stmt = $this->db->prepare("
                    UPDATE prescriptions 
                    SET diagnosis = ?, instructions = ?, follow_up_required = ?, follow_up_date = ?, updated_by = ?
                    WHERE id = ?
                ");
                $user_id = getSessionUserId();
                $stmt->bind_param('ssisii', $diagnosis, $instructions, $follow_up_required, $follow_up_date, $user_id, $prescription_id);
            } else {
                $stmt = $this->db->prepare("
                    UPDATE prescriptions 
                    SET diagnosis = ?, instructions = ?, follow_up_required = ?, follow_up_date = NULL, updated_by = ?
                    WHERE id = ?
                ");
                $user_id = getSessionUserId();
                $stmt->bind_param('ssiii', $diagnosis, $instructions, $follow_up_required, $user_id, $prescription_id);
            }
            $stmt->execute();
            
            // Delete existing medications
            $stmt = $this->db->prepare("DELETE FROM prescription_medications WHERE prescription_id = ?");
            $stmt->bind_param('i', $prescription_id);
            $stmt->execute();
            
            // Add updated medications
            if (!empty($medications)) {
                $stmt = $this->db->prepare("
                    INSERT INTO prescription_medications 
                    (prescription_id, medication_name, dosage, form, frequency, duration, quantity, instructions, with_food, is_priority, sort_order) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                foreach ($medications as $index => $med) {
                    $medication_name = $med['medication_name'];
                    $dosage = $med['dosage'];
                    $form = $med['form'] ?? 'tablet';
                    $frequency = $med['frequency'];
                    $duration = $med['duration'];
                    $quantity = $med['quantity'] ?? '1';
                    $med_instructions = $med['instructions'] ?? '';
                    // Convert boolean to int, handle null
                    $with_food = isset($med['with_food']) ? (int)$med['with_food'] : 0;
                    $is_priority = isset($med['is_priority']) ? (int)$med['is_priority'] : 0;
                    $sort_order = $index + 1;
                    
                    $stmt->bind_param('isssssssiii',
                        $prescription_id,
                        $medication_name,
                        $dosage,
                        $form,
                        $frequency,
                        $duration,
                        $quantity,
                        $med_instructions,
                        $with_food,
                        $is_priority,
                        $sort_order
                    );
                    $stmt->execute();
                }
            }
            
            $this->db->commit();
            
            return ['success' => true, 'message' => 'Prescription updated successfully'];
            
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => 'Error updating prescription: ' . $e->getMessage()];
        }
    }
}

// API handler - only run if accessed directly, not when included by other files
if (php_sapi_name() !== 'cli' && !defined('PRESCRIPTION_CONTROLLER_INCLUDED')) {
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    $ctrl = new PrescriptionController();
    $resp = array('success'=>false,'message'=>'Invalid action');
    
    if ($action === 'createPrescription') $resp = $ctrl->createPrescription();
    elseif ($action === 'getPrescriptions') $resp = $ctrl->getPrescriptions();
    elseif ($action === 'getAppointmentsNeedingPrescriptions') $resp = $ctrl->getAppointmentsNeedingPrescriptions();
    elseif ($action === 'updatePrescription') $resp = $ctrl->updatePrescription();
    
    header('Content-Type: application/json');
    echo json_encode($resp);
    exit;
}
?>