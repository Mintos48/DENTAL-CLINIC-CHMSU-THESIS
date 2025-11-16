<?php
/**
 * Treatment Controller - Handle Treatment Types and Time Blocking
 */

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/config/session.php';

class TreatmentController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getConnection();
    }
    
    /**
     * Get all treatments (for dentist management)
     */
    public function getAllTreatments() {
        try {
            $sql = "SELECT * FROM treatment_types ORDER BY is_active DESC, name ASC";
            $result = $this->db->query($sql);
            $treatments = $result->fetch_all(MYSQLI_ASSOC);

            return ['success' => true, 'treatments' => $treatments];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to load treatments: ' . $e->getMessage()];
        }
    }

    /**
     * Get single treatment by ID
     */
    public function getTreatment() {
        try {
            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            
            $sql = "SELECT * FROM treatment_types WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $treatment = $stmt->get_result()->fetch_assoc();

            if (!$treatment) {
                return ['success' => false, 'message' => 'Treatment not found'];
            }

            return ['success' => true, 'treatment' => $treatment];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to load treatment: ' . $e->getMessage()];
        }
    }

    /**
     * Create new treatment
     */
    public function createTreatment() {
        try {
            // Check permission - only dentist, staff, or admin
            $role = getSessionRole();
            if (!in_array($role, [ROLE_DENTIST, ROLE_STAFF, ROLE_ADMIN])) {
                return ['success' => false, 'message' => 'Unauthorized'];
            }

            $input = json_decode(file_get_contents('php://input'), true);
            
            $sql = "INSERT INTO treatment_types (name, description, duration_minutes, base_price, 
                    color_code, requires_specialist, preparation_instructions, 
                    post_treatment_instructions, is_active) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('ssisssssi',
                $input['name'],
                $input['description'],
                $input['duration_minutes'],
                $input['base_price'],
                $input['color_code'],
                $input['requires_specialist'],
                $input['preparation_instructions'],
                $input['post_treatment_instructions'],
                $input['is_active']
            );
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Treatment created successfully', 'id' => $stmt->insert_id];
            } else {
                return ['success' => false, 'message' => 'Failed to create treatment'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Update existing treatment
     */
    public function updateTreatment() {
        try {
            // Check permission
            $role = getSessionRole();
            if (!in_array($role, [ROLE_DENTIST, ROLE_STAFF, ROLE_ADMIN])) {
                return ['success' => false, 'message' => 'Unauthorized'];
            }

            $input = json_decode(file_get_contents('php://input'), true);
            
            $sql = "UPDATE treatment_types SET 
                    name = ?, 
                    description = ?, 
                    duration_minutes = ?, 
                    base_price = ?, 
                    color_code = ?, 
                    requires_specialist = ?, 
                    preparation_instructions = ?, 
                    post_treatment_instructions = ?, 
                    is_active = ?
                    WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('ssisssssii',
                $input['name'],
                $input['description'],
                $input['duration_minutes'],
                $input['base_price'],
                $input['color_code'],
                $input['requires_specialist'],
                $input['preparation_instructions'],
                $input['post_treatment_instructions'],
                $input['is_active'],
                $input['id']
            );
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Treatment updated successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to update treatment'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Delete treatment
     */
    public function deleteTreatment() {
        try {
            // Check permission
            $role = getSessionRole();
            if (!in_array($role, [ROLE_DENTIST, ROLE_ADMIN])) {
                return ['success' => false, 'message' => 'Unauthorized - Only dentists and admins can delete treatments'];
            }

            $input = json_decode(file_get_contents('php://input'), true);
            $id = intval($input['id']);
            
            // Check if treatment is being used in appointments
            $checkSql = "SELECT COUNT(*) as count FROM appointments WHERE treatment_type_id = ?";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->bind_param('i', $id);
            $checkStmt->execute();
            $result = $checkStmt->get_result()->fetch_assoc();
            
            if ($result['count'] > 0) {
                return ['success' => false, 'message' => 'Cannot delete treatment that is used in appointments. Set it to inactive instead.'];
            }
            
            $sql = "DELETE FROM treatment_types WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $id);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Treatment deleted successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to delete treatment'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get all active treatment types for a specific branch
     */
    public function getTreatmentTypes() {
        try {
            $branch_id = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : getSessionBranchId();
            
            if (!$branch_id) {
                return ['success' => false, 'message' => 'Branch ID is required'];
            }
            
            $stmt = $this->db->prepare(
                "SELECT tt.id, tt.name, tt.description, tt.duration_minutes, tt.color_code, 
                        bs.price, bs.is_available
                 FROM treatment_types tt
                 JOIN branch_services bs ON tt.id = bs.treatment_type_id
                 WHERE bs.branch_id = ? AND bs.is_available = 1 AND tt.is_active = 1 
                 ORDER BY tt.name ASC"
            );
            
            $stmt->bind_param("i", $branch_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $treatmentTypes = [];
            while ($row = $result->fetch_assoc()) {
                $treatmentTypes[] = $row;
            }
            
            return [
                'success' => true,
                'treatmentTypes' => $treatmentTypes,
                'branch_id' => $branch_id,
                'count' => count($treatmentTypes)
            ];
            
        } catch (Exception $e) {
            error_log("TreatmentController::getTreatmentTypes - " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to load treatment types'];
        }
    }
    
    /**
     * Get branch treatments (alias for getTreatmentTypes with different response format)
     * This provides consistency with ReferralController
     */
    public function getBranchTreatments() {
        try {
            $branch_id = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : getSessionBranchId();
            
            if (!$branch_id) {
                return ['success' => false, 'message' => 'Branch ID is required'];
            }
            
            $stmt = $this->db->prepare(
                "SELECT tt.id, tt.name, tt.description, tt.duration_minutes, tt.color_code, 
                        bs.price, bs.is_available
                 FROM treatment_types tt
                 JOIN branch_services bs ON tt.id = bs.treatment_type_id
                 WHERE bs.branch_id = ? AND bs.is_available = 1 AND tt.is_active = 1 
                 ORDER BY tt.name ASC"
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
                'treatments' => $treatments,
                'branch_id' => $branch_id,
                'count' => count($treatments)
            ];
            
        } catch (Exception $e) {
            error_log("TreatmentController::getBranchTreatments - " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to load treatments'];
        }
    }
    
    /**
     * Get all treatment types (not branch-specific) for admin purposes
     */
    public function getAllTreatmentTypes() {
        try {
            $stmt = $this->db->prepare(
                "SELECT id, name, description, duration_minutes, color_code, is_active 
                 FROM treatment_types 
                 WHERE is_active = 1 
                 ORDER BY name ASC"
            );
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            $treatmentTypes = [];
            while ($row = $result->fetch_assoc()) {
                $treatmentTypes[] = $row;
            }
            
            return [
                'success' => true,
                'treatmentTypes' => $treatmentTypes
            ];
            
        } catch (Exception $e) {
            error_log("TreatmentController::getAllTreatmentTypes - " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to load treatment types'];
        }
    }
    
    /**
     * Check time slot availability considering treatment duration and existing appointments
     */
    public function checkAvailability() {
        try {
            $date = isset($_GET['date']) ? $_GET['date'] : '';
            $duration_minutes = isset($_GET['duration_minutes']) ? intval($_GET['duration_minutes']) : 60;
            $branch_id = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : 0;
            
            if (empty($date) || $branch_id === 0) {
                return ['success' => false, 'message' => 'Missing required parameters'];
            }
            
            // Get branch schedule for the given date
            $dayOfWeek = date('w', strtotime($date)); // 0 = Sunday, 1 = Monday, etc.
            
            $scheduleStmt = $this->db->prepare(
                "SELECT open_time, close_time, break_start_time, break_end_time, is_open 
                 FROM branch_schedules 
                 WHERE branch_id = ? AND day_of_week = ?"
            );
            $scheduleStmt->bind_param("ii", $branch_id, $dayOfWeek);
            $scheduleStmt->execute();
            $scheduleResult = $scheduleStmt->get_result();
            
            if ($scheduleResult->num_rows === 0) {
                return [
                    'success' => true,
                    'availableSlots' => [],
                    'blockedSlots' => [],
                    'message' => 'No schedule found for this day'
                ];
            }
            
            $schedule = $scheduleResult->fetch_assoc();
            
            if (!$schedule['is_open']) {
                return [
                    'success' => true,
                    'availableSlots' => [],
                    'blockedSlots' => [],
                    'message' => 'Branch is closed on this day'
                ];
            }
            
            // Generate all possible time slots
            $allTimeSlots = [
                '08:00', '09:00', '10:00', '11:00',
                '13:00', '14:00', '15:00', '16:00'
            ];
            
            $availableSlots = [];
            $blockedSlots = [];
            
            foreach ($allTimeSlots as $timeSlot) {
                if ($this->isTimeSlotAvailable($branch_id, $date, $timeSlot, $duration_minutes, $schedule)) {
                    $availableSlots[] = $timeSlot;
                } else {
                    $blockedSlots[] = $timeSlot;
                }
            }
            
            return [
                'success' => true,
                'availableSlots' => $availableSlots,
                'blockedSlots' => $blockedSlots,
                'duration_minutes' => $duration_minutes
            ];
            
        } catch (Exception $e) {
            error_log("TreatmentController::checkAvailability - " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to check availability'];
        }
    }
    
    /**
     * Get branch service price for a specific treatment
     */
    public function getBranchServicePrice() {
        try {
            $treatment_id = isset($_GET['treatment_id']) ? intval($_GET['treatment_id']) : 0;
            $branch_id = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : 0;
            
            if (!$treatment_id || !$branch_id) {
                return ['success' => false, 'message' => 'Treatment ID and Branch ID are required'];
            }
            
            $stmt = $this->db->prepare(
                "SELECT bs.price, tt.name as treatment_name, b.name as branch_name
                 FROM branch_services bs
                 JOIN treatment_types tt ON bs.treatment_type_id = tt.id
                 JOIN branches b ON bs.branch_id = b.id
                 WHERE bs.treatment_type_id = ? AND bs.branch_id = ? AND bs.is_available = 1"
            );
            
            $stmt->bind_param("ii", $treatment_id, $branch_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                return [
                    'success' => false, 
                    'message' => 'Service not available at this branch',
                    'price' => null
                ];
            }
            
            $service = $result->fetch_assoc();
            
            return [
                'success' => true,
                'price' => $service['price'],
                'treatment_name' => $service['treatment_name'],
                'branch_name' => $service['branch_name'],
                'formatted_price' => '₱' . number_format($service['price'], 2)
            ];
            
        } catch (Exception $e) {
            error_log("Get branch service price error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to get service price: ' . $e->getMessage()];
        }
    }

    /**
     * Check if a specific time slot is available considering treatment duration
     */
    private function isTimeSlotAvailable($branch_id, $date, $start_time, $duration_minutes, $schedule) {
        try {
            // Calculate end time for the appointment
            $start_datetime = new DateTime("$date $start_time");
            $end_datetime = clone $start_datetime;
            $end_datetime->add(new DateInterval("PT{$duration_minutes}M"));
            $end_time = $end_datetime->format('H:i');
            
            // Check if appointment would extend beyond business hours
            $close_time = $schedule['close_time'];
            if ($end_time > $close_time) {
                return false;
            }
            
            // Check if appointment conflicts with break time
            if ($schedule['break_start_time'] && $schedule['break_end_time']) {
                $break_start = $schedule['break_start_time'];
                $break_end = $schedule['break_end_time'];
                
                // Check if appointment overlaps with break time
                if (($start_time < $break_end && $end_time > $break_start)) {
                    return false;
                }
            }
            
            // Check for existing appointments and time blocks
            $conflictStmt = $this->db->prepare(
                "SELECT COUNT(*) as conflict_count FROM (
                    SELECT 1 FROM appointments 
                    WHERE branch_id = ? AND appointment_date = ? 
                    AND status NOT IN ('cancelled', 'rejected')
                    AND (
                        (appointment_time < ? AND end_time > ?) OR
                        (appointment_time < ? AND end_time > ?) OR
                        (appointment_time >= ? AND appointment_time < ?)
                    )
                    
                    UNION ALL
                    
                    SELECT 1 FROM appointment_time_blocks 
                    WHERE branch_id = ? AND appointment_date = ?
                    AND (
                        (start_time < ? AND end_time > ?) OR
                        (start_time < ? AND end_time > ?) OR
                        (start_time >= ? AND start_time < ?)
                    )
                ) as conflicts"
            );
            
            $conflictStmt->bind_param(
                "issssssssisssssss",
                $branch_id, $date,
                $end_time, $start_time,    // Check if existing appointment starts before our end and ends after our start
                $end_time, $start_time,    // Same check (overlapping condition)
                $start_time, $end_time,    // Check if existing appointment is completely within our time
                $branch_id, $date,
                $end_time, $start_time,    // Same checks for time blocks
                $end_time, $start_time,
                $start_time, $end_time
            );
            
            $conflictStmt->execute();
            $result = $conflictStmt->get_result()->fetch_assoc();
            
            return $result['conflict_count'] == 0;
            
        } catch (Exception $e) {
            error_log("TreatmentController::isTimeSlotAvailable - " . $e->getMessage());
            return false;
        }
    }
}

// Handle API requests
if (php_sapi_name() !== 'cli') {
    // Check if user is logged in
    if (!isLoggedIn()) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Please login to access this resource']);
        exit;
    }
    
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    $controller = new TreatmentController();
    $response = [];
    
    switch ($action) {
        case 'getAllTreatments':
            $response = $controller->getAllTreatments();
            break;
            
        case 'getTreatment':
            $response = $controller->getTreatment();
            break;
            
        case 'createTreatment':
            $response = $controller->createTreatment();
            break;
            
        case 'updateTreatment':
            $response = $controller->updateTreatment();
            break;
            
        case 'deleteTreatment':
            $response = $controller->deleteTreatment();
            break;
            
        case 'getTreatmentTypes':
            $response = $controller->getTreatmentTypes();
            break;
            
        case 'getBranchTreatments':
            $response = $controller->getBranchTreatments();
            break;
            
        case 'getAllTreatmentTypes':
            $response = $controller->getAllTreatmentTypes();
            break;
            
        case 'checkAvailability':
            $response = $controller->checkAvailability();
            break;
            
        case 'getBranchServicePrice':
            $response = $controller->getBranchServicePrice();
            break;
            
        default:
            http_response_code(400);
            $response = ['success' => false, 'message' => 'Invalid action'];
            break;
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>