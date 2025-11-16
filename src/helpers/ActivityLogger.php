<?php
/**
 * Activity Logger Helper
 * Centralized logging for all user activities across the system
 */

require_once __DIR__ . '/../config/database.php';

class ActivityLogger {
    private static $db;
    
    private static function getDb() {
        if (self::$db === null) {
            self::$db = Database::getConnection();
        }
        return self::$db;
    }
    
    /**
     * Log any user activity
     * 
     * @param int $user_id User performing the action
     * @param string $action Action identifier (e.g., 'login', 'appointment_booked')
     * @param string $description Human-readable description
     * @param string $level Log level: info, warning, error, critical
     * @param string $category Category: auth, appointment, user, system, etc.
     * @param array $data Additional data to store as JSON
     */
    public static function log($user_id, $action, $description, $level = 'info', $category = 'general', $data = null) {
        try {
            $db = self::getDb();
            
            // Get client IP address
            $ip_address = self::getClientIP();
            
            // Get user agent
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            // Prepare data as JSON if provided
            $request_data = $data ? json_encode($data) : null;
            
            $stmt = $db->prepare(
                "INSERT INTO system_logs 
                (user_id, level, category, action, description, ip_address, user_agent, request_data) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            
            $stmt->bind_param(
                "isssssss", 
                $user_id, 
                $level, 
                $category, 
                $action, 
                $description, 
                $ip_address, 
                $user_agent,
                $request_data
            );
            
            $stmt->execute();
            return true;
            
        } catch (Exception $e) {
            error_log("ActivityLogger error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Shorthand methods for common log levels
     */
    public static function info($user_id, $action, $description, $category = 'general', $data = null) {
        return self::log($user_id, $action, $description, 'info', $category, $data);
    }
    
    public static function warning($user_id, $action, $description, $category = 'general', $data = null) {
        return self::log($user_id, $action, $description, 'warning', $category, $data);
    }
    
    public static function error($user_id, $action, $description, $category = 'general', $data = null) {
        return self::log($user_id, $action, $description, 'error', $category, $data);
    }
    
    public static function critical($user_id, $action, $description, $category = 'general', $data = null) {
        return self::log($user_id, $action, $description, 'critical', $category, $data);
    }
    
    /**
     * Category-specific logging methods
     */
    public static function logAuth($user_id, $action, $description, $data = null) {
        return self::info($user_id, $action, $description, 'auth', $data);
    }
    
    public static function logAppointment($user_id, $action, $description, $data = null) {
        return self::info($user_id, $action, $description, 'appointment', $data);
    }
    
    public static function logUser($user_id, $action, $description, $data = null) {
        return self::info($user_id, $action, $description, 'user', $data);
    }
    
    public static function logSystem($user_id, $action, $description, $data = null) {
        return self::info($user_id, $action, $description, 'system', $data);
    }
    
    public static function logPayment($user_id, $action, $description, $data = null) {
        return self::info($user_id, $action, $description, 'payment', $data);
    }
    
    /**
     * Get client IP address (handles proxies)
     */
    private static function getClientIP() {
        $ip_address = '127.0.0.1';
        
        if (isset($_SERVER['HTTP_CLIENT_IP']) && !empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip_address = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED']) && !empty($_SERVER['HTTP_X_FORWARDED'])) {
            $ip_address = $_SERVER['HTTP_X_FORWARDED'];
        } elseif (isset($_SERVER['HTTP_FORWARDED_FOR']) && !empty($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ip_address = $_SERVER['HTTP_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_FORWARDED']) && !empty($_SERVER['HTTP_FORWARDED'])) {
            $ip_address = $_SERVER['HTTP_FORWARDED'];
        } elseif (isset($_SERVER['REMOTE_ADDR']) && !empty($_SERVER['REMOTE_ADDR'])) {
            $ip_address = $_SERVER['REMOTE_ADDR'];
        }
        
        // Handle comma-separated IPs (take first one)
        if (strpos($ip_address, ',') !== false) {
            $ip_array = explode(',', $ip_address);
            $ip_address = trim($ip_array[0]);
        }
        
        return $ip_address;
    }
    
    /**
     * Get recent activity for a user
     */
    public static function getUserActivity($user_id, $limit = 50) {
        try {
            $db = self::getDb();
            
            $stmt = $db->prepare(
                "SELECT * FROM system_logs 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT ?"
            );
            
            $stmt->bind_param("ii", $user_id, $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $activities = array();
            while ($row = $result->fetch_assoc()) {
                $activities[] = $row;
            }
            
            return $activities;
            
        } catch (Exception $e) {
            error_log("Get user activity error: " . $e->getMessage());
            return array();
        }
    }
    
    /**
     * Get activity by category
     */
    public static function getActivityByCategory($category, $limit = 50) {
        try {
            $db = self::getDb();
            
            $stmt = $db->prepare(
                "SELECT l.*, u.name as user_name, u.email as user_email 
                FROM system_logs l 
                LEFT JOIN users u ON l.user_id = u.id 
                WHERE l.category = ? 
                ORDER BY l.created_at DESC 
                LIMIT ?"
            );
            
            $stmt->bind_param("si", $category, $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $activities = array();
            while ($row = $result->fetch_assoc()) {
                $activities[] = $row;
            }
            
            return $activities;
            
        } catch (Exception $e) {
            error_log("Get activity by category error: " . $e->getMessage());
            return array();
        }
    }
    
    /**
     * Get activity statistics
     */
    public static function getStats($days = 30) {
        try {
            $db = self::getDb();
            
            $sql = "SELECT 
                        COUNT(*) as total_activities,
                        COUNT(DISTINCT user_id) as active_users,
                        COUNT(CASE WHEN level = 'error' THEN 1 END) as errors,
                        COUNT(CASE WHEN level = 'critical' THEN 1 END) as critical_issues,
                        COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 END) as last_24h
                    FROM system_logs 
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
            
            $stmt = $db->prepare($sql);
            $stmt->bind_param("i", $days);
            $stmt->execute();
            $result = $stmt->get_result();
            
            return $result->fetch_assoc();
            
        } catch (Exception $e) {
            error_log("Get activity stats error: " . $e->getMessage());
            return array();
        }
    }
}

/**
 * Global helper function for quick logging
 */
function logActivity($user_id, $action, $description, $level = 'info', $category = 'general', $data = null) {
    return ActivityLogger::log($user_id, $action, $description, $level, $category, $data);
}

?>
