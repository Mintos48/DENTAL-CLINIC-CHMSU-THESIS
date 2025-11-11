<?php
/**
 * Appointment Model
 */

require_once dirname(__DIR__) . '/config/database.php';

class Appointment {
    private $db;
    
    public function __construct() {
        $this->db = Database::getConnection();
    }
    
    /**
     * Book appointment
     */
    public function bookAppointment($patient_id, $branch_id, $appointment_date, $appointment_time, $notes = null) {
        $status = APPOINTMENT_PENDING;
        
        $stmt = $this->db->prepare(
            "INSERT INTO appointments (patient_id, branch_id, appointment_date, appointment_time, notes, status, created_at) 
             VALUES (?, ?, ?, ?, ?, ?, NOW())"
        );
        
        $stmt->bind_param("iissss", $patient_id, $branch_id, $appointment_date, $appointment_time, $notes, $status);
        
        if ($stmt->execute()) {
            return [
                'success' => true,
                'appointment_id' => $this->db->insert_id,
                'message' => SUCCESS_APPOINTMENT_BOOKED
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Failed to book appointment'
        ];
    }
    
    /**
     * Get appointments for patient
     */
    public function getPatientAppointments($patient_id, $branch_id) {
        $stmt = $this->db->prepare(
            "SELECT * FROM appointments 
             WHERE patient_id = ? AND branch_id = ? 
             ORDER BY appointment_date DESC"
        );
        
        $stmt->bind_param("ii", $patient_id, $branch_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $appointments = [];
        while ($row = $result->fetch_assoc()) {
            $appointments[] = $row;
        }
        
        return $appointments;
    }
    
    /**
     * Get all appointments for branch (for staff)
     */
    public function getBranchAppointments($branch_id, $status = null) {
        $query = "SELECT a.*, u.full_name, u.email, u.phone 
                  FROM appointments a 
                  JOIN users u ON a.patient_id = u.id 
                  WHERE a.branch_id = ?";
        
        $params = [$branch_id];
        $types = "i";
        
        if ($status !== null) {
            $query .= " AND a.status = ?";
            $params[] = $status;
            $types .= "s";
        }
        
        $query .= " ORDER BY a.appointment_date DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $appointments = [];
        while ($row = $result->fetch_assoc()) {
            $appointments[] = $row;
        }
        
        return $appointments;
    }
    
    /**
     * Get appointment by ID
     */
    public function getAppointmentById($appointment_id) {
        $stmt = $this->db->prepare(
            "SELECT a.*, u.full_name, u.email, u.phone 
             FROM appointments a 
             JOIN users u ON a.patient_id = u.id 
             WHERE a.id = ?"
        );
        
        $stmt->bind_param("i", $appointment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->num_rows > 0 ? $result->fetch_assoc() : null;
    }
    
    /**
     * Approve appointment
     */
    public function approveAppointment($appointment_id, $branch_id) {
        $status = APPOINTMENT_APPROVED;
        $verified_by = getCurrentUserId();
        
        $stmt = $this->db->prepare(
            "UPDATE appointments 
             SET status = ?, verified_by = ?, verified_at = NOW() 
             WHERE id = ? AND branch_id = ?"
        );
        
        $stmt->bind_param("siii", $status, $verified_by, $appointment_id, $branch_id);
        
        if ($stmt->execute()) {
            return [
                'success' => true,
                'message' => SUCCESS_APPOINTMENT_APPROVED
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Failed to approve appointment'
        ];
    }
    
    /**
     * Cancel appointment
     */
    public function cancelAppointment($appointment_id, $branch_id, $reason = null) {
        $status = APPOINTMENT_CANCELLED;
        
        $stmt = $this->db->prepare(
            "UPDATE appointments 
             SET status = ?, cancellation_reason = ?, cancelled_at = NOW() 
             WHERE id = ? AND branch_id = ?"
        );
        
        $stmt->bind_param("ssii", $status, $reason, $appointment_id, $branch_id);
        
        if ($stmt->execute()) {
            return [
                'success' => true,
                'message' => 'Appointment cancelled successfully'
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Failed to cancel appointment'
        ];
    }
    
    /**
     * Check availability
     */
    public function checkAvailability($branch_id, $appointment_date, $appointment_time) {
        // Handle null parameters
        if (empty($branch_id) || empty($appointment_date) || empty($appointment_time)) {
            return false; // Not available if required parameters are missing
        }
        
        try {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) as count FROM appointments 
                 WHERE branch_id = ? AND appointment_date = ? AND appointment_time = ? 
                 AND status != ?"
            );
            
            $cancelled_status = APPOINTMENT_CANCELLED;
            $stmt->bind_param("isss", $branch_id, $appointment_date, $appointment_time, $cancelled_status);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result === false) {
                // Database error occurred
                return false;
            }
            
            $row = $result->fetch_assoc();
            
            // Handle case where no data is returned or count is null
            if ($row === null || !isset($row['count'])) {
                return true; // Available if no conflicting appointments found
            }
            
            return intval($row['count']) == 0;
            
        } catch (Exception $e) {
            // Log error if needed and return false (not available) as safe default
            error_log("Appointment availability check error: " . $e->getMessage());
            return false;
        }
    }
}
?>
