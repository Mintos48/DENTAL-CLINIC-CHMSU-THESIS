<!DOCTYPE html>
<html>
<head>
    <title>Clinic Status Feature - Quick Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .test-card {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .success { color: #10b981; }
        .error { color: #ef4444; }
        button {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px;
        }
        button:hover {
            background: #2563eb;
        }
    </style>
</head>
<body>
    <h1>🏥 Clinic Daily Status Feature Test</h1>
    
    <div class="test-card">
        <h2>✅ Feature Implementation Summary</h2>
        <ul>
            <li><strong>Database Table:</strong> clinic_daily_status created</li>
            <li><strong>API Endpoint:</strong> /public/api/clinic-status.php</li>
            <li><strong>Dentist Dashboard:</strong> New "Clinic Status" tab added</li>
            <li><strong>Backend Integration:</strong> ClinicDataHelper updated</li>
        </ul>
    </div>

    <div class="test-card">
        <h2>🎯 Feature Capabilities</h2>
        <h3>Dentists can now:</h3>
        <ol>
            <li><strong>Set Today's Status:</strong> Open, Closed, Busy, or Fully Booked</li>
            <li><strong>Add Reasons:</strong> Optional explanations for status changes</li>
            <li><strong>Schedule Future:</strong> Set status for upcoming dates</li>
            <li><strong>View Upcoming:</strong> See all scheduled status changes</li>
            <li><strong>Remove Schedules:</strong> Delete scheduled status overrides</li>
        </ol>
    </div>

    <div class="test-card">
        <h2>🔧 How to Test</h2>
        <ol>
            <li>Login as dentist (Dr. Maria Rodriguez - Gamboa branch)</li>
            <li>Navigate to dentist dashboard</li>
            <li>Click on "Clinic Status" tab</li>
            <li>Try setting today's status to "Closed"</li>
            <li>Visit clinic listing page</li>
            <li>Verify Gamboa Dental shows "Closed Today"</li>
        </ol>
        
        <div style="margin-top: 20px;">
            <a href="../auth/login.php">
                <button>🔐 Go to Login</button>
            </a>
            <a href="dentist-dashboard.php">
                <button>📊 Dentist Dashboard</button>
            </a>
            <a href="clinic-listing.php">
                <button>🏥 Clinic Listing</button>
            </a>
        </div>
    </div>

    <div class="test-card">
        <h2>📝 Test Credentials</h2>
        <p><strong>Dentist Account:</strong></p>
        <ul>
            <li>Email: maria.rodriguez@gamboadental.com</li>
            <li>Branch: Gamboa Dental Clinic (ID: 3)</li>
        </ul>
    </div>

    <div class="test-card">
        <h2>🔍 Quick Database Check</h2>
        <p>Current status records in database:</p>
        <?php
        require_once '../../src/config/database.php';
        $db = Database::getConnection();
        $result = $db->query("
            SELECT cds.*, b.name as branch_name 
            FROM clinic_daily_status cds
            JOIN branches b ON cds.branch_id = b.id
            ORDER BY cds.status_date DESC
            LIMIT 5
        ");
        
        if ($result && $result->num_rows > 0) {
            echo '<table border="1" cellpadding="8" style="width: 100%; border-collapse: collapse;">';
            echo '<tr><th>Branch</th><th>Date</th><th>Status</th><th>Reason</th></tr>';
            while ($row = $result->fetch_assoc()) {
                $statusColors = [
                    'open' => '#10b981',
                    'closed' => '#ef4444',
                    'busy' => '#f59e0b',
                    'fully_booked' => '#3b82f6'
                ];
                $color = $statusColors[$row['status']] ?? '#6b7280';
                echo "<tr>";
                echo "<td>{$row['branch_name']}</td>";
                echo "<td>{$row['status_date']}</td>";
                echo "<td style='color: $color; font-weight: bold;'>{$row['status']}</td>";
                echo "<td>{$row['reason']}</td>";
                echo "</tr>";
            }
            echo '</table>';
        } else {
            echo '<p style="color: #6b7280;">No status records found.</p>';
        }
        ?>
    </div>

    <div class="test-card">
        <h2>✨ What's Working</h2>
        <ul class="success">
            <li>✅ Database table created successfully</li>
            <li>✅ API endpoint responding correctly</li>
            <li>✅ Dentist dashboard tab added</li>
            <li>✅ Status buttons functional</li>
            <li>✅ Backend integration complete</li>
            <li>✅ Clinic listing will reflect changes</li>
            <li>✅ All JavaScript errors fixed (showAlert → alert)</li>
        </ul>
    </div>

    <div class="test-card">
        <h2>🎨 Status Display Examples</h2>
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-top: 15px;">
            <div style="background: #d1fae5; color: #065f46; padding: 15px; border-radius: 8px;">
                <strong>✓ Open</strong><br>
                <small>Normal operations</small>
            </div>
            <div style="background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 8px;">
                <strong>✕ Closed Today</strong><br>
                <small>Emergency closure</small>
            </div>
            <div style="background: #fef3c7; color: #92400e; padding: 15px; border-radius: 8px;">
                <strong>⏰ Busy</strong><br>
                <small>High patient volume</small>
            </div>
            <div style="background: #dbeafe; color: #1e40af; padding: 15px; border-radius: 8px;">
                <strong>✕ Fully Booked</strong><br>
                <small>No available slots</small>
            </div>
        </div>
    </div>
</body>
</html>
