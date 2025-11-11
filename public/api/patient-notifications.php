<?php
/**
 * Real-time Patient Notification API
 * Provides real-time updates for appointment status changes
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../../src/config/database.php';
require_once '../../src/config/session.php';

// Get database connection
$conn = Database::getConnection();

try {
    // Get database connection
    $conn = Database::getConnection();
    
    if (!$conn) {
        throw new Exception('Failed to establish database connection');
    }
    
    // Check if user is logged in
    if (!isLoggedIn()) {
        throw new Exception('User not authenticated');
    }

    // Get action from GET or POST parameters
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    $user_id = getSessionUserId();

    if (!$user_id) {
        throw new Exception('User session not found');
    }

    switch ($action) {
        case 'get':
        case 'get_notifications':
            getPatientNotifications($conn, $user_id);
            break;
        
        case 'mark_read':
        case 'mark_as_read':
            markNotificationAsRead($conn, $user_id);
            break;
        
        case 'mark_all_read':
            markAllNotificationsAsRead($conn, $user_id);
            break;
        
        case 'clear_all':
            clearAllNotifications($conn, $user_id);
            break;
            
        case 'count':
        case 'get_unread_count':
            getUnreadNotificationCount($conn, $user_id);
            break;
            
        default:
            throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function getPatientNotifications($conn, $user_id) {
    try {
        // Get recent appointment changes for this patient
        $query = "SELECT 
                    ah.id,
                    ah.appointment_id,
                    ah.event_type as action,
                    ah.event_description,
                    ah.created_at,
                    ah.referring_staff_id,
                    ah.referring_staff_name,
                    COALESCE(ah.is_read, 0) as is_read,
                    a.appointment_date,
                    a.appointment_time,
                    a.status as current_status,
                    tt.name as treatment_name,
                    COALESCE(ah.referring_staff_name, u_staff.name, 'Staff') as staff_name,
                    b.name as branch_name,
                    CASE 
                        WHEN ah.event_type = 'completed' THEN 'success'
                        WHEN ah.event_type = 'referred' THEN 'info' 
                        WHEN ah.event_type = 'cancelled' THEN 'warning'
                        WHEN ah.event_type = 'treatment_changed' THEN 'info'
                        WHEN ah.event_type = 'branch_changed' THEN 'info'
                        ELSE 'info'
                    END as notification_type
                  FROM appointment_history ah
                  JOIN appointments a ON ah.appointment_id = a.id
                  LEFT JOIN treatment_types tt ON a.treatment_type_id = tt.id
                  LEFT JOIN users u_staff ON ah.referring_staff_id = u_staff.id
                  LEFT JOIN branches b ON a.branch_id = b.id
                  WHERE a.patient_id = ? 
                  AND ah.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
                  AND ah.event_type IN ('referred', 'treatment_changed', 'branch_changed', 'completed', 'cancelled')
                  ORDER BY ah.created_at DESC
                  LIMIT 20";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $user_id);
        
        if (!$stmt->execute()) {
            throw new Exception('Database query failed: ' . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $notifications = [];
        $unread_count = 0;
        
        while ($row = $result->fetch_assoc()) {
            if ($row['is_read'] == 0) {
                $unread_count++;
            }
            
            $notifications[] = [
                'id' => $row['id'],
                'appointment_id' => $row['appointment_id'],
                'action' => $row['action'],
                'title' => generateNotificationTitle($row['action'], $row['treatment_name']),
                'message' => generateNotificationMessage($row),
                'created_at' => $row['created_at'],
                'is_read' => $row['is_read'],
                'staff_name' => $row['staff_name'],
                'branch_name' => $row['branch_name'],
                'appointment_date' => $row['appointment_date'],
                'appointment_time' => $row['appointment_time']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'notifications' => $notifications,
            'unread_count' => $unread_count,
            'total_count' => count($notifications)
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Error fetching notifications: ' . $e->getMessage());
    }
}

function markNotificationAsRead($conn, $user_id) {
    try {
        $notification_id = $_POST['notification_id'] ?? null;
        
        if (empty($notification_id)) {
            throw new Exception('Notification ID is required');
        }
        
        // Update specific notification read status
        $query = "UPDATE appointment_history ah
                  JOIN appointments a ON ah.appointment_id = a.id
                  SET ah.is_read = 1
                  WHERE ah.id = ? 
                  AND a.patient_id = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ii', $notification_id, $user_id);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to mark notification as read: ' . $stmt->error);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Notification marked as read',
            'updated_count' => $stmt->affected_rows
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Error marking notification as read: ' . $e->getMessage());
    }
}

function getUnreadNotificationCount($conn, $user_id) {
    try {
        // Get count of unread notifications from last 24 hours
        $query = "SELECT COUNT(*) as unread_count
                  FROM appointment_history ah
                  JOIN appointments a ON ah.appointment_id = a.id
                  WHERE a.patient_id = ? 
                  AND ah.created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                  AND ah.event_type IN ('referred', 'treatment_changed', 'branch_changed', 'completed', 'cancelled')
                  AND (ah.is_read IS NULL OR ah.is_read = 0)";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $user_id);
        
        if (!$stmt->execute()) {
            throw new Exception('Database query failed: ' . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        echo json_encode([
            'success' => true,
            'count' => (int)$row['unread_count'],
            'unread_count' => (int)$row['unread_count']
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Error getting unread count: ' . $e->getMessage());
    }
}

function markAllNotificationsAsRead($conn, $user_id) {
    try {
        // Mark all notifications as read for this patient
        $query = "UPDATE appointment_history ah
                  JOIN appointments a ON ah.appointment_id = a.id
                  SET ah.is_read = 1
                  WHERE a.patient_id = ? 
                  AND ah.event_type IN ('referred', 'treatment_changed', 'branch_changed', 'completed', 'cancelled')
                  AND (ah.is_read IS NULL OR ah.is_read = 0)";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $user_id);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to mark all notifications as read: ' . $stmt->error);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'All notifications marked as read',
            'updated_count' => $stmt->affected_rows
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Error marking all notifications as read: ' . $e->getMessage());
    }
}

function clearAllNotifications($conn, $user_id) {
    try {
        // Mark all notifications as read for this patient (soft delete by marking as read)
        $query = "UPDATE appointment_history ah
                  JOIN appointments a ON ah.appointment_id = a.id
                  SET ah.is_read = 1
                  WHERE a.patient_id = ? 
                  AND ah.event_type IN ('referred', 'treatment_changed', 'branch_changed', 'completed', 'cancelled')
                  AND (ah.is_read IS NULL OR ah.is_read = 0)";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $user_id);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to clear notifications: ' . $stmt->error);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'All notifications cleared',
            'cleared_count' => $stmt->affected_rows
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Error clearing notifications: ' . $e->getMessage());
    }
}

function generateNotificationTitle($event_type, $treatment_name) {
    $titles = [
        'referred' => '🔄 Appointment Referred',
        'treatment_changed' => '� Treatment Updated',
        'branch_changed' => '🏢 Branch Changed',
        'completed' => '🎉 Appointment Completed',
        'cancelled' => '❌ Appointment Cancelled'
    ];
    
    return $titles[$event_type] ?? '📬 Appointment Update';
}

function generateNotificationMessage($row) {
    $date = date('M j, Y', strtotime($row['appointment_date']));
    $time = date('g:i A', strtotime($row['appointment_time']));
    $treatment = $row['treatment_name'] ?: 'appointment';
    $staff = $row['staff_name'] ?: 'Staff';
    
    switch ($row['action']) {
        case 'referred':
            return "Your {$treatment} appointment has been referred to another branch for specialized care. You'll receive more details soon.";
            
        case 'treatment_changed':
            return "The treatment for your appointment on {$date} at {$time} has been updated by {$staff}.";
            
        case 'branch_changed':
            return "Your {$treatment} appointment on {$date} at {$time} has been moved to a different branch by {$staff}.";
            
        case 'cancelled':
            return "Your {$treatment} appointment on {$date} at {$time} has been cancelled by {$staff}. Please contact us to reschedule.";
            
        case 'completed':
            return "Your {$treatment} appointment on {$date} has been completed successfully. Thank you for visiting us!";
            
        default:
            return "There's an update on your {$treatment} appointment scheduled for {$date} at {$time}.";
    }
}

function formatNotificationTime($timestamp) {
    $time = strtotime($timestamp);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, g:i A', $time);
    }
}
?>