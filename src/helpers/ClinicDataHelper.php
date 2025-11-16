<?php
/**
 * Clinic Data Helper
 * Fetches clinic/branch data from database
 */

require_once __DIR__ . '/../config/database.php';

class ClinicDataHelper {
    private static $db;
    
    public static function init() {
        if (self::$db === null) {
            self::$db = Database::getConnection();
        }
    }
    
    /**
     * Get all branches/clinics
     */
    public static function getAllClinics() {
        self::init();
        
        $query = "SELECT * FROM branches WHERE status = 'active' ORDER BY name ASC";
        $result = self::$db->query($query);
        
        $clinics = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $clinics[] = self::formatClinicData($row);
            }
        }
        
        return $clinics;
    }
    
    /**
     * Get clinic by ID
     */
    public static function getClinicById($id) {
        self::init();
        
        $stmt = self::$db->prepare("SELECT * FROM branches WHERE id = ? AND status = 'active'");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            return self::formatClinicData($result->fetch_assoc());
        }
        
        return null;
    }
    
    /**
     * Get clinic by name (for URL-friendly names)
     */
    public static function getClinicByName($name) {
        self::init();
        
        // Map URL-friendly names to database names or IDs
        $nameMapping = [
            'happy-teeth' => 'Happy Teeth Dental',
            'happy-teeth-dental' => 'Happy Teeth Dental',
            'ardent' => 'Ardent Dental Clinic', 
            'gamboa' => 'Gamboa Dental Clinic'
        ];
        
        $clinicName = $nameMapping[$name] ?? $name;
        
        $stmt = self::$db->prepare("SELECT * FROM branches WHERE name = ? AND status = 'active'");
        $stmt->bind_param("s", $clinicName);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            return self::formatClinicData($result->fetch_assoc());
        }
        
        return null;
    }
    
    /**
     * Get clinic services
     */
    public static function getClinicServices($branchId) {
        self::init();
        
        $query = "
            SELECT bs.*, tt.name as treatment_name, tt.description, tt.duration_minutes, tt.color_code
            FROM branch_services bs
            JOIN treatment_types tt ON bs.treatment_type_id = tt.id
            WHERE bs.branch_id = ? AND bs.is_available = 1 AND tt.is_active = 1
            ORDER BY tt.name ASC
        ";
        
        $stmt = self::$db->prepare($query);
        $stmt->bind_param("i", $branchId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $services = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $services[] = $row;
            }
        }
        
        return $services;
    }
    
    /**
     * Get clinic schedule
     */
    public static function getClinicSchedule($branchId) {
        self::init();
        
        $query = "
            SELECT * FROM branch_schedules 
            WHERE branch_id = ? AND is_open = 1
            ORDER BY day_of_week ASC
        ";
        
        $stmt = self::$db->prepare($query);
        $stmt->bind_param("i", $branchId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $schedule = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $schedule[] = $row;
            }
        }
        
        return $schedule;
    }
    
    /**
     * Get clinic statistics
     */
    public static function getClinicStats($branchId) {
        self::init();
        
        $stats = [];
        
        // Total appointments
        $query = "SELECT COUNT(*) as total_appointments FROM appointments WHERE branch_id = ?";
        $stmt = self::$db->prepare($query);
        $stmt->bind_param("i", $branchId);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['total_appointments'] = $result->fetch_assoc()['total_appointments'];
        
        // Completed appointments
        $query = "SELECT COUNT(*) as completed_appointments FROM appointments WHERE branch_id = ? AND status = 'completed'";
        $stmt = self::$db->prepare($query);
        $stmt->bind_param("i", $branchId);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['completed_appointments'] = $result->fetch_assoc()['completed_appointments'];
        
        // Active services
        $query = "SELECT COUNT(*) as active_services FROM branch_services WHERE branch_id = ? AND is_available = 1";
        $stmt = self::$db->prepare($query);
        $stmt->bind_param("i", $branchId);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['active_services'] = $result->fetch_assoc()['active_services'];
        
        return $stats;
    }
    
    /**
     * Format clinic data with additional information
     */
    private static function formatClinicData($clinic) {
        // Add URL-friendly name
        $urlMapping = [
            'Happy Teeth Dental' => 'happy-teeth-dental',
            'Ardent Dental Clinic' => 'ardent',
            'Gamboa Dental Clinic' => 'gamboa'
        ];
        
        $clinic['url_name'] = $urlMapping[$clinic['name']] ?? strtolower(str_replace(' ', '-', $clinic['name']));
        
        // Parse operating hours if stored as JSON or comma-separated
        if (!empty($clinic['operating_hours'])) {
            $clinic['parsed_hours'] = self::parseOperatingHours($clinic['operating_hours']);
        }
        
        // Add default rating (you can calculate this from actual data later)
        $clinic['rating'] = 4.8;
        $clinic['review_count'] = rand(80, 200);
        
        // Add verification status
        $clinic['is_verified'] = in_array($clinic['name'], ['Happy Teeth Dental']);
        
        // Add dynamic status information
        $clinic['is_featured'] = self::isFeaturedClinic($clinic['id']);
        $clinic['is_popular'] = self::isPopularClinic($clinic['id']);
        $clinic['is_open_today'] = self::isOpenToday($clinic['id']);
        $clinic['current_status'] = self::getCurrentStatus($clinic['id']);
        
        return $clinic;
    }
    
    /**
     * Parse operating hours
     */
    private static function parseOperatingHours($hours) {
        // If stored as JSON
        if (json_decode($hours)) {
            return json_decode($hours, true);
        }
        
        // If stored as simple string, return as is
        return $hours;
    }
    
    /**
     * Get clinic profile data with all related information
     */
    public static function getFullClinicProfile($nameOrId) {
        if (is_numeric($nameOrId)) {
            $clinic = self::getClinicById($nameOrId);
        } else {
            $clinic = self::getClinicByName($nameOrId);
        }
        
        if (!$clinic) {
            return null;
        }
        
        $clinic['services'] = self::getClinicServices($clinic['id']);
        $clinic['schedule'] = self::getClinicSchedule($clinic['id']);
        $clinic['stats'] = self::getClinicStats($clinic['id']);
        
        return $clinic;
    }
    
    /**
     * Check if clinic is featured (based on service count and activity)
     */
    private static function isFeaturedClinic($branchId) {
        self::init();
        
        // Get service count
        $stmt = self::$db->prepare("SELECT COUNT(*) as service_count FROM branch_services WHERE branch_id = ? AND is_available = 1");
        $stmt->bind_param("i", $branchId);
        $stmt->execute();
        $result = $stmt->get_result();
        $serviceCount = $result->fetch_assoc()['service_count'];
        
        // Get recent appointment count (last 30 days)
        $stmt = self::$db->prepare("SELECT COUNT(*) as recent_appointments FROM appointments WHERE branch_id = ? AND appointment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
        $stmt->bind_param("i", $branchId);
        $stmt->execute();
        $result = $stmt->get_result();
        $recentAppointments = $result->fetch_assoc()['recent_appointments'];
        
        // Consider featured if has 10+ services or 20+ recent appointments
        return $serviceCount >= 10 || $recentAppointments >= 20;
    }
    
    /**
     * Check if clinic is popular (based on appointment history)
     */
    private static function isPopularClinic($branchId) {
        self::init();
        
        // Get total completed appointments
        $stmt = self::$db->prepare("SELECT COUNT(*) as total_appointments FROM appointments WHERE branch_id = ? AND status = 'completed'");
        $stmt->bind_param("i", $branchId);
        $stmt->execute();
        $result = $stmt->get_result();
        $totalAppointments = $result->fetch_assoc()['total_appointments'];
        
        // Get recent appointment count (last 7 days)
        $stmt = self::$db->prepare("SELECT COUNT(*) as recent_appointments FROM appointments WHERE branch_id = ? AND appointment_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
        $stmt->bind_param("i", $branchId);
        $stmt->execute();
        $result = $stmt->get_result();
        $recentAppointments = $result->fetch_assoc()['recent_appointments'];
        
        // Consider popular if has 50+ total appointments or 10+ this week
        return $totalAppointments >= 50 || $recentAppointments >= 10;
    }
    
    /**
     * Check if clinic is open today
     * Priority: Manual daily status > Schedule-based status
     */
    private static function isOpenToday($branchId) {
        self::init();
        
        // First check if there's a daily status override set by dentist
        $today = date('Y-m-d');
        $stmt = self::$db->prepare("SELECT status FROM clinic_daily_status WHERE branch_id = ? AND status_date = ?");
        $stmt->bind_param("is", $branchId, $today);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $dailyStatus = $result->fetch_assoc();
            // Manual status takes precedence
            // 'closed' = not open, all other statuses (open, busy, fully_booked) = is open
            if ($dailyStatus['status'] === 'closed') {
                return false;
            } else {
                // open, busy, or fully_booked means the clinic is operating
                return true;
            }
        }
        
        // Fallback to schedule-based check if no manual status is set
        $currentDay = strtolower(date('l'));
        $currentTime = date('H:i:s');
        
        $stmt = self::$db->prepare("SELECT open_time, close_time, is_open FROM branch_schedules WHERE branch_id = ? AND day_of_week = ?");
        $stmt->bind_param("is", $branchId, $currentDay);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $schedule = $result->fetch_assoc();
            
            if (!$schedule['is_open']) {
                return false;
            }
            
            // Check if current time is within operating hours
            $openTime = $schedule['open_time'];
            $closeTime = $schedule['close_time'];
            
            return ($currentTime >= $openTime && $currentTime <= $closeTime);
        }
        
        return false; // No schedule found, assume closed
    }
    
    /**
     * Get current status of clinic (Open, Closed, Busy, etc.)
     * Priority: Manual daily status > Automatic appointment-based status
     */
    private static function getCurrentStatus($branchId) {
        self::init();
        
        // First check if there's a manual daily status set by dentist
        $today = date('Y-m-d');
        $stmt = self::$db->prepare("SELECT status, reason FROM clinic_daily_status WHERE branch_id = ? AND status_date = ?");
        $stmt->bind_param("is", $branchId, $today);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $dailyStatus = $result->fetch_assoc();
            // Manual status takes full precedence - return it directly
            $statusMap = [
                'closed' => 'Closed Today',
                'busy' => 'Busy',
                'fully_booked' => 'Fully Booked',
                'open' => 'Available' // Explicitly set as open/available
            ];
            
            return $statusMap[$dailyStatus['status']] ?? 'Available';
        }
        
        // Fallback to schedule-based and automatic status detection
        if (!self::isOpenToday($branchId)) {
            return 'Closed Today';
        }
        
        // Automatic status based on current appointments
        $currentDate = date('Y-m-d');
        $currentTime = date('H:i:s');
        
        $stmt = self::$db->prepare("SELECT COUNT(*) as current_appointments FROM appointments WHERE branch_id = ? AND appointment_date = ? AND appointment_time <= ? AND end_time >= ? AND status IN ('approved', 'pending')");
        $stmt->bind_param("isss", $branchId, $currentDate, $currentTime, $currentTime);
        $stmt->execute();
        $result = $stmt->get_result();
        $currentAppointments = $result->fetch_assoc()['current_appointments'];
        
        // Check total appointments for today
        $stmt = self::$db->prepare("SELECT COUNT(*) as total_today FROM appointments WHERE branch_id = ? AND appointment_date = ? AND status IN ('approved', 'pending')");
        $stmt->bind_param("is", $branchId, $currentDate);
        $stmt->execute();
        $result = $stmt->get_result();
        $totalToday = $result->fetch_assoc()['total_today'];
        
        if ($currentAppointments > 0) {
            return 'Busy';
        } elseif ($totalToday >= 15) {
            return 'Fully Booked';
        } else {
            return 'Available';
        }
    }
}
?>