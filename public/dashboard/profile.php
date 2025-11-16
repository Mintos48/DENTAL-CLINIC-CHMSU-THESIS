<?php
session_start();
require_once '../../src/config/database.php';
require_once '../../src/config/session.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$user = $_SESSION;
$userName = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - DCMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-50: #eff6ff;
            --primary-100: #dbeafe;
            --primary-200: #bfdbfe;
            --primary-300: #93c5fd;
            --primary-400: #60a5fa;
            --primary-500: #3b82f6;
            --primary-600: #2563eb;
            --primary-700: #1d4ed8;
            --primary-800: #1e40af;
            --primary-900: #1e3a8a;
            --secondary-50: #f9fafb;
            --secondary-100: #f3f4f6;
            --secondary-200: #e5e7eb;
            --secondary-300: #d1d5db;
            --secondary-400: #9ca3af;
            --secondary-500: #6b7280;
            --secondary-600: #4b5563;
            --secondary-700: #374151;
            --secondary-800: #1f2937;
            --secondary-900: #111827;
            --orange-accent: #f17300;
            --spacing-1: 0.25rem;
            --spacing-2: 0.5rem;
            --spacing-3: 0.75rem;
            --spacing-4: 1rem;
            --spacing-5: 1.25rem;
            --spacing-6: 1.5rem;
            --text-xs: 0.75rem;
            --text-sm: 0.875rem;
            --text-base: 1rem;
            --text-lg: 1.125rem;
            --text-xl: 1.25rem;
            --text-2xl: 1.5rem;
            --text-3xl: 1.875rem;
            --border-radius: 0.375rem;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8fafc;
            line-height: 1.6;
            color: var(--secondary-700);
        }

        /* Header */
        .header {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 70px;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 800;
            color: #054A91;
            text-decoration: none;
        }

        .nav-menu {
            display: flex;
            list-style: none;
            gap: 30px;
        }

        .nav-menu a {
            text-decoration: none;
            color: #4a5568;
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-menu a:hover {
            color: #054A91;
        }

        .user-section {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .back-button {
            background: var(--primary-600);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            padding: 8px 16px;
            font-weight: 500;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .back-button:hover {
            background: var(--primary-700);
            transform: translateY(-1px);
        }

        /* Main Content */
        .main-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-title {
            font-size: 2rem;
            color: var(--secondary-800);
            margin-bottom: 8px;
        }

        .page-subtitle {
            color: var(--secondary-600);
            font-size: 1.1rem;
        }

        /* Profile Card */
        .profile-card {
            background: white;
            border-radius: 16px;
            box-shadow: var(--shadow-lg);
            overflow: hidden;
        }

        .profile-header {
            background: linear-gradient(135deg, #054A91 0%, #3E7CB1 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .profile-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: bold;
            margin: 0 auto 20px;
            border: 4px solid rgba(255, 255, 255, 0.3);
        }

        .profile-basic h2 {
            margin-bottom: 8px;
            font-size: 1.5rem;
        }

        .profile-basic p {
            opacity: 0.9;
            margin-bottom: 12px;
        }

        .profile-role-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .profile-content {
            padding: 30px;
        }

        .profile-actions {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            justify-content: center;
        }

        /* Profile View/Edit Modes */
        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 30px;
            margin-bottom: 30px;
        }

        .profile-details {
            display: grid;
            gap: 20px;
        }

        .detail-item {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            padding: 15px;
            background: var(--secondary-50);
            border-radius: var(--border-radius);
            border-left: 4px solid var(--primary-500);
        }

        .detail-item i {
            color: var(--primary-600);
            font-size: 1.1rem;
            margin-top: 2px;
            width: 20px;
        }

        .detail-item label {
            font-weight: 600;
            color: var(--secondary-800);
            display: block;
            margin-bottom: 4px;
            font-size: var(--text-sm);
        }

        .detail-item span {
            color: var(--secondary-700);
            word-break: break-word;
        }

        .profile-stats-card {
            background: var(--secondary-50);
            border-radius: var(--border-radius);
            padding: 25px;
            height: fit-content;
        }

        .profile-stats-card h5 {
            margin-bottom: 20px;
            color: var(--secondary-800);
            font-size: var(--text-lg);
        }

        .profile-stats {
            display: grid;
            gap: 15px;
            margin-bottom: 25px;
        }

        .stat-item {
            text-align: center;
            padding: 15px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary-600);
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: var(--text-sm);
            color: var(--secondary-600);
        }

        /* Form Styles */
        .modern-form {
            background: white;
            border-radius: var(--border-radius);
            padding: 0;
        }

        .form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid var(--secondary-200);
            background: var(--secondary-50);
            border-radius: var(--border-radius) var(--border-radius) 0 0;
        }

        .form-header h4 {
            color: var(--secondary-800);
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
        }

        .form-actions {
            display: flex;
            gap: 10px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            padding: 25px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group-full {
            grid-column: 1 / -1;
        }

        .form-section-header {
            grid-column: 1 / -1;
            margin: 15px 0 5px 0;
            padding-bottom: 8px;
            border-bottom: 2px solid var(--primary-200);
        }

        .form-section-header h5 {
            color: var(--primary-700);
            font-size: var(--text-lg);
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
        }

        label {
            font-weight: 600;
            color: var(--secondary-700);
            font-size: var(--text-sm);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        input, select, textarea {
            padding: 12px;
            border: 2px solid var(--secondary-200);
            border-radius: var(--border-radius);
            font-size: var(--text-base);
            transition: all 0.3s;
            background: white;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--primary-500);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        textarea {
            resize: vertical;
            min-height: 80px;
        }

        /* Checkbox Styles */
        .checkbox-group {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            padding: 12px;
            border-radius: var(--border-radius);
            transition: background 0.2s;
        }

        .checkbox-label:hover {
            background: var(--secondary-50);
        }

        .checkbox-label input[type="checkbox"] {
            width: 18px;
            height: 18px;
            margin: 0;
        }

        .checkbox-text {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: var(--text-sm);
            color: var(--secondary-700);
        }

        /* Button Styles */
        .btn {
            padding: 12px 24px;
            border-radius: var(--border-radius);
            font-weight: 600;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            justify-content: center;
            font-size: var(--text-sm);
        }

        .btn-primary {
            background: linear-gradient(135deg, #054A91, #3E7CB1);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(5, 74, 145, 0.4);
        }

        .btn-secondary {
            background: #DBE4EE;
            color: #054A91;
            border: 2px solid #81A4CD;
        }

        .btn-secondary:hover {
            background: #81A4CD;
            border-color: #3E7CB1;
            color: white;
        }

        .btn-small {
            padding: 8px 16px;
            font-size: 0.875rem;
        }

        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            background: var(--primary-100);
            color: var(--primary-700);
        }

        /* Modal Styles */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
        }

        .modal.show {
            opacity: 1;
            visibility: visible;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: var(--shadow-lg);
            transform: scale(0.9);
            transition: transform 0.3s;
        }

        .modal.show .modal-content {
            transform: scale(1);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid var(--secondary-200);
            background: var(--secondary-50);
            border-radius: 12px 12px 0 0;
        }

        .modal-header h4 {
            margin: 0;
            color: var(--secondary-800);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            color: var(--secondary-500);
            padding: 5px;
            border-radius: 50%;
            transition: all 0.2s;
        }

        .modal-close:hover {
            background: var(--secondary-200);
            color: var(--secondary-700);
        }

        .modal-body {
            padding: 25px;
        }

        /* Loading Skeleton */
        .loading-skeleton {
            background: linear-gradient(90deg, var(--secondary-200) 25%, var(--secondary-100) 50%, var(--secondary-200) 75%);
            background-size: 200% 100%;
            animation: skeleton-loading 1.5s infinite;
            border-radius: 4px;
        }

        @keyframes skeleton-loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-menu {
                display: none;
            }

            .main-content {
                padding: 20px 15px;
            }

            .profile-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .form-grid {
                grid-template-columns: 1fr;
                padding: 20px;
            }

            .profile-actions {
                flex-direction: column;
            }

            .modal-content {
                width: 95%;
                margin: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="nav-container">
            <a href="../index.php" class="logo">DCMS</a>
            
            <nav>
                <ul class="nav-menu">
                    <li><a href="clinic-listing.php">Clinics</a></li>
                    <li><a href="#about">About</a></li>
                    <li><a href="#contact">Contact</a></li>
                </ul>
            </nav>
            
            <div class="user-section">
                <a href="patient-dashboard.php" class="back-button">
                    <i class="fas fa-arrow-left"></i>
                    Back to Dashboard
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">My Profile</h1>
            <p class="page-subtitle">Manage your personal information and account settings</p>
        </div>

        <div class="profile-card">
            <!-- Profile Header -->
            <div class="profile-header">
                <div class="profile-avatar" id="profile-avatar">P</div>
                <div class="profile-basic">
                    <h2 id="profile-name">Loading...</h2>
                    <p id="profile-email">Loading...</p>
                    <span class="profile-role-badge" id="profile-role">Patient</span>
                </div>
            </div>

            <!-- Profile Content -->
            <div class="profile-content">
                <!-- Profile Actions -->
                <div class="profile-actions">
                    <button class="btn btn-primary" id="edit-profile-btn" onclick="profileManager.toggleEditMode()">
                        <i class="fas fa-edit"></i> Edit Profile
                    </button>
                    <button class="btn btn-secondary" onclick="profileManager.showChangePasswordModal()">
                        <i class="fas fa-lock"></i> Change Password
                    </button>
                    <button class="btn btn-secondary" onclick="profileManager.downloadProfile()">
                        <i class="fas fa-download"></i> Download Profile
                    </button>
                </div>

                <!-- Profile View Mode -->
                <div id="profile-view-mode">
                    <div class="profile-grid">
                        <div class="profile-details">
                            <div class="detail-item">
                                <i class="fas fa-id-card"></i>
                                <div>
                                    <label>Patient ID</label>
                                    <span id="profile-id">Loading...</span>
                                </div>
                            </div>
                            
                            <div class="detail-item">
                                <i class="fas fa-user"></i>
                                <div>
                                    <label>Full Name</label>
                                    <span id="profile-full-name">Loading...</span>
                                </div>
                            </div>
                            
                            <div class="detail-item">
                                <i class="fas fa-envelope"></i>
                                <div>
                                    <label>Email</label>
                                    <span id="profile-email-display">Loading...</span>
                                </div>
                            </div>
                            
                            <div class="detail-item">
                                <i class="fas fa-phone"></i>
                                <div>
                                    <label>Phone</label>
                                    <span id="profile-phone">Not provided</span>
                                </div>
                            </div>
                            
                            <div class="detail-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <div>
                                    <label>Address</label>
                                    <span id="profile-address">Not provided</span>
                                </div>
                            </div>
                            
                            <div class="detail-item">
                                <i class="fas fa-birthday-cake"></i>
                                <div>
                                    <label>Date of Birth</label>
                                    <span id="profile-birthdate">Not provided</span>
                                </div>
                            </div>
                            
                            <div class="detail-item">
                                <i class="fas fa-venus-mars"></i>
                                <div>
                                    <label>Gender</label>
                                    <span id="profile-gender">Not specified</span>
                                </div>
                            </div>
                            
                            <div class="detail-item">
                                <i class="fas fa-user-shield"></i>
                                <div>
                                    <label>Emergency Contact</label>
                                    <span id="profile-emergency-contact">Not provided</span>
                                </div>
                            </div>
                            
                            <div class="detail-item">
                                <i class="fas fa-hospital"></i>
                                <div>
                                    <label>Primary Branch</label>
                                    <span id="profile-branch">Loading...</span>
                                </div>
                            </div>
                            
                            <div class="detail-item">
                                <i class="fas fa-calendar-check"></i>
                                <div>
                                    <label>Member Since</label>
                                    <span id="profile-member-since">Loading...</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="profile-stats-card">
                            <h5>Account Statistics</h5>
                            <div class="profile-stats">
                                <div class="stat-item">
                                    <div class="stat-number" id="profile-total-appointments">0</div>
                                    <div class="stat-label">Total Appointments</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number" id="profile-completed-appointments">0</div>
                                    <div class="stat-label">Completed</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number" id="profile-pending-appointments">0</div>
                                    <div class="stat-label">Upcoming</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Profile Edit Mode -->
                <div id="profile-edit-mode" style="display: none;">
                    <form id="profile-edit-form" class="modern-form" onsubmit="profileManager.saveProfile(event)">
                        <div class="form-header">
                            <h4><i class="fas fa-user-edit"></i> Edit Profile Information</h4>
                            <div class="form-actions">
                                <button type="button" class="btn btn-secondary btn-small" onclick="profileManager.cancelEditMode()">
                                    <i class="fas fa-times"></i> Cancel
                                </button>
                                <button type="submit" class="btn btn-primary btn-small">
                                    <i class="fas fa-save"></i> Save Changes
                                </button>
                            </div>
                        </div>

                        <div class="form-grid">
                            <!-- Personal Information -->
                            <div class="form-section-header">
                                <h5><i class="fas fa-user"></i> Personal Information</h5>
                            </div>

                            <div class="form-group">
                                <label for="edit-name">
                                    <i class="fas fa-user"></i> Full Name *
                                </label>
                                <input type="text" id="edit-name" name="name" required>
                            </div>

                            <div class="form-group">
                                <label for="edit-email">
                                    <i class="fas fa-envelope"></i> Email Address *
                                </label>
                                <input type="email" id="edit-email" name="email" required>
                            </div>

                            <div class="form-group">
                                <label for="edit-phone">
                                    <i class="fas fa-phone"></i> Phone Number
                                </label>
                                <input type="tel" id="edit-phone" name="phone" placeholder="+63 912 345 6789">
                            </div>

                            <div class="form-group">
                                <label for="edit-birthdate">
                                    <i class="fas fa-birthday-cake"></i> Date of Birth
                                </label>
                                <input type="date" id="edit-birthdate" name="date_of_birth">
                            </div>

                            <div class="form-group">
                                <label for="edit-gender">
                                    <i class="fas fa-venus-mars"></i> Gender
                                </label>
                                <select id="edit-gender" name="gender">
                                    <option value="prefer_not_to_say">Prefer not to say</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>

                            <div class="form-group form-group-full">
                                <label for="edit-address">
                                    <i class="fas fa-map-marker-alt"></i> Address
                                </label>
                                <textarea id="edit-address" name="address" rows="3" placeholder="Complete address including city and province"></textarea>
                            </div>

                            <!-- Emergency Contact -->
                            <div class="form-section-header">
                                <h5><i class="fas fa-user-shield"></i> Emergency Contact</h5>
                            </div>

                            <div class="form-group">
                                <label for="edit-emergency-name">
                                    <i class="fas fa-user-friends"></i> Emergency Contact Name
                                </label>
                                <input type="text" id="edit-emergency-name" name="emergency_contact_name" placeholder="Full name">
                            </div>

                            <div class="form-group">
                                <label for="edit-emergency-phone">
                                    <i class="fas fa-phone-alt"></i> Emergency Contact Phone
                                </label>
                                <input type="tel" id="edit-emergency-phone" name="emergency_contact_phone" placeholder="+63 912 345 6789">
                            </div>

                            <!-- Preferences -->
                            <div class="form-section-header">
                                <h5><i class="fas fa-cog"></i> Notification Preferences</h5>
                            </div>

                            <div class="form-group form-group-full">
                                <div class="checkbox-group">
                                    <label class="checkbox-label">
                                        <input type="checkbox" id="edit-receive-notifications" name="receive_notifications" checked>
                                        <span class="checkbox-text">
                                            <i class="fas fa-bell"></i> Receive general notifications
                                        </span>
                                    </label>
                                    
                                    <label class="checkbox-label">
                                        <input type="checkbox" id="edit-receive-email-reminders" name="receive_email_reminders" checked>
                                        <span class="checkbox-text">
                                            <i class="fas fa-envelope"></i> Email appointment reminders
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <!-- Change Password Modal -->
    <div id="change-password-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h4><i class="fas fa-lock"></i> Change Password</h4>
                <button type="button" class="modal-close" onclick="profileManager.hideChangePasswordModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="change-password-form" onsubmit="profileManager.changePassword(event)">
                    <div class="form-group">
                        <label for="current-password">
                            <i class="fas fa-key"></i> Current Password *
                        </label>
                        <input type="password" id="current-password" name="current_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new-password">
                            <i class="fas fa-lock"></i> New Password *
                        </label>
                        <input type="password" id="new-password" name="new_password" required minlength="8">
                        <small>Password must be at least 8 characters long</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm-password">
                            <i class="fas fa-check-circle"></i> Confirm New Password *
                        </label>
                        <input type="password" id="confirm-password" name="confirm_password" required>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="profileManager.hideChangePasswordModal()">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Change Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        class ProfileManager {
            constructor() {
                this.isEditingProfile = false;
                this.currentProfileData = null;
                this.init();
            }

            init() {
                this.loadCompleteProfileData();
                this.setupModalHandlers();
                
                // Set initial avatar based on user name
                const userName = '<?php echo addslashes($userName); ?>';
                const avatarElement = document.getElementById('profile-avatar');
                if (avatarElement && userName) {
                    avatarElement.textContent = userName.charAt(0).toUpperCase();
                }
            }

            loadCompleteProfileData() {
                fetch('../api/user-session.php?action=getProfile')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.currentProfileData = data.profile;
                            this.populateProfileView(data.profile);
                            this.updateProfileStats();
                        } else {
                            console.error('Failed to load profile data:', data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error loading profile data:', error);
                    });
            }

            populateProfileView(profile) {
                document.getElementById('profile-id').textContent = profile.id || 'N/A';
                document.getElementById('profile-name').textContent = profile.name || 'Not provided';
                document.getElementById('profile-full-name').textContent = profile.name || 'Not provided';
                document.getElementById('profile-email').textContent = profile.email || 'Not provided';
                document.getElementById('profile-email-display').textContent = profile.email || 'Not provided';
                document.getElementById('profile-phone').textContent = profile.phone || 'Not provided';
                document.getElementById('profile-address').textContent = profile.address || 'Not provided';
                document.getElementById('profile-birthdate').textContent = profile.date_of_birth || 'Not provided';
                document.getElementById('profile-gender').textContent = profile.gender ? profile.gender.charAt(0).toUpperCase() + profile.gender.slice(1) : 'Not specified';
                
                const emergencyContact = profile.emergency_contact_name && profile.emergency_contact_phone 
                    ? `${profile.emergency_contact_name} (${profile.emergency_contact_phone})`
                    : 'Not provided';
                document.getElementById('profile-emergency-contact').textContent = emergencyContact;
                
                document.getElementById('profile-branch').textContent = profile.branch_name || 'Not assigned';
                document.getElementById('profile-member-since').textContent = profile.created_at ? new Date(profile.created_at).toLocaleDateString() : 'Unknown';
            }

            updateProfileStats() {
                // Fetch real appointment statistics from the API
                fetch('../api/user-session.php?action=getAppointmentStats')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.stats) {
                            document.getElementById('profile-total-appointments').textContent = data.stats.total;
                            document.getElementById('profile-completed-appointments').textContent = data.stats.completed;
                            document.getElementById('profile-pending-appointments').textContent = data.stats.pending;
                        } else {
                            console.error('Failed to load appointment statistics:', data.message);
                            // Keep default values of 0 if fetch fails
                            document.getElementById('profile-total-appointments').textContent = '0';
                            document.getElementById('profile-completed-appointments').textContent = '0';
                            document.getElementById('profile-pending-appointments').textContent = '0';
                        }
                    })
                    .catch(error => {
                        console.error('Error loading appointment statistics:', error);
                        // Keep default values of 0 if fetch fails
                        document.getElementById('profile-total-appointments').textContent = '0';
                        document.getElementById('profile-completed-appointments').textContent = '0';
                        document.getElementById('profile-pending-appointments').textContent = '0';
                    });
            }

            toggleEditMode() {
                if (this.isEditingProfile) {
                    this.cancelEditMode();
                } else {
                    this.enableEditMode();
                }
            }

            enableEditMode() {
                this.isEditingProfile = true;
                document.getElementById('profile-view-mode').style.display = 'none';
                document.getElementById('profile-edit-mode').style.display = 'block';
                
                // Update button text
                const editBtn = document.getElementById('edit-profile-btn');
                editBtn.innerHTML = '<i class="fas fa-times"></i> Cancel Edit';
                
                this.populateEditForm();
            }

            cancelEditMode() {
                this.isEditingProfile = false;
                document.getElementById('profile-view-mode').style.display = 'block';
                document.getElementById('profile-edit-mode').style.display = 'none';
                
                // Update button text
                const editBtn = document.getElementById('edit-profile-btn');
                editBtn.innerHTML = '<i class="fas fa-edit"></i> Edit Profile';
                
                // Reset form
                document.getElementById('profile-edit-form').reset();
            }

            populateEditForm() {
                if (!this.currentProfileData) return;
                
                const profile = this.currentProfileData;
                document.getElementById('edit-name').value = profile.name || '';
                document.getElementById('edit-email').value = profile.email || '';
                document.getElementById('edit-phone').value = profile.phone || '';
                document.getElementById('edit-address').value = profile.address || '';
                document.getElementById('edit-birthdate').value = profile.date_of_birth || '';
                document.getElementById('edit-gender').value = profile.gender || '';
                document.getElementById('edit-emergency-name').value = profile.emergency_contact_name || '';
                document.getElementById('edit-emergency-phone').value = profile.emergency_contact_phone || '';
                
                // Set checkboxes
                document.getElementById('edit-receive-notifications').checked = profile.receive_notifications !== 0;
                document.getElementById('edit-receive-email-reminders').checked = profile.receive_email_reminders !== 0;
            }

            saveProfile(event) {
                event.preventDefault();
                
                const formData = new FormData(event.target);
                const profileData = {
                    action: 'updateProfile',
                    name: formData.get('name'),
                    email: formData.get('email'),
                    phone: formData.get('phone'),
                    address: formData.get('address'),
                    date_of_birth: formData.get('date_of_birth'),
                    gender: formData.get('gender'),
                    emergency_contact_name: formData.get('emergency_contact_name'),
                    emergency_contact_phone: formData.get('emergency_contact_phone'),
                    receive_notifications: formData.get('receive_notifications') === 'on' ? 1 : 0,
                    receive_email_reminders: formData.get('receive_email_reminders') === 'on' ? 1 : 0
                };

                // Show loading state
                const submitBtn = event.target.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
                submitBtn.disabled = true;

                fetch('../api/user-session.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(profileData)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Profile updated successfully!');
                        this.loadCompleteProfileData(); // Reload the data
                        this.cancelEditMode();
                    } else {
                        alert('Error updating profile: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error saving profile:', error);
                    alert('Error saving profile. Please try again.');
                })
                .finally(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                });
            }

            showChangePasswordModal() {
                const modal = document.getElementById('change-password-modal');
                modal.style.display = 'flex';
                setTimeout(() => modal.classList.add('show'), 10);
            }

            hideChangePasswordModal() {
                const modal = document.getElementById('change-password-modal');
                modal.classList.remove('show');
                setTimeout(() => {
                    modal.style.display = 'none';
                    document.getElementById('change-password-form').reset();
                }, 300);
            }

            changePassword(event) {
                event.preventDefault();
                
                const formData = new FormData(event.target);
                const newPassword = formData.get('new_password');
                const confirmPassword = formData.get('confirm_password');
                
                if (newPassword !== confirmPassword) {
                    alert('New passwords do not match');
                    return;
                }

                const passwordData = {
                    action: 'changePassword',
                    current_password: formData.get('current_password'),
                    new_password: newPassword
                };

                // Show loading state
                const submitBtn = event.target.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Changing...';
                submitBtn.disabled = true;

                fetch('../api/user-session.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(passwordData)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Password changed successfully!');
                        this.hideChangePasswordModal();
                    } else {
                        alert('Error changing password: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error changing password:', error);
                    alert('Error changing password. Please try again.');
                })
                .finally(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                });
            }

            downloadProfile() {
                if (!this.currentProfileData) {
                    alert('Profile data not loaded yet. Please wait a moment and try again.');
                    return;
                }

                const profile = this.currentProfileData;
                const profileText = `
DENTAL CLINIC MANAGEMENT SYSTEM
PATIENT PROFILE

Name: ${profile.name || 'Not provided'}
Email: ${profile.email || 'Not provided'}
Phone: ${profile.phone || 'Not provided'}
Address: ${profile.address || 'Not provided'}
Date of Birth: ${profile.date_of_birth || 'Not provided'}
Gender: ${profile.gender || 'Not specified'}
Emergency Contact: ${profile.emergency_contact_name || 'Not provided'}
Emergency Phone: ${profile.emergency_contact_phone || 'Not provided'}

Notification Preferences:
- General Notifications: ${profile.receive_notifications ? 'Enabled' : 'Disabled'}
- Email Reminders: ${profile.receive_email_reminders ? 'Enabled' : 'Disabled'}

Generated on: ${new Date().toLocaleString()}
                `.trim();

                const blob = new Blob([profileText], { type: 'text/plain' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `profile_${profile.name?.replace(/\s+/g, '_') || 'patient'}.txt`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            }

            setupModalHandlers() {
                // Close modal when clicking outside
                document.getElementById('change-password-modal').addEventListener('click', (e) => {
                    if (e.target.id === 'change-password-modal') {
                        this.hideChangePasswordModal();
                    }
                });

                // Close modal on Escape key
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape') {
                        this.hideChangePasswordModal();
                    }
                });
            }
        }

        // Initialize profile manager when DOM is loaded
        let profileManager;
        document.addEventListener('DOMContentLoaded', function() {
            profileManager = new ProfileManager();
        });
    </script>
</body>
</html>