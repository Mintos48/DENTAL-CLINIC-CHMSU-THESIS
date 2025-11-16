<?php
require_once '../../src/config/constants.php';
require_once '../../src/config/session.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || getSessionRole() !== ROLE_ADMIN) {
    header('Location: ../auth/login.php');
    exit();
}

$userName = getSessionName();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Dental Clinic Management</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .dashboard-tabs {
            display: flex;
            border-bottom: 2px solid #eee;
            margin-bottom: 30px;
            overflow-x: auto;
        }
        
        .tab-button {
            padding: 15px 25px;
            border: none;
            background: none;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            color: #666;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
            white-space: nowrap;
        }
        
        .tab-button.active {
            color: #054A91;
            border-bottom-color: #054A91;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .management-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .management-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .users-table, .branches-table, .reports-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .users-table th, .users-table td,
        .branches-table th, .branches-table td,
        .reports-table th, .reports-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .users-table th, .branches-table th, .reports-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        .btn-small {
            padding: 5px 10px;
            font-size: 12px;
            border-radius: 4px;
        }
        
        .system-settings {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .setting-group {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .chart-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: #000;
        }
        
        @media (max-width: 768px) {
            .admin-container {
                padding: 10px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .dashboard-tabs {
                flex-wrap: wrap;
            }
        }

        /* Modal Styles */
        .modal {
            display: block;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #f8f9fa;
            border-radius: 8px 8px 0 0;
        }

        .modal-header h3 {
            margin: 0;
            color: #333;
        }

        .close {
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: #999;
            line-height: 1;
        }

        .close:hover {
            color: #333;
        }

        .modal-body {
            padding: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #054A91;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
        }

        .modal-footer {
            padding: 15px 20px 20px;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .modal-footer button {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }

        .modal-footer button[type="submit"] {
            background-color: #054A91;
            color: white;
        }

        .modal-footer button[type="submit"]:hover {
            background-color: #043a7e;
        }

        .modal-footer button[type="button"] {
            background-color: #6c757d;
            color: white;
        }

        .modal-footer button[type="button"]:hover {
            background-color: #545b62;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="navbar-brand">
            <h1><i class="fas fa-hospital"></i> <span id="clinic-brand-name">Dental Clinic Management</span></h1>
            <span style="font-size: 0.75rem; opacity: 0.8; margin-left: var(--spacing-2);">Admin Panel</span>
        </div>
        <div class="navbar-menu">
            <ul>
                <li><span style="opacity: 0.8;"><i class="fas fa-user-shield"></i> Welcome, <?php echo htmlspecialchars($userName); ?></span></li>
                <li><a href="#logout" onclick="logout()"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
    </div>

    <div class="admin-container">
        <!-- Statistics Overview -->
        <div class="stats-grid">
            <div class="stat-card" style="border-left: 4px solid #054A91;">
                <div class="stat-number" id="total-users">...</div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card" style="border-left: 4px solid #10b981;">
                <div class="stat-number" id="total-branches">...</div>
                <div class="stat-label">Active Branches</div>
            </div>
            <div class="stat-card" style="border-left: 4px solid #f17300;">
                <div class="stat-number" id="total-appointments">...</div>
                <div class="stat-label">Total Appointments</div>
            </div>
            <div class="stat-card" style="border-left: 4px solid #3E7CB1;">
                <div class="stat-number" id="pending-appointments">...</div>
                <div class="stat-label">Pending Approvals</div>
            </div>
            <div class="stat-card" style="border-left: 4px solid #dc3545;">
                <div class="stat-number" id="system-alerts">...</div>
                <div class="stat-label">System Alerts</div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="dashboard-tabs">
            <button class="tab-button active" onclick="showTab('users')">👥 User Management</button>
            <button class="tab-button" onclick="showTab('branches')">🏢 Branch Management</button>
            <button class="tab-button" onclick="showTab('appointments')">📅 Appointments Overview</button>
            <button class="tab-button" onclick="showTab('reports')">📊 Reports & Analytics</button>
            <button class="tab-button" onclick="showTab('logs')">📋 System Logs</button>
            <button class="tab-button" onclick="showTab('settings')">⚙️ System Settings</button>
        </div>

        <!-- User Management Tab -->
        <div id="users-tab" class="tab-content active">
            <div class="management-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3>🧑‍💼 User Management</h3>
                    <div>
                        <button class="btn btn-primary" onclick="addNewUser()">➕ Add New User</button>
                        <button class="btn btn-info" onclick="exportUsers()">📤 Export Users</button>
                    </div>
                </div>
                
                <div style="display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap;">
                    <input type="text" id="user-search" placeholder="🔍 Search users..." style="flex: 1; min-width: 200px; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px;">
                    <select id="role-filter" style="min-width: 120px; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="">All Roles</option>
                        <option value="admin">Admin</option>
                        <option value="staff">Staff</option>
                        <option value="dentist">Dentist</option>
                        <option value="patient">Patient</option>
                    </select>
                </div>
                
                <div id="users-list">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>👤 Name</th>
                                <th>📧 Email</th>
                                <th>🏷️ Role</th>
                                <th>🏢 Branch</th>
                                <th>📅 Created</th>
                                <th>� Last Login</th>
                            </tr>
                        </thead>
                        <tbody id="users-tbody">
                            <!-- Dynamic content loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Branch Management Tab -->
        <div id="branches-tab" class="tab-content">
            <div class="management-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3>🏢 Branch Management</h3>
                    <button class="btn btn-primary" onclick="addNewBranch()">➕ Add New Branch</button>
                </div>
                
                <div id="branches-list">
                    <table class="branches-table">
                        <thead>
                            <tr>
                                <th>🏢 Branch Name</th>
                                <th>📍 Location</th>
                                <th>📞 Phone</th>
                                <th>📧 Email</th>
                                <th>� Operating Hours</th>
                                <th>�👥 Users</th>
                                <th>⏳ Pending</th>
                                <th>📅 Total Appointments</th>
                                <th>🔧 Actions</th>
                            </tr>
                        </thead>
                        <tbody id="branches-tbody">
                            <!-- Dynamic content loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Appointments Overview Tab -->
        <div id="appointments-tab" class="tab-content">
            <div class="chart-container">
                <h3>📈 Appointments Analytics</h3>
                <p style="color: #666; margin: 20px 0;">Real-time system-wide appointment statistics and trends</p>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">
                    <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                        <h4>Today's Total</h4>
                        <div id="todays-appointments" style="font-size: 2em; color: #054A91; font-weight: bold;">...</div>
                    </div>
                    <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                        <h4>This Week</h4>
                        <div id="week-appointments" style="font-size: 2em; color: #10b981; font-weight: bold;">...</div>
                    </div>
                    <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                        <h4>This Month</h4>
                        <div id="month-appointments" style="font-size: 2em; color: #f17300; font-weight: bold;">...</div>
                    </div>
                    <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                        <h4>Completion Rate</h4>
                        <div id="completion-rate" style="font-size: 2em; color: #3E7CB1; font-weight: bold;">...</div>
                    </div>
                </div>
            </div>
            
            <div class="management-card">
                <h3>📅 Recent System Appointments</h3>
                <div style="display: flex; gap: 15px; margin: 20px 0; flex-wrap: wrap;">
                    <input type="date" id="appointment-date-filter">
                    <select id="appointment-status-filter">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                    <select id="appointment-branch-filter">
                        <option value="">All Branches</option>
                        <!-- Options will be loaded dynamically from database -->
                    </select>
                </div>
                
                <div id="appointments-list">
                    <table class="reports-table">
                        <thead>
                            <tr>
                                <th>👤 Patient</th>
                                <th>📅 Date</th>
                                <th>⏰ Time</th>
                                <th>🏢 Branch</th>
                                <th>📊 Status</th>
                                <th>🔧 Actions</th>
                            </tr>
                        </thead>
                        <tbody id="appointments-tbody">
                            <!-- Dynamic content loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Reports & Analytics Tab -->
        <div id="reports-tab" class="tab-content">
            <div class="management-grid">
                <div class="management-card">
                    <h3>📊 Generate Reports</h3>
                    <div style="margin: 20px 0;">
                        <label>Report Type:</label>
                        <select id="report-type" style="width: 100%; margin: 10px 0;">
                            <option value="appointments">Appointments Report</option>
                            <option value="users">Users Report</option>
                            <option value="branches">Branches Performance</option>
                            <option value="system">System Usage</option>
                        </select>
                        
                        <label>Date Range:</label>
                        <div style="display: flex; gap: 10px; margin: 10px 0;">
                            <input type="date" id="report-start-date" style="flex: 1;">
                            <input type="date" id="report-end-date" style="flex: 1;">
                        </div>
                        
                        <div style="margin: 20px 0;">
                            <button class="btn btn-primary" onclick="generateReport()">📋 Generate Report</button>
                            <button class="btn btn-success" onclick="exportReport('pdf')">📄 Export PDF</button>
                            <button class="btn btn-info" onclick="exportReport('excel')">📊 Export Excel</button>
                        </div>
                    </div>
                </div>
                
                <div class="management-card">
                    <h3>📈 Key Metrics</h3>
                    <div style="margin: 15px 0; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                        <strong>📅 This Month:</strong>
                        <div>Total Appointments: <span id="reports-month-appointments" style="color: #054A91; font-weight: bold;">...</span></div>
                        <div>Completion Rate: <span id="reports-completion-rate" style="color: #10b981; font-weight: bold;">...</span></div>
                        <div>Average Wait Time: <span id="reports-wait-time" style="color: #3E7CB1; font-weight: bold;">...</span></div>
                    </div>
                    
                    <div style="margin: 15px 0; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                        <strong>👥 Users:</strong>
                        <div>New Registrations: <span id="reports-new-users" style="color: #054A91; font-weight: bold;">...</span></div>
                        <div>Active Users: <span id="reports-active-users" style="color: #10b981; font-weight: bold;">...</span></div>
                        <div>Patient Growth: <span id="reports-patient-growth" style="color: #f17300; font-weight: bold;">...</span></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Logs Tab -->
        <div id="logs-tab" class="tab-content">
            <div class="management-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3>📋 System Activity Logs</h3>
                    <div>
                        <button class="btn btn-info" onclick="exportSystemLogs()">📤 Export Logs</button>
                    </div>
                </div>
                
                <div style="display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap;">
                    <select id="log-action-filter" onchange="filterSystemLogs()" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; min-width: 200px;">
                        <option value="">All Actions</option>
                        <option value="login">Login</option>
                        <option value="logout">Logout</option>
                        <option value="appointment_booked">Appointment Booked</option>
                        <option value="appointment_approved">Appointment Approved</option>
                        <option value="appointment_completed">Appointment Completed</option>
                        <option value="appointment_cancelled">Appointment Cancelled</option>
                        <option value="user_created">User Created</option>
                        <option value="user_updated">User Updated</option>
                        <option value="user_deleted">User Deleted</option>
                        <option value="branch_created">Branch Created</option>
                        <option value="branch_updated">Branch Updated</option>
                        <option value="branch_deleted">Branch Deleted</option>
                        <option value="settings_updated">Settings Updated</option>
                    </select>
                    <input type="date" id="log-date-filter" onchange="filterSystemLogs()" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                
                <div id="system-logs-list">
                    <table class="reports-table">
                        <thead>
                            <tr>
                                <th>🕐 Timestamp</th>
                                <th>👤 User</th>
                                <th>🔧 Action</th>
                                <th>📝 Description</th>
                                <th>🌐 IP Address</th>
                            </tr>
                        </thead>
                        <tbody id="logs-tbody">
                            <!-- Dynamic content will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- System Settings Tab -->
        <div id="settings-tab" class="tab-content">
            <div class="system-settings">
                <div class="setting-group">
                    <h3>🔧 General Settings</h3>
                    <div style="margin: 15px 0;">
                        <label>System Name:</label>
                        <input type="text" id="system_name" value="Dental Clinic Management System" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                    <div style="margin: 15px 0;">
                        <label>Default Appointment Duration (minutes):</label>
                        <input type="number" id="appointment_duration" value="30" min="15" max="120" step="15" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                    <div style="margin: 15px 0;">
                        <label>Time Zone:</label>
                        <select id="timezone" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="Asia/Manila" selected>Asia/Manila (PHT)</option>
                            <option value="UTC">UTC</option>
                            <option value="America/New_York">America/New_York (EST)</option>
                            <option value="Europe/London">Europe/London (GMT)</option>
                        </select>
                    </div>
                    <div style="margin: 15px 0;">
                        <label>Max Appointments Per Day:</label>
                        <input type="number" id="max_appointments_per_day" value="50" min="10" max="200" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                </div>
                
                <div class="setting-group">
                    <h3>🔐 Security Settings</h3>
                    <div style="margin: 15px 0;">
                        <label>
                            <input type="checkbox" id="require_email_verification" checked> 
                            Require email verification for new accounts
                        </label>
                    </div>
                    <div style="margin: 15px 0;">
                        <label>Password Expiry (days):</label>
                        <input type="number" id="password_expiry_days" value="90" min="0" max="365" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                    <div style="margin: 15px 0;">
                        <label>Session Timeout (minutes):</label>
                        <input type="number" id="session_timeout" value="60" min="15" max="480" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                </div>
                
                <div class="setting-group">
                    <h3>📧 Notification Settings</h3>
                    <div style="margin: 15px 0;">
                        <label>
                            <input type="checkbox" id="send_email_notifications" checked> 
                            Send email notifications
                        </label>
                    </div>
                    <div style="margin: 15px 0;">
                        <label>Notification Email:</label>
                        <input type="email" id="notification_email" placeholder="admin@dentalclinic.com" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                    <div style="margin: 15px 0;">
                        <label>Reminder Lead Time (hours before appointment):</label>
                        <input type="number" id="reminder_lead_time" value="24" min="1" max="168" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                </div>
                
                <div class="setting-group">
                    <h3>🗄️ Database Management</h3>
                    <div style="margin: 15px 0;">
                        <button class="btn btn-info" onclick="backupDatabase()">💾 Backup Database</button>
                    </div>
                </div>
            </div>
            
            <div style="margin: 30px 0; text-align: center;">
                <button class="btn btn-success btn-large" onclick="saveSettings()">💾 Save All Settings</button>
            </div>
        </div>
    </div>

    <script>
        let currentTab = 'users';
        
        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            // Set default dates for reports
            const today = new Date();
            const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, today.getDate());
            document.getElementById('report-start-date').value = lastMonth.toISOString().split('T')[0];
            document.getElementById('report-end-date').value = today.toISOString().split('T')[0];
            
            // Load clinic branding
            loadClinicBranding();
            
            // Load dynamic data from database
            loadSystemStats();
            loadAllUsers();
            loadAllBranches();
            loadSystemLogs();
            loadAppointmentsOverview();
            loadAnalyticsData();
            
            // Setup search and filters
            setupUserSearchAndFilter();
            
            // Auto-refresh every 60 seconds
            setInterval(() => {
                loadSystemStats();
                loadAnalyticsData();
            }, 60000);
        });

        function loadClinicBranding() {
            // Load primary branch or system name
            fetch('/dental-clinic-chmsu-thesis/src/controllers/AdminController.php?action=getAllBranches')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.branches && data.branches.length > 0) {
                    // Use the first active branch name as the clinic brand
                    const primaryBranch = data.branches.find(b => b.status === 'active') || data.branches[0];
                    const brandNameElement = document.getElementById('clinic-brand-name');
                    if (brandNameElement) {
                        // Extract the main clinic name (before "Dental")
                        const clinicName = primaryBranch.name.includes('Dental') 
                            ? primaryBranch.name.split('Dental')[0].trim() 
                            : primaryBranch.name;
                        brandNameElement.textContent = clinicName + ' Management System';
                    }
                }
            })
            .catch(error => {
                console.error('Error loading clinic branding:', error);
                // Keep default "Dental Clinic Management"
            });
        }

        function loadSystemStats() {
            fetch('/dental-clinic-chmsu-thesis/src/controllers/AdminController.php?action=getSystemStats')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateStatistics(data.stats);
                } else {
                    console.error('Failed to load system stats:', data.message);
                }
            })
            .catch(error => {
                console.error('Error loading system stats:', error);
            });
        }

        function updateStatistics(stats) {
            document.getElementById('total-users').textContent = stats.total_users || 0;
            document.getElementById('total-branches').textContent = stats.total_branches || 0;
            document.getElementById('total-appointments').textContent = stats.total_appointments || 0;
            document.getElementById('pending-appointments').textContent = stats.pending_appointments || 0;
            document.getElementById('system-alerts').textContent = stats.system_alerts || 0;
        }

        function loadAllUsers() {
            console.log('Loading users...');
            fetch('/dental-clinic-chmsu-thesis/src/controllers/AdminController.php?action=getAllUsers')
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                return response.text(); // Get raw text first
            })
            .then(text => {
                console.log('Raw response text:', text);
                try {
                    const data = JSON.parse(text);
                    console.log('Parsed JSON data:', data);
                    if (data.success) {
                        console.log('Number of users:', data.users.length);
                        
                        // Store users globally BEFORE displaying
                        window.allUsers = data.users;
                        console.log('Stored', window.allUsers.length, 'users globally');
                        
                        // Display the users
                        displayUsersTable(data.users);
                        
                        // Setup filters AFTER data is loaded
                        setTimeout(() => {
                            setupUserSearchAndFilter();
                        }, 100);
                    } else {
                        console.error('Failed to load users:', data.message);
                        const usersList = document.getElementById('users-tbody');
                        if (usersList) {
                            usersList.innerHTML = '<tr><td colspan="6" style="text-align: center; color: #dc3545; padding: 30px;">Error loading users: ' + data.message + '</td></tr>';
                        }
                    }
                } catch (jsonError) {
                    console.error('JSON parsing error:', jsonError);
                    console.error('Response was not valid JSON:', text);
                    const usersList = document.getElementById('users-tbody');
                    if (usersList) {
                        usersList.innerHTML = '<tr><td colspan="6" style="text-align: center; color: #dc3545; padding: 30px;">Invalid response from server. Check console for details.</td></tr>';
                    }
                }
            })
            .catch(error => {
                console.error('Network error loading users:', error);
                const usersList = document.getElementById('users-tbody');
                if (usersList) {
                    usersList.innerHTML = '<tr><td colspan="6" style="text-align: center; color: #dc3545; padding: 30px;">Network error loading users</td></tr>';
                }
            });
        }

        function displayUsersTable(users) {
            console.log('Displaying users table with data:', users);
            
            // Find the users table in the dashboard and update it
            const usersList = document.getElementById('users-tbody');
            console.log('Users table element found:', usersList);
            if (!usersList) return;
            
            // Handle empty state
            if (!users || users.length === 0) {
                usersList.innerHTML = '<tr><td colspan="6" style="text-align: center; color: #666; padding: 30px;">No users found</td></tr>';
                return;
            }
            
            let html = '';
            users.forEach(user => {
                console.log('Processing user:', user);
                const roleColors = {
                    'admin': '#dc3545',
                    'staff': '#10b981', 
                    'patient': '#054A91',
                    'dentist': '#9333ea'  // Purple color for dentist
                };
                
                html += `
                    <tr data-user-id="${user.id}">
                        <td><strong>${user.name}</strong></td>
                        <td>${user.email}</td>
                        <td><span style="background: ${roleColors[user.role] || '#6c757d'}; color: white; padding: 4px 8px; border-radius: 12px; font-size: 12px;">${user.role.toUpperCase()}</span></td>
                        <td>${user.branch_name || 'N/A'}</td>
                        <td>${new Date(user.created_at).toLocaleDateString()}</td>
                        <td>${user.last_login ? new Date(user.last_login).toLocaleDateString() : 'Never'}</td>
                    </tr>
                `;
            });
            
            console.log('Setting innerHTML with', users.length, 'users');
            usersList.innerHTML = html;
        }

        // Debounce function for better performance
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        // User search and filter functionality with best practices
        let userSearchDebounced = null;
        
        function setupUserSearchAndFilter() {
            const searchInput = document.getElementById('user-search');
            const roleFilter = document.getElementById('role-filter');
            
            // Create debounced version of filter function (300ms delay)
            userSearchDebounced = debounce(filterUsers, 300);
            
            if (searchInput) {
                // Clear any existing value and listeners
                searchInput.value = '';
                
                // Remove old listeners
                const newSearchInput = searchInput.cloneNode(true);
                searchInput.parentNode.replaceChild(newSearchInput, searchInput);
                
                // Add event listener to the new element
                newSearchInput.addEventListener('input', function(e) {
                    console.log('Search input changed:', e.target.value);
                    userSearchDebounced();
                });
                
                console.log('User search listener attached with debouncing');
            }
            
            if (roleFilter) {
                // Clear any existing selection
                roleFilter.value = '';
                
                // Remove old listeners
                const newRoleFilter = roleFilter.cloneNode(true);
                roleFilter.parentNode.replaceChild(newRoleFilter, roleFilter);
                
                // Add event listener to the new element
                newRoleFilter.addEventListener('change', function(e) {
                    console.log('Role filter changed:', e.target.value);
                    filterUsers(); // No debounce for dropdown changes
                });
                
                console.log('Role filter listener attached');
            }
        }

        function filterUsers() {
            // Get fresh references to elements
            const searchInput = document.getElementById('user-search');
            const roleFilterSelect = document.getElementById('role-filter');
            
            if (!window.allUsers || window.allUsers.length === 0) {
                console.warn('No users data available for filtering');
                return;
            }
            
            const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
            const roleFilter = roleFilterSelect ? roleFilterSelect.value : '';
            
            console.log('Filtering users - Search:', searchTerm, 'Role:', roleFilter, 'Total users:', window.allUsers.length);
            
            // If both filters are empty, show all users
            if (!searchTerm && !roleFilter) {
                console.log('No filters - showing all', window.allUsers.length, 'users');
                displayUsersTable(window.allUsers);
                return;
            }
            
            // Filter the users
            const filteredUsers = window.allUsers.filter(user => {
                let matchesSearch = true;
                let matchesRole = true;
                
                // Check search term
                if (searchTerm) {
                    const nameMatch = user.name && user.name.toLowerCase().includes(searchTerm);
                    const emailMatch = user.email && user.email.toLowerCase().includes(searchTerm);
                    const phoneMatch = user.phone && user.phone.toLowerCase().includes(searchTerm);
                    matchesSearch = nameMatch || emailMatch || phoneMatch;
                }
                
                // Check role filter
                if (roleFilter) {
                    matchesRole = user.role === roleFilter;
                }
                
                return matchesSearch && matchesRole;
            });
            
            console.log(`Filtered ${filteredUsers.length} users from ${window.allUsers.length} total`);
            displayUsersTable(filteredUsers);
        }

        function loadAllBranches() {
            fetch('/dental-clinic-chmsu-thesis/src/controllers/AdminController.php?action=getAllBranches')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayBranchesTable(data.branches);
                    // Store branches data globally for use in modals
                    window.branchesData = data.branches;
                } else {
                    console.error('Failed to load branches:', data.message);
                }
            })
            .catch(error => {
                console.error('Error loading branches:', error);
            });
        }

        function displayBranchesTable(branches) {
            // Find the branches table in the dashboard and update it
            const branchesList = document.getElementById('branches-tbody');
            if (!branchesList) return;
            
            if (!branches || branches.length === 0) {
                branchesList.innerHTML = '<tr><td colspan="8" style="text-align: center; color: #666; padding: 30px;">No branches found</td></tr>';
                return;
            }
            
            let html = '';
            branches.forEach(branch => {
                html += `
                    <tr>
                        <td>
                            <strong>${branch.name}</strong>
                            ${branch.code ? '<br><small style="color: #666;">Code: ' + branch.code + '</small>' : ''}
                        </td>
                        <td>${branch.location || 'N/A'}</td>
                        <td>${branch.phone || 'N/A'}</td>
                        <td>${branch.email || 'N/A'}</td>
                        <td>${branch.operating_hours || 'N/A'}</td>
                        <td><span style="background: #054A91; color: white; padding: 2px 8px; border-radius: 12px; font-size: 11px;">${branch.user_count}</span></td>
                        <td><span style="background: #f17300; color: white; padding: 2px 8px; border-radius: 12px; font-size: 11px;">${branch.pending_count}</span></td>
                        <td><span style="background: #10b981; color: white; padding: 2px 8px; border-radius: 12px; font-size: 11px;">${branch.total_appointments}</span></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-small btn-info" onclick="viewBranchDetails(${branch.id})" title="View Details">👁️</button>
                                <button class="btn btn-small btn-warning" onclick="editBranch(${branch.id})" title="Edit">✏️</button>
                                ${branch.user_count == 0 ? 
                                    '<button class="btn btn-small btn-danger" onclick="deleteBranch(' + branch.id + ')" title="Delete">🗑️</button>' : 
                                    '<button class="btn btn-small" disabled title="Cannot delete - has users" style="opacity: 0.5; cursor: not-allowed;">🗑️</button>'
                                }
                            </div>
                        </td>
                    </tr>
                `;
            });
            
            branchesList.innerHTML = html;
        }

        function loadSystemLogs() {
            console.log('Loading system logs...');
            
            // Get filter values
            const actionFilter = document.getElementById('log-action-filter')?.value || '';
            const dateFilter = document.getElementById('log-date-filter')?.value || '';
            
            console.log('Filters - Action:', actionFilter, 'Date:', dateFilter);
            
            // Build URL with proper parameter naming
            let url = '/dental-clinic-chmsu-thesis/src/controllers/AdminController.php?action=getSystemLogs&limit=100';
            
            if (actionFilter) {
                url += '&log_action=' + encodeURIComponent(actionFilter);
            }
            
            if (dateFilter) {
                url += '&log_date=' + encodeURIComponent(dateFilter);
            }
            
            console.log('Fetching logs from:', url);
            
            fetch(url)
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Logs data received:', data);
                if (data.success) {
                    console.log(`Received ${data.logs.length} logs`);
                    displaySystemLogs(data.logs);
                } else {
                    console.error('Failed to load system logs:', data.message);
                    const logsList = document.getElementById('logs-tbody');
                    if (logsList) {
                        logsList.innerHTML = '<tr><td colspan="5" style="text-align: center; color: #dc3545; padding: 30px;">Error: ' + data.message + '</td></tr>';
                    }
                }
            })
            .catch(error => {
                console.error('Error loading system logs:', error);
                const logsList = document.getElementById('logs-tbody');
                if (logsList) {
                    logsList.innerHTML = '<tr><td colspan="5" style="text-align: center; color: #dc3545; padding: 30px;">Network error loading logs. Check console for details.</td></tr>';
                }
            });
        }

        function displaySystemLogs(logs) {
            // Find the logs table in the dashboard and update it
            const logsList = document.getElementById('logs-tbody');
            if (!logsList) {
                console.error('Logs table body not found');
                return;
            }
            
            console.log('Displaying system logs:', logs);
            
            if (!logs || logs.length === 0) {
                logsList.innerHTML = '<tr><td colspan="5" style="text-align: center; color: #666; padding: 30px;">No logs found</td></tr>';
                return;
            }
            
            let html = '';
            logs.forEach(log => {
                const actionColors = {
                    'login': '#10b981',
                    'logout': '#6c757d',
                    'appointment_booked': '#054A91',
                    'appointment_approved': '#10b981',
                    'appointment_completed': '#3E7CB1',
                    'appointment_cancelled': '#dc3545',
                    'user_created': '#3E7CB1',
                    'user_updated': '#f17300',
                    'user_deleted': '#dc3545',
                    'branch_created': '#10b981',
                    'branch_updated': '#f17300',
                    'branch_deleted': '#dc3545',
                    'settings_updated': '#9333ea'
                };
                
                const actionColor = actionColors[log.action] || '#6c757d';
                const userName = log.user_name || 'Unknown User';
                const userEmail = log.user_email || 'N/A';
                const action = log.action || 'unknown';
                const description = log.description || 'No description';
                const ipAddress = log.ip_address || 'N/A';
                const createdAt = log.created_at ? new Date(log.created_at).toLocaleString() : 'N/A';
                
                html += `
                    <tr>
                        <td>${createdAt}</td>
                        <td>${userName}<br><small style="color: #666;">${userEmail}</small></td>
                        <td><span style="background: ${actionColor}; color: white; padding: 2px 8px; border-radius: 12px; font-size: 11px;">${action}</span></td>
                        <td>${description}</td>
                        <td>${ipAddress}</td>
                    </tr>
                `;
            });
            
            logsList.innerHTML = html;
            console.log(`Displayed ${logs.length} log entries`);
        }

        function filterSystemLogs() {
            console.log('Filtering system logs...');
            loadSystemLogs();
        }

        function loadAppointmentsOverview() {
            // Get current filter values
            const dateFilter = document.getElementById('appointment-date-filter')?.value || '';
            const statusFilter = document.getElementById('appointment-status-filter')?.value || '';
            const branchFilter = document.getElementById('appointment-branch-filter')?.value || '';
            
            let url = '/dental-clinic-chmsu-thesis/src/controllers/AdminController.php?action=getAllAppointments';
            const params = new URLSearchParams();
            
            if (dateFilter) params.append('date', dateFilter);
            if (statusFilter) params.append('status', statusFilter);
            if (branchFilter) params.append('branch_id', branchFilter);
            
            if (params.toString()) {
                url += '&' + params.toString();
            }
            
            fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayAppointmentsTable(data.appointments);
                    
                    // Populate branch filter dropdown if not already done
                    populateBranchFilter();
                } else {
                    console.error('Failed to load appointments overview:', data.message);
                }
            })
            .catch(error => {
                console.error('Error loading appointments overview:', error);
            });
        }

        function populateBranchFilter() {
            const branchFilter = document.getElementById('appointment-branch-filter');
            if (!branchFilter) return;
            
            // If already populated, skip
            if (branchFilter.dataset.populated === 'true') return;
            
            // Use cached branches data if available
            if (window.branchesData && window.branchesData.length > 0) {
                let options = '<option value="">All Branches</option>';
                window.branchesData.forEach(branch => {
                    options += `<option value="${branch.id}">${branch.name}</option>`;
                });
                branchFilter.innerHTML = options;
                branchFilter.dataset.populated = 'true';
                console.log('Branch filter populated with', window.branchesData.length, 'branches from cache');
            } else {
                // If branches data not available, fetch it
                console.log('Branches data not available, fetching from database...');
                fetch('/dental-clinic-chmsu-thesis/src/controllers/AdminController.php?action=getAllBranches')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.branches) {
                        window.branchesData = data.branches;
                        let options = '<option value="">All Branches</option>';
                        data.branches.forEach(branch => {
                            options += `<option value="${branch.id}">${branch.name}</option>`;
                        });
                        branchFilter.innerHTML = options;
                        branchFilter.dataset.populated = 'true';
                        console.log('Branch filter populated with', data.branches.length, 'branches from database');
                    }
                })
                .catch(error => {
                    console.error('Error loading branches for filter:', error);
                });
            }
        }

        function displayAppointmentsTable(appointments) {
            const appointmentsList = document.getElementById('appointments-tbody');
            if (!appointmentsList) return;
            
            if (!appointments || appointments.length === 0) {
                appointmentsList.innerHTML = '<tr><td colspan="6" style="text-align: center; color: #666; padding: 30px;">No appointments found</td></tr>';
                return;
            }
            
            let html = '';
            appointments.forEach(appointment => {
                const statusColors = {
                    'pending': '#f17300',
                    'approved': '#10b981',
                    'completed': '#054A91',
                    'cancelled': '#dc3545'
                };
                
                html += `
                    <tr>
                        <td>
                            <strong>${appointment.patient_name}</strong>
                            <br><small style="color: #666;">${appointment.patient_email}</small>
                        </td>
                        <td>${new Date(appointment.appointment_date).toLocaleDateString()}</td>
                        <td><strong>${appointment.appointment_time}</strong></td>
                        <td>${appointment.branch_name}</td>
                        <td>
                            <span style="background: ${statusColors[appointment.status] || '#6c757d'}; color: white; padding: 4px 8px; border-radius: 12px; font-size: 12px;">${appointment.status.toUpperCase()}</span>
                            ${appointment.status === 'approved' && appointment.has_prescription ? 
                                '<br><span style="background: #10b981; color: white; padding: 3px 6px; border-radius: 8px; font-size: 10px; display: inline-block; margin-top: 4px;">💊 Prescription Added</span>' : ''
                            }
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-small btn-info" onclick="viewAppointmentDetails('${appointment.id}')" title="View Details">👁️</button>
                                ${appointment.status === 'pending' ? 
                                    '<button class="btn btn-small btn-success" onclick="approveAppointment(' + appointment.id + ')" title="Approve">✅</button>' : ''
                                }
                                ${appointment.status === 'approved' ? 
                                    '<button class="btn btn-small btn-primary" onclick="markAsCompleted(' + appointment.id + ')" title="Mark as Completed">✓</button>' : ''
                                }
                                ${appointment.status === 'approved' && !appointment.has_prescription ? 
                                    '<button class="btn btn-small btn-warning" onclick="alert(\'Prescription feature coming soon!\')" title="Needs Prescription">⚠️ Needs Prescription</button>' : ''
                                }
                            </div>
                        </td>
                    </tr>
                `;
            });
            
            appointmentsList.innerHTML = html;
        }

        // Function to mark appointment as completed
        function markAsCompleted(appointmentId) {
            if (!confirm('Mark this appointment as completed?')) {
                return;
            }

            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '⏳';
            button.disabled = true;

            fetch('/dental-clinic-chmsu-thesis/src/controllers/AdminController.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ 
                    action: 'markAppointmentCompleted',
                    appointment_id: appointmentId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Appointment marked as completed!', 'success');
                    loadAppointmentsOverview();
                    loadSystemStats();
                } else {
                    showAlert('Error: ' + (data.message || 'Failed to update appointment'), 'danger');
                    button.innerHTML = originalText;
                    button.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('An error occurred', 'danger');
                button.innerHTML = originalText;
                button.disabled = false;
            });
        }

        function loadAnalyticsData() {
            fetch('/dental-clinic-chmsu-thesis/src/controllers/AdminController.php?action=getAnalyticsData')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateAnalyticsDisplay(data.analytics);
                } else {
                    console.error('Failed to load analytics data:', data.message);
                }
            })
            .catch(error => {
                console.error('Error loading analytics data:', error);
            });
        }

        function updateAnalyticsDisplay(analytics) {
            // Update the analytics cards in appointments tab using IDs
            document.getElementById('todays-appointments').textContent = analytics.todays_appointments || 0;
            document.getElementById('week-appointments').textContent = analytics.week_appointments || 0;
            document.getElementById('month-appointments').textContent = analytics.month_appointments || 0;
            document.getElementById('completion-rate').textContent = (analytics.completion_rate || 0) + '%';
            
            // Update reports tab metrics if visible
            updateReportsMetrics(analytics);
        }

        function updateReportsMetrics(analytics) {
            // Update the key metrics in reports tab using IDs
            document.getElementById('reports-month-appointments').textContent = analytics.month_appointments || 0;
            document.getElementById('reports-completion-rate').textContent = (analytics.completion_rate || 0) + '%';
            document.getElementById('reports-wait-time').textContent = analytics.avg_wait_time || '2.3 days';
            document.getElementById('reports-new-users').textContent = analytics.new_users_month || 0;
            document.getElementById('reports-active-users').textContent = analytics.active_users || 0;
            document.getElementById('reports-patient-growth').textContent = (analytics.patient_growth || 0) + '%';
        }

        // Add filter event listeners for appointments
        function attachAppointmentFilters() {
            const dateFilter = document.getElementById('appointment-date-filter');
            const statusFilter = document.getElementById('appointment-status-filter');
            const branchFilter = document.getElementById('appointment-branch-filter');
            
            if (dateFilter) dateFilter.addEventListener('change', loadAppointmentsOverview);
            if (statusFilter) statusFilter.addEventListener('change', loadAppointmentsOverview);
            if (branchFilter) branchFilter.addEventListener('change', loadAppointmentsOverview);
        }

        // Tab management
        function showTab(tabName) {
            // Hide all tabs
            const tabs = document.querySelectorAll('.tab-content');
            tabs.forEach(tab => tab.classList.remove('active'));
            
            // Remove active class from all buttons
            const buttons = document.querySelectorAll('.tab-button');
            buttons.forEach(btn => btn.classList.remove('active'));
            
            // Show selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
            event.target.classList.add('active');
            
            // Load data specific to the tab
            switch(tabName) {
                case 'users':
                    loadAllUsers();
                    break;
                case 'branches':
                    loadAllBranches();
                    break;
                case 'appointments':
                    loadAppointmentsOverview();
                    loadAnalyticsData();
                    // Attach event listeners for filters
                    setTimeout(attachAppointmentFilters, 100);
                    break;
                case 'reports':
                    loadAnalyticsData();
                    break;
                case 'logs':
                    loadSystemLogs();
                    break;
                case 'settings':
                    loadSettings();
                    break;
            }
            
            currentTab = tabName;
        }

        // User management functions
        function addNewUser() {
            showAddUserModal();
        }

        function showAddUserModal() {
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.innerHTML = `
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Add New User</h3>
                        <span class="close" onclick="closeModal(this)">&times;</span>
                    </div>
                    <form id="add-user-form" class="modal-body">
                        <div class="form-group">
                            <label for="first_name">First Name:</label>
                            <input type="text" id="first_name" name="first_name" required>
                        </div>
                        <div class="form-group">
                            <label for="last_name">Last Name:</label>
                            <input type="text" id="last_name" name="last_name" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email:</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone:</label>
                            <input type="tel" id="phone" name="phone" required>
                        </div>
                        <div class="form-group">
                            <label for="role">Role:</label>
                            <select id="role" name="role" required>
                                <option value="">Select Role</option>
                                <option value="admin">Admin</option>
                                <option value="staff">Staff</option>
                                <option value="dentist">Dentist</option>
                                <option value="patient">Patient</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="branch_id">Branch:</label>
                            <select id="branch_id" name="branch_id" required>
                                <option value="">Select Branch</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="password">Password:</label>
                            <input type="password" id="password" name="password" required minlength="6">
                        </div>
                        <div class="modal-footer">
                            <button type="button" onclick="closeModal(this)">Cancel</button>
                            <button type="submit">Add User</button>
                        </div>
                    </form>
                </div>
            `;
            
            document.body.appendChild(modal);
            loadBranchOptions();
            
            // Handle form submission
            document.getElementById('add-user-form').addEventListener('submit', handleAddUser);
        }

        function loadBranchOptions() {
            const branchSelect = document.getElementById('branch_id');
            if (!branchSelect) return;
            
            // Use the branches data we already have
            if (window.branchesData && window.branchesData.length > 0) {
                branchSelect.innerHTML = '<option value="">Select Branch</option>';
                window.branchesData.forEach(branch => {
                    const option = document.createElement('option');
                    option.value = branch.id;
                    option.textContent = branch.name;
                    branchSelect.appendChild(option);
                });
            }
        }

        function handleAddUser(event) {
            event.preventDefault();
            
            const formData = new FormData(event.target);
            const userData = {
                action: 'addUser',
                first_name: formData.get('first_name'),
                last_name: formData.get('last_name'),
                email: formData.get('email'),
                phone: formData.get('phone'),
                role: formData.get('role'),
                branch_id: formData.get('branch_id'),
                password: formData.get('password')
            };

            fetch('/dental-clinic-chmsu-thesis/src/controllers/AdminController.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(userData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('✅ User added successfully!', 'success');
                    closeModal(document.querySelector('.modal .close'));
                    
                    // Refresh both stats and users table
                    loadSystemStats();
                    loadAllUsers(); // This will update the table with the new user
                } else {
                    showAlert('Error: ' + (data.message || 'Failed to add user'), 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('An error occurred while adding the user', 'danger');
            });
        }

        function closeModal(element) {
            const modal = element.closest('.modal');
            if (modal) {
                modal.remove();
            }
        }

        function exportUsers() {
            fetch('/dental-clinic-chmsu-thesis/src/controllers/AdminController.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ action: 'exportUsers' })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Create CSV content
                    const csvContent = generateCSV(data.users);
                    
                    // Create and download file
                    const blob = new Blob([csvContent], { type: 'text/csv' });
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `users_export_${new Date().toISOString().split('T')[0]}.csv`;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    window.URL.revokeObjectURL(url);
                    
                    showAlert('Users exported successfully!', 'success');
                } else {
                    showAlert('Error exporting users: ' + (data.message || 'Unknown error'), 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('An error occurred while exporting users', 'error');
            });
        }

        function generateCSV(users) {
            const headers = ['ID', 'First Name', 'Last Name', 'Email', 'Phone', 'Role', 'Branch', 'Created At'];
            const csvRows = [headers.join(',')];
            
            users.forEach(user => {
                const row = [
                    user.id,
                    `"${user.first_name}"`,
                    `"${user.last_name}"`,
                    `"${user.email}"`,
                    `"${user.phone}"`,
                    `"${user.role}"`,
                    `"${user.branch_name || 'N/A'}"`,
                    `"${user.created_at}"`
                ];
                csvRows.push(row.join(','));
            });
            
            return csvRows.join('\n');
        }

        // Branch management functions
        function addNewBranch() {
            showAddBranchModal();
        }
        
        function showAddBranchModal() {
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.innerHTML = `
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Add New Branch</h3>
                        <span class="close" onclick="closeModal(this)">&times;</span>
                    </div>
                    <form id="add-branch-form" class="modal-body">
                        <div class="form-group">
                            <label for="branch_name">Branch Name:</label>
                            <input type="text" id="branch_name" name="branch_name" required>
                        </div>
                        <div class="form-group">
                            <label for="branch_code">Branch Code:</label>
                            <input type="text" id="branch_code" name="branch_code" placeholder="e.g., TLS, SLY" required>
                        </div>
                        <div class="form-group">
                            <label for="branch_location">Location:</label>
                            <input type="text" id="branch_location" name="branch_location" required>
                        </div>
                        <div class="form-group">
                            <label for="branch_phone">Phone:</label>
                            <input type="tel" id="branch_phone" name="branch_phone" required>
                        </div>
                        <div class="form-group">
                            <label for="branch_email">Email:</label>
                            <input type="email" id="branch_email" name="branch_email" required>
                        </div>
                        <div class="form-group">
                            <label for="branch_operating_hours">Operating Hours:</label>
                            <input type="text" id="branch_operating_hours" name="branch_operating_hours" placeholder="e.g., Mon-Fri: 8AM-5PM">
                        </div>
                        <div class="modal-footer">
                            <button type="button" onclick="closeModal(this)">Cancel</button>
                            <button type="submit">Add Branch</button>
                        </div>
                    </form>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Handle form submission
            document.getElementById('add-branch-form').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(e.target);
                const branchData = {
                    action: 'addBranch',
                    name: formData.get('branch_name'),
                    code: formData.get('branch_code'),
                    location: formData.get('branch_location'),
                    phone: formData.get('branch_phone'),
                    email: formData.get('branch_email'),
                    operating_hours: formData.get('branch_operating_hours')
                };
                
                // Disable submit button
                const submitBtn = e.target.querySelector('button[type="submit"]');
                submitBtn.disabled = true;
                submitBtn.textContent = 'Adding...';
                
                fetch('/dental-clinic-chmsu-thesis/src/controllers/AdminController.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(branchData)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('Branch added successfully!', 'success');
                        closeModal(document.querySelector('.modal .close'));
                        loadAllBranches(); // Refresh the branches list
                        loadSystemStats(); // Refresh statistics
                    } else {
                        showAlert('Error: ' + (data.message || 'Failed to add branch'), 'error');
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Add Branch';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('An error occurred while adding the branch', 'error');
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Add Branch';
                });
            });
        }
        
        function viewBranchDetails(branchId) {
            const branch = window.branchesData.find(b => b.id == branchId);
            if (!branch) {
                showAlert('Branch not found', 'error');
                return;
            }
            
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.innerHTML = `
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>📋 Branch Details</h3>
                        <span class="close" onclick="closeModal(this)">&times;</span>
                    </div>
                    <div class="modal-body">
                        <div style="padding: 15px;">
                            <h4 style="color: #054A91; margin-bottom: 15px;">${branch.name}</h4>
                            ${branch.code ? '<p><strong>Code:</strong> ' + branch.code + '</p>' : ''}
                            <p><strong>📍 Location:</strong> ${branch.location || 'N/A'}</p>
                            <p><strong>📞 Phone:</strong> ${branch.phone || 'N/A'}</p>
                            <p><strong>📧 Email:</strong> ${branch.email || 'N/A'}</p>
                            <p><strong>🕐 Operating Hours:</strong> ${branch.operating_hours || 'N/A'}</p>
                            <hr style="margin: 15px 0;">
                            <h4 style="color: #054A91; margin-bottom: 10px;">Statistics</h4>
                            <p><strong>👥 Total Users:</strong> ${branch.user_count}</p>
                            <p><strong>⏳ Pending Appointments:</strong> ${branch.pending_count}</p>
                            <p><strong>📅 Total Appointments:</strong> ${branch.total_appointments}</p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" onclick="closeModal(this)">Close</button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }
        
        function editBranch(branchId) {
            const branch = window.branchesData.find(b => b.id == branchId);
            if (!branch) {
                showAlert('Branch not found', 'error');
                return;
            }
            
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.innerHTML = `
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>✏️ Edit Branch</h3>
                        <span class="close" onclick="closeModal(this)">&times;</span>
                    </div>
                    <form id="edit-branch-form" class="modal-body">
                        <input type="hidden" id="edit_branch_id" value="${branch.id}">
                        <div class="form-group">
                            <label for="edit_branch_name">Branch Name:</label>
                            <input type="text" id="edit_branch_name" name="branch_name" value="${branch.name}" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_branch_code">Branch Code:</label>
                            <input type="text" id="edit_branch_code" name="branch_code" value="${branch.code || ''}" placeholder="e.g., TLS, SLY">
                        </div>
                        <div class="form-group">
                            <label for="edit_branch_location">Location:</label>
                            <input type="text" id="edit_branch_location" name="branch_location" value="${branch.location || ''}" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_branch_phone">Phone:</label>
                            <input type="tel" id="edit_branch_phone" name="branch_phone" value="${branch.phone || ''}">
                        </div>
                        <div class="form-group">
                            <label for="edit_branch_email">Email:</label>
                            <input type="email" id="edit_branch_email" name="branch_email" value="${branch.email || ''}">
                        </div>
                        <div class="form-group">
                            <label for="edit_branch_operating_hours">Operating Hours:</label>
                            <input type="text" id="edit_branch_operating_hours" name="branch_operating_hours" value="${branch.operating_hours || ''}" placeholder="e.g., Mon-Fri: 8AM-5PM">
                        </div>
                        <div class="modal-footer">
                            <button type="button" onclick="closeModal(this)">Cancel</button>
                            <button type="submit">Update Branch</button>
                        </div>
                    </form>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Handle form submission
            document.getElementById('edit-branch-form').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(e.target);
                const branchData = {
                    action: 'updateBranch',
                    branch_id: document.getElementById('edit_branch_id').value,
                    name: formData.get('branch_name'),
                    code: formData.get('branch_code'),
                    location: formData.get('branch_location'),
                    phone: formData.get('branch_phone'),
                    email: formData.get('branch_email'),
                    operating_hours: formData.get('branch_operating_hours')
                };
                
                const submitBtn = e.target.querySelector('button[type="submit"]');
                submitBtn.disabled = true;
                submitBtn.textContent = 'Updating...';
                
                fetch('/dental-clinic-chmsu-thesis/src/controllers/AdminController.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(branchData)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('Branch updated successfully!', 'success');
                        closeModal(document.querySelector('.modal .close'));
                        loadAllBranches();
                    } else {
                        showAlert('Error: ' + (data.message || 'Failed to update branch'), 'error');
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Update Branch';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('An error occurred while updating the branch', 'error');
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Update Branch';
                });
            });
        }
        
        function deleteBranch(branchId) {
            if (!confirm('Are you sure you want to delete this branch? This action cannot be undone.')) {
                return;
            }
            
            fetch('/dental-clinic-chmsu-thesis/src/controllers/AdminController.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ 
                    action: 'deleteBranch',
                    branch_id: branchId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Branch deleted successfully!', 'success');
                    loadAllBranches();
                    loadSystemStats();
                } else {
                    showAlert('Error: ' + (data.message || 'Failed to delete branch'), 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('An error occurred while deleting the branch', 'error');
            });
        }

        // Appointment management functions
        function approveAppointment(appointmentId) {
            if (!confirm('Are you sure you want to approve this appointment?')) {
                return;
            }

            // Show loading state
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '⏳';
            button.disabled = true;

            fetch('/dental-clinic-chmsu-thesis/src/controllers/AdminController.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ 
                    action: 'approveAppointment',
                    appointment_id: appointmentId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Appointment approved successfully!', 'success');
                    
                    // Update the specific row immediately
                    updateAppointmentRowStatus(appointmentId, 'approved');
                    
                    // Refresh all data to ensure consistency
                    loadSystemStats();
                    loadAppointmentsOverview();
                } else {
                    showAlert('Error: ' + (data.message || 'Failed to approve appointment'), 'error');
                    // Restore button on error
                    button.innerHTML = originalText;
                    button.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('An error occurred while approving the appointment', 'error');
                // Restore button on error
                button.innerHTML = originalText;
                button.disabled = false;
            });
        }

        function updateAppointmentRowStatus(appointmentId, newStatus) {
            // Find the specific row in the appointments table
            const appointmentsTable = document.getElementById('appointments-tbody');
            if (!appointmentsTable) return;

            const rows = appointmentsTable.querySelectorAll('tr');
            rows.forEach(row => {
                const actionButtons = row.querySelector('.action-buttons');
                if (actionButtons) {
                    const approveButton = actionButtons.querySelector(`button[onclick*="${appointmentId}"]`);
                    if (approveButton && approveButton.getAttribute('onclick').includes('approveAppointment')) {
                        // This is the row for our appointment
                        const statusCell = row.cells[4]; // Status is the 5th column (index 4)
                        if (statusCell) {
                            const statusColors = {
                                'pending': '#f17300',
                                'approved': '#10b981',
                                'completed': '#054A91',
                                'cancelled': '#dc3545'
                            };
                            
                            // Update status badge
                            statusCell.innerHTML = `<span style="background: ${statusColors[newStatus]}; color: white; padding: 4px 8px; border-radius: 12px; font-size: 12px;">${newStatus.toUpperCase()}</span>`;
                            
                            // Remove the approve button since it's no longer pending
                            if (newStatus === 'approved') {
                                approveButton.remove();
                            }
                        }
                        return; // Exit loop once found
                    }
                }
            });
        }

        function viewAppointmentDetails(appointmentId) {
            // Placeholder for appointment details view
            showAlert('View appointment details functionality coming soon!', 'info');
        }

        // System functions
        function generateReport() {
            const reportType = document.getElementById('report-type').value;
            const startDate = document.getElementById('report-start-date').value;
            const endDate = document.getElementById('report-end-date').value;
            
            if (!startDate || !endDate) {
                showAlert('Please select both start and end dates', 'warning');
                return;
            }
            
            // Validate date range
            if (new Date(startDate) > new Date(endDate)) {
                showAlert('Start date must be before end date', 'warning');
                return;
            }
            
            // Show loading state
            const generateBtn = event?.target;
            if (generateBtn) {
                generateBtn.disabled = true;
                generateBtn.textContent = '⏳ Generating...';
            }
            
            showAlert(`Generating ${reportType} report...`, 'info');
            
            const url = `/dental-clinic-chmsu-thesis/src/controllers/AdminController.php?action=generateReport&report_type=${reportType}&start_date=${startDate}&end_date=${endDate}`;
            
            fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayReportResults(data.report);
                    showAlert(`Report generated successfully! ${data.report.total_records} records found.`, 'success');
                } else {
                    showAlert('Failed to generate report: ' + data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Error generating report:', error);
                showAlert('Error generating report', 'danger');
            })
            .finally(() => {
                if (generateBtn) {
                    generateBtn.disabled = false;
                    generateBtn.textContent = '📋 Generate Report';
                }
            });
        }

        function displayReportResults(report) {
            if (!report || !report.data) {
                showAlert('No report data to display', 'warning');
                return;
            }
            
            // Create a modal or section to display report results
            let resultsHtml = `
                <div style="margin-top: 20px; padding: 20px; background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <h4 style="margin: 0;">📊 ${report.type.toUpperCase().replace(/_/g, ' ')} REPORT</h4>
                        <button class="btn btn-secondary" onclick="clearReportResults()" style="padding: 6px 12px;">❌ Close</button>
                    </div>
                    <div style="background: #f8f9fa; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                        <p style="margin: 5px 0;"><strong>Period:</strong> ${report.start_date} to ${report.end_date}</p>
                        <p style="margin: 5px 0;"><strong>Generated:</strong> ${new Date(report.generated_at).toLocaleString()}</p>
                        <p style="margin: 5px 0;"><strong>Total Records:</strong> <span style="color: #054A91; font-weight: bold;">${report.total_records}</span></p>
                    </div>
                    
                    <div style="max-height: 400px; overflow-y: auto; margin-top: 15px; border: 1px solid #ddd; border-radius: 5px;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                            <thead style="background: #054A91; color: white; position: sticky; top: 0;">
            `;
            
            // Dynamic table headers based on report type
            if (report.data.length > 0) {
                const firstRow = report.data[0];
                resultsHtml += '<tr>';
                Object.keys(firstRow).forEach(key => {
                    resultsHtml += `<th style="padding: 10px; border: 1px solid #ddd; text-align: left;">${key.replace(/_/g, ' ').toUpperCase()}</th>`;
                });
                resultsHtml += '</tr></thead><tbody>';
                
                // Data rows
                report.data.forEach((row, index) => {
                    const bgColor = index % 2 === 0 ? '#f8f9fa' : 'white';
                    resultsHtml += `<tr style="background: ${bgColor};">`;
                    Object.values(row).forEach(value => {
                        const displayValue = value || '-';
                        resultsHtml += `<td style="padding: 8px; border: 1px solid #ddd;">${displayValue}</td>`;
                    });
                    resultsHtml += '</tr>';
                });
                resultsHtml += '</tbody>';
            } else {
                resultsHtml += '<tr><td colspan="100" style="text-align: center; padding: 30px; color: #666;">No data found for this period</td></tr>';
            }
            
            resultsHtml += `
                        </table>
                    </div>
                    
                    <div style="margin-top: 15px; display: flex; gap: 10px; justify-content: flex-end;">
                        <button class="btn btn-info" onclick="downloadReportData()">📊 Download CSV</button>
                        <button class="btn btn-success" onclick="exportReport('pdf')">📄 Export PDF</button>
                        <button class="btn btn-primary" onclick="exportReport('excel')">📊 Export Excel</button>
                    </div>
                </div>
            `;
            
            // Find the reports tab and append/update results
            const reportsTab = document.getElementById('reports-tab');
            if (!reportsTab) return;
            
            // Remove any existing results
            const existingResults = reportsTab.querySelector('.report-results');
            if (existingResults) {
                existingResults.remove();
            }
            
            // Add new results
            const resultsDiv = document.createElement('div');
            resultsDiv.className = 'report-results';
            resultsDiv.innerHTML = resultsHtml;
            reportsTab.appendChild(resultsDiv);
            
            // Store report data globally for download
            window.currentReportData = report;
        }

        function downloadReportData() {
            if (!window.currentReportData) {
                showAlert('No report data to download', 'warning');
                return;
            }
            
            const report = window.currentReportData;
            downloadReportCSV(report);
        }

        function downloadReportCSV(report) {
            if (!report.data || report.data.length === 0) {
                showAlert('No data to download', 'warning');
                return;
            }
            
            // Generate CSV content
            const headers = Object.keys(report.data[0]);
            let csvContent = headers.join(',') + '\n';
            
            report.data.forEach(row => {
                const values = headers.map(header => {
                    const value = row[header] || '';
                    return `"${value.toString().replace(/"/g, '""')}"`;
                });
                csvContent += values.join(',') + '\n';
            });
            
            // Create and download the file
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = `${report.type}_report_${report.start_date}_to_${report.end_date}.csv`;
            link.click();
            
            showAlert('Report downloaded successfully!', 'success');
        }

        function clearReportResults() {
            const existingResults = document.querySelector('.report-results');
            if (existingResults) {
                existingResults.remove();
            }
        }

        function exportReport(format) {
            const reportType = document.getElementById('report-type').value;
            const startDate = document.getElementById('report-start-date').value;
            const endDate = document.getElementById('report-end-date').value;
            
            if (!startDate || !endDate) {
                showAlert('Please select date range first', 'warning');
                return;
            }
            
            // Validate date range
            if (new Date(startDate) > new Date(endDate)) {
                showAlert('Start date must be before end date', 'warning');
                return;
            }
            
            showAlert(`Exporting report as ${format.toUpperCase()}...`, 'info');
            
            // Build export URL
            const action = format === 'pdf' ? 'exportPDF' : 'exportExcel';
            const url = `/dental-clinic-chmsu-thesis/src/controllers/AdminController.php?action=${action}&report_type=${reportType}&start_date=${startDate}&end_date=${endDate}`;
            
            // Create hidden iframe to trigger download
            const iframe = document.createElement('iframe');
            iframe.style.display = 'none';
            iframe.src = url;
            document.body.appendChild(iframe);
            
            // Remove iframe after download starts
            setTimeout(() => {
                document.body.removeChild(iframe);
                showAlert(`${format.toUpperCase()} export initiated! Check your downloads folder.`, 'success');
            }, 2000);
        }

        function backupDatabase() {
            if (confirm('Create database backup? This may take a few minutes.')) {
                const button = event.target;
                button.disabled = true;
                button.textContent = '⏳ Creating backup...';
                
                fetch('/dental-clinic-chmsu-thesis/src/controllers/AdminController.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ action: 'backupDatabase' })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert(`✅ Database backup created successfully!\nFile: ${data.backup_file}\nSize: ${data.file_size}\nTables: ${data.tables_backed_up}`, 'success');
                    } else {
                        showAlert('Backup failed: ' + data.message, 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('Error creating backup', 'danger');
                })
                .finally(() => {
                    button.disabled = false;
                    button.textContent = '💾 Backup Database';
                });
            }
        }

        function exportSystemLogs() {
            // Export current logs as CSV
            fetch('/dental-clinic-chmsu-thesis/src/controllers/AdminController.php?action=getSystemLogs&limit=1000')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.logs.length > 0) {
                    const headers = ['Timestamp', 'User', 'Action', 'Description', 'IP Address'];
                    let csvContent = headers.join(',') + '\n';
                    
                    data.logs.forEach(log => {
                        const row = [
                            `"${log.created_at}"`,
                            `"${log.user_name} (${log.user_email})"`,
                            `"${log.action}"`,
                            `"${log.description.replace(/"/g, '""')}"`,
                            `"${log.ip_address}"`
                        ];
                        csvContent += row.join(',') + '\n';
                    });
                    
                    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                    const link = document.createElement('a');
                    link.href = URL.createObjectURL(blob);
                    link.download = `system_logs_${new Date().toISOString().split('T')[0]}.csv`;
                    link.click();
                    
                    showAlert('System logs exported successfully!', 'success');
                } else {
                    showAlert('No logs to export', 'warning');
                }
            })
            .catch(error => {
                console.error('Error exporting logs:', error);
                showAlert('Error exporting logs', 'danger');
            });
        }

        function filterSystemLogs() {
            // This would require backend filtering, for now just reload
            loadSystemLogs();
        }

        // Appointment actions for admin
        function viewAppointmentDetails(appointmentId) {
            showAlert(`Viewing details for appointment #${appointmentId}`, 'info');
        }

        function saveSettings() {
            // Collect all settings from the form
            const settings = {
                system_name: document.getElementById('system_name')?.value || '',
                appointment_duration: document.getElementById('appointment_duration')?.value || '30',
                timezone: document.getElementById('timezone')?.value || 'Asia/Manila',
                max_appointments_per_day: document.getElementById('max_appointments_per_day')?.value || '50',
                require_email_verification: document.getElementById('require_email_verification')?.checked ? '1' : '0',
                password_expiry_days: document.getElementById('password_expiry_days')?.value || '90',
                session_timeout: document.getElementById('session_timeout')?.value || '60',
                send_email_notifications: document.getElementById('send_email_notifications')?.checked ? '1' : '0',
                notification_email: document.getElementById('notification_email')?.value || '',
                reminder_lead_time: document.getElementById('reminder_lead_time')?.value || '24'
            };
            
            // Show loading state
            const saveBtn = event?.target;
            if (saveBtn) {
                saveBtn.disabled = true;
                saveBtn.textContent = '💾 Saving...';
            }
            
            fetch('/dental-clinic-chmsu-thesis/src/controllers/AdminController.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ 
                    action: 'saveSettings',
                    settings: settings
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Settings saved successfully!', 'success');
                } else {
                    showAlert('Failed to save settings: ' + data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Error saving settings', 'danger');
            })
            .finally(() => {
                if (saveBtn) {
                    saveBtn.disabled = false;
                    saveBtn.textContent = '💾 Save All Settings';
                }
            });
        }

        // Load settings on page load
        function loadSettings() {
            fetch('/dental-clinic-chmsu-thesis/src/controllers/AdminController.php?action=getSettings')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.settings) {
                    const settings = data.settings;
                    
                    // Populate form fields with saved settings
                    if (settings.system_name) document.getElementById('system_name').value = settings.system_name;
                    if (settings.appointment_duration) document.getElementById('appointment_duration').value = settings.appointment_duration;
                    if (settings.timezone) document.getElementById('timezone').value = settings.timezone;
                    if (settings.max_appointments_per_day) document.getElementById('max_appointments_per_day').value = settings.max_appointments_per_day;
                    if (settings.require_email_verification) document.getElementById('require_email_verification').checked = settings.require_email_verification === '1';
                    if (settings.password_expiry_days) document.getElementById('password_expiry_days').value = settings.password_expiry_days;
                    if (settings.session_timeout) document.getElementById('session_timeout').value = settings.session_timeout;
                    if (settings.send_email_notifications) document.getElementById('send_email_notifications').checked = settings.send_email_notifications === '1';
                    if (settings.notification_email) document.getElementById('notification_email').value = settings.notification_email;
                    if (settings.reminder_lead_time) document.getElementById('reminder_lead_time').value = settings.reminder_lead_time;
                }
            })
            .catch(error => {
                console.error('Error loading settings:', error);
            });
        }

        // Utility functions
        function showAlert(message, type) {
            const alertColors = {
                'success': '#10b981',
                'danger': '#dc3545',
                'warning': '#f17300',
                'info': '#3E7CB1'
            };
            
            const alert = document.createElement('div');
            alert.style.cssText = `
                position: fixed; 
                top: 20px; 
                right: 20px; 
                z-index: 1050; 
                padding: 15px; 
                border-radius: 5px; 
                color: white; 
                max-width: 300px;
                background-color: ${alertColors[type] || '#6c757d'};
            `;
            alert.textContent = message;
            
            document.body.appendChild(alert);
            
            setTimeout(() => {
                alert.remove();
            }, 3000);
        }

        function logout() {
            if (!confirm('Are you sure you want to logout?')) {
                return;
            }
            
            fetch('/dental-clinic-chmsu-thesis/src/controllers/AuthController.php?action=logout', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = '../auth/login.php';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                window.location.href = '../auth/login.php';
            });
        }
    </script>
</body>
</html>
