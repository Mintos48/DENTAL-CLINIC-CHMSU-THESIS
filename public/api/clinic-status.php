<?php
session_start();
require_once '../../src/config/database.php';

header('Content-Type: application/json');

// Check if user is logged in and is dentist or staff
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['dentist', 'staff', 'admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$db = Database::getConnection();
$user_id = $_SESSION['user_id'];
$branch_id = $_SESSION['branch_id'];

// Get user's branch
if (!$branch_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No branch assigned to user']);
    exit();
}

// Handle GET request - Get current and upcoming status
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Get today's status
        $today = date('Y-m-d');
        $stmt = $db->prepare("
            SELECT * FROM clinic_daily_status 
            WHERE branch_id = ? AND status_date = ?
        ");
        $stmt->bind_param("is", $branch_id, $today);
        $stmt->execute();
        $todayStatus = $stmt->get_result()->fetch_assoc();
        
        // Get upcoming status (next 7 days)
        $nextWeek = date('Y-m-d', strtotime('+7 days'));
        $stmt = $db->prepare("
            SELECT * FROM clinic_daily_status 
            WHERE branch_id = ? AND status_date BETWEEN ? AND ?
            ORDER BY status_date ASC
        ");
        $stmt->bind_param("iss", $branch_id, $today, $nextWeek);
        $stmt->execute();
        $upcomingStatus = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        echo json_encode([
            'success' => true,
            'today' => $todayStatus,
            'upcoming' => $upcomingStatus
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit();
}

// Handle POST request - Set status for a specific date
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $status_date = $data['status_date'] ?? date('Y-m-d');
    $status = $data['status'] ?? 'open';
    $reason = $data['reason'] ?? null;
    
    // Validate status
    $validStatuses = ['open', 'closed', 'busy', 'fully_booked'];
    if (!in_array($status, $validStatuses)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid status value']);
        exit();
    }
    
    // Validate date
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $status_date)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid date format']);
        exit();
    }
    
    try {
        // Check if status exists for this date
        $stmt = $db->prepare("
            SELECT id FROM clinic_daily_status 
            WHERE branch_id = ? AND status_date = ?
        ");
        $stmt->bind_param("is", $branch_id, $status_date);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        
        if ($existing) {
            // Update existing status
            $stmt = $db->prepare("
                UPDATE clinic_daily_status 
                SET status = ?, reason = ?, set_by_user_id = ?, updated_at = CURRENT_TIMESTAMP
                WHERE branch_id = ? AND status_date = ?
            ");
            $stmt->bind_param("ssiis", $status, $reason, $user_id, $branch_id, $status_date);
        } else {
            // Insert new status
            $stmt = $db->prepare("
                INSERT INTO clinic_daily_status (branch_id, status_date, status, reason, set_by_user_id)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("isssi", $branch_id, $status_date, $status, $reason, $user_id);
        }
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Status updated successfully',
                'status' => $status,
                'date' => $status_date
            ]);
        } else {
            throw new Exception('Failed to update status');
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit();
}

// Handle DELETE request - Remove status override (revert to default)
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $status_date = $_GET['date'] ?? date('Y-m-d');
    
    try {
        $stmt = $db->prepare("
            DELETE FROM clinic_daily_status 
            WHERE branch_id = ? AND status_date = ?
        ");
        $stmt->bind_param("is", $branch_id, $status_date);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Status override removed successfully'
            ]);
        } else {
            throw new Exception('Failed to remove status');
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit();
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
?>
