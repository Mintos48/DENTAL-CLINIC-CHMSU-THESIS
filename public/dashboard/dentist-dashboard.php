<?php
require_once '../../src/config/constants.php';
require_once '../../src/config/session.php';
require_once '../../src/config/database.php';

// Only dentists, staff and admins may access this page
if (!isLoggedIn() || (getSessionRole() !== ROLE_DENTIST && getSessionRole() !== ROLE_STAFF && getSessionRole() !== ROLE_ADMIN)) {
    header('Location: ../auth/login.php');
    exit();
}

$userName = getSessionName();
$userBranchId = getSessionBranchId();

// Fetch actual clinic name from database
$clinicName = 'Unknown Clinic';
try {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT name FROM branches WHERE id = ?");
    $stmt->bind_param("i", $userBranchId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $clinicName = $row['name'];
    }
    $stmt->close();
} catch (Exception $e) {
    error_log("Error fetching clinic name: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Dentist Dashboard</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Modern Dashboard Design - Matching Staff Dashboard */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #054A91 0%, #3E7CB1 100%);
            min-height: 100vh;
            color: #1a202c;
            line-height: 1.6;
        }

        .dentist-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 24px;
        }

        /* Top Header - Glassmorphism Style */
        .top {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            padding: 20px 28px;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .top h2 {
            font-size: 24px;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 6px;
        }

        .top > div:first-child > div {
            font-size: 14px;
            color: #64748b;
            font-weight: 500;
        }

        /* Cards Grid */
        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .card[onclick]:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.2);
            cursor: pointer;
        }

        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        }

        .card h4 {
            font-size: 13px;
            font-weight: 600;
            color: #64748b;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .card > div:first-of-type {
            font-size: 32px;
            font-weight: 800;
            color: #054A91;
        }

        /* Tab Navigation */
        .tab-nav {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 8px;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            flex-wrap: wrap;
        }

        .tab-nav button {
            padding: 12px 20px;
            border: none;
            background: transparent;
            cursor: pointer;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            color: #64748b;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .tab-nav button:hover {
            background: #e2e8f0;
            color: #374151;
        }

        .tab-nav button.active {
            background: #054A91;
            color: white;
        }

        /* View Tabs - For sub-navigation within tabs */
        .view-tabs {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .view-tab-btn {
            padding: 12px 24px;
            border: none;
            background: transparent;
            color: #64748b;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            border-bottom: 3px solid transparent;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .view-tab-btn:hover {
            color: #054A91;
            background: rgba(5, 74, 145, 0.05);
        }

        .view-tab-btn.active {
            color: #054A91;
            border-bottom-color: #054A91;
            background: rgba(5, 74, 145, 0.08);
        }

        .view-tab-btn i {
            font-size: 16px;
        }

        /* Tab Content */
        .tab-content {
            display: none;
            animation: fadeInUp 0.3s ease;
        }

        .tab-content.active {
            display: block;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Tables */
        table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        th, td {
            padding: 16px 20px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        th {
            background: #f8fafc;
            font-weight: 600;
            color: #374151;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        tbody tr:hover {
            background: #f8fafc;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        /* Buttons */
        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 10px 16px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .btn-primary {
            background: #054A91;
            color: #fff;
        }

        .btn-primary:hover {
            background: #3E7CB1;
        }

        .btn-success {
            background: #10b981;
            color: #fff;
        }

        .btn-success:hover {
            background: #059669;
        }

        .btn-warning {
            background: #f59e0b;
            color: #fff;
        }

        .btn-warning:hover {
            background: #d97706;
        }

        .btn-danger {
            background: #ef4444;
            color: #fff;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        .btn-warning {
            background: #f59e0b;
            color: #fff;
        }

        .btn-warning:hover {
            background: #d97706;
        }

        /* Forms */
        .prescription-form {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 24px;
            border-radius: 12px;
            margin: 20px 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .medication-item {
            background: #fff;
            padding: 20px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin: 12px 0;
        }

        .form-group {
            margin: 16px 0;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
            font-size: 14px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s ease;
            background: white;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #054A91;
            box-shadow: 0 0 0 3px rgba(5, 74, 145, 0.1);
        }

        /* Staff Grid */
        .staff-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 20px;
        }

        .staff-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .staff-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        }

        .staff-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            padding-bottom: 16px;
            border-bottom: 1px solid #e2e8f0;
        }

        /* Modals */
        /* Modern Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.65);
            backdrop-filter: blur(10px);
            z-index: 1050;
            animation: modalBackdropFadeIn 0.3s ease;
            padding: 40px 20px;
            overflow-y: auto;
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            position: relative;
            background: white;
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25), 0 10px 25px rgba(0, 0, 0, 0.15);
            max-width: 650px;
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
                transform: translateY(-30px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .modal-header {
            margin: 0;
            padding: 28px 32px;
            background: linear-gradient(135deg, #054A91 0%, #3E7CB1 100%);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 20px 20px 0 0;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .modal-header h3 {
            font-size: 22px;
            font-weight: 700;
            color: white;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .modal-header h3 i {
            font-size: 24px;
            opacity: 0.9;
        }

        /* Modal Body - Scrollable */
        .modal-body {
            padding: 32px;
            overflow-y: auto;
            flex: 1;
        }

        .modal-body::-webkit-scrollbar {
            width: 8px;
        }

        .modal-body::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        .modal-body::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        .modal-body::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        .close {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            font-size: 24px;
            cursor: pointer;
            color: rgba(255, 255, 255, 0.9);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }

        .close:hover {
            background: rgba(239, 68, 68, 0.9);
            color: white;
            transform: rotate(90deg) scale(1.1);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
        }

        /* Loading State */
        .loading-spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.6s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
            border-radius: 4px;
        }

        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        /* Status Badges */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
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

        /* Prescription Indicators */
        .prescription-needed {
            color: #ff9800;
            font-weight: bold;
            font-size: 0.8rem;
        }

        .prescription-needed i {
            margin-right: 3px;
        }

        .info-box {
            background: #e8f4fd;
            border: 1px solid #b3d8f2;
            padding: 12px;
            margin: 15px 0;
            border-radius: 5px;
        }

        .info-box i {
            color: #1976d2;
            margin-right: 8px;
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
            opacity: 0.5;
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

        /* Alert Messages */
        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideDown 0.3s ease;
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

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .alert-info {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }

        .alert i {
            font-size: 20px;
        }

        /* Focus States for Accessibility */
        button:focus-visible,
        input:focus-visible,
        select:focus-visible,
        textarea:focus-visible {
            outline: 2px solid #054A91;
            outline-offset: 2px;
        }

        /* Smooth Scrolling */
        html {
            scroll-behavior: smooth;
        }

        /* Print Styles */
        @media print {
            body {
                background: white;
            }

            .top,
            .tab-nav,
            .btn,
            button {
                display: none !important;
            }

            .card,
            table {
                box-shadow: none;
                border: 1px solid #ddd;
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .dentist-container {
                padding: 16px;
            }

            .tab-nav {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            .tab-nav button {
                flex-shrink: 0;
            }

            .cards {
                grid-template-columns: 1fr;
            }

            table {
                font-size: 13px;
                display: block;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            th, td {
                padding: 12px 10px;
            }

            .top {
                flex-direction: column;
                gap: 16px;
                align-items: flex-start;
            }

            .modal-content {
                width: 95%;
                padding: 24px;
            }
        }

        @media (max-width: 480px) {
            .dentist-container {
                padding: 12px;
            }

            .top h2 {
                font-size: 20px;
            }

            .card {
                padding: 20px;
            }

            .btn {
                padding: 8px 14px;
                font-size: 13px;
            }
        }

        /* Dark Mode Support (optional for future) */
        @media (prefers-color-scheme: dark) {
            /* Can be implemented later if needed */
        }

        /* ============================================ */
        /* Clinic Status Management Styles */
        /* ============================================ */
        .status-section {
            background: #f9fafb;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .status-section h5 {
            margin: 0 0 20px 0;
            color: #374151;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .status-display {
            margin-bottom: 20px;
        }

        .status-badge {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 20px;
            border-radius: 10px;
            font-size: 16px;
        }

        .status-badge i {
            font-size: 32px;
        }

        .status-badge strong {
            display: block;
            font-size: 18px;
            margin-bottom: 4px;
        }

        .status-badge small {
            display: block;
            opacity: 0.9;
        }

        .status-badge.status-open {
            background: #d1fae5;
            color: #065f46;
        }

        .status-badge.status-open i {
            color: #10b981;
        }

        .status-badge.status-closed {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-badge.status-closed i {
            color: #ef4444;
        }

        .status-badge.status-busy {
            background: #fef3c7;
            color: #92400e;
        }

        .status-badge.status-busy i {
            color: #f59e0b;
        }

        .status-badge.status-fully_booked {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-badge.status-fully_booked i {
            color: #3b82f6;
        }

        .status-controls {
            margin-top: 20px;
        }

        .status-controls label {
            display: block;
            margin-bottom: 12px;
            font-weight: 600;
            color: #374151;
        }

        .status-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin-bottom: 15px;
        }

        .status-btn {
            padding: 12px 16px;
            border: 2px solid transparent;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .status-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .status-btn.status-open {
            background: #d1fae5;
            color: #065f46;
        }

        .status-btn.status-open:hover {
            background: #a7f3d0;
        }

        .status-btn.status-closed {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-btn.status-closed:hover {
            background: #fecaca;
        }

        .status-btn.status-busy {
            background: #fef3c7;
            color: #92400e;
        }

        .status-btn.status-busy:hover {
            background: #fde68a;
        }

        .status-btn.status-fully-booked {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-btn.status-fully-booked:hover {
            background: #bfdbfe;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .upcoming-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .upcoming-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px;
            background: white;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }

        .upcoming-date {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            color: #374151;
            flex: 1;
        }

        .upcoming-date i {
            color: #6b7280;
        }

        .upcoming-status {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .upcoming-status .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            width: fit-content;
        }

        .upcoming-status .badge.open {
            background: #d1fae5;
            color: #065f46;
        }

        .upcoming-status .badge.closed {
            background: #fee2e2;
            color: #991b1b;
        }

        .upcoming-status .badge.busy {
            background: #fef3c7;
            color: #92400e;
        }

        .upcoming-status .badge.fully_booked {
            background: #dbeafe;
            color: #1e40af;
        }

        .upcoming-status small {
            color: #6b7280;
            font-size: 13px;
        }

        .btn-remove {
            padding: 8px 12px;
            background: #fee2e2;
            color: #991b1b;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-remove:hover {
            background: #fecaca;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #9ca3af;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 10px;
            display: block;
        }

        .loading {
            text-align: center;
            padding: 20px;
            color: #6b7280;
        }

        .info-box {
            background: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .info-box p {
            margin: 0;
            color: #1e40af;
        }
    </style>
</head>
<body>
    <div class="dentist-container">
        <!-- Professional Header -->
        <div class="top">
            <div style="display: flex; align-items: center; gap: 16px;">
                <div style="width: 48px; height: 48px; border-radius: 12px; background: linear-gradient(135deg, #054A91, #3E7CB1); display: flex; align-items: center; justify-content: center; color: white; font-size: 24px;">
                    <i class="fas fa-tooth"></i>
                </div>
                <div>
                    <h2><?php echo htmlspecialchars($clinicName); ?></h2>
                    <div>Welcome, <strong><?php echo htmlspecialchars($userName); ?></strong> • Dentist Dashboard</div>
                </div>
            </div>
            <div>
                <button class="btn btn-danger" onclick="logout()"><i class="fas fa-sign-out-alt"></i> Logout</button>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="tab-nav">
            <button class="tab-btn active" onclick="showTab('analytics')"><i class="fas fa-chart-line"></i> Analytics</button>
            <button class="tab-btn" onclick="showTab('prescriptions')"><i class="fas fa-prescription"></i> Prescriptions</button>
            <button class="tab-btn" onclick="showTab('staff')"><i class="fas fa-users"></i> Staff Management</button>
            <button class="tab-btn" onclick="showTab('treatments')"><i class="fas fa-tooth"></i> Treatment Management</button>
            <button class="tab-btn" onclick="showTab('appointments')"><i class="fas fa-calendar-alt"></i> Appointments</button>
            <button class="tab-btn" onclick="showTab('credentials')"><i class="fas fa-certificate"></i> Credentials</button>
            <button class="tab-btn" onclick="showTab('clinic-status')"><i class="fas fa-store"></i> Clinic Status</button>
        </div>

        <!-- Analytics Tab -->
        <div id="analytics-tab" class="tab-content active">
            <!-- Summary Cards -->
            <div class="cards">
                <div class="card">
                    <h4>Branch Revenue (This Month)</h4>
                    <div id="branch-revenue-month" style="font-size:1.6rem;font-weight:700">Loading...</div>
                    <div style="color:#666;margin-top:6px;font-size:0.9rem">Total branch revenue this month</div>
                </div>
                <div class="card">
                    <h4>Total Patients</h4>
                    <div id="total-patients" style="font-size:1.6rem;font-weight:700">...</div>
                    <div style="color:#666;margin-top:6px;font-size:0.9rem">Active patients in branch</div>
                </div>
                <div class="card">
                    <h4>Today's Appointments</h4>
                    <div id="appointments-today" style="font-size:1.6rem;font-weight:700">...</div>
                    <div style="color:#666;margin-top:6px;font-size:0.9rem">Pending / Approved / Completed</div>
                </div>
                <div class="card">
                    <h4>Pending Approvals</h4>
                    <div id="pending-count" style="font-size:1.6rem;font-weight:700">...</div>
                    <div style="color:#666;margin-top:6px;font-size:0.9rem">Appointments awaiting approval</div>
                </div>
                <div class="card">
                    <h4>Total Prescriptions</h4>
                    <div id="total-prescriptions" style="font-size:1.6rem;font-weight:700">...</div>
                    <div style="color:#666;margin-top:6px;font-size:0.9rem">Prescriptions issued this month</div>
                </div>
                <div class="card" style="border-left: 4px solid #f59e0b;cursor:pointer;" onclick="showTab('prescriptions'); loadAppointmentsNeedingPrescriptions();">
                    <h4>Need Prescriptions</h4>
                    <div id="need-prescriptions" style="font-size:1.6rem;font-weight:700;color:#f59e0b">...</div>
                    <div style="color:#666;margin-top:6px;font-size:0.9rem">Approved appointments needing prescriptions (click to view)</div>
                </div>
                <div class="card">
                    <h4>Staff Count</h4>
                    <div id="staff-count" style="font-size:1.6rem;font-weight:700">...</div>
                    <div style="color:#666;margin-top:6px;font-size:0.9rem">Active staff members</div>
                </div>
            </div>

            <!-- Detailed Analytics -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin:20px 0">
                <!-- Recent Patients -->
                <div class="card">
                    <h4>Recent Patients</h4>
                    <div id="recent-patients">
                        <div style="text-align:center;color:#666;padding:20px">Loading...</div>
                    </div>
                </div>

                <!-- Top Treatments -->
                <div class="card">
                    <h4>Popular Treatments</h4>
                    <div id="popular-treatments">
                        <div style="text-align:center;color:#666;padding:20px">Loading...</div>
                    </div>
                </div>
            </div>

            <!-- Schedule Overview -->
            <div class="card">
                <h4>My Schedule Overview</h4>
                <div id="schedule-overview">
                    <div style="text-align:center;color:#666;padding:20px">Loading schedule...</div>
                </div>
            </div>
        </div>

        <!-- Prescriptions Tab -->
        <div id="prescriptions-tab" class="tab-content">
            <div class="card">
                <h4>Prescription Management</h4>
                
                <!-- Prescription View Tabs -->
                <div class="view-tabs" style="margin: 20px 0; border-bottom: 2px solid #e2e8f0;">
                    <button class="view-tab-btn active" onclick="switchPrescriptionView('today')">
                        <i class="fas fa-calendar-day"></i> Today's Prescriptions
                    </button>
                    <button class="view-tab-btn" onclick="switchPrescriptionView('all')">
                        <i class="fas fa-calendar-alt"></i> All Prescriptions
                    </button>
                    <button class="view-tab-btn" onclick="switchPrescriptionView('pending')">
                        <i class="fas fa-clock"></i> Pending Prescriptions
                    </button>
                </div>
                
                <!-- Action Buttons -->
                <div style="margin:15px 0;display:flex;gap:10px">
                    <button class="btn btn-primary" onclick="loadAppointmentsNeedingPrescriptions()">
                        <i class="fas fa-clipboard-list"></i> Appointments Needing Prescriptions
                    </button>
                    <button class="btn btn-primary" onclick="refreshPrescriptions()">
                        <i class="fas fa-sync"></i> Refresh
                    </button>
                </div>
                
                <!-- Today's Prescriptions View -->
                <div id="prescriptions-today-view" class="prescription-view">
                    <h5 style="margin: 20px 0 10px 0; color: #475569;">
                        <i class="fas fa-calendar-check"></i> Prescriptions Created Today
                    </h5>
                    <div id="prescriptions-today-list">
                        <!-- Will be populated by JavaScript -->
                    </div>
                </div>
                
                <!-- All Prescriptions View -->
                <div id="prescriptions-all-view" class="prescription-view" style="display: none;">
                    <h5 style="margin: 20px 0 10px 0; color: #475569;">
                        <i class="fas fa-file-medical"></i> All Prescriptions
                    </h5>
                    <div id="prescriptions-all-list">
                        <!-- Will be populated by JavaScript -->
                    </div>
                </div>
                
                <!-- Pending Prescriptions View -->
                <div id="prescriptions-pending-view" class="prescription-view" style="display: none;">
                    <h5 style="margin: 20px 0 10px 0; color: #475569;">
                        <i class="fas fa-hourglass-half"></i> Appointments Needing Prescriptions
                    </h5>
                    <div id="appointments-needing-prescriptions">
                        <!-- Will be populated by JavaScript -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Staff Management Tab -->
        <div id="staff-tab" class="tab-content">
            <div class="card">
                <h4>Staff Management - <?php echo htmlspecialchars(getBranchName($userBranchId)); ?></h4>
                <div style="margin:15px 0;display:flex;gap:10px">
                    <button class="btn btn-primary" onclick="showAddStaffModal()">
                        <i class="fas fa-user-plus"></i> Add New Staff
                    </button>
                    <button class="btn btn-primary" onclick="loadBranchStaff()">
                        <i class="fas fa-sync"></i> Reload Staff
                    </button>
                </div>
                
                <div id="staff-grid" class="staff-grid" style="margin:20px 0">
                    <!-- Will be populated by JavaScript -->
                </div>
            </div>
        </div>

        <!-- Treatment Management Tab -->
        <div id="treatments-tab" class="tab-content">
            <div class="card">
                <h4>Treatment & Services Management</h4>
                <div style="margin:15px 0;display:flex;gap:10px">
                    <button class="btn btn-primary" onclick="showAddTreatmentModal()">
                        <i class="fas fa-plus"></i> Add New Treatment
                    </button>
                    <button class="btn btn-primary" onclick="loadBranchTreatments()">
                        <i class="fas fa-sync"></i> Reload Treatments
                    </button>
                </div>
                
                <div id="treatments-list" style="margin-top:20px">
                    <div style="text-align:center;color:#666;padding:20px">Loading treatments...</div>
                </div>
            </div>
        </div>

        <!-- Appointments Tab -->
        <div id="appointments-tab" class="tab-content">
            <div class="card">
                <h4>Appointment Management</h4>
                
                <!-- Appointment View Tabs -->
                <div class="view-tabs" style="margin: 20px 0; border-bottom: 2px solid #e2e8f0;">
                    <button class="view-tab-btn active" onclick="switchAppointmentView('today')">
                        <i class="fas fa-calendar-day"></i> Today's Appointments
                    </button>
                    <button class="view-tab-btn" onclick="switchAppointmentView('all')">
                        <i class="fas fa-calendar-alt"></i> All Appointments
                    </button>
                    <button class="view-tab-btn" onclick="switchAppointmentView('schedule')">
                        <i class="fas fa-calendar-week"></i> My Schedule
                    </button>
                </div>
                
                <!-- Action Buttons -->
                <div style="margin:15px 0;display:flex;gap:10px;align-items:center">
                    <label style="font-weight: 600; color: #475569;">Select Date:</label>
                    <input type="date" id="appointment-date-filter" value="<?php echo date('Y-m-d'); ?>" 
                           style="padding: 8px 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;" />
                    <button class="btn btn-primary" onclick="refreshAppointments()">
                        <i class="fas fa-sync"></i> Refresh
                    </button>
                </div>
                
                <!-- Today's Appointments View -->
                <div id="appointments-today-view" class="appointment-view">
                    <h5 style="margin: 20px 0 10px 0; color: #475569;">
                        <i class="fas fa-calendar-check"></i> Today's Appointments - <?php echo date('F d, Y'); ?>
                    </h5>
                    <div id="appointments-today-list">
                        <div style="text-align:center;color:#666;padding:20px">Loading today's appointments...</div>
                    </div>
                </div>
                
                <!-- All Appointments View -->
                <div id="appointments-all-view" class="appointment-view" style="display: none;">
                    <h5 style="margin: 20px 0 10px 0; color: #475569;">
                        <i class="fas fa-calendar"></i> All Appointments
                    </h5>
                    <div id="daily-appointments">
                        <div style="text-align:center;color:#666;padding:20px">Select a date and click "Refresh"</div>
                    </div>
                </div>
                
                <!-- My Schedule View -->
                <div id="appointments-schedule-view" class="appointment-view" style="display: none;">
                    <h5 style="margin: 20px 0 10px 0; color: #475569;">
                        <i class="fas fa-user-clock"></i> My Personal Schedule
                    </h5>
                    <div id="my-schedule">
                        <div style="text-align:center;color:#666;padding:20px">Loading your schedule...</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Credentials Tab -->
        <div id="credentials-tab" class="tab-content">
            <div class="card">
                <h4>Dentist & Clinic Credentials</h4>
                
                <!-- Dentist Credentials -->
                <div style="background:#f9f9f9;padding:20px;border-radius:8px;margin:15px 0">
                    <h5>Personal Credentials</h5>
                    <form id="dentist-credentials-form" onsubmit="saveDentistCredentials(event)">
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;margin:10px 0">
                            <div class="form-group">
                                <label>License Number:</label>
                                <input type="text" id="license-number" required />
                            </div>
                            <div class="form-group">
                                <label>Specialization:</label>
                                <select id="dentist-specialization">
                                    <option value="">Select specialization</option>
                                    <option value="General Dentistry">General Dentistry</option>
                                    <option value="Orthodontics">Orthodontics</option>
                                    <option value="Oral Surgery">Oral Surgery</option>
                                    <option value="Pediatric Dentistry">Pediatric Dentistry</option>
                                    <option value="Periodontics">Periodontics</option>
                                    <option value="Endodontics">Endodontics</option>
                                    <option value="Prosthodontics">Prosthodontics</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Years of Experience:</label>
                                <input type="number" id="experience-years" min="0" />
                            </div>
                            <div class="form-group">
                                <label>Education:</label>
                                <input type="text" id="education" placeholder="e.g., DDS, University Name" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Professional Bio:</label>
                            <textarea id="professional-bio" rows="4" placeholder="Brief description of your expertise and approach..."></textarea>
                        </div>
                        <div class="form-group">
                            <label>License Certificate (PDF/Image):</label>
                            <input type="file" id="license-file" accept=".pdf,.jpg,.jpeg,.png" />
                        </div>
                        <button type="submit" class="btn btn-primary">Save Personal Credentials</button>
                    </form>
                </div>
                
                <!-- Clinic Credentials -->
                <div style="background:#f9f9f9;padding:20px;border-radius:8px;margin:15px 0">
                    <h5>Clinic Credentials</h5>
                    <form id="clinic-credentials-form" onsubmit="saveClinicCredentials(event)">
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;margin:10px 0">
                            <div class="form-group">
                                <label>Clinic License Number:</label>
                                <input type="text" id="clinic-license" required />
                            </div>
                            <div class="form-group">
                                <label>Business Permit Number:</label>
                                <input type="text" id="business-permit" />
                            </div>
                            <div class="form-group">
                                <label>Accreditations:</label>
                                <input type="text" id="accreditations" placeholder="e.g., DOH, PhilHealth" />
                            </div>
                            <div class="form-group">
                                <label>Established Year:</label>
                                <input type="number" id="established-year" min="1900" max="<?php echo date('Y'); ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Services Offered:</label>
                            <textarea id="services-offered" rows="3" placeholder="List of dental services provided..."></textarea>
                        </div>
                        <div class="form-group">
                            <label>Clinic Photos:</label>
                            <input type="file" id="clinic-photos" accept=".jpg,.jpeg,.png" multiple />
                            <small style="color:#666">You can select multiple images</small>
                        </div>
                        <div class="form-group">
                            <label>Certifications (PDF/Images):</label>
                            <input type="file" id="clinic-certifications" accept=".pdf,.jpg,.jpeg,.png" multiple />
                        </div>
                        <button type="submit" class="btn btn-primary">Save Clinic Credentials</button>
                    </form>
                </div>
                
                <!-- Current Credentials Display -->
                <div id="current-credentials" style="margin-top:20px">
                    <h5>Current Credentials</h5>
                    <div id="credentials-display">
                        <div style="text-align:center;color:#666;padding:20px">Click "Load Current Credentials" to view</div>
                    </div>
                    <button class="btn btn-primary" onclick="loadCurrentCredentials()">Load Current Credentials</button>
                </div>
            </div>
        </div>

        <!-- Clinic Status Tab -->
        <div id="clinic-status-tab" class="tab-content">
            <div class="card">
                <h4><i class="fas fa-store"></i> Clinic Status Management</h4>

                <!-- Today's Status -->
                <div class="status-section">
                    <h5><i class="fas fa-calendar-day"></i> Today's Status</h5>
                    <div id="today-status-display" class="status-display">
                        <div class="loading">Loading today's status...</div>
                    </div>
                    
                    <div class="status-controls">
                        <label>Set Today's Status:</label>
                        <div class="status-buttons">
                            <button type="button" class="status-btn status-open" onclick="setTodayStatus('open')">
                                <i class="fas fa-check-circle"></i> Open
                            </button>
                            <button type="button" class="status-btn status-busy" onclick="setTodayStatus('busy')">
                                <i class="fas fa-clock"></i> Busy
                            </button>
                            <button type="button" class="status-btn status-fully-booked" onclick="setTodayStatus('fully_booked')">
                                <i class="fas fa-calendar-times"></i> Fully Booked
                            </button>
                            <button type="button" class="status-btn status-closed" onclick="setTodayStatus('closed')">
                                <i class="fas fa-times-circle"></i> Closed Today
                            </button>
                        </div>
                        <div class="form-group" style="margin-top: 15px;">
                            <label>Reason (Optional):</label>
                            <input type="text" id="today-status-reason" placeholder="e.g., Emergency closure, Holiday, Maintenance" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        </div>
                    </div>
                </div>

                <!-- Schedule Future Status -->
                <div class="status-section" style="margin-top: 30px;">
                    <h5><i class="fas fa-calendar-alt"></i> Schedule Future Status</h5>
                    <form id="future-status-form" onsubmit="setFutureStatus(event)">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Date:</label>
                                <input type="date" id="future-status-date" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" style="padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                            </div>
                            <div class="form-group">
                                <label>Status:</label>
                                <select id="future-status-select" required style="padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                                    <option value="open">Open</option>
                                    <option value="busy">Busy</option>
                                    <option value="fully_booked">Fully Booked</option>
                                    <option value="closed">Closed</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Reason (Optional):</label>
                                <input type="text" id="future-status-reason" placeholder="e.g., Scheduled maintenance" style="padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-calendar-plus"></i> Schedule Status
                        </button>
                    </form>
                </div>

                <!-- Upcoming Scheduled Status -->
                <div class="status-section" style="margin-top: 30px;">
                    <h5><i class="fas fa-list"></i> Upcoming Scheduled Status</h5>
                    <div id="upcoming-status-list">
                        <div class="loading">Loading scheduled status...</div>
                    </div>
                </div>
            </div>
        </div>

        <div style="text-align:right;color:#666;font-size:0.9rem">Dentist dashboard — Enhanced with prescription management and staff administration</div>
    </div>

    <!-- Prescription Modal -->
    <div id="prescription-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-prescription"></i> Create/Edit Prescription</h3>
                <button class="close" onclick="closePrescriptionModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="prescription-form" onsubmit="savePrescription(event)">
                    <input type="hidden" id="prescription-id" />
                    <input type="hidden" id="appointment-id" />
                    
                    <div class="form-group">
                        <label style="font-weight: 600; color: #374151; margin-bottom: 8px; display: block;">
                            <i class="fas fa-user"></i> Patient:
                        </label>
                        <input type="text" id="patient-name" readonly 
                               style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px; background: #f8fafc;" />
                    </div>
                    
                    <div class="form-group">
                        <label style="font-weight: 600; color: #374151; margin-bottom: 8px; display: block;">
                            <i class="fas fa-stethoscope"></i> Diagnosis: <span style="color: #ef4444;">*</span>
                        </label>
                        <textarea id="diagnosis" required 
                                  style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px; min-height: 80px; resize: vertical;"
                                  placeholder="Enter diagnosis..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label style="font-weight: 600; color: #374151; margin-bottom: 8px; display: block;">
                            <i class="fas fa-notes-medical"></i> Instructions:
                        </label>
                        <textarea id="instructions"
                                  style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px; min-height: 80px; resize: vertical;"
                                  placeholder="Enter special instructions..."></textarea>
                    </div>
                    
                    <div style="margin: 24px 0;">
                        <h4 style="display: flex; align-items: center; gap: 8px; margin-bottom: 16px; color: #1f2937;">
                            <i class="fas fa-pills"></i> Medications:
                        </h4>
                        <div id="medications-list"></div>
                        <button type="button" onclick="addMedication()" class="btn btn-primary" style="margin-top: 12px;">
                            <i class="fas fa-plus"></i> Add Medication
                        </button>
                    </div>
                    
                    <div style="margin-top:24px;display:flex;gap:10px;padding-top:20px;border-top:2px solid #e2e8f0">
                        <button type="submit" class="btn btn-primary" style="flex: 1;">
                            <i class="fas fa-save"></i> Save Prescription
                        </button>
                        <button type="button" onclick="closePrescriptionModal()" class="btn" style="flex: 1;">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Credential File Viewer Modal -->
    <div id="credential-file-modal" class="modal">
        <div class="modal-content" style="max-width:90%;max-height:90vh">
            <div class="modal-header">
                <h3><i class="fas fa-file"></i> License Document</h3>
                <button class="close" onclick="closeCredentialFileModal()">&times;</button>
            </div>
            <div class="modal-body" style="padding:0;max-height:80vh;overflow:auto">
                <div id="credential-file-content"></div>
            </div>
        </div>
    </div>

    <!-- Prescription View Modal (Read-only) -->
    <div id="prescription-view-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="prescription-view-modal-title"><i class="fas fa-prescription"></i> Prescription Details</h3>
                <button class="close" onclick="closePrescriptionViewModal()">&times;</button>
            </div>
            <div class="modal-body" id="prescription-view-modal-body">
                <!-- Content will be dynamically inserted here -->
            </div>
        </div>
    </div>

    <!-- Staff Edit Modal -->
    <div id="staff-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="staff-modal-title"><i class="fas fa-user-md"></i> Edit Staff Member</h3>
                <button class="close" onclick="closeStaffModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="staff-form" onsubmit="saveStaffMember(event)">
                    <input type="hidden" id="staff-id" />
                    <input type="hidden" id="staff-action" value="update" />
                    
                    <div class="form-group">
                        <label style="font-weight: 600; color: #374151; margin-bottom: 8px; display: block;">
                            <i class="fas fa-user"></i> Full Name: <span style="color: #ef4444;">*</span>
                        </label>
                        <input type="text" id="staff-name" required 
                               style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;" />
                    </div>
                    
                    <div class="form-group">
                        <label style="font-weight: 600; color: #374151; margin-bottom: 8px; display: block;">
                            <i class="fas fa-envelope"></i> Email: <span style="color: #ef4444;">*</span>
                        </label>
                        <input type="email" id="staff-email" required 
                               style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;" />
                    </div>
                    
                    <div class="form-group">
                        <label style="font-weight: 600; color: #374151; margin-bottom: 8px; display: block;">
                            <i class="fas fa-phone"></i> Phone:
                        </label>
                        <input type="text" id="staff-phone" 
                               style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;" />
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="form-group">
                            <label style="font-weight: 600; color: #374151; margin-bottom: 8px; display: block;">
                                <i class="fas fa-user-tag"></i> Role:
                            </label>
                            <select id="staff-role" 
                                    style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                                <option value="staff">Staff</option>
                                <option value="dentist">Dentist</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label style="font-weight: 600; color: #374151; margin-bottom: 8px; display: block;">
                                <i class="fas fa-briefcase"></i> Specialization:
                            </label>
                            <select id="staff-specialization" 
                                    style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                                <option value="">Select specialization</option>
                                <option value="General Dentistry">General Dentistry</option>
                                <option value="Orthodontics">Orthodontics</option>
                                <option value="Oral Surgery">Oral Surgery</option>
                                <option value="Pediatric Dentistry">Pediatric Dentistry</option>
                                <option value="Periodontics">Periodontics</option>
                                <option value="Endodontics">Endodontics</option>
                                <option value="Prosthodontics">Prosthodontics</option>
                                <option value="Dental Hygienist">Dental Hygienist</option>
                                <option value="Dental Assistant">Dental Assistant</option>
                                <option value="Reception">Reception</option>
                            </select>
                        </div>
                    </div>
                    
                    <div id="password-fields" style="display:none; margin-top: 20px; padding: 20px; background: #f8fafc; border-radius: 8px; border: 2px dashed #cbd5e1;">
                        <h4 style="margin: 0 0 16px 0; color: #475569; font-size: 14px;">
                            <i class="fas fa-lock"></i> Change Password (Optional)
                        </h4>
                        <div class="form-group">
                            <label style="font-weight: 600; color: #374151; margin-bottom: 8px; display: block;">
                                New Password:
                            </label>
                            <input type="password" id="staff-password" 
                                   style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;" />
                        </div>
                        <div class="form-group">
                            <label style="font-weight: 600; color: #374151; margin-bottom: 8px; display: block;">
                                Confirm Password:
                            </label>
                            <input type="password" id="staff-password-confirm" 
                                   style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;" />
                        </div>
                    </div>
                    
                    <div style="margin-top:24px;display:flex;gap:10px;padding-top:20px;border-top:2px solid #e2e8f0">
                        <button type="submit" class="btn btn-primary" id="staff-submit-btn" style="flex: 1;">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                        <button type="button" onclick="closeStaffModal()" class="btn" style="flex: 1;">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Treatment Management Modal -->
    <div id="treatment-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="treatment-modal-title"><i class="fas fa-tooth"></i> Add New Treatment</h3>
                <button class="close" onclick="closeTreatmentModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="treatment-form" onsubmit="saveTreatment(event)">
                    <input type="hidden" id="treatment-id" />
                    <input type="hidden" id="treatment-action" value="create" />
                    
                    <div class="form-group">
                        <label style="font-weight: 600; color: #374151; margin-bottom: 8px; display: block;">
                            <i class="fas fa-stethoscope"></i> Treatment/Service Name: <span style="color: #ef4444;">*</span>
                        </label>
                        <input type="text" id="treatment-name" required placeholder="e.g., Tooth Extraction, Cleaning, Root Canal" 
                               style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;" />
                    </div>
                    
                    <div class="form-group">
                        <label style="font-weight: 600; color: #374151; margin-bottom: 8px; display: block;">
                            <i class="fas fa-file-alt"></i> Description:
                        </label>
                        <textarea id="treatment-description" rows="3" placeholder="Brief description of the treatment/service..."
                                  style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px; resize: vertical;"></textarea>
                    </div>
                    
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px">
                        <div class="form-group">
                            <label style="font-weight: 600; color: #374151; margin-bottom: 8px; display: block;">
                                <i class="fas fa-peso-sign"></i> Price (₱): <span style="color: #ef4444;">*</span>
                            </label>
                            <input type="number" id="treatment-price" required step="0.01" min="0" placeholder="e.g., 500.00" 
                                   style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;" />
                        </div>
                        
                        <div class="form-group">
                            <label style="font-weight: 600; color: #374151; margin-bottom: 8px; display: block;">
                                <i class="fas fa-clock"></i> Duration (minutes):
                            </label>
                            <input type="number" id="treatment-duration" min="15" step="15" placeholder="e.g., 30, 60" 
                                   style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;" />
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label style="font-weight: 600; color: #374151; margin-bottom: 8px; display: block;">
                            <i class="fas fa-tags"></i> Category:
                        </label>
                        <select id="treatment-category" 
                                style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                            <option value="">Select category</option>
                            <option value="Preventive">Preventive Care</option>
                            <option value="Restorative">Restorative</option>
                            <option value="Cosmetic">Cosmetic</option>
                            <option value="Orthodontics">Orthodontics</option>
                            <option value="Surgery">Oral Surgery</option>
                            <option value="Emergency">Emergency Care</option>
                            <option value="Pediatric">Pediatric Dentistry</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group" style="display: flex; align-items: center; gap: 8px; padding: 12px; background: #f0f9ff; border-radius: 8px; border: 2px solid #bae6fd;">
                        <input type="checkbox" id="treatment-active" checked 
                               style="width: 18px; height: 18px; cursor: pointer;" />
                        <label for="treatment-active" style="margin: 0; font-weight: 600; color: #0369a1; cursor: pointer;">
                            <i class="fas fa-check-circle"></i> Active (available for booking)
                        </label>
                    </div>
                    
                    <div style="margin-top:24px;padding-top:20px;border-top:2px solid #e2e8f0;display:flex;gap:10px">
                        <button type="submit" class="btn btn-primary" style="flex: 1;">
                            <i class="fas fa-save"></i> Save Treatment
                        </button>
                        <button type="button" onclick="closeTreatmentModal()" class="btn" style="flex: 1;">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Appointment Details Modal -->
    <div id="appointment-details-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-calendar-check"></i> Appointment Details</h3>
                <button class="close" onclick="closeAppointmentDetailsModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div id="appointment-details-content">
                    <!-- Will be populated by JavaScript -->
                </div>
                <div style="margin-top:24px;padding-top:20px;border-top:2px solid #e2e8f0;display:flex;gap:10px">
                    <button type="button" onclick="closeAppointmentDetailsModal()" class="btn" style="flex: 1;">
                        <i class="fas fa-times"></i> Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const apiBase = '../api/dentist.php';
        const prescriptionsApi = '../api/prescriptions.php';
        const staffApi = '../api/staff-management.php';

        document.addEventListener('DOMContentLoaded', () => {
            loadComprehensiveAnalytics();
            // Set today's date for appointment filter
            document.getElementById('appointment-date-filter').value = new Date().toISOString().split('T')[0];
        });

        // Tab Management
        function showTab(tabName) {
            // Hide all tabs
            const tabs = document.querySelectorAll('.tab-content');
            tabs.forEach(tab => tab.classList.remove('active'));
            
            // Remove active from all buttons
            const buttons = document.querySelectorAll('.tab-btn');
            buttons.forEach(btn => btn.classList.remove('active'));
            
            // Show selected tab
            const selectedTab = document.getElementById(tabName + '-tab');
            if (selectedTab) {
                selectedTab.classList.add('active');
            }
            
            // Add active to clicked button
            event.target.classList.add('active');
            
            // Load data for specific tabs
            if (tabName === 'prescriptions') {
                loadAppointmentsNeedingPrescriptions();
            } else if (tabName === 'staff') {
                loadBranchStaff();
            } else if (tabName === 'treatments') {
                loadBranchTreatments();
            } else if (tabName === 'appointments') {
                loadDailyAppointments();
            } else if (tabName === 'analytics') {
                loadComprehensiveAnalytics();
            } else if (tabName === 'credentials') {
                loadCurrentCredentials();
            } else if (tabName === 'clinic-status') {
                loadClinicStatus();
            }
        }

        // Prescription View Switching
        function switchPrescriptionView(view) {
            // Hide all prescription views
            document.querySelectorAll('.prescription-view').forEach(el => el.style.display = 'none');
            
            // Remove active from all view tabs
            document.querySelectorAll('#prescriptions-tab .view-tab-btn').forEach(btn => btn.classList.remove('active'));
            
            // Show selected view
            if (view === 'today') {
                document.getElementById('prescriptions-today-view').style.display = 'block';
                event.target.classList.add('active');
                loadTodaysPrescriptions();
            } else if (view === 'all') {
                document.getElementById('prescriptions-all-view').style.display = 'block';
                event.target.classList.add('active');
                loadAllPrescriptions();
            } else if (view === 'pending') {
                document.getElementById('prescriptions-pending-view').style.display = 'block';
                event.target.classList.add('active');
                loadAppointmentsNeedingPrescriptions();
            }
        }

        // Appointment View Switching
        function switchAppointmentView(view) {
            // Hide all appointment views
            document.querySelectorAll('.appointment-view').forEach(el => el.style.display = 'none');
            
            // Remove active from all view tabs
            document.querySelectorAll('#appointments-tab .view-tab-btn').forEach(btn => btn.classList.remove('active'));
            
            // Show selected view
            if (view === 'today') {
                document.getElementById('appointments-today-view').style.display = 'block';
                event.target.classList.add('active');
                loadTodaysAppointments();
            } else if (view === 'all') {
                document.getElementById('appointments-all-view').style.display = 'block';
                event.target.classList.add('active');
                loadDailyAppointments();
            } else if (view === 'schedule') {
                document.getElementById('appointments-schedule-view').style.display = 'block';
                event.target.classList.add('active');
                loadMySchedule();
            }
        }

        // Refresh Functions
        function refreshPrescriptions() {
            const activeView = document.querySelector('#prescriptions-tab .view-tab-btn.active');
            if (activeView) {
                activeView.click();
            }
        }

        function refreshAppointments() {
            const activeView = document.querySelector('#appointments-tab .view-tab-btn.active');
            if (activeView) {
                activeView.click();
            }
        }

        // Enhanced Analytics functions
        function loadComprehensiveAnalytics() {
            console.log('Loading comprehensive analytics...');
            fetch(apiBase + '?action=getComprehensiveAnalytics', {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'Cache-Control': 'no-cache'
                }
            })
                .then(response => {
                    console.log('Response status:', response.status);
                    console.log('Response URL:', response.url);
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('API Response:', data);
                    if (data.success) {
                        const analytics = data.analytics;
                        
                        // Update summary cards
                        document.getElementById('branch-revenue-month').textContent = '₱ ' + (analytics.branch_revenue_month || 0).toFixed(2);
                        document.getElementById('total-patients').textContent = analytics.total_patients || 0;
                        document.getElementById('appointments-today').textContent = `${analytics.today_pending || 0} / ${analytics.today_approved || 0} / ${analytics.today_completed || 0}`;
                        document.getElementById('pending-count').textContent = analytics.pending_count || 0;
                        document.getElementById('total-prescriptions').textContent = analytics.prescriptions_month || 0;
                        document.getElementById('staff-count').textContent = analytics.staff_count || 0;
                        
                        // Load appointments needing prescriptions count
                        loadAppointmentsNeedingPrescriptionsCount();
                        
                        // Load recent patients
                        loadRecentPatients(analytics.recent_patients || []);
                        
                        // Load popular treatments
                        loadPopularTreatments(analytics.popular_treatments || []);
                        
                        // Load schedule overview
                        loadScheduleOverview(analytics.schedule_overview || []);
                    } else {
                        console.error('API Error:', data.message);
                        alert('Failed to load analytics: ' + data.message);
                    }
                }).catch(error => {
                    console.error('Fetch error:', error);
                    alert('Error loading analytics data: ' + error.message);
                });
        }

        function loadRecentPatients(patients) {
            const container = document.getElementById('recent-patients');
            if (patients.length === 0) {
                container.innerHTML = '<div style="text-align:center;color:#666;padding:20px">No recent patients</div>';
                return;
            }
            
            let html = '<table style="width:100%;font-size:0.9rem"><thead><tr><th>Patient</th><th>Last Visit</th><th>Status</th></tr></thead><tbody>';
            patients.forEach(p => {
                html += `<tr>
                    <td>${p.patient_name}</td>
                    <td>${p.last_visit}</td>
                    <td><span style="color:${p.status === 'completed' ? 'green' : 'orange'}">${p.status}</span></td>
                </tr>`;
            });
            html += '</tbody></table>';
            container.innerHTML = html;
        }

        function loadPopularTreatments(treatments) {
            const container = document.getElementById('popular-treatments');
            if (treatments.length === 0) {
                container.innerHTML = '<div style="text-align:center;color:#666;padding:20px">No treatment data</div>';
                return;
            }
            
            let html = '<table style="width:100%;font-size:0.9rem"><thead><tr><th>Treatment</th><th>Count</th><th>Revenue</th></tr></thead><tbody>';
            treatments.forEach(t => {
                html += `<tr>
                    <td>${t.treatment_name}</td>
                    <td>${t.count}</td>
                    <td>₱${parseFloat(t.revenue || 0).toFixed(2)}</td>
                </tr>`;
            });
            html += '</tbody></table>';
            container.innerHTML = html;
        }

        function loadScheduleOverview(schedule) {
            const container = document.getElementById('schedule-overview');
            if (schedule.length === 0) {
                container.innerHTML = '<div style="text-align:center;color:#666;padding:20px">No upcoming appointments</div>';
                return;
            }
            
            let html = '<table style="width:100%"><thead><tr><th>Date</th><th>Time</th><th>Patient</th><th>Treatment</th><th>Status</th></tr></thead><tbody>';
            schedule.forEach(s => {
                html += `<tr>
                    <td>${s.appointment_date}</td>
                    <td>${s.appointment_time}</td>
                    <td>${s.patient_name}</td>
                    <td>${s.treatment_name || 'N/A'}</td>
                    <td><span style="color:${s.status === 'approved' ? 'green' : 'orange'}">${s.status}</span></td>
                </tr>`;
            });
            html += '</tbody></table>';
            container.innerHTML = html;
        }

        // Enhanced Appointment functions
        function loadDailyAppointments() {
            const date = document.getElementById('appointment-date-filter').value;
            if (!date) {
                alert('Please select a date');
                return;
            }
            
            fetch(apiBase + '?action=getDailyAppointments&date=' + encodeURIComponent(date))
                .then(r => r.json())
                .then(data => {
                    const container = document.getElementById('daily-appointments');
                    if (!data.success) {
                        container.innerHTML = '<div style="text-align:center;color:#ef4444;padding:20px">Error: ' + (data.message || 'Failed to load appointments') + '</div>';
                        return;
                    }
                    
                    const appointments = data.appointments || [];
                    if (appointments.length === 0) {
                        container.innerHTML = '<div style="text-align:center;color:#666;padding:20px">No appointments found for ' + date + '</div>';
                        return;
                    }
                    
                    let html = '<table><thead><tr><th>Time</th><th>Patient</th><th>Treatment</th><th>Status</th><th>Actions</th></tr></thead><tbody>';
                    
                    appointments.forEach(a => {
                        html += `<tr>
                            <td><strong>${a.appointment_time}</strong></td>
                            <td>${a.patient_name}</td>
                            <td>${a.treatment_name || 'N/A'}</td>
                            <td>
                                <span style="color:${getStatusColor(a.status)};font-weight:600">${a.status}</span>
                                ${a.status === 'approved' && !a.has_prescription ? '<br><small style="color:#f59e0b"><i class="fas fa-prescription"></i> Needs Prescription</small>' : ''}
                            </td>
                            <td class="actions">
                                <button class="btn" onclick="viewAppointmentDetails(${a.id})">
                                    <i class="fas fa-eye"></i> Details
                                </button>
                                ${a.status === 'pending' ? `<button class="btn btn-success" onclick="updateStatus(${a.id},'approved')"><i class="fas fa-check"></i> Approve</button>` : ''}
                                ${a.status === 'approved' ? `
                                    <div style="display:flex;gap:5px;flex-wrap:wrap;margin-top:5px">
                                        <button class="btn btn-warning" onclick="createPrescription(${a.id}, '${a.patient_name}')" title="Create prescription">
                                            <i class="fas fa-prescription"></i> Prescription
                                        </button>
                                        <button class="btn btn-primary" onclick="updateStatus(${a.id},'completed')">
                                            <i class="fas fa-check-double"></i> Complete
                                        </button>
                                    </div>
                                ` : ''}
                                ${a.status === 'completed' && !a.has_prescription ? `<button class="btn btn-primary" onclick="createPrescription(${a.id}, '${a.patient_name}')"><i class="fas fa-plus"></i> Add Prescription</button>` : ''}
                            </td>
                        </tr>`;
                    });
                    
                    html += '</tbody></table>';
                    container.innerHTML = html;
                })
                .catch(e => {
                    console.error(e);
                    document.getElementById('daily-appointments').innerHTML = '<div style="text-align:center;color:#ef4444;padding:20px">Network error loading appointments</div>';
                });
        }

        function loadTodaysAppointments() {
            const today = new Date().toISOString().split('T')[0];
            
            fetch(apiBase + '?action=getDailyAppointments&date=' + encodeURIComponent(today))
                .then(r => r.json())
                .then(data => {
                    const container = document.getElementById('appointments-today-list');
                    if (!data.success) {
                        container.innerHTML = '<div style="text-align:center;color:#ef4444;padding:20px">Error: ' + (data.message || 'Failed to load appointments') + '</div>';
                        return;
                    }
                    
                    const appointments = data.appointments || [];
                    if (appointments.length === 0) {
                        container.innerHTML = '<div style="text-align:center;color:#666;padding:20px"><i class="fas fa-calendar-check" style="font-size:48px;color:#cbd5e1;margin-bottom:10px;display:block"></i>No appointments scheduled for today</div>';
                        return;
                    }
                    
                    let html = '<table><thead><tr><th>Time</th><th>Patient</th><th>Treatment</th><th>Status</th><th>Actions</th></tr></thead><tbody>';
                    
                    appointments.forEach(a => {
                        const statusColors = {
                            'pending': '#f59e0b',
                            'approved': '#3b82f6',
                            'completed': '#10b981',
                            'cancelled': '#ef4444'
                        };
                        const statusBg = {
                            'pending': '#fef3c7',
                            'approved': '#dbeafe',
                            'completed': '#d1fae5',
                            'cancelled': '#fee2e2'
                        };
                        html += `<tr>
                            <td><strong style="font-size:16px">${a.appointment_time}</strong></td>
                            <td><strong>${a.patient_name}</strong></td>
                            <td>${a.treatment_name || 'N/A'}</td>
                            <td>
                                <span style="background:${statusBg[a.status] || '#e5e7eb'};color:${statusColors[a.status] || '#666'};padding:6px 12px;border-radius:12px;font-size:13px;font-weight:600;display:inline-block">${a.status.toUpperCase()}</span>
                                ${a.status === 'approved' && !a.has_prescription ? '<br><small style="color:#f59e0b;margin-top:4px;display:block"><i class="fas fa-prescription"></i> Needs Prescription</small>' : ''}
                            </td>
                            <td class="actions">
                                <div style="display:flex;gap:5px;flex-wrap:wrap">
                                    <button class="btn" onclick="viewAppointmentDetails(${a.id})">
                                        <i class="fas fa-eye"></i> Details
                                    </button>
                                    ${a.status === 'pending' ? `<button class="btn btn-success" onclick="updateStatus(${a.id},'approved')"><i class="fas fa-check"></i> Approve</button>` : ''}
                                    ${a.status === 'approved' ? `
                                        <button class="btn btn-warning" onclick="createPrescription(${a.id}, '${a.patient_name}')" title="Create prescription">
                                            <i class="fas fa-prescription"></i> Rx
                                        </button>
                                        <button class="btn btn-primary" onclick="updateStatus(${a.id},'completed')">
                                            <i class="fas fa-check-double"></i> Complete
                                        </button>
                                    ` : ''}
                                    ${a.status === 'completed' && !a.has_prescription ? `<button class="btn btn-primary" onclick="createPrescription(${a.id}, '${a.patient_name}')"><i class="fas fa-plus"></i> Rx</button>` : ''}
                                </div>
                            </td>
                        </tr>`;
                    });
                    
                    html += '</tbody></table>';
                    container.innerHTML = html;
                })
                .catch(e => {
                    console.error(e);
                    document.getElementById('appointments-today-list').innerHTML = '<div style="text-align:center;color:#ef4444;padding:20px">Network error loading appointments</div>';
                });
        }

        function loadMySchedule() {
            fetch(apiBase + '?action=getMySchedule')
                .then(r => r.json())
                .then(data => {
                    const container = document.getElementById('my-schedule');
                    if (!data.success) {
                        container.innerHTML = '<div class="card">Error: ' + (data.message || 'Failed to load schedule') + '</div>';
                        return;
                    }
                    
                    const schedule = data.schedule || [];
                    if (schedule.length === 0) {
                        container.innerHTML = '<div class="card">No upcoming appointments in your schedule</div>';
                        container.style.display = 'block';
                        return;
                    }
                    
                    let html = '<div class="card"><h4>My Upcoming Schedule</h4>';
                    html += '<table><thead><tr><th>Date</th><th>Time</th><th>Patient</th><th>Treatment</th><th>Status</th></tr></thead><tbody>';
                    
                    schedule.forEach(s => {
                        html += `<tr>
                            <td>${s.appointment_date}</td>
                            <td>${s.appointment_time}</td>
                            <td>${s.patient_name}</td>
                            <td>${s.treatment_name || 'N/A'}</td>
                            <td><span style="color:${getStatusColor(s.status)}">${s.status}</span></td>
                        </tr>`;
                    });
                    
                    html += '</tbody></table></div>';
                    container.innerHTML = html;
                    container.style.display = 'block';
                })
                .catch(e => {
                    console.error(e);
                    document.getElementById('my-schedule').innerHTML = '<div class="card">Network error loading schedule</div>';
                });
        }

        function getStatusColor(status) {
            switch(status) {
                case 'pending': return 'orange';
                case 'approved': return 'blue';
                case 'completed': return 'green';
                case 'cancelled': return 'red';
                default: return 'gray';
            }
        }

        function viewAppointmentDetails(appointmentId) {
            fetch(apiBase + '?action=getAppointmentDetails&appointment_id=' + appointmentId)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const appointment = data.appointment;
                        const content = document.getElementById('appointment-details-content');
                        content.innerHTML = `
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px">
                                <div><strong>Patient:</strong> ${appointment.patient_name}</div>
                                <div><strong>Date:</strong> ${appointment.appointment_date}</div>
                                <div><strong>Time:</strong> ${appointment.appointment_time}</div>
                                <div><strong>Status:</strong> ${appointment.status}</div>
                                <div><strong>Treatment:</strong> ${appointment.treatment_name || 'N/A'}</div>
                                <div><strong>Phone:</strong> ${appointment.patient_phone || 'N/A'}</div>
                            </div>
                            ${appointment.notes ? `<div style="margin-top:15px"><strong>Notes:</strong><br>${appointment.notes}</div>` : ''}
                            ${appointment.prescription ? `<div style="margin-top:15px"><strong>Prescription:</strong> Yes</div>` : ''}
                        `;
                        document.getElementById('appointment-details-modal').classList.add('show');
                    } else {
                        alert('Error loading appointment details: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(e => {
                    console.error(e);
                    alert('Network error loading appointment details');
                });
        }

        function closeAppointmentDetailsModal() {
            document.getElementById('appointment-details-modal').classList.remove('show');
        }

        function updateStatus(id, status) {
            if (!confirm('Confirm change to ' + status + '?')) return;
            
            // Show loading spinner
            const loadingDiv = document.createElement('div');
            loadingDiv.id = 'status-update-loading';
            loadingDiv.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);backdrop-filter:blur(5px);z-index:9999;display:flex;align-items:center;justify-content:center;';
            loadingDiv.innerHTML = `
                <div style="background:white;padding:40px;border-radius:16px;text-align:center;box-shadow:0 25px 50px rgba(0,0,0,0.3);">
                    <div class="loading-spinner" style="width:50px;height:50px;border:4px solid #e5e7eb;border-top-color:#054A91;border-radius:50%;animation:spin 0.8s linear infinite;margin:0 auto 20px;"></div>
                    <div style="font-size:18px;font-weight:600;color:#1f2937;margin-bottom:8px;">Updating Status...</div>
                    <div style="font-size:14px;color:#6b7280;">Please wait</div>
                </div>
            `;
            document.body.appendChild(loadingDiv);
            
            fetch(apiBase + '?action=updateStatus', {
                method: 'POST',
                headers: {'Content-Type':'application/json'},
                body: JSON.stringify({appointment_id: id, status: status})
            })
            .then(r=>r.json())
            .then(data=>{
                // Remove loading spinner
                const spinner = document.getElementById('status-update-loading');
                if (spinner) spinner.remove();
                
                if (data.success) { 
                    alert(data.message || 'Status updated successfully!'); 
                    
                    // Reload data
                    const activeAppointmentView = document.querySelector('#appointments-tab .view-tab-btn.active');
                    if (activeAppointmentView) {
                        activeAppointmentView.click();
                    } else {
                        loadDailyAppointments();
                    }
                    loadComprehensiveAnalytics(); 
                    
                    // If completed, automatically send invoice with prescription
                    if (status === 'completed') {
                        setTimeout(() => {
                            if (confirm('Send invoice and prescription to patient email?')) {
                                sendInvoiceWithPrescription(id);
                            }
                        }, 500);
                    }
                }
                else {
                    alert('Error: ' + (data.message||'Update failed'));
                }
            })
            .catch(e=>{
                // Remove loading spinner on error
                const spinner = document.getElementById('status-update-loading');
                if (spinner) spinner.remove();
                console.error(e); 
                alert('Network error');
            });
        }

        function sendInvoiceWithPrescription(appointmentId) {
            // Show loading spinner
            const loadingDiv = document.createElement('div');
            loadingDiv.id = 'email-sending-loading';
            loadingDiv.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);backdrop-filter:blur(5px);z-index:9999;display:flex;align-items:center;justify-content:center;';
            loadingDiv.innerHTML = `
                <div style="background:white;padding:40px;border-radius:16px;text-align:center;box-shadow:0 25px 50px rgba(0,0,0,0.3);">
                    <div class="loading-spinner" style="width:50px;height:50px;border:4px solid #e5e7eb;border-top-color:#10b981;border-radius:50%;animation:spin 0.8s linear infinite;margin:0 auto 20px;"></div>
                    <div style="font-size:18px;font-weight:600;color:#1f2937;margin-bottom:8px;">Sending Email...</div>
                    <div style="font-size:14px;color:#6b7280;">Including invoice and prescription</div>
                </div>
            `;
            document.body.appendChild(loadingDiv);
            
            fetch(apiBase + '?action=sendInvoiceWithPrescription', {
                method: 'POST',
                headers: {'Content-Type':'application/json'},
                body: JSON.stringify({appointment_id: appointmentId})
            })
            .then(r=>r.json())
            .then(data=>{
                // Remove loading spinner
                const spinner = document.getElementById('email-sending-loading');
                if (spinner) spinner.remove();
                
                if (data.success) {
                    alert('✅ Invoice and prescription sent successfully to patient email!');
                } else {
                    alert('⚠️ Error sending email: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(e=>{
                // Remove loading spinner on error
                const spinner = document.getElementById('email-sending-loading');
                if (spinner) spinner.remove();
                console.error(e);
                alert('❌ Network error sending email');
            });
        }

        // Prescription Management Functions
        function loadAppointmentsNeedingPrescriptions() {
            fetch(prescriptionsApi + '?action=getAppointmentsNeedingPrescriptions')
                .then(r => r.json())
                .then(data => {
                    const container = document.getElementById('appointments-needing-prescriptions');
                    if (!data.success) {
                        container.innerHTML = '<div class="card">Error: ' + (data.message || 'Failed to load') + '</div>';
                        return;
                    }
                    
                    const appointments = data.appointments || [];
                    if (appointments.length === 0) {
                        container.innerHTML = '<div class="card">No appointments need prescriptions currently.</div>';
                        return;
                    }
                    
                    let html = '<div class="card"><h4>Appointments Needing Prescriptions</h4><table><thead><tr><th>Patient</th><th>Date</th><th>Treatment</th><th>Status</th><th>Action</th></tr></thead><tbody>';
                    appointments.forEach(a => {
                        html += `<tr>
                            <td>${a.patient_name}</td>
                            <td>${a.appointment_date}</td>
                            <td>${a.treatment_name || 'N/A'}</td>
                            <td>${a.status}</td>
                            <td><button class="btn btn-primary" onclick="createPrescription(${a.id}, '${a.patient_name}')">Create Prescription</button></td>
                        </tr>`;
                    });
                    html += '</tbody></table></div>';
                    container.innerHTML = html;
                })
                .catch(e => {
                    console.error(e);
                    document.getElementById('appointments-needing-prescriptions').innerHTML = '<div class="card">Network error loading appointments</div>';
                });
        }

        function loadAllPrescriptions() {
            fetch(prescriptionsApi + '?action=getPrescriptions')
                .then(r => r.json())
                .then(data => {
                    const container = document.getElementById('prescriptions-all-list');
                    if (!data.success) {
                        container.innerHTML = '<div style="text-align:center;color:#ef4444;padding:20px">Error: ' + (data.message || 'Failed to load') + '</div>';
                        return;
                    }
                    
                    const prescriptions = data.prescriptions || [];
                    if (prescriptions.length === 0) {
                        container.innerHTML = '<div style="text-align:center;color:#666;padding:20px">No prescriptions found.</div>';
                        return;
                    }
                    
                    let html = '<table><thead><tr><th>Patient</th><th>Date</th><th>Diagnosis</th><th>Medications</th><th>Actions</th></tr></thead><tbody>';
                    prescriptions.forEach(p => {
                        html += `<tr>
                            <td>${p.patient_name}</td>
                            <td>${new Date(p.created_at).toLocaleDateString()}</td>
                            <td>${p.diagnosis || 'N/A'}</td>
                            <td>${p.medication_count || 0} medications</td>
                            <td>
                                <button class="btn btn-primary" onclick="viewPrescription(${p.id})">
                                    <i class="fas fa-eye"></i> View
                                </button>
                            </td>
                        </tr>`;
                    });
                    html += '</tbody></table>';
                    container.innerHTML = html;
                })
                .catch(e => {
                    console.error(e);
                    document.getElementById('prescriptions-all-list').innerHTML = '<div style="text-align:center;color:#ef4444;padding:20px">Network error loading prescriptions</div>';
                });
        }

        function loadTodaysPrescriptions() {
            fetch(prescriptionsApi + '?action=getPrescriptions')
                .then(r => r.json())
                .then(data => {
                    const container = document.getElementById('prescriptions-today-list');
                    if (!data.success) {
                        container.innerHTML = '<div style="text-align:center;color:#ef4444;padding:20px">Error: ' + (data.message || 'Failed to load') + '</div>';
                        return;
                    }
                    
                    const prescriptions = data.prescriptions || [];
                    const today = new Date().toDateString();
                    const todaysPrescriptions = prescriptions.filter(p => {
                        const pDate = new Date(p.created_at).toDateString();
                        return pDate === today;
                    });
                    
                    if (todaysPrescriptions.length === 0) {
                        container.innerHTML = '<div style="text-align:center;color:#666;padding:20px">No prescriptions created today.</div>';
                        return;
                    }
                    
                    let html = '<table><thead><tr><th>Patient</th><th>Time</th><th>Diagnosis</th><th>Medications</th><th>Actions</th></tr></thead><tbody>';
                    todaysPrescriptions.forEach(p => {
                        const time = new Date(p.created_at).toLocaleTimeString();
                        html += `<tr>
                            <td><strong>${p.patient_name}</strong></td>
                            <td>${time}</td>
                            <td>${p.diagnosis || 'N/A'}</td>
                            <td><span style="background:#dbeafe;color:#1e40af;padding:4px 12px;border-radius:12px;font-size:13px;font-weight:600">${p.medication_count || 0} meds</span></td>
                            <td>
                                <button class="btn btn-primary" onclick="viewPrescription(${p.id})">
                                    <i class="fas fa-eye"></i> View
                                </button>
                            </td>
                        </tr>`;
                    });
                    html += '</tbody></table>';
                    container.innerHTML = html;
                })
                .catch(e => {
                    console.error(e);
                    document.getElementById('prescriptions-today-list').innerHTML = '<div style="text-align:center;color:#ef4444;padding:20px">Network error loading prescriptions</div>';
                });
        }

        function loadAppointmentsNeedingPrescriptionsCount() {
            fetch(prescriptionsApi + '?action=getAppointmentsNeedingPrescriptions')
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const count = data.appointments ? data.appointments.length : 0;
                        document.getElementById('need-prescriptions').textContent = count;
                        
                        // Add visual alert if there are appointments needing prescriptions
                        const card = document.getElementById('need-prescriptions').parentElement;
                        if (count > 0) {
                            card.style.background = '#fff8e1';
                            card.style.borderLeft = '4px solid #f59e0b';
                        } else {
                            card.style.background = '';
                            card.style.borderLeft = '4px solid #10b981';
                        }
                    } else {
                        document.getElementById('need-prescriptions').textContent = '0';
                    }
                })
                .catch(e => {
                    console.error(e);
                    document.getElementById('need-prescriptions').textContent = 'Error';
                });
        }

        // Prescription Modal Functions
        function createPrescription(appointmentId, patientName) {
            document.getElementById('prescription-id').value = '';
            document.getElementById('appointment-id').value = appointmentId;
            document.getElementById('patient-name').value = patientName;
            document.getElementById('diagnosis').value = '';
            document.getElementById('instructions').value = '';
            document.getElementById('medications-list').innerHTML = '';
            addMedication(); // Start with one medication
            document.getElementById('prescription-modal').classList.add('show');
        }

        function viewPrescription(prescriptionId) {
            if (!prescriptionId) {
                alert('Invalid prescription ID');
                return;
            }
            
            // Fetch prescription details
            fetch(prescriptionsApi + '?action=getPrescriptionDetails&id=' + prescriptionId)
                .then(r => r.json())
                .then(data => {
                    if (!data.success) {
                        alert(data.message || 'Failed to load prescription');
                        return;
                    }
                    
                    const p = data.prescription;
                    const medications = data.medications || [];
                    
                    // Build medications HTML
                    let medicationsHtml = '';
                    if (medications.length > 0) {
                        medications.forEach((med, index) => {
                            medicationsHtml += `
                                <div style="background:#f0f9ff;padding:15px;margin:10px 0;border-left:4px solid #3b82f6;border-radius:4px">
                                    <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:10px">
                                        <strong style="font-size:16px;color:#1e40af">💊 ${med.medication_name}</strong>
                                        <span style="background:#dbeafe;color:#1e40af;padding:4px 12px;border-radius:12px;font-size:12px;font-weight:600">#${index + 1}</span>
                                    </div>
                                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:10px">
                                        <div>
                                            <div style="color:#6b7280;font-size:13px">Dosage</div>
                                            <div style="font-weight:600;margin-top:4px">${med.dosage}</div>
                                        </div>
                                        <div>
                                            <div style="color:#6b7280;font-size:13px">Frequency</div>
                                            <div style="font-weight:600;margin-top:4px">${med.frequency}</div>
                                        </div>
                                        <div>
                                            <div style="color:#6b7280;font-size:13px">Duration</div>
                                            <div style="font-weight:600;margin-top:4px">${med.duration}</div>
                                        </div>
                                        ${med.instructions ? `
                                        <div>
                                            <div style="color:#6b7280;font-size:13px">Special Instructions</div>
                                            <div style="font-weight:600;margin-top:4px">${med.instructions}</div>
                                        </div>
                                        ` : ''}
                                    </div>
                                </div>
                            `;
                        });
                    } else {
                        medicationsHtml = '<div style="text-align:center;color:#666;padding:20px">No medications prescribed</div>';
                    }
                    
                    // Build prescription details modal content
                    const modalContent = `
                        <div style="max-height:70vh;overflow-y:auto">
                            <!-- Patient & Date Info -->
                            <div style="background:#f9fafb;padding:15px;border-radius:8px;margin-bottom:20px">
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px">
                                    <div>
                                        <div style="color:#6b7280;font-size:13px;margin-bottom:4px">Patient</div>
                                        <div style="font-weight:600;font-size:16px">${p.patient_name}</div>
                                    </div>
                                    <div>
                                        <div style="color:#6b7280;font-size:13px;margin-bottom:4px">Date Prescribed</div>
                                        <div style="font-weight:600">${new Date(p.created_at).toLocaleString()}</div>
                                    </div>
                                    ${p.appointment_date ? `
                                    <div>
                                        <div style="color:#6b7280;font-size:13px;margin-bottom:4px">Appointment Date</div>
                                        <div style="font-weight:600">${new Date(p.appointment_date).toLocaleDateString()}</div>
                                    </div>
                                    ` : ''}
                                    ${p.treatment_name ? `
                                    <div>
                                        <div style="color:#6b7280;font-size:13px;margin-bottom:4px">Treatment</div>
                                        <div style="font-weight:600">${p.treatment_name}</div>
                                    </div>
                                    ` : ''}
                                </div>
                            </div>
                            
                            <!-- Diagnosis -->
                            <div style="margin-bottom:20px">
                                <h4 style="color:#054A91;margin-bottom:10px;border-bottom:2px solid #054A91;padding-bottom:8px">
                                    <i class="fas fa-stethoscope"></i> Diagnosis
                                </h4>
                                <div style="background:white;padding:15px;border-radius:8px;border:1px solid #e5e7eb">
                                    ${p.diagnosis || '<em style="color:#9ca3af">No diagnosis provided</em>'}
                                </div>
                            </div>
                            
                            <!-- General Instructions -->
                            ${p.instructions ? `
                            <div style="margin-bottom:20px">
                                <h4 style="color:#054A91;margin-bottom:10px;border-bottom:2px solid #054A91;padding-bottom:8px">
                                    <i class="fas fa-info-circle"></i> General Instructions
                                </h4>
                                <div style="background:#fef3c7;padding:15px;border-radius:8px;border-left:4px solid #f59e0b">
                                    ${p.instructions}
                                </div>
                            </div>
                            ` : ''}
                            
                            <!-- Medications -->
                            <div>
                                <h4 style="color:#054A91;margin-bottom:10px;border-bottom:2px solid #054A91;padding-bottom:8px">
                                    <i class="fas fa-pills"></i> Prescribed Medications
                                    <span style="background:#dbeafe;color:#1e40af;padding:4px 12px;border-radius:12px;font-size:13px;font-weight:600;margin-left:10px">
                                        ${medications.length} ${medications.length === 1 ? 'medication' : 'medications'}
                                    </span>
                                </h4>
                                ${medicationsHtml}
                            </div>
                        </div>
                    `;
                    
                    // Show in modal
                    document.getElementById('prescription-view-modal-title').textContent = 'Prescription Details - ' + p.patient_name;
                    document.getElementById('prescription-view-modal-body').innerHTML = modalContent;
                    document.getElementById('prescription-view-modal').style.display = 'flex';
                })
                .catch(e => {
                    console.error(e);
                    alert('Error loading prescription details');
                });
        }

        function addMedication() {
            const container = document.getElementById('medications-list');
            const medId = 'med_' + Date.now();
            const html = `
                <div class="medication-item" id="${medId}">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
                        <h5>Medication</h5>
                        <button type="button" onclick="removeMedication('${medId}')" class="btn btn-danger">Remove</button>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                        <div class="form-group">
                            <label>Medicine Name:</label>
                            <input type="text" name="medication_name[]" required />
                        </div>
                        <div class="form-group">
                            <label>Dosage:</label>
                            <input type="text" name="dosage[]" required />
                        </div>
                        <div class="form-group">
                            <label>Frequency:</label>
                            <select name="frequency[]" required>
                                <option value="">Select frequency</option>
                                <option value="Once daily">Once daily</option>
                                <option value="Twice daily">Twice daily</option>
                                <option value="Three times daily">Three times daily</option>
                                <option value="Four times daily">Four times daily</option>
                                <option value="As needed">As needed</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Duration:</label>
                            <input type="text" name="duration[]" placeholder="e.g., 7 days" required />
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Instructions:</label>
                        <textarea name="medication_instructions[]" placeholder="e.g., Take with food"></textarea>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
        }

        function removeMedication(medId) {
            document.getElementById(medId).remove();
        }

        function savePrescription(event) {
            event.preventDefault();
            
            const formData = new FormData(event.target);
            const data = {
                appointment_id: document.getElementById('appointment-id').value,
                diagnosis: formData.get('diagnosis') || document.getElementById('diagnosis').value,
                instructions: formData.get('instructions') || document.getElementById('instructions').value,
                medications: []
            };
            
            // Collect medications data
            const names = formData.getAll('medication_name[]');
            const dosages = formData.getAll('dosage[]');
            const frequencies = formData.getAll('frequency[]');
            const durations = formData.getAll('duration[]');
            const instructions = formData.getAll('medication_instructions[]');
            
            for (let i = 0; i < names.length; i++) {
                if (names[i]) {
                    data.medications.push({
                        medication_name: names[i],
                        dosage: dosages[i],
                        frequency: frequencies[i],
                        duration: durations[i],
                        instructions: instructions[i] || '',
                        form: 'tablet',
                        quantity: '1',
                        with_food: false,
                        is_priority: false
                    });
                }
            }
            
            fetch(prescriptionsApi + '?action=createPrescription', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('Prescription saved successfully!');
                    closePrescriptionModal();
                    loadAppointmentsNeedingPrescriptions();
                    loadAllPrescriptions();
                } else {
                    alert('Error: ' + (data.message || 'Failed to save prescription'));
                }
            })
            .catch(e => {
                console.error(e);
                alert('Network error saving prescription');
            });
        }

        function closePrescriptionModal() {
            document.getElementById('prescription-modal').classList.remove('show');
        }

        function closePrescriptionViewModal() {
            document.getElementById('prescription-view-modal').style.display = 'none';
        }

        // Credential File Viewer Functions
        function viewCredentialFile(fileUrl, isPdf, isImage) {
            const content = document.getElementById('credential-file-content');
            
            if (isPdf === 'true') {
                // Display PDF in iframe
                content.innerHTML = `<iframe src="${fileUrl}" style="width:100%;height:75vh;border:none"></iframe>`;
            } else if (isImage === 'true') {
                // Display image
                content.innerHTML = `<img src="${fileUrl}" style="max-width:100%;height:auto;display:block;margin:0 auto" />`;
            } else {
                // Fallback - download link
                content.innerHTML = `
                    <div style="padding:40px;text-align:center">
                        <i class="fas fa-file" style="font-size:64px;color:#054A91;margin-bottom:20px"></i>
                        <h3>Document Preview Not Available</h3>
                        <p>Click below to download the file</p>
                        <a href="${fileUrl}" download class="btn btn-primary" style="margin-top:20px">
                            <i class="fas fa-download"></i> Download File
                        </a>
                    </div>
                `;
            }
            
            document.getElementById('credential-file-modal').style.display = 'flex';
        }

        function closeCredentialFileModal() {
            document.getElementById('credential-file-modal').style.display = 'none';
            document.getElementById('credential-file-content').innerHTML = '';
        }

        // Treatment Management Functions
        function loadBranchTreatments() {
            fetch(apiBase + '?action=getBranchTreatments')
                .then(r => r.json())
                .then(data => {
                    const container = document.getElementById('treatments-list');
                    if (!data.success) {
                        container.innerHTML = '<div class="empty-state"><i class="fas fa-exclamation-circle"></i><h3>Error Loading Treatments</h3><p>' + (data.message || 'Failed to load treatments') + '</p></div>';
                        return;
                    }
                    
                    const treatments = data.treatments || [];
                    if (treatments.length === 0) {
                        container.innerHTML = '<div class="empty-state"><i class="fas fa-tooth"></i><h3>No Treatments Found</h3><p>Click "Add New Treatment" to create your first treatment or service.</p></div>';
                        return;
                    }
                    
                    let html = '<table><thead><tr><th>Treatment Name</th><th>Description</th><th>Price</th><th>Duration</th><th>Category</th><th>Status</th><th>Actions</th></tr></thead><tbody>';
                    
                    treatments.forEach(t => {
                        const statusColor = t.is_active ? 'green' : 'gray';
                        const statusText = t.is_active ? 'Active' : 'Inactive';
                        html += `<tr>
                            <td><strong>${t.name}</strong></td>
                            <td>${t.description || '-'}</td>
                            <td>₱${parseFloat(t.price).toFixed(2)}</td>
                            <td>${t.duration ? t.duration + ' min' : '-'}</td>
                            <td>${t.category || '-'}</td>
                            <td><span style="color:${statusColor};font-weight:600">${statusText}</span></td>
                            <td class="actions">
                                <button class="btn btn-primary" onclick='editTreatment(${JSON.stringify(t)})'>
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="btn ${t.is_active ? 'btn-warning' : 'btn-success'}" onclick="toggleTreatmentStatus(${t.id}, ${t.is_active})">
                                    <i class="fas fa-${t.is_active ? 'toggle-off' : 'toggle-on'}"></i> ${t.is_active ? 'Deactivate' : 'Activate'}
                                </button>
                            </td>
                        </tr>`;
                    });
                    
                    html += '</tbody></table>';
                    container.innerHTML = html;
                })
                .catch(e => {
                    console.error(e);
                    document.getElementById('treatments-list').innerHTML = '<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><h3>Network Error</h3><p>Failed to load treatments</p></div>';
                });
        }

        function showAddTreatmentModal() {
            document.getElementById('treatment-modal-title').textContent = 'Add New Treatment';
            document.getElementById('treatment-action').value = 'create';
            
            // Clear form
            document.getElementById('treatment-id').value = '';
            document.getElementById('treatment-name').value = '';
            document.getElementById('treatment-description').value = '';
            document.getElementById('treatment-price').value = '';
            document.getElementById('treatment-duration').value = '';
            document.getElementById('treatment-category').value = '';
            document.getElementById('treatment-active').checked = true;
            
            document.getElementById('treatment-modal').classList.add('show');
        }

        function editTreatment(treatment) {
            document.getElementById('treatment-modal-title').textContent = 'Edit Treatment';
            document.getElementById('treatment-action').value = 'update';
            
            // Fill form
            document.getElementById('treatment-id').value = treatment.id;
            document.getElementById('treatment-name').value = treatment.name;
            document.getElementById('treatment-description').value = treatment.description || '';
            document.getElementById('treatment-price').value = treatment.price;
            document.getElementById('treatment-duration').value = treatment.duration || '';
            document.getElementById('treatment-category').value = treatment.category || '';
            document.getElementById('treatment-active').checked = treatment.is_active == 1;
            
            document.getElementById('treatment-modal').classList.add('show');
        }

        function saveTreatment(event) {
            event.preventDefault();
            
            const action = document.getElementById('treatment-action').value;
            const data = {
                treatment_id: document.getElementById('treatment-id').value,
                name: document.getElementById('treatment-name').value,
                description: document.getElementById('treatment-description').value,
                price: document.getElementById('treatment-price').value,
                duration: document.getElementById('treatment-duration').value,
                category: document.getElementById('treatment-category').value,
                is_active: document.getElementById('treatment-active').checked ? 1 : 0
            };
            
            const apiAction = action === 'create' ? 'createTreatment' : 'updateTreatment';
            
            fetch(apiBase + '?action=' + apiAction, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            })
            .then(r => {
                if (!r.ok) {
                    throw new Error('Server returned ' + r.status);
                }
                return r.text();
            })
            .then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Invalid JSON response:', text);
                    throw new Error('Invalid server response');
                }
            })
            .then(data => {
                if (data.success) {
                    alert(action === 'create' ? 'Treatment created successfully!' : 'Treatment updated successfully!');
                    closeTreatmentModal();
                    loadBranchTreatments();
                } else {
                    alert('Error: ' + (data.message || 'Failed to save treatment'));
                }
            })
            .catch(e => {
                console.error(e);
                alert('Network error saving treatment');
            });
        }

        function toggleTreatmentStatus(treatmentId, currentStatus) {
            const newStatus = currentStatus ? 0 : 1;
            const statusText = newStatus ? 'activate' : 'deactivate';
            
            if (!confirm(`Are you sure you want to ${statusText} this treatment?`)) {
                return;
            }
            
            fetch(apiBase + '?action=toggleTreatmentStatus', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({treatment_id: treatmentId, is_active: newStatus})
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('Treatment status updated successfully!');
                    loadBranchTreatments();
                } else {
                    alert('Error: ' + (data.message || 'Failed to update status'));
                }
            })
            .catch(e => {
                console.error(e);
                alert('Network error updating treatment status');
            });
        }

        function closeTreatmentModal() {
            document.getElementById('treatment-modal').classList.remove('show');
        }

        // Enhanced Staff Management Functions
        function showAddStaffModal() {
            document.getElementById('staff-modal-title').textContent = 'Add New Staff Member';
            document.getElementById('staff-action').value = 'create';
            document.getElementById('staff-submit-btn').textContent = 'Create Staff';
            
            // Clear form
            document.getElementById('staff-id').value = '';
            document.getElementById('staff-name').value = '';
            document.getElementById('staff-email').value = '';
            document.getElementById('staff-phone').value = '';
            document.getElementById('staff-role').value = 'staff';
            document.getElementById('staff-specialization').value = '';
            document.getElementById('staff-password').value = '';
            document.getElementById('staff-password-confirm').value = '';
            
            // Show password fields for new staff
            document.getElementById('password-fields').style.display = 'block';
            document.getElementById('staff-password').required = true;
            document.getElementById('staff-password-confirm').required = true;
            
            document.getElementById('staff-modal').classList.add('show');
        }

        function loadBranchStaff() {
            fetch(staffApi + '?action=getBranchStaff')
                .then(r => r.json())
                .then(data => {
                    const container = document.getElementById('staff-grid');
                    if (!data.success) {
                        container.innerHTML = '<div class="card">Error: ' + (data.message || 'Failed to load staff') + '</div>';
                        return;
                    }
                    
                    const staff = data.staff || [];
                    if (staff.length === 0) {
                        container.innerHTML = '<div class="card">No staff found in your branch.</div>';
                        return;
                    }
                    
                    let html = '';
                    staff.forEach(s => {
                        const statusColor = s.is_active ? '#10b981' : '#ef4444';
                        const statusText = s.is_active ? 'Active' : 'Inactive';
                        const statusBtnText = s.is_active ? 'Set Inactive' : 'Set Active';
                        const statusBtnIcon = s.is_active ? 'fa-ban' : 'fa-check-circle';
                        
                        html += `
                            <div class="staff-card">
                                <div class="staff-header">
                                    <h4>${s.name}</h4>
                                    <div style="display:flex;gap:10px">
                                        <button class="btn btn-primary" onclick="editStaff(${s.id}, '${s.name}', '${s.email}', '${s.phone || ''}', '${s.role || 'staff'}', '${s.specialization || ''}')">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="btn ${s.is_active ? 'btn-warning' : 'btn-success'}" onclick="toggleStaffStatus(${s.id}, ${s.is_active ? 0 : 1}, '${s.name}')">
                                            <i class="fas ${statusBtnIcon}"></i> ${statusBtnText}
                                        </button>
                                    </div>
                                </div>
                                <div><strong>Role:</strong> ${s.role}</div>
                                <div><strong>Email:</strong> ${s.email}</div>
                                ${s.phone ? '<div><strong>Phone:</strong> ' + s.phone + '</div>' : ''}
                                ${s.specialization ? '<div><strong>Specialization:</strong> ' + s.specialization + '</div>' : ''}
                                <div><strong>Status:</strong> <span style="color:${statusColor};font-weight:600">${statusText}</span></div>
                                ${s.last_login ? '<div><strong>Last Login:</strong> ' + s.last_login + '</div>' : ''}
                            </div>
                        `;
                    });
                    container.innerHTML = html;
                })
                .catch(e => {
                    console.error(e);
                    document.getElementById('staff-grid').innerHTML = '<div class="card">Network error loading staff</div>';
                });
        }

        function editStaff(id, name, email, phone, role, specialization) {
            document.getElementById('staff-modal-title').textContent = 'Edit Staff Member';
            document.getElementById('staff-action').value = 'update';
            document.getElementById('staff-submit-btn').textContent = 'Save Changes';
            
            document.getElementById('staff-id').value = id;
            document.getElementById('staff-name').value = name;
            document.getElementById('staff-email').value = email;
            document.getElementById('staff-phone').value = phone;
            document.getElementById('staff-role').value = role;
            document.getElementById('staff-specialization').value = specialization;
            
            // Hide password fields for existing staff
            document.getElementById('password-fields').style.display = 'none';
            document.getElementById('staff-password').required = false;
            document.getElementById('staff-password-confirm').required = false;
            
            document.getElementById('staff-modal').classList.add('show');
        }

        // Delete function removed - staff members should be deactivated instead of deleted

        function saveStaffMember(event) {
            event.preventDefault();
            
            const action = document.getElementById('staff-action').value;
            const password = document.getElementById('staff-password').value;
            const passwordConfirm = document.getElementById('staff-password-confirm').value;
            
            // Validate passwords if creating new staff or if password is provided
            if (action === 'create' || password) {
                if (password !== passwordConfirm) {
                    alert('Passwords do not match!');
                    return;
                }
                if (password.length < 6) {
                    alert('Password must be at least 6 characters long!');
                    return;
                }
            }
            
            const data = {
                staff_id: document.getElementById('staff-id').value,
                name: document.getElementById('staff-name').value,
                email: document.getElementById('staff-email').value,
                phone: document.getElementById('staff-phone').value,
                role: document.getElementById('staff-role').value,
                specialization: document.getElementById('staff-specialization').value
            };
            
            if (password) {
                data.password = password;
            }
            
            const apiAction = action === 'create' ? 'createStaffMember' : 'updateStaffMember';
            
            fetch(staffApi + '?action=' + apiAction, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert(action === 'create' ? 'Staff member created successfully!' : 'Staff member updated successfully!');
                    closeStaffModal();
                    loadBranchStaff();
                    loadComprehensiveAnalytics(); // Refresh staff count
                } else {
                    alert('Error: ' + (data.message || 'Failed to save staff member'));
                }
            })
            .catch(e => {
                console.error(e);
                alert('Network error saving staff member');
            });
        }

        function toggleStaffStatus(staffId, newStatus, staffName) {
            const action = newStatus ? 'activate' : 'deactivate';
            const statusText = newStatus ? 'activate' : 'deactivate';
            
            if (!confirm(`Are you sure you want to ${statusText} ${staffName}?`)) {
                return;
            }
            
            fetch(staffApi + '?action=toggleStatus', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    staff_id: staffId,
                    is_active: newStatus
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert(`✅ Staff member ${newStatus ? 'activated' : 'deactivated'} successfully!`);
                    loadBranchStaff();
                } else {
                    alert('❌ Error: ' + (data.message || 'Failed to update status'));
                }
            })
            .catch(e => {
                console.error(e);
                alert('❌ Network error updating staff status');
            });
        }

        function showStaffPerformance() {
            fetch(staffApi + '?action=getStaffPerformance')
                .then(r => r.json())
                .then(data => {
                    const container = document.getElementById('staff-performance');
                    if (!data.success) {
                        container.innerHTML = '<div class="card">Error: ' + (data.message || 'Failed to load performance data') + '</div>';
                        return;
                    }
                    
                    const performance = data.performance || [];
                    if (performance.length === 0) {
                        container.innerHTML = '<div class="card">No performance data available.</div>';
                        return;
                    }
                    
                    let html = '<div class="card"><h4>Staff Performance</h4><table><thead><tr><th>Staff</th><th>Appointments</th><th>Revenue</th><th>Avg Rating</th></tr></thead><tbody>';
                    performance.forEach(p => {
                        html += `<tr>
                            <td>${p.staff_name}</td>
                            <td>${p.appointment_count || 0}</td>
                            <td>₱${(p.total_revenue || 0).toFixed(2)}</td>
                            <td>${p.avg_rating ? parseFloat(p.avg_rating).toFixed(1) + '/5' : 'N/A'}</td>
                        </tr>`;
                    });
                    html += '</tbody></table></div>';
                    container.innerHTML = html;
                    container.style.display = 'block';
                })
                .catch(e => {
                    console.error(e);
                    document.getElementById('staff-performance').innerHTML = '<div class="card">Network error loading performance data</div>';
                });
        }

        // Credentials Management Functions
        function loadCurrentCredentials() {
            fetch(apiBase + '?action=getCurrentCredentials')
                .then(r => r.json())
                .then(data => {
                    const container = document.getElementById('credentials-display');
                    if (!data.success) {
                        container.innerHTML = '<div style="text-align:center;color:#666;padding:20px">Error loading credentials: ' + (data.message || 'Unknown error') + '</div>';
                        return;
                    }
                    
                    const credentials = data.credentials || {};
                    let html = '';
                    
                    if (credentials.dentist) {
                        const d = credentials.dentist;
                        // Fix file path - prepend ../../ to go from /public/dashboard/ to root
                        const licenseFileUrl = d.license_file ? `../../${d.license_file}` : null;
                        const fileExtension = d.license_file ? d.license_file.split('.').pop().toLowerCase() : null;
                        const isPdf = fileExtension === 'pdf';
                        const isImage = ['jpg', 'jpeg', 'png', 'gif'].includes(fileExtension);
                        
                        html += `
                            <div style="background:#f9f9f9;padding:15px;border-radius:8px;margin:10px 0">
                                <h5>Personal Credentials</h5>
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                                    <div><strong>License:</strong> ${d.license_number || 'Not set'}</div>
                                    <div><strong>Specialization:</strong> ${d.specialization || 'Not set'}</div>
                                    <div><strong>Experience:</strong> ${d.experience_years || 'Not set'} years</div>
                                    <div><strong>Education:</strong> ${d.education || 'Not set'}</div>
                                </div>
                                ${d.professional_bio ? `<div style="margin-top:10px"><strong>Bio:</strong><br>${d.professional_bio}</div>` : ''}
                                ${d.license_file ? `
                                    <div style="margin-top:10px">
                                        <strong>License File:</strong> 
                                        <button onclick="viewCredentialFile('${licenseFileUrl}', '${isPdf}', '${isImage}')" class="btn btn-small btn-primary" style="margin-left:10px">
                                            <i class="fas fa-eye"></i> View Document
                                        </button>
                                        <a href="${licenseFileUrl}" download class="btn btn-small" style="margin-left:5px">
                                            <i class="fas fa-download"></i> Download
                                        </a>
                                    </div>
                                ` : ''}
                            </div>
                        `;
                        
                        // Pre-fill form
                        document.getElementById('license-number').value = d.license_number || '';
                        document.getElementById('dentist-specialization').value = d.specialization || '';
                        document.getElementById('experience-years').value = d.experience_years || '';
                        document.getElementById('education').value = d.education || '';
                        document.getElementById('professional-bio').value = d.professional_bio || '';
                    }
                    
                    if (credentials.clinic) {
                        const c = credentials.clinic;
                        
                        // Parse JSON fields
                        const clinicPhotos = c.clinic_photos ? (typeof c.clinic_photos === 'string' ? JSON.parse(c.clinic_photos) : c.clinic_photos) : [];
                        const certifications = c.certifications ? (typeof c.certifications === 'string' ? JSON.parse(c.certifications) : c.certifications) : [];
                        
                        html += `
                            <div style="background:#f9f9f9;padding:15px;border-radius:8px;margin:10px 0">
                                <h5>Clinic Credentials</h5>
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                                    <div><strong>Clinic License:</strong> ${c.clinic_license || 'Not set'}</div>
                                    <div><strong>Business Permit:</strong> ${c.business_permit || 'Not set'}</div>
                                    <div><strong>Accreditations:</strong> ${c.accreditations || 'Not set'}</div>
                                    <div><strong>Established:</strong> ${c.established_year || 'Not set'}</div>
                                </div>
                                ${c.services_offered ? `<div style="margin-top:10px"><strong>Services:</strong><br>${c.services_offered}</div>` : ''}
                                
                                ${clinicPhotos.length > 0 ? `
                                    <div style="margin-top:15px">
                                        <strong><i class="fas fa-images"></i> Clinic Photos (${clinicPhotos.length}):</strong>
                                        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:10px;margin-top:10px">
                                            ${clinicPhotos.map((photo, idx) => {
                                                const photoUrl = '../../' + photo;
                                                return `
                                                    <div style="position:relative;border:2px solid #e5e7eb;border-radius:8px;overflow:hidden;cursor:pointer" onclick="viewCredentialFile('${photoUrl}', 'false', 'true')">
                                                        <img src="${photoUrl}" style="width:100%;height:150px;object-fit:cover" alt="Clinic Photo ${idx + 1}" />
                                                        <div style="position:absolute;bottom:0;left:0;right:0;background:rgba(0,0,0,0.7);color:white;padding:5px;text-align:center;font-size:12px">
                                                            <i class="fas fa-search-plus"></i> Click to view
                                                        </div>
                                                    </div>
                                                `;
                                            }).join('')}
                                        </div>
                                    </div>
                                ` : '<div style="margin-top:10px;color:#666"><i class="fas fa-info-circle"></i> No clinic photos uploaded</div>'}
                                
                                ${certifications.length > 0 ? `
                                    <div style="margin-top:15px">
                                        <strong><i class="fas fa-certificate"></i> Certifications (${certifications.length}):</strong>
                                        <div style="margin-top:10px">
                                            ${certifications.map((cert, idx) => {
                                                const certUrl = '../../' + cert;
                                                const fileExtension = cert.split('.').pop().toLowerCase();
                                                const isPdf = fileExtension === 'pdf';
                                                const isImage = ['jpg', 'jpeg', 'png', 'gif'].includes(fileExtension);
                                                const fileName = cert.split('/').pop();
                                                
                                                return `
                                                    <div style="background:white;padding:12px;margin:8px 0;border-radius:8px;border:1px solid #e5e7eb;display:flex;align-items:center;justify-content:space-between">
                                                        <div style="display:flex;align-items:center;gap:10px">
                                                            <i class="fas fa-${isPdf ? 'file-pdf' : 'file-image'}" style="font-size:24px;color:${isPdf ? '#dc2626' : '#3b82f6'}"></i>
                                                            <span style="font-weight:500">${fileName}</span>
                                                        </div>
                                                        <div>
                                                            <button onclick="viewCredentialFile('${certUrl}', '${isPdf}', '${isImage}')" class="btn btn-small btn-primary">
                                                                <i class="fas fa-eye"></i> View
                                                            </button>
                                                            <a href="${certUrl}" download class="btn btn-small" style="margin-left:5px">
                                                                <i class="fas fa-download"></i> Download
                                                            </a>
                                                        </div>
                                                    </div>
                                                `;
                                            }).join('')}
                                        </div>
                                    </div>
                                ` : '<div style="margin-top:10px;color:#666"><i class="fas fa-info-circle"></i> No certifications uploaded</div>'}
                            </div>
                        `;
                        
                        // Pre-fill form
                        document.getElementById('clinic-license').value = c.clinic_license || '';
                        document.getElementById('business-permit').value = c.business_permit || '';
                        document.getElementById('accreditations').value = c.accreditations || '';
                        document.getElementById('established-year').value = c.established_year || '';
                        document.getElementById('services-offered').value = c.services_offered || '';
                    }
                    
                    if (html === '') {
                        html = '<div style="text-align:center;color:#666;padding:20px">No credentials uploaded yet</div>';
                    }
                    
                    container.innerHTML = html;
                })
                .catch(e => {
                    console.error(e);
                    document.getElementById('credentials-display').innerHTML = '<div style="text-align:center;color:#666;padding:20px">Network error loading credentials</div>';
                });
        }

        function saveDentistCredentials(event) {
            event.preventDefault();
            
            const formData = new FormData();
            formData.append('license_number', document.getElementById('license-number').value);
            formData.append('specialization', document.getElementById('dentist-specialization').value);
            formData.append('experience_years', document.getElementById('experience-years').value);
            formData.append('education', document.getElementById('education').value);
            formData.append('professional_bio', document.getElementById('professional-bio').value);
            
            const licenseFile = document.getElementById('license-file').files[0];
            if (licenseFile) {
                formData.append('license_file', licenseFile);
            }
            
            fetch(apiBase + '?action=saveDentistCredentials', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('Personal credentials saved successfully!');
                    loadCurrentCredentials();
                } else {
                    alert('Error: ' + (data.message || 'Failed to save credentials'));
                }
            })
            .catch(e => {
                console.error(e);
                alert('Network error saving credentials');
            });
        }

        function saveClinicCredentials(event) {
            event.preventDefault();
            
            const formData = new FormData();
            formData.append('clinic_license', document.getElementById('clinic-license').value);
            formData.append('business_permit', document.getElementById('business-permit').value);
            formData.append('accreditations', document.getElementById('accreditations').value);
            formData.append('established_year', document.getElementById('established-year').value);
            formData.append('services_offered', document.getElementById('services-offered').value);
            
            const clinicPhotos = document.getElementById('clinic-photos').files;
            for (let i = 0; i < clinicPhotos.length; i++) {
                formData.append('clinic_photos[]', clinicPhotos[i]);
            }
            
            const certifications = document.getElementById('clinic-certifications').files;
            for (let i = 0; i < certifications.length; i++) {
                formData.append('clinic_certifications[]', certifications[i]);
            }
            
            fetch(apiBase + '?action=saveClinicCredentials', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('Clinic credentials saved successfully!');
                    loadCurrentCredentials();
                } else {
                    alert('Error: ' + (data.message || 'Failed to save credentials'));
                }
            })
            .catch(e => {
                console.error(e);
                alert('Network error saving credentials');
            });
        }

        function closeStaffModal() {
            document.getElementById('staff-modal').classList.remove('show');
        }

        function logout(){
            fetch('../../src/controllers/AuthController.php?action=logout',{method:'POST'})
            .then(r=>r.json()).then(()=>window.location.href='../auth/login.php').catch(()=>window.location.href='../auth/login.php');
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const prescriptionModal = document.getElementById('prescription-modal');
            const staffModal = document.getElementById('staff-modal');
            const appointmentModal = document.getElementById('appointment-details-modal');
            const treatmentModal = document.getElementById('treatment-modal');
            
            if (event.target === prescriptionModal) {
                closePrescriptionModal();
            }
            if (event.target === staffModal) {
                closeStaffModal();
            }
            if (event.target === appointmentModal) {
                closeAppointmentDetailsModal();
            }
            if (event.target === treatmentModal) {
                closeTreatmentModal();
            }
        }

        // ============================================
        // Clinic Status Management Functions
        // ============================================

        function loadClinicStatus() {
            fetch('../api/clinic-status.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayTodayStatus(data.today);
                        displayUpcomingStatus(data.upcoming);
                    } else {
                        alert('Error loading clinic status: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Network error loading clinic status');
                });
        }

        function displayTodayStatus(statusData) {
            const container = document.getElementById('today-status-display');
            
            if (!statusData) {
                container.innerHTML = `
                    <div class="status-badge status-open">
                        <i class="fas fa-check-circle"></i>
                        <div>
                            <strong>Open</strong>
                            <small>Using default schedule</small>
                        </div>
                    </div>
                `;
                return;
            }

            const statusIcons = {
                'open': 'fa-check-circle',
                'closed': 'fa-times-circle',
                'busy': 'fa-clock',
                'fully_booked': 'fa-calendar-times'
            };

            const statusTexts = {
                'open': 'Open',
                'closed': 'Closed Today',
                'busy': 'Busy',
                'fully_booked': 'Fully Booked'
            };

            container.innerHTML = `
                <div class="status-badge status-${statusData.status}">
                    <i class="fas ${statusIcons[statusData.status]}"></i>
                    <div>
                        <strong>${statusTexts[statusData.status]}</strong>
                        ${statusData.reason ? `<small>${statusData.reason}</small>` : ''}
                    </div>
                </div>
            `;
        }

        function displayUpcomingStatus(upcomingList) {
            const container = document.getElementById('upcoming-status-list');
            
            if (!upcomingList || upcomingList.length === 0) {
                container.innerHTML = '<div class="empty-state"><i class="fas fa-calendar-check"></i> No upcoming status changes scheduled</div>';
                return;
            }

            const statusTexts = {
                'open': 'Open',
                'closed': 'Closed',
                'busy': 'Busy',
                'fully_booked': 'Fully Booked'
            };

            let html = '<div class="upcoming-list">';
            upcomingList.forEach(item => {
                const date = new Date(item.status_date);
                const formattedDate = date.toLocaleDateString('en-US', { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' });
                
                html += `
                    <div class="upcoming-item">
                        <div class="upcoming-date">
                            <i class="fas fa-calendar"></i>
                            ${formattedDate}
                        </div>
                        <div class="upcoming-status">
                            <span class="badge ${item.status}">${statusTexts[item.status]}</span>
                            ${item.reason ? `<small>${item.reason}</small>` : ''}
                        </div>
                        <button type="button" class="btn-remove" onclick="removeScheduledStatus('${item.status_date}')" title="Remove">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                `;
            });
            html += '</div>';
            
            container.innerHTML = html;
        }

        function setTodayStatus(status) {
            const reason = document.getElementById('today-status-reason').value;
            
            fetch('../api/clinic-status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    status_date: '<?php echo date("Y-m-d"); ?>',
                    status: status,
                    reason: reason || null
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`✅ Clinic status set to "${status}" successfully`);
                    document.getElementById('today-status-reason').value = '';
                    loadClinicStatus(); // Reload to show updated status
                } else {
                    alert('❌ Error: ' + (data.message || 'Failed to update status'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('❌ Network error updating status');
            });
        }

        function setFutureStatus(event) {
            event.preventDefault();
            
            const date = document.getElementById('future-status-date').value;
            const status = document.getElementById('future-status-select').value;
            const reason = document.getElementById('future-status-reason').value;
            
            fetch('../api/clinic-status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    status_date: date,
                    status: status,
                    reason: reason || null
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`✅ Status scheduled for ${date} successfully`);
                    document.getElementById('future-status-form').reset();
                    loadClinicStatus(); // Reload to show updated list
                } else {
                    alert('❌ Error: ' + (data.message || 'Failed to schedule status'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('❌ Network error scheduling status');
            });
        }

        function removeScheduledStatus(date) {
            if (!confirm(`Remove scheduled status for ${date}?`)) {
                return;
            }
            
            fetch(`../api/clinic-status.php?date=${date}`, {
                method: 'DELETE'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Scheduled status removed successfully');
                    loadClinicStatus(); // Reload to show updated list
                } else {
                    alert('❌ Error: ' + (data.message || 'Failed to remove status'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('❌ Network error removing status');
            });
        }
    </script>
</body>
</html>
