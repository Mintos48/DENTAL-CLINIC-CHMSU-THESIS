<?php
require_once __DIR__ . '/../services/AppointmentReminderService.php';

class ReminderController {
    private $conn;
    private $reminderService;
    
    public function __construct($connection) {
        $this->conn = $connection;
        $this->reminderService = new AppointmentReminderService();
    }
    
    public function handleRequest() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }
        
        $action = $_POST['action'] ?? $_GET['action'] ?? '';
        $userRole = $_SESSION['role'] ?? '';
        $userId = $_SESSION['user_id'];
        
        switch ($action) {
            case 'check_reminders':
                $this->checkReminders($userId, $userRole);
                break;
                
            case 'send_reminders':
                if ($userRole !== 'admin' && $userRole !== 'staff') {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
                    return;
                }
                $this->sendReminders();
                break;
                
            case 'test_reminder':
                if ($userRole !== 'admin') {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Admin access required']);
                    return;
                }
                $this->testReminder();
                break;
                
            case 'reminder_stats':
                if ($userRole !== 'admin' && $userRole !== 'staff') {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
                    return;
                }
                $this->getReminderStats();
                break;
                
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    }
    
    private function checkReminders($userId, $userRole) {
        try {
            // For patients, check their specific reminders
            if ($userRole === 'patient') {
                $upcomingAppointments = $this->getUpcomingAppointmentsForUser($userId);
                
                if (!empty($upcomingAppointments)) {
                    $appointment = $upcomingAppointments[0]; // Get the next upcoming appointment
                    $appointmentTime = new DateTime($appointment['appointment_date'] . ' ' . $appointment['appointment_time']);
                    $now = new DateTime();
                    $timeDifference = $appointmentTime->diff($now);
                    
                    // Check if appointment is within 30 minutes
                    $totalMinutes = ($timeDifference->h * 60) + $timeDifference->i;
                    
                    if (!$timeDifference->invert && $totalMinutes <= 30) {
                        echo json_encode([
                            'success' => true,
                            'has_reminders' => true,
                            'reminder_message' => "Your appointment at {$appointment['clinic_name']} is coming up in {$totalMinutes} minutes at " . date('g:i A', strtotime($appointment['appointment_time'])) . "."
                        ]);
                        return;
                    }
                }
            }
            
            echo json_encode([
                'success' => true,
                'has_reminders' => false,
                'reminder_message' => ''
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error checking reminders: ' . $e->getMessage()
            ]);
        }
    }
    
    private function getUpcomingAppointmentsForUser($userId) {
        $query = "
            SELECT a.*, 'Dental Clinic' as clinic_name 
            FROM appointments a 
            WHERE a.patient_id = ? 
            AND a.status = 'confirmed' 
            AND DATE(a.appointment_date) >= CURDATE()
            AND CONCAT(a.appointment_date, ' ', a.appointment_time) > NOW()
            ORDER BY a.appointment_date ASC, a.appointment_time ASC
            LIMIT 5
        ";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    private function sendReminders() {
        try {
            $result = $this->reminderService->checkAndSendReminders();
            echo json_encode([
                'success' => true,
                'message' => 'Reminder check completed',
                'reminders_sent' => $result['sent'],
                'total_checked' => $result['total']
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error sending reminders: ' . $e->getMessage()
            ]);
        }
    }
    
    private function testReminder() {
        $email = $_POST['email'] ?? '';
        
        if (empty($email)) {
            echo json_encode(['success' => false, 'message' => 'Email address required']);
            return;
        }
        
        try {
            $testResult = $this->reminderService->sendTestReminder($email);
            echo json_encode([
                'success' => $testResult,
                'message' => $testResult ? 'Test reminder sent successfully' : 'Failed to send test reminder'
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error sending test reminder: ' . $e->getMessage()
            ]);
        }
    }
    
    private function getReminderStats() {
        try {
            $stats = $this->reminderService->getReminderStats();
            echo json_encode([
                'success' => true,
                'stats' => $stats
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error getting reminder stats: ' . $e->getMessage()
            ]);
        }
    }
}
?>