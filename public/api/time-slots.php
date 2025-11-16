<?php
/**
 * Branch Time Slots API
 * Provides available appointment time slots based on branch schedules
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../../src/config/database.php';
require_once __DIR__ . '/../../src/config/session.php';

try {
    $conn = Database::getConnection();
    
    $action = $_GET['action'] ?? '';
    $branch_id = $_GET['branch_id'] ?? '';
    $date = $_GET['date'] ?? '';
    
    switch ($action) {
        case 'get_time_slots':
            getAvailableTimeSlots($conn, $branch_id, $date);
            break;
            
        case 'get_branch_schedule':
            getBranchSchedule($conn, $branch_id);
            break;
            
        default:
            throw new Exception('Invalid action specified');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Get available time slots for a specific branch and date
 */
function getAvailableTimeSlots($conn, $branch_id, $date) {
    if (empty($branch_id) || empty($date)) {
        throw new Exception('Branch ID and date are required');
    }
    
    // Get treatment duration from request (default to 60 minutes)
    $duration = intval($_GET['duration'] ?? 60);
    
    // Validate date format
    $datetime = DateTime::createFromFormat('Y-m-d', $date);
    if (!$datetime) {
        throw new Exception('Invalid date format. Use YYYY-MM-DD');
    }
    
    // Get day of week
    $day_of_week = strtolower($datetime->format('l'));
    
    // Get branch schedule for this day
    $schedule_query = "SELECT * FROM branch_schedules 
                      WHERE branch_id = ? AND day_of_week = ? AND is_open = 1";
    $stmt = $conn->prepare($schedule_query);
    $stmt->bind_param('is', $branch_id, $day_of_week);
    $stmt->execute();
    $schedule_result = $stmt->get_result();
    
    if ($schedule_result->num_rows === 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Branch is closed on this day',
            'slots' => [],
            'day_info' => [
                'day' => ucfirst($day_of_week),
                'is_open' => false
            ]
        ]);
        return;
    }
    
    $schedule = $schedule_result->fetch_assoc();
    
    // Generate time slots
    $slots = generateTimeSlots(
        $schedule['open_time'],
        $schedule['close_time'],
        $schedule['break_start_time'],
        $schedule['break_end_time'],
        $duration
    );
    
    // Get existing appointments for this date and branch with their durations
    // This ensures that time slots are blocked for the ENTIRE duration of each appointment
    // Example: If appointment is at 9:00 AM with 1 hour duration, it blocks 9:00 AM - 10:00 AM
    //          If appointment is at 9:00 AM with 2.5 hours duration, it blocks 9:00 AM - 11:30 AM
    $appointments_query = "SELECT a.appointment_time, a.duration_minutes, tt.duration_minutes as treatment_duration
                          FROM appointments a
                          LEFT JOIN treatment_types tt ON a.treatment_type_id = tt.id
                          WHERE a.branch_id = ? AND a.appointment_date = ? 
                          AND a.status IN ('pending', 'approved')";
    $stmt = $conn->prepare($appointments_query);
    $stmt->bind_param('is', $branch_id, $date);
    $stmt->execute();
    $appointments_result = $stmt->get_result();
    
    $booked_periods = [];
    while ($appointment = $appointments_result->fetch_assoc()) {
        $start_time = substr($appointment['appointment_time'], 0, 5); // Get HH:MM format
        // Use appointment duration or treatment duration, default to 60 minutes
        $appointment_duration = $appointment['duration_minutes'] ?: $appointment['treatment_duration'] ?: 60;
        // Calculate the exact end time based on start time + duration
        $end_time = calculateEndTime($start_time, $appointment_duration);
        
        // Store the complete time period that this appointment blocks
        $booked_periods[] = [
            'start' => $start_time,
            'end' => $end_time,  // This ensures the full duration is blocked
            'duration' => $appointment_duration
        ];
    }
    
    // Get time blocks for this date and branch
    $blocks_query = "SELECT start_time, end_time, is_blocked, block_reason
                     FROM appointment_time_blocks 
                     WHERE branch_id = ? AND appointment_date = ? AND is_blocked = 1";
    $stmt = $conn->prepare($blocks_query);
    $stmt->bind_param('is', $branch_id, $date);
    $stmt->execute();
    $blocks_result = $stmt->get_result();
    
    $blocked_periods = [];
    while ($block = $blocks_result->fetch_assoc()) {
        $blocked_periods[] = [
            'start' => substr($block['start_time'], 0, 5),
            'end' => substr($block['end_time'], 0, 5),
            'type' => 'blocked', // Since we're only getting blocked periods
            'reason' => $block['block_reason']
        ];
    }
    
    // Mark slots as available or unavailable based on duration-aware blocking
    $available_slots = [];
    foreach ($slots as $slot) {
        $slot_start = $slot['time'];
        // Calculate when this new appointment would end based on treatment duration
        $slot_end = calculateEndTime($slot_start, $duration);
        
        // Check if this slot has enough time remaining in the day
        $has_sufficient_time = hasEnoughTimeRemaining($slot_start, $slot_end, $schedule['close_time']);
        
        // Check for conflicts with existing appointments
        // This properly detects overlaps: A new appointment at $slot_start would run until $slot_end
        // It conflicts if ANY part of this time range overlaps with existing booked periods
        // Example: Existing appointment 9:00-11:30, new slot 10:00-11:00 = CONFLICT detected
        $appointment_conflict = checkAppointmentConflict($slot_start, $slot_end, $booked_periods);
        
        // Check for conflicts with blocked periods
        $block_conflict = checkBlockConflict($slot_start, $slot_end, $blocked_periods);
        
        $available = $has_sufficient_time && !$appointment_conflict['has_conflict'] && !$block_conflict['has_conflict'];
        
        $reason = null;
        if (!$has_sufficient_time) {
            $reason = 'insufficient_time';
        } elseif ($appointment_conflict['has_conflict']) {
            $reason = 'overlap';
        } elseif ($block_conflict['has_conflict']) {
            $reason = 'blocked';
        }
        
        $available_slots[] = [
            'time' => $slot['time'],
            'display' => $slot['display'],
            'period' => $slot['period'],
            'emoji' => $slot['emoji'],
            'available' => $available,
            'reason' => $reason,
            'end_time' => $slot_end,
            'duration' => $duration
        ];
    }
    
    echo json_encode([
        'success' => true,
        'slots' => $available_slots,
        'day_info' => [
            'day' => ucfirst($day_of_week),
            'is_open' => true,
            'hours' => $schedule['open_time'] . ' - ' . $schedule['close_time'],
            'break' => $schedule['break_start_time'] ? $schedule['break_start_time'] . ' - ' . $schedule['break_end_time'] : null
        ],
        'total_slots' => count($available_slots),
        'available_count' => count(array_filter($available_slots, function($slot) { return $slot['available']; })),
        'booked_count' => count($booked_periods),
        'blocked_count' => count($blocked_periods),
        'duration' => $duration
    ]);
}

/**
 * Generate time slots based on operating hours
 */
function generateTimeSlots($open_time, $close_time, $break_start = null, $break_end = null, $duration = 60) {
    $slots = [];
    $interval = 30; // 30 minutes interval for slot generation (allows appointments every 30 minutes)
    
    $start = new DateTime($open_time);
    $end = new DateTime($close_time);
    $break_start_dt = $break_start ? new DateTime($break_start) : null;
    $break_end_dt = $break_end ? new DateTime($break_end) : null;
    
    // Ensure we don't generate slots that would extend beyond closing time
    $last_slot_time = clone $end;
    $last_slot_time->sub(new DateInterval('PT' . $duration . 'M'));
    
    while ($start <= $last_slot_time) {
        $time_str = $start->format('H:i');
        
        // Skip break time slots
        if ($break_start_dt && $break_end_dt) {
            if ($start >= $break_start_dt && $start < $break_end_dt) {
                $start->add(new DateInterval('PT' . $interval . 'M'));
                continue;
            }
        }
        
        // Determine emoji and period based on time
        $hour = (int)$start->format('H');
        $emoji = '🌅'; // morning
        $period = 'Morning';
        
        if ($hour >= 12 && $hour < 15) {
            $emoji = '🌇'; // afternoon
            $period = 'Afternoon';
        } elseif ($hour >= 15) {
            $emoji = '🌆'; // evening
            $period = 'Evening';
        } elseif ($hour >= 10) {
            $emoji = '🌞'; // late morning
            $period = 'Late Morning';
        }
        
        $slots[] = [
            'time' => $time_str,
            'display' => $emoji . ' ' . $start->format('h:i A'),
            'period' => $period,
            'emoji' => $emoji
        ];
        
        $start->add(new DateInterval('PT' . $interval . 'M'));
    }
    
    return $slots;
}

/**
 * Calculate end time based on start time and duration
 */
function calculateEndTime($start_time, $duration_minutes) {
    $start = new DateTime($start_time);
    $end = clone $start;
    $end->add(new DateInterval('PT' . $duration_minutes . 'M'));
    return $end->format('H:i');
}

/**
 * Check if there's enough time remaining in the day for the appointment
 */
function hasEnoughTimeRemaining($start_time, $end_time, $close_time) {
    $end = new DateTime($end_time);
    $close = new DateTime($close_time);
    return $end <= $close;
}

/**
 * Check for conflicts with existing appointments
 */
function checkAppointmentConflict($slot_start, $slot_end, $booked_periods) {
    foreach ($booked_periods as $period) {
        if (timePeriodsOverlap($slot_start, $slot_end, $period['start'], $period['end'])) {
            return [
                'has_conflict' => true,
                'conflict_type' => 'appointment',
                'details' => $period
            ];
        }
    }
    return ['has_conflict' => false];
}

/**
 * Check for conflicts with blocked time periods
 */
function checkBlockConflict($slot_start, $slot_end, $blocked_periods) {
    foreach ($blocked_periods as $period) {
        if (timePeriodsOverlap($slot_start, $slot_end, $period['start'], $period['end'])) {
            return [
                'has_conflict' => true,
                'conflict_type' => 'block',
                'details' => $period
            ];
        }
    }
    return ['has_conflict' => false];
}

/**
 * Check if two time periods overlap
 * This is the core logic that ensures appointments block the correct time range
 * 
 * Example scenarios:
 * 1. Existing appointment: 9:00-10:00, New slot: 9:00-10:00 → OVERLAP (same time)
 * 2. Existing appointment: 9:00-10:00, New slot: 9:30-10:30 → OVERLAP (starts during existing)
 * 3. Existing appointment: 9:00-11:30, New slot: 10:00-11:00 → OVERLAP (completely within)
 * 4. Existing appointment: 9:00-10:00, New slot: 10:00-11:00 → NO OVERLAP (back-to-back is OK)
 * 5. Existing appointment: 9:00-10:00, New slot: 11:00-12:00 → NO OVERLAP (completely separate)
 */
function timePeriodsOverlap($start1, $end1, $start2, $end2) {
    $start1_dt = new DateTime($start1);
    $end1_dt = new DateTime($end1);
    $start2_dt = new DateTime($start2);
    $end2_dt = new DateTime($end2);
    
    // Two periods overlap if one starts before the other ends
    // This ensures that the FULL duration of each appointment is respected
    return ($start1_dt < $end2_dt) && ($start2_dt < $end1_dt);
}

/**
 * Get complete branch schedule
 */
function getBranchSchedule($conn, $branch_id) {
    if (empty($branch_id)) {
        throw new Exception('Branch ID is required');
    }
    
    $query = "SELECT bs.*, b.name as branch_name 
              FROM branch_schedules bs 
              JOIN branches b ON bs.branch_id = b.id 
              WHERE bs.branch_id = ? 
              ORDER BY FIELD(bs.day_of_week, 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday')";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $branch_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $schedule = [];
    $branch_name = '';
    
    while ($row = $result->fetch_assoc()) {
        if (empty($branch_name)) {
            $branch_name = $row['branch_name'];
        }
        
        $schedule[] = [
            'day' => $row['day_of_week'],
            'day_display' => ucfirst($row['day_of_week']),
            'is_open' => (bool)$row['is_open'],
            'open_time' => $row['open_time'],
            'close_time' => $row['close_time'],
            'break_start_time' => $row['break_start_time'],
            'break_end_time' => $row['break_end_time'],
            'hours_display' => $row['is_open'] ? 
                $row['open_time'] . ' - ' . $row['close_time'] : 'Closed',
            'break_display' => ($row['break_start_time'] && $row['break_end_time']) ? 
                $row['break_start_time'] . ' - ' . $row['break_end_time'] : null
        ];
    }
    
    echo json_encode([
        'success' => true,
        'branch_name' => $branch_name,
        'branch_id' => $branch_id,
        'schedule' => $schedule
    ]);
}
?>