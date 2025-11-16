<?php
/**
 * Staff Dashboard - Public Access Point
 */
require_once '../../src/config/constants.php';
require_once '../../src/config/session.php';
require_once '../../src/config/database.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit;
}

// Check if user has staff role
if (getSessionRole() !== ROLE_STAFF) {
    header('Location: ../auth/login.php');
    exit;
}

// Get user's branch information from database
$branchName = 'Dental Clinic Management';
$branchLocation = 'Staff Member';
$branchId = getSessionBranchId();

if ($branchId) {
    try {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT name, location FROM branches WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $branchId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $branch = $result->fetch_assoc();
            $branchName = $branch['name'];
            $branchLocation = $branch['location'] ?: $branch['name'];
        }
        $stmt->close();
    } catch (Exception $e) {
        error_log("Error fetching branch info: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard | Dental Clinic Management</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Enhanced modern styling for the dashboard */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            overflow-x: hidden;
        }

        /* Touch-friendly improvements */
        button, a, .quick-action, .tab-nav-button {
            -webkit-tap-highlight-color: rgba(0, 0, 0, 0.1);
            touch-action: manipulation;
        }

        /* Prevent horizontal scroll on mobile */
        .dashboard-container,
        .dashboard-main,
        .dashboard-sidebar {
            overflow-x: hidden;
        }

        /* Smooth scrolling for better UX */
        html {
            scroll-behavior: smooth;
        }

        /* Ensure images are responsive */
        img {
            max-width: 100%;
            height: auto;
        }

        /* Improve text readability on mobile */
        body {
            -webkit-text-size-adjust: 100%;
            text-size-adjust: 100%;
        }

        .modern-dashboard {
            background: linear-gradient(135deg, #054A91 0%, #3E7CB1 100%);
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
        }

        .dashboard-container {
            max-width: 1440px;
            margin: 0 auto;
            padding: 24px;
        }

        /* Top Navigation Bar */
        .top-navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            padding: 16px 24px;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            position: relative;
            z-index: 1000;
        }

        .brand-section {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #054A91, #3E7CB1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
        }

        .brand-text h1 {
            font-size: 20px;
            font-weight: 700;
            color: #1a202c;
            margin: 0;
            line-height: 1;
        }

        .brand-text p {
            font-size: 12px;
            color: #64748b;
            margin: 0;
            font-weight: 500;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .nav-notification {
            position: relative;
            padding: 8px;
            border-radius: 10px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            cursor: pointer;
            transition: all 0.2s ease;
            z-index: 100000;
        }

        .nav-notification:hover {
            background: #e2e8f0;
            transform: translateY(-1px);
        }

        .nav-notification.active {
            background: #e2e8f0;
            border-color: #054A91;
            box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.1);
        }

        .notification-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            background: #ef4444;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 12px;
            border-radius: 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .user-profile:hover {
            background: #e2e8f0;
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: linear-gradient(135deg, #054A91, #3E7CB1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 14px;
        }

        .user-info h3 {
            font-size: 14px;
            font-weight: 600;
            color: #1a202c;
            margin: 0;
            line-height: 1;
        }

        .user-info p {
            font-size: 12px;
            color: #64748b;
            margin: 0;
            font-weight: 500;
        }

        /* Main Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 24px;
            min-height: calc(100vh - 120px);
        }

        /* Left Sidebar */
        .dashboard-sidebar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            padding: 24px;
            height: fit-content;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .sidebar-section {
            margin-bottom: 32px;
        }

        .sidebar-title {
            font-size: 14px;
            font-weight: 700;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .quick-stats {
            display: grid;
            gap: 16px;
        }

        .quick-stat {
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .quick-stat:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            background: linear-gradient(135deg, #054A91, #3E7CB1);
            color: white;
            border-color: #054A91;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 4px;
            line-height: 1;
        }

        .stat-label {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            opacity: 0.8;
        }

        .quick-actions {
            display: grid;
            gap: 12px;
        }

        .quick-action {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            color: #374151;
        }

        .quick-action:hover {
            background: #054A91;
            color: white;
            transform: translateX(4px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .action-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: linear-gradient(135deg, #054A91, #3E7CB1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 14px;
        }

        .quick-action:hover .action-icon {
            background: rgba(255, 255, 255, 0.2);
        }

        .action-text {
            font-size: 14px;
            font-weight: 500;
        }

        /* Main Content Area */
        .dashboard-main {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
        }

        /* Tab Navigation */
        .tab-navigation {
            display: flex;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            padding: 0;
        }

        .tab-nav-button {
            flex: 1;
            padding: 16px 24px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            color: #64748b;
            transition: all 0.2s ease;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .tab-nav-button:hover {
            background: #e2e8f0;
            color: #374151;
        }

        .tab-nav-button.active {
            background: white;
            color: #054A91;
            border-bottom: 2px solid #054A91;
        }

        .tab-badge {
            background: #ef4444;
            color: white;
            border-radius: 10px;
            padding: 2px 8px;
            font-size: 10px;
            font-weight: 700;
            margin-left: 4px;
        }

        .tab-nav-button.active .tab-badge {
            background: #054A91;
        }

        /* Tab Content */
        .tab-content-area {
            flex: 1;
            background: white;
        }

        .tab-panel {
            display: none;
            padding: 24px;
            height: 100%;
        }

        .tab-panel.active {
            display: block;
            animation: fadeInUp 0.3s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Enhanced Table Styling */
        .modern-table-container {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
        }

        .table-wrapper {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .table-header {
            background: #f8fafc;
            padding: 20px 24px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-title {
            font-size: 18px;
            font-weight: 700;
            color: #1a202c;
            margin: 0;
        }

        .table-actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .search-box {
            position: relative;
            width: 280px;
        }

        .search-input {
            width: 100%;
            padding: 10px 16px 10px 40px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            background: white;
            transition: all 0.2s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: #054A91;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 14px;
        }

        .filter-button {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            color: #374151;
            transition: all 0.2s ease;
        }

        .filter-button:hover {
            background: #e2e8f0;
        }

        .modern-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .modern-table thead th {
            background: #f8fafc;
            padding: 16px 20px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #e2e8f0;
        }

        .modern-table tbody td {
            padding: 16px 20px;
            border-bottom: 1px solid #f1f5f9;
            color: #374151;
            vertical-align: middle;
        }

        .modern-table tbody tr {
            transition: all 0.2s ease;
        }

        .modern-table tbody tr:hover {
            background: #f8fafc;
        }

        /* Modern Status Badges */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-badge.pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-badge.approved {
            background: #d1fae5;
            color: #065f46;
        }

        .status-badge.completed {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-badge.cancelled {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-badge.referred {
            background: #fde8e6;
            color: #c2410c;
        }

        .status-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
        }

        .status-badge.pending .status-dot {
            background: #f59e0b;
        }

        .status-badge.approved .status-dot {
            background: #10b981;
        }

        .status-badge.completed .status-dot {
            background: #3b82f6;
        }

        .status-badge.cancelled .status-dot {
            background: #ef4444;
        }

        .status-badge.referred .status-dot {
            background: #f97316;
        }

        /* Modern Action Buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .btn-modern {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            min-width: 36px;
            height: 32px;
            user-select: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            touch-action: manipulation;
        }

        .btn-modern.btn-primary {
            background: #054A91;
            color: white;
        }

        .btn-modern.btn-primary:hover {
            background: #5a6fd8;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .btn-modern.btn-success {
            background: #10b981;
            color: white;
        }

        .btn-modern.btn-success:hover {
            background: #059669;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .btn-modern.btn-danger {
            background: #ef4444;
            color: white;
        }

        .btn-modern.btn-danger:hover {
            background: #dc2626;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        .btn-modern.btn-warning {
            background: #f59e0b;
            color: white;
        }

        .btn-modern.btn-warning:hover {
            background: #d97706;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        }

        .btn-modern.btn-info {
            background: #3b82f6;
            color: white;
        }

        .btn-modern.btn-info:hover {
            background: #2563eb;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        /* Patient Info Card */
        .patient-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .patient-avatar {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background: linear-gradient(135deg, #054A91, #3E7CB1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 14px;
        }

        .patient-details h4 {
            font-size: 14px;
            font-weight: 600;
            color: #1a202c;
            margin: 0;
            line-height: 1;
        }

        .patient-details p {
            font-size: 12px;
            color: #64748b;
            margin: 0;
            margin-top: 2px;
        }

        /* Priority Indicators */
        .priority-indicator {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 12px;
            font-weight: 600;
        }

        .priority-indicator.high {
            color: #dc2626;
        }

        .priority-indicator.normal {
            color: #059669;
        }

        .priority-indicator.low {
            color: #64748b;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 48px 24px;
            color: #64748b;
        }

        .empty-state i {
            font-size: 48px;
            color: #cbd5e1;
            margin-bottom: 16px;
        }

        .empty-state h3 {
            font-size: 18px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }

        .empty-state p {
            font-size: 14px;
            color: #64748b;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
                gap: 16px;
                height: auto;
            }

            .dashboard-sidebar {
                order: 2;
            }

            .dashboard-main {
                order: 1;
            }
        }

        @media (max-width: 768px) {
            .dashboard-container {
                padding: 12px;
            }

            .top-navbar {
                flex-direction: column;
                gap: 12px;
                align-items: stretch;
                padding: 12px 16px;
                margin-bottom: 16px;
            }

            .brand-section {
                flex-direction: row;
                justify-content: center;
            }

            .brand-text h1 {
                font-size: 16px;
            }

            .brand-text p {
                font-size: 11px;
            }

            .nav-actions {
                flex-direction: row;
                justify-content: space-between;
                gap: 12px;
                flex-wrap: wrap;
            }

            .user-profile {
                flex-direction: row;
                padding: 8px 12px;
                width: 100%;
            }

            .user-info h3 {
                font-size: 13px;
            }

            .user-info p {
                font-size: 10px;
            }

            .search-box {
                width: 100%;
                max-width: 280px;
            }

            .dashboard-grid {
                gap: 12px;
                min-height: auto;
            }

            .dashboard-sidebar {
                padding: 16px;
            }

            .quick-stats {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }

            .quick-stat {
                padding: 16px;
            }

            .stat-value {
                font-size: 22px;
            }

            .stat-label {
                font-size: 11px;
            }

            .quick-actions {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }

            .quick-action {
                flex-direction: column;
                text-align: center;
                padding: 12px 8px;
            }

            .action-text {
                font-size: 12px;
            }

            .tab-navigation {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                scrollbar-width: none;
                display: flex;
            }

            .tab-navigation::-webkit-scrollbar {
                display: none;
            }

            .tab-nav-button {
                padding: 12px 16px;
                font-size: 12px;
                white-space: nowrap;
                min-width: auto;
                flex-shrink: 0;
            }

            .tab-nav-button i {
                font-size: 14px;
            }

            .tab-badge {
                font-size: 9px;
                padding: 2px 6px;
            }

            .tab-panel {
                padding: 16px;
            }

            .table-header {
                flex-direction: column;
                gap: 12px;
                align-items: stretch;
                padding: 16px;
            }

            .table-title {
                text-align: center;
            }

            .table-actions {
                flex-direction: column;
                gap: 10px;
            }

            .search-box {
                max-width: 100%;
            }

            .modern-table {
                font-size: 11px;
            }

            .modern-table thead {
                display: none;
            }

            .modern-table tbody tr {
                display: block;
                margin-bottom: 16px;
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                padding: 12px;
                background: white;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            }

            .modern-table tbody td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 8px 0;
                border: none;
                text-align: right;
                gap: 8px;
            }

            .modern-table tbody td::before {
                content: attr(data-label);
                font-weight: 600;
                color: #374151;
                text-align: left;
                flex: 0 0 auto;
                max-width: 40%;
            }

            .modern-table tbody td:last-child {
                border-bottom: none;
            }

            .action-buttons {
                flex-wrap: wrap;
                gap: 6px;
                justify-content: flex-end;
                width: 100%;
            }

            .btn-modern {
                min-width: 32px;
                height: 28px;
                padding: 6px 8px;
                font-size: 11px;
            }

            .btn-modern i {
                font-size: 12px;
            }

            .status-badge {
                padding: 4px 8px;
                font-size: 10px;
                white-space: nowrap;
            }

            .notification-dropdown {
                position: fixed;
                top: 60px;
                left: 10px;
                right: 10px;
                width: auto;
                max-width: none;
                max-height: calc(100vh - 80px);
                overflow-y: auto;
            }

            /* Modal improvements for mobile */
            .modal-content {
                margin: 5% auto;
                max-height: 85vh;
                overflow-y: auto;
            }
        }

        @media (max-width: 576px) {
            .dashboard-container {
                padding: 8px;
            }

            .top-navbar {
                padding: 10px 12px;
                border-radius: 12px;
                margin-bottom: 12px;
            }

            .brand-icon {
                width: 32px;
                height: 32px;
                font-size: 14px;
            }

            .brand-text h1 {
                font-size: 14px;
            }

            .brand-text p {
                display: none;
            }

            .nav-actions {
                gap: 8px;
            }

            .user-profile {
                padding: 6px 10px;
            }

            .user-avatar {
                width: 32px;
                height: 32px;
                font-size: 12px;
            }

            .user-info h3 {
                font-size: 12px;
            }

            .user-info p {
                display: none;
            }

            .dashboard-sidebar,
            .dashboard-main {
                border-radius: 12px;
                padding: 12px;
            }

            .quick-stats {
                grid-template-columns: 1fr;
                gap: 10px;
            }

            .quick-stat {
                padding: 14px;
            }

            .quick-actions {
                grid-template-columns: 1fr;
                gap: 8px;
            }

            .quick-action {
                flex-direction: row;
                text-align: left;
                justify-content: flex-start;
                gap: 12px;
            }

            .action-icon {
                flex-shrink: 0;
            }

            .tab-panel {
                padding: 12px;
            }

            .table-header {
                padding: 12px;
            }

            .table-title {
                font-size: 16px;
            }

            .table-actions select,
            .table-actions input,
            .table-actions button {
                font-size: 12px;
                padding: 8px 12px;
                width: 100%;
            }

            .modern-table {
                font-size: 10px;
            }

            .modern-table tbody tr {
                padding: 10px;
                margin-bottom: 12px;
            }

            .modern-table tbody td {
                padding: 6px 0;
                font-size: 11px;
            }

            .modern-table tbody td::before {
                font-size: 11px;
                max-width: 45%;
            }

            .btn-modern {
                min-width: 28px;
                height: 26px;
                padding: 4px 6px;
                font-size: 10px;
                flex: 1;
            }

            .btn-modern i {
                margin: 0;
            }

            .btn-modern span {
                display: none;
            }

            .modal {
                padding: 10px;
            }

            .modal-content {
                width: 98%;
                padding: 16px;
                max-height: 90vh;
                margin: 2% auto;
                border-radius: 12px;
            }

            .modal-content h2 {
                font-size: 18px;
                margin-bottom: 16px;
            }

            .modal-content .close {
                font-size: 28px;
                top: 8px;
                right: 12px;
            }

            .form-group label {
                font-size: 12px;
                margin-bottom: 6px;
            }

            .form-group input,
            .form-group select,
            .form-group textarea {
                font-size: 13px;
                padding: 10px;
            }

            .notification-dropdown {
                top: 55px;
                left: 5px;
                right: 5px;
            }

            .notification-item {
                padding: 10px;
            }

            /* Better touch targets */
            button,
            a,
            .quick-action,
            .tab-nav-button {
                min-height: 44px;
            }

            /* Stack form buttons vertically */
            .modal-content button[type="submit"],
            .modal-content button[type="button"] {
                width: 100%;
            }

            .modal-content form > div:last-child {
                flex-direction: column;
                gap: 8px;
            }
        }

        @media (max-width: 480px) {
            .dashboard-container {
                padding: 6px;
            }

            .top-navbar {
                padding: 8px 10px;
                gap: 8px;
                border-radius: 10px;
            }

            .brand-section {
                gap: 8px;
            }

            .brand-icon {
                width: 28px;
                height: 28px;
                font-size: 12px;
            }

            .brand-text h1 {
                font-size: 13px;
            }

            .nav-actions {
                gap: 6px;
                width: 100%;
            }

            .user-profile {
                gap: 6px;
                flex: 1;
                min-width: 0;
            }

            .user-info {
                min-width: 0;
                overflow: hidden;
            }

            .user-info h3 {
                font-size: 11px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .dashboard-sidebar,
            .dashboard-main {
                padding: 10px;
                border-radius: 10px;
            }

            .quick-stat {
                padding: 12px;
            }

            .stat-value {
                font-size: 20px;
            }

            .stat-label {
                font-size: 10px;
            }

            .action-icon {
                width: 28px;
                height: 28px;
                font-size: 12px;
            }

            .action-text {
                font-size: 11px;
            }

            .tab-nav-button {
                padding: 10px 12px;
                font-size: 11px;
                gap: 4px;
            }

            .tab-badge {
                font-size: 8px;
                padding: 1px 5px;
            }

            .tab-panel {
                padding: 10px;
            }

            .table-header {
                padding: 10px;
            }

            .table-title {
                font-size: 14px;
            }

            .table-actions select,
            .table-actions input,
            .table-actions button {
                font-size: 11px;
                padding: 8px 10px;
            }

            .modern-table tbody tr {
                padding: 8px;
                margin-bottom: 10px;
            }

            .modern-table tbody td {
                padding: 5px 0;
                font-size: 11px;
            }

            .modern-table tbody td::before {
                font-size: 10px;
            }

            .btn-modern {
                min-width: 36px;
                height: 36px;
                padding: 8px;
                font-size: 12px;
                border-radius: 6px;
            }

            .action-buttons {
                gap: 4px;
            }

            .status-badge {
                font-size: 9px;
                padding: 3px 6px;
            }

            .modal {
                padding: 5px;
            }

            .modal-content {
                width: 100%;
                padding: 14px;
                margin: 1% auto;
                border-radius: 10px;
            }

            .modal-content h2 {
                font-size: 16px;
                margin-bottom: 12px;
            }

            .modal-content .close {
                font-size: 26px;
                width: 32px;
                height: 32px;
                line-height: 32px;
            }

            .form-group {
                margin-bottom: 12px;
            }

            .form-group label {
                font-size: 11px;
                margin-bottom: 4px;
            }

            .form-group input,
            .form-group select,
            .form-group textarea {
                font-size: 12px;
                padding: 9px;
            }

            .notification-dropdown {
                top: 50px;
                left: 3px;
                right: 3px;
                max-height: calc(100vh - 60px);
            }

            .notification-item {
                padding: 8px;
            }

            .notification-item h4 {
                font-size: 12px;
            }

            .notification-item p {
                font-size: 11px;
            }

            /* Improve horizontal scroll for tabs */
            .tab-navigation {
                padding: 0;
            }

            /* Better spacing for stacked elements */
            .modal-content > div {
                margin-bottom: 10px;
            }

            /* Full width buttons in mobile */
            .modal-content button {
                width: 100%;
                justify-content: center;
                min-height: 44px;
            }

            /* Improve form layout */
            .modal-content form > div[style*="grid"] {
                grid-template-columns: 1fr !important;
                gap: 10px;
            }
        }

            .btn-modern {
                min-width: 26px;
                height: 24px;
                padding: 3px 5px;
                font-size: 9px;
            }

            .modal-content {
                width: 100%;
                padding: 12px;
                border-radius: 8px;
            }

            .modal-content h2 {
                font-size: 16px;
            }

            .quick-reason-btn {
                font-size: 10px;
                padding: 5px 8px;
            }
        }

        /* Loading States */
        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }

        @keyframes loading {
            0% {
                background-position: 200% 0;
            }

            100% {
                background-position: -200% 0;
            }
        }

        .skeleton-text {
            height: 14px;
            border-radius: 4px;
            margin-bottom: 8px;
        }

        .skeleton-avatar {
            width: 40px;
            height: 40px;
            border-radius: 8px;
        }

        /* Enhanced UI/UX Improvements */
        .section {
            margin-bottom: 30px;
            padding: 25px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
        }

        .btn {
            transition: all 0.3s ease;
            font-weight: 500;
            border: none;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .btn-small {
            padding: 8px 16px;
            font-size: 13px;
            border-radius: 8px;
        }

        .form-control:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .table-container {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        /* Referral Tables Styling */
        .referrals-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .referrals-table th {
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            color: #374151;
            font-weight: 600;
            text-align: left;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .referrals-table tr:hover {
            background: #f8fafc;
            transition: background-color 0.2s ease;
        }

        .referrals-table td {
            border-left: none;
            border-right: none;
        }

        .referrals-table tr:first-child td {
            border-top: none;
        }

        .referrals-table tr:last-child td {
            border-bottom: none;
        }

        /* Tab styling improvements */
        .tab-button {
            position: relative;
            overflow: hidden;
        }

        .tab-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.6s;
        }

        .tab-button:hover::before {
            left: 100%;
        }

        .navbar {
            background: linear-gradient(135deg, #1e40af, #3b82f6);
            margin-bottom: 30px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(30, 64, 175, 0.3);
        }

        .tab-button:hover {
            background: #f3f4f6 !important;
            color: #374151 !important;
        }

        .tab-button.active {
            background: #ff6b35 !important;
            color: white !important;
        }

        /* Responsive improvements for referral tables */
        @media (max-width: 1200px) {
            .referrals-table {
                font-size: 13px;
            }

            .referrals-table th,
            .referrals-table td {
                padding: 10px 8px;
            }
        }

        @media (max-width: 768px) {
            .section {
                padding: 20px;
                margin-bottom: 20px;
            }

            .btn {
                font-size: 12px;
                padding: 10px 12px;
            }

            .referrals-table {
                font-size: 12px;
            }

            .referrals-table th,
            .referrals-table td {
                padding: 8px 6px;
            }

            .tab-button {
                padding: 10px 12px;
                font-size: 13px;
            }
        }

        @media (max-width: 576px) {

            .referrals-table th,
            .referrals-table td {
                padding: 6px 4px;
            }

            .btn-small {
                padding: 6px 8px;
                font-size: 11px;
            }
        }

        /* Notification Dropdown Styles */
        .nav-notification {
            position: relative;
            cursor: pointer;
            padding: 8px;
            border-radius: 8px;
            transition: background 0.2s;
        }

        .nav-notification:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        #notification-bell {
            transition: color 0.3s, transform 0.2s;
        }

        #notification-bell:hover {
            transform: scale(1.1);
        }

        .notification-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            width: 350px;
            max-height: 400px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
            border: 1px solid #e2e8f0;
            z-index: 99999;
            overflow: hidden;
            margin-top: 8px;
            transition: opacity 0.2s ease, transform 0.2s ease;
            opacity: 1;
            transform: translateY(0);
        }

        /* Responsive dropdown */
        @media (max-width: 768px) {
            .notification-dropdown {
                width: 320px;
                right: -20px;
            }
        }

        @media (max-width: 480px) {
            .notification-dropdown {
                width: 280px;
                right: -40px;
            }
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .notification-header {
            padding: 16px 20px;
            background: linear-gradient(135deg, #054A91 0%, #3E7CB1 100%);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }

        .notification-header h4 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
            flex: 1;
        }

        .notification-status {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .status-indicator {
            font-size: 12px;
            opacity: 0.8;
            padding: 4px 8px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .refresh-btn {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 12px;
        }

        .refresh-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(180deg);
        }

        .notification-controls {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .enable-notifications-btn {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 11px;
            font-weight: 500;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .enable-notifications-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
            transform: translateY(-1px);
        }

        .enable-notifications-btn.enabled {
            background: rgba(16, 185, 129, 0.2);
            border-color: rgba(16, 185, 129, 0.3);
            color: #10b981;
        }

        .enable-notifications-btn.denied {
            background: rgba(239, 68, 68, 0.2);
            border-color: rgba(239, 68, 68, 0.3);
            color: #ef4444;
        }

        /* Notification Status Alerts */
        .notification-status-alert {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            padding: 12px 16px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            max-width: 350px;
            animation: slideInRight 0.3s ease;
            font-size: 14px;
            border: none;
        }

        .notification-status-alert.success {
            background: #10b981;
            color: white;
        }

        .notification-status-alert.warning {
            background: #f59e0b;
            color: white;
        }

        .notification-status-alert.error {
            background: #ef4444;
            color: white;
        }

        .notification-status-alert.info {
            background: #3b82f6;
            color: white;
        }

        .notification-status-alert .alert-content {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .notification-status-alert .alert-close {
            background: none;
            border: none;
            color: inherit;
            font-size: 18px;
            cursor: pointer;
            margin-left: auto;
            opacity: 0.8;
            padding: 0;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .notification-status-alert .alert-close:hover {
            opacity: 1;
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(100%);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .mark-read-btn {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .mark-read-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .notification-list {
            max-height: 320px;
            overflow-y: auto;
        }

        .notification-item {
            padding: 16px 20px;
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.2s;
            cursor: pointer;
        }

        .notification-item:hover {
            background: #f8fafc;
        }

        .notification-item.unread {
            background: #f0f9ff;
            border-left: 4px solid #3b82f6;
        }

        .notification-content {
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .notification-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: white;
            flex-shrink: 0;
        }

        .notification-icon.appointment {
            background: linear-gradient(135deg, #054A91, #3E7CB1);
        }

        .notification-icon.referral {
            background: linear-gradient(135deg, #f093fb, #f5576c);
        }

        .notification-details {
            flex: 1;
        }

        .notification-title {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 4px;
            font-size: 14px;
        }

        .notification-message {
            color: #64748b;
            font-size: 13px;
            line-height: 1.4;
            margin-bottom: 6px;
        }

        .notification-time {
            color: #94a3b8;
            font-size: 12px;
        }

        .no-notifications {
            padding: 40px 20px;
            text-align: center;
            color: #94a3b8;
            font-style: italic;
        }

        /* Bell Animation */
        @keyframes bellShake {

            0%,
            50%,
            100% {
                transform: rotate(0deg);
            }

            10%,
            30% {
                transform: rotate(-10deg);
            }

            20%,
            40% {
                transform: rotate(10deg);
            }
        }

        .bell-shake {
            animation: bellShake 0.5s ease-in-out;
        }

        .notification-badge.pulse {
            animation: pulse 1s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.1);
            }

            100% {
                transform: scale(1);
            }
        }

        /* Modal Styles */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.65);
            backdrop-filter: blur(10px);
            z-index: 1050;
            animation: modalBackdropFadeIn 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            overflow-y: auto;
        }

        .modal-content {
            position: relative;
            background: white;
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25), 0 10px 25px rgba(0, 0, 0, 0.15);
            max-width: 600px;
            width: 100%;
            max-height: calc(100vh - 80px);
            padding: 0;
            margin: auto;
            animation: modalContentSlideIn 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.18);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* Modal Header */
        .modal-content h2 {
            margin: 0;
            padding: 28px 32px;
            background: linear-gradient(135deg, #054A91 0%, #3E7CB1 100%);
            color: white;
            font-size: 22px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
            border-radius: 20px 20px 0 0;
            flex-shrink: 0;
            z-index: 10;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .modal-content h2 i {
            font-size: 24px;
            opacity: 0.9;
        }

        /* Modal Body - Scrollable Container */
        .modal-content > div:not(.modal-header) {
            padding: 32px;
            overflow-y: auto;
            flex: 1;
        }

        .modal-content form {
            padding: 0 32px 32px 32px;
            overflow-y: auto;
            flex: 1;
        }

        /* Improved scrollbar for modal body */
        .modal-content > div::-webkit-scrollbar,
        .modal-content form::-webkit-scrollbar {
            width: 8px;
        }

        .modal-content > div::-webkit-scrollbar-track,
        .modal-content form::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 0 20px 20px 0;
        }

        .modal-content > div::-webkit-scrollbar-thumb,
        .modal-content form::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        .modal-content > div::-webkit-scrollbar-thumb:hover,
        .modal-content form::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* Appointment Detail Item Styles */
        .appointment-detail-item {
            display: grid;
            grid-template-columns: 140px 1fr;
            gap: 16px;
            padding: 20px;
            margin-bottom: 12px;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-radius: 12px;
            border-left: 4px solid #3b82f6;
            transition: all 0.3s ease;
            align-items: start;
        }

        .appointment-detail-item:hover {
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            transform: translateX(4px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .appointment-detail-label {
            font-weight: 700;
            color: #475569;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 8px;
            line-height: 1.6;
        }

        .appointment-detail-label i {
            color: #3b82f6;
            font-size: 16px;
            min-width: 20px;
        }

        .appointment-detail-value {
            font-size: 15px;
            color: #1e293b;
            font-weight: 500;
            line-height: 1.6;
            word-break: break-word;
        }

        /* Status Badge Styling */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 13px;
            text-transform: capitalize;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }

        .status-badge.status-pending {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
            border: 1px solid #fbbf24;
        }

        .status-badge.status-approved {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1e3a8a;
            border: 1px solid #3b82f6;
        }

        .status-badge.status-completed {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
            border: 1px solid #10b981;
        }

        .status-badge.status-cancelled {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
            border: 1px solid #ef4444;
        }

        .status-badge.status-referred {
            background: linear-gradient(135deg, #e9d5ff 0%, #d8b4fe 100%);
            color: #581c87;
            border: 1px solid #a855f7;
        }

        .status-badge i {
            font-size: 12px;
        }

        /* Notes Section Special Styling */
        .appointment-detail-item.notes-section {
            grid-template-columns: 1fr;
            border-left-color: #8b5cf6;
            background: linear-gradient(135deg, #faf5ff 0%, #f3e8ff 100%);
        }

        .appointment-detail-item.notes-section:hover {
            background: linear-gradient(135deg, #f3e8ff 0%, #e9d5ff 100%);
        }

        .appointment-detail-item.notes-section .appointment-detail-label {
            margin-bottom: 8px;
        }

        .appointment-detail-item.notes-section .appointment-detail-label i {
            color: #8b5cf6;
        }

        .appointment-detail-item.notes-section .appointment-detail-value {
            padding: 12px;
            background: white;
            border-radius: 8px;
            border: 1px solid #e9d5ff;
            font-style: italic;
            color: #4c1d95;
        }

        /* Modal Responsive */
        @media (max-width: 640px) {
            .modal {
                padding: 20px 10px;
            }

            .modal-content {
                max-width: 100%;
                border-radius: 16px;
                max-height: calc(100vh - 40px);
            }

            .modal-content h2 {
                padding: 20px 24px;
                font-size: 18px;
            }

            .modal-content > div:not(.modal-header),
            .modal-content form {
                padding: 24px;
            }

            .appointment-detail-item {
                grid-template-columns: 1fr;
                gap: 8px;
                padding: 16px;
            }

            .appointment-detail-label {
                font-size: 12px;
            }

            .appointment-detail-value {
                font-size: 14px;
            }
        }

        .modal .close {
            position: absolute;
            top: 28px;
            right: 28px;
            font-size: 24px;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.9);
            cursor: pointer;
            transition: all 0.3s ease;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            z-index: 11;
        }

        .modal .close:hover {
            color: white;
            background: rgba(239, 68, 68, 0.9);
            transform: rotate(90deg) scale(1.1);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .btn-modern {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            min-height: 44px;
            justify-content: center;
        }

        .btn-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .btn-modern:active {
            transform: translateY(0);
        }

        /* Quick Reason Buttons */
        .quick-reason-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            color: #64748b;
            cursor: pointer;
            transition: all 0.3s ease;
            margin: 2px;
        }

        .quick-reason-btn:hover {
            background: #e2e8f0;
            border-color: #cbd5e1;
            color: #475569;
            transform: translateY(-1px);
        }

        .quick-reason-btn:active {
            transform: translateY(0);
            background: #cbd5e1;
        }

        .quick-reason-btn i {
            font-size: 10px;
            opacity: 0.7;
        }

        /* Alert Styles for Modals */
        .modal-alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin: 16px 0;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 500;
        }

        .modal-alert.success {
            background: #d1fae5;
            border: 1px solid #a7f3d0;
            color: #065f46;
        }

        .modal-alert.error {
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #7f1d1d;
        }

        .modal-alert.warning {
            background: #fef3c7;
            border: 1px solid #fde68a;
            color: #78350f;
        }

        .modal-alert.info {
            background: #dbeafe;
            border: 1px solid #bfdbfe;
            color: #1e3a8a;
        }

        /* Animations */
        @keyframes modalBackdropFadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes modalContentSlideIn {
            from {
                opacity: 0;
                transform: translate(-50%, -45%);
            }

            to {
                opacity: 1;
                transform: translate(-50%, -50%);
            }
        }

        /* Responsive Modal Design */
        @media (max-width: 768px) {
            .modal-content {
                width: 95%;
                padding: 24px;
                max-height: 95vh;
            }

            .modal-content h2 {
                font-size: 20px;
            }

            .quick-reason-btn {
                font-size: 11px;
                padding: 6px 10px;
            }
        }

        /* Landscape Orientation Support */
        @media (max-height: 600px) and (orientation: landscape) {
            .dashboard-container {
                padding: 8px;
            }

            .top-navbar {
                padding: 8px 16px;
                margin-bottom: 8px;
            }

            .dashboard-grid {
                min-height: auto;
                gap: 12px;
            }

            .quick-stats {
                grid-template-columns: repeat(4, 1fr);
                gap: 8px;
            }

            .quick-stat {
                padding: 10px;
            }

            .stat-value {
                font-size: 18px;
            }

            .stat-label {
                font-size: 9px;
            }

            .tab-nav-button {
                padding: 8px 12px;
                font-size: 11px;
            }

            .tab-panel {
                padding: 12px;
            }

            .modal-content {
                max-height: 95vh;
                overflow-y: auto;
            }

            .notification-dropdown {
                max-height: 80vh;
            }
        }

        /* Tablet Portrait (768px - 1024px) */
        @media (min-width: 769px) and (max-width: 1024px) {
            .dashboard-grid {
                grid-template-columns: 280px 1fr;
            }

            .quick-stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .quick-actions {
                grid-template-columns: repeat(2, 1fr);
            }

            .tab-nav-button {
                font-size: 13px;
                padding: 14px 20px;
            }
        }

        /* Extra Small Devices */
        @media (max-width: 360px) {
            .dashboard-container {
                padding: 4px;
            }

            .top-navbar {
                padding: 6px 8px;
            }

            .brand-text h1 {
                font-size: 12px;
            }

            .tab-nav-button {
                padding: 8px 10px;
                font-size: 10px;
            }

            .tab-badge {
                font-size: 7px;
                padding: 1px 4px;
            }

            .modern-table tbody td {
                font-size: 10px;
            }

            .modern-table tbody td::before {
                font-size: 9px;
            }

            .btn-modern {
                min-width: 32px;
                height: 32px;
                padding: 6px;
                font-size: 11px;
            }
        }
    </style>
</head>

<body class="modern-dashboard">
    <div class="dashboard-container">
        <!-- Top Navigation -->
        <header class="top-navbar">
            <div class="brand-section">
                <div class="brand-icon">
                    <i class="fas fa-hospital-alt"></i>
                </div>
                <div class="brand-text">
                    <h1><?php echo htmlspecialchars($branchName); ?></h1>
                    <p>Staff Dashboard</p>
                </div>
            </div>
            <div class="nav-actions">
                <div class="nav-notification" title="Notifications" onclick="showNotificationDropdown()">
                    <i class="fas fa-bell" id="notification-bell"></i>
                    <span class="notification-badge" id="notification-count">0</span>
                    <!-- Notification Dropdown -->
                    <div class="notification-dropdown" id="notification-dropdown" style="display: none;">
                        <div class="notification-header">
                            <h4>Recent Notifications</h4>
                            <div class="notification-status">
                                <span class="status-indicator" id="polling-status">🔄 Starting...</span>
                                <button onclick="refreshNotifications()" class="refresh-btn" title="Check now">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>
                            <div class="notification-controls">
                                <button onclick="requestNotificationPermission()" class="enable-notifications-btn"
                                    id="enable-notifications-btn" title="Enable browser notifications">
                                    <i class="fas fa-bell-slash"></i> Enable Notifications
                                </button>
                                <button onclick="markAllAsRead()" class="mark-read-btn">Mark all as read</button>
                            </div>
                        </div>
                        <div class="notification-list" id="notification-list">
                            <div class="no-notifications">No new notifications</div>
                        </div>
                    </div>
                </div>
                <div class="user-profile" onclick="logout()">
                    <div class="user-avatar">
                        <?php
                        $userName = getSessionName();
                        echo strtoupper(substr($userName, 0, 1));
                        ?>
                    </div>
                    <div class="user-info">
                        <h3><?php echo htmlspecialchars($userName); ?></h3>
                        <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($branchLocation); ?></p>
                    </div>
                    <i class="fas fa-sign-out-alt"></i>
                </div>
            </div>
        </header>

        <!-- Main Dashboard Grid -->
        <div class="dashboard-grid">
            <!-- Left Sidebar -->
            <aside class="dashboard-sidebar">
                <!-- Quick Stats -->
                <div class="sidebar-section">
                    <h3 class="sidebar-title">
                        <i class="fas fa-chart-line"></i>
                        Today's Overview
                    </h3>
                    <div class="quick-stats">
                        <div class="quick-stat" onclick="showTodayOnly()">
                            <div class="stat-value" id="stats-pending-today">0</div>
                            <div class="stat-label">Pending Today</div>
                        </div>
                        <div class="quick-stat" onclick="showByStatus('approved')">
                            <div class="stat-value" id="stats-approved-today">0</div>
                            <div class="stat-label">Approved Today</div>
                        </div>
                        <div class="quick-stat" onclick="filterByToday()">
                            <div class="stat-value" id="stats-total-today">0</div>
                            <div class="stat-label">Total Today</div>
                        </div>
                        <div class="quick-stat" onclick="openReferralManagement()">
                            <div class="stat-value" id="stats-pending-referrals">0</div>
                            <div class="stat-label">Pending Referrals</div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="sidebar-section">
                    <h3 class="sidebar-title">
                        <i class="fas fa-bolt"></i>
                        Quick Actions
                    </h3>
                    <div class="quick-actions">
                        <a href="#" class="quick-action" onclick="showAllPending()">
                            <div class="action-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="action-text">View Pending</div>
                        </a>
                        <a href="#" class="quick-action" onclick="showTodayOnly()">
                            <div class="action-icon">
                                <i class="fas fa-calendar-day"></i>
                            </div>
                            <div class="action-text">Today's Schedule</div>
                        </a>
                        <a href="#" class="quick-action" onclick="openReferralManagement()">
                            <div class="action-icon">
                                <i class="fas fa-exchange-alt"></i>
                            </div>
                            <div class="action-text">Manage Referrals</div>
                        </a>
                        <a href="#" class="quick-action" onclick="openWalkInModal()">
                            <div class="action-icon">
                                <i class="fas fa-walking"></i>
                            </div>
                            <div class="action-text">Walk-in Patient</div>
                        </a>
                    </div>
                </div>
            </aside>

            <!-- Main Content Area -->
            <main class="dashboard-main">
                <!-- Tab Navigation -->
                <nav class="tab-navigation">
                    <button class="tab-nav-button active" onclick="showTab('pending')" id="pending-tab">
                        <i class="fas fa-clock"></i>
                        Pending Appointments
                        <span class="tab-badge" id="pending-count-badge">0</span>
                    </button>
                    <button class="tab-nav-button" onclick="showTab('all')" id="all-tab">
                        <i class="fas fa-calendar"></i>
                        All Appointments
                        <span class="tab-badge" id="all-count-badge">0</span>
                    </button>
                    <button class="tab-nav-button" onclick="showTab('referrals')" id="referrals-tab">
                        <i class="fas fa-exchange-alt"></i>
                        Patient Referrals
                        <span class="tab-badge" id="referrals-count-badge">0</span>
                    </button>
                </nav>

                <!-- Tab Content Area -->
                <div class="tab-content-area">
                    <!-- Pending Appointments Tab -->
                    <div id="pending-tab-content" class="tab-panel active">
                        <div class="modern-table-container">
                            <div class="table-header">
                                <h3 class="table-title">
                                    <i class="fas fa-clock"></i>
                                    Pending Appointments
                                </h3>
                                <div class="table-actions">
                                    <div class="search-box">
                                        <i class="fas fa-search search-icon"></i>
                                        <input type="text" class="search-input" placeholder="Search patients..."
                                            id="search-pending">
                                    </div>
                                    <button class="filter-button" onclick="refreshData()">
                                        <i class="fas fa-sync-alt"></i>
                                        Refresh
                                    </button>
                                </div>
                            </div>
                            <div id="pending-appointments-list">
                                <!-- Pending appointments will be loaded here -->
                            </div>
                        </div>
                    </div>

                    <!-- All Appointments Tab -->
                    <div id="all-tab-content" class="tab-panel">
                        <div class="modern-table-container">
                            <div class="table-header">
                                <h3 class="table-title">
                                    <i class="fas fa-calendar"></i>
                                    All Appointments
                                </h3>
                                <div class="table-actions">
                                    <div class="search-box">
                                        <i class="fas fa-search search-icon"></i>
                                        <input type="text" class="search-input" placeholder="Search appointments..."
                                            id="search-patient" onkeyup="filterAppointments()">
                                    </div>
                                    <select id="filter-status" class="filter-button" onchange="filterAppointments()">
                                        <option value="">All Status</option>
                                        <option value="pending">Pending</option>
                                        <option value="approved">Approved</option>
                                        <option value="completed">Completed</option>
                                        <option value="cancelled">Cancelled</option>
                                        <option value="referred">Referred</option>
                                    </select>
                                    <input type="date" id="filter-date" class="filter-button"
                                        onchange="filterAppointments()">
                                    <button class="filter-button" onclick="resetFilters()">
                                        <i class="fas fa-times"></i>
                                        Clear
                                    </button>
                                </div>
                            </div>
                            <div id="all-appointments-list">
                                <!-- All appointments will be loaded here -->
                            </div>
                        </div>
                    </div>

                    <!-- Patient Referrals Tab -->
                    <div id="referrals-tab-content" class="tab-panel">
                        <div class="modern-table-container">
                            <div class="table-header">
                                <h3 class="table-title">
                                    <i class="fas fa-exchange-alt"></i>
                                    Patient Referrals Management
                                </h3>
                                <div class="table-actions">
                                    <button class="filter-button" onclick="loadReferralData()">
                                        <i class="fas fa-sync-alt"></i>
                                        Refresh
                                    </button>
                                </div>
                            </div>

                            <!-- Referral Sub-tabs -->
                            <div class="tab-navigation" style="border-top: 1px solid #e2e8f0;">
                                <button class="tab-nav-button active" onclick="showReferralTab('incoming')"
                                    id="incoming-tab">
                                    <i class="fas fa-inbox"></i>
                                    Incoming
                                    <span class="tab-badge" id="incoming-badge">0</span>
                                </button>
                                <button class="tab-nav-button" onclick="showReferralTab('sent')" id="sent-tab">
                                    <i class="fas fa-paper-plane"></i>
                                    Sent
                                    <span class="tab-badge" id="sent-badge">0</span>
                                </button>
                            </div>

                            <!-- Incoming Referrals -->
                            <div id="incoming-referrals-tab" class="referral-tab-content">
                                <!-- Workflow Info Banner -->
                                <div
                                    style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border-left: 4px solid #3b82f6; padding: 16px; margin: 16px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                                    <div style="display: flex; align-items: flex-start; gap: 12px;">
                                        <div style="color: #1d4ed8; font-size: 20px; margin-top: 2px;">
                                            <i class="fas fa-info-circle"></i>
                                        </div>
                                        <div style="flex: 1;">
                                            <h4
                                                style="margin: 0 0 8px 0; color: #1e40af; font-weight: 600; font-size: 14px;">
                                                New Two-Step Referral Workflow
                                            </h4>
                                            <p style="margin: 0; color: #1e40af; font-size: 13px; line-height: 1.5;">
                                                📋 <strong>Step 1:</strong> Branch A refers patient to your branch<br>
                                                ✅ <strong>Step 2:</strong> Patient approves the referral (only approved
                                                referrals appear here)<br>
                                                🏥 <strong>Step 3:</strong> You review and accept/reject
                                                patient-approved referrals
                                            </p>
                                            <div
                                                style="margin-top: 8px; padding: 8px 12px; background: rgba(59, 130, 246, 0.1); border-radius: 6px; font-size: 12px; color: #1e40af;">
                                                <i class="fas fa-shield-alt"></i> <strong>Note:</strong> This ensures
                                                patient consent before processing appointments
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div id="incoming-referrals-list">
                                    <!-- Incoming referrals will be loaded here -->
                                </div>
                            </div>

                            <!-- Sent Referrals -->
                            <div id="sent-referrals-tab" class="referral-tab-content" style="display: none;">
                                <div id="sent-referrals-list">
                                    <!-- Sent referrals will be loaded here -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>


    <script src="../../assets/js/auth.js"></script>
    <script>
        // =================== ESSENTIAL FUNCTION STUBS (PREVENT UNDEFINED ERRORS) ===================
        // These are basic stubs that will be properly defined later in the script

        function showTab(tabName) {
            console.log('showTab called with:', tabName);
        }
        function logout() {
            console.log('logout called');
        }
        function openWalkInModal() {
            console.log('openWalkInModal called');
        }
        function showTodayOnly() {
            console.log('showTodayOnly called');
        }
        function showByStatus(status) {
            console.log('showByStatus called with:', status);
        }
        function filterByToday() {
            showTodayOnly();
        }
        function showAllPending() {
            console.log('showAllPending called');
        }
        function openReferralManagement() {
            console.log('openReferralManagement called');
        }

        // Global variables
        let allAppointments = [];
        let currentFilter = {};

        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function () {
            initializeDashboard();
            initializeModernUI();
            initializeReferralFormValidation(); // Add referral form validation
            initializeReferralFormSubmission(); // Add referral form submission
        });

        function initializeModernUI() {
            // Set up search functionality
            setupSearchFunctionality();

            // Initialize notification count
            updateNotificationCount();

            // Set active tab
            showTab('pending');

            // Update badge counts
            updateTabBadges();
        }

        function setupSearchFunctionality() {
            // Pending appointments search
            const searchPending = document.getElementById('search-pending');
            if (searchPending) {
                searchPending.addEventListener('input', function () {
                    // Implement search functionality for pending appointments
                    filterPendingAppointments(this.value);
                });
            }

            // All appointments search is already handled by existing code
        }

        function filterPendingAppointments(searchTerm) {
            // This would filter the pending appointments based on search term
            // Implementation would depend on your current data structure
        }

        function updateNotificationCount() {
            // This function is now handled by the real-time notification system
            // The notification count will be updated automatically by updateNotificationDisplay()

            // For initial load, we can still sync with pending appointments if no notifications exist
            if (notificationQueue.length === 0) {
                const pendingCountBadge = document.getElementById('pending-count-badge');
                const notificationBadge = document.getElementById('notification-count');

                if (notificationBadge && pendingCountBadge) {
                    const pendingCount = parseInt(pendingCountBadge.textContent) || 0;
                    notificationBadge.textContent = pendingCount;
                    notificationBadge.style.display = pendingCount > 0 ? 'flex' : 'none';
                }
            }
        }

        function updateTabBadges() {
            // Update tab badges with current counts
            // These badges are updated directly in the display functions:
            // - Pending appointments badge: updated in displayPendingAppointments()
            // - All appointments badge: updated in displayAllAppointments() 
            // - Referrals badge: updated in loadIncomingReferrals()

            // No additional action needed as badges are updated individually
        }

        // =================== ACTUAL FUNCTION IMPLEMENTATIONS ===================

        function showTab(tabName) {
            // Hide all tab panels
            const tabPanels = document.querySelectorAll('.tab-panel');
            tabPanels.forEach(panel => {
                panel.classList.remove('active');
            });

            // Remove active class from all tab buttons
            const tabButtons = document.querySelectorAll('.tab-nav-button');
            tabButtons.forEach(button => {
                button.classList.remove('active');
            });

            // Show selected tab panel
            const selectedPanel = document.getElementById(tabName + '-tab-content');
            const selectedButton = document.getElementById(tabName + '-tab');

            if (selectedPanel) {
                selectedPanel.classList.add('active');
            }

            if (selectedButton) {
                selectedButton.classList.add('active');
            }

            // Load appropriate data
            switch (tabName) {
                case 'pending':
                    loadPendingAppointments();
                    break;
                case 'all':
                    loadAllAppointments();
                    break;
                case 'referrals':
                    loadReferralData();
                    break;
            }
        }

        // Ensure global availability
        window.showTab = showTab;

        function initializeDashboard() {
            // Set today's date in filter
            document.getElementById('filter-date').value = new Date().toISOString().split('T')[0];

            // Load data (initial load shows notification)
            refreshData();

            // Set up auto-refresh every 30 seconds (silent mode)
            setInterval(() => refreshData(true), 30000);
            setInterval(() => loadReferralData(true), 60000);

            // Initialize real-time notifications
            initializeNotifications();
        }

        // =================== SIMPLE POLLING NOTIFICATION SYSTEM ===================

        let lastNotificationCheck = Date.now();
        let notificationQueue = [];
        let shownNotifications = new Set(); // Track IDs of notifications we've already shown popups for
        let isNotificationDropdownOpen = false;
        let pollingInterval = null;
        let pollingIntervalMs = 15000; // Check every 15 seconds

        function initializeNotifications() {
            // Update notification button state on page load
            updateNotificationButtonState();

            // Start polling for notifications
            startNotificationPolling();
            
            // Clean up shown notifications Set every hour to prevent memory issues
            setInterval(() => {
                if (shownNotifications.size > 100) {
                    // Keep only the most recent 50 entries by converting to array and back
                    const recentEntries = Array.from(shownNotifications).slice(-50);
                    shownNotifications = new Set(recentEntries);
                    console.log('🧹 Cleaned up shown notifications. Current size:', shownNotifications.size);
                }
            }, 3600000); // Run every hour

            // Close dropdown when clicking outside
            document.addEventListener('click', function (event) {
                const dropdown = document.getElementById('notification-dropdown');
                const notification = document.querySelector('.nav-notification');

                if (!notification.contains(event.target) && isNotificationDropdownOpen) {
                    hideNotificationDropdown();
                }
            });

            // Don't auto-request permission on page load - let user choose
            console.log('🔔 Notification system initialized. Current permission:', Notification.permission);

            // Pause polling when page is not visible to save resources
            document.addEventListener('visibilitychange', function () {
                if (document.hidden) {
                    updatePollingStatus('⏸️ Paused');
                    stopNotificationPolling();
                } else {
                    startNotificationPolling();
                }
            });
        }

        function updateNotificationButtonState() {
            const btn = document.getElementById('enable-notifications-btn');
            if (!btn) return;

            if (!('Notification' in window)) {
                btn.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Not Supported';
                btn.className = 'enable-notifications-btn denied';
                btn.disabled = true;
                btn.title = 'Browser notifications are not supported';
                return;
            }

            const permission = Notification.permission;

            if (permission === 'granted') {
                btn.innerHTML = '<i class="fas fa-bell"></i> Enabled';
                btn.className = 'enable-notifications-btn enabled';
                btn.title = 'Browser notifications are enabled';
            } else if (permission === 'denied') {
                btn.innerHTML = '<i class="fas fa-bell-slash"></i> Denied';
                btn.className = 'enable-notifications-btn denied';
                btn.title = 'Enable notifications in browser settings';
            } else {
                btn.innerHTML = '<i class="fas fa-bell-slash"></i> Enable Notifications';
                btn.className = 'enable-notifications-btn';
                btn.title = 'Click to enable browser notifications';
            }
        }

        function startNotificationPolling() {
            // Initial check
            updatePollingStatus('🔄 Checking...');
            checkForNewNotifications();

            // Set up regular polling
            pollingInterval = setInterval(() => {
                updatePollingStatus('🔄 Checking...');
                checkForNewNotifications();
            }, pollingIntervalMs);

            console.log(`✅ Notification polling started (checking every ${pollingIntervalMs / 1000} seconds)`);
        }

        function stopNotificationPolling() {
            if (pollingInterval) {
                clearInterval(pollingInterval);
                pollingInterval = null;
                updatePollingStatus('⏸️ Stopped');
                console.log('⏸️ Notification polling stopped');
            }
        }

        function updatePollingStatus(status) {
            const statusElement = document.getElementById('polling-status');
            if (statusElement) {
                statusElement.textContent = status;
            }
        }

        function checkForNewNotifications() {
            // Check for new appointments
            checkForNewAppointments();

            // Check for new referrals
            checkForNewReferrals();

            // Update last check timestamp
            lastNotificationCheck = Date.now();

            // Update status after checks complete
            setTimeout(() => {
                updatePollingStatus('✅ Up to date');
            }, 1000);
        }

        function checkForNewAppointments() {
            fetch('../../src/controllers/AppointmentController.php?action=getNewAppointments&since=' + lastNotificationCheck)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success && data.appointments && data.appointments.length > 0) {
                        data.appointments.forEach(appointment => {
                            handleNewAppointmentNotification(appointment);
                        });
                    }
                })
                .catch(error => {
                    console.log('Appointment check failed:', error.message);
                    updatePollingStatus('⚠️ Check failed');
                    setTimeout(() => updatePollingStatus('✅ Retrying...'), 2000);
                });
        }

        function checkForNewReferrals() {
            fetch('../../src/controllers/ReferralController.php?action=getNewReferrals&since=' + lastNotificationCheck)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success && data.referrals && data.referrals.length > 0) {
                        data.referrals.forEach(referral => {
                            handleNewReferralNotification(referral);
                        });
                    }
                })
                .catch(error => {
                    console.log('Referral check failed:', error.message);
                    updatePollingStatus('⚠️ Check failed');
                    setTimeout(() => updatePollingStatus('✅ Retrying...'), 2000);
                });
        }

        function handleNewAppointmentNotification(appointment) {
            // Create a unique ID for this notification
            const notificationId = `appt-${appointment.id}-${appointment.created_at || appointment.appointment_date}`;
            
            // Check if we've already shown a popup for this notification
            const isNewNotification = !shownNotifications.has(notificationId);
            
            if (isNewNotification) {
                // Mark as shown
                shownNotifications.add(notificationId);
                
                // Show popup alert only for new notifications
                showNewAppointmentAlert(1);
                
                // Send browser notification if supported
                showBrowserNotification(
                    'New Appointment Request',
                    `${appointment.patient_name} scheduled for ${appointment.appointment_date} at ${appointment.appointment_time}`,
                    'appointment'
                );
            }
            
            // Always add to notification queue for dropdown display
            addNotification('appointment', appointment);

            // Refresh appointment data silently
            loadPendingAppointments(true);
            loadAllAppointments(true);
            updateStats(true);
        }

        function handleNewReferralNotification(referral) {
            // Create a unique ID for this notification
            const notificationId = `ref-${referral.id}-${referral.created_at || referral.referral_date}`;
            
            // Check if we've already shown a popup for this notification
            const isNewNotification = !shownNotifications.has(notificationId);
            
            if (isNewNotification) {
                // Mark as shown
                shownNotifications.add(notificationId);
                
                // Show popup alert only for new notifications
                showNewReferralAlert(1);
                
                // Send browser notification if supported
                showBrowserNotification(
                    'New Referral Received',
                    `Referral from ${referral.from_branch_name} for ${referral.patient_name}`,
                    'referral'
                );
            }
            
            // Always add to notification queue for dropdown display
            addNotification('referral', referral);

            // Refresh referral data silently
            loadReferralData(true);
            updateReferralStats(true);
        }

        function requestNotificationPermission() {
            // Check if notifications are supported
            if (!('Notification' in window)) {
                console.log('⚠️ This browser does not support notifications');
                showNotificationStatusAlert('Browser does not support notifications', 'warning');
                return;
            }

            // Check current permission status
            const permission = Notification.permission;

            if (permission === 'granted') {
                console.log('✅ Browser notifications already enabled');
                showNotificationStatusAlert('Browser notifications are enabled!', 'success');
                return;
            }

            if (permission === 'denied') {
                console.log('❌ Browser notifications previously denied');
                showNotificationStatusAlert('Notifications were previously denied. Please enable them in browser settings.', 'warning');
                showNotificationInstructions();
                return;
            }

            // Request permission (only works with user gesture)
            if (permission === 'default') {
                console.log('🔔 Requesting notification permission...');

                Notification.requestPermission().then(function (result) {
                    if (result === 'granted') {
                        console.log('✅ Browser notifications enabled');
                        showNotificationStatusAlert('Browser notifications enabled successfully!', 'success');
                        updateNotificationButtonState(); // Update button state
                        // Send a test notification
                        setTimeout(() => {
                            showTestNotification();
                        }, 1000);
                    } else {
                        console.log('⚠️ Browser notifications denied');
                        showNotificationStatusAlert('Notifications were denied. You can enable them later in browser settings.', 'warning');
                        updateNotificationButtonState(); // Update button state
                        showNotificationInstructions();
                    }
                }).catch(function (error) {
                    console.error('Error requesting notification permission:', error);
                    showNotificationStatusAlert('Error requesting notification permission', 'error');
                    updateNotificationButtonState(); // Update button state
                });
            }
        }

        function showNotificationStatusAlert(message, type) {
            // Create a temporary status alert
            const alertDiv = document.createElement('div');
            alertDiv.className = `notification-status-alert ${type}`;
            alertDiv.innerHTML = `
                <div class="alert-content">
                    <span class="alert-icon">
                        ${type === 'success' ? '✅' : type === 'warning' ? '⚠️' : '❌'}
                    </span>
                    <span class="alert-message">${message}</span>
                    <button class="alert-close" onclick="this.parentElement.parentElement.remove()">×</button>
                </div>
            `;

            // Add styles
            alertDiv.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 10000;
                background: ${type === 'success' ? '#10b981' : type === 'warning' ? '#f59e0b' : '#ef4444'};
                color: white;
                padding: 12px 16px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                max-width: 350px;
                animation: slideInRight 0.3s ease;
            `;

            document.body.appendChild(alertDiv);

            // Auto-remove after 6 seconds
            setTimeout(() => {
                if (alertDiv.parentElement) {
                    alertDiv.remove();
                }
            }, 6000);
        }

        function showNotificationInstructions() {
            const instructions = `
                <div class="notification-instructions">
                    <h4>🔔 How to Enable Browser Notifications:</h4>
                    <ol>
                        <li><strong>Chrome:</strong> Click the lock icon in address bar → Notifications → Allow</li>
                        <li><strong>Firefox:</strong> Click the shield icon → Permissions → Notifications → Allow</li>
                        <li><strong>Safari:</strong> Safari menu → Preferences → Websites → Notifications → Allow</li>
                        <li><strong>Edge:</strong> Click the lock icon → Notifications → Allow</li>
                    </ol>
                    <p><small>Then refresh the page and click "Enable Notifications" again.</small></p>
                </div>
            `;

            setTimeout(() => {
                showNotificationStatusAlert('Click the notification bell for setup instructions', 'info');
            }, 2000);
        }

        function showTestNotification() {
            if (Notification.permission === 'granted') {
                const testNotification = new Notification('🦷 Dental Clinic Notifications', {
                    body: 'Notifications are now enabled! You\'ll receive alerts for new appointments and referrals.',
                    icon: '../../assets/images/dental-icon.png',
                    badge: '../../assets/images/notification-badge.png',
                    tag: 'test',
                    requireInteraction: false,
                    silent: false
                });

                setTimeout(() => testNotification.close(), 4000);

                testNotification.onclick = function () {
                    window.focus();
                    testNotification.close();
                };
            }
        }

        function refreshNotifications() {
            updatePollingStatus('🔄 Refreshing...');
            checkForNewNotifications();

            // Visual feedback - rotate the refresh button
            const refreshBtn = event.target.closest('.refresh-btn');
            if (refreshBtn) {
                refreshBtn.style.transform = 'rotate(360deg)';
                setTimeout(() => {
                    refreshBtn.style.transform = '';
                }, 300);
            }
        }

        function showBrowserNotification(title, body, type) {
            // Check if browser notifications are supported and permitted
            if ('Notification' in window && Notification.permission === 'granted') {
                const notification = new Notification(title, {
                    body: body,
                    icon: '../../assets/images/dental-icon.png',
                    badge: '../../assets/images/notification-badge.png',
                    tag: type,
                    requireInteraction: false,
                    silent: false
                });

                // Auto-close after 5 seconds
                setTimeout(() => notification.close(), 5000);

                // Handle click to focus window
                notification.onclick = function () {
                    window.focus();
                    notification.close();
                };
            }
        }

        // Clean up polling on page unload
        window.addEventListener('beforeunload', function () {
            stopNotificationPolling();
        });

        function addNotification(type, data) {
            const notification = {
                id: Date.now() + Math.random(),
                type: type,
                data: data,
                timestamp: new Date(),
                read: false
            };

            notificationQueue.unshift(notification);

            // Keep only last 20 notifications
            if (notificationQueue.length > 20) {
                notificationQueue = notificationQueue.slice(0, 20);
            }

            updateNotificationDisplay();
        }

        function updateNotificationDisplay() {
            const unreadCount = notificationQueue.filter(n => !n.read).length;
            const badge = document.getElementById('notification-count');
            const bell = document.getElementById('notification-bell');

            // Update badge count
            badge.textContent = unreadCount;
            badge.style.display = unreadCount > 0 ? 'flex' : 'none';

            // Add pulse effect for unread notifications
            if (unreadCount > 0) {
                badge.classList.add('pulse');
                bell.style.color = '#f59e0b';
            } else {
                badge.classList.remove('pulse');
                bell.style.color = '';
            }

            // Update dropdown content
            updateNotificationDropdown();
        }

        function updateNotificationDropdown() {
            const listContainer = document.getElementById('notification-list');

            if (notificationQueue.length === 0) {
                listContainer.innerHTML = '<div class="no-notifications">No new notifications</div>';
                return;
            }

            let html = '';
            notificationQueue.forEach(notification => {
                html += createNotificationHTML(notification);
            });

            listContainer.innerHTML = html;
        }

        function createNotificationHTML(notification) {
            const timeAgo = getTimeAgo(notification.timestamp);
            const unreadClass = notification.read ? '' : 'unread';

            let icon, title, message;

            if (notification.type === 'appointment') {
                const appt = notification.data;
                icon = '<i class="fas fa-calendar-plus"></i>';
                title = 'New Appointment Request';
                message = `${appt.patient_name || 'Patient'} scheduled for ${appt.appointment_date} at ${appt.appointment_time}`;

                if (appt.treatment_name) {
                    message += ` - ${appt.treatment_name}`;
                }
            } else if (notification.type === 'referral') {
                const ref = notification.data;
                icon = '<i class="fas fa-exchange-alt"></i>';
                title = 'New Referral Received';
                message = `Referral from ${ref.from_branch_name || 'Another Branch'} for ${ref.patient_name || 'Patient'}`;
            }

            return `
                <div class="notification-item ${unreadClass}" onclick="markNotificationAsRead('${notification.id}')">
                    <div class="notification-content">
                        <div class="notification-icon ${notification.type}">
                            ${icon}
                        </div>
                        <div class="notification-details">
                            <div class="notification-title">${title}</div>
                            <div class="notification-message">${message}</div>
                            <div class="notification-time">${timeAgo}</div>
                        </div>
                    </div>
                </div>
            `;
        }

        function getTimeAgo(timestamp) {
            const now = new Date();
            const diff = now - timestamp;
            const minutes = Math.floor(diff / 60000);

            if (minutes < 1) return 'Just now';
            if (minutes < 60) return `${minutes}m ago`;

            const hours = Math.floor(minutes / 60);
            if (hours < 24) return `${hours}h ago`;

            const days = Math.floor(hours / 24);
            return `${days}d ago`;
        }

        function showNotificationDropdown() {
            const dropdown = document.getElementById('notification-dropdown');
            const notificationBtn = document.querySelector('.nav-notification');
            const isOpen = dropdown.style.display === 'block';

            if (isOpen) {
                // Hide dropdown with fade out
                dropdown.style.opacity = '0';
                dropdown.style.transform = 'translateY(-10px)';
                notificationBtn.classList.remove('active');
                setTimeout(() => {
                    dropdown.style.display = 'none';
                    isNotificationDropdownOpen = false;
                }, 200);
            } else {
                // Show dropdown with fade in
                dropdown.style.display = 'block';
                dropdown.style.opacity = '0';
                dropdown.style.transform = 'translateY(-10px)';
                notificationBtn.classList.add('active');
                isNotificationDropdownOpen = true;

                // Force reflow and animate
                setTimeout(() => {
                    dropdown.style.opacity = '1';
                    dropdown.style.transform = 'translateY(0)';
                }, 10);
            }
        }

        function hideNotificationDropdown() {
            const dropdown = document.getElementById('notification-dropdown');
            const notificationBtn = document.querySelector('.nav-notification');
            dropdown.style.opacity = '0';
            dropdown.style.transform = 'translateY(-10px)';
            notificationBtn.classList.remove('active');
            setTimeout(() => {
                dropdown.style.display = 'none';
                isNotificationDropdownOpen = false;
            }, 200);
        }

        function markNotificationAsRead(notificationId) {
            const notification = notificationQueue.find(n => n.id == notificationId);
            if (notification) {
                notification.read = true;
                updateNotificationDisplay();
            }
        }

        function markAllAsRead() {
            notificationQueue.forEach(n => n.read = true);
            updateNotificationDisplay();
        }

        function showNewAppointmentAlert(count) {
            const bell = document.getElementById('notification-bell');

            // Add shake animation
            bell.classList.add('bell-shake');
            setTimeout(() => bell.classList.remove('bell-shake'), 500);

            // Play notification sound (optional)
            try {
                playNotificationSound();
            } catch (e) {
                console.log('Notification sound not available');
            }

            // Show toast notification
            const message = count === 1 ? 'New appointment request received!' : `${count} new appointments received!`;
            showAlert(message, 'info', 4000);
        }

        function showNewReferralAlert(count) {
            const bell = document.getElementById('notification-bell');

            // Add shake animation
            bell.classList.add('bell-shake');
            setTimeout(() => bell.classList.remove('bell-shake'), 500);

            // Play notification sound
            try {
                playNotificationSound();
            } catch (e) {
                console.log('Notification sound not available');
            }

            // Show toast notification
            const message = count === 1 ? 'New referral received!' : `${count} new referrals received!`;
            showAlert(message, 'warning', 4000);
        }

        function playNotificationSound() {
            // Create a simple notification beep
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();

            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);

            oscillator.frequency.value = 800;
            oscillator.type = 'sine';

            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);

            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.3);
        }
        // =================== END NOTIFICATION SYSTEM ===================

        function refreshData(silent = false) {
            if (!silent) {
                showAlert('Refreshing data...', 'info');
            } else {
                // Silent refresh: add subtle indicator without alert
                addSilentRefreshIndicator();
            }
            loadPendingAppointments(silent);
            loadAllAppointments(silent);
            loadReferralData(silent); // This will update the referrals badge
            updateStats(silent);
        }

        function addSilentRefreshIndicator() {
            // Add a subtle pulse effect to show data is being refreshed
            const dashboard = document.querySelector('.modern-dashboard');
            if (dashboard) {
                dashboard.style.transition = 'opacity 0.3s ease';
                dashboard.style.opacity = '0.95';

                setTimeout(() => {
                    dashboard.style.opacity = '1';
                }, 300);
            }
        }

        function loadPendingAppointments(silent = false) {
            fetch('../../src/controllers/AppointmentController.php?action=getPendingAppointments')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayPendingAppointments(data.appointments);
                    } else {
                        if (!silent) {
                            showAlert('Failed to load pending appointments: ' + data.message, 'danger');
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    if (!silent) {
                        showAlert('Error loading pending appointments', 'danger');
                    }
                });
        }

        function loadAllAppointments(silent = false) {
            fetch('../../src/controllers/AppointmentController.php?action=getAppointments')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        allAppointments = data.appointments;
                        displayAllAppointments(allAppointments);
                        updateStats();
                    } else {
                        if (!silent) {
                            showAlert('Failed to load appointments: ' + data.message, 'danger');
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    if (!silent) {
                        showAlert('Error loading appointments', 'danger');
                    }
                });
        }

        function updateStats(silent = false) {
            fetch('../../src/controllers/AppointmentController.php?action=getStats')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update sidebar stats
                        const statsPendingElement = document.getElementById('stats-pending-today');
                        if (statsPendingElement) {
                            statsPendingElement.textContent = data.stats.pending_today || 0;
                        }

                        const statsApprovedElement = document.getElementById('stats-approved-today');
                        if (statsApprovedElement) {
                            statsApprovedElement.textContent = data.stats.approved_today || 0;
                        }

                        const statsTotalElement = document.getElementById('stats-total-today');
                        if (statsTotalElement) {
                            statsTotalElement.textContent = data.stats.total_today || 0;
                        }

                        // Update notification count
                        updateNotificationCount();

                        // Update tab badges
                        updateTabBadges();
                    } else {
                        console.error('Failed to load stats:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error loading stats:', error);
                });
        }

        function displayPendingAppointments(appointments) {
            var list = document.getElementById('pending-appointments-list');
            const pendingCountBadge = document.getElementById('pending-count-badge');

            if (pendingCountBadge) {
                pendingCountBadge.textContent = appointments.length;
            }

            if (appointments.length === 0) {
                list.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-calendar-check"></i>
                        <h3>No Pending Appointments</h3>
                        <p>All appointments have been processed. Great work!</p>
                    </div>
                `;
                return;
            }

            var html = '<table class="modern-table">';
            html += '<thead><tr>';
            html += '<th>Patient</th>';
            html += '<th>Date & Time</th>';
            html += '<th>Treatment</th>';
            html += '<th>Priority</th>';
            html += '<th>Notes</th>';
            html += '<th>Actions</th>';
            html += '</tr></thead><tbody>';

            appointments.forEach(function (appt, index) {
                const isToday = appt.appointment_date === new Date().toISOString().split('T')[0];
                const isUrgent = appt.notes && appt.notes.toLowerCase().includes('pain');

                html += '<tr>';

                // Patient Info
                html += '<td data-label="Patient">';
                html += '<div class="patient-info">';
                html += '<div class="patient-avatar">' + (appt.patient_name ? appt.patient_name.charAt(0).toUpperCase() : 'N') + '</div>';
                html += '<div class="patient-details">';
                html += '<h4>' + (appt.patient_name || 'N/A');

                // Add walk-in indicator
                if (appt.patient_type === 'walk_in') {
                    html += ' <span style="background: #3b82f6; color: white; padding: 2px 6px; border-radius: 4px; font-size: 10px; font-weight: 600; margin-left: 4px;">WALK-IN</span>';
                }

                html += '</h4>';
                if (appt.patient_phone) html += '<p><i class="fas fa-phone"></i> ' + appt.patient_phone + '</p>';

                // Show email only for registered patients
                if (appt.patient_type === 'registered' && appt.patient_email) {
                    html += '<p><i class="fas fa-envelope"></i> ' + appt.patient_email + '</p>';
                }

                html += '</div>';
                html += '</div>';
                html += '</td>';

                // Date & Time
                html += '<td data-label="Date & Time">';
                html += '<div style="font-weight: 600; margin-bottom: 4px;">' + appt.appointment_date + '</div>';
                html += '<div style="color: #054A91; font-weight: 600;">' + appt.appointment_time + '</div>';
                if (isToday) html += '<div style="color: #f59e0b; font-size: 12px; font-weight: 600; margin-top: 4px;"><i class="fas fa-star"></i> TODAY</div>';
                if (appt.created_at) html += '<div style="color: #888; font-size: 11px; margin-top: 2px;"><i class="fas fa-calendar-plus"></i> Created: ' + new Date(appt.created_at).toLocaleDateString() + '</div>';
                html += '</td>';

                // Treatment
                html += '<td data-label="Treatment">';
                const treatmentName = appt.treatment_name || 'General Consultation';
                const isReferralAppointment = appt.notes && (appt.notes.includes('REFERRAL') || appt.notes.includes('referred'));

                if (isReferralAppointment) {
                    html += '<div style="position: relative;">';
                    html += '<i class="fas fa-exchange-alt" style="color: #f97316; margin-right: 4px;" title="Referral appointment"></i>';
                    html += treatmentName;
                    if (!appt.treatment_name || !appt.treatment_type_id) {
                        html += '<div style="font-size: 11px; color: #f97316; margin-top: 2px; font-weight: 600;">';
                        html += '<i class="fas fa-info-circle"></i> Treatment details will be copied on approval';
                        html += '</div>';
                    }
                    html += '</div>';
                } else {
                    html += treatmentName;
                }
                html += '</td>';

                // Priority
                html += '<td data-label="Priority">';
                if (isUrgent) {
                    html += '<div class="priority-indicator high"><i class="fas fa-exclamation-triangle"></i> Urgent</div>';
                } else if (isToday) {
                    html += '<div class="priority-indicator normal"><i class="fas fa-clock"></i> Today</div>';
                } else {
                    html += '<div class="priority-indicator normal"><i class="fas fa-check-circle"></i> Normal</div>';
                }
                html += '</td>';

                // Notes
                html += '<td data-label="Notes" style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;">' + (appt.notes || '-') + '</td>';

                // Actions
                html += '<td data-label="Actions">';
                html += '<div class="action-buttons">';
                html += '<button class="btn-modern btn-primary" onclick="editAppointment(' + appt.id + ')" title="Edit appointment details">';
                html += '<i class="fas fa-edit"></i></button>';
                html += '<button class="btn-modern btn-success" onclick="approveAppointment(' + appt.id + ')" title="Approve appointment">';
                html += '<i class="fas fa-check"></i></button>';
                html += '<button class="btn-modern btn-danger" onclick="rejectAppointment(' + appt.id + ')" title="Reject appointment">';
                html += '<i class="fas fa-times"></i></button>';
                html += '<button class="btn-modern btn-warning" onclick="referAppointment(' + appt.id + ')" title="Refer to another branch">';
                html += '<i class="fas fa-exchange-alt"></i></button>';
                html += '<button class="btn-modern btn-info" onclick="viewAppointmentDetails(' + appt.id + ')" title="View details">';
                html += '<i class="fas fa-eye"></i></button>';
                html += '</div>';
                html += '</td>';

                html += '</tr>';
            });

            html += '</tbody></table>';
            list.innerHTML = html;

            // Update notification count
            updateNotificationCount();
        }

        function displayAllAppointments(appointments) {
            var list = document.getElementById('all-appointments-list');
            const allCountBadge = document.getElementById('all-count-badge');

            if (allCountBadge) {
                allCountBadge.textContent = appointments.length;
            }

            if (appointments.length === 0) {
                list.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-calendar-alt"></i>
                        <h3>No Appointments Found</h3>
                        <p>No appointments match your current filters.</p>
                    </div>
                `;
                return;
            }

            var html = '<table class="modern-table">';
            html += '<thead><tr>';
            html += '<th style="cursor: pointer;" onclick="sortAppointments(\'patient_name\')">Patient <i class="fas fa-sort"></i></th>';
            html += '<th style="cursor: pointer;" onclick="sortAppointments(\'appointment_date\')">Date <i class="fas fa-sort"></i></th>';
            html += '<th style="cursor: pointer;" onclick="sortAppointments(\'appointment_time\')">Time <i class="fas fa-sort"></i></th>';
            html += '<th style="cursor: pointer;" onclick="sortAppointments(\'status\')">Status <i class="fas fa-sort"></i></th>';
            html += '<th>Treatment</th>';
            html += '<th>Notes</th>';
            html += '<th>Actions</th>';
            html += '</tr></thead><tbody>';

            // Define status styling
            const statusConfig = {
                'pending': { class: 'pending', icon: 'clock', color: '#f59e0b' },
                'approved': { class: 'approved', icon: 'check-circle', color: '#10b981' },
                'cancelled': { class: 'cancelled', icon: 'times-circle', color: '#ef4444' },
                'completed': { class: 'completed', icon: 'check-double', color: '#3b82f6' },
                'referred': { class: 'referred', icon: 'exchange-alt', color: '#f97316' }
            };

            appointments.forEach(function (appt) {
                const statusInfo = statusConfig[appt.status] || statusConfig['pending'];

                html += '<tr>';

                // Patient Info
                html += '<td data-label="Patient">';
                html += '<div class="patient-info">';
                html += '<div class="patient-avatar">' + (appt.patient_name ? appt.patient_name.charAt(0).toUpperCase() : 'N') + '</div>';
                html += '<div class="patient-details">';
                html += '<h4>' + (appt.patient_name || 'N/A');

                // Add walk-in indicator
                if (appt.patient_type === 'walk_in') {
                    html += ' <span style="background: #3b82f6; color: white; padding: 2px 6px; border-radius: 4px; font-size: 10px; font-weight: 600; margin-left: 4px;">WALK-IN</span>';
                }

                html += '</h4>';
                if (appt.phone) html += '<p><i class="fas fa-phone"></i> ' + appt.phone + '</p>';

                // Show email only for registered patients
                if (appt.patient_type === 'registered' && appt.patient_email) {
                    html += '<p><i class="fas fa-envelope"></i> ' + appt.patient_email + '</p>';
                }

                html += '</div>';
                html += '</div>';
                html += '</td>';

                // Date
                html += '<td data-label="Date">';
                html += '<div style="font-weight: 600;">' + appt.appointment_date + '</div>';
                if (appt.created_at) html += '<div style="color: #888; font-size: 11px; margin-top: 2px;"><i class="fas fa-calendar-plus"></i> Created: ' + new Date(appt.created_at).toLocaleDateString() + '</div>';
                html += '</td>';

                // Time  
                html += '<td data-label="Time" style="color: #054A91; font-weight: 600;">' + appt.appointment_time + '</td>';

                // Status
                html += '<td data-label="Status">';
                html += '<span class="status-badge ' + statusInfo.class + '">';
                html += '<span class="status-dot"></span>';
                html += appt.status.charAt(0).toUpperCase() + appt.status.slice(1);
                html += '</span>';
                html += '</td>';

                // Treatment
                html += '<td data-label="Treatment">';
                const treatmentName = appt.treatment_name || 'General';
                const isReferralAppointment = appt.notes && (appt.notes.includes('REFERRAL') || appt.notes.includes('referred'));

                if (isReferralAppointment) {
                    html += '<div style="position: relative;">';
                    html += '<i class="fas fa-exchange-alt" style="color: #f97316; margin-right: 4px;" title="Referral appointment"></i>';
                    html += treatmentName;
                    if (appt.duration_minutes) {
                        html += '<div style="font-size: 11px; color: #666; margin-top: 2px;">';
                        html += '<i class="fas fa-clock"></i> ' + appt.duration_minutes + ' min';
                        html += '</div>';
                    }
                    html += '</div>';
                } else {
                    html += treatmentName;
                    if (appt.duration_minutes) {
                        html += '<div style="font-size: 11px; color: #666; margin-top: 2px;">';
                        html += '<i class="fas fa-clock"></i> ' + appt.duration_minutes + ' min';
                        html += '</div>';
                    }
                }
                html += '</td>';

                // Notes
                html += '<td data-label="Notes" style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;">' + (appt.notes || '-') + '</td>';

                // Actions
                html += '<td data-label="Actions">';
                html += '<div class="action-buttons">';
                html += '<button class="btn-modern btn-info" onclick="viewAppointmentDetails(' + appt.id + ')" title="View details">';
                html += '<i class="fas fa-eye"></i></button>';

                if (appt.status === 'pending') {
                    html += '<button class="btn-modern btn-primary" onclick="editAppointment(' + appt.id + ')" title="Edit appointment">';
                    html += '<i class="fas fa-edit"></i></button>';
                    html += '<button class="btn-modern btn-success" onclick="approveAppointment(' + appt.id + ')" title="Approve">';
                    html += '<i class="fas fa-check"></i></button>';
                    html += '<button class="btn-modern btn-danger" onclick="rejectAppointment(' + appt.id + ')" title="Reject">';
                    html += '<i class="fas fa-times"></i></button>';
                    html += '<button class="btn-modern btn-warning" onclick="referAppointment(' + appt.id + ')" title="Refer">';
                    html += '<i class="fas fa-exchange-alt"></i></button>';
                }
                // For approved status, staff can only view (no additional actions)

                html += '</div>';
                html += '</td>';

                html += '</tr>';
            });

            html += '</tbody></table>';
            list.innerHTML = html;
        }

        // Edit Appointment Functions
        function editAppointment(appointmentId) {
            // Find the appointment in our data
            const appointment = allAppointments.find(appt => appt.id == appointmentId);
            
            if (!appointment) {
                showAlert('Appointment not found', 'danger');
                return;
            }

            // Only allow editing of pending appointments
            if (appointment.status !== 'pending') {
                showAlert('Only pending appointments can be edited', 'warning');
                return;
            }

            // Populate the edit form
            populateEditForm(appointment);
            
            // Load treatments and available times
            loadTreatmentsForEdit();
            loadAvailableTimesForEdit();
            
            // Show the modal
            document.getElementById('edit-appointment-modal').style.display = 'block';
        }

        function populateEditForm(appointment) {
            document.getElementById('edit-appointment-id').value = appointment.id;
            document.getElementById('edit-patient-name').value = appointment.patient_name || '';
            document.getElementById('edit-appointment-date').value = appointment.appointment_date;
            document.getElementById('edit-notes').value = appointment.notes || '';
            
            // Check if this is a walk-in appointment using appointment_source field
            const isWalkIn = appointment.appointment_source === 'walk_in';
            
            // Handle patient name field based on appointment type
            const patientNameField = document.getElementById('edit-patient-name');
            const patientNameLabel = document.querySelector('label[for="edit-patient-name"]');
            
            if (isWalkIn) {
                // Walk-in appointment - allow name editing
                patientNameField.readOnly = false;
                patientNameField.style.backgroundColor = '';
                patientNameField.style.cursor = '';
                if (patientNameLabel) {
                    patientNameLabel.innerHTML = '<i class="fas fa-user"></i> Patient Name <span style="color: #ef4444;">*</span>';
                }
            } else {
                // Regular appointment - make name field read-only with explanation
                patientNameField.readOnly = true;
                patientNameField.style.backgroundColor = '#f8f9fa';
                patientNameField.style.cursor = 'not-allowed';
                if (patientNameLabel) {
                    patientNameLabel.innerHTML = '<i class="fas fa-user"></i> Patient Name <small style="color: #6b7280;">(Read-only for registered users)</small>';
                }
            }
            
            // Update character counter
            updateEditNotesCounter();
        }

        function loadTreatmentsForEdit() {
            fetch('../../src/controllers/TreatmentController.php?action=getBranchTreatments')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const treatmentSelect = document.getElementById('edit-treatment-type');
                        treatmentSelect.innerHTML = '<option value="">Select treatment</option>';
                        
                        data.treatments.forEach(treatment => {
                            const option = document.createElement('option');
                            option.value = treatment.id;
                            option.textContent = `${treatment.name} - ₱${treatment.price} (${treatment.duration_minutes}min)`;
                            treatmentSelect.appendChild(option);
                        });
                        
                        // Set the current treatment if available
                        const appointment = allAppointments.find(appt => appt.id == document.getElementById('edit-appointment-id').value);
                        if (appointment && appointment.treatment_type_id) {
                            treatmentSelect.value = appointment.treatment_type_id;
                        }
                    } else {
                        showEditAlert('Failed to load treatments: ' + data.message, 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error loading treatments:', error);
                    showEditAlert('Error loading treatments', 'danger');
                });
        }

        function loadAvailableTimesForEdit() {
            const dateInput = document.getElementById('edit-appointment-date');
            const timeSelect = document.getElementById('edit-appointment-time');
            
            if (!dateInput.value) {
                timeSelect.innerHTML = '<option value="">Select time</option>';
                return;
            }

            // Try to get time slots from API first - use absolute path from root
            fetch(`../api/time-slots.php?action=get_time_slots&date=${dateInput.value}&branch_id=<?php echo getSessionBranchId(); ?>&duration=60`)
                .then(response => response.json())
                .then(data => {
                    timeSelect.innerHTML = '<option value="">Select time</option>';
                    
                    if (data.success && data.slots) {
                        data.slots.forEach(slot => {
                            const option = document.createElement('option');
                            option.value = slot.time;
                            option.textContent = slot.display + (slot.available ? '' : ' (Unavailable)');
                            option.disabled = !slot.available;
                            timeSelect.appendChild(option);
                        });
                        
                        // Set the current time if available
                        const appointment = allAppointments.find(appt => appt.id == document.getElementById('edit-appointment-id').value);
                        if (appointment && appointment.appointment_time) {
                            timeSelect.value = appointment.appointment_time;
                        }
                    } else {
                        // Fallback to basic time slots if API fails
                        loadBasicTimeSlots();
                    }
                })
                .catch(error => {
                    console.error('Error loading time slots:', error);
                    // Fallback to basic time slots on error
                    loadBasicTimeSlots();
                });
                
            function loadBasicTimeSlots() {
                // Basic time slots as fallback
                const basicSlots = [
                    { time: '09:00', display: '🌅 9:00 AM' },
                    { time: '10:00', display: '🌞 10:00 AM' },
                    { time: '11:00', display: '🌞 11:00 AM' },
                    { time: '13:00', display: '🌇 1:00 PM' },
                    { time: '14:00', display: '🌇 2:00 PM' },
                    { time: '15:00', display: '🌆 3:00 PM' },
                    { time: '16:00', display: '🌆 4:00 PM' }
                ];
                
                basicSlots.forEach(slot => {
                    const option = document.createElement('option');
                    option.value = slot.time;
                    option.textContent = slot.display;
                    timeSelect.appendChild(option);
                });
                
                // Set the current time if available
                const appointment = allAppointments.find(appt => appt.id == document.getElementById('edit-appointment-id').value);
                if (appointment && appointment.appointment_time) {
                    timeSelect.value = appointment.appointment_time;
                }
                
                showEditAlert('Using basic time slots. Advanced availability checking unavailable.', 'info');
            }
        }

        function updateEditNotesCounter() {
            const notesTextarea = document.getElementById('edit-notes');
            const counter = document.getElementById('edit-notes-count');
            if (notesTextarea && counter) {
                counter.textContent = notesTextarea.value.length;
                
                // Change color based on character count
                if (notesTextarea.value.length > 400) {
                    counter.style.color = '#ef4444';
                } else if (notesTextarea.value.length > 300) {
                    counter.style.color = '#f59e0b';
                } else {
                    counter.style.color = '#64748b';
                }
            }
        }

        function validateEditForm() {
            const patientName = document.getElementById('edit-patient-name').value.trim();
            const appointmentDate = document.getElementById('edit-appointment-date').value;
            const appointmentTime = document.getElementById('edit-appointment-time').value;
            const treatmentTypeId = document.getElementById('edit-treatment-type').value;
            const patientNameField = document.getElementById('edit-patient-name');
            
            // Reset error displays
            document.getElementById('edit-patient-name-error').style.display = 'none';
            
            // Only validate patient name if field is enabled (walk-in appointments)
            if (!patientNameField.disabled) {
                if (!patientName) {
                    showEditAlert('Please enter the patient name', 'danger');
                    document.getElementById('edit-patient-name-error').textContent = 'Patient name is required';
                    document.getElementById('edit-patient-name-error').style.display = 'block';
                    return false;
                }
                
                if (patientName.length < 2) {
                    showEditAlert('Patient name must be at least 2 characters long', 'danger');
                    document.getElementById('edit-patient-name-error').textContent = 'Name must be at least 2 characters';
                    document.getElementById('edit-patient-name-error').style.display = 'block';
                    return false;
                }
            }
            
            if (!appointmentDate) {
                showEditAlert('Please select an appointment date', 'danger');
                return false;
            }
            
            // Check if date is not in the past
            const selectedDate = new Date(appointmentDate);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            if (selectedDate < today) {
                showEditAlert('Appointment date cannot be in the past', 'danger');
                return false;
            }
            
            if (!appointmentTime) {
                showEditAlert('Please select an appointment time', 'danger');
                return false;
            }
            
            if (!treatmentTypeId) {
                showEditAlert('Please select a treatment type', 'danger');
                return false;
            }
            
            return true;
        }

        function showEditAlert(message, type) {
            const alertDiv = document.getElementById('edit-alert');
            const alertIcon = document.getElementById('edit-alert-icon');
            const alertMessage = document.getElementById('edit-alert-message');
            
            alertDiv.className = `modal-alert ${type}`;
            alertDiv.style.display = 'block';
            
            const icons = {
                'success': 'fas fa-check-circle',
                'danger': 'fas fa-exclamation-triangle',
                'warning': 'fas fa-exclamation-triangle',
                'info': 'fas fa-info-circle'
            };
            
            alertIcon.className = icons[type] || 'fas fa-info-circle';
            alertMessage.textContent = message;
            
            // Auto-hide success messages
            if (type === 'success') {
                setTimeout(() => {
                    alertDiv.style.display = 'none';
                }, 3000);
            }
        }

        function closeEditModal() {
            document.getElementById('edit-appointment-modal').style.display = 'none';
            document.getElementById('edit-alert').style.display = 'none';
            document.getElementById('edit-patient-name-error').style.display = 'none';
            document.getElementById('editAppointmentForm').reset();
        }

        function approveAppointment(id) {
            // Find the appointment in our data to check if it's a referral
            const appointment = allAppointments.find(appt => appt.id == id);

            let confirmMessage = 'Are you sure you want to approve this appointment?';

            // Check if this might be a referral appointment
            if (appointment && (appointment.notes && appointment.notes.includes('REFERRAL') ||
                appointment.notes && appointment.notes.includes('referred') ||
                !appointment.treatment_name ||
                !appointment.treatment_type_id)) {
                confirmMessage = '🔄 This appears to be a referral appointment.\n\n' +
                    '✅ Approving will:\n' +
                    '• Copy treatment details from the original appointment\n' +
                    '• Update the patient_referrals table status to "accepted"\n' +
                    '• Add treatment information to this appointment\n' +
                    '• Notify the patient of approval\n\n' +
                    'Proceed with approval?';
            }

            if (!confirm(confirmMessage)) {
                return;
            }

            // Show appropriate loading message
            if (appointment && (!appointment.treatment_name || !appointment.treatment_type_id)) {
                showAlert('🔄 Approving referral appointment and copying treatment details...', 'info');
            } else {
                showAlert('🔄 Approving appointment...', 'info');
            }

            var formData = new FormData();
            formData.append('appointment_id', id);
            formData.append('status', 'approved');
            
            // Add appointment type to fix branch validation issue
            if (appointment && appointment.patient_type === 'walk_in') {
                formData.append('type', 'walk-in');
            } else {
                formData.append('type', 'regular');
            }

            fetch('../../src/controllers/AppointmentController.php?action=updateStatus', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show specific success message for referral appointments
                        if (appointment && (!appointment.treatment_name || !appointment.treatment_type_id)) {
                            showAlert('🎉 Referral appointment approved successfully!\n\n' +
                                '✅ Treatment details copied from original appointment\n' +
                                '✅ Referral status updated to "accepted"\n' +
                                '✅ Patient will be notified of approval\n' +
                                '📋 Appointment now includes complete treatment information', 'success');
                        } else {
                            showAlert(data.message, 'success');
                        }

                        setTimeout(() => {
                            loadPendingAppointments();
                            loadAllAppointments();
                        }, 1500);
                    } else {
                        showAlert(data.message, 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('An error occurred. Please try again.', 'danger');
                });
        }

        function rejectAppointment(id) {
            const reason = prompt('Please enter a reason for rejection:');
            if (!reason) return;

            // Find the appointment in our data to check if it's walk-in
            const appointment = allAppointments.find(appt => appt.id == id);

            var formData = new FormData();
            formData.append('appointment_id', id);
            formData.append('status', 'cancelled');
            formData.append('reason', reason);
            
            // Add appointment type to fix branch validation issue
            if (appointment && appointment.patient_type === 'walk_in') {
                formData.append('type', 'walk-in');
            } else {
                formData.append('type', 'regular');
            }

            fetch('../../src/controllers/AppointmentController.php?action=updateStatus', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    showAlert(data.message, data.success ? 'success' : 'danger');
                    if (data.success) {
                        setTimeout(() => {
                            loadPendingAppointments();
                            loadAllAppointments();
                        }, 1500);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('An error occurred. Please try again.', 'danger');
                });
        }

        function referAppointment(appointmentId) {
            // Set the appointment ID for referral creation
            document.getElementById('referral-appointment-id').value = appointmentId;

            // Scroll to and show the referral management section
            document.getElementById('referrals').scrollIntoView({ behavior: 'smooth' });

            // Show a helpful message
            showAlert('📝 Please fill out the referral form below', 'info');
        }

        // Filter functions
        function filterByToday() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('date-filter').value = today;
            loadAppointments();
        }

        function filterByWeek() {
            const today = new Date();
            const weekEnd = new Date(today.getTime() + 7 * 24 * 60 * 60 * 1000);
            document.getElementById('date-filter').value = weekEnd.toISOString().split('T')[0];
            loadAppointments();
        }

        function resetFilters() {
            document.getElementById('date-filter').value = '';
            document.getElementById('status-filter').value = '';
            document.getElementById('patient-search').value = '';
            loadAppointments();
        }

        // Sorting function
        let currentSort = { field: null, direction: 'asc' };
        function sortAppointments(field) {
            if (currentSort.field === field) {
                currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
            } else {
                currentSort.field = field;
                currentSort.direction = 'asc';
            }
            loadAppointments();
        }

        function markCompleted(appointmentId) {
            // Find the appointment to check if it's a referral
            const appointment = allAppointments.find(appt => appt.id == appointmentId);

            if (!appointment) {
                showAlert('Appointment not found', 'danger');
                return;
            }

            // Enhanced referral detection
            const isReferralAppointment = appointment.status === 'referred' ||
                appointment.referral_status ||
                appointment.referral_info ||
                appointment.referral_id ||
                appointment.referred_from_branch ||
                appointment.referred_to_branch ||
                (appointment.notes && (appointment.notes.includes('REFERRAL') || appointment.notes.includes('referred'))) ||
                (!appointment.treatment_name || !appointment.treatment_type_id);

            let confirmMessage = 'Mark this appointment as completed?';
            if (isReferralAppointment) {
                if (!appointment.treatment_name || !appointment.treatment_type_id) {
                    confirmMessage = '🔄 Complete this referral appointment?\n\n' +
                        '⚠️ This appointment appears to be missing treatment details.\n\n' +
                        '✅ Completing will:\n' +
                        '• Copy treatment details from the original appointment\n' +
                        '• Update the referral status to "completed"\n' +
                        '• Record completion timestamp and staff information\n' +
                        '• Notify the patient of completion\n\n' +
                        'Proceed with completion?';
                } else {
                    confirmMessage = '✅ Mark this referral appointment as completed?\n\n' +
                        '⚠️ This will also update the referral status to "completed" in the patient_referrals table.\n\n' +
                        '📱 The patient will see the updated status with completion details in their dashboard.\n\n' +
                        '🕒 Completion timestamp and staff information will be recorded.';
                }
            }

            if (confirm(confirmMessage)) {
                updateAppointmentStatus(appointmentId, 'completed', isReferralAppointment);
            }
        }

        function updateAppointmentStatus(appointmentId, status, isReferralAppointment = false) {
            // Find the appointment in our data to check if it's walk-in
            const appointment = allAppointments.find(appt => appt.id == appointmentId);
            
            var formData = new FormData();
            formData.append('appointment_id', appointmentId);
            formData.append('status', status);
            
            // Add appointment type to fix branch validation issue
            if (appointment && appointment.patient_type === 'walk_in') {
                formData.append('type', 'walk-in');
            } else {
                formData.append('type', 'regular');
            }

            // If this is a referral appointment being completed, also update referral status
            if (isReferralAppointment && status === 'completed') {
                formData.append('update_referral_status', 'true');
                // Add additional information for referral completion
                formData.append('completion_source', 'staff_dashboard');
                formData.append('completion_notes', 'Treatment completed successfully at referred branch');
            }

            // Show loading state with specific messages
            if (isReferralAppointment && status === 'completed') {
                const appointment = allAppointments.find(appt => appt.id == appointmentId);
                if (appointment && (!appointment.treatment_name || !appointment.treatment_type_id)) {
                    showAlert('🔄 Completing referral appointment, copying treatment details, and updating patient_referrals table...', 'info');
                } else {
                    showAlert('🔄 Completing referral appointment and updating patient_referrals table...', 'info');
                }
            } else if (isReferralAppointment && status === 'approved') {
                const appointment = allAppointments.find(appt => appt.id == appointmentId);
                if (appointment && (!appointment.treatment_name || !appointment.treatment_type_id)) {
                    showAlert('🔄 Approving referral appointment and copying treatment details from original appointment...', 'info');
                } else {
                    showAlert('🔄 Approving referral appointment...', 'info');
                }
            } else {
                showAlert('🔄 Updating appointment status...', 'info');
            }

            fetch('../../src/controllers/AppointmentController.php?action=updateStatus', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        loadPendingAppointments();
                        loadAllAppointments();

                        if (isReferralAppointment && status === 'completed') {
                            const appointment = allAppointments.find(appt => appt.id == appointmentId);
                            if (appointment && (!appointment.treatment_name || !appointment.treatment_type_id)) {
                                showAlert('🎉 Referral appointment completed successfully!\n\n' +
                                    '✅ Treatment details copied from original appointment\n' +
                                    '✅ Patient appointment status updated to completed\n' +
                                    '✅ Patient_referrals table updated with completion details\n' +
                                    '✅ Completion timestamp and staff ID recorded\n' +
                                    '📱 Patient notified of completion with full treatment information', 'success');
                            } else {
                                showAlert('🎉 Referral appointment completed successfully!\n\n' +
                                    '✅ Patient appointment status updated\n' +
                                    '✅ Patient_referrals table updated with completion details\n' +
                                    '✅ Completion timestamp and staff ID recorded\n' +
                                    '📱 Patient notified of completion', 'success');
                            }

                            // Also update referral data to reflect the completion
                            setTimeout(() => {
                                loadReferralData();
                            }, 1000);
                        } else if (isReferralAppointment && status === 'approved') {
                            const appointment = allAppointments.find(appt => appt.id == appointmentId);
                            if (appointment && (!appointment.treatment_name || !appointment.treatment_type_id)) {
                                showAlert('🎉 Referral appointment approved successfully!\n\n' +
                                    '✅ Treatment details copied from original appointment\n' +
                                    '✅ Referral status updated to "accepted"\n' +
                                    '✅ Patient notified of approval\n' +
                                    '📋 Appointment now includes complete treatment information', 'success');
                            } else {
                                showAlert(data.message, 'success');
                            }
                        } else {
                            showAlert('Appointment status updated successfully!', 'success');
                        }
                    } else {
                        if (isReferralAppointment && status === 'completed') {
                            showAlert('❌ Failed to complete referral appointment: ' + (data.message || 'Unknown error occurred'), 'danger');
                        } else {
                            showAlert(data.message || 'Failed to update appointment status', 'danger');
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    if (isReferralAppointment && status === 'completed') {
                        showAlert('❌ Network error while completing referral appointment. Please check your connection and try again.', 'danger');
                    } else {
                        showAlert('An error occurred while updating appointment status', 'danger');
                    }
                });
        }

        function viewAppointmentDetails(appointmentId) {
            const appointment = allAppointments.find(appt => appt.id == appointmentId);
            if (appointment) {
                showAppointmentModal(appointment);
            } else {
                showAlert('Appointment not found', 'danger');
            }
        }

        function showAppointmentModal(appointment) {
            document.getElementById('modal-patient-name').textContent = appointment.patient_name || 'N/A';
            document.getElementById('modal-appointment-date').textContent = appointment.appointment_date;
            document.getElementById('modal-appointment-time').textContent = appointment.appointment_time;
            
            // Status with badge
            const statusEl = document.getElementById('modal-status');
            const status = appointment.status.toLowerCase();
            const statusText = appointment.status.charAt(0).toUpperCase() + appointment.status.slice(1);
            
            let statusIcon = 'fa-info-circle';
            if (status === 'pending') statusIcon = 'fa-clock';
            else if (status === 'approved') statusIcon = 'fa-check-circle';
            else if (status === 'completed') statusIcon = 'fa-check-double';
            else if (status === 'cancelled') statusIcon = 'fa-times-circle';
            else if (status === 'referred') statusIcon = 'fa-exchange-alt';
            
            statusEl.innerHTML = `<span class="status-badge status-${status}"><i class="fas ${statusIcon}"></i> ${statusText}</span>`;
            
            document.getElementById('modal-notes').textContent = appointment.notes || 'No notes provided';
            document.getElementById('modal-created').textContent = appointment.created_at ? new Date(appointment.created_at).toLocaleString() : 'N/A';

            document.getElementById('appointment-modal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('appointment-modal').style.display = 'none';
        }

        function closeReferModal() {
            document.getElementById('refer-appointment-modal').style.display = 'none';
            document.getElementById('createReferralForm').reset();
            hideReferAlert(); // Hide any existing alerts when closing modal

            // Reset treatment field label to default
            const treatmentLabel = document.querySelector('label[for="referral-treatment"]');
            if (treatmentLabel) {
                treatmentLabel.innerHTML = '🦷 Treatment Type <span style="color: #dc3545;">*</span>';
            }

            // Reset treatment dropdown to default state
            const treatmentSelect = document.getElementById('referral-treatment');
            if (treatmentSelect) {
                treatmentSelect.innerHTML = '<option value="">-- Select Branch First --</option>';
                treatmentSelect.disabled = true;
            }
        }

        // Function to show alerts in the refer modal
        function showReferAlert(type, message) {
            const alertDiv = document.getElementById('refer-alert');
            const iconSpan = document.getElementById('refer-alert-icon');
            const messageSpan = document.getElementById('refer-alert-message');

            // Configure based on alert type
            if (type === 'success') {
                alertDiv.style.background = '#d1f2eb';
                alertDiv.style.borderLeft = '4px solid #28a745';
                alertDiv.style.color = '#155724';
                iconSpan.innerHTML = '✅';
            } else if (type === 'error') {
                alertDiv.style.background = '#f8d7da';
                alertDiv.style.borderLeft = '4px solid #dc3545';
                alertDiv.style.color = '#721c24';
                iconSpan.innerHTML = '❌';
            } else if (type === 'warning') {
                alertDiv.style.background = '#fff3cd';
                alertDiv.style.borderLeft = '4px solid #ffc107';
                alertDiv.style.color = '#856404';
                iconSpan.innerHTML = '⚠️';
            } else if (type === 'info') {
                alertDiv.style.background = '#d1ecf1';
                alertDiv.style.borderLeft = '4px solid #17a2b8';
                alertDiv.style.color = '#0c5460';
                iconSpan.innerHTML = 'ℹ️';
            }

            messageSpan.textContent = message;
            alertDiv.style.display = 'block';

            // Auto-hide success messages after 4 seconds
            if (type === 'success') {
                setTimeout(() => {
                    hideReferAlert();
                }, 4000);
            }
        }

        // Function to hide alerts in the refer modal
        function hideReferAlert() {
            const alertDiv = document.getElementById('refer-alert');
            alertDiv.style.display = 'none';
        }

        function referAppointment(appointmentId) {
            const appointment = allAppointments.find(appt => appt.id == appointmentId);
            if (!appointment) {
                showAlert('Appointment not found', 'danger');
                return;
            }

            // Clear any existing alerts in the modal
            hideReferAlert();

            // Populate appointment details
            const detailsDiv = document.getElementById('refer-appointment-details');
            detailsDiv.innerHTML = `
                <h5>Appointment Information</h5>
                <p><strong>Patient:</strong> ${appointment.patient_name}</p>
                <p><strong>Date:</strong> ${appointment.appointment_date}</p>
                <p><strong>Time:</strong> ${appointment.appointment_time}</p>
                <p><strong>Treatment:</strong> ${appointment.treatment_name || 'Not specified'}</p>
                <p><strong>Current Status:</strong> <span style="color: #007bff;">${appointment.status.toUpperCase()}</span></p>
                <p><strong>Notes:</strong> ${appointment.notes || 'None'}</p>
            `;

            // Load available branches for referral
            loadAvailableBranchesForRefer();

            // Update treatment field label for appointment referrals
            const treatmentLabel = document.querySelector('label[for="referral-treatment"]');
            if (treatmentLabel) {
                treatmentLabel.innerHTML = '🦷 Treatment Type <span style="color: #666; font-weight: normal;">(Optional for appointment referrals)</span>';
            }

            // Show info about treatment selection for appointment referrals
            setTimeout(() => {
                showReferAlert('info', 'For appointment referrals, you can optionally select a specific treatment at the target branch. If no treatment is selected, the referral will be for general consultation.');
            }, 1000);

            // Store appointment ID for form submission
            const referralForm = document.getElementById('createReferralForm');
            if (referralForm) {
                referralForm.dataset.appointmentId = appointmentId;
            }

            // Store appointment ID in hidden field
            const appointmentIdField = document.getElementById('referral-appointment-id');
            if (appointmentIdField) {
                appointmentIdField.value = appointmentId;
            }

            // Show modal
            document.getElementById('refer-appointment-modal').style.display = 'block';
        }

        function loadAvailableBranchesForRefer() {
            fetch('../../src/controllers/ReferralController.php?action=getAvailableBranches')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const select = document.getElementById('referral-branch');
                        select.innerHTML = '<option value="">-- Select Branch --</option>';
                        data.branches.forEach(branch => {
                            const option = document.createElement('option');
                            option.value = branch.id;
                            option.textContent = branch.name;
                            select.appendChild(option);
                        });

                        // Remove any existing event listeners to prevent duplicates
                        const newSelect = select.cloneNode(true);
                        select.parentNode.replaceChild(newSelect, select);

                        // Add event listener for branch change to load treatments dynamically
                        newSelect.addEventListener('change', function () {
                            const treatmentSelect = document.getElementById('referral-treatment');
                            if (this.value) {
                                // Show loading state
                                treatmentSelect.innerHTML = '<option value="">Loading treatments...</option>';
                                treatmentSelect.disabled = true;

                                // Load treatments for selected branch
                                loadBranchTreatmentsForRefer(this.value);
                            } else {
                                // Reset treatment dropdown
                                treatmentSelect.innerHTML = '<option value="">-- Select Branch First --</option>';
                                treatmentSelect.disabled = true;
                            }
                        });

                        // Initially disable treatment selection until branch is chosen
                        const treatmentSelect = document.getElementById('referral-treatment');
                        treatmentSelect.innerHTML = '<option value="">-- Select Branch First --</option>';
                        treatmentSelect.disabled = true;
                    }
                })
                .catch(error => {
                    console.error('Error loading branches:', error);
                    showReferAlert('error', 'Failed to load available branches. Please try again.');
                });
        }

        function loadBranchTreatmentsForRefer(branchId) {
            fetch(`../../src/controllers/ReferralController.php?action=getBranchTreatments&branch_id=${branchId}`)
                .then(response => response.json())
                .then(data => {
                    const select = document.getElementById('referral-treatment');

                    if (data.success && data.treatments && data.treatments.length > 0) {
                        select.innerHTML = '<option value="">-- Select Treatment --</option>';
                        data.treatments.forEach(treatment => {
                            const option = document.createElement('option');
                            option.value = treatment.id;
                            option.textContent = `${treatment.name} (${treatment.duration_minutes} min) - ₱${parseFloat(treatment.price).toLocaleString()}`;
                            select.appendChild(option);
                        });
                        select.disabled = false;

                        // Show success message
                        showReferAlert('info', `Found ${data.treatments.length} available treatments at the selected branch.`);
                    } else {
                        select.innerHTML = '<option value="">No treatments available at this branch</option>';
                        select.disabled = true;

                        // Show warning message
                        showReferAlert('warning', 'No treatments are available at the selected branch. Please choose a different branch.');
                    }
                })
                .catch(error => {
                    console.error('Error loading treatments:', error);
                    const select = document.getElementById('referral-treatment');
                    select.innerHTML = '<option value="">Error loading treatments</option>';
                    select.disabled = true;

                    showReferAlert('error', 'Failed to load treatments for the selected branch. Please try again.');
                });
        }

        // Form validation and enhancement functions
        function initializeReferralFormValidation() {
            // Character counter for reason textarea
            const reasonTextarea = document.getElementById('referral-reason');
            const charCount = document.getElementById('current-count');

            if (reasonTextarea && charCount) {
                reasonTextarea.addEventListener('input', function () {
                    const currentLength = this.value.length;
                    charCount.textContent = currentLength;

                    // Color coding based on length
                    if (currentLength > 500) {
                        charCount.style.color = '#dc3545';
                        this.style.borderColor = '#dc3545';
                    } else if (currentLength > 400) {
                        charCount.style.color = '#ffc107';
                        this.style.borderColor = '#ffc107';
                    } else {
                        charCount.style.color = '#6b7280';
                        this.style.borderColor = '#e5e7eb';
                    }

                    // Real-time validation
                    validateField('referral-reason');
                });
            }

            // Add focus/blur effects for all form fields
            const formFields = ['referral-branch', 'referral-priority', 'referral-reason'];
            formFields.forEach(fieldId => {
                const field = document.getElementById(fieldId);

                if (field) {
                    field.addEventListener('focus', function () {
                        this.style.borderColor = '#3b82f6';
                        this.style.boxShadow = '0 0 0 3px rgba(59, 130, 246, 0.1)';
                    });

                    field.addEventListener('blur', function () {
                        this.style.borderColor = '#e5e7eb';
                        this.style.boxShadow = '0 1px 3px rgba(0,0,0,0.1)';
                        validateField(fieldId);
                    });

                    field.addEventListener('change', function () {
                        validateField(fieldId);
                    });
                }
            });
        }

        function validateField(fieldId) {
            const field = document.getElementById(fieldId);
            const errorDiv = document.getElementById(fieldId + '-error');
            let isValid = true;
            let errorMessage = '';

            switch (fieldId) {
                case 'referral-branch':
                    if (!field.value) {
                        isValid = false;
                        errorMessage = '⚠️ Please select a target branch for the referral';
                    }
                    break;

                case 'refer-priority':
                    if (!field.value) {
                        isValid = false;
                        errorMessage = '⚠️ Please select a priority level for this referral';
                    }
                    break;

                case 'referral-reason':
                    const reason = field.value.trim();
                    if (!reason) {
                        isValid = false;
                        errorMessage = '⚠️ Please provide a reason for the referral';
                    } else if (reason.length < 10) {
                        isValid = false;
                        errorMessage = '⚠️ Reason must be at least 10 characters long';
                    } else if (reason.length > 500) {
                        isValid = false;
                        errorMessage = '⚠️ Reason cannot exceed 500 characters';
                    }
                    break;
            }

            // Update field appearance and error message
            if (isValid) {
                field.style.borderColor = '#10b981';
                field.style.backgroundColor = '#f0fdf4';
                errorDiv.style.display = 'none';
            } else {
                field.style.borderColor = '#dc3545';
                field.style.backgroundColor = '#fef2f2';
                errorDiv.textContent = errorMessage;
                errorDiv.style.display = 'block';
            }

            return isValid;
        }

        function validateReferralForm() {
            const fields = ['referral-branch', 'referral-priority', 'referral-reason'];
            let isFormValid = true;

            fields.forEach(fieldId => {
                if (!validateField(fieldId)) {
                    isFormValid = false;
                }
            });

            // Show general error if form is invalid
            if (!isFormValid) {
                showReferAlert('error', 'Please correct the errors in the form before submitting.');
            }

            return isFormValid;
        }

        // Filter and action functions
        function showAllPending() {
            document.getElementById('filter-status').value = 'pending';
            filterAppointments();
        }

        function showTodayOnly() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('filter-date').value = today;
            filterAppointments();
        }

        function showByStatus(status) {
            document.getElementById('filter-status').value = status;
            filterAppointments();
        }

        function filterByToday() {
            showTodayOnly();
        }

        function openReferralManagement() {
            showTab('referrals');
        }

        // Ensure global availability
        window.showAllPending = showAllPending;
        window.showTodayOnly = showTodayOnly;
        window.showByStatus = showByStatus;
        window.filterByToday = filterByToday;
        window.openReferralManagement = openReferralManagement;

        function filterAppointments() {
            const dateFilter = document.getElementById('filter-date').value;
            const statusFilter = document.getElementById('filter-status').value;
            const searchFilter = document.getElementById('search-patient').value.toLowerCase();

            let filtered = allAppointments;

            if (dateFilter) {
                filtered = filtered.filter(appt => appt.appointment_date === dateFilter);
            }

            if (statusFilter) {
                filtered = filtered.filter(appt => appt.status === statusFilter);
            }

            if (searchFilter) {
                filtered = filtered.filter(appt =>
                    (appt.patient_name && appt.patient_name.toLowerCase().includes(searchFilter))
                );
            }

            displayAllAppointments(filtered);
        }

        function exportAppointments() {
            exportToCSV();
        }

        // Print function
        function printAppointments() {
            window.print();
        }

        // =================== REFERRAL MANAGEMENT FUNCTIONS ===================

        function loadReferralData(silent = false) {
            // Only load data needed for appointment referrals and referral management
            loadIncomingReferrals(silent);
            loadSentReferrals(silent);
            updateReferralStats(silent);
        }

        function loadBranchPatients() {
            // Check if referral-patient element exists (for standalone referral form)
            const patientSelect = document.getElementById('referral-patient');
            if (!patientSelect) {
                // Element doesn't exist, this is for appointment referrals only
                return;
            }

            fetch('../../src/controllers/ReferralController.php?action=getBranchPatients')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        patientSelect.innerHTML = '<option value="">-- Select Patient --</option>';
                        data.patients.forEach(patient => {
                            const option = document.createElement('option');
                            option.value = patient.id;
                            option.textContent = `${patient.name} (${patient.email})`;
                            patientSelect.appendChild(option);
                        });
                    }
                })
                .catch(error => console.error('Error loading patients:', error));
        }

        function loadAvailableBranches() {
            // Check if referral-branch element exists
            const select = document.getElementById('referral-branch');
            if (!select) {
                // Element doesn't exist, skip loading
                return;
            }

            fetch('../../src/controllers/ReferralController.php?action=getAvailableBranches')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        select.innerHTML = '<option value="">-- Select Branch --</option>';
                        data.branches.forEach(branch => {
                            const option = document.createElement('option');
                            option.value = branch.id;
                            option.textContent = branch.name;
                            select.appendChild(option);
                        });

                        // Only add event listener if it doesn't already exist
                        if (!select.hasAttribute('data-listener-added')) {
                            select.addEventListener('change', function () {
                                if (this.value) {
                                    loadBranchTreatments(this.value);
                                } else {
                                    const treatmentSelect = document.getElementById('referral-treatment');
                                    if (treatmentSelect) {
                                        treatmentSelect.innerHTML = '<option value="">-- Select Treatment --</option>';
                                    }
                                }
                            });
                            select.setAttribute('data-listener-added', 'true');
                        }
                    }
                })
                .catch(error => console.error('Error loading branches:', error));
        }

        function loadBranchTreatments(branchId) {
            // Check if referral-treatment element exists
            const select = document.getElementById('referral-treatment');
            if (!select) {
                // Element doesn't exist, skip loading
                return;
            }

            fetch(`../../src/controllers/ReferralController.php?action=getBranchTreatments&branch_id=${branchId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        select.innerHTML = '<option value="">-- Select Treatment --</option>';
                        data.treatments.forEach(treatment => {
                            const option = document.createElement('option');
                            option.value = treatment.id;
                            option.textContent = `${treatment.name} (${treatment.duration_minutes} min) - ₱${parseFloat(treatment.price).toLocaleString()}`;
                            select.appendChild(option);
                        });
                    }
                })
                .catch(error => console.error('Error loading treatments:', error));
        }

        function loadIncomingReferrals(silent = false) {
            fetch('../../src/controllers/ReferralController.php?action=getPendingReferrals')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayIncomingReferrals(data.referrals);
                        const referralCount = data.referrals.length;

                        // Update both incoming badge and main referrals tab badge
                        document.getElementById('incoming-badge').textContent = referralCount;
                        const mainReferralsBadge = document.getElementById('referrals-count-badge');
                        if (mainReferralsBadge) {
                            mainReferralsBadge.textContent = referralCount;
                        }
                    }
                })
                .catch(error => {
                    console.error('Error loading incoming referrals:', error);
                    // Silent mode: only log errors, don't show user alerts for automatic refreshes
                });
        }

        function loadSentReferrals(silent = false) {
            fetch('../../src/controllers/ReferralController.php?action=getSentReferrals')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displaySentReferrals(data.referrals);
                        document.getElementById('sent-badge').textContent = data.referrals.length;
                    }
                })
                .catch(error => {
                    console.error('Error loading sent referrals:', error);
                    // Silent mode: only log errors, don't show user alerts for automatic refreshes
                });
        }

        function updateReferralStats(silent = false) {
            fetch('../../src/controllers/ReferralController.php?action=getPendingReferrals')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const pendingCount = data.referrals ? data.referrals.length : 0;

                        // Update Quick Stats pending referrals
                        const pendingReferralsElement = document.getElementById('stats-pending-referrals');
                        if (pendingReferralsElement) {
                            pendingReferralsElement.textContent = pendingCount;
                        }

                        // Update main referrals count
                        const referralsCountElement = document.getElementById('referrals-count');
                        if (referralsCountElement) {
                            referralsCountElement.textContent = pendingCount;
                        }

                        // Update incoming badge in tabs
                        const incomingBadge = document.getElementById('incoming-badge');
                        if (incomingBadge) {
                            incomingBadge.textContent = pendingCount;
                        }
                    }
                })
                .catch(error => {
                    console.error('Error loading referral stats:', error);
                    // Silent mode: only log errors, don't show user alerts for automatic refreshes
                });
        }

        function showReferralTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.referral-tab-content').forEach(tab => {
                if (tab) tab.style.display = 'none';
            });

            // Remove active class and reset styles from all referral tabs
            document.querySelectorAll('#incoming-tab, #sent-tab').forEach(tab => {
                if (tab) {
                    tab.classList.remove('active');
                    tab.style.background = '';
                    tab.style.color = '';
                    tab.style.fontWeight = '';
                    tab.style.boxShadow = '';
                }
            });

            // Show selected tab content
            const selectedTabContent = document.getElementById(tabName + '-referrals-tab');
            if (selectedTabContent) {
                selectedTabContent.style.display = 'block';
            }

            // Activate selected tab with modern styling
            const activeTab = document.getElementById(tabName + '-tab');
            if (activeTab) {
                activeTab.classList.add('active');
                activeTab.style.background = 'linear-gradient(135deg, #ff6b35, #e85d1b)';
                activeTab.style.color = 'white';
                activeTab.style.fontWeight = '600';
                activeTab.style.boxShadow = '0 4px 12px rgba(255, 107, 53, 0.3)';
            }
        }

        function displayIncomingReferrals(referrals) {
            const container = document.getElementById('incoming-referrals-list');
            const incomingBadge = document.getElementById('incoming-badge');

            if (incomingBadge) {
                incomingBadge.textContent = referrals.length;
            }

            if (referrals.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h3>No Patient-Approved Referrals</h3>
                        <p>Only referrals approved by patients will appear here for your review.</p>
                        <small style="color: #6b7280; margin-top: 10px; display: block;">
                            <i class="fas fa-info-circle"></i> New workflow: Patient approves → You approve → Appointment scheduled
                        </small>
                    </div>
                `;
                return;
            }

            let html = '<table class="modern-table">';
            html += '<thead><tr>';
            html += '<th>Patient</th>';
            html += '<th>From Branch</th>';
            html += '<th>Treatment</th>';
            html += '<th>Priority</th>';
            html += '<th>Patient Response</th>';
            html += '<th>Approved Date</th>';
            html += '<th>Actions</th>';
            html += '</tr></thead><tbody>';

            const priorityConfig = {
                'high': { class: 'high', icon: 'exclamation-triangle', color: '#dc2626' },
                'normal': { class: 'normal', icon: 'check-circle', color: '#059669' },
                'low': { class: 'low', icon: 'minus-circle', color: '#64748b' }
            };

            referrals.forEach(referral => {
                const priority = referral.priority || 'normal';
                const priorityInfo = priorityConfig[priority] || priorityConfig['normal'];
                const safePatientName = referral.patient_name || 'Unknown Patient';
                const safeBranchName = referral.from_branch_name || 'Unknown Branch';
                const safeTreatmentName = referral.treatment_name || 'General Treatment';
                const safeReason = referral.reason || 'No reason provided';
                const patientNotes = referral.patient_response_notes || 'Patient approved this referral';

                html += '<tr>';

                // Patient
                html += '<td data-label="Patient">';
                html += '<div class="patient-info">';
                html += '<div class="patient-avatar">' + safePatientName.charAt(0).toUpperCase() + '</div>';
                html += '<div class="patient-details">';
                html += '<h4>' + safePatientName + '</h4>';
                html += '<p style="color: #10b981; font-size: 12px; margin: 2px 0;"><i class="fas fa-check-circle"></i> Patient Approved</p>';
                html += '</div>';
                html += '</div>';
                html += '</td>';

                // From Branch
                html += '<td data-label="From Branch" style="font-weight: 500;">' + safeBranchName + '</td>';

                // Treatment
                html += '<td data-label="Treatment">';
                html += '<div style="font-weight: 500;">' + safeTreatmentName + '</div>';
                html += '<div style="color: #6b7280; font-size: 12px; margin-top: 2px;">' + safeReason + '</div>';
                html += '</td>';

                // Priority
                html += '<td data-label="Priority">';
                html += '<div class="priority-indicator ' + priorityInfo.class + '">';
                html += '<i class="fas fa-' + priorityInfo.icon + '"></i>';
                html += priority.charAt(0).toUpperCase() + priority.slice(1);
                html += '</div>';
                html += '</td>';

                // Patient Response
                html += '<td data-label="Patient Response" style="max-width: 200px;">';
                html += '<div style="background: #f0fdf4; padding: 8px; border-radius: 6px; border-left: 3px solid #10b981;">';
                html += '<div style="font-size: 12px; color: #10b981; font-weight: 600; margin-bottom: 4px;"><i class="fas fa-thumbs-up"></i> Approved by Patient</div>';
                html += '<div style="font-size: 12px; color: #374151; overflow: hidden; text-overflow: ellipsis;">' + patientNotes + '</div>';
                html += '</div>';
                html += '</td>';

                // Patient Approved Date
                html += '<td data-label="Approved Date" style="font-weight: 500;">';
                if (referral.patient_approved_at) {
                    const approvedDate = new Date(referral.patient_approved_at);
                    html += '<div>' + approvedDate.toLocaleDateString() + '</div>';
                    html += '<div style="color: #6b7280; font-size: 11px;">' + approvedDate.toLocaleTimeString() + '</div>';
                } else {
                    html += '<div style="color: #6b7280;">Unknown</div>';
                }
                html += '</td>';

                // Actions
                html += '<td data-label="Actions">';
                html += '<div class="action-buttons">';
                html += '<button class="btn-modern btn-success" onclick="acceptReferral(' + referral.id + ')" title="Accept patient-approved referral">';
                html += '<i class="fas fa-check"></i> Accept</button>';
                html += '<button class="btn-modern btn-danger" onclick="rejectReferral(' + referral.id + ')" title="Reject patient-approved referral">';
                html += '<i class="fas fa-times"></i> Reject</button>';
                html += '</div>';
                html += '</td>';

                html += '</tr>';
            });

            html += '</tbody></table>';
            container.innerHTML = html;
        }

        function displaySentReferrals(referrals) {
            const container = document.getElementById('sent-referrals-list');
            const sentBadge = document.getElementById('sent-badge');

            if (sentBadge) {
                sentBadge.textContent = referrals.length;
            }

            if (referrals.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-paper-plane"></i>
                        <h3>No Sent Referrals</h3>
                        <p>No referrals have been sent yet.</p>
                    </div>
                `;
                return;
            }

            let html = '<table class="modern-table">';
            html += '<thead><tr>';
            html += '<th>Patient</th>';
            html += '<th>To Branch</th>';
            html += '<th>Treatment</th>';
            html += '<th>Status</th>';
            html += '<th>Priority</th>';
            html += '<th>Created</th>';
            html += '</tr></thead><tbody>';

            const statusConfig = {
                'pending': { class: 'pending', icon: 'clock' },
                'accepted': { class: 'approved', icon: 'check-circle' },
                'rejected': { class: 'cancelled', icon: 'times-circle' }
            };

            const priorityConfig = {
                'high': { class: 'high', icon: 'exclamation-triangle' },
                'normal': { class: 'normal', icon: 'check-circle' },
                'low': { class: 'low', icon: 'minus-circle' }
            };

            referrals.forEach(referral => {
                const priority = referral.priority || 'normal';
                const status = referral.status || 'pending';
                const statusInfo = statusConfig[status] || statusConfig['pending'];
                const priorityInfo = priorityConfig[priority] || priorityConfig['normal'];
                const safePatientName = referral.patient_name || 'Unknown Patient';
                const safeBranchName = referral.to_branch_name || 'Unknown Branch';
                const safeTreatmentName = referral.treatment_name || 'General Treatment';

                html += '<tr>';

                // Patient
                html += '<td data-label="Patient">';
                html += '<div class="patient-info">';
                html += '<div class="patient-avatar">' + safePatientName.charAt(0).toUpperCase() + '</div>';
                html += '<div class="patient-details">';
                html += '<h4>' + safePatientName + '</h4>';
                html += '</div>';
                html += '</div>';
                html += '</td>';

                // To Branch
                html += '<td data-label="To Branch" style="font-weight: 500;">' + safeBranchName + '</td>';

                // Treatment
                html += '<td data-label="Treatment">' + safeTreatmentName + '</td>';

                // Status
                html += '<td data-label="Status">';
                html += '<span class="status-badge ' + statusInfo.class + '">';
                html += '<span class="status-dot"></span>';
                html += status.charAt(0).toUpperCase() + status.slice(1);
                html += '</span>';
                html += '</td>';

                // Priority
                html += '<td data-label="Priority">';
                html += '<div class="priority-indicator ' + priorityInfo.class + '">';
                html += '<i class="fas fa-' + priorityInfo.icon + '"></i>';
                html += priority.charAt(0).toUpperCase() + priority.slice(1);
                html += '</div>';
                html += '</td>';

                // Date (Created)
                html += '<td data-label="Date" style="font-weight: 500;">' + (referral.created_at ? new Date(referral.created_at).toLocaleDateString() : 'Unknown') + '</td>';

                html += '</tr>';
            });

            html += '</tbody></table>';
            container.innerHTML = html;
        }

        function initializeReferralFormSubmission() {
            const createReferralForm = document.getElementById('createReferralForm');
            if (createReferralForm) {
                createReferralForm.addEventListener('submit', function (e) {
                    e.preventDefault();

                    // Hide any existing alerts
                    hideReferAlert();

                    // Basic validation
                    const branchElement = document.getElementById('referral-branch');
                    const priorityElement = document.getElementById('referral-priority');
                    const reasonElement = document.getElementById('referral-reason');

                    if (!branchElement || !branchElement.value) {
                        showReferAlert('error', 'Please select a target branch for the referral.');
                        return;
                    }

                    if (!priorityElement || !priorityElement.value) {
                        showReferAlert('error', 'Please select a priority level for the referral.');
                        return;
                    }

                    if (!reasonElement || !reasonElement.value.trim()) {
                        showReferAlert('error', 'Please provide a reason for the referral.');
                        return;
                    }

                    if (reasonElement.value.trim().length < 10) {
                        showReferAlert('error', 'Referral reason must be at least 10 characters long.');
                        return;
                    }

                    // Show loading message
                    showReferAlert('info', 'Creating referral... Please wait.');

                    const formData = new FormData();
                    const appointmentIdElement = document.getElementById('referral-appointment-id');
                    const appointmentId = appointmentIdElement ? appointmentIdElement.value : '';

                    // Get form elements with null checks
                    const patientElement = document.getElementById('referral-patient');
                    const treatmentElement = document.getElementById('referral-treatment');

                    if (appointmentId) {
                        // Creating referral from appointment (treatment is optional)
                        formData.append('action', 'createReferralFromAppointment');
                        formData.append('appointment_id', appointmentId);
                        formData.append('to_branch_id', branchElement.value);
                        formData.append('priority', priorityElement.value);
                        formData.append('reason', reasonElement.value);

                        // Add treatment if selected (optional for appointment referrals)
                        if (treatmentElement && treatmentElement.value && !treatmentElement.disabled) {
                            formData.append('treatment_type_id', treatmentElement.value);
                        }
                    } else {
                        // Creating new referral
                        if (!patientElement || !patientElement.value) {
                            showReferAlert('error', 'Please select a patient for the referral.');
                            return;
                        }

                        if (!treatmentElement || !treatmentElement.value || treatmentElement.disabled) {
                            showReferAlert('error', 'Please select a treatment type that is available at the target branch.');
                            return;
                        }

                        formData.append('action', 'createReferral');
                        formData.append('patient_id', patientElement.value);
                        formData.append('to_branch_id', branchElement.value);
                        formData.append('treatment_type_id', treatmentElement.value);
                        formData.append('priority', priorityElement.value);
                        formData.append('reason', reasonElement.value);
                    }

                    fetch('../../src/controllers/ReferralController.php', {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                showReferAlert('success', '✅ Referral created successfully! The receiving branch will be notified.');
                                resetReferralForm();
                                loadSentReferrals();
                                updateReferralStats();

                                // Auto-close modal after 3 seconds on success
                                setTimeout(() => {
                                    closeReferModal();
                                }, 3000);
                            } else {
                                showReferAlert('error', data.message || 'Failed to create referral. Please try again.');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showReferAlert('error', 'Network error occurred. Please check your connection and try again.');
                        });
                });
            }
        }

        // Create referral form submission

        function acceptReferral(referralId) {
            // Find the referral details from the current data
            fetch(`../../src/controllers/ReferralController.php?action=getReferralDetails&referral_id=${referralId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.referral) {
                        const referral = data.referral;

                        // Populate referral details in the modal
                        const detailsDiv = document.getElementById('accept-referral-details');
                        detailsDiv.innerHTML = `
                        <h5 style="margin: 0 0 12px 0; color: #1f2937; font-weight: 600;">
                            <i class="fas fa-user" style="color: #3b82f6;"></i> Patient-Approved Referral Information
                        </h5>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px;">
                            <div>
                                <strong>Patient:</strong> ${referral.patient_name || 'Unknown'}
                            </div>
                            <div>
                                <strong>From Branch:</strong> ${referral.from_branch_name || 'Unknown'}
                            </div>
                            <div>
                                <strong>Treatment:</strong> ${referral.treatment_name || 'General consultation'}
                            </div>
                            <div>
                                <strong>Priority:</strong> 
                                <span style="color: ${referral.priority === 'high' ? '#ef4444' : referral.priority === 'normal' ? '#059669' : '#6b7280'}; font-weight: 600;">
                                    ${referral.priority ? referral.priority.charAt(0).toUpperCase() + referral.priority.slice(1) : 'Normal'}
                                </span>
                            </div>
                            <div style="grid-column: 1 / -1;">
                                <strong>Original Reason:</strong> ${referral.reason || 'No reason provided'}
                            </div>
                            ${referral.patient_approved_at ? `
                            <div style="grid-column: 1 / -1; background: #f0fdf4; padding: 12px; border-radius: 6px; border-left: 3px solid #10b981;">
                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                                    <i class="fas fa-check-circle" style="color: #10b981;"></i>
                                    <strong style="color: #15803d;">Patient Approval Details</strong>
                                </div>
                                <div style="color: #374151; font-size: 13px;">
                                    <strong>Approved on:</strong> ${new Date(referral.patient_approved_at).toLocaleDateString()} at ${new Date(referral.patient_approved_at).toLocaleTimeString()}<br>
                                    <strong>Patient Notes:</strong> ${referral.patient_response_notes || 'Patient approved this referral without additional notes'}
                                </div>
                            </div>
                            ` : ''}
                        </div>
                    `;

                        // Set referral ID in hidden field
                        document.getElementById('accept-referral-id').value = referralId;

                        // Set minimum date to today
                        const today = new Date().toISOString().split('T')[0];
                        const dateField = document.getElementById('accept-appointment-date');
                        dateField.min = today;

                        // Add event listener for date change to load available times
                        const existingListener = dateField.onchange;
                        dateField.onchange = function () {
                            if (existingListener) existingListener.call(this);
                            loadAvailableTimesForAccept(this.value);
                        };

                        // Clear form and reset time options
                        document.getElementById('acceptReferralForm').reset();
                        const timeSelect = document.getElementById('accept-appointment-time');
                        timeSelect.innerHTML = '<option value="">-- Select date first --</option>';
                        hideAcceptAlert();

                        // Show modal
                        document.getElementById('accept-referral-modal').style.display = 'block';
                    } else {
                        showAlert('❌ Could not load referral details', 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('❌ Error loading referral details', 'danger');
                });
        }

        function rejectReferral(referralId) {
            // Find the referral details from the current data
            fetch(`../../src/controllers/ReferralController.php?action=getReferralDetails&referral_id=${referralId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.referral) {
                        const referral = data.referral;

                        // Populate referral details in the modal
                        const detailsDiv = document.getElementById('reject-referral-details');
                        detailsDiv.innerHTML = `
                        <h5 style="margin: 0 0 12px 0; color: #1f2937; font-weight: 600;">
                            <i class="fas fa-exclamation-triangle" style="color: #f59e0b;"></i> Patient-Approved Referral to be Rejected
                        </h5>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px;">
                            <div>
                                <strong>Patient:</strong> ${referral.patient_name || 'Unknown'}
                            </div>
                            <div>
                                <strong>From Branch:</strong> ${referral.from_branch_name || 'Unknown'}
                            </div>
                            <div>
                                <strong>Treatment:</strong> ${referral.treatment_name || 'General consultation'}
                            </div>
                            <div>
                                <strong>Priority:</strong> 
                                <span style="color: ${referral.priority === 'high' ? '#ef4444' : referral.priority === 'normal' ? '#059669' : '#6b7280'}; font-weight: 600;">
                                    ${referral.priority ? referral.priority.charAt(0).toUpperCase() + referral.priority.slice(1) : 'Normal'}
                                </span>
                            </div>
                            <div style="grid-column: 1 / -1;">
                                <strong>Original Reason:</strong> ${referral.reason || 'No reason provided'}
                            </div>
                            ${referral.patient_approved_at ? `
                            <div style="grid-column: 1 / -1; background: #fef3cd; padding: 12px; border-radius: 6px; border-left: 3px solid #fbbf24;">
                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                                    <i class="fas fa-exclamation-triangle" style="color: #f59e0b;"></i>
                                    <strong style="color: #92400e;">Patient Already Approved This Referral</strong>
                                </div>
                                <div style="color: #374151; font-size: 13px;">
                                    <strong>Approved on:</strong> ${new Date(referral.patient_approved_at).toLocaleDateString()} at ${new Date(referral.patient_approved_at).toLocaleTimeString()}<br>
                                    <strong>Patient Notes:</strong> ${referral.patient_response_notes || 'Patient approved this referral without additional notes'}<br>
                                    <br>
                                    <div style="color: #d97706; font-weight: 600;">
                                        ⚠️ Rejecting will cancel the patient's approved appointment request
                                    </div>
                                </div>
                            </div>
                            ` : ''}
                        </div>
                    `;

                        // Set referral ID in hidden field
                        document.getElementById('reject-referral-id').value = referralId;

                        // Clear form
                        document.getElementById('rejectReferralForm').reset();
                        hideRejectAlert();

                        // Show modal
                        document.getElementById('reject-referral-modal').style.display = 'block';
                    } else {
                        showAlert('❌ Could not load referral details', 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('❌ Error loading referral details', 'danger');
                });
        }

        // Modal Management Functions
        function closeAcceptReferralModal() {
            document.getElementById('accept-referral-modal').style.display = 'none';
            document.getElementById('acceptReferralForm').reset();

            // Reset time select to default state
            const timeSelect = document.getElementById('accept-appointment-time');
            timeSelect.innerHTML = '<option value="">-- Select date first --</option>';
            timeSelect.disabled = false;

            // Hide any error messages
            const timeError = document.getElementById('accept-appointment-time-error');
            if (timeError) timeError.style.display = 'none';

            hideAcceptAlert();
        }

        function closeRejectReferralModal() {
            document.getElementById('reject-referral-modal').style.display = 'none';
            document.getElementById('rejectReferralForm').reset();
            hideRejectAlert();
        }

        // Alert Functions for Modals
        function showAcceptAlert(type, message) {
            const alertDiv = document.getElementById('accept-alert');
            const iconSpan = document.getElementById('accept-alert-icon');
            const messageSpan = document.getElementById('accept-alert-message');

            alertDiv.className = `modal-alert ${type}`;

            const icons = {
                'success': '✅',
                'error': '❌',
                'warning': '⚠️',
                'info': 'ℹ️'
            };

            iconSpan.innerHTML = icons[type] || 'ℹ️';
            messageSpan.textContent = message;
            alertDiv.style.display = 'flex';

            if (type === 'success') {
                setTimeout(hideAcceptAlert, 4000);
            }
        }

        function hideAcceptAlert() {
            document.getElementById('accept-alert').style.display = 'none';
        }

        function showRejectAlert(type, message) {
            const alertDiv = document.getElementById('reject-alert');
            const iconSpan = document.getElementById('reject-alert-icon');
            const messageSpan = document.getElementById('reject-alert-message');

            alertDiv.className = `modal-alert ${type}`;

            const icons = {
                'success': '✅',
                'error': '❌',
                'warning': '⚠️',
                'info': 'ℹ️'
            };

            iconSpan.innerHTML = icons[type] || 'ℹ️';
            messageSpan.textContent = message;
            alertDiv.style.display = 'flex';

            if (type === 'success') {
                setTimeout(hideRejectAlert, 4000);
            }
        }

        function hideRejectAlert() {
            document.getElementById('reject-alert').style.display = 'none';
        }

        // Quick reason selection function
        function setQuickReason(reason) {
            const reasonTextarea = document.getElementById('reject-reason');
            reasonTextarea.value = reason;
            reasonTextarea.focus();

            // Update character count
            const countSpan = document.getElementById('reject-reason-count');
            if (countSpan) {
                countSpan.textContent = reason.length;
            }

            // Trigger validation
            validateRejectReason();
        }

        // Form Validation Functions
        function loadAvailableTimesForAccept(selectedDate) {
            const timeSelect = document.getElementById('accept-appointment-time');
            const timeError = document.getElementById('accept-appointment-time-error');

            if (!selectedDate) {
                timeSelect.innerHTML = '<option value="">-- Select date first --</option>';
                timeSelect.disabled = true;
                return;
            }

            // Show loading state
            timeSelect.innerHTML = '<option value="">⏳ Loading available times...</option>';
            timeSelect.disabled = true;

            // Get the day of week for the selected date
            const date = new Date(selectedDate);
            const dayOfWeek = date.toLocaleDateString('en-US', { weekday: 'long' }).toLowerCase();

            // Load branch operating hours
            fetch(`../api/branch-hours.php?action=get_hours&branch_id=<?php echo getSessionBranchId(); ?>`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.schedule) {
                        const daySchedule = data.schedule.find(day => day.day === dayOfWeek);

                        if (!daySchedule || !daySchedule.is_open) {
                            timeSelect.innerHTML = '<option value="">❌ Branch closed on this day</option>';
                            timeError.textContent = 'The branch is closed on the selected date. Please choose another date.';
                            timeError.style.display = 'block';
                            return;
                        }

                        // Generate time slots based on operating hours
                        const timeSlots = generateTimeSlots(daySchedule.open_time, daySchedule.close_time);

                        if (timeSlots.length === 0) {
                            timeSelect.innerHTML = '<option value="">❌ No available times</option>';
                            timeError.textContent = 'No time slots available for the selected date.';
                            timeError.style.display = 'block';
                            return;
                        }

                        // Load existing appointments and blocked times to check conflicts
                        const appointmentsPromise = loadExistingAppointments(selectedDate);
                        const blockedTimesPromise = loadBlockedTimeSlots(selectedDate);

                        return Promise.all([appointmentsPromise, blockedTimesPromise]).then(([existingAppointments, blockedTimes]) => {
                            const availableSlots = filterAvailableTimeSlots(timeSlots, existingAppointments, blockedTimes);
                            const unavailableSlots = generateUnavailableSlots(timeSlots, existingAppointments, blockedTimes);

                            timeSelect.innerHTML = '<option value="">-- Select time --</option>';

                            // Add available slots first
                            availableSlots.forEach(slot => {
                                const option = document.createElement('option');
                                option.value = slot.value;
                                option.textContent = slot.display;
                                timeSelect.appendChild(option);
                            });

                            // Add unavailable slots with visual indicators (disabled)
                            unavailableSlots.forEach(slot => {
                                const option = document.createElement('option');
                                option.value = '';
                                option.disabled = true;
                                option.textContent = slot.display;
                                option.style.color = '#999';
                                option.style.fontStyle = 'italic';
                                timeSelect.appendChild(option);
                            });

                            if (availableSlots.length === 0) {
                                timeSelect.innerHTML += '<option value="">❌ All time slots are booked</option>';
                                timeError.textContent = 'All time slots are booked for this date. Please choose another date.';
                                timeError.style.display = 'block';
                            } else {
                                timeSelect.disabled = false;
                                timeError.style.display = 'none';

                                const blockedCount = timeSlots.length - availableSlots.length;
                                let message = `Found ${availableSlots.length} available time slots`;
                                if (blockedCount > 0) {
                                    message += ` (${blockedCount} slots unavailable due to bookings or time blocks)`;
                                }
                                message += ` for ${date.toLocaleDateString()}.`;
                                showAcceptAlert('success', message);
                            }
                        });
                    } else {
                        throw new Error('Failed to load branch hours');
                    }
                })
                .catch(error => {
                    console.error('Error loading available times:', error);
                    timeSelect.innerHTML = '<option value="">❌ Error loading times</option>';
                    timeError.textContent = 'Unable to load available times. Please try again.';
                    timeError.style.display = 'block';
                    showAcceptAlert('error', 'Failed to load available appointment times. Please try again.');
                });
        }

        function generateTimeSlots(openTime, closeTime, intervalMinutes = 60) {
            const slots = [];
            const start = new Date(`2000-01-01 ${openTime}`);
            const end = new Date(`2000-01-01 ${closeTime}`);

            // Subtract 1 hour from close time to ensure last appointment can complete
            end.setHours(end.getHours() - 1);

            const current = new Date(start);

            while (current < end) {
                const timeValue = current.toTimeString().slice(0, 5); // HH:MM format
                const timeDisplay = current.toLocaleTimeString('en-US', {
                    hour: 'numeric',
                    minute: '2-digit',
                    hour12: true
                });

                slots.push({
                    value: timeValue,
                    display: `🕐 ${timeDisplay}`
                });

                current.setMinutes(current.getMinutes() + intervalMinutes);
            }

            return slots;
        }

        function loadExistingAppointments(date) {
            return fetch(`../../src/controllers/AppointmentController.php?action=getAppointments`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.appointments) {
                        // Filter appointments for the specific date
                        const dateAppointments = data.appointments.filter(appointment =>
                            appointment.appointment_date === date
                        );
                        return dateAppointments;
                    }
                    return [];
                })
                .catch(error => {
                    console.error('Error loading existing appointments:', error);
                    return [];
                });
        }

        function loadBlockedTimeSlots(date) {
            return fetch(`../../src/controllers/AppointmentController.php?action=getBlockedTimes&date=${date}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.blocked_times) {
                        return data.blocked_times;
                    }
                    return [];
                })
                .catch(error => {
                    console.error('Error loading blocked time slots:', error);
                    return [];
                });
        }

        function filterAvailableTimeSlots(timeSlots, existingAppointments, blockedTimes = []) {
            return timeSlots.filter(slot => {
                const slotTime = slot.value;

                // Check if this time slot conflicts with any existing appointment
                const hasAppointmentConflict = existingAppointments.some(appointment => {
                    if (appointment.status === 'cancelled' || appointment.status === 'rejected') {
                        return false; // Don't consider cancelled/rejected appointments
                    }

                    const appointmentTime = appointment.appointment_time.slice(0, 5); // HH:MM format
                    const appointmentEndTime = appointment.end_time ?
                        appointment.end_time.slice(0, 5) :
                        calculateEndTime(appointmentTime, appointment.duration_minutes || 60);

                    // Check if slot overlaps with existing appointment
                    return (slotTime >= appointmentTime && slotTime < appointmentEndTime);
                });

                // Check if this time slot conflicts with any blocked time
                const hasBlockedTimeConflict = blockedTimes.some(blockedTime => {
                    const blockStartTime = blockedTime.start_time.slice(0, 5); // HH:MM format
                    const blockEndTime = blockedTime.end_time.slice(0, 5); // HH:MM format

                    // Check if slot overlaps with blocked time period
                    return (slotTime >= blockStartTime && slotTime < blockEndTime);
                });

                return !hasAppointmentConflict && !hasBlockedTimeConflict;
            });
        }

        function generateUnavailableSlots(timeSlots, existingAppointments, blockedTimes = []) {
            return timeSlots.filter(slot => {
                const slotTime = slot.value;

                // Check for appointment conflicts
                const appointmentConflict = existingAppointments.find(appointment => {
                    if (appointment.status === 'cancelled' || appointment.status === 'rejected') {
                        return false;
                    }

                    const appointmentTime = appointment.appointment_time.slice(0, 5);
                    const appointmentEndTime = appointment.end_time ?
                        appointment.end_time.slice(0, 5) :
                        calculateEndTime(appointmentTime, appointment.duration_minutes || 60);

                    return (slotTime >= appointmentTime && slotTime < appointmentEndTime);
                });

                // Check for blocked time conflicts
                const blockedTimeConflict = blockedTimes.find(blockedTime => {
                    const blockStartTime = blockedTime.start_time.slice(0, 5);
                    const blockEndTime = blockedTime.end_time.slice(0, 5);

                    return (slotTime >= blockStartTime && slotTime < blockEndTime);
                });

                if (appointmentConflict) {
                    return {
                        ...slot,
                        display: `❌ ${slot.display.replace('🕐 ', '')} - Booked (${appointmentConflict.patient_name || 'Patient'})`
                    };
                } else if (blockedTimeConflict) {
                    const reason = blockedTimeConflict.reason || 'Time blocked';
                    const blockType = blockedTimeConflict.block_type || 'general';
                    return {
                        ...slot,
                        display: `🚫 ${slot.display.replace('🕐 ', '')} - ${reason} (${blockType})`
                    };
                }

                return null;
            }).filter(slot => slot !== null);
        }

        function calculateEndTime(startTime, durationMinutes) {
            const [hours, minutes] = startTime.split(':').map(Number);
            const startDate = new Date();
            startDate.setHours(hours, minutes, 0, 0);

            const endDate = new Date(startDate.getTime() + (durationMinutes * 60000));

            return endDate.toTimeString().slice(0, 5); // HH:MM format
        }

        function validateAcceptForm() {
            const date = document.getElementById('accept-appointment-date').value;
            const time = document.getElementById('accept-appointment-time').value;
            const timeSelect = document.getElementById('accept-appointment-time');

            if (!date) {
                showAcceptAlert('error', 'Please select an appointment date.');
                return false;
            }

            if (!time || time === '' || timeSelect.disabled) {
                if (timeSelect.disabled) {
                    showAcceptAlert('error', 'Please wait for available times to load or select a different date.');
                } else {
                    showAcceptAlert('error', 'Please select an appointment time.');
                }
                return false;
            }

            // Check if selected option indicates an error state
            if (time.includes('❌') || time.includes('⏳')) {
                showAcceptAlert('error', 'Please select a valid appointment time.');
                return false;
            }

            // Validate date is not in the past
            const selectedDate = new Date(date);
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            if (selectedDate < today) {
                showAcceptAlert('error', 'Appointment date cannot be in the past.');
                return false;
            }

            // Additional validation: Check if selected time is for today and in the past
            if (selectedDate.toDateString() === today.toDateString()) {
                const now = new Date();
                const [hours, minutes] = time.split(':').map(Number);
                const selectedDateTime = new Date();
                selectedDateTime.setHours(hours, minutes, 0, 0);

                if (selectedDateTime <= now) {
                    showAcceptAlert('error', 'Appointment time cannot be in the past for today\'s date.');
                    return false;
                }
            }

            return true;
        }

        function validateRejectReason() {
            const reason = document.getElementById('reject-reason').value.trim();
            const errorDiv = document.getElementById('reject-reason-error');

            if (!reason) {
                errorDiv.textContent = 'Please provide a reason for rejection.';
                errorDiv.style.display = 'block';
                return false;
            }

            if (reason.length < 10) {
                errorDiv.textContent = 'Reason must be at least 10 characters long.';
                errorDiv.style.display = 'block';
                return false;
            }

            errorDiv.style.display = 'none';
            return true;
        }

        // Form Submission Handlers
        document.addEventListener('DOMContentLoaded', function () {
            // Edit Appointment Form
            const editAppointmentForm = document.getElementById('editAppointmentForm');
            if (editAppointmentForm) {
                editAppointmentForm.addEventListener('submit', function (e) {
                    e.preventDefault();
                    
                    if (!validateEditForm()) {
                        return;
                    }
                    
                    showEditAlert('Updating appointment...', 'info');
                    
                    const formData = new FormData(editAppointmentForm);
                    formData.append('action', 'updateAppointment');
                    
                    fetch('../../src/controllers/AppointmentController.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showEditAlert('Appointment updated successfully!', 'success');
                            setTimeout(() => {
                                closeEditModal();
                                loadPendingAppointments();
                                loadAllAppointments();
                                showAlert('Appointment updated successfully!', 'success');
                            }, 2000);
                        } else {
                            showEditAlert('Failed to update appointment: ' + data.message, 'danger');
                        }
                    })
                    .catch(error => {
                        console.error('Error updating appointment:', error);
                        showEditAlert('Error updating appointment', 'danger');
                    });
                });
                
                // Date change handler
                const editDateInput = document.getElementById('edit-appointment-date');
                if (editDateInput) {
                    editDateInput.addEventListener('change', function() {
                        loadAvailableTimesForEdit();
                    });
                }
                
                // Notes character counter
                const editNotesTextarea = document.getElementById('edit-notes');
                if (editNotesTextarea) {
                    editNotesTextarea.addEventListener('input', updateEditNotesCounter);
                }
            }

            // Walk-in Patient Form
            const walkInForm = document.getElementById('walkInForm');
            if (walkInForm) {
                walkInForm.addEventListener('submit', function (e) {
                    e.preventDefault();

                    console.log('🔥 Walk-in form submitted');

                    if (!validateWalkInForm()) {
                        console.log('❌ Walk-in form validation failed');
                        showWalkInAlert('error', 'Please correct the errors in the form.');
                        return;
                    }

                    console.log('✅ Walk-in form validation passed');
                    showWalkInAlert('info', 'Registering walk-in patient...');

                    const formData = new FormData();
                    const walkInData = getWalkInFormData();

                    console.log('📋 Walk-in data collected:', walkInData);

                    Object.keys(walkInData).forEach(key => {
                        if (walkInData[key]) {
                            formData.append(key, walkInData[key]);
                        }
                    });
                    formData.append('action', 'createWalkInAppointment');
                    formData.append('walk_in', 'true');

                    console.log('🚀 Sending walk-in request to server...');

                    fetch('../../src/controllers/AppointmentController.php', {
                        method: 'POST',
                        credentials: 'same-origin',
                        body: formData
                    })
                        .then(response => response.json())
                        .then(data => {
                            console.log('📥 Server response:', data);
                            if (data.success) {
                                console.log('✅ Walk-in patient created successfully');
                                showWalkInAlert('success', '✅ Walk-in patient registered successfully!');
                                setTimeout(() => {
                                    closeWalkInModal();
                                    refreshData();
                                    showAlert('✅ Walk-in patient registered and appointment created!', 'success');
                                }, 2000);
                            } else {
                                console.log('❌ Walk-in creation failed:', data.message);
                                showWalkInAlert('error', data.message || 'Failed to register walk-in patient.');
                            }
                        })
                        .catch(error => {
                            console.error('💥 Walk-in request error:', error);
                            showWalkInAlert('error', 'Network error occurred. Please try again.');
                        });
                });

                // Date change listener for walk-in
                const walkInDate = document.getElementById('walkin-date');
                if (walkInDate) {
                    walkInDate.addEventListener('change', function () {
                        loadAvailableTimesForWalkIn(this.value);
                    });
                }

                // Notes character counter
                const walkInNotes = document.getElementById('walkin-notes');
                if (walkInNotes) {
                    walkInNotes.addEventListener('input', function () {
                        const count = this.value.length;
                        document.getElementById('walkin-notes-count').textContent = count;
                    });
                }

                // Real-time validation
                const walkInFields = ['walkin-name', 'walkin-phone', 'walkin-email', 'walkin-date', 'walkin-time', 'walkin-treatment', 'walkin-priority'];
                walkInFields.forEach(fieldId => {
                    const field = document.getElementById(fieldId);
                    if (field) {
                        field.addEventListener('blur', function () {
                            validateWalkInField(fieldId);
                        });
                    }
                });
            }

            // Walk-in Referral Form
            const walkInReferralForm = document.getElementById('walkInReferralForm');
            if (walkInReferralForm) {
                walkInReferralForm.addEventListener('submit', function (e) {
                    e.preventDefault();

                    const branch = document.getElementById('walkin-referral-branch').value;
                    const priority = document.getElementById('walkin-referral-priority').value;
                    const reason = document.getElementById('walkin-referral-reason').value.trim();

                    if (!branch || !priority || !reason) {
                        showWalkInReferralAlert('error', 'Please fill in all required fields.');
                        return;
                    }

                    if (reason.length < 10) {
                        showWalkInReferralAlert('error', 'Reason must be at least 10 characters long.');
                        return;
                    }

                    showWalkInReferralAlert('info', 'Creating walk-in patient referral...');

                    const formData = new FormData();
                    const walkInData = getWalkInFormData();

                    // Add patient data
                    Object.keys(walkInData).forEach(key => {
                        if (walkInData[key]) {
                            formData.append('patient_' + key, walkInData[key]);
                        }
                    });

                    // Add referral data
                    formData.append('action', 'createWalkInReferral');
                    formData.append('to_branch_id', branch);
                    formData.append('priority', priority);
                    formData.append('reason', reason);
                    formData.append('walk_in_referral', 'true');

                    fetch('../../src/controllers/ReferralController.php', {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                showWalkInReferralAlert('success', '✅ Walk-in patient referral created successfully!');
                                setTimeout(() => {
                                    closeWalkInReferralModal();
                                    closeWalkInModal();
                                    loadReferralData();
                                    showAlert('✅ Walk-in patient referred to another branch!', 'success');
                                }, 2000);
                            } else {
                                showWalkInReferralAlert('error', data.message || 'Failed to create referral.');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showWalkInReferralAlert('error', 'Network error occurred. Please try again.');
                        });
                });

                // Character counter for referral reason
                const walkInReferralReason = document.getElementById('walkin-referral-reason');
                if (walkInReferralReason) {
                    walkInReferralReason.addEventListener('input', function () {
                        const count = this.value.length;
                        document.getElementById('walkin-referral-reason-count').textContent = count;
                    });
                }
            }

            // Accept Referral Form
            const acceptForm = document.getElementById('acceptReferralForm');
            if (acceptForm) {
                acceptForm.addEventListener('submit', function (e) {
                    e.preventDefault();

                    if (!validateAcceptForm()) {
                        return;
                    }

                    showAcceptAlert('info', 'Processing referral acceptance...');

                    const formData = new FormData();
                    formData.append('action', 'acceptReferral');
                    formData.append('referral_id', document.getElementById('accept-referral-id').value);
                    formData.append('appointment_date', document.getElementById('accept-appointment-date').value);
                    formData.append('appointment_time', document.getElementById('accept-appointment-time').value);

                    const notes = document.getElementById('accept-notes').value.trim();
                    if (notes) {
                        formData.append('notes', notes);
                    }

                    fetch('../../src/controllers/ReferralController.php', {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                showAcceptAlert('success', '✅ Referral accepted and appointment created successfully!');
                                setTimeout(() => {
                                    closeAcceptReferralModal();
                                    loadIncomingReferrals();
                                    loadAllAppointments();
                                    updateReferralStats();
                                    showAlert('✅ Referral accepted and appointment created!', 'success');
                                }, 2000);
                            } else {
                                showAcceptAlert('error', data.message || 'Failed to accept referral.');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showAcceptAlert('error', 'Network error occurred. Please try again.');
                        });
                });
            }

            // Reject Referral Form
            const rejectForm = document.getElementById('rejectReferralForm');
            if (rejectForm) {
                rejectForm.addEventListener('submit', function (e) {
                    e.preventDefault();

                    if (!validateRejectReason()) {
                        return;
                    }

                    showRejectAlert('info', 'Processing referral rejection...');

                    const formData = new FormData();
                    formData.append('action', 'rejectReferral');
                    formData.append('referral_id', document.getElementById('reject-referral-id').value);
                    formData.append('rejection_reason', document.getElementById('reject-reason').value);

                    fetch('../../src/controllers/ReferralController.php', {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                showRejectAlert('success', '✅ Referral rejected successfully.');
                                setTimeout(() => {
                                    closeRejectReferralModal();
                                    loadIncomingReferrals();
                                    updateReferralStats();
                                    showAlert('✅ Referral rejected', 'success');
                                }, 2000);
                            } else {
                                showRejectAlert('error', data.message || 'Failed to reject referral.');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showRejectAlert('error', 'Network error occurred. Please try again.');
                        });
                });
            }

            // Character counters
            const acceptNotes = document.getElementById('accept-notes');
            if (acceptNotes) {
                acceptNotes.addEventListener('input', function () {
                    const count = this.value.length;
                    const counter = document.getElementById('accept-notes-count');
                    if (counter) {
                        counter.textContent = count;
                    }
                });
            }

            const rejectReason = document.getElementById('reject-reason');
            if (rejectReason) {
                rejectReason.addEventListener('input', function () {
                    const count = this.value.length;
                    const counter = document.getElementById('reject-reason-count');
                    if (counter) {
                        counter.textContent = count;
                    }
                    validateRejectReason();
                });
            }
        });

        function resetReferralForm() {
            const form = document.getElementById('createReferralForm');
            if (form) {
                form.reset();
            }

            const appointmentIdField = document.getElementById('referral-appointment-id');
            if (appointmentIdField) {
                appointmentIdField.value = '';
            }

            const treatmentField = document.getElementById('referral-treatment');
            if (treatmentField) {
                treatmentField.innerHTML = '<option value="">-- Select Treatment --</option>';
            }
        }

        function openReferralManagement() {
            document.getElementById('referrals').scrollIntoView({ behavior: 'smooth' });
            showReferralTab('incoming');
        }

        // =================== WALK-IN PATIENT MANAGEMENT ===================

        function openWalkInModal() {
            // Clear any existing alerts
            hideWalkInAlert();

            // Reset form
            document.getElementById('walkInForm').reset();

            // Set today as default date
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('walkin-date').value = today;

            // Load treatments for current branch
            loadBranchTreatmentsForWalkIn();

            // Load available times for today
            loadAvailableTimesForWalkIn(today);

            // Show modal
            document.getElementById('walkin-modal').style.display = 'block';

            // Focus on name field
            setTimeout(() => {
                document.getElementById('walkin-name').focus();
            }, 100);
        }

        // Ensure global availability
        window.openWalkInModal = openWalkInModal;

        function closeWalkInModal() {
            document.getElementById('walkin-modal').style.display = 'none';
            document.getElementById('walkInForm').reset();
            hideWalkInAlert();

            // Reset time select
            const timeSelect = document.getElementById('walkin-time');
            timeSelect.innerHTML = '<option value="">-- Select date first --</option>';
        }

        function openWalkInReferralModal() {
            // Get form data from walk-in form
            const walkInData = getWalkInFormData();

            if (!validateWalkInFormForReferral(walkInData)) {
                return;
            }

            // Populate patient info in referral modal
            populateWalkInReferralPatientInfo(walkInData);

            // Load available branches
            loadAvailableBranchesForWalkInReferral();

            // Clear referral form
            document.getElementById('walkInReferralForm').reset();
            hideWalkInReferralAlert();

            // Show referral modal
            document.getElementById('walkin-referral-modal').style.display = 'block';
        }

        function closeWalkInReferralModal() {
            document.getElementById('walkin-referral-modal').style.display = 'none';
            document.getElementById('walkInReferralForm').reset();
            hideWalkInReferralAlert();
        }

        function getWalkInFormData() {
            return {
                name: document.getElementById('walkin-name').value.trim(),
                phone: document.getElementById('walkin-phone').value.trim(),
                email: document.getElementById('walkin-email').value.trim(),
                birthdate: document.getElementById('walkin-birthdate').value,
                address: document.getElementById('walkin-address').value.trim(),
                appointment_date: document.getElementById('walkin-date').value,
                appointment_time: document.getElementById('walkin-time').value,
                treatment_type_id: document.getElementById('walkin-treatment').value,
                priority: document.getElementById('walkin-priority').value,
                notes: document.getElementById('walkin-notes').value.trim()
            };
        }

        function validateWalkInFormForReferral(data) {
            if (!data.name) {
                showWalkInAlert('error', 'Please enter patient name before creating referral.');
                return false;
            }

            if (!data.phone) {
                showWalkInAlert('error', 'Please enter patient phone number before creating referral.');
                return false;
            }

            return true;
        }

        function populateWalkInReferralPatientInfo(data) {
            const container = document.getElementById('walkin-referral-patient-info');

            let html = `
                <h5 style="margin: 0 0 12px 0; color: #1f2937; font-weight: 600;">
                    <i class="fas fa-walking" style="color: #f59e0b;"></i> Walk-in Patient Information
                </h5>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px;">
                    <div><strong>Name:</strong> ${data.name || 'Not provided'}</div>
                    <div><strong>Phone:</strong> ${data.phone || 'Not provided'}</div>
            `;

            if (data.email) {
                html += `<div><strong>Email:</strong> ${data.email}</div>`;
            }

            if (data.appointment_date) {
                html += `<div><strong>Requested Date:</strong> ${data.appointment_date}</div>`;
            }

            if (data.appointment_time) {
                html += `<div><strong>Requested Time:</strong> ${data.appointment_time}</div>`;
            }

            if (data.notes) {
                html += `<div style="grid-column: 1 / -1;"><strong>Notes:</strong> ${data.notes}</div>`;
            }

            html += '</div>';
            container.innerHTML = html;
        }

        function loadBranchTreatmentsForWalkIn() {
            fetch(`../../src/controllers/TreatmentController.php?action=getBranchTreatments&branch_id=<?php echo getSessionBranchId(); ?>`)
                .then(response => response.json())
                .then(data => {
                    const select = document.getElementById('walkin-treatment');

                    if (data.success && data.treatments) {
                        select.innerHTML = '<option value="">-- Select Treatment --</option>';
                        data.treatments.forEach(treatment => {
                            const option = document.createElement('option');
                            option.value = treatment.id;
                            option.textContent = `${treatment.name} (${treatment.duration_minutes} min) - ₱${parseFloat(treatment.price).toLocaleString()}`;
                            select.appendChild(option);
                        });
                    } else {
                        select.innerHTML = '<option value="">No treatments available</option>';
                        showWalkInAlert('warning', 'No treatments available at this branch.');
                    }
                })
                .catch(error => {
                    console.error('Error loading treatments:', error);
                    showWalkInAlert('error', 'Failed to load treatments. Please try again.');
                });
        }

        function loadAvailableTimesForWalkIn(selectedDate) {
            const timeSelect = document.getElementById('walkin-time');
            const timeError = document.getElementById('walkin-time-error');

            if (!selectedDate) {
                timeSelect.innerHTML = '<option value="">-- Select date first --</option>';
                return;
            }

            // Show loading state
            timeSelect.innerHTML = '<option value="">⏳ Loading available times...</option>';
            timeSelect.disabled = true;

            // Get day of week
            const date = new Date(selectedDate);
            const dayOfWeek = date.toLocaleDateString('en-US', { weekday: 'long' }).toLowerCase();

            // Load branch operating hours and available slots
            Promise.all([
                fetch(`../api/branch-hours.php?action=get_hours&branch_id=<?php echo getSessionBranchId(); ?>`),
                fetch(`../../src/controllers/AppointmentController.php?action=getAppointments`),
                fetch(`../../src/controllers/AppointmentController.php?action=getBlockedTimes&date=${selectedDate}`)
            ])
                .then(responses => Promise.all(responses.map(r => r.json())))
                .then(([hoursData, appointmentsData, blockedData]) => {
                    if (!hoursData.success || !hoursData.schedule) {
                        throw new Error('Failed to load branch hours');
                    }

                    const daySchedule = hoursData.schedule.find(day => day.day === dayOfWeek);

                    if (!daySchedule || !daySchedule.is_open) {
                        timeSelect.innerHTML = '<option value="">❌ Branch closed on this day</option>';
                        timeError.textContent = 'The branch is closed on the selected date.';
                        timeError.style.display = 'block';
                        return;
                    }

                    // Generate time slots
                    const timeSlots = generateTimeSlots(daySchedule.open_time, daySchedule.close_time, 30); // 30-minute intervals for walk-ins

                    // Filter existing appointments for the date
                    const existingAppointments = appointmentsData.success ?
                        appointmentsData.appointments.filter(apt => apt.appointment_date === selectedDate) : [];

                    // Filter blocked times
                    const blockedTimes = blockedData.success ? blockedData.blocked_times : [];

                    // Get available slots
                    const availableSlots = filterAvailableTimeSlots(timeSlots, existingAppointments, blockedTimes);

                    if (availableSlots.length === 0) {
                        timeSelect.innerHTML = '<option value="">❌ No available time slots</option>';
                        timeError.textContent = 'No time slots available for this date.';
                        timeError.style.display = 'block';
                    } else {
                        timeSelect.innerHTML = '<option value="">-- Select time --</option>';
                        availableSlots.forEach(slot => {
                            const option = document.createElement('option');
                            option.value = slot.value;
                            option.textContent = slot.display;
                            timeSelect.appendChild(option);
                        });

                        timeSelect.disabled = false;
                        timeError.style.display = 'none';

                        showWalkInAlert('success', `Found ${availableSlots.length} available time slots for ${date.toLocaleDateString()}.`);
                    }
                })
                .catch(error => {
                    console.error('Error loading available times:', error);
                    timeSelect.innerHTML = '<option value="">❌ Error loading times</option>';
                    timeError.textContent = 'Unable to load available times.';
                    timeError.style.display = 'block';
                    showWalkInAlert('error', 'Failed to load available times. Please try again.');
                });
        }

        function loadAvailableBranchesForWalkInReferral() {
            fetch('../../src/controllers/ReferralController.php?action=getAvailableBranches')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const select = document.getElementById('walkin-referral-branch');
                        select.innerHTML = '<option value="">-- Select Branch --</option>';
                        data.branches.forEach(branch => {
                            const option = document.createElement('option');
                            option.value = branch.id;
                            option.textContent = branch.name;
                            select.appendChild(option);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error loading branches:', error);
                    showWalkInReferralAlert('error', 'Failed to load available branches.');
                });
        }

        function validateWalkInForm() {
            const fields = ['walkin-name', 'walkin-phone', 'walkin-date', 'walkin-time', 'walkin-treatment', 'walkin-priority'];
            let isValid = true;

            fields.forEach(fieldId => {
                if (!validateWalkInField(fieldId)) {
                    isValid = false;
                }
            });

            // Additional validations
            const phone = document.getElementById('walkin-phone').value.trim();
            if (phone && !phone.match(/^09\d{9}$/)) {
                showFieldError('walkin-phone', 'Please enter a valid Philippine mobile number (09XXXXXXXXX)');
                isValid = false;
            }

            const email = document.getElementById('walkin-email').value.trim();
            if (email && !email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
                showFieldError('walkin-email', 'Please enter a valid email address');
                isValid = false;
            }

            const selectedDate = document.getElementById('walkin-date').value;
            const today = new Date().toISOString().split('T')[0];
            if (selectedDate < today) {
                showFieldError('walkin-date', 'Appointment date cannot be in the past');
                isValid = false;
            }

            return isValid;
        }

        function validateWalkInField(fieldId) {
            const field = document.getElementById(fieldId);
            const value = field.value.trim();
            let isValid = true;
            let errorMessage = '';

            switch (fieldId) {
                case 'walkin-name':
                    if (!value) {
                        isValid = false;
                        errorMessage = 'Patient name is required';
                    } else if (value.length < 2) {
                        isValid = false;
                        errorMessage = 'Name must be at least 2 characters';
                    }
                    break;

                case 'walkin-phone':
                    if (!value) {
                        isValid = false;
                        errorMessage = 'Phone number is required';
                    }
                    break;

                case 'walkin-date':
                    if (!value) {
                        isValid = false;
                        errorMessage = 'Appointment date is required';
                    }
                    break;

                case 'walkin-time':
                    if (!value) {
                        isValid = false;
                        errorMessage = 'Appointment time is required';
                    }
                    break;

                case 'walkin-treatment':
                    if (!value) {
                        isValid = false;
                        errorMessage = 'Treatment type is required';
                    }
                    break;

                case 'walkin-priority':
                    if (!value) {
                        isValid = false;
                        errorMessage = 'Priority level is required';
                    }
                    break;
            }

            if (isValid) {
                clearFieldError(fieldId);
            } else {
                showFieldError(fieldId, errorMessage);
            }

            return isValid;
        }

        function showFieldError(fieldId, message) {
            const field = document.getElementById(fieldId);
            const errorDiv = document.getElementById(fieldId + '-error');

            field.style.borderColor = '#ef4444';
            field.style.backgroundColor = '#fef2f2';
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
        }

        function clearFieldError(fieldId) {
            const field = document.getElementById(fieldId);
            const errorDiv = document.getElementById(fieldId + '-error');

            field.style.borderColor = '#10b981';
            field.style.backgroundColor = '#f0fdf4';
            errorDiv.style.display = 'none';
        }

        // Alert functions for walk-in modals
        function showWalkInAlert(type, message) {
            const alertDiv = document.getElementById('walkin-alert');
            const iconSpan = document.getElementById('walkin-alert-icon');
            const messageSpan = document.getElementById('walkin-alert-message');

            alertDiv.className = `modal-alert ${type}`;

            const icons = {
                'success': '✅',
                'error': '❌',
                'warning': '⚠️',
                'info': 'ℹ️'
            };

            iconSpan.innerHTML = icons[type] || 'ℹ️';
            messageSpan.textContent = message;
            alertDiv.style.display = 'flex';

            if (type === 'success') {
                setTimeout(hideWalkInAlert, 4000);
            }
        }

        function hideWalkInAlert() {
            document.getElementById('walkin-alert').style.display = 'none';
        }

        function showWalkInReferralAlert(type, message) {
            const alertDiv = document.getElementById('walkin-referral-alert');
            const iconSpan = document.getElementById('walkin-referral-alert-icon');
            const messageSpan = document.getElementById('walkin-referral-alert-message');

            alertDiv.className = `modal-alert ${type}`;

            const icons = {
                'success': '✅',
                'error': '❌',
                'warning': '⚠️',
                'info': 'ℹ️'
            };

            iconSpan.innerHTML = icons[type] || 'ℹ️';
            messageSpan.textContent = message;
            alertDiv.style.display = 'flex';

            if (type === 'success') {
                setTimeout(hideWalkInReferralAlert, 4000);
            }
        }

        function hideWalkInReferralAlert() {
            document.getElementById('walkin-referral-alert').style.display = 'none';
        }

        // =================== END WALK-IN PATIENT MANAGEMENT ===================

        // =================== END REFERRAL MANAGEMENT ===================

        function logout() {
            if (!confirm('Are you sure you want to logout?')) {
                return;
            }

            fetch('../../src/controllers/AuthController.php?action=logout', {
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

        // Ensure global availability
        window.logout = logout;

        // Enhanced alert system for modern UI
        function showAlert(message, type = 'info', duration = 4000) {
            const alertContainer = document.getElementById('alert-container');
            if (!alertContainer) return;

            const alertId = 'alert-' + Date.now();

            const alertConfig = {
                'success': { icon: 'check-circle', color: '#10b981', bg: '#d1fae5' },
                'danger': { icon: 'exclamation-triangle', color: '#ef4444', bg: '#fee2e2' },
                'warning': { icon: 'exclamation-triangle', color: '#f59e0b', bg: '#fef3c7' },
                'info': { icon: 'info-circle', color: '#3b82f6', bg: '#dbeafe' }
            };

            const config = alertConfig[type] || alertConfig['info'];

            const alertElement = document.createElement('div');
            alertElement.id = alertId;
            alertElement.style.cssText = `
                background: ${config.bg};
                color: ${config.color};
                padding: 16px;
                border-radius: 12px;
                margin-bottom: 12px;
                border: 1px solid ${config.color}40;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
                display: flex;
                align-items: center;
                gap: 12px;
                font-size: 14px;
                font-weight: 500;
                transform: translateX(100%);
                transition: all 0.3s ease;
                backdrop-filter: blur(10px);
            `;

            alertElement.innerHTML = `
                <i class="fas fa-${config.icon}" style="font-size: 16px;"></i>
                <span style="flex: 1;">${message}</span>
                <button onclick="removeAlert('${alertId}')" style="background: none; border: none; color: ${config.color}; cursor: pointer; padding: 4px;">
                    <i class="fas fa-times"></i>
                </button>
            `;

            alertContainer.appendChild(alertElement);

            // Trigger animation
            setTimeout(() => {
                alertElement.style.transform = 'translateX(0)';
            }, 10);

            // Auto remove
            if (duration > 0) {
                setTimeout(() => {
                    removeAlert(alertId);
                }, duration);
            }
        }

        function removeAlert(alertId) {
            const alertElement = document.getElementById(alertId);
            if (alertElement) {
                alertElement.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    alertElement.remove();
                }, 300);
            }
        }
    </script>

    <!-- Modern Modals -->

    <!-- Walk-in Patient Modal -->
    <div id="walkin-modal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 800px;">
            <span class="close" onclick="closeWalkInModal()">&times;</span>
            <h2><i class="fas fa-walking" style="color: #10b981;"></i> Register Walk-in Patient</h2>

            <!-- Alert Area -->
            <div id="walkin-alert" style="display: none; padding: 12px; border-radius: 8px; margin: 16px 0;">
                <span id="walkin-alert-icon" style="margin-right: 8px;"></span>
                <span id="walkin-alert-message"></span>
            </div>

            <!-- Walk-in Form -->
            <form id="walkInForm" style="margin-top: 20px;">
                <!-- Patient Information Section -->
                <div
                    style="background: #f8fafc; padding: 20px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #10b981;">
                    <h4 style="margin: 0 0 16px 0; color: #1f2937; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-user" style="color: #10b981;"></i>
                        Patient Information
                    </h4>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px;">
                        <div class="form-group">
                            <label for="walkin-name" style="font-weight: 600; color: #374151;">Full Name <span
                                    style="color: #ef4444;">*</span></label>
                            <input type="text" id="walkin-name" name="name" required
                                style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;"
                                placeholder="Enter patient's full name">
                            <div id="walkin-name-error"
                                style="color: #ef4444; font-size: 12px; margin-top: 4px; display: none;"></div>
                        </div>

                        <div class="form-group">
                            <label for="walkin-phone" style="font-weight: 600; color: #374151;">Phone Number <span
                                    style="color: #ef4444;">*</span></label>
                            <input type="tel" id="walkin-phone" name="phone" required
                                style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;"
                                placeholder="09XXXXXXXXX">
                            <div id="walkin-phone-error"
                                style="color: #ef4444; font-size: 12px; margin-top: 4px; display: none;"></div>
                        </div>

                        <div class="form-group">
                            <label for="walkin-email" style="font-weight: 600; color: #374151;">Email Address</label>
                            <input type="email" id="walkin-email" name="email"
                                style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;"
                                placeholder="patient@example.com (optional)">
                            <div id="walkin-email-error"
                                style="color: #ef4444; font-size: 12px; margin-top: 4px; display: none;"></div>
                        </div>

                        <div class="form-group">
                            <label for="walkin-birthdate" style="font-weight: 600; color: #374151;">Birth Date</label>
                            <input type="date" id="walkin-birthdate" name="birthdate"
                                style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;"
                                max="<?php echo date('Y-m-d'); ?>">
                            <div id="walkin-birthdate-error"
                                style="color: #ef4444; font-size: 12px; margin-top: 4px; display: none;"></div>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 16px;">
                        <label for="walkin-address" style="font-weight: 600; color: #374151;">Address</label>
                        <textarea id="walkin-address" name="address" rows="2"
                            style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px; resize: vertical;"
                            placeholder="Complete address (optional)"></textarea>
                    </div>
                </div>

                <!-- Appointment Information Section -->
                <div
                    style="background: #f0f9ff; padding: 20px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #3b82f6;">
                    <h4 style="margin: 0 0 16px 0; color: #1f2937; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-calendar-plus" style="color: #3b82f6;"></i>
                        Appointment Details
                    </h4>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px;">
                        <div class="form-group">
                            <label for="walkin-date" style="font-weight: 600; color: #374151;">Appointment Date <span
                                    style="color: #ef4444;">*</span></label>
                            <input type="date" id="walkin-date" name="appointment_date" required
                                style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;"
                                min="<?php echo date('Y-m-d'); ?>">
                            <div id="walkin-date-error"
                                style="color: #ef4444; font-size: 12px; margin-top: 4px; display: none;"></div>
                        </div>

                        <div class="form-group">
                            <label for="walkin-time" style="font-weight: 600; color: #374151;">Appointment Time <span
                                    style="color: #ef4444;">*</span></label>
                            <select id="walkin-time" name="appointment_time" required
                                style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                                <option value="">-- Select date first --</option>
                            </select>
                            <div id="walkin-time-error"
                                style="color: #ef4444; font-size: 12px; margin-top: 4px; display: none;"></div>
                        </div>

                        <div class="form-group">
                            <label for="walkin-treatment" style="font-weight: 600; color: #374151;">Treatment Type <span
                                    style="color: #ef4444;">*</span></label>
                            <select id="walkin-treatment" name="treatment_type_id" required
                                style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                                <option value="">-- Select Treatment --</option>
                            </select>
                            <div id="walkin-treatment-error"
                                style="color: #ef4444; font-size: 12px; margin-top: 4px; display: none;"></div>
                        </div>

                        <div class="form-group">
                            <label for="walkin-priority" style="font-weight: 600; color: #374151;">Priority Level <span
                                    style="color: #ef4444;">*</span></label>
                            <select id="walkin-priority" name="priority" required
                                style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                                <option value="">-- Select Priority --</option>
                                <option value="low">Low Priority</option>
                                <option value="normal" selected>Normal Priority</option>
                                <option value="high">High Priority - Emergency</option>
                            </select>
                            <div id="walkin-priority-error"
                                style="color: #ef4444; font-size: 12px; margin-top: 4px; display: none;"></div>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 16px;">
                        <label for="walkin-notes" style="font-weight: 600; color: #374151;">Notes/Symptoms</label>
                        <textarea id="walkin-notes" name="notes" rows="3" maxlength="500"
                            style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px; resize: vertical;"
                            placeholder="Patient symptoms, complaints, or special instructions..."></textarea>
                        <div style="display: flex; justify-content: flex-end; margin-top: 4px;">
                            <small style="color: #64748b; font-size: 12px;">
                                <span id="walkin-notes-count">0</span>/500 characters
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div
                    style="display: flex; gap: 12px; justify-content: space-between; margin-top: 24px; padding-top: 16px; border-top: 1px solid #e2e8f0;">
                    <div>
                        <button type="button" onclick="openWalkInReferralModal()" class="btn-modern"
                            style="background: linear-gradient(135deg, #f59e0b, #d97706); color: white; padding: 12px 20px;">
                            <i class="fas fa-exchange-alt"></i> Refer to Another Branch
                        </button>
                    </div>
                    <div style="display: flex; gap: 12px;">
                        <button type="button" onclick="closeWalkInModal()" class="btn-modern"
                            style="background: #6b7280; color: white; padding: 12px 24px;">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" class="btn-modern"
                            style="background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 12px 24px;">
                            <i class="fas fa-plus"></i> Register Walk-in
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Walk-in Referral Modal -->
    <div id="walkin-referral-modal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 600px;">
            <span class="close" onclick="closeWalkInReferralModal()">&times;</span>
            <h2><i class="fas fa-exchange-alt" style="color: #f59e0b;"></i> Refer Walk-in Patient</h2>

            <!-- Alert Area -->
            <div id="walkin-referral-alert" style="display: none; padding: 12px; border-radius: 8px; margin: 16px 0;">
                <span id="walkin-referral-alert-icon" style="margin-right: 8px;"></span>
                <span id="walkin-referral-alert-message"></span>
            </div>

            <!-- Patient Summary -->
            <div id="walkin-referral-patient-info"
                style="background: #fef3c7; padding: 16px; border-radius: 8px; margin: 16px 0; border-left: 4px solid #f59e0b;">
                <!-- Will be populated by JavaScript -->
            </div>

            <!-- Referral Form -->
            <form id="walkInReferralForm" style="margin-top: 20px;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px;">
                    <div class="form-group">
                        <label for="walkin-referral-branch" style="font-weight: 600; color: #374151;">Target Branch
                            <span style="color: #ef4444;">*</span></label>
                        <select id="walkin-referral-branch" name="to_branch_id" required
                            style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                            <option value="">-- Select Branch --</option>
                        </select>
                        <div id="walkin-referral-branch-error"
                            style="color: #ef4444; font-size: 12px; margin-top: 4px; display: none;"></div>
                    </div>

                    <div class="form-group">
                        <label for="walkin-referral-priority" style="font-weight: 600; color: #374151;">Priority Level
                            <span style="color: #ef4444;">*</span></label>
                        <select id="walkin-referral-priority" name="priority" required
                            style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                            <option value="">-- Select Priority --</option>
                            <option value="low">Low Priority</option>
                            <option value="normal" selected>Normal Priority</option>
                            <option value="high">High Priority</option>
                        </select>
                        <div id="walkin-referral-priority-error"
                            style="color: #ef4444; font-size: 12px; margin-top: 4px; display: none;"></div>
                    </div>
                </div>

                <div class="form-group" style="margin-top: 16px;">
                    <label for="walkin-referral-reason" style="font-weight: 600; color: #374151;">Reason for Referral
                        <span style="color: #ef4444;">*</span></label>
                    <textarea id="walkin-referral-reason" name="reason" required rows="4" maxlength="500"
                        style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px; resize: vertical;"
                        placeholder="Please provide a detailed reason for referring this walk-in patient..."></textarea>
                    <div style="display: flex; justify-content: space-between; margin-top: 4px;">
                        <div id="walkin-referral-reason-error" style="color: #ef4444; font-size: 12px; display: none;">
                        </div>
                        <small style="color: #64748b; font-size: 12px;">
                            <span id="walkin-referral-reason-count">0</span>/500 characters
                        </small>
                    </div>
                </div>

                <div
                    style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px; padding-top: 16px; border-top: 1px solid #e2e8f0;">
                    <button type="button" onclick="closeWalkInReferralModal()" class="btn-modern"
                        style="background: #6b7280; color: white; padding: 12px 24px;">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn-modern"
                        style="background: linear-gradient(135deg, #f59e0b, #d97706); color: white; padding: 12px 24px;">
                        <i class="fas fa-paper-plane"></i> Create Referral
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Appointment Details Modal -->
    <div id="appointment-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2><i class="fas fa-calendar-check"></i> Appointment Details</h2>
            <div>
                <div class="appointment-detail-item">
                    <div class="appointment-detail-label">
                        <i class="fas fa-user"></i>
                        Patient Name
                    </div>
                    <div class="appointment-detail-value" id="modal-patient-name"></div>
                </div>

                <div class="appointment-detail-item">
                    <div class="appointment-detail-label">
                        <i class="fas fa-calendar"></i>
                        Appointment Date
                    </div>
                    <div class="appointment-detail-value" id="modal-appointment-date"></div>
                </div>

                <div class="appointment-detail-item">
                    <div class="appointment-detail-label">
                        <i class="fas fa-clock"></i>
                        Appointment Time
                    </div>
                    <div class="appointment-detail-value" id="modal-appointment-time"></div>
                </div>

                <div class="appointment-detail-item">
                    <div class="appointment-detail-label">
                        <i class="fas fa-info-circle"></i>
                        Status
                    </div>
                    <div class="appointment-detail-value" id="modal-status"></div>
                </div>

                <div class="appointment-detail-item notes-section">
                    <div class="appointment-detail-label">
                        <i class="fas fa-sticky-note"></i>
                        Notes
                    </div>
                    <div class="appointment-detail-value" id="modal-notes"></div>
                </div>

                <div class="appointment-detail-item">
                    <div class="appointment-detail-label">
                        <i class="fas fa-calendar-plus"></i>
                        Created
                    </div>
                    <div class="appointment-detail-value" id="modal-created"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Refer Appointment Modal -->
    <div id="refer-appointment-modal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 700px;">
            <span class="close" onclick="closeReferModal()">&times;</span>
            <h2><i class="fas fa-exchange-alt"></i> Refer Patient to Another Branch</h2>

            <!-- Alert Area -->
            <div id="refer-alert"
                style="display: none; padding: 12px; border-radius: 8px; margin: 16px 0; position: relative;">
                <span id="refer-alert-icon" style="margin-right: 8px;"></span>
                <span id="refer-alert-message"></span>
            </div>

            <!-- Appointment Details -->
            <div id="refer-appointment-details"
                style="background: #f8fafc; padding: 16px; border-radius: 8px; margin: 16px 0;">
                <!-- Will be populated by JavaScript -->
            </div>

            <!-- Referral Form -->
            <form id="createReferralForm" style="margin-top: 20px;">
                <input type="hidden" id="referral-appointment-id" name="appointment_id">

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px;">
                    <div class="form-group">
                        <label for="referral-branch" style="font-weight: 600; color: #374151;">Target Branch <span
                                style="color: #ef4444;">*</span></label>
                        <select id="referral-branch" name="to_branch_id" required
                            style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                            <option value="">-- Select Branch --</option>
                        </select>
                        <div id="referral-branch-error"
                            style="color: #ef4444; font-size: 12px; margin-top: 4px; display: none;"></div>
                    </div>

                    <div class="form-group">
                        <label for="referral-treatment" style="font-weight: 600; color: #374151;">Treatment Type</label>
                        <select id="referral-treatment" name="treatment_type_id" disabled
                            style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                            <option value="">-- Select Branch First --</option>
                        </select>
                        <div id="referral-treatment-error"
                            style="color: #ef4444; font-size: 12px; margin-top: 4px; display: none;"></div>
                    </div>

                    <div class="form-group">
                        <label for="referral-priority" style="font-weight: 600; color: #374151;">Priority Level <span
                                style="color: #ef4444;">*</span></label>
                        <select id="referral-priority" name="priority" required
                            style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                            <option value="">-- Select Priority --</option>
                            <option value="low">Low Priority</option>
                            <option value="normal" selected>Normal Priority</option>
                            <option value="high">High Priority</option>
                        </select>
                        <div id="referral-priority-error"
                            style="color: #ef4444; font-size: 12px; margin-top: 4px; display: none;"></div>
                    </div>
                </div>

                <div class="form-group" style="margin-top: 16px;">
                    <label for="referral-reason" style="font-weight: 600; color: #374151;">Reason for Referral <span
                            style="color: #ef4444;">*</span></label>
                    <textarea id="referral-reason" name="reason" required rows="4" maxlength="500"
                        style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; resize: vertical;"
                        placeholder="Please provide a detailed reason for this referral..."></textarea>
                    <div style="display: flex; justify-content: space-between; margin-top: 4px;">
                        <div id="referral-reason-error" style="color: #ef4444; font-size: 12px; display: none;"></div>
                        <small style="color: #64748b; font-size: 12px;">
                            <span id="current-count">0</span>/500 characters
                        </small>
                    </div>
                </div>

                <div
                    style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px; padding-top: 16px; border-top: 1px solid #e2e8f0;">
                    <button type="button" onclick="closeReferModal()" class="btn-modern"
                        style="background: #64748b; color: white; padding: 12px 24px;">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn-modern btn-primary" style="padding: 12px 24px;">
                        <i class="fas fa-paper-plane"></i> Create Referral
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Accept Referral Modal -->
    <div id="accept-referral-modal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 600px;">
            <span class="close" onclick="closeAcceptReferralModal()">&times;</span>
            <h2><i class="fas fa-check-circle" style="color: #10b981;"></i> Accept Patient-Approved Referral</h2>

            <!-- Patient Approval Status -->
            <div
                style="background: #f0fdf4; padding: 12px; border-radius: 8px; margin: 16px 0; border-left: 4px solid #10b981; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-user-check" style="color: #10b981; font-size: 16px;"></i>
                <span style="color: #15803d; font-weight: 600; font-size: 14px;">Patient has already approved this
                    referral</span>
            </div>

            <!-- Referral Details -->
            <div id="accept-referral-details"
                style="background: #f0f9ff; padding: 16px; border-radius: 8px; margin: 16px 0; border-left: 4px solid #3b82f6;">
                <!-- Will be populated by JavaScript -->
            </div>

            <!-- Alert Area -->
            <div id="accept-alert" style="display: none; padding: 12px; border-radius: 8px; margin: 16px 0;">
                <span id="accept-alert-icon" style="margin-right: 8px;"></span>
                <span id="accept-alert-message"></span>
            </div>

            <!-- Appointment Form -->
            <form id="acceptReferralForm" style="margin-top: 20px;">
                <input type="hidden" id="accept-referral-id" name="referral_id">

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px;">
                    <div class="form-group">
                        <label for="accept-appointment-date"
                            style="font-weight: 600; color: #374151; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-calendar-alt" style="color: #3b82f6;"></i>
                            Appointment Date <span style="color: #ef4444;">*</span>
                        </label>
                        <input type="date" id="accept-appointment-date" name="appointment_date" required
                            style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px; transition: all 0.3s ease;"
                            min="<?php echo date('Y-m-d'); ?>">
                        <div id="accept-appointment-date-error"
                            style="color: #ef4444; font-size: 12px; margin-top: 4px; display: none;"></div>
                    </div>

                    <div class="form-group">
                        <label for="accept-appointment-time"
                            style="font-weight: 600; color: #374151; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-clock" style="color: #3b82f6;"></i>
                            Appointment Time <span style="color: #ef4444;">*</span>
                        </label>
                        <select id="accept-appointment-time" name="appointment_time" required
                            style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px; transition: all 0.3s ease; background: white;">
                            <option value="">-- Select date first --</option>
                        </select>
                        <div id="accept-appointment-time-error"
                            style="color: #ef4444; font-size: 12px; margin-top: 4px; display: none;"></div>
                        <small style="color: #6b7280; font-size: 12px; margin-top: 4px; display: block;">Available time
                            slots based on branch operating hours</small>
                    </div>
                </div>

                <div class="form-group" style="margin-top: 16px;">
                    <label for="accept-notes"
                        style="font-weight: 600; color: #374151; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-sticky-note" style="color: #3b82f6;"></i>
                        Additional Notes <span style="color: #6b7280; font-weight: normal;">(Optional)</span>
                    </label>
                    <textarea id="accept-notes" name="notes" rows="3" maxlength="300"
                        style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px; resize: vertical; transition: all 0.3s ease;"
                        placeholder="Add any special instructions or notes for this appointment..."></textarea>
                    <div style="display: flex; justify-content: flex-end; margin-top: 4px;">
                        <small style="color: #64748b; font-size: 12px;">
                            <span id="accept-notes-count">0</span>/300 characters
                        </small>
                    </div>
                </div>

                <div
                    style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px; padding-top: 16px; border-top: 1px solid #e2e8f0;">
                    <button type="button" onclick="closeAcceptReferralModal()" class="btn-modern"
                        style="background: #6b7280; color: white; padding: 12px 24px; transition: all 0.3s ease;">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn-modern"
                        style="background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 12px 24px; transition: all 0.3s ease; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);">
                        <i class="fas fa-check"></i> Accept & Schedule
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Appointment Modal -->
    <div id="edit-appointment-modal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 700px;">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h2><i class="fas fa-edit" style="color: #054A91;"></i> Edit Appointment Details</h2>
            
            <!-- Alert Area -->
            <div id="edit-alert" style="display: none; padding: 12px; border-radius: 8px; margin: 16px 0;">
                <span id="edit-alert-icon" style="margin-right: 8px;"></span>
                <span id="edit-alert-message"></span>
            </div>

            <!-- Edit Form -->
            <form id="editAppointmentForm" style="margin-top: 20px;">
                <input type="hidden" id="edit-appointment-id" name="appointment_id">
                
                <div class="form-group" style="margin-bottom: 16px;">
                    <label for="edit-patient-name" style="font-weight: 600; color: #374151;">
                        <i class="fas fa-user"></i> Patient Name <span style="color: #ef4444;">*</span>
                    </label>
                    <input type="text" id="edit-patient-name" name="patient_name" required maxlength="100"
                           style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;"
                           placeholder="Enter patient's full name">
                    <div id="edit-patient-name-error" style="color: #ef4444; font-size: 12px; display: none;"></div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                    <div class="form-group">
                        <label for="edit-appointment-date" style="font-weight: 600; color: #374151;">
                            <i class="fas fa-calendar"></i> Appointment Date <span style="color: #ef4444;">*</span>
                        </label>
                        <input type="date" id="edit-appointment-date" name="appointment_date" required
                               style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit-appointment-time" style="font-weight: 600; color: #374151;">
                            <i class="fas fa-clock"></i> Appointment Time <span style="color: #ef4444;">*</span>
                        </label>
                        <select id="edit-appointment-time" name="appointment_time" required
                                style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                            <option value="">Select time</option>
                        </select>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label for="edit-treatment-type" style="font-weight: 600; color: #374151;">
                        <i class="fas fa-tooth"></i> Treatment Type <span style="color: #ef4444;">*</span>
                    </label>
                    <select id="edit-treatment-type" name="treatment_type_id" required
                            style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                        <option value="">Select treatment</option>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label for="edit-notes" style="font-weight: 600; color: #374151;">
                        <i class="fas fa-notes-medical"></i> Notes
                    </label>
                    <textarea id="edit-notes" name="notes" rows="4" maxlength="500"
                              style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px; resize: vertical;"
                              placeholder="Additional notes or special requirements..."></textarea>
                    <div style="display: flex; justify-content: space-between; margin-top: 4px;">
                        <div id="edit-notes-error" style="color: #ef4444; font-size: 12px; display: none;"></div>
                        <small style="color: #64748b; font-size: 12px;">
                            <span id="edit-notes-count">0</span>/500 characters
                        </small>
                    </div>
                </div>

                <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px; padding-top: 16px; border-top: 1px solid #e2e8f0;">
                    <button type="button" onclick="closeEditModal()" class="btn-modern"
                            style="background: #6b7280; color: white; padding: 12px 24px;">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn-modern"
                            style="background: linear-gradient(135deg, #054A91, #3E7CB1); color: white; padding: 12px 24px;">
                        <i class="fas fa-save"></i> Update Appointment
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reject Referral Modal -->
    <div id="reject-referral-modal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 600px;">
            <span class="close" onclick="closeRejectReferralModal()">&times;</span>
            <h2><i class="fas fa-times-circle" style="color: #ef4444;"></i> Reject Patient-Approved Referral</h2>

            <!-- Warning about rejecting patient-approved referral -->
            <div
                style="background: #fef3cd; padding: 12px; border-radius: 8px; margin: 16px 0; border-left: 4px solid #fbbf24; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-exclamation-triangle" style="color: #f59e0b; font-size: 16px;"></i>
                <span style="color: #92400e; font-weight: 600; font-size: 14px;">⚠️ This referral was already approved
                    by the patient</span>
            </div>

            <!-- Referral Details -->
            <div id="reject-referral-details"
                style="background: #fef2f2; padding: 16px; border-radius: 8px; margin: 16px 0; border-left: 4px solid #ef4444;">
                <!-- Will be populated by JavaScript -->
            </div>

            <!-- Alert Area -->
            <div id="reject-alert" style="display: none; padding: 12px; border-radius: 8px; margin: 16px 0;">
                <span id="reject-alert-icon" style="margin-right: 8px;"></span>
                <span id="reject-alert-message"></span>
            </div>

            <!-- Rejection Form -->
            <form id="rejectReferralForm" style="margin-top: 20px;">
                <input type="hidden" id="reject-referral-id" name="referral_id">

                <div class="form-group">
                    <label for="reject-reason"
                        style="font-weight: 600; color: #374151; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-exclamation-triangle" style="color: #f59e0b;"></i>
                        Reason for Rejection <span style="color: #ef4444;">*</span>
                    </label>
                    <div style="margin: 8px 0;">
                        <p
                            style="color: #6b7280; font-size: 14px; margin: 0; background: #f8fafc; padding: 12px; border-radius: 6px; border-left: 3px solid #f59e0b;">
                            <i class="fas fa-info-circle" style="color: #3b82f6;"></i>
                            Please provide a clear and professional reason for rejecting this referral. This will help
                            the referring branch understand your decision and improve future referrals.
                        </p>
                    </div>
                    <textarea id="reject-reason" name="rejection_reason" required rows="4" maxlength="500"
                        style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px; resize: vertical; transition: all 0.3s ease;"
                        placeholder="Please explain why this referral cannot be accepted..."></textarea>
                    <div style="display: flex; justify-content: space-between; margin-top: 4px;">
                        <div id="reject-reason-error" style="color: #ef4444; font-size: 12px; display: none;"></div>
                        <small style="color: #64748b; font-size: 12px;">
                            <span id="reject-reason-count">0</span>/500 characters
                        </small>
                    </div>
                </div>

                <!-- Common Rejection Reasons (Quick Selection) -->
                <div class="form-group" style="margin-top: 16px;">
                    <label style="font-weight: 600; color: #374151; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-list" style="color: #6b7280;"></i>
                        Quick Reasons <span style="color: #6b7280; font-weight: normal;">(Optional - Click to
                            use)</span>
                    </label>
                    <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px;">
                        <button type="button" class="quick-reason-btn"
                            onclick="setQuickReason('Branch capacity fully booked for the next 2 weeks')">
                            <i class="fas fa-calendar-times"></i> Fully Booked
                        </button>
                        <button type="button" class="quick-reason-btn"
                            onclick="setQuickReason('Required specialist not available at this branch')">
                            <i class="fas fa-user-md"></i> Specialist Unavailable
                        </button>
                        <button type="button" class="quick-reason-btn"
                            onclick="setQuickReason('Treatment requires equipment not available at this location')">
                            <i class="fas fa-tools"></i> Equipment Unavailable
                        </button>
                        <button type="button" class="quick-reason-btn"
                            onclick="setQuickReason('Patient should be referred to specialized clinic for this condition')">
                            <i class="fas fa-hospital"></i> Specialized Care Needed
                        </button>
                        <button type="button" class="quick-reason-btn"
                            onclick="setQuickReason('Insufficient information provided in referral request')">
                            <i class="fas fa-info-circle"></i> Insufficient Information
                        </button>
                    </div>
                </div>

                <div
                    style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px; padding-top: 16px; border-top: 1px solid #e2e8f0;">
                    <button type="button" onclick="closeRejectReferralModal()" class="btn-modern"
                        style="background: #6b7280; color: white; padding: 12px 24px; transition: all 0.3s ease;">
                        <i class="fas fa-arrow-left"></i> Back
                    </button>
                    <button type="submit" class="btn-modern"
                        style="background: linear-gradient(135deg, #ef4444, #dc2626); color: white; padding: 12px 24px; transition: all 0.3s ease; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);">
                        <i class="fas fa-times"></i> Reject Referral
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Success/Error Alert System -->
    <div id="alert-container" style="position: fixed; top: 20px; right: 20px; z-index: 1100; max-width: 400px;">
        <!-- Alerts will be dynamically inserted here -->
    </div>
</body>

</html>