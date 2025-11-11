<?php
/**
 * Branch Operating Hours API
 * Provides clinic operating hours information
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../../src/config/database.php';
require_once '../../src/config/session.php';

// Require login
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit();
}

try {
    $db = Database::getConnection();
    $action = $_GET['action'] ?? 'get_hours';
    
    switch ($action) {
        case 'get_hours':
            $branchId = $_GET['branch_id'] ?? null;
            
            if ($branchId) {
                // Get hours for specific branch
                $stmt = $db->prepare("
                    SELECT 
                        b.id,
                        b.name,
                        b.location,
                        b.operating_hours as summary,
                        b.status,
                        bs.day_of_week,
                        bs.open_time,
                        bs.close_time,
                        bs.is_open,
                        TIME_FORMAT(bs.open_time, '%h:%i %p') as formatted_open,
                        TIME_FORMAT(bs.close_time, '%h:%i %p') as formatted_close
                    FROM branches b
                    LEFT JOIN branch_schedules bs ON b.id = bs.branch_id
                    WHERE b.id = ? AND b.status = 'active'
                    ORDER BY FIELD(bs.day_of_week, 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday')
                ");
                $stmt->bind_param("i", $branchId);
                $stmt->execute();
                $result = $stmt->get_result();
                
                $schedule = [];
                $branchInfo = null;
                
                while ($row = $result->fetch_assoc()) {
                    if (!$branchInfo) {
                        $branchInfo = [
                            'id' => $row['id'],
                            'name' => $row['name'],
                            'location' => $row['location'],
                            'summary' => $row['summary'],
                            'status' => $row['status']
                        ];
                    }
                    
                    if ($row['day_of_week']) {
                        $schedule[] = [
                            'day' => $row['day_of_week'],
                            'day_display' => ucfirst($row['day_of_week']),
                            'open_time' => $row['open_time'],
                            'close_time' => $row['close_time'],
                            'formatted_open' => $row['formatted_open'],
                            'formatted_close' => $row['formatted_close'],
                            'is_open' => (bool)$row['is_open'],
                            'hours_display' => $row['is_open'] ? 
                                $row['formatted_open'] . ' - ' . $row['formatted_close'] : 
                                'CLOSED'
                        ];
                    }
                }
                
                if (!$branchInfo) {
                    throw new Exception('Branch not found or inactive');
                }
                
                // Set Philippine timezone for current status check
                date_default_timezone_set('Asia/Manila');
                
                // Get current status (open/closed now)
                $currentTime = date('H:i:s');
                $currentDay = strtolower(date('l'));
                $isCurrentlyOpen = false;
                
                foreach ($schedule as $day) {
                    if ($day['day'] === $currentDay && $day['is_open']) {
                        if ($currentTime >= $day['open_time'] && $currentTime <= $day['close_time']) {
                            $isCurrentlyOpen = true;
                        }
                        break;
                    }
                }
                
                echo json_encode([
                    'success' => true,
                    'branch' => $branchInfo,
                    'schedule' => $schedule,
                    'current_status' => [
                        'is_open' => $isCurrentlyOpen,
                        'current_time' => date('h:i A'),
                        'current_day' => ucfirst($currentDay),
                        'timezone' => 'Asia/Manila (Philippine Time)',
                        'message' => $isCurrentlyOpen ? 'Currently Open' : 'Currently Closed'
                    ]
                ]);
                
            } else {
                // Get hours for all active branches
                $result = $db->query("
                    SELECT 
                        b.id,
                        b.name,
                        b.location,
                        b.operating_hours as summary,
                        b.status
                    FROM branches b
                    WHERE b.status = 'active'
                    ORDER BY b.name
                ");
                
                $branches = [];
                while ($row = $result->fetch_assoc()) {
                    $branches[] = $row;
                }
                
                echo json_encode([
                    'success' => true,
                    'branches' => $branches
                ]);
            }
            break;
            
        case 'get_current_status':
            // Set Philippine timezone
            date_default_timezone_set('Asia/Manila');
            
            // Check if any branch is currently open
            $currentTime = date('H:i:s');
            $currentDay = strtolower(date('l'));
            
            $stmt = $db->prepare("
                SELECT 
                    b.name,
                    bs.open_time,
                    bs.close_time
                FROM branches b
                JOIN branch_schedules bs ON b.id = bs.branch_id
                WHERE b.status = 'active' 
                AND bs.day_of_week = ? 
                AND bs.is_open = 1
                AND ? BETWEEN bs.open_time AND bs.close_time
            ");
            $stmt->bind_param("ss", $currentDay, $currentTime);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $openBranches = [];
            while ($row = $result->fetch_assoc()) {
                $openBranches[] = $row['name'];
            }
            
            echo json_encode([
                'success' => true,
                'current_time' => date('h:i A'),
                'current_day' => ucfirst($currentDay),
                'timezone' => 'Asia/Manila (Philippine Time)',
                'open_branches' => $openBranches,
                'any_open' => count($openBranches) > 0
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>