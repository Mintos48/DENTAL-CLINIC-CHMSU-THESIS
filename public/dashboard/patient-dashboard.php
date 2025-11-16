<?php
/**
 * Patient Dashboard
 * Secure patient portal for managing appointments
 */

// Include session management and authentication
require_once '../../src/config/session.php';

// Require login and patient role
requireLogin();

// Check if user is a patient
if (!hasRole('patient')) {
    // Redirect non-patients to appropriate dashboard
    $role = getCurrentUserRole();
    if ($role === 'admin' || $role === 'staff') {
        header('Location: admin-dashboard.php');
        exit();
    } else {
        header('Location: ../auth/login.php');
        exit();
    }
}

// Handle clinic selection from clinic listing page
$selectedClinic = isset($_GET['clinic']) ? $_GET['clinic'] : null;
$selectedBranchId = isset($_GET['branch_id']) ? $_GET['branch_id'] : null;
$bookingAction = isset($_GET['action']) && $_GET['action'] === 'book';

// Clinic mapping - updated with correct names from database
$clinicData = [
    'ardent' => ['id' => 2, 'name' => 'Ardent Dental Clinic', 'location' => 'Bonifacio, Silay City'],
    'gamboa' => ['id' => 3, 'name' => 'Gamboa Dental Clinic', 'location' => 'Poblacion I, E.B. Magalona'],
    'happy-teeth' => ['id' => 1, 'name' => 'Happy Teeth Dental', 'location' => 'Zone 2, Talisay City'],
    'happy-teeth-dental' => ['id' => 1, 'name' => 'Happy Teeth Dental', 'location' => 'Zone 2, Talisay City']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard - Dental Clinic Management System</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            /* Updated Color Palette */
            --primary-50: #e8f2ff;
            --primary-100: #bdd9ff;
            --primary-200: #81A4CD;
            --primary-300: #3E7CB1;
            --primary-400: #2a6ba8;
            --primary-500: #054A91;
            --primary-600: #043d7a;
            --primary-700: #033063;
            --primary-800: #02234c;
            --primary-900: #011635;
            
            --secondary-50: #DBE4EE;
            --secondary-100: #c9d5e2;
            --secondary-200: #b7c6d6;
            --secondary-300: #a5b7ca;
            --secondary-400: #93a8be;
            --secondary-500: #81A4CD;
            --secondary-600: #6d8db0;
            --secondary-700: #597693;
            --secondary-800: #455f76;
            --secondary-900: #314859;
            
            --accent-50: #fff4e6;
            --accent-100: #ffe0b3;
            --accent-200: #ffcc80;
            --accent-300: #ffb84d;
            --accent-400: #ffa41a;
            --accent-500: #f17300;
            --accent-600: #d65f00;
            --accent-700: #bb4b00;
            --accent-800: #a03700;
            --accent-900: #852300;
            
            --success-50: #ecfdf5;
            --success-100: #d1fae5;
            --success-200: #a7f3d0;
            --success-300: #6ee7b7;
            --success-400: #34d399;
            --success-500: #10b981;
            --success-600: #059669;
            --success-700: #047857;
            --success-800: #065f46;
            --success-900: #064e3b;
            
            --warning-50: #fffbeb;
            --warning-100: #fef3c7;
            --warning-200: #fde68a;
            --warning-300: #fcd34d;
            --warning-400: #fbbf24;
            --warning-500: #f59e0b;
            --warning-600: #d97706;
            --warning-700: #b45309;
            --warning-800: #92400e;
            --warning-900: #78350f;
            
            --error-50: #fef2f2;
            --error-100: #fee2e2;
            --error-200: #fecaca;
            --error-300: #fca5a5;
            --error-400: #f87171;
            --error-500: #ef4444;
            --error-600: #dc2626;
            --error-700: #b91c1c;
            --error-800: #991b1b;
            --error-900: #7f1d1d;
            
            --medical-accent: #8b5cf6;
            --medical-light: #f3e8ff;
            --gradient-primary: linear-gradient(135deg, var(--primary-500), var(--primary-600));
            --gradient-success: linear-gradient(135deg, var(--success-500), var(--success-600));
            --gradient-warning: linear-gradient(135deg, var(--warning-500), var(--warning-600));
            
            /* Enhanced Spacing System */
            --spacing-1: 0.25rem;
            --spacing-2: 0.5rem;
            --spacing-3: 0.75rem;
            --spacing-4: 1rem;
            --spacing-5: 1.25rem;
            --spacing-6: 1.5rem;
            --spacing-8: 2rem;
            --spacing-10: 2.5rem;
            --spacing-12: 3rem;
            --spacing-16: 4rem;
            --spacing-20: 5rem;
            --spacing-24: 6rem;
            
            /* Enhanced Border Radius */
            --radius-xs: 0.25rem;
            --radius-sm: 0.375rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --radius-xl: 1rem;
            --radius-2xl: 1.5rem;
            --radius-3xl: 2rem;
            --radius-full: 9999px;
            
            /* Enhanced Shadow System */
            --shadow-xs: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-sm: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --shadow-2xl: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            --shadow-inner: inset 0 2px 4px 0 rgba(0, 0, 0, 0.06);
            
            /* Typography Scale */
            --text-xs: 0.75rem;
            --text-sm: 0.875rem;
            --text-base: 1rem;
            --text-lg: 1.125rem;
            --text-xl: 1.25rem;
            --text-2xl: 1.5rem;
            --text-3xl: 1.875rem;
            --text-4xl: 2.25rem;
            --text-5xl: 3rem;
            
            /* Transitions */
            --transition-fast: 150ms ease-in-out;
            --transition-normal: 250ms ease-in-out;
            --transition-slow: 350ms ease-in-out;
            
            /* Font Weights */
            --font-light: 300;
            --font-normal: 400;
            --font-medium: 500;
            --font-semibold: 600;
            --font-bold: 700;
            --font-extrabold: 800;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            line-height: 1.6;
            color: var(--secondary-900);
            font-size: var(--text-base);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .dashboard-background {
            background: linear-gradient(135deg, 
                var(--primary-50) 0%, 
                var(--primary-100) 25%, 
                var(--success-50) 50%, 
                var(--primary-100) 75%, 
                var(--primary-200) 100%);
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }
        
        .dashboard-background::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="20" height="20" patternUnits="userSpaceOnUse"><path d="M 20 0 L 0 0 0 20" fill="none" stroke="rgba(255,255,255,0.03)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>') repeat;
            pointer-events: none;
        }
        
        .modern-navbar {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(24px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: var(--spacing-4) 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: var(--shadow-lg);
            transition: var(--transition-normal);
        }
        
        .navbar-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 var(--spacing-6);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
        }
        
        .navbar-brand {
            display: flex;
            align-items: center;
            gap: var(--spacing-3);
            cursor: pointer;
            transition: var(--transition-normal);
        }
        
        .navbar-brand:hover {
            transform: translateY(-1px);
        }
        
        .navbar-brand .logo {
            width: 52px;
            height: 52px;
            background: var(--gradient-primary);
            border-radius: var(--radius-xl);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: var(--text-2xl);
            box-shadow: var(--shadow-md);
            position: relative;
            overflow: hidden;
        }
        
        .navbar-brand .logo::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.6s ease;
        }
        
        .navbar-brand:hover .logo::before {
            left: 100%;
        }
        
        .navbar-brand h1 {
            font-size: var(--text-xl);
            font-weight: var(--font-bold);
            background: var(--gradient-primary);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin: 0;
            transition: all 0.3s ease;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 280px;
        }
        
        .navbar-brand h1:hover {
            transform: translateY(-1px);
        }
        
        /* Loading state for navbar title */
        .navbar-brand h1 .loading-skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
            border-radius: 4px;
        }
        
        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        
        /* Responsive navbar title */
        @media (max-width: 768px) {
            .navbar-brand h1 {
                font-size: var(--text-lg);
                max-width: 200px;
            }
        }
        
        @media (max-width: 480px) {
            .navbar-brand h1 {
                font-size: var(--text-base);
                max-width: 150px;
            }
        }
        
        /* Responsive back button */
        @media (max-width: 768px) {
            .nav-link.back-btn span {
                display: none;
            }
            
            .nav-link.back-btn {
                padding: var(--spacing-2) var(--spacing-3);
                min-width: 40px;
                justify-content: center;
            }
        }
        
        .navbar-menu {
            display: flex;
            align-items: center;
            gap: var(--spacing-2);
            position: relative;
            overflow: visible;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            gap: var(--spacing-2);
            padding: var(--spacing-3) var(--spacing-4);
            border-radius: var(--radius-xl);
            color: var(--secondary-600);
            text-decoration: none;
            font-weight: var(--font-medium);
            transition: var(--transition-normal);
            position: relative;
            font-size: var(--text-sm);
            white-space: nowrap;
        }
        
        .nav-link::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: var(--radius-xl);
            background: transparent;
            transition: var(--transition-normal);
            z-index: -1;
        }
        
        .nav-link:hover::before,
        .nav-link.active::before {
            background: var(--primary-50);
            box-shadow: var(--shadow-sm);
        }
        
        .nav-link:hover,
        .nav-link.active {
            color: var(--primary-700);
            transform: translateY(-1px);
        }
        
        .nav-link.logout {
            background: var(--error-50);
            color: var(--error-600);
            border: 1px solid var(--error-200);
        }
        
        .nav-link.logout:hover {
            background: var(--error-100);
            color: var(--error-700);
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }
        
        /* Back button styles */
        .nav-link.back-btn {
            background: var(--secondary-50);
            color: var(--secondary-700);
            border: 1px solid var(--secondary-200);
            transition: all 0.3s ease;
        }
        
        .nav-link.back-btn:hover {
            background: var(--secondary-100);
            color: var(--secondary-800);
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
            border-color: var(--secondary-300);
        }
        
        .nav-link.back-btn i {
            transition: transform 0.3s ease;
        }
        
        .nav-link.back-btn:hover i {
            transform: translateX(-2px);
        }
        
        .navbar-user-info {
            margin-left: var(--spacing-4);
            margin-right: var(--spacing-2);
        }
        
        .user-profile-nav {
            display: flex;
            align-items: center;
            gap: var(--spacing-3);
            padding: var(--spacing-2) var(--spacing-4);
            background: var(--secondary-50);
            border-radius: var(--radius-xl);
            border: 1px solid var(--secondary-100);
            transition: var(--transition-normal);
        }
        
        .user-profile-nav:hover {
            background: var(--secondary-100);
            transform: translateY(-1px);
            box-shadow: var(--shadow-sm);
        }
        
        .user-avatar-nav {
            width: 36px;
            height: 36px;
            background: var(--gradient-primary);
            border-radius: var(--radius-full);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: var(--font-semibold);
            font-size: var(--text-sm);
            box-shadow: var(--shadow-sm);
        }
        
        .user-details-nav {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-1);
        }
        
        .user-name-nav {
            font-weight: var(--font-semibold);
            color: var(--secondary-900);
            font-size: var(--text-sm);
        }
        
        .user-branch-nav {
            font-size: var(--text-xs);
            color: var(--secondary-500);
            font-weight: var(--font-medium);
        }
        
        .navbar-user-info {
            margin-left: auto;
            margin-right: var(--spacing-4);
        }
        
        .user-profile-nav {
            display: flex;
            align-items: center;
            gap: var(--spacing-3);
            padding: var(--spacing-2) var(--spacing-3);
            background: rgba(255, 255, 255, 0.7);
            border-radius: var(--radius-lg);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .user-avatar-nav {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-500), var(--primary-600));
            color: white;
            font-weight: 600;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .user-details-nav {
            display: flex;
            flex-direction: column;
            min-width: 120px;
        }
        
        .user-name-nav {
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--secondary-900);
            line-height: 1.2;
        }
        
        .user-branch-nav {
            font-size: 0.75rem;
            color: var(--secondary-500);
            line-height: 1.2;
        }
        
        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: var(--spacing-8) var(--spacing-6);
            position: relative;
            z-index: 1;
        }
        
        .welcome-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(24px);
            border-radius: var(--radius-3xl);
            padding: var(--spacing-10);
            margin-bottom: var(--spacing-10);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: var(--shadow-2xl);
            position: relative;
            overflow: hidden;
            transition: var(--transition-normal);
        }
        
        .welcome-header:hover {
            transform: translateY(-2px);
            box-shadow: 0 32px 64px -12px rgba(0, 0, 0, 0.15);
        }
        
        .welcome-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(135deg, var(--primary-500), var(--medical-accent), var(--success-500));
            border-radius: var(--radius-3xl) var(--radius-3xl) 0 0;
        }
        
        .welcome-header::after {
            content: '';
            position: absolute;
            bottom: -50%;
            right: -20%;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, var(--primary-100) 0%, transparent 70%);
            opacity: 0.3;
            border-radius: 50%;
            pointer-events: none;
        }
        
        .welcome-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: var(--spacing-8);
            position: relative;
            z-index: 2;
        }
        
        .welcome-text {
            flex: 1;
        }
        
        .welcome-text h2 {
            font-size: var(--text-4xl);
            font-weight: var(--font-extrabold);
            color: var(--secondary-900);
            margin-bottom: var(--spacing-3);
            display: flex;
            align-items: center;
            gap: var(--spacing-3);
            line-height: 1.2;
        }
        
        .welcome-text .wave {
            animation: wave 2.5s ease-in-out infinite;
            font-size: var(--text-3xl);
        }
        
        .user-name-text {
            background: var(--gradient-primary);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            position: relative;
        }
        
        .branch-info {
            display: flex;
            align-items: center;
            gap: var(--spacing-2);
            font-size: var(--text-lg);
            color: var(--secondary-600);
            font-weight: var(--font-medium);
            margin-bottom: var(--spacing-2);
        }
        
        .branch-info i {
            color: var(--primary-500);
            font-size: var(--text-base);
        }
        
        .branch-name-text {
            color: var(--primary-700);
            font-weight: var(--font-semibold);
        }
        
        .user-role-info {
            display: flex;
            align-items: center;
            gap: var(--spacing-2);
            font-size: var(--text-base);
            color: var(--medical-accent);
            font-weight: var(--font-semibold);
            margin-bottom: var(--spacing-2);
        }
        
        .user-role-info i {
            font-size: var(--text-sm);
        }
        
        .last-login-info {
            display: flex;
            align-items: center;
            gap: var(--spacing-2);
            font-size: var(--text-sm);
            color: var(--secondary-500);
            font-weight: var(--font-medium);
        }
        
        .last-login-info i {
            color: var(--success-500);
        }
        /* Remove duplicate styles */
        
        @keyframes wave {
            0%, 100% { transform: rotate(0deg); }
            25% { transform: rotate(20deg); }
            75% { transform: rotate(-10deg); }
        }
        
        .user-avatar {
            width: 96px;
            height: 96px;
            background: var(--gradient-primary);
            border-radius: var(--radius-3xl);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: var(--text-3xl);
            font-weight: var(--font-extrabold);
            box-shadow: var(--shadow-xl);
            cursor: pointer;
            transition: var(--transition-normal);
            position: relative;
            overflow: hidden;
            border: 4px solid rgba(255, 255, 255, 0.9);
        }
        
        .user-avatar:hover {
            transform: scale(1.05) rotate(5deg);
            box-shadow: 0 20px 40px rgba(14, 165, 233, 0.4);
        }
        
        .user-avatar::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent 30%, rgba(255, 255, 255, 0.3) 50%, transparent 70%);
            transform: translateX(-100%) translateY(-100%) rotate(45deg);
            transition: transform 0.8s ease;
        }
        
        .user-avatar:hover::before {
            transform: translateX(100%) translateY(100%) rotate(45deg);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: var(--spacing-8);
            margin-bottom: var(--spacing-12);
        }
        
        .enhanced-stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(24px);
            border-radius: var(--radius-2xl);
            padding: var(--spacing-8);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: var(--shadow-lg);
            transition: var(--transition-normal);
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }
        
        .enhanced-stat-card:hover {
            transform: translateY(-6px) scale(1.02);
            box-shadow: var(--shadow-2xl);
        }
        
        .enhanced-stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: var(--gradient-primary);
            border-radius: var(--radius-2xl) var(--radius-2xl) 0 0;
        }
        
        .enhanced-stat-card.primary::before {
            background: var(--gradient-primary);
        }
        
        .enhanced-stat-card.warning::before {
            background: var(--gradient-warning);
        }
        
        .enhanced-stat-card.success::before {
            background: var(--gradient-success);
        }
        
        .enhanced-stat-card::after {
            content: '';
            position: absolute;
            bottom: -50%;
            right: -20%;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, var(--primary-50) 0%, transparent 70%);
            opacity: 0.3;
            border-radius: 50%;
            pointer-events: none;
            transition: var(--transition-slow);
        }
        
        .enhanced-stat-card.warning::after {
            background: radial-gradient(circle, var(--warning-50) 0%, transparent 70%);
        }
        
        .enhanced-stat-card.success::after {
            background: radial-gradient(circle, var(--success-50) 0%, transparent 70%);
        }
        
        .enhanced-stat-card:hover::after {
            transform: scale(1.2);
            opacity: 0.5;
        }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            z-index: 2;
        }
        
        .stat-number {
            font-size: var(--text-4xl);
            font-weight: var(--font-extrabold);
            color: var(--secondary-900);
            line-height: 1;
            margin-bottom: var(--spacing-2);
        }
        
        .stat-label {
            font-size: var(--text-base);
            color: var(--secondary-600);
            font-weight: var(--font-semibold);
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }
        
        .stat-icon {
            width: 64px;
            height: 64px;
            border-radius: var(--radius-xl);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: var(--text-2xl);
            color: white;
            box-shadow: var(--shadow-md);
            transition: var(--transition-normal);
        }
        
        .stat-icon.primary {
            background: var(--gradient-primary);
        }
        
        .stat-icon.warning {
            background: var(--gradient-warning);
        }
        
        .stat-icon.success {
            background: var(--gradient-success);
        }
        
        .enhanced-stat-card:hover .stat-icon {
            transform: scale(1.1) rotate(5deg);
            box-shadow: var(--shadow-lg);
        }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--spacing-4);
        }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: white;
        }
        
        .stat-icon.primary {
            background: linear-gradient(135deg, var(--primary-500), var(--primary-600));
        }
        
        .stat-icon.warning {
            background: linear-gradient(135deg, var(--warning-500), var(--warning-600));
        }
        
        .stat-icon.success {
            background: linear-gradient(135deg, var(--success-500), var(--success-600));
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--secondary-900);
            line-height: 1;
        }
        
        .stat-label {
            color: var(--secondary-600);
            font-weight: 500;
            margin-top: var(--spacing-1);
        }
        
        .section-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(24px);
            border-radius: var(--radius-3xl);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: var(--shadow-xl);
            margin-bottom: var(--spacing-12);
            overflow: hidden;
            transition: var(--transition-normal);
            position: relative;
        }
        
        .section-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-2xl);
        }
        
        .section-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: var(--gradient-primary);
            border-radius: var(--radius-3xl) var(--radius-3xl) 0 0;
        }
        
        .section-header {
            padding: var(--spacing-8) var(--spacing-10);
            background: linear-gradient(135deg, var(--primary-50), var(--medical-light));
            border-bottom: 1px solid rgba(255, 255, 255, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .section-header::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(139, 92, 246, 0.1) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }
        
        .section-header h3 {
            font-size: var(--text-2xl);
            font-weight: var(--font-bold);
            color: var(--secondary-900);
            margin: 0;
            display: flex;
            align-items: center;
            gap: var(--spacing-4);
            position: relative;
            z-index: 2;
        }
        
        .section-header h3 i {
            color: var(--medical-accent);
            font-size: var(--text-xl);
        }
        
        .badge {
            background: linear-gradient(135deg, var(--primary-100), var(--primary-200));
            color: var(--primary-800);
            padding: var(--spacing-2) var(--spacing-4);
            border-radius: var(--radius-full);
            font-size: var(--text-xs);
            font-weight: var(--font-semibold);
            border: 1px solid var(--primary-200);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }
        
        .section-content {
            padding: var(--spacing-10);
            position: relative;
        }
        
        /* Modern Form Styles */
        .modern-form {
            position: relative;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: var(--spacing-6);
            margin-bottom: var(--spacing-8);
        }
        
        .form-group {
            position: relative;
        }
        
        .form-group label {
            display: flex;
            align-items: center;
            gap: var(--spacing-2);
            font-weight: var(--font-semibold);
            color: var(--secondary-700);
            margin-bottom: var(--spacing-3);
            font-size: var(--text-base);
        }
        
        .form-group label i {
            color: var(--primary-500);
            width: 16px;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: var(--spacing-4) var(--spacing-5);
            border: 2px solid var(--secondary-200);
            border-radius: var(--radius-xl);
            font-size: var(--text-base);
            font-family: inherit;
            background: rgba(255, 255, 255, 0.9);
            transition: var(--transition-normal);
            backdrop-filter: blur(8px);
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-500);
            box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.1);
            background: rgba(255, 255, 255, 1);
            transform: translateY(-1px);
        }
        
        .form-group small {
            display: block;
            margin-top: var(--spacing-2);
            color: var(--secondary-500);
            font-size: var(--text-sm);
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }
        
        /* Selected Clinic Info Card */
        .selected-clinic-info {
            margin-bottom: var(--spacing-6);
        }
        
        .clinic-selection-card {
            display: flex;
            align-items: center;
            gap: var(--spacing-4);
            padding: var(--spacing-4);
            background: linear-gradient(135deg, var(--primary-50), var(--primary-100));
            border: 2px solid var(--primary-200);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-sm);
            transition: var(--transition-normal);
        }
        
        .clinic-selection-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-300);
        }
        
        .clinic-icon {
            width: 48px;
            height: 48px;
            background: var(--primary-500);
            border-radius: var(--radius-full);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            flex-shrink: 0;
        }
        
        .clinic-details {
            flex: 1;
        }
        
        .clinic-details h4 {
            margin: 0 0 var(--spacing-1) 0;
            color: var(--text-900);
            font-size: var(--text-lg);
            font-weight: var(--font-semibold);
        }
        
        .clinic-details p {
            margin: 0;
            color: var(--text-600);
            font-size: var(--text-sm);
        }
        
        .clinic-status {
            flex-shrink: 0;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: var(--spacing-1);
            padding: var(--spacing-1) var(--spacing-3);
            border-radius: var(--radius-full);
            font-size: var(--text-xs);
            font-weight: var(--font-medium);
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }
        
        .status-badge.success {
            background: var(--success-100);
            color: var(--success-700);
            border: 1px solid var(--success-200);
        }
        
        /* Enhanced Button System */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: var(--spacing-2);
            padding: var(--spacing-3) var(--spacing-6);
            border: none;
            border-radius: var(--radius-xl);
            font-size: var(--text-base);
            font-weight: var(--font-semibold);
            font-family: inherit;
            cursor: pointer;
            transition: var(--transition-normal);
            text-decoration: none;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.3s ease, height 0.3s ease;
        }
        
        .btn:hover::before {
            width: 300px;
            height: 300px;
        }
        
        .btn-primary {
            background: var(--gradient-primary);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, var(--secondary-100), var(--secondary-200));
            color: var(--secondary-700);
            border: 1px solid var(--secondary-300);
        }
        
        .btn-secondary:hover {
            background: linear-gradient(135deg, var(--secondary-200), var(--secondary-300));
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, var(--error-500), var(--error-600));
            color: white;
        }
        
        .btn-danger:hover {
            background: linear-gradient(135deg, var(--error-600), var(--error-700));
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .btn-large {
            padding: var(--spacing-4) var(--spacing-8);
            font-size: var(--text-lg);
            border-radius: var(--radius-2xl);
        }
        
        .btn-small {
            padding: var(--spacing-2) var(--spacing-4);
            font-size: var(--text-sm);
            border-radius: var(--radius-lg);
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }
        
        .quick-actions {
            display: flex;
            gap: var(--spacing-4);
            flex-wrap: wrap;
            align-items: center;
            justify-content: flex-start;
        }
        
        .quick-action-btn {
            display: inline-flex;
            align-items: center;
            gap: var(--spacing-3);
            padding: var(--spacing-4) var(--spacing-6);
            background: var(--gradient-primary);
            color: white;
            text-decoration: none;
            border-radius: var(--radius-2xl);
            font-weight: var(--font-semibold);
            font-size: var(--text-base);
            box-shadow: var(--shadow-md);
            transition: var(--transition-normal);
            position: relative;
            overflow: hidden;
        }
        
        .quick-action-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.6s ease;
        }
        
        .quick-action-btn:hover::before {
            left: 100%;
        }
        
        .quick-action-btn:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: var(--shadow-xl);
        }
        
        .profile-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: var(--spacing-6);
        }
        
        .profile-info-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border-radius: var(--radius-xl);
            padding: var(--spacing-6);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .profile-header {
            display: flex;
            align-items: center;
            gap: var(--spacing-4);
            margin-bottom: var(--spacing-6);
            padding-bottom: var(--spacing-4);
            border-bottom: 1px solid var(--secondary-200);
        }
        
        .profile-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-500), var(--primary-600));
            color: white;
            font-weight: 700;
            font-size: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        }
        
        .profile-basic h4 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--secondary-900);
            margin: 0 0 var(--spacing-1) 0;
        }
        
        .profile-basic p {
            color: var(--secondary-600);
            margin: 0 0 var(--spacing-2) 0;
        }
        
        .profile-role-badge {
            background: linear-gradient(135deg, var(--success-500), var(--success-600));
            color: white;
            padding: var(--spacing-1) var(--spacing-3);
            border-radius: var(--radius-full);
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .profile-details {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-4);
        }
        
        .detail-item {
            display: flex;
            align-items: center;
            gap: var(--spacing-3);
            padding: var(--spacing-3);
            background: var(--secondary-50);
            border-radius: var(--radius-lg);
        }
        
        .detail-item i {
            width: 20px;
            color: var(--primary-600);
        }
        
        .detail-item div {
            flex: 1;
        }
        
        .detail-item label {
            display: block;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--secondary-500);
            margin-bottom: var(--spacing-1);
        }
        
        .detail-item span {
            font-weight: 500;
            color: var(--secondary-900);
        }
        
        .profile-stats-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border-radius: var(--radius-xl);
            padding: var(--spacing-6);
            border: 1px solid rgba(255, 255, 255, 0.3);
            height: fit-content;
        }
        
        .profile-stats-card h5 {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--secondary-900);
            margin: 0 0 var(--spacing-4) 0;
        }
        
        .profile-stats {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-4);
        }
        
        .stat-item {
            text-align: center;
            padding: var(--spacing-4);
            background: var(--primary-50);
            border-radius: var(--radius-lg);
            border: 1px solid var(--primary-100);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 800;
            color: var(--primary-700);
            margin-bottom: var(--spacing-1);
        }
        
        .stat-label {
            font-size: 0.875rem;
            color: var(--secondary-600);
            font-weight: 500;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: var(--spacing-6);
            margin-bottom: var(--spacing-6);
        }

        /* Enhanced Profile Editing Styles */
        .section-actions {
            display: flex;
            gap: var(--spacing-2);
        }

        .form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--spacing-6);
            padding-bottom: var(--spacing-4);
            border-bottom: 2px solid var(--primary-200);
        }

        .form-header h4 {
            margin: 0;
            color: var(--primary-700);
            display: flex;
            align-items: center;
            gap: var(--spacing-2);
            font-size: var(--text-lg);
            font-weight: 600;
        }

        .form-actions {
            display: flex;
            gap: var(--spacing-2);
        }

        .form-section-header {
            grid-column: 1 / -1;
            margin: var(--spacing-6) 0 var(--spacing-3) 0;
        }

        .form-section-header h5 {
            margin: 0;
            color: var(--secondary-700);
            display: flex;
            align-items: center;
            gap: var(--spacing-2);
            font-size: var(--text-base);
            font-weight: 600;
            padding-bottom: var(--spacing-2);
            border-bottom: 1px solid var(--secondary-200);
        }

        .form-section-header h5 i {
            color: var(--primary-500);
        }

        .form-group-full {
            grid-column: 1 / -1;
        }

        .form-group label {
            margin-bottom: var(--spacing-2);
            font-weight: 500;
            color: var(--secondary-700);
            display: flex;
            align-items: center;
            gap: var(--spacing-2);
            font-size: var(--text-sm);
        }

        .form-group label i {
            color: var(--primary-500);
            width: 16px;
            text-align: center;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: var(--spacing-3) var(--spacing-4);
            border: 2px solid var(--secondary-200);
            border-radius: var(--radius-lg);
            font-size: var(--text-sm);
            transition: var(--transition-normal);
            background: white;
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-400);
            box-shadow: 0 0 0 3px rgba(5, 74, 145, 0.1);
        }

        .form-group small {
            margin-top: var(--spacing-1);
            font-size: var(--text-xs);
            color: var(--secondary-500);
        }

        /* Checkbox Group Styles */
        .checkbox-group {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-3);
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: var(--spacing-3);
            cursor: pointer;
            padding: var(--spacing-3);
            border-radius: var(--radius-lg);
            transition: var(--transition-normal);
        }

        .checkbox-label:hover {
            background: var(--primary-50);
        }

        .checkbox-label input[type="checkbox"] {
            display: none;
        }

        .checkmark {
            width: 20px;
            height: 20px;
            background: white;
            border: 2px solid var(--secondary-300);
            border-radius: var(--radius-sm);
            position: relative;
            transition: var(--transition-normal);
            flex-shrink: 0;
        }

        .checkbox-label input[type="checkbox"]:checked + .checkmark {
            background: var(--primary-500);
            border-color: var(--primary-500);
        }

        .checkbox-label input[type="checkbox"]:checked + .checkmark::after {
            content: '✓';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-weight: bold;
            font-size: 12px;
        }

        .checkbox-text {
            display: flex;
            align-items: center;
            gap: var(--spacing-2);
            color: var(--secondary-700);
            font-size: var(--text-sm);
        }

        .checkbox-text i {
            color: var(--primary-500);
        }

        /* Modal Styles */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            border-radius: var(--radius-2xl);
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: var(--shadow-2xl);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--spacing-6);
            border-bottom: 1px solid var(--secondary-200);
        }

        .modal-header h4 {
            margin: 0;
            color: var(--primary-700);
            display: flex;
            align-items: center;
            gap: var(--spacing-2);
        }

        .modal-close {
            background: none;
            border: none;
            font-size: var(--text-lg);
            color: var(--secondary-500);
            cursor: pointer;
            padding: var(--spacing-2);
            border-radius: var(--radius-lg);
            transition: var(--transition-normal);
        }

        .modal-close:hover {
            background: var(--secondary-100);
            color: var(--secondary-700);
        }

        .modal-body {
            padding: var(--spacing-6);
        }

        /* Badge Styles */
        .badge {
            padding: var(--spacing-1) var(--spacing-3);
            border-radius: var(--radius-full);
            font-size: var(--text-xs);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        #profile-status-badge {
            background: var(--success-100);
            color: var(--success-700);
        }

        #profile-status-badge.editing {
            background: var(--warning-100);
            color: var(--warning-700);
        }

        /* Responsive Design for Profile Section */
        @media (max-width: 768px) {
            .profile-grid {
                grid-template-columns: 1fr;
                gap: var(--spacing-4);
            }
            
            .profile-details {
                grid-template-columns: 1fr;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .form-header {
                flex-direction: column;
                gap: var(--spacing-3);
                align-items: stretch;
            }
            
            .form-actions {
                justify-content: stretch;
            }
            
            .form-actions .btn {
                flex: 1;
            }
            
            .section-header {
                flex-direction: column;
                gap: var(--spacing-3);
                align-items: stretch;
            }
            
            .section-actions {
                justify-content: stretch;
            }
            
            .modal-content {
                width: 95%;
                margin: var(--spacing-4);
            }
            
            .profile-header {
                flex-direction: column;
                text-align: center;
                gap: var(--spacing-4);
            }
            
            .profile-avatar {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
            }
        }
        
        /* Enhanced Table Styles */
        .enhanced-table {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
            border-radius: var(--radius-2xl);
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--secondary-100);
            transition: var(--transition-normal);
        }
        
        .enhanced-table:hover {
            box-shadow: var(--shadow-xl);
        }
        
        .enhanced-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .enhanced-table thead {
            background: var(--gradient-primary);
            color: white;
            position: relative;
        }
        
        .enhanced-table thead::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, var(--medical-accent), var(--primary-400));
        }
        
        .enhanced-table th {
            padding: var(--spacing-5) var(--spacing-6);
            text-align: left;
            font-weight: var(--font-semibold);
            font-size: var(--text-sm);
            text-transform: uppercase;
            letter-spacing: 0.025em;
            position: relative;
        }
        
        .enhanced-table th i {
            margin-right: var(--spacing-2);
            opacity: 0.9;
        }
        
        .enhanced-table td {
            padding: var(--spacing-5) var(--spacing-6);
            border-bottom: 1px solid var(--secondary-100);
            color: var(--secondary-700);
            font-size: var(--text-base);
            transition: var(--transition-fast);
        }
        
        .enhanced-table tbody tr {
            transition: var(--transition-fast);
        }
        
        .enhanced-table tbody tr:hover {
            background: linear-gradient(135deg, var(--primary-25), var(--medical-light));
            transform: scale(1.005);
        }
        
        .enhanced-table tbody tr:last-child td {
            border-bottom: none;
        }
        
        /* Enhanced Status Badge System */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: var(--spacing-2);
            padding: var(--spacing-2) var(--spacing-4);
            border-radius: var(--radius-full);
            font-size: var(--text-xs);
            font-weight: var(--font-bold);
            text-transform: uppercase;
            letter-spacing: 0.025em;
            position: relative;
            overflow: hidden;
            border: 1px solid;
            box-shadow: var(--shadow-sm);
        }
        
        .status-badge::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: currentColor;
            opacity: 0.3;
        }
        
        .status-badge.pending {
            background: linear-gradient(135deg, var(--warning-50), var(--warning-100));
            color: var(--warning-700);
            border-color: var(--warning-200);
        }
        
        .status-badge.approved {
            background: linear-gradient(135deg, var(--success-50), var(--success-100));
            color: var(--success-700);
            border-color: var(--success-200);
        }
        
        .status-badge.cancelled {
            background: linear-gradient(135deg, var(--error-50), var(--error-100));
            color: var(--error-700);
            border-color: var(--error-200);
        }
        
        .status-badge.completed {
            background: linear-gradient(135deg, var(--primary-50), var(--primary-100));
            color: var(--primary-700);
            border-color: var(--primary-200);
        }
        
        .status-badge.referred {
            background: linear-gradient(135deg, var(--medical-light), #e9d5ff);
            color: var(--medical-accent);
            border-color: #c4b5fd;
        }
        
        /* Price Display Styling */
        .price-display {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            padding: 8px 12px;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 8px;
            border: 1px solid #dee2e6;
            min-width: 100px;
        }
        
        .price-display.referral-price {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            border-color: #ffeb3b;
        }
        
        .price-amount {
            font-weight: 700;
            font-size: 1rem;
            color: #28a745;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .price-display.referral-price .price-amount {
            color: #856404;
        }
        
        .price-label {
            font-size: 0.75rem;
            color: #6c757d;
            text-align: center;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .price-display:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: all 0.2s ease;
        }
        
        /* Treatment and Service Info Styling */
        .treatment-info {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        
        .treatment-name {
            font-weight: 600;
            color: var(--primary-600);
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .treatment-duration,
        .service-category {
            font-size: 0.75rem;
            color: var(--secondary-500);
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        /* Branch Information Styling */
        .branch-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        
        .branch-name {
            font-weight: 600;
            color: var(--info-600);
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .branch-location {
            font-size: 0.75rem;
            color: var(--secondary-500);
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .action-group {
            display: flex;
            gap: var(--spacing-2);
            align-items: center;
        }
        
        /* Enhanced Empty State */
        .empty-state {
            text-align: center;
            padding: var(--spacing-16);
            background: linear-gradient(135deg, var(--secondary-50), var(--primary-50));
            border-radius: var(--radius-2xl);
            margin: var(--spacing-8);
        }
            color: var(--secondary-500);
        }
        
        .empty-state i {
            font-size: 4rem;
        .empty-state {
            text-align: center;
            padding: var(--spacing-16);
            background: linear-gradient(135deg, var(--secondary-50), var(--primary-50));
            border-radius: var(--radius-2xl);
            margin: var(--spacing-8);
        }
        
        .empty-state i {
            font-size: var(--text-5xl);
            margin-bottom: var(--spacing-6);
            opacity: 0.4;
            color: var(--primary-400);
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        .empty-state h4 {
            font-size: var(--text-xl);
            font-weight: var(--font-bold);
            margin-bottom: var(--spacing-3);
            color: var(--secondary-800);
        }
        
        .empty-state p {
            color: var(--secondary-600);
            font-size: var(--text-base);
            margin-bottom: var(--spacing-6);
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
        }
        
        /* Enhanced Loading Skeleton */
        .loading-skeleton {
            background: linear-gradient(90deg, var(--secondary-200) 25%, var(--secondary-100) 50%, var(--secondary-200) 75%);
            background-size: 200% 100%;
            animation: shimmer 1.8s infinite;
            border-radius: var(--radius-md);
            height: 20px;
            margin-bottom: var(--spacing-2);
        }
        
        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }
        
        /* Connection Status */
        .connection-status {
            position: fixed;
            top: 100px;
            right: var(--spacing-6);
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
            padding: var(--spacing-3) var(--spacing-5);
            border-radius: var(--radius-full);
            border: 1px solid var(--secondary-200);
            box-shadow: var(--shadow-lg);
            font-size: var(--text-sm);
            font-weight: var(--font-semibold);
            z-index: 1000;
            transition: var(--transition-normal);
        }
        
        .connection-status.online {
            color: var(--success-700);
            border-color: var(--success-200);
        }
        
        .connection-status.offline {
            color: var(--error-700);
            border-color: var(--error-200);
            background: rgba(254, 242, 242, 0.95);
        }
        
        /* Floating Refresh Button */
        .floating-refresh {
            position: fixed;
            bottom: var(--spacing-8);
            right: var(--spacing-8);
            width: 64px;
            height: 64px;
            background: var(--gradient-primary);
            border-radius: var(--radius-full);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: var(--text-xl);
            box-shadow: var(--shadow-xl);
            cursor: pointer;
            transition: var(--transition-normal);
            z-index: 1000;
            border: 4px solid rgba(255, 255, 255, 0.9);
        }
        
        .floating-refresh:hover {
            transform: scale(1.1) rotate(15deg);
            box-shadow: var(--shadow-2xl);
        }
        
        .floating-refresh.spinning {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        /* Reminder Notification */
        .reminder-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 16px 20px;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(102, 126, 234, 0.3);
            z-index: 1000;
            min-width: 320px;
            max-width: 400px;
            transform: translateX(100%);
            transition: transform 0.3s ease-in-out;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .reminder-notification.show {
            transform: translateX(0);
        }
        
        .reminder-notification .notification-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 8px;
        }
        
        .reminder-notification .notification-icon {
            width: 24px;
            height: 24px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }
        
        .reminder-notification .notification-title {
            font-weight: 600;
            font-size: 16px;
        }
        
        .reminder-notification .notification-message {
            font-size: 14px;
            line-height: 1.4;
            margin-bottom: 12px;
            opacity: 0.95;
        }
        
        .reminder-notification .notification-actions {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
        }
        
        .reminder-notification .btn-dismiss {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .reminder-notification .btn-dismiss:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
        /* Alert System */
        .alert {
            padding: var(--spacing-4) var(--spacing-6);
            border-radius: var(--radius-xl);
            margin-bottom: var(--spacing-6);
            display: flex;
            align-items: center;
            gap: var(--spacing-3);
            font-weight: var(--font-medium);
            border: 1px solid;
            backdrop-filter: blur(8px);
            animation: slideInDown 0.5s ease;
        }
        
        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert-success {
            background: linear-gradient(135deg, var(--success-50), var(--success-100));
            color: var(--success-800);
            border-color: var(--success-200);
        }
        
        .alert-danger {
            background: linear-gradient(135deg, var(--error-50), var(--error-100));
            color: var(--error-800);
            border-color: var(--error-200);
        }
        
        .alert-info {
            background: linear-gradient(135deg, var(--primary-50), var(--primary-100));
            color: var(--primary-800);
            border-color: var(--primary-200);
        }
        
        /* Profile Grid Enhancements */
        .profile-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: var(--spacing-8);
        }
        
        .profile-info-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: var(--radius-2xl);
            padding: var(--spacing-8);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: var(--shadow-lg);
            transition: var(--transition-normal);
        }
        
        .profile-info-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-xl);
        }
        
        .profile-header {
            display: flex;
            align-items: center;
            gap: var(--spacing-6);
            margin-bottom: var(--spacing-8);
            padding-bottom: var(--spacing-6);
            border-bottom: 2px solid var(--secondary-100);
        }
        
        .profile-avatar {
            width: 96px;
            height: 96px;
            border-radius: var(--radius-3xl);
            background: var(--gradient-primary);
            color: white;
            font-weight: var(--font-extrabold);
            font-size: var(--text-3xl);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow-lg);
            border: 4px solid rgba(255, 255, 255, 0.9);
        }
        
        .profile-basic h4 {
            font-size: var(--text-2xl);
            font-weight: var(--font-bold);
            color: var(--secondary-900);
            margin: 0 0 var(--spacing-2) 0;
        }
        
        .profile-basic p {
            color: var(--secondary-600);
            margin: 0 0 var(--spacing-3) 0;
            font-size: var(--text-base);
        }
        
        .profile-role-badge {
            background: var(--gradient-success);
            color: white;
            padding: var(--spacing-2) var(--spacing-4);
            border-radius: var(--radius-full);
            font-size: var(--text-sm);
            font-weight: var(--font-semibold);
            box-shadow: var(--shadow-sm);
        }
        
        .profile-details {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-5);
        }
        
        .detail-item {
            display: flex;
            align-items: center;
            gap: var(--spacing-4);
            padding: var(--spacing-4);
            background: var(--secondary-50);
            border-radius: var(--radius-xl);
            transition: var(--transition-normal);
            border: 1px solid var(--secondary-100);
        }
        
        .detail-item:hover {
            background: var(--secondary-100);
            transform: translateX(4px);
        }
        
        .detail-item i {
            width: 20px;
            text-align: center;
            color: var(--primary-500);
            font-size: var(--text-lg);
        }
        
        .detail-item div {
            flex: 1;
        }
        
        .detail-item label {
            display: block;
            font-size: var(--text-sm);
            color: var(--secondary-500);
            font-weight: var(--font-medium);
            margin-bottom: var(--spacing-1);
        }
        
        .detail-item span {
            font-size: var(--text-base);
            color: var(--secondary-800);
            font-weight: var(--font-semibold);
        }
            padding: var(--spacing-2) var(--spacing-4);
            border-radius: var(--radius-lg);
            font-size: 0.75rem;
            font-weight: 600;
            z-index: 1000;
            transition: all 0.3s ease;
            display: none;
        }
        
        .connection-status.online {
            background: var(--success-100);
            color: var(--success-700);
            border: 1px solid var(--success-200);
        }
        
        .connection-status.offline {
            background: var(--error-100);
            color: var(--error-700);
            border: 1px solid var(--error-200);
        }
        
        .floating-refresh {
            position: fixed;
            bottom: var(--spacing-6);
            right: var(--spacing-6);
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, var(--primary-600), var(--primary-700));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            cursor: pointer;
            box-shadow: 0 8px 24px rgba(37, 99, 235, 0.3);
            transition: all 0.3s ease;
            z-index: 100;
        }
        
        .floating-refresh:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(37, 99, 235, 0.4);
        }
        
        .floating-refresh.spinning {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
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
        
        .user-info-loaded {
            animation: fadeInUp 0.6s ease-out;
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
        
        /* Enhanced Responsive Design */
        @media (max-width: 1200px) {
            .dashboard-container {
                padding: var(--spacing-6) var(--spacing-4);
            }
            
            .welcome-header {
                padding: var(--spacing-8);
            }
            
            .section-content {
                padding: var(--spacing-8);
            }
        }
        
        @media (max-width: 768px) {
            .navbar-container {
                padding: 0 var(--spacing-4);
                flex-wrap: wrap;
            }
            
            .navbar-brand h1 {
                display: none;
            }
            
            .navbar-menu {
                gap: var(--spacing-1);
                flex-wrap: wrap;
            }
            
            .nav-link {
                padding: var(--spacing-2) var(--spacing-3);
                font-size: var(--text-xs);
            }
            
            .nav-link span {
                display: none;
            }
            
            .navbar-user-info {
                margin-left: var(--spacing-2);
                margin-right: var(--spacing-1);
            }
            
            .user-profile-nav {
                padding: var(--spacing-1) var(--spacing-2);
                gap: var(--spacing-1);
            }
            
            .user-details-nav {
                display: none;
            }
            
            .user-avatar-nav {
                width: 32px;
                height: 32px;
                font-size: var(--text-xs);
            }
            
            .dashboard-container {
                padding: var(--spacing-4) var(--spacing-3);
            }
            
            .welcome-header {
                padding: var(--spacing-6);
                margin-bottom: var(--spacing-6);
            }
            
            .welcome-content {
                flex-direction: column;
                text-align: center;
                gap: var(--spacing-6);
            }
            
            .welcome-text h2 {
                font-size: var(--text-2xl);
            }
            
            .user-avatar {
                width: 80px;
                height: 80px;
                font-size: var(--text-2xl);
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
                gap: var(--spacing-4);
                margin-bottom: var(--spacing-8);
            }
            
            .enhanced-stat-card {
                padding: var(--spacing-6);
            }
            
            .section-card {
                margin-bottom: var(--spacing-8);
            }
            
            .section-header {
                padding: var(--spacing-6);
            }
            
            .section-content {
                padding: var(--spacing-6);
            }
            
            .form-grid {
                grid-template-columns: 1fr;
                gap: var(--spacing-4);
            }
            
            .quick-actions {
                flex-direction: column;
                align-items: stretch;
            }
            
            .btn-large {
                padding: var(--spacing-4) var(--spacing-6);
                font-size: var(--text-base);
            }
            
            .profile-grid {
                grid-template-columns: 1fr;
                gap: var(--spacing-6);
            }
            
            .profile-header {
                flex-direction: column;
                text-align: center;
                gap: var(--spacing-4);
                padding-bottom: var(--spacing-4);
            }
            
            .profile-avatar {
                width: 80px;
                height: 80px;
                font-size: var(--text-2xl);
            }
            
            .profile-details {
                gap: var(--spacing-3);
            }
            
            .detail-item {
                padding: var(--spacing-3);
                gap: var(--spacing-3);
            }
            
            .enhanced-table {
                font-size: var(--text-sm);
            }
            
            .enhanced-table th,
            .enhanced-table td {
                padding: var(--spacing-3) var(--spacing-4);
            }
            
            .floating-refresh {
                width: 56px;
                height: 56px;
                bottom: var(--spacing-6);
                right: var(--spacing-6);
                font-size: var(--text-lg);
            }
        }
        
        @media (max-width: 480px) {
            .navbar-brand .logo {
                width: 40px;
                height: 40px;
                font-size: var(--text-lg);
            }
            
            .welcome-header {
                padding: var(--spacing-4);
            }
            
            .welcome-text h2 {
                font-size: var(--text-xl);
                flex-direction: column;
                gap: var(--spacing-2);
            }
            
            .user-avatar {
                width: 64px;
                height: 64px;
                font-size: var(--text-xl);
            }
            
            .stats-grid {
                margin-bottom: var(--spacing-6);
            }
            
            .enhanced-stat-card {
                padding: var(--spacing-4);
            }
            
            .stat-number {
                font-size: var(--text-3xl);
            }
            
            .stat-icon {
                width: 48px;
                height: 48px;
                font-size: var(--text-lg);
            }
            
            .section-header {
                padding: var(--spacing-4);
            }
            
            .section-header h3 {
                font-size: var(--text-xl);
                gap: var(--spacing-2);
            }
            
            .section-content {
                padding: var(--spacing-4);
            }
            
            .form-group label {
                font-size: var(--text-sm);
            }
            
            .form-group input,
            .form-group select,
            .form-group textarea {
                padding: var(--spacing-3) var(--spacing-4);
                font-size: var(--text-sm);
            }
            
            .btn {
                padding: var(--spacing-3) var(--spacing-4);
                font-size: var(--text-sm);
            }
            
            .enhanced-table {
                font-size: var(--text-xs);
                overflow-x: auto;
            }
            
            .enhanced-table th,
            .enhanced-table td {
                padding: var(--spacing-2) var(--spacing-3);
                white-space: nowrap;
            }
            
            .status-badge {
                font-size: 0.625rem;
                padding: var(--spacing-1) var(--spacing-2);
            }
            
            .empty-state {
                padding: var(--spacing-8);
            }
            
            .empty-state i {
                font-size: var(--text-4xl);
            }
            
            .profile-avatar {
                width: 64px;
                height: 64px;
                font-size: var(--text-lg);
            }
        }
        
        /* Accessibility & Performance Enhancements */
        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
        
        /* Focus styles for accessibility */
        *:focus {
            outline: 2px solid var(--primary-500);
            outline-offset: 2px;
        }
        
        .btn:focus,
        .nav-link:focus,
        .quick-action-btn:focus {
            outline: 3px solid var(--primary-300);
            outline-offset: 2px;
        }
        
        /* High contrast mode support */
        @media (prefers-contrast: high) {
            .section-card,
            .enhanced-stat-card,
            .enhanced-table,
            .profile-info-card {
                border: 2px solid var(--secondary-800);
            }
            
            .btn {
                border: 2px solid currentColor;
            }
        }
        
        /* Performance: Hardware acceleration for animations */
        .enhanced-stat-card,
        .section-card,
        .btn,
        .nav-link,
        .user-avatar,
        .floating-refresh {
            will-change: transform;
        }
        
        /* Smooth scrolling */
        html {
            scroll-behavior: smooth;
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: var(--secondary-100);
            border-radius: var(--radius-full);
        }
        
        ::-webkit-scrollbar-thumb {
            background: var(--primary-300);
            border-radius: var(--radius-full);
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary-500);
        }
        
        /* Pulse animation for referral progress */
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.6; }
            100% { opacity: 1; }
        }
        
        /* Modal slide-in animation */
        @keyframes modalSlideIn {
            from {
                transform: translateY(-30px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
            
            .profile-stats {
                flex-direction: row;
                gap: var(--spacing-2);
            }
            
            .stat-item {
                padding: var(--spacing-2);
            }
            
            .stat-number {
                font-size: 1.5rem;
            }
            
            .dashboard-container {
                padding: var(--spacing-4);
            }
        
        /* Completion countdown animation */
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
        
        /* Completion Notification Animations */
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
        
        /* Pulse animation for progress indicators */
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.5;
            }
        }
        
        /* Pricing info highlight animation */
        .pricing-info {
            animation: fadeInUp 0.5s ease-out;
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
        
        /* Dynamic Logo Animations */
        @keyframes sparkle {
            0%, 100% { 
                opacity: 1; 
                transform: scale(1) rotate(0deg); 
            }
            25% { 
                opacity: 0.7; 
                transform: scale(1.2) rotate(90deg); 
            }
            50% { 
                opacity: 0.4; 
                transform: scale(0.8) rotate(180deg); 
            }
            75% { 
                opacity: 0.7; 
                transform: scale(1.1) rotate(270deg); 
            }
        }
        
        /* Logo hover effects and image styling */
        .logo {
            transition: transform 0.3s ease, filter 0.3s ease;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 48px;
            height: 48px;
            border-radius: 12px;
            overflow: hidden;
        }
        
        .logo:hover {
            transform: scale(1.1);
            filter: brightness(1.1) contrast(1.05);
        }
        
        .logo img {
            transition: all 0.3s ease;
            opacity: 0.9;
        }
        
        .logo img:hover {
            opacity: 1;
            transform: scale(1.05);
        }
        
        /* Loading state for logo */
        .logo-loading {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
            border-radius: 8px;
        }
        
        /* Modal animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideInUp {
            from { 
                opacity: 0; 
                transform: translate(-50%, -50%) translateY(20px) scale(0.95); 
            }
            to { 
                opacity: 1; 
                transform: translate(-50%, -50%) translateY(0) scale(1); 
            }
        }
        
        /* Prescription button styling */
        .btn-info {
            background: linear-gradient(135deg, #17a2b8, #138496);
            border: none;
            color: white;
            font-size: 0.8rem;
            padding: 6px 12px;
            border-radius: 15px;
            transition: all 0.3s ease;
        }
        
        .btn-info:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(23, 162, 184, 0.3);
        }
        
        /* Enhanced Notification System Styles (Same as Staff Dashboard) */
        .nav-notification {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: var(--spacing-2);
            padding: var(--spacing-3) var(--spacing-4);
            border-radius: var(--radius-xl);
            color: var(--secondary-600);
            cursor: pointer;
            transition: var(--transition-normal);
            font-weight: var(--font-medium);
            font-size: var(--text-sm);
            white-space: nowrap;
            text-decoration: none;
            background: transparent;
            border: none;
            overflow: visible;
        }
        
        .nav-notification::before {
            content: '';
            position: absolute;
            inset: 0;
            background: var(--primary-gradient);
            border-radius: inherit;
            opacity: 0;
            transition: var(--transition-normal);
        }
        
        .nav-notification:hover::before,
        .nav-notification.active::before {
            opacity: 0.05;
        }
        
        .nav-notification:hover,
        .nav-notification.active {
            color: var(--primary-700);
            transform: translateY(-1px);
        }

        #notification-bell {
            font-size: 16px;
            color: var(--secondary-600);
            transition: color 0.3s, transform 0.2s;
        }
        
        .nav-notification:hover #notification-bell,
        .nav-notification.active #notification-bell {
            color: var(--primary-700);
        }

        #notification-bell:hover {
            transform: scale(1.1);
        }

        .notification-dropdown {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            width: 450px;
            max-height: 400px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.25);
            border: 1px solid #e2e8f0;
            z-index: 9999;
            overflow: hidden;
            transition: opacity 0.2s ease, transform 0.2s ease;
            opacity: 1;
            transform: translateY(0);
        }

        .notification-dropdown::before {
            content: '';
            position: absolute;
            top: -8px;
            right: 20px;
            width: 0;
            height: 0;
            border-left: 8px solid transparent;
            border-right: 8px solid transparent;
            border-bottom: 8px solid white;
            filter: drop-shadow(0 -2px 4px rgba(0,0,0,0.1));
        }

        /* Responsive dropdown */
        @media (max-width: 768px) {
            .notification-dropdown {
                width: 380px;
                right: -20px;
            }
        }

        @media (max-width: 480px) {
            .notification-dropdown {
                width: 320px;
                right: -40px;
            }
        }

        @media (max-width: 360px) {
            .notification-dropdown {
                width: 280px;
                right: -60px;
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
            background: rgba(255,255,255,0.1);
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .refresh-btn {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 12px;
        }

        .refresh-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: rotate(180deg);
        }

        .notification-controls {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .enable-notifications-btn {
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
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
            background: rgba(255,255,255,0.3);
            border-color: rgba(255,255,255,0.5);
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
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
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
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .mark-read-btn:hover {
            background: rgba(255,255,255,0.3);
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
            align-items: center;
            gap: 12px;
        }

        .notification-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            color: white;
            flex-shrink: 0;
        }

        .notification-icon.appointment {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        }

        .notification-icon.referral {
            background: linear-gradient(135deg, #f97316, #ea580c);
        }

        .notification-details {
            flex: 1;
            min-width: 0;
        }

        .notification-title {
            font-weight: 600;
            color: #1f2937;
            font-size: 14px;
            margin-bottom: 4px;
        }

        .notification-message {
            color: #6b7280;
            font-size: 13px;
            line-height: 1.4;
            margin-bottom: 4px;
        }

        .notification-time {
            color: #9ca3af;
            font-size: 11px;
        }

        .notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #dc2626;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 11px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid white;
            z-index: 2;
            min-width: 20px;
        }

        .notification-badge.pulse {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.7;
            }
        }

        .no-notifications {
            padding: 40px 20px;
            text-align: center;
            color: #9ca3af;
            font-size: 14px;
        }

        .bell-shake {
            animation: bellShake 0.5s ease-in-out 3;
        }

        @keyframes bellShake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-3px); }
            75% { transform: translateX(3px); }
        }

    </style>
</head>
<body>
    <div class="dashboard-background">
        <!-- Modern Navigation -->
        <nav class="modern-navbar">
            <div class="navbar-container">
                <div class="navbar-brand">
                    <div class="logo" id="navbar-logo">
                        <!-- Default logo, will be updated dynamically -->
                        <img src="../../assets/images/happy-teeth-dental.png" alt="Clinic Logo" style="width: 40px; height: 40px; object-fit: contain; border-radius: 8px;" id="navbar-logo-img">
                    </div>
                    <h1 id="navbar-clinic-name">
                        <span class="loading-skeleton" style="width: 150px; height: 24px; display: inline-block;"></span>
                    </h1>
                </div>
                <div class="navbar-menu">
                    <!-- Back to Clinic Selection Button -->
                    <a href="#" class="nav-link back-btn" onclick="goBackToClinicSelection()" title="Choose Different Clinic">
                        <i class="fas fa-arrow-left"></i>
                        <span>Change Clinic</span>
                    </a>
                    
                    <!-- Enhanced Notification System (Same as Staff Dashboard) -->
                    <div class="nav-notification" title="Notifications" onclick="showNotificationDropdown()">
                        <i class="fas fa-bell" id="notification-bell"></i>
                        <span>Notifications</span>
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
                                    <button onclick="requestNotificationPermission()" class="enable-notifications-btn" id="enable-notifications-btn" title="Enable browser notifications">
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
                    
                    <div class="navbar-user-info">
                        <div class="user-profile-nav">
                            <div class="user-avatar-nav" id="user-avatar-nav">P</div>
                            <div class="user-details-nav">
                                <span class="user-name-nav" id="user-name-nav">Loading...</span>
                                <span class="user-branch-nav" id="user-branch-nav">Loading...</span>
                            </div>
                        </div>
                    </div>
                    <a href="#logout" class="nav-link logout" onclick="logout()">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
        </nav>
        
        <!-- Main Dashboard Container -->
        <div class="dashboard-container">
            <!-- Welcome Header -->
            <div class="welcome-header">
                <div class="welcome-content">
                    <div class="welcome-text">
                        <h2>
                            <span class="wave">👋</span>
                            Welcome back, <span id="user-name" class="user-name-text">
                                <div class="loading-skeleton" style="width: 120px; height: 24px; display: inline-block;"></div>
                            </span>!
                        </h2>
                        <p id="branch-info" class="branch-info">
                            <i class="fas fa-map-marker-alt"></i> 
                            <span id="branch-name" class="branch-name-text">
                                
                            </span>
                        </p>
                        <p id="user-role-info" class="user-role-info" style="margin-top: var(--spacing-2); color: var(--primary-600); font-weight: 500;">
                            <i class="fas fa-user-shield"></i> 
                            <span id="user-role-display">Loading role...</span>
                        </p>
                        <p id="last-login-info" class="last-login-info" style="margin-top: var(--spacing-1); color: var(--secondary-500); font-size: 0.875rem;">
                            <i class="fas fa-clock"></i> 
                            <span id="last-login-display">Checking last login...</span>
                        </p>
                    </div>
                    <div class="user-avatar" id="user-avatar" title="Click to view profile">
                        <div class="loading-skeleton" style="width: 100%; height: 100%; border-radius: 50%;"></div>
                    </div>
                </div>
            </div>
            
            <!-- Alert Messages -->
            <div id="alert" class="alert" style="display: none;"></div>
            
            <!-- Enhanced Stats Grid -->
            <div class="stats-grid">
                <div class="enhanced-stat-card primary">
                    <div class="stat-header">
                        <div>
                            <div class="stat-number" id="total-appointments">
                                <div class="loading-skeleton" style="width: 60px;"></div>
                            </div>
                            <div class="stat-label">Total Appointments</div>
                        </div>
                        <div class="stat-icon primary">
                            <i class="fas fa-calendar"></i>
                        </div>
                    </div>
                </div>
                
                <div class="enhanced-stat-card warning">
                    <div class="stat-header">
                        <div>
                            <div class="stat-number" id="pending-appointments">
                                <div class="loading-skeleton" style="width: 40px;"></div>
                            </div>
                            <div class="stat-label">Pending Approval</div>
                        </div>
                        <div class="stat-icon warning">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                </div>
                
                <div class="enhanced-stat-card success">
                    <div class="stat-header">
                        <div>
                            <div class="stat-number" id="upcoming-appointments">
                                <div class="loading-skeleton" style="width: 40px;"></div>
                            </div>
                            <div class="stat-label">Upcoming This Week</div>
                        </div>
                        <div class="stat-icon success">
                            <i class="fas fa-calendar-plus"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Clinic Operating Hours Section -->
            <div class="section-card">
                <div class="section-header">
                    <h3>
                        <i class="fas fa-clock"></i>
                        Clinic Operating Hours
                        <span id="clinic-status-badge" class="badge" style="padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; margin-left: 12px;">
                            <div class="loading-skeleton" style="width: 80px; height: 16px; display: inline-block;"></div>
                        </span>
                    </h3>
                </div>
                <div class="section-content">
                    <div class="clinic-hours-container">
                        <!-- Branch Information -->
                        <div class="branch-hours-card" id="branch-hours-card" style="background: linear-gradient(135deg, #f8fafc, #ffffff); border: 2px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 20px; position: relative; overflow: hidden;">
                            <!-- Status indicator line -->
                            <div id="hours-status-line" style="position: absolute; top: 0; left: 0; right: 0; height: 4px; background: linear-gradient(90deg, var(--primary-500), var(--primary-600)); transition: all 0.3s ease;"></div>
                            
                            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px;">
                                <div id="clinic-status-emoji" style="font-size: 36px; transition: all 0.3s ease;">🏥</div>
                                <div style="flex: 1;">
                                    <h4 id="clinic-status-title" style="margin: 0; color: var(--secondary-800); transition: color 0.3s ease;">
                                        <div class="loading-skeleton" style="width: 200px; height: 20px;"></div>
                                    </h4>
                                    <p id="clinic-status-subtitle" style="margin: 5px 0 0 0; color: var(--secondary-600); font-size: 0.875rem;">
                                        <div class="loading-skeleton" style="width: 150px; height: 16px;"></div>
                                    </p>
                                </div>
                                <div id="current-time-display" style="text-align: right; color: var(--secondary-600); font-size: 0.875rem;">
                                    <div id="current-time" style="font-weight: 600; color: var(--secondary-800);">
                                        <div class="loading-skeleton" style="width: 80px; height: 16px;"></div>
                                    </div>
                                    <div id="current-day" style="margin-top: 2px;">
                                        <div class="loading-skeleton" style="width: 70px; height: 14px;"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Quick Summary -->
                            <div id="hours-summary" style="background: rgba(59, 130, 246, 0.1); border-radius: 8px; padding: 12px; margin-bottom: 16px; border-left: 4px solid var(--primary-500);">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <i class="fas fa-info-circle" style="color: var(--primary-600);"></i>
                                    <span style="color: var(--primary-700); font-weight: 500; font-size: 0.875rem;">
                                        <div class="loading-skeleton" style="width: 300px; height: 16px;"></div>
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Detailed Schedule -->
                            <div id="detailed-schedule" style="display: none;">
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; margin-top: 16px;">
                                    <!-- Schedule items will be inserted here -->
                                </div>
                                
                                <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid #e2e8f0;">
                                    <button id="toggle-schedule" style="background: none; border: none; color: var(--primary-600); font-size: 0.875rem; cursor: pointer; display: flex; align-items: center; gap: 4px; transition: color 0.2s ease;" onclick="toggleScheduleDetails()">
                                        <i class="fas fa-chevron-up"></i>
                                        <span>Hide Details</span>
                                    </button>
                                </div>
                            </div>
                            
                            <div id="show-schedule" style="margin-top: 16px; padding-top: 16px; border-top: 1px solid #e2e8f0;">
                                <button id="toggle-schedule-btn" style="background: none; border: none; color: var(--primary-600); font-size: 0.875rem; cursor: pointer; display: flex; align-items: center; gap: 4px; transition: color 0.2s ease;" onclick="toggleScheduleDetails()">
                                    <i class="fas fa-chevron-down"></i>
                                    <span>Show Detailed Schedule</span>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Emergency Contact Info -->
                        <div style="background: linear-gradient(135deg, #fef3c7, #ffffff); border: 2px solid #f59e0b; border-radius: 10px; padding: 16px; margin-top: 16px;">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="font-size: 24px;">🚨</div>
                                <div>
                                    <h5 style="margin: 0; color: #92400e; font-size: 0.875rem; font-weight: 600;">Emergency Dental Care</h5>
                                    <p style="margin: 4px 0 0 0; color: #92400e; font-size: 0.75rem; line-height: 1.4;">
                                        For urgent dental emergencies outside operating hours, contact your branch directly or visit the nearest emergency dental clinic.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Referral Status Section -->
            <div id="referral-status" class="section-card" style="display: none;">
                <div class="section-header">
                    <h3>
                        <i class="fas fa-exchange-alt"></i>
                        Referral Status
                        <span id="referral-status-badge" class="badge" style="background: var(--warning-100); color: var(--warning-700); padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; margin-left: 12px;">Active</span>
                    </h3>
                </div>
                <div class="section-content">
                    <div id="referral-info" class="referral-info-card" style="background: linear-gradient(135deg, #fff3cd, #ffffff); border: 2px solid #ffc107; border-radius: 10px; padding: 20px; margin-bottom: 20px; position: relative; overflow: hidden;">
                        <!-- Status indicator line -->
                        <div id="referral-status-line" style="position: absolute; top: 0; left: 0; right: 0; height: 4px; background: linear-gradient(90deg, #ffc107, #ffed4e); transition: all 0.3s ease;"></div>
                        
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div id="referral-emoji" style="font-size: 48px; transition: all 0.3s ease;">🔄</div>
                            <div style="flex: 1;">
                                <h4 id="referral-title" style="margin: 0; color: #856404; transition: color 0.3s ease;">You have been referred!</h4>
                                <p id="referral-details" style="margin: 5px 0; color: #856404; line-height: 1.5;">Loading referral information...</p>
                                <p id="referral-action" style="margin: 0; font-size: 0.875rem; color: #6c757d; font-style: italic; margin-top: 8px;">Your care team is coordinating the best treatment for you.</p>
                                
                                <!-- Progress indicator for pending referrals -->
                                <div id="referral-progress" style="display: none; margin-top: 12px;">
                                    <div style="width: 100%; height: 6px; background: rgba(0,0,0,0.1); border-radius: 3px; overflow: hidden;">
                                        <div style="height: 100%; width: 60%; background: linear-gradient(90deg, #ffc107, #ffed4e); border-radius: 3px; animation: pulse 2s infinite;"></div>
                                    </div>
                                    <small style="color: #6c757d; font-size: 0.75rem; margin-top: 4px; display: block;">Processing your referral...</small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Additional action buttons for certain statuses -->
                        <div id="referral-actions" style="margin-top: 15px; display: none;">
                            <div style="border-top: 1px solid rgba(0,0,0,0.1); padding-top: 15px;">
                                <!-- Buttons will be dynamically added here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Book Appointment Section -->
            <div class="section-card" id="book-appointment-section">
                <div class="section-header">
                    <h3>
                        <i class="fas fa-calendar-plus"></i>
                        Book New Appointment
                    </h3>
                </div>
                <div class="section-content">
                    <form id="appointmentForm" class="modern-form">
                        <!-- Hidden field for selected clinic (set dynamically by Book Now button) -->
                        <input type="hidden" id="clinic_id" name="clinic_id" value="">
                        
                        <!-- Display selected clinic info -->
                        <div class="selected-clinic-info" id="selected-clinic-info" style="display: none;">
                            <div class="clinic-selection-card">
                                <div class="clinic-icon">
                                    <i class="fas fa-hospital"></i>
                                </div>
                                <div class="clinic-details">
                                    <h4 id="selected-clinic-name">Selected Clinic</h4>
                                    <p id="selected-clinic-location">Clinic Location</p>
                                </div>
                                <div class="clinic-status">
                                    <span class="status-badge success">
                                        <i class="fas fa-check-circle"></i> Selected
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label for="treatment_type_id">
                                    <i class="fas fa-tooth"></i> Treatment Type *
                                </label>
                                <select id="treatment_type_id" name="treatment_type_id" required>
                                    <option value="">-- Select treatment --</option>
                                </select>
                                <small id="treatment-duration-info" style="color: var(--primary-600); font-weight: 500; display: none;">
                                    <i class="fas fa-info-circle"></i> Duration: <span id="selected-duration"></span>
                                </small>
                            </div>
                            
                            <div class="form-group">
                                <label for="appointment_date">
                                    <i class="fas fa-calendar-alt"></i> Appointment Date *
                                </label>
                                <input type="date" id="appointment_date" name="appointment_date" required>
                                <small>Select your preferred date</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="appointment_time">
                                    <i class="fas fa-clock"></i> Appointment Time *
                                </label>
                                <select id="appointment_time" name="appointment_time" required>
                                    <option value="">-- Select time --</option>
                                    <option value="08:00">🌅 08:00 AM</option>
                                    <option value="09:00">🌅 09:00 AM</option>
                                    <option value="10:00">🌞 10:00 AM</option>
                                    <option value="11:00">🌞 11:00 AM</option>
                                    <option value="13:00">🌇 01:00 PM</option>
                                    <option value="14:00">🌇 02:00 PM</option>
                                    <option value="15:00">🌆 03:00 PM</option>
                                    <option value="16:00">🌆 04:00 PM</option>
                                </select>
                                <small id="time-availability-info">Choose your preferred time slot</small>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="notes">
                                <i class="fas fa-sticky-note"></i> Notes & Special Requests (Optional)
                            </label>
                            <textarea id="notes" name="notes" rows="4" 
                                      placeholder="Tell us about any specific concerns, symptoms, or special requirements..."></textarea>
                            <small>Help us prepare for your visit by sharing any relevant information</small>
                        </div>
                        
                        <div class="quick-actions">
                            <button type="submit" class="btn btn-primary btn-large">
                                <i class="fas fa-calendar-plus"></i> Book Appointment
                            </button>
                            <button type="button" class="btn btn-secondary btn-large" onclick="clearForm()">
                                <i class="fas fa-eraser"></i> Clear Form
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Appointments List Section -->
            <div class="section-card" id="appointments-section">
                <div class="section-header">
                    <h3>
                        <i class="fas fa-list-ul"></i>
                        My Appointments
                        <span class="badge" id="appointments-count" style="background: var(--primary-100); color: var(--primary-700); padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; margin-left: 12px;">Loading...</span>
                    </h3>
                </div>
                <div class="section-content">
                    <div id="appointments-list">
                        <div class="empty-state">
                            <div class="loading-skeleton" style="height: 40px; margin-bottom: 16px;"></div>
                            <div class="loading-skeleton" style="height: 40px; margin-bottom: 16px;"></div>
                            <div class="loading-skeleton" style="height: 40px; margin-bottom: 16px;"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Compact Clinic Information Footer -->
            <div class="clinic-info-footer" style="margin-top: var(--spacing-6); background: linear-gradient(135deg, var(--primary-50), var(--secondary-50)); border: 1px solid var(--primary-200); border-radius: 12px; padding: 16px; text-align: center;">
                <div class="footer-content" style="max-width: 600px; margin: 0 auto;">
                    <!-- Compact Clinic Header -->
                    <div class="footer-header" style="margin-bottom: 12px;">
                        <div style="display: flex; align-items: center; justify-content: center; gap: 8px; margin-bottom: 6px;">
                            <div id="footer-clinic-logo" style="width: 24px; height: 24px; border-radius: 6px; overflow: hidden;">
                                <img src="../../assets/images/happy-teeth-dental.png" alt="Clinic Logo" style="width: 100%; height: 100%; object-fit: contain;" id="footer-logo-img">
                            </div>
                            <h5 id="footer-clinic-name" style="margin: 0; color: var(--primary-700); font-weight: 600; font-size: 0.95rem;">
                                <span class="loading-skeleton" style="width: 140px; height: 16px; display: inline-block;"></span>
                            </h5>
                        </div>
                        <p id="footer-clinic-tagline" style="margin: 0; color: var(--secondary-600); font-size: 0.75rem; font-style: italic;">
                            Your trusted dental care partner
                        </p>
                    </div>
                    
                    <!-- Compact Clinic Details Grid -->
                    <div class="footer-details-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-bottom: 12px;">
                        <!-- Location -->
                        <div class="footer-detail-item" style="background: rgba(255, 255, 255, 0.6); border-radius: 8px; padding: 10px; border: 1px solid var(--secondary-200);">
                            <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                                <i class="fas fa-map-marker-alt" style="color: var(--primary-600); font-size: 12px;"></i>
                                <strong style="color: var(--secondary-800); font-size: 0.75rem;">Location</strong>
                            </div>
                            <p id="footer-clinic-location" style="margin: 0; color: var(--secondary-700); font-size: 0.7rem; line-height: 1.3;">
                                <span class="loading-skeleton" style="width: 80px; height: 10px; display: inline-block;"></span>
                            </p>
                        </div>
                        
                        <!-- Contact -->
                        <div class="footer-detail-item" style="background: rgba(255, 255, 255, 0.6); border-radius: 8px; padding: 10px; border: 1px solid var(--secondary-200);">
                            <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                                <i class="fas fa-phone" style="color: var(--primary-600); font-size: 12px;"></i>
                                <strong style="color: var(--secondary-800); font-size: 0.75rem;">Contact</strong>
                            </div>
                            <p id="footer-clinic-contact" style="margin: 0; color: var(--secondary-700); font-size: 0.7rem; line-height: 1.3;">
                                <span class="loading-skeleton" style="width: 70px; height: 10px; display: inline-block;"></span>
                            </p>
                        </div>
                        
                        <!-- Status -->
                        <div class="footer-detail-item" style="background: rgba(255, 255, 255, 0.6); border-radius: 8px; padding: 10px; border: 1px solid var(--secondary-200);">
                            <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                                <i class="fas fa-clock" style="color: var(--primary-600); font-size: 12px;"></i>
                                <strong style="color: var(--secondary-800); font-size: 0.75rem;">Status</strong>
                            </div>
                            <p id="footer-clinic-status" style="margin: 0; color: var(--secondary-700); font-size: 0.7rem; line-height: 1.3;">
                                <span class="loading-skeleton" style="width: 60px; height: 10px; display: inline-block;"></span>
                            </p>
                        </div>
                    </div>
                    
                    <!-- Compact Footer Message -->
                    <div class="footer-message" style="padding: 10px; background: rgba(255, 255, 255, 0.7); border-radius: 8px; border: 1px solid var(--primary-200);">
                        <div style="display: flex; align-items: center; justify-content: center; gap: 6px; margin-bottom: 4px;">
                            <i class="fas fa-heart" style="color: var(--orange-accent); font-size: 12px;"></i>
                            <strong style="color: var(--primary-700); font-size: 0.75rem;">Thank you for choosing us!</strong>
                        </div>
                        <p id="footer-clinic-message" style="margin: 0; color: var(--secondary-700); font-size: 0.7rem; line-height: 1.4;">
                            <span id="footer-dynamic-message">Book your appointment today and let us take care of your smile!</span>
                        </p>
                    </div>
                    
                    <!-- Compact Copyright -->
                    <div class="footer-copyright" style="margin-top: 10px; padding-top: 8px; border-top: 1px solid var(--secondary-300);">
                        <p style="margin: 0; color: var(--secondary-500); font-size: 0.65rem;">
                            © <?php echo date('Y'); ?> Dental Clinic Network | Quality Care, Trusted Service
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Appointment Reminder Notification -->
        <div id="reminder-notification" class="reminder-notification" style="display: none;">
            <div class="reminder-content">
                <div class="reminder-icon">
                    <i class="fas fa-bell"></i>
                </div>
                <div class="reminder-message">
                    <h4 id="reminder-title">Appointment Reminder</h4>
                    <p id="reminder-text">Your appointment is coming up soon!</p>
                </div>
                <div class="reminder-actions">
                    <button id="reminder-dismiss" class="reminder-btn dismiss">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Connection Status Indicator -->
        <div id="connection-status" class="connection-status">
            <i class="fas fa-wifi"></i> <span id="connection-text">Real-time Active</span>
        </div>
        
        <!-- Floating Refresh Button -->
        <div class="floating-refresh" id="refresh-btn" title="Refresh All Data (Appointments, Notifications & Status)">
            <i class="fas fa-sync-alt"></i>
        </div>
    </div>
    
    <script>
        // Pass PHP data to JavaScript
        const selectedClinic = <?php echo json_encode($selectedClinic); ?>;
        const bookingAction = <?php echo json_encode($bookingAction); ?>;
        const clinicData = <?php echo json_encode($clinicData); ?>;
        
        // Enhanced Dashboard Controller
        class PatientDashboard {
            constructor() {
                this.appointments = [];
                this.treatmentTypes = [];
                this.currentUser = null;
                this.selectedTreatment = null;
                this.availableTimeSlots = [];
                this.selectedClinicId = null; // Initialize selected clinic ID
                this.lastNotificationRefresh = 0; // Track notification-triggered refreshes
                this.isEditingProfile = false; // Track profile editing state
                this.init();
            }
            
            init() {
                // Handle clinic selection FIRST to set selectedClinicId from URL
                this.handleClinicSelection();
                
                this.setupEventListeners();
                this.setupConnectionMonitoring();
                this.setupModalHandlers();
                this.loadUserInfo();
                this.loadTreatmentTypes();
                this.loadAppointments();
                
                // Test the ReferralController endpoint first
                this.testReferralController().then(() => {
                    this.loadReferralStatus();
                });
                
                this.loadClinicHours();
                this.setMinDate();
                this.setupNavigation();
                this.startPeriodicRefresh();
                this.startClockUpdate();
                
                // Initialize footer information (will be updated if clinic is selected)
                setTimeout(() => {
                    if (!this.selectedClinicId) {
                        this.setGeneralFooter();
                    }
                }, 500);
            }

            // Test method to verify ReferralController endpoint
            testReferralController() {
                const testUrl = '../../src/controllers/ReferralController.php?action=test';
                
                return fetch(testUrl)
                .then(response => {
                    return response.text();
                })
                .then(text => {
                    try {
                        const data = JSON.parse(text);
                        return data;
                    } catch (e) {
                        throw e;
                    }
                })
                .catch(error => {
                    throw error;
                });
            }
            
            setupConnectionMonitoring() {
                // Monitor online/offline status
                window.addEventListener('online', () => {
                    this.updateConnectionStatus(true);
                    this.loadAppointments(); // Refresh data when back online
                });
                
                window.addEventListener('offline', () => {
                    this.updateConnectionStatus(false);
                });
                
                // Initial status
                this.updateConnectionStatus(navigator.onLine);
            }
            
            updateConnectionStatus(isOnline) {
                const statusEl = document.getElementById('connection-status');
                const textEl = document.getElementById('connection-text');
                
                if (isOnline) {
                    statusEl.className = 'connection-status online';
                    textEl.textContent = 'Connected';
                    statusEl.style.display = 'block';
                    setTimeout(() => {
                        statusEl.style.display = 'none';
                    }, 3000);
                } else {
                    statusEl.className = 'connection-status offline';
                    textEl.textContent = 'Offline';
                    statusEl.style.display = 'block';
                }
            }
            
            setupEventListeners() {
                // Form submission
                document.getElementById('appointmentForm').addEventListener('submit', (e) => {
                    this.handleAppointmentSubmission(e);
                });

                // Treatment type selection change
                document.getElementById('treatment_type_id').addEventListener('change', (e) => {
                    this.handleTreatmentTypeChange(e);
                });

                // Date change - reload available times
                document.getElementById('appointment_date').addEventListener('change', (e) => {
                    this.handleDateChange(e);
                });

                // Time selection change
                document.getElementById('appointment_time').addEventListener('change', (e) => {
                    this.handleTimeChange(e);
                });
                
                // Navigation links
                document.querySelectorAll('.nav-link[data-section]').forEach(link => {
                    link.addEventListener('click', (e) => {
                        e.preventDefault();
                        this.navigateToSection(link.dataset.section);
                    });
                });
                
                // Floating refresh button
                document.getElementById('refresh-btn').addEventListener('click', () => {
                    this.manualRefresh();
                });
                
                // User avatar click to profile
                document.getElementById('user-avatar').addEventListener('click', () => {
                    this.navigateToSection('profile');
                });
            }
            
            manualRefresh() {
                const refreshBtn = document.getElementById('refresh-btn');
                refreshBtn.classList.add('spinning');
                
                Promise.all([
                    this.loadUserInfo(),
                    this.loadAppointments(),
                    this.loadReferralStatus(),
                    // Refresh notifications as well
                    notificationSystem ? notificationSystem.loadNotifications() : Promise.resolve()
                ]).finally(() => {
                    refreshBtn.classList.remove('spinning');
                    this.showAlert('success', '🔄 Data refreshed successfully!');
                });
            }
            
            setupNavigation() {
                // Smooth scrolling for navigation
                document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                    anchor.addEventListener('click', function (e) {
                        e.preventDefault();
                        const target = document.querySelector(this.getAttribute('href') + '-section');
                        if (target) {
                            target.scrollIntoView({
                                behavior: 'smooth',
                                block: 'start'
                            });
                        }
                    });
                });
                
                // Active navigation highlighting
                window.addEventListener('scroll', () => {
                    this.updateActiveNavigation();
                });
            }
            
            navigateToSection(sectionId) {
                const section = document.getElementById(sectionId + '-section');
                if (section) {
                    section.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
            
            updateActiveNavigation() {
                const sections = ['book-appointment', 'appointments', 'profile'];
                const navLinks = document.querySelectorAll('.nav-link[data-section]');
                
                sections.forEach(sectionId => {
                    const section = document.getElementById(sectionId + '-section');
                    const navLink = document.querySelector(`[data-section="${sectionId}"]`);
                    
                    if (section && navLink) {
                        const rect = section.getBoundingClientRect();
                        const isVisible = rect.top <= 100 && rect.bottom >= 100;
                        
                        if (isVisible) {
                            navLinks.forEach(link => link.classList.remove('active'));
                            navLink.classList.add('active');
                        }
                    }
                });
            }
            
            setMinDate() {
                const today = new Date();
                const minDate = today.toISOString().split('T')[0];
                document.getElementById('appointment_date').setAttribute('min', minDate);
            }
            
            // Clinic Operating Hours Methods - Now supports specific clinic ID or general mode
            loadClinicHours(specificClinicId = null) {
                let clinicId = specificClinicId;
                
                // If no specific clinic ID provided, try to get from selected clinic or user branch
                if (!clinicId) {
                    if (this.selectedClinicId) {
                        clinicId = this.selectedClinicId;
                    } else {
                        // Fallback to user's branch (legacy support)
                        clinicId = this.getCurrentUserBranchId();
                    }
                }
                
                if (!clinicId) {
                    this.setGeneralClinicHoursView();
                    return;
                }
                
                fetch(`../api/branch-hours.php?action=get_hours&branch_id=${clinicId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.displayClinicHours(data);
                    } else {
                        console.error('Failed to load clinic hours:', data.error);
                        this.displayClinicHoursError();
                    }
                })
                .catch(error => {
                    console.error('Error loading clinic hours:', error);
                    this.displayClinicHoursError();
                });
            }
            
            getCurrentUserBranchId() {
                // Try to get branch ID from user info that should be loaded by loadUserInfo
                const branchInfo = document.getElementById('branch-name');
                if (branchInfo && branchInfo.dataset.branchId) {
                    return branchInfo.dataset.branchId;
                }
                
                // Fallback: parse from branch info if available
                const branchInfoText = document.getElementById('branch-info');
                if (branchInfoText && branchInfoText.textContent) {
                    // Extract branch ID from existing branch info
                    const branches = {
                        'Talisay': 1,
                        'Silay': 2, 
                        'Sarabia': 3,
                        'Bacolod': 4
                    };
                    
                    for (const [name, id] of Object.entries(branches)) {
                        if (branchInfoText.textContent.includes(name)) {
                            return id;
                        }
                    }
                }
                
                return null;
            }
            
            displayClinicHours(data) {
                const { branch, schedule, current_status } = data;
                
                // Update status badge
                const statusBadge = document.getElementById('clinic-status-badge');
                const statusLine = document.getElementById('hours-status-line');
                const statusEmoji = document.getElementById('clinic-status-emoji');
                const statusTitle = document.getElementById('clinic-status-title');
                const statusSubtitle = document.getElementById('clinic-status-subtitle');
                
                if (current_status.is_open) {
                    statusBadge.innerHTML = '<i class="fas fa-circle" style="color: #10b981; margin-right: 4px;"></i>Currently Open';
                    statusBadge.style.background = 'var(--success-100)';
                    statusBadge.style.color = 'var(--success-700)';
                    statusLine.style.background = 'linear-gradient(90deg, var(--success-500), var(--success-600))';
                    statusEmoji.textContent = '🏥';
                    statusTitle.innerHTML = `${branch.name} is <span style="color: var(--success-600);">OPEN</span>`;
                    statusSubtitle.textContent = `Welcome! We're here to serve you today.`;
                } else {
                    statusBadge.innerHTML = '<i class="fas fa-circle" style="color: #ef4444; margin-right: 4px;"></i>Currently Closed';
                    statusBadge.style.background = 'var(--error-100)';
                    statusBadge.style.color = 'var(--error-700)';
                    statusLine.style.background = 'linear-gradient(90deg, var(--error-500), var(--error-600))';
                    statusEmoji.textContent = '🏥';
                    statusTitle.innerHTML = `${branch.name} is <span style="color: var(--error-600);">CLOSED</span>`;
                    statusSubtitle.textContent = `We'll be happy to serve you during our operating hours.`;
                }
                
                // Update current time
                this.updateCurrentTime();
                
                // Update hours summary
                const hoursSummary = document.getElementById('hours-summary');
                hoursSummary.innerHTML = `
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-info-circle" style="color: var(--primary-600);"></i>
                        <span style="color: var(--primary-700); font-weight: 500; font-size: 0.875rem;">
                            ${branch.summary}
                        </span>
                    </div>
                `;
                
                // Populate detailed schedule
                this.populateDetailedSchedule(schedule, current_status.current_day.toLowerCase());
                
                // Store data for future use
                this.clinicHoursData = data;
            }
            
            populateDetailedSchedule(schedule, currentDay) {
                const detailedSchedule = document.getElementById('detailed-schedule');
                const scheduleGrid = detailedSchedule.querySelector('div[style*="grid"]');
                
                scheduleGrid.innerHTML = '';
                
                schedule.forEach(day => {
                    const isToday = day.day === currentDay;
                    const dayCard = document.createElement('div');
                    dayCard.style.cssText = `
                        background: ${isToday ? 'linear-gradient(135deg, var(--primary-50), var(--primary-100))' : 'var(--secondary-50)'};
                        border: 2px solid ${isToday ? 'var(--primary-200)' : 'var(--secondary-200)'};
                        border-radius: 8px;
                        padding: 12px;
                        transition: all 0.2s ease;
                        ${isToday ? 'box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);' : ''}
                    `;
                    
                    dayCard.innerHTML = `
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                            <span style="font-weight: 600; color: ${isToday ? 'var(--primary-700)' : 'var(--secondary-700)'}; font-size: 0.875rem;">
                                ${day.day_display}
                                ${isToday ? '<span style="font-size: 0.75rem; color: var(--primary-600); margin-left: 4px;">(Today)</span>' : ''}
                            </span>
                            ${day.is_open ? 
                                `<i class="fas fa-circle" style="color: var(--success-500); font-size: 0.5rem;"></i>` : 
                                `<i class="fas fa-circle" style="color: var(--error-500); font-size: 0.5rem;"></i>`
                            }
                        </div>
                        <div style="color: ${isToday ? 'var(--primary-600)' : 'var(--secondary-600)'}; font-size: 0.75rem; font-weight: 500;">
                            ${day.hours_display}
                        </div>
                    `;
                    
                    scheduleGrid.appendChild(dayCard);
                });
            }
            
            updateCurrentTime() {
                const now = new Date();
                const timeElement = document.getElementById('current-time');
                const dayElement = document.getElementById('current-day');
                
                if (timeElement) {
                    timeElement.textContent = now.toLocaleTimeString('en-US', { 
                        hour: 'numeric', 
                        minute: '2-digit',
                        hour12: true 
                    });
                }
                
                if (dayElement) {
                    dayElement.textContent = now.toLocaleDateString('en-US', { 
                        weekday: 'long'
                    });
                }
            }
            
            startClockUpdate() {
                // Update time every minute
                this.updateCurrentTime();
                setInterval(() => {
                    this.updateCurrentTime();
                }, 60000);
                
                // Check status every 5 minutes
                setInterval(() => {
                    if (this.clinicHoursData) {
                        this.checkCurrentStatus();
                    }
                }, 300000);
            }
            
            checkCurrentStatus() {
                const userBranchId = this.getCurrentUserBranchId();
                if (!userBranchId) return;
                
                fetch(`../api/branch-hours.php?action=get_current_status`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update just the status if it changed
                        this.updateStatusIndicators(data);
                    }
                })
                .catch(error => {
                    console.error('Error checking current status:', error);
                });
            }
            
            updateStatusIndicators(statusData) {
                // Update current time display
                const timeElement = document.getElementById('current-time');
                const dayElement = document.getElementById('current-day');
                
                if (timeElement) timeElement.textContent = statusData.current_time;
                if (dayElement) dayElement.textContent = statusData.current_day;
            }
            
            displayClinicHoursError() {
                const statusBadge = document.getElementById('clinic-status-badge');
                const statusTitle = document.getElementById('clinic-status-title');
                const statusSubtitle = document.getElementById('clinic-status-subtitle');
                const hoursSummary = document.getElementById('hours-summary');
                
                statusBadge.innerHTML = '<i class="fas fa-exclamation-triangle" style="color: #f59e0b; margin-right: 4px;"></i>Info Unavailable';
                statusBadge.style.background = 'var(--warning-100)';
                statusBadge.style.color = 'var(--warning-700)';
                
                statusTitle.textContent = 'Operating Hours Information';
                statusSubtitle.textContent = 'Unable to load current operating hours';
                
                hoursSummary.innerHTML = `
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-info-circle" style="color: var(--primary-600);"></i>
                        <span style="color: var(--primary-700); font-weight: 500; font-size: 0.875rem;">
                            Monday - Saturday: 9:00 AM - 5:00 PM, Sunday: 1:00 PM - 5:00 PM
                        </span>
                    </div>
                `;
                
                this.updateCurrentTime();
            }
            
            loadReferralStatus() {
                const url = '../../src/controllers/ReferralController.php?action=getPatientReferralStatus';
                
                fetch(url)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    
                    // Check content type
                    const contentType = response.headers.get('content-type');
                    
                    return response.text(); // Get text first to see raw response
                })
                .then(text => {
                    try {
                        const data = JSON.parse(text);
                        
                        if (data.success && data.hasReferral) {
                            this.displayReferralStatus(data.referral);
                        } else {
                            // Hide referral status section if no active referrals
                            document.getElementById('referral-status').style.display = 'none';
                        }
                    } catch (parseError) {
                        document.getElementById('referral-status').style.display = 'none';
                    }
                })
                .catch(error => {
                    document.getElementById('referral-status').style.display = 'none';
                });
            }
            
            // Helper function to extract treatment name from referral data
            extractTreatmentName(referral) {
                // First check if treatment_name exists directly
                if (referral.treatment_name) {
                    return referral.treatment_name;
                }
                
                // Extract from notes field if it contains "Treatment requested:"
                if (referral.notes && referral.notes.includes('Treatment requested:')) {
                    const match = referral.notes.match(/Treatment requested:\s*(.+?)(?:\n|$)/i);
                    if (match && match[1]) {
                        const extractedName = match[1].trim();
                        return extractedName;
                    }
                }
                
                // Check if notes contain treatment information in other formats
                if (referral.notes) {
                    // Look for patterns like "Treatment: [name]" or "For: [treatment]"
                    const treatmentPatterns = [
                        /Treatment:\s*(.+?)(?:\n|$)/i,
                        /For:\s*(.+?)(?:\n|$)/i,
                        /Procedure:\s*(.+?)(?:\n|$)/i,
                        /Service:\s*(.+?)(?:\n|$)/i
                    ];
                    
                    for (const pattern of treatmentPatterns) {
                        const match = referral.notes.match(pattern);
                        if (match && match[1]) {
                            const extractedName = match[1].trim();
                            return extractedName;
                        }
                    }
                }
                
                // Fallback to general consultation
                return 'General consultation';
            }
            
            // Helper function to format price display
            formatPrice(price, showCurrency = true) {
                if (!price || price === 0 || price === '0') {
                    return showCurrency ? 'Price on consultation' : 'N/A';
                }
                
                const numericPrice = parseFloat(price);
                if (isNaN(numericPrice)) {
                    return showCurrency ? 'Price on consultation' : 'N/A';
                }
                
                const formattedPrice = numericPrice.toLocaleString('en-PH', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
                
                return showCurrency ? `₱${formattedPrice}` : formattedPrice;
            }
            
            // Helper function to get treatment pricing information
            async fetchTreatmentPrice(treatmentTypeId, branchId) {
                if (!treatmentTypeId || !branchId) {
                    return null;
                }
                
                try {
                    const response = await fetch(`../../src/controllers/ReferralController.php?action=getBranchTreatments&branch_id=${branchId}`);
                    const data = await response.json();
                    
                    if (data.success && data.treatments) {
                        const treatment = data.treatments.find(t => t.id == treatmentTypeId);
                        return treatment ? treatment.price : null;
                    }
                } catch (error) {
                    console.error('Error fetching treatment price:', error);
                }
                
                return null;
            }
            
            // Enhanced function to build pricing information HTML
            buildPricingInfo(referral, treatmentName) {
                let pricingHtml = '';
                
                // Enhanced pricing for referrals with both original and new appointment pricing
                if (referral.original_treatment_price && referral.new_treatment_price) {
                    // Both original and new prices available
                    const originalPrice = parseFloat(referral.original_treatment_price);
                    const newPrice = parseFloat(referral.new_treatment_price);
                    const priceDiff = newPrice - originalPrice;
                    
                    if (originalPrice !== newPrice) {
                        // Different prices - show both
                        pricingHtml = `
                            <div style="margin: 8px 0; padding: 12px; background: rgba(248, 250, 252, 0.8); border: 1px solid #e2e8f0; border-radius: 8px;">
                                <div style="margin-bottom: 8px;">
                                    <small style="color: #059669; font-weight: 600; display: block;">
                                        <i class="fas fa-hospital"></i> Original: ${referral.original_treatment_name || treatmentName}
                                        <span style="float: right; font-size: 1.1em;">${this.formatPrice(originalPrice)}</span>
                                    </small>
                                    <small style="color: #6b7280; font-size: 0.85em;">at ${referral.original_branch_name || referral.from_branch_name}</small>
                                </div>
                                <div style="margin-bottom: 8px;">
                                    <small style="color: #2563eb; font-weight: 600; display: block;">
                                        <i class="fas fa-arrow-right"></i> New Branch: ${referral.treatment_name || treatmentName}
                                        <span style="float: right; font-size: 1.1em;">${this.formatPrice(newPrice)}</span>
                                    </small>
                                    <small style="color: #6b7280; font-size: 0.85em;">at ${referral.to_branch_name}</small>
                                </div>
                                <div style="border-top: 1px solid #e5e7eb; padding-top: 8px; margin-top: 8px;">
                                    <small style="color: ${priceDiff >= 0 ? '#dc2626' : '#059669'}; font-weight: 600;">
                                        <i class="fas fa-${priceDiff >= 0 ? 'arrow-up' : 'arrow-down'}"></i> 
                                        ${priceDiff >= 0 ? '+' : ''}${this.formatPrice(Math.abs(priceDiff))} 
                                        ${priceDiff >= 0 ? 'more expensive' : 'less expensive'}
                                    </small>
                                </div>
                            </div>`;
                    } else {
                        // Same price - show single entry
                        pricingHtml = `
                            <div style="margin: 8px 0; padding: 8px 12px; background: rgba(34, 197, 94, 0.1); border-left: 3px solid #22c55e; border-radius: 0 6px 6px 0;">
                                <small style="color: #15803d; font-weight: 600;">
                                    <i class="fas fa-tag"></i> Treatment Cost: <span style="font-size: 1.1em;">${this.formatPrice(originalPrice)}</span>
                                    <span style="margin-left: 8px; color: #6b7280;">(same at both branches)</span>
                                </small>
                            </div>`;
                    }
                } else if (referral.original_treatment_price) {
                    // Only original price available
                    pricingHtml = `<div style="margin: 8px 0; padding: 8px 12px; background: rgba(34, 197, 94, 0.1); border-left: 3px solid #22c55e; border-radius: 0 6px 6px 0;">
                        <small style="color: #15803d; font-weight: 600;">
                            <i class="fas fa-tag"></i> Original Cost: <span style="font-size: 1.1em;">${this.formatPrice(referral.original_treatment_price)}</span>
                        </small>
                    </div>`;
                } else if (referral.new_treatment_price) {
                    // Only new price available
                    pricingHtml = `<div style="margin: 8px 0; padding: 8px 12px; background: rgba(59, 130, 246, 0.1); border-left: 3px solid #3b82f6; border-radius: 0 6px 6px 0;">
                        <small style="color: #1e40af; font-weight: 600;">
                            <i class="fas fa-tag"></i> Estimated Cost: <span style="font-size: 1.1em;">${this.formatPrice(referral.new_treatment_price)}</span>
                        </small>
                    </div>`;
                } else if (referral.treatment_price && referral.treatment_price > 0) {
                    // Fallback to treatment_price field
                    pricingHtml = `<div style="margin: 8px 0; padding: 8px 12px; background: rgba(59, 130, 246, 0.1); border-left: 3px solid #3b82f6; border-radius: 0 6px 6px 0;">
                        <small style="color: #1e40af; font-weight: 600;">
                            <i class="fas fa-tag"></i> Estimated Cost: <span style="font-size: 1.1em;">${this.formatPrice(referral.treatment_price)}</span>
                        </small>
                    </div>`;
                } else if (referral.treatment_type_id && referral.to_branch_id) {
                    // Async fetch pricing if we have treatment and branch IDs
                    this.fetchTreatmentPrice(referral.treatment_type_id, referral.to_branch_id).then(price => {
                        if (price && price > 0) {
                            const pricingDiv = document.createElement('div');
                            pricingDiv.style.cssText = 'margin: 8px 0; padding: 8px 12px; background: rgba(59, 130, 246, 0.1); border-left: 3px solid #3b82f6; border-radius: 0 6px 6px 0;';
                            pricingDiv.innerHTML = `<small style="color: #1e40af; font-weight: 600;">
                                <i class="fas fa-tag"></i> Estimated Cost: <span style="font-size: 1.1em;">${this.formatPrice(price)}</span>
                            </small>`;
                            
                            // Insert pricing info after the treatment info
                            const detailsEl = document.getElementById('referral-details');
                            if (detailsEl && !detailsEl.querySelector('.pricing-info')) {
                                pricingDiv.className = 'pricing-info';
                                detailsEl.appendChild(pricingDiv);
                            }
                        }
                    });
                } else {
                    // Show consultation message for general referrals
                    pricingHtml = `<div style="margin: 8px 0; padding: 8px 12px; background: rgba(107, 114, 128, 0.1); border-left: 3px solid #6b7280; border-radius: 0 6px 6px 0;">
                        <small style="color: #374151; font-weight: 500;">
                            <i class="fas fa-info-circle"></i> Pricing will be discussed during consultation
                        </small>
                    </div>`;
                }
                
                return pricingHtml;
            }
            
            displayReferralStatus(referral) {
                const referralSection = document.getElementById('referral-status');
                const referralInfoCard = document.getElementById('referral-info');
                const statusBadge = document.getElementById('referral-status-badge');
                const statusLine = document.getElementById('referral-status-line');
                const emojiEl = document.getElementById('referral-emoji');
                const titleEl = document.getElementById('referral-title');
                const detailsEl = document.getElementById('referral-details');
                const actionEl = document.getElementById('referral-action');
                const progressEl = document.getElementById('referral-progress');
                const actionsEl = document.getElementById('referral-actions');
                
                // Extract treatment name using helper function
                const treatmentName = this.extractTreatmentName(referral);
                
                // Build pricing information
                const pricingInfo = this.buildPricingInfo(referral, treatmentName);
                
                // Clean and normalize the status
                const normalizedStatus = referral.status ? referral.status.toString().trim() : '';
                
                // Show completed referrals with invoice functionality
                if (normalizedStatus === 'completed') {
                    // Show completed referral with invoice options
                    this.displayCompletedReferral(referral);
                    return;
                }
                
                referralSection.style.display = 'block';
                
                // Status configurations for different referral states
                const statusConfig = {
                    'pending_patient_approval': {
                        emoji: '🤝',
                        title: 'Referral Awaiting Your Approval',
                        badge: 'Awaiting Your Response',
                        badgeStyle: 'background: #e3f2fd; color: #1565c0; border: 1px solid #2196f3;',
                        background: 'linear-gradient(135deg, #e3f2fd, #ffffff)',
                        border: '2px solid #2196f3',
                        statusLine: 'linear-gradient(90deg, #2196f3, #42a5f5)',
                        titleColor: '#1565c0',
                        textColor: '#1565c0',
                        showProgress: false,
                        showPatientActions: true
                    },
                    'patient_approved': {
                        emoji: '�',
                        title: 'You Approved This Referral',
                        badge: 'Approved by You',
                        badgeStyle: 'background: #e8f5e8; color: #2e7d32; border: 1px solid #4caf50;',
                        background: 'linear-gradient(135deg, #e8f5e8, #ffffff)',
                        border: '2px solid #4caf50',
                        statusLine: 'linear-gradient(90deg, #4caf50, #66bb6a)',
                        titleColor: '#2e7d32',
                        textColor: '#2e7d32',
                        showProgress: true,
                        showPatientActions: false
                    },
                    'patient_rejected': {
                        emoji: '👎',
                        title: 'You Declined This Referral',
                        badge: 'Declined by You',
                        badgeStyle: 'background: #ffebee; color: #c62828; border: 1px solid #f44336;',
                        background: 'linear-gradient(135deg, #ffebee, #ffffff)',
                        border: '2px solid #f44336',
                        statusLine: 'linear-gradient(90deg, #f44336, #e57373)',
                        titleColor: '#c62828',
                        textColor: '#c62828',
                        showProgress: false,
                        showPatientActions: false
                    },
                    'pending': {
                        emoji: '�🔄',
                        title: 'Referral Pending Review',
                        badge: 'Under Review',
                        badgeStyle: 'background: #fff3cd; color: #856404; border: 1px solid #ffc107;',
                        background: 'linear-gradient(135deg, #fff3cd, #ffffff)',
                        border: '2px solid #ffc107',
                        statusLine: 'linear-gradient(90deg, #ffc107, #ffed4e)',
                        titleColor: '#856404',
                        textColor: '#856404',
                        showProgress: true,
                        showPatientActions: false
                    },
                    'accepted': {
                        emoji: '✅',
                        title: 'Referral Accepted',
                        badge: 'Accepted',
                        badgeStyle: 'background: #d1f2eb; color: #155724; border: 1px solid #28a745;',
                        background: 'linear-gradient(135deg, #d1f2eb, #ffffff)',
                        border: '2px solid #28a745',
                        statusLine: 'linear-gradient(90deg, #28a745, #34ce57)',
                        titleColor: '#155724',
                        textColor: '#155724',
                        showProgress: false,
                        showPatientActions: false
                    },
                    'rejected': {
                        emoji: '❌',
                        title: 'Referral Not Approved',
                        badge: 'Rejected',
                        badgeStyle: 'background: #f8d7da; color: #721c24; border: 1px solid #dc3545;',
                        background: 'linear-gradient(135deg, #f8d7da, #ffffff)',
                        border: '2px solid #dc3545',
                        statusLine: 'linear-gradient(90deg, #dc3545, #e74c3c)',
                        titleColor: '#721c24',
                        textColor: '#721c24',
                        showProgress: false,
                        showPatientActions: false
                    },
                    'completed': {
                        emoji: '🎉',
                        title: 'Treatment Completed Successfully',
                        badge: 'Completed',
                        badgeStyle: 'background: #e7f3ff; color: #004085; border: 1px solid #007bff;',
                        background: 'linear-gradient(135deg, #e7f3ff, #ffffff)',
                        border: '2px solid #007bff',
                        statusLine: 'linear-gradient(90deg, #007bff, #3498db)',
                        titleColor: '#004085',
                        textColor: '#004085',
                        showProgress: false,
                        showPatientActions: false
                    },
                    'cancelled': {
                        emoji: '🚫',
                        title: 'Referral Cancelled',
                        badge: 'Cancelled',
                        badgeStyle: 'background: #f5f5f5; color: #495057; border: 1px solid #6c757d;',
                        background: 'linear-gradient(135deg, #f5f5f5, #ffffff)',
                        border: '2px solid #6c757d',
                        statusLine: 'linear-gradient(90deg, #6c757d, #95a5a6)',
                        titleColor: '#495057',
                        textColor: '#495057',
                        showProgress: false,
                        showPatientActions: false
                    }
                };
                
                const config = statusConfig[normalizedStatus] || statusConfig['pending'];
                
                // Update status badge
                statusBadge.textContent = config.badge;
                statusBadge.style.cssText = config.badgeStyle;
                
                // Update visual styling based on status
                referralInfoCard.style.background = config.background;
                referralInfoCard.style.border = config.border;
                statusLine.style.background = config.statusLine;
                
                // Update the emoji icon
                emojiEl.textContent = config.emoji;
                
                // Update title with color
                titleEl.textContent = config.title;
                titleEl.style.color = config.titleColor;
                
                // Show/hide progress indicator
                progressEl.style.display = config.showProgress ? 'block' : 'none';
                
                // Update content based on referral status
                if (normalizedStatus === 'pending_patient_approval') {
                    detailsEl.innerHTML = `
                        You have been referred to <strong>${referral.to_branch_name}</strong> for <strong>${treatmentName}</strong>.<br>
                        <small><strong>Referring Branch:</strong> ${referral.from_branch_name}</small><br>
                        <small><strong>Reason:</strong> ${referral.reason}</small><br>
                        ${pricingInfo}
                        <small style="color: #6c757d;"><i class="fas fa-clock"></i> Referred on: ${this.formatDate(referral.created_at || referral.referral_date)}</small>
                    `;
                    actionEl.innerHTML = '🤝 <strong>Your approval is required!</strong> Please review the details above and decide whether to accept or decline this referral.';
                    
                    // Show patient approval actions
                    actionsEl.style.display = 'block';
                    const buttonHtml = `
                        <div style="border-top: 1px solid rgba(0,0,0,0.1); padding-top: 15px;">
                            <div style="background: #f8f9fa; padding: 12px; border-radius: 6px; margin-bottom: 12px;">
                                <h6 style="margin: 0 0 8px 0; color: #495057; font-size: 14px;">
                                    <i class="fas fa-user-check"></i> Your Response Required
                                </h6>
                                <p style="margin: 0; font-size: 13px; color: #6c757d; line-height: 1.4;">
                                    By accepting, you agree to receive treatment at the referred branch. 
                                    By declining, this referral will be cancelled.
                                </p>
                            </div>
                            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                <button class="btn btn-success" onclick="dashboard.approveReferral(${referral.id})" style="flex: 1; min-width: 140px;">
                                    <i class="fas fa-thumbs-up"></i> Accept Referral
                                </button>
                                <button class="btn btn-danger" onclick="dashboard.showRejectReferralModal(${referral.id})" style="flex: 1; min-width: 140px;">
                                    <i class="fas fa-thumbs-down"></i> Decline Referral
                                </button>
                            </div>
                        </div>
                    `;
                    actionsEl.innerHTML = buttonHtml;
                    
                } else if (normalizedStatus === 'patient_approved') {
                    actionsEl.style.display = 'none';
                    detailsEl.innerHTML = `
                        You approved this referral to <strong>${referral.to_branch_name}</strong> for <strong>${treatmentName}</strong>.<br>
                        <small><strong>Approved on:</strong> ${this.formatDate(referral.patient_approved_at)}</small><br>
                        ${referral.patient_response_notes ? `<small><strong>Your notes:</strong> ${referral.patient_response_notes}</small><br>` : ''}
                        <small><strong>Treatment:</strong> ${treatmentName}</small><br>
                        ${pricingInfo}
                        <small style="color: #6c757d;"><i class="fas fa-calendar-plus"></i> Originally referred: ${this.formatDate(referral.created_at || referral.referral_date)}</small>
                    `;
                    actionEl.innerHTML = '⏳ Your approval has been sent to the receiving branch. They will review and schedule your appointment if accepted.';
                    
                } else if (normalizedStatus === 'patient_rejected') {
                    detailsEl.innerHTML = `
                        You declined this referral to <strong>${referral.to_branch_name}</strong> for <strong>${treatmentName}</strong>.<br>
                        <small><strong>Declined on:</strong> ${this.formatDate(referral.patient_rejected_at)}</small><br>
                        ${referral.patient_response_notes ? `<small><strong>Your reason:</strong> ${referral.patient_response_notes}</small><br>` : ''}
                        <small><strong>Treatment:</strong> ${treatmentName}</small><br>
                        ${pricingInfo}
                        <small style="color: #6c757d;"><i class="fas fa-calendar-times"></i> Originally referred: ${this.formatDate(referral.created_at || referral.referral_date)}</small>
                    `;
                    actionEl.innerHTML = '📞 You can still book a regular appointment at your original branch or contact them for alternative treatment options.';
                    
                    // Add action button for patient rejected referrals
                    actionsEl.style.display = 'block';
                    actionsEl.innerHTML = `
                        <div style="border-top: 1px solid rgba(0,0,0,0.1); padding-top: 15px;">
                            <button class="btn btn-primary btn-small" onclick="dashboard.navigateToSection('book-appointment')">
                                <i class="fas fa-calendar-plus"></i> Book New Appointment
                            </button>
                        </div>
                    `;
                    
                } else if (referral.status === 'pending') {
                    detailsEl.innerHTML = `
                        You have been referred to <strong>${referral.to_branch_name}</strong> for <strong>${treatmentName}</strong>.<br>
                        <small><strong>Reason:</strong> ${referral.reason}</small><br>
                        ${pricingInfo}
                        <small style="color: #6c757d;"><i class="fas fa-clock"></i> Submitted on: ${this.formatDate(referral.created_at || referral.referral_date)}</small>
                    `;
                    actionEl.innerHTML = '⏳ Your referral is being reviewed by the receiving branch. You will be notified once it\'s processed.';
                    
                } else if (referral.status === 'accepted') {
                    detailsEl.innerHTML = `
                        Great news! Your referral to <strong>${referral.to_branch_name}</strong> has been accepted!<br>
                        ${referral.appointment_date ? 
                            `<strong>📅 New appointment scheduled:</strong> ${this.formatDate(referral.appointment_date)} at ${this.formatTime(referral.appointment_time)}<br>` : 
                            '<strong>📅 Appointment scheduling in progress</strong><br>'
                        }
                        <small><strong>Treatment:</strong> ${treatmentName}</small><br>
                        ${pricingInfo}
                        <small style="color: #999;"><i class="fas fa-calendar-plus"></i> Originally created: ${this.formatDate(referral.created_at || referral.referral_date)}</small>
                    `;
                    actionEl.innerHTML = '🏥 Please attend your appointment at the new branch location. You may receive a confirmation call.';
                    
                    // Add action button for accepted referrals
                    actionsEl.style.display = 'block';
                    actionsEl.innerHTML = `
                        <div style="border-top: 1px solid rgba(0,0,0,0.1); padding-top: 15px;">
                            <button class="btn btn-primary btn-small" onclick="dashboard.showDirections('${referral.to_branch_name}')" style="margin-right: 10px;">
                                <i class="fas fa-map-marker-alt"></i> Get Directions
                            </button>
                            <button class="btn btn-secondary btn-small" onclick="dashboard.addToCalendar('${referral.appointment_date}', '${referral.appointment_time}', '${referral.to_branch_name}')">
                                <i class="fas fa-calendar-plus"></i> Add to Calendar
                            </button>
                        </div>
                    `;
                    
                } else if (referral.status === 'rejected') {
                    detailsEl.innerHTML = `
                        Unfortunately, your referral to <strong>${referral.to_branch_name}</strong> was not approved.<br>
                        <strong>Reason:</strong> ${referral.rejection_reason || referral.response_notes || 'No specific reason provided'}<br>
                        <small><strong>Treatment:</strong> ${treatmentName}</small><br>
                        ${pricingInfo}
                        <small style="color: #6c757d;"><i class="fas fa-calendar-times"></i> Decision made on: ${referral.responded_at ? this.formatDate(referral.responded_at) : this.formatDate(referral.updated_at) || 'Recently'}</small><br>
                        <small style="color: #999;"><i class="fas fa-calendar-plus"></i> Originally created: ${this.formatDate(referral.created_at || referral.referral_date)}</small>
                    `;
                    actionEl.innerHTML = '📞 Please contact your original branch to discuss alternative treatment options or schedule a regular appointment.';
                    
                    // Add action button for rejected referrals
                    actionsEl.style.display = 'block';
                    actionsEl.innerHTML = `
                        <div style="border-top: 1px solid rgba(0,0,0,0.1); padding-top: 15px;">
                            <button class="btn btn-primary btn-small" onclick="dashboard.navigateToSection('book-appointment')">
                                <i class="fas fa-calendar-plus"></i> Book New Appointment
                            </button>
                        </div>
                    `;
                    
                } else if (referral.status === 'completed') {
                    // Enhanced completed status display
                    const completionDate = referral.completed_at || referral.completion_date || referral.updated_at;
                    const completionNotes = referral.completion_notes ? `<br><small><strong>Notes:</strong> ${referral.completion_notes}</small>` : '';
                    
                    detailsEl.innerHTML = `
                        🎊 Excellent! Your referral treatment at <strong>${referral.to_branch_name}</strong> has been completed successfully!<br>
                        ${referral.appointment_date ? 
                            `<strong>📅 Appointment completed:</strong> ${this.formatDate(referral.appointment_date)} at ${this.formatTime(referral.appointment_time)}<br>` : 
                            ''
                        }
                        <strong>🦷 Treatment:</strong> ${treatmentName}<br>
                        ${pricingInfo}
                        ${completionNotes}
                        <small style="color: #6c757d;"><i class="fas fa-check-double"></i> Completed on: ${completionDate ? this.formatDate(completionDate) : 'Recently'}</small><br>
                        <small style="color: #999;"><i class="fas fa-calendar-plus"></i> Originally created: ${this.formatDate(referral.created_at || referral.referral_date)}</small>
                    `;
                    actionEl.innerHTML = '� Thank you for completing your referral appointment! Your treatment has been successfully coordinated between branches. You can now book new appointments or hide this notification.';
                    
                    // Add action button for completed referrals
                    actionsEl.style.display = 'block';
                    actionsEl.innerHTML = `
                        <div style="border-top: 1px solid rgba(0,0,0,0.1); padding-top: 15px;">
                            <button class="btn btn-secondary btn-small" onclick="dashboard.hideReferralStatus()" style="margin-right: 10px;">
                                <i class="fas fa-eye-slash"></i> Hide Status
                            </button>
                            <button class="btn btn-primary btn-small" onclick="dashboard.navigateToSection('book-appointment')">
                                <i class="fas fa-calendar-plus"></i> Book Another Appointment
                            </button>
                        </div>
                    `;
                    
                    // Auto-hide completed referrals after 15 seconds with countdown
                    this.showCompletionNotification(referral);
                    
                } else if (referral.status === 'cancelled') {
                    detailsEl.innerHTML = `
                        Your referral to <strong>${referral.to_branch_name}</strong> has been cancelled.<br>
                        <strong>Reason:</strong> ${referral.cancellation_reason || 'Not specified'}<br>
                        <small><strong>Treatment:</strong> ${treatmentName}</small><br>
                        ${pricingInfo}
                        <small style="color: #6c757d;"><i class="fas fa-ban"></i> Cancelled on: ${referral.cancelled_at ? this.formatDate(referral.cancelled_at) : this.formatDate(referral.updated_at) || 'Recently'}</small><br>
                        <small style="color: #999;"><i class="fas fa-calendar-plus"></i> Originally created: ${this.formatDate(referral.created_at || referral.referral_date)}</small>
                    `;
                    actionEl.innerHTML = '📋 Your referral has been cancelled. You can book a new appointment at your original branch or request another referral if needed.';
                    
                    // Add action button for cancelled referrals
                    actionsEl.style.display = 'block';
                    actionsEl.innerHTML = `
                        <div style="border-top: 1px solid rgba(0,0,0,0.1); padding-top: 15px;">
                            <button class="btn btn-primary btn-small" onclick="dashboard.navigateToSection('book-appointment')">
                                <i class="fas fa-calendar-plus"></i> Book New Appointment
                            </button>
                        </div>
                    `;
                } else {
                    // Handle unknown status with pricing info
                    detailsEl.innerHTML = `
                        Referral to <strong>${referral.to_branch_name}</strong> for <strong>${treatmentName}</strong>.<br>
                        <small><strong>Status:</strong> ${referral.status}</small><br>
                        ${pricingInfo}
                        <small style="color: #6c757d;"><i class="fas fa-info-circle"></i> Last updated: ${this.formatDate(referral.updated_at || referral.created_at || referral.referral_date)}</small>
                    `;
                    actionEl.innerHTML = 'Please contact your healthcare provider for more information about this referral.';
                    // Hide action buttons for unknown status
                    actionsEl.style.display = 'none';
                }
                
                // Set consistent styling for details and action
                detailsEl.style.color = config.textColor;
                actionEl.style.color = '#6c757d';
                
                // Add a subtle animation effect when status changes
                referralInfoCard.style.transform = 'scale(0.98)';
                referralInfoCard.style.transition = 'all 0.3s ease';
                setTimeout(() => {
                    referralInfoCard.style.transform = 'scale(1)';
                }, 150);
                
                // Scroll to referral section to make it noticeable (for active and completed statuses)
                if (['pending', 'accepted', 'completed'].includes(referral.status)) {
                    setTimeout(() => {
                        referralSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }, 1000);
                }
            }
            
            // Display completed referral with invoice functionality
            displayCompletedReferral(referral) {
                const referralSection = document.getElementById('referral-status');
                const referralInfoCard = document.getElementById('referral-info');
                
                if (!referralSection || !referralInfoCard) return;
                
                referralSection.style.display = 'block';
                
                // Completed referral styling
                referralInfoCard.style.cssText = `
                    background: linear-gradient(135deg, #ecfdf5, #ffffff);
                    border: 2px solid #10b981;
                    border-radius: 12px;
                    padding: 20px;
                    margin: 15px 0;
                    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.15);
                `;
                
                const treatmentName = referral.treatment_name || 'Dental Treatment';
                const pricingInfo = this.buildPricingInfo(referral, treatmentName);
                
                // Build completed referral content
                referralInfoCard.innerHTML = `
                    <div style="background: linear-gradient(90deg, #10b981, #34d399); height: 4px; border-radius: 2px; margin: -10px -10px 15px -10px;"></div>
                    
                    <div style="display: flex; align-items: center; margin-bottom: 15px;">
                        <div style="font-size: 2rem; margin-right: 12px;">✅</div>
                        <div>
                            <h4 style="margin: 0; color: #065f46; font-size: 1.25rem;">Treatment Completed Successfully</h4>
                            <span style="
                                background: #d1fae5; 
                                color: #065f46; 
                                padding: 3px 8px; 
                                border-radius: 20px; 
                                font-size: 0.75rem; 
                                font-weight: 600; 
                                border: 1px solid #10b981;
                                margin-top: 5px;
                                display: inline-block;
                            ">COMPLETED</span>
                        </div>
                    </div>
                    
                    <div id="referral-details" style="margin-bottom: 15px;">
                        Your referral to <strong>${referral.to_branch_name}</strong> for <strong>${treatmentName}</strong> has been completed successfully.<br>
                        <small style="color: #065f46;"><strong>Completed on:</strong> ${this.formatDate(referral.completion_date || referral.completed_at)}</small><br>
                        ${pricingInfo}
                    </div>
                    
                    <div id="referral-action" style="background: #f0fdf4; padding: 12px; border-radius: 6px; margin-bottom: 15px;">
                        <p style="margin: 0; color: #166534; font-size: 14px;">
                            <i class="fas fa-check-circle"></i> Your treatment has been completed. You can view and print your invoices below.
                        </p>
                    </div>
                    
                    <div id="referral-actions" style="display: block;">
                        <div style="border-top: 1px solid rgba(16, 185, 129, 0.2); padding-top: 15px;">
                            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                ${referral.original_appointment_id ? `
                                <button class="btn btn-success" onclick="dashboard.viewInvoice(${referral.original_appointment_id}, 'Original Appointment Invoice')" style="flex: 1; min-width: 140px;">
                                    <i class="fas fa-file-invoice"></i> Original Invoice
                                </button>
                                ` : ''}
                                <button class="btn btn-primary" onclick="dashboard.viewInvoice(${referral.new_appointment_id || referral.id}, 'Referral Treatment Invoice')" style="flex: 1; min-width: 140px;">
                                    <i class="fas fa-file-invoice-dollar"></i> ${referral.original_appointment_id ? 'Referral' : 'Treatment'} Invoice
                                </button>
                                ${referral.original_appointment_id ? `
                                <button class="btn btn-info" onclick="dashboard.printBothInvoices(${referral.original_appointment_id}, ${referral.new_appointment_id || referral.id})" style="flex: 1; min-width: 140px;">
                                    <i class="fas fa-print"></i> Print All
                                </button>
                                ` : ''}
                                <button class="btn btn-secondary" onclick="dashboard.hideReferralStatus()" style="min-width: 100px;">
                                    <i class="fas fa-eye-slash"></i> Hide
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            }
            
            // View invoice for appointments
            async viewInvoice(appointmentId, title = 'Treatment Invoice') {
                if (!appointmentId) {
                    this.showAlert('error', 'Invalid appointment ID');
                    return;
                }
                
                try {
                    this.showAlert('info', 'Loading invoice...', 3000);
                    
                    const response = await fetch(`../../src/controllers/InvoiceController.php?action=getPatientInvoices`);
                    const data = await response.json();
                    
                    if (data.success && data.invoices) {
                        // Find invoice for this appointment
                        const invoice = data.invoices.find(inv => inv.appointment_id == appointmentId);
                        
                        if (invoice) {
                            this.displayInvoiceModal(invoice, title);
                        } else {
                            this.showAlert('warning', 'No invoice found for this appointment. It may still be processing.');
                        }
                    } else {
                        this.showAlert('error', 'Failed to load invoice: ' + (data.message || 'Unknown error'));
                    }
                } catch (error) {
                    console.error('Error loading invoice:', error);
                    this.showAlert('error', 'Error loading invoice. Please try again later.');
                }
            }
            
            // Print both invoices for referrals
            async printBothInvoices(originalAppointmentId, newAppointmentId) {
                try {
                    this.showAlert('info', 'Preparing invoices for printing...', 3000);
                    
                    const response = await fetch(`../../src/controllers/InvoiceController.php?action=getPatientInvoices`);
                    const data = await response.json();
                    
                    if (data.success && data.invoices) {
                        let invoicesToPrint = [];
                        
                        // Find original invoice if exists
                        if (originalAppointmentId) {
                            const originalInvoice = data.invoices.find(inv => inv.appointment_id == originalAppointmentId);
                            if (originalInvoice) {
                                invoicesToPrint.push({
                                    invoice: originalInvoice,
                                    title: 'Original Appointment Invoice'
                                });
                            }
                        }
                        
                        // Find referral invoice
                        if (newAppointmentId) {
                            const referralInvoice = data.invoices.find(inv => inv.appointment_id == newAppointmentId);
                            if (referralInvoice) {
                                invoicesToPrint.push({
                                    invoice: referralInvoice,
                                    title: 'Referral Treatment Invoice'
                                });
                            }
                        }
                        
                        if (invoicesToPrint.length > 0) {
                            this.printMultipleInvoices(invoicesToPrint);
                        } else {
                            this.showAlert('warning', 'No invoices found to print. They may still be processing.');
                        }
                    } else {
                        this.showAlert('error', 'Failed to load invoices: ' + (data.message || 'Unknown error'));
                    }
                } catch (error) {
                    console.error('Error loading invoices:', error);
                    this.showAlert('error', 'Error loading invoices. Please try again later.');
                }
            }
            
            // Display invoice in a modal
            displayInvoiceModal(invoice, title = 'Treatment Invoice') {
                const modal = document.createElement('div');
                modal.className = 'invoice-modal';
                modal.style.cssText = `
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.7);
                    z-index: 10000;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    animation: fadeIn 0.3s ease-out;
                `;
                
                const modalContent = document.createElement('div');
                modalContent.style.cssText = `
                    background: white;
                    border-radius: 12px;
                    max-width: 600px;
                    max-height: 90vh;
                    overflow-y: auto;
                    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
                    position: relative;
                `;
                
                modalContent.innerHTML = this.generateInvoiceHTML(invoice, false, title);
                modal.appendChild(modalContent);
                document.body.appendChild(modal);
                
                // Close modal when clicking outside
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) {
                        document.body.removeChild(modal);
                    }
                });
            }
            
            // Print multiple invoices
            printMultipleInvoices(invoicesToPrint) {
                const printWindow = window.open('', '_blank', 'width=800,height=600');
                let combinedHTML = `
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <title>Patient Invoices</title>
                        <style>
                            body { font-family: Arial, sans-serif; margin: 20px; }
                            .invoice-page { margin-bottom: 40px; page-break-after: always; }
                            .invoice-page:last-child { page-break-after: auto; }
                            .invoice-header { text-align: center; margin-bottom: 20px; }
                            .invoice-title { color: #2563eb; font-size: 24px; margin-bottom: 10px; }
                            @media print {
                                body { margin: 0; }
                                .invoice-page { margin-bottom: 0; }
                            }
                        </style>
                    </head>
                    <body>
                `;
                
                invoicesToPrint.forEach((item, index) => {
                    combinedHTML += `
                        <div class="invoice-page">
                            <div class="invoice-header">
                                <h2 class="invoice-title">${item.title}</h2>
                            </div>
                            ${this.generateInvoiceHTML(item.invoice, true, item.title)}
                        </div>
                    `;
                });
                
                combinedHTML += `
                    </body>
                    </html>
                `;
                
                printWindow.document.write(combinedHTML);
                printWindow.document.close();
                
                printWindow.onload = function() {
                    setTimeout(() => {
                        printWindow.print();
                        printWindow.close();
                    }, 500);
                };
            }
            
            // Generate invoice HTML
            generateInvoiceHTML(invoice, forPrint = false, customTitle = null) {
                const formatCurrency = (amount) => {
                    return new Intl.NumberFormat('en-PH', {
                        style: 'currency',
                        currency: 'PHP'
                    }).format(amount || 0);
                };
                
                const formatDate = (dateString) => {
                    return new Date(dateString).toLocaleDateString('en-PH', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    });
                };
                
                return `
                    <div style="padding: 30px;">
                        ${!forPrint ? `
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <button onclick="dashboard.printSingleInvoice(${JSON.stringify(invoice).replace(/"/g, '&quot;')}, '${customTitle || 'DENTAL INVOICE'}')" style="background: #28a745; color: white; border: none; border-radius: 8px; padding: 8px 16px; cursor: pointer; font-size: 14px;">
                                <i class="fas fa-print"></i> Print Invoice
                            </button>
                            <button onclick="this.closest('.invoice-modal').remove()" style="background: #dc3545; color: white; border: none; border-radius: 50%; width: 35px; height: 35px; cursor: pointer; font-size: 18px;">&times;</button>
                        </div>
                        ` : ''}
                        
                        <div class="invoice-header">
                            <h1 style="color: #007bff; margin: 0;">${customTitle || 'DENTAL INVOICE'}</h1>
                            <h2 style="margin: 5px 0 0 0; color: #6c757d;">Invoice #${invoice.invoice_number}</h2>
                        </div>
                        
                        <div class="invoice-details" style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">
                            <div>
                                <h3 style="margin-bottom: 10px; color: #333;">Bill To:</h3>
                                <p style="margin: 5px 0;"><strong>${invoice.patient_name}</strong></p>
                                <p style="margin: 5px 0;">${invoice.patient_email}</p>
                                <p style="margin: 5px 0;">${invoice.patient_phone || ''}</p>
                            </div>
                            <div style="text-align: right;">
                                <h3 style="margin-bottom: 10px; color: #333;">Invoice Details:</h3>
                                <p style="margin: 5px 0;"><strong>Date:</strong> ${formatDate(invoice.invoice_date)}</p>
                                <p style="margin: 5px 0;"><strong>Status:</strong> <span style="color: ${invoice.status === 'paid' ? '#28a745' : '#ffc107'};">${invoice.status.toUpperCase()}</span></p>
                                <p style="margin: 5px 0;"><strong>Branch:</strong> ${invoice.branch_name}</p>
                            </div>
                        </div>
                        
                        <table class="invoice-table" style="width: 100%; border-collapse: collapse; margin-bottom: 30px;">
                            <thead>
                                <tr style="background: #f8f9fa;">
                                    <th style="padding: 12px; text-align: left; border: 1px solid #dee2e6;">Service</th>
                                    <th style="padding: 12px; text-align: left; border: 1px solid #dee2e6;">Date</th>
                                    <th style="padding: 12px; text-align: right; border: 1px solid #dee2e6;">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td style="padding: 12px; border: 1px solid #dee2e6;">${invoice.treatment_name}</td>
                                    <td style="padding: 12px; border: 1px solid #dee2e6;">${formatDate(invoice.appointment_date)}</td>
                                    <td style="padding: 12px; text-align: right; border: 1px solid #dee2e6;">${formatCurrency(invoice.total_amount)}</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr style="background: #f8f9fa; font-weight: bold;">
                                    <td colspan="2" style="padding: 12px; border: 1px solid #dee2e6; text-align: right;">Total:</td>
                                    <td style="padding: 12px; text-align: right; border: 1px solid #dee2e6;">${formatCurrency(invoice.total_amount)}</td>
                                </tr>
                            </tfoot>
                        </table>
                        
                        <div style="text-align: center; color: #6c757d; font-size: 0.9em;">
                            <p>Thank you for choosing our dental services!</p>
                        </div>
                    </div>
                `;
            }
            
            // Helper function to hide referral status
            hideReferralStatus() {
                const referralSection = document.getElementById('referral-status');
                if (referralSection) {
                    referralSection.style.display = 'none';
                    this.showAlert('success', '✅ Referral status hidden. You can refresh the page to see it again if needed.');
                }
            }
            
            // Helper function to hide referral status
            hideReferralStatus() {
                const referralSection = document.getElementById('referral-status');
                if (referralSection) {
                    referralSection.style.display = 'none';
                    this.showAlert('success', '✅ Referral status hidden. You can refresh the page to see it again if needed.');
                }
            }
            
            // Helper function to show completion notification with countdown
            showCompletionNotification(referral) {
                // Create countdown notification
                const countdownDiv = document.createElement('div');
                countdownDiv.id = 'completion-countdown';
                countdownDiv.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: linear-gradient(135deg, #e7f3ff, #ffffff);
                    border: 2px solid #007bff;
                    border-radius: 12px;
                    padding: 16px 20px;
                    z-index: 1000;
                    box-shadow: 0 8px 25px rgba(0, 123, 255, 0.3);
                    max-width: 320px;
                    animation: slideInRight 0.5s ease-out;
                `;
                
                let countdown = 15; // 15 seconds
                countdownDiv.innerHTML = `
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="font-size: 24px;">🎉</div>
                        <div style="flex: 1;">
                            <div style="font-weight: 600; color: #004085; margin-bottom: 4px;">
                                Treatment Completed!
                            </div>
                            <div style="font-size: 12px; color: #6c757d;">
                                This notification will auto-hide in <span id="countdown-number" style="font-weight: 600; color: #007bff;">${countdown}</span> seconds
                            </div>
                        </div>
                        <button onclick="dashboard.clearCompletionNotification()" style="background: none; border: none; color: #6c757d; font-size: 18px; cursor: pointer; padding: 4px;">
                            ×
                        </button>
                    </div>
                `;
                
                document.body.appendChild(countdownDiv);
                
                // Countdown timer
                const countdownInterval = setInterval(() => {
                    countdown--;
                    const countdownNumber = document.getElementById('countdown-number');
                    if (countdownNumber) {
                        countdownNumber.textContent = countdown;
                    }
                    
                    if (countdown <= 0) {
                        clearInterval(countdownInterval);
                        this.clearCompletionNotification();
                    }
                }, 1000);
                
                // Store interval ID for cleanup
                this.completionCountdownInterval = countdownInterval;
            }
            
            // Helper function to clear completion notification
            clearCompletionNotification() {
                const countdownDiv = document.getElementById('completion-countdown');
                if (countdownDiv) {
                    countdownDiv.style.animation = 'slideOutRight 0.3s ease-in';
                    setTimeout(() => {
                        countdownDiv.remove();
                    }, 300);
                }
                
                if (this.completionCountdownInterval) {
                    clearInterval(this.completionCountdownInterval);
                    this.completionCountdownInterval = null;
                }
            }
            
            // Helper functions for action buttons
            showDirections(branchName) {
                // Open Google Maps with branch name search
                const searchQuery = encodeURIComponent(`${branchName} dental clinic`);
                const mapsUrl = `https://www.google.com/maps/search/${searchQuery}`;
                window.open(mapsUrl, '_blank');
                this.showAlert('info', `🗺️ Opening directions to ${branchName}...`);
            }
            
            addToCalendar(date, time, branchName) {
                if (!date || !time) {
                    this.showAlert('warning', 'Appointment date and time not available');
                    return;
                }
                
                try {
                    const appointmentDate = new Date(`${date}T${time}`);
                    const endDate = new Date(appointmentDate.getTime() + (60 * 60 * 1000)); // Add 1 hour
                    
                    // Format dates for calendar
                    const startDateTime = appointmentDate.toISOString().replace(/[-:]/g, '').split('.')[0] + 'Z';
                    const endDateTime = endDate.toISOString().replace(/[-:]/g, '').split('.')[0] + 'Z';
                    
                    // Create Google Calendar URL
                    const calendarUrl = `https://calendar.google.com/calendar/render?action=TEMPLATE&text=Dental%20Appointment%20-%20Referral&dates=${startDateTime}/${endDateTime}&details=Referral%20appointment%20at%20${encodeURIComponent(branchName)}&location=${encodeURIComponent(branchName + ' dental clinic')}`;
                    
                    window.open(calendarUrl, '_blank');
                    this.showAlert('success', '📅 Calendar event created! Check your new tab.');
                } catch (error) {
                    console.error('Error creating calendar event:', error);
                    this.showAlert('error', 'Unable to create calendar event. Please add it manually.');
                }
            }
            
            // Patient referral approval functions
            approveReferral(referralId) {
                if (!referralId) {
                    this.showAlert('error', 'Invalid referral ID');
                    return;
                }
                
                // Show confirmation dialog
                if (!confirm('Are you sure you want to accept this referral? This will notify the receiving branch to review and schedule your appointment.')) {
                    return;
                }
                
                this.showAlert('info', 'Processing your approval...');
                
                const formData = new FormData();
                formData.append('action', 'approveReferralByPatient');
                formData.append('referral_id', referralId);
                formData.append('notes', 'Patient approved this referral'); // Default note
                
                fetch('../../src/controllers/ReferralController.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.showAlert('success', '✅ Referral approved! The receiving branch will review and contact you to schedule your appointment.');
                        // Reload referral status to show updated information
                        setTimeout(() => {
                            this.loadReferralStatus();
                        }, 1500);
                    } else {
                        this.showAlert('error', data.message || 'Failed to approve referral. Please try again.');
                    }
                })
                .catch(error => {
                    console.error('Error approving referral:', error);
                    this.showAlert('error', 'Network error occurred. Please try again.');
                });
            }
            
            showRejectReferralModal(referralId) {
                // Create modal for rejection with reason
                const modal = document.createElement('div');
                modal.className = 'modal';
                modal.style.display = 'block';
                modal.style.zIndex = '10000';
                
                modal.innerHTML = `
                    <div class="modal-content" style="max-width: 500px; animation: modalSlideIn 0.3s ease;">
                        <span class="close" onclick="this.closest('.modal').remove()">&times;</span>
                        <h2 style="color: #dc3545; margin-bottom: 20px;">
                            <i class="fas fa-thumbs-down"></i> Decline Referral
                        </h2>
                        
                        <div style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <i class="fas fa-info-circle" style="color: #856404; font-size: 18px;"></i>
                                <div>
                                    <h4 style="margin: 0; color: #856404; font-size: 14px;">Before declining</h4>
                                    <p style="margin: 2px 0 0 0; color: #856404; font-size: 13px;">
                                        Consider if you'd like to discuss alternative options with your referring dentist.
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <form id="rejectReferralForm">
                            <div class="form-group">
                                <label for="rejection-reason" style="font-weight: 600; margin-bottom: 8px; display: block;">
                                    <i class="fas fa-comment"></i> Reason for declining (optional)
                                </label>
                                <textarea 
                                    id="rejection-reason" 
                                    name="rejection_reason" 
                                    rows="3" 
                                    placeholder="Please share why you're declining this referral (optional)..."
                                    style="width: 100%; border: 1px solid #ddd; border-radius: 6px; padding: 12px; font-size: 14px; resize: vertical; min-height: 80px;"
                                ></textarea>
                                <small style="color: #6c757d; margin-top: 5px; display: block;">
                                    This information will help your care team provide better alternatives.
                                </small>
                            </div>
                            
                            <div style="display: flex; gap: 10px; margin-top: 20px;">
                                <button type="button" class="btn btn-secondary" onclick="this.closest('.modal').remove()" style="flex: 1;">
                                    <i class="fas fa-times"></i> Cancel
                                </button>
                                <button type="submit" class="btn btn-danger" style="flex: 1;">
                                    <i class="fas fa-thumbs-down"></i> Decline Referral
                                </button>
                            </div>
                        </form>
                    </div>
                `;
                
                document.body.appendChild(modal);
                
                // Handle form submission
                const form = modal.querySelector('#rejectReferralForm');
                form.addEventListener('submit', (e) => {
                    e.preventDefault();
                    const reason = modal.querySelector('#rejection-reason').value.trim();
                    this.rejectReferral(referralId, reason);
                    modal.remove();
                });
                
                // Auto-focus on textarea
                setTimeout(() => {
                    modal.querySelector('#rejection-reason').focus();
                }, 100);
            }
            
            rejectReferral(referralId, reason = '') {
                if (!referralId) {
                    this.showAlert('error', 'Invalid referral ID');
                    return;
                }
                
                this.showAlert('info', 'Processing your response...');
                
                const formData = new FormData();
                formData.append('action', 'rejectReferralByPatient');
                formData.append('referral_id', referralId);
                formData.append('rejection_reason', reason || 'Patient declined this referral');
                
                fetch('../../src/controllers/ReferralController.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.showAlert('success', '✅ Referral declined. You can still book a regular appointment at your original branch if needed.');
                        // Reload referral status to show updated information
                        setTimeout(() => {
                            this.loadReferralStatus();
                        }, 1500);
                    } else {
                        this.showAlert('error', data.message || 'Failed to decline referral. Please try again.');
                    }
                })
                .catch(error => {
                    console.error('Error rejecting referral:', error);
                    this.showAlert('error', 'Network error occurred. Please try again.');
                });
            }
            
            loadUserInfo() {
                // Load actual user session data - branch-independent approach
                fetch('../api/user-session.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success && data.user) {
                        const user = data.user;
                        this.currentUser = user; // Store for reference
                        
                        // Update welcome section with animation
                        const welcomeText = document.querySelector('.welcome-text');
                        if (welcomeText) {
                            welcomeText.classList.add('user-info-loaded');
                        }
                        
                        // Update user name
                        const userNameEl = document.getElementById('user-name');
                        if (userNameEl) {
                            userNameEl.innerHTML = `<span class="user-name-text">${user.name}</span>`;
                        }
                        
                        // Update branch info to show selected clinic or general network membership
                        const branchNameEl = document.getElementById('branch-name');
                        if (branchNameEl) {
                            // Check if there's a selected clinic
                            const selectedClinic = this.getSelectedClinicFromStorage();
                            if (selectedClinic && this.selectedClinicId) {
                                // Show selected clinic name
                                branchNameEl.innerHTML = `<span class="branch-name-text">${selectedClinic.name}</span>`;
                                branchNameEl.dataset.selectedClinicId = selectedClinic.id;
                                } else {
                                // Show general network membership when no clinic is selected
                                branchNameEl.innerHTML = `<span class="branch-name-text">Dental Clinic Network Member</span>`;
                                delete branchNameEl.dataset.selectedClinicId;
                                }
                        }
                        
                        // Only update navbar if no clinic is currently selected
                        if (!this.selectedClinicId) {
                            this.setGeneralNavbarBranding();
                        } else {
                            }
                        
                        // Update user avatar
                        const userAvatarEl = document.getElementById('user-avatar');
                        if (userAvatarEl) {
                            userAvatarEl.textContent = user.initials;
                            userAvatarEl.title = `${user.name} - Click to view profile`;
                        }
                        
                        // Update role and last login info
                        const roleEl = document.getElementById('user-role-display');
                        if (roleEl) {
                            roleEl.textContent = user.role.charAt(0).toUpperCase() + user.role.slice(1);
                        }
                        
                        const lastLoginEl = document.getElementById('last-login-display');
                        if (lastLoginEl) {
                            lastLoginEl.textContent = 'Active now';
                        }
                        
                        // Update navbar user info
                        const userNameNavEl = document.getElementById('user-name-nav');
                        if (userNameNavEl) {
                            userNameNavEl.textContent = user.name;
                        }
                        
                        const userBranchNavEl = document.getElementById('user-branch-nav');
                        if (userBranchNavEl) {
                            userBranchNavEl.textContent = 'Network Member';
                        }
                        
                        const userAvatarNavEl = document.getElementById('user-avatar-nav');
                        if (userAvatarNavEl) {
                            userAvatarNavEl.textContent = user.initials;
                        }
                        
                        // Load profile info after user data is available
                        this.loadProfileInfo();
                    } else {
                        console.error('API returned error:', data.message || 'Unknown error');
                        this.loadFallbackUserInfo();
                    }
                })
                .catch(error => {
                    console.error('Error loading user info:', error);
                    this.loadFallbackUserInfo();
                });
            }
            
            loadFallbackUserInfo() {
                // Fallback to placeholder data - branch-independent
                const userNameEl = document.getElementById('user-name');
                if (userNameEl) {
                    userNameEl.innerHTML = '<span class="user-name-text">Patient</span>';
                }
                
                const branchNameEl = document.getElementById('branch-name');
                if (branchNameEl) {
                    branchNameEl.innerHTML = '<span class="branch-name-text">Network Member</span>';
                }
                
                // Set general navbar branding for fallback
                this.setGeneralNavbarBranding();
                
                const userAvatarEl = document.getElementById('user-avatar');
                if (userAvatarEl) {
                    userAvatarEl.textContent = 'P';
                }
                
                const roleEl = document.getElementById('user-role-display');
                if (roleEl) {
                    roleEl.textContent = 'Patient';
                }
                
                const lastLoginEl = document.getElementById('last-login-display');
                if (lastLoginEl) {
                    lastLoginEl.textContent = 'Unable to load';
                }
                
                // Fallback for navbar
                const userNameNavEl = document.getElementById('user-name-nav');
                if (userNameNavEl) {
                    userNameNavEl.textContent = 'Patient';
                }
                
                const userBranchNavEl = document.getElementById('user-branch-nav');
                if (userBranchNavEl) {
                    userBranchNavEl.textContent = 'Selected Branch';
                }
                
                const userAvatarNavEl = document.getElementById('user-avatar-nav');
                if (userAvatarNavEl) {
                    userAvatarNavEl.textContent = 'P';
                }
                
                // Show user-friendly warning
                this.showAlert('warning', 'Unable to load user information. Using default values.');
            }
            
            loadProfileInfo() {
                if (!this.currentUser) return;
                
                const user = this.currentUser;
                
                // Update profile header (only if elements exist - they may not since profile was moved)
                const profileAvatar = document.getElementById('profile-avatar');
                if (profileAvatar) {
                    profileAvatar.textContent = user.initials;
                }
                
                const profileName = document.getElementById('profile-name');
                if (profileName) {
                    profileName.textContent = user.name;
                }
                
                const profileEmail = document.getElementById('profile-email');
                if (profileEmail) {
                    profileEmail.textContent = user.email;
                }
                
                const profileRole = document.getElementById('profile-role');
                if (profileRole) {
                    profileRole.textContent = user.role.charAt(0).toUpperCase() + user.role.slice(1);
                }
                
                // Update profile details (only if elements exist)
                const profileId = document.getElementById('profile-id');
                if (profileId) {
                    profileId.textContent = `#${user.id}`;
                }
                
                const profileBranch = document.getElementById('profile-branch');
                if (profileBranch) {
                    profileBranch.textContent = user.branch_name;
                }
                
                // Set member since date from API data (only if element exists)
                const profileMemberSince = document.getElementById('profile-member-since');
                if (profileMemberSince) {
                    if (user.member_since) {
                        const memberDate = new Date(user.member_since);
                        const memberSince = memberDate.toLocaleDateString('en-US', { 
                            year: 'numeric', 
                            month: 'long', 
                            day: 'numeric' 
                        });
                        profileMemberSince.textContent = memberSince;
                    } else {
                        profileMemberSince.textContent = 'Unknown';
                    }
                }
                
                // Calculate appointment statistics (only if method exists)
                if (typeof this.updateProfileStats === 'function') {
                    this.updateProfileStats();
                }
            }

            // Update profile statistics (safe method that checks if elements exist)
            updateProfileStats() {
                if (!this.appointments) return;
                
                const totalAppointments = this.appointments.length;
                const completedAppointments = this.appointments.filter(apt => apt.status === 'completed').length;
                const upcomingAppointments = this.appointments.filter(apt => apt.status === 'confirmed' || apt.status === 'scheduled').length;
                const cancelledAppointments = this.appointments.filter(apt => apt.status === 'cancelled').length;
                
                // Update elements only if they exist (since profile section was moved)
                const totalElement = document.getElementById('profile-total-appointments');
                if (totalElement) {
                    totalElement.textContent = totalAppointments;
                }
                
                const completedElement = document.getElementById('profile-completed-appointments');
                if (completedElement) {
                    completedElement.textContent = completedAppointments;
                }
                
                const upcomingElement = document.getElementById('profile-upcoming-appointments');
                if (upcomingElement) {
                    upcomingElement.textContent = upcomingAppointments;
                }
                
                const cancelledElement = document.getElementById('profile-cancelled-appointments');
                if (cancelledElement) {
                    cancelledElement.textContent = cancelledAppointments;
                }
            }

            // Enhanced Profile Management Methods
            loadCompleteProfileData() {
                fetch('../api/user-session.php?action=getProfile')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.profile) {
                        this.populateProfileView(data.profile);
                    } else {
                        console.error('Failed to load complete profile data:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error loading complete profile data:', error);
                });
            }

            populateProfileView(profile) {
                // Update view mode with complete data
                document.getElementById('profile-full-name').textContent = profile.name || 'Not provided';
                document.getElementById('profile-email-display').textContent = profile.email || 'Not provided';
                document.getElementById('profile-phone').textContent = profile.phone || 'Not provided';
                document.getElementById('profile-address').textContent = profile.address || 'Not provided';
                document.getElementById('profile-birthdate').textContent = profile.date_of_birth 
                    ? new Date(profile.date_of_birth).toLocaleDateString() : 'Not provided';
                document.getElementById('profile-gender').textContent = profile.gender 
                    ? profile.gender.charAt(0).toUpperCase() + profile.gender.slice(1) : 'Not specified';
                
                // Emergency contact
                const emergencyContact = profile.emergency_contact_name 
                    ? `${profile.emergency_contact_name}${profile.emergency_contact_phone ? ' (' + profile.emergency_contact_phone + ')' : ''}`
                    : 'Not provided';
                document.getElementById('profile-emergency-contact').textContent = emergencyContact;
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
                
                // Update UI
                document.getElementById('profile-view-mode').style.display = 'none';
                document.getElementById('profile-edit-mode').style.display = 'block';
                document.getElementById('edit-profile-btn').innerHTML = '<i class="fas fa-times"></i> Cancel Edit';
                document.getElementById('profile-status-badge').textContent = 'Edit Mode';
                document.getElementById('profile-status-badge').classList.add('editing');
                
                // Populate edit form with current data
                this.populateEditForm();
            }

            cancelEditMode() {
                this.isEditingProfile = false;
                
                // Update UI
                document.getElementById('profile-view-mode').style.display = 'block';
                document.getElementById('profile-edit-mode').style.display = 'none';
                document.getElementById('edit-profile-btn').innerHTML = '<i class="fas fa-edit"></i> Edit Profile';
                document.getElementById('profile-status-badge').textContent = 'View Mode';
                document.getElementById('profile-status-badge').classList.remove('editing');
                
                // Reset form
                document.getElementById('profile-edit-form').reset();
            }

            populateEditForm() {
                if (!this.currentUser) return;
                
                const user = this.currentUser;
                
                // Populate basic fields
                document.getElementById('edit-name').value = user.name || '';
                document.getElementById('edit-email').value = user.email || '';
                document.getElementById('edit-phone').value = user.phone || '';
                document.getElementById('edit-address').value = user.address || '';
                document.getElementById('edit-birthdate').value = user.date_of_birth || '';
                document.getElementById('edit-gender').value = user.gender || '';
                document.getElementById('edit-emergency-name').value = user.emergency_contact_name || '';
                document.getElementById('edit-emergency-phone').value = user.emergency_contact_phone || '';
                
                // Populate preferences
                document.getElementById('edit-receive-notifications').checked = user.receive_notifications !== false;
                document.getElementById('edit-receive-email-reminders').checked = user.receive_email_reminders !== false;
                document.getElementById('edit-receive-sms-reminders').checked = user.receive_sms_reminders === true;
            }

            saveProfile(event) {
                event.preventDefault();
                
                const formData = new FormData(event.target);
                const profileData = Object.fromEntries(formData.entries());
                
                // Convert checkbox values
                profileData.receive_notifications = formData.has('receive_notifications');
                profileData.receive_email_reminders = formData.has('receive_email_reminders');
                profileData.receive_sms_reminders = formData.has('receive_sms_reminders');
                
                // Show loading state
                const submitBtn = event.target.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
                submitBtn.disabled = true;
                
                fetch('../api/user-session.php?action=updateProfile', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(profileData)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.showAlert('success', 'Profile updated successfully!');
                        this.cancelEditMode();
                        this.loadUserInfo(); // Refresh user data
                        this.loadCompleteProfileData(); // Refresh profile view
                    } else {
                        this.showAlert('error', data.message || 'Failed to update profile');
                    }
                })
                .catch(error => {
                    console.error('Error updating profile:', error);
                    this.showAlert('error', 'Failed to update profile');
                })
                .finally(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                });
            }

            showChangePasswordModal() {
                document.getElementById('change-password-modal').style.display = 'flex';
                document.getElementById('current-password').focus();
            }

            hideChangePasswordModal() {
                document.getElementById('change-password-modal').style.display = 'none';
                document.getElementById('change-password-form').reset();
            }

            // Close modal when clicking outside
            setupModalHandlers() {
                const modal = document.getElementById('change-password-modal');
                if (modal) {
                    modal.addEventListener('click', (e) => {
                        if (e.target === modal) {
                            this.hideChangePasswordModal();
                        }
                    });
                }
            }

            changePassword(event) {
                event.preventDefault();
                
                const formData = new FormData(event.target);
                const currentPassword = formData.get('current_password');
                const newPassword = formData.get('new_password');
                const confirmPassword = formData.get('confirm_password');
                
                // Validate passwords match
                if (newPassword !== confirmPassword) {
                    this.showAlert('error', 'New passwords do not match');
                    return;
                }
                
                // Show loading state
                const submitBtn = event.target.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Changing...';
                submitBtn.disabled = true;
                
                fetch('../api/user-session.php?action=changePassword', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        current_password: currentPassword,
                        new_password: newPassword
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.showAlert('success', 'Password changed successfully!');
                        this.hideChangePasswordModal();
                    } else {
                        this.showAlert('error', data.message || 'Failed to change password');
                    }
                })
                .catch(error => {
                    console.error('Error changing password:', error);
                    this.showAlert('error', 'Failed to change password');
                })
                .finally(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                });
            }

            downloadProfile() {
                if (!this.currentUser) {
                    this.showAlert('error', 'Profile data not available');
                    return;
                }
                
                // Create profile data for download
                const profileData = {
                    'Patient ID': this.currentUser.id,
                    'Full Name': this.currentUser.name,
                    'Email': this.currentUser.email,
                    'Phone': this.currentUser.phone || 'Not provided',
                    'Address': this.currentUser.address || 'Not provided',
                    'Date of Birth': this.currentUser.date_of_birth || 'Not provided',
                    'Gender': this.currentUser.gender || 'Not specified',
                    'Emergency Contact': this.currentUser.emergency_contact_name || 'Not provided',
                    'Emergency Phone': this.currentUser.emergency_contact_phone || 'Not provided',
                    'Member Since': this.currentUser.member_since || 'Unknown',
                    'Total Appointments': this.appointments.length,
                    'Completed Appointments': this.appointments.filter(apt => apt.status === 'completed').length,
                    'Pending Appointments': this.appointments.filter(apt => 
                        apt.status === 'confirmed' || apt.status === 'pending'
                    ).length
                };
                
                // Convert to text format
                let content = '=== PATIENT PROFILE ===\n\n';
                for (const [key, value] of Object.entries(profileData)) {
                    content += `${key}: ${value}\n`;
                }
                content += `\nGenerated on: ${new Date().toLocaleString()}\n`;
                
                // Create and download file
                const blob = new Blob([content], { type: 'text/plain' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `profile_${this.currentUser.name.replace(/\s+/g, '_')}_${new Date().toISOString().split('T')[0]}.txt`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
                
                this.showAlert('success', 'Profile downloaded successfully!');
            }
            
            loadAppointments() {
                fetch('../../src/controllers/AppointmentController.php?action=getPatientAppointments')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        this.appointments = data.appointments;
                        this.displayAppointments(data.appointments);
                        this.updateStats(data.appointments);
                        // Update profile statistics (only if method exists)
                        if (typeof this.updateProfileStats === 'function') {
                            this.updateProfileStats();
                        }
                    } else {
                        console.error('Failed to load appointments:', data.message);
                        this.showAlert('error', 'Failed to load appointments');
                        this.displayEmptyState();
                    }
                })
                .catch(error => {
                    console.error('Error loading appointments:', error);
                    this.showAlert('error', 'Failed to load appointments');
                    this.displayEmptyState();
                });
            }

            loadTreatmentTypes() {
                fetch('../../src/controllers/TreatmentController.php?action=getTreatmentTypes')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.treatmentTypes = data.treatmentTypes;
                        this.populateTreatmentTypesDropdown(data.treatmentTypes);
                    } else {
                        console.error('Failed to load treatment types:', data.message);
                        this.showAlert('error', 'Failed to load treatment types');
                    }
                })
                .catch(error => {
                    console.error('Error loading treatment types:', error);
                    this.showAlert('error', 'Failed to load treatment types');
                });
            }

            populateTreatmentTypesDropdown(treatmentTypes) {
                const select = document.getElementById('treatment_type_id');
                
                // Clear existing options except the first one
                while (select.children.length > 1) {
                    select.removeChild(select.lastChild);
                }
                
                treatmentTypes.forEach(treatment => {
                    if (treatment.is_available) {
                        const option = document.createElement('option');
                        option.value = treatment.id;
                        option.textContent = `${treatment.name} (${this.formatDuration(treatment.duration_minutes)}) - ₱${parseFloat(treatment.price).toLocaleString()}`;
                        option.dataset.duration = treatment.duration_minutes;
                        option.dataset.description = treatment.description;
                        option.dataset.price = treatment.price;
                        select.appendChild(option);
                    }
                });
            }

            loadTreatmentsForClinic(clinicId) {
                const treatmentSelect = document.getElementById('treatment_type_id');
                
                // Show loading state
                treatmentSelect.innerHTML = '<option value="">⏳ Loading treatments...</option>';
                treatmentSelect.disabled = true;
                
                const apiUrl = `../../src/controllers/TreatmentController.php?action=getTreatmentTypes&branch_id=${clinicId}`;
                fetch(apiUrl)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.treatmentTypes) {
                        treatmentSelect.innerHTML = '<option value="">-- Select treatment --</option>';
                        
                        data.treatmentTypes.forEach(treatment => {
                            const option = document.createElement('option');
                            option.value = treatment.id;
                            option.textContent = `${treatment.name} - ₱${treatment.price}`;
                            option.dataset.duration = treatment.duration_minutes;
                            option.dataset.price = treatment.price;
                            treatmentSelect.appendChild(option);
                        });
                        
                        treatmentSelect.disabled = false;
                    } else {
                        treatmentSelect.innerHTML = '<option value="">No treatments available</option>';
                        console.error('Failed to load treatments:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error loading treatments:', error);
                    treatmentSelect.innerHTML = '<option value="">Error loading treatments</option>';
                });
            }

            handleTreatmentTypeChange(e) {
                const selectedOption = e.target.selectedOptions[0];
                const durationInfo = document.getElementById('treatment-duration-info');
                const durationSpan = document.getElementById('selected-duration');
                
                if (selectedOption && selectedOption.value) {
                    this.selectedTreatment = {
                        id: selectedOption.value,
                        name: selectedOption.textContent.split(' (')[0],
                        duration_minutes: parseInt(selectedOption.dataset.duration),
                        description: selectedOption.dataset.description
                    };
                    
                    durationSpan.textContent = this.formatDuration(this.selectedTreatment.duration_minutes);
                    durationInfo.style.display = 'block';
                    
                    // Refresh available times if date is selected
                    const dateInput = document.getElementById('appointment_date');
                    if (dateInput.value) {
                        this.loadAvailableTimeSlots(dateInput.value);
                    }
                } else {
                    this.selectedTreatment = null;
                    durationInfo.style.display = 'none';
                    this.resetTimeSlots();
                }
            }

            handleDateChange(e) {
                const selectedDate = e.target.value;
                if (selectedDate && this.currentUser) {
                    this.loadDynamicTimeSlots(selectedDate);
                } else {
                    this.resetTimeSlots();
                }
            }

            handleTimeChange(e) {
                const selectedTime = e.target.value;
                const infoElement = document.getElementById('time-availability-info');
                
                if (selectedTime && this.selectedTreatment) {
                    const endTime = this.calculateEndTime(selectedTime, this.selectedTreatment.duration_minutes);
                    infoElement.innerHTML = `⏰ Your appointment will be from ${this.formatTime(selectedTime)} to ${this.formatTime(endTime)}`;
                    infoElement.style.color = 'var(--primary-600)';
                } else {
                    infoElement.textContent = 'Choose your preferred time slot';
                    infoElement.style.color = '';
                }
            }

            loadDynamicTimeSlots(date) {
                // Use selected clinic if available, otherwise fall back to user's branch
                // IMPORTANT: Prioritize selectedClinicId for cross-clinic booking
                let branchId = null;
                
                // First priority: selectedClinicId (for cross-clinic booking)
                if (this.selectedClinicId && this.selectedClinicId !== 'null' && this.selectedClinicId !== '') {
                    branchId = parseInt(this.selectedClinicId);
                    } 
                // Second priority: hidden clinic field value (if set)
                else {
                    const hiddenClinicField = document.getElementById('clinic_id');
                    if (hiddenClinicField && hiddenClinicField.value && hiddenClinicField.value !== '') {
                        branchId = parseInt(hiddenClinicField.value);
                        this.selectedClinicId = branchId; // Sync the selectedClinicId
                        }
                    // Last resort: user's home clinic
                    else if (this.currentUser && this.currentUser.branch_id) {
                        branchId = parseInt(this.currentUser.branch_id);
                        } else {
                        console.error('No clinic selected and user branch information not available');
                        this.showAlert('error', 'Please select a clinic by clicking "Book Now" from the clinic listing page.');
                        return;
                    }
                }

                const timeSelect = document.getElementById('appointment_time');
                const infoElement = document.getElementById('time-availability-info');
                
                // Show loading state
                timeSelect.innerHTML = '<option value="">⏳ Loading available times...</option>';
                timeSelect.disabled = true;
                infoElement.textContent = 'Loading available appointment times...';
                infoElement.style.color = 'var(--primary-600)';

                // Get treatment duration if selected
                const treatmentDuration = this.selectedTreatment ? this.selectedTreatment.duration_minutes : 60;

                const params = new URLSearchParams({
                    action: 'get_time_slots',
                    branch_id: branchId, // Use determined clinic ID
                    date: date,
                    duration: treatmentDuration
                });

                fetch(`../api/time-slots.php?${params}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.populateTimeSlots(data.slots, data.day_info, treatmentDuration);
                        
                        // Update info based on availability
                        if (data.slots.length === 0) {
                            infoElement.innerHTML = `❌ ${data.day_info.day}: Branch is closed on this day`;
                            infoElement.style.color = 'var(--error-600)';
                        } else {
                            const availableCount = data.available_count;
                            const totalCount = data.total_slots;
                            
                            if (availableCount === 0) {
                                infoElement.innerHTML = `⚠️ ${data.day_info.day}: All ${totalCount} slots are fully booked`;
                                infoElement.style.color = 'var(--warning-600)';
                            } else {
                                const durationText = this.selectedTreatment ? ` (${this.formatDuration(treatmentDuration)} treatments)` : '';
                                infoElement.innerHTML = `✅ ${data.day_info.day}: ${availableCount}/${totalCount} slots available${durationText}`;
                                infoElement.style.color = 'var(--success-600)';
                            }
                        }
                    } else {
                        console.error('Failed to load time slots:', data.error);
                        this.showAlert('error', 'Failed to load available time slots');
                        timeSelect.innerHTML = '<option value="">⚠️ Failed to load times</option>';
                        infoElement.textContent = 'Unable to load available times';
                        infoElement.style.color = 'var(--error-600)';
                    }
                })
                .catch(error => {
                    console.error('Error loading time slots:', error);
                    this.showAlert('error', 'Failed to load available time slots');
                    timeSelect.innerHTML = '<option value="">⚠️ Failed to load times</option>';
                    infoElement.textContent = 'Unable to load available times';
                    infoElement.style.color = 'var(--error-600)';
                })
                .finally(() => {
                    timeSelect.disabled = false;
                });
            }

            populateTimeSlots(slots, dayInfo, treatmentDuration = 60) {
                const timeSelect = document.getElementById('appointment_time');
                
                // Clear existing options
                timeSelect.innerHTML = '<option value="">-- Select time --</option>';
                
                if (slots.length === 0) {
                    timeSelect.innerHTML += `<option value="" disabled>🏥 Closed on ${dayInfo.day}</option>`;
                    return;
                }
                
                // Add available slots with enhanced availability and duration display
                slots.forEach(slot => {
                    const option = document.createElement('option');
                    option.value = slot.time;
                    
                    if (slot.available) {
                        // Calculate end time for this slot
                        const endTime = this.calculateEndTime(slot.time, treatmentDuration);
                        const formattedEndTime = this.formatTime(endTime);
                        const durationText = this.formatDuration(treatmentDuration);
                        
                        // Enhanced display with clear availability indicator and duration
                        option.textContent = `✅ AVAILABLE | ${slot.display} - ${formattedEndTime} | Duration: ${durationText}`;
                        option.disabled = false;
                        option.style.color = '#10b981'; // Green color for available
                        option.style.fontWeight = '500';
                    } else {
                        // Show why slot is not available with clear indicators
                        let reason = 'Booked';
                        let emoji = '❌';
                        
                        if (slot.reason === 'blocked') {
                            reason = 'Time blocked';
                            emoji = '🚫';
                        } else if (slot.reason === 'insufficient_time') {
                            reason = 'Insufficient time';
                            emoji = '⏰';
                        } else if (slot.reason === 'overlap') {
                            reason = 'Already booked';
                            emoji = '📅';
                        }
                        
                        option.textContent = `${emoji} NOT AVAILABLE | ${slot.display} | Reason: ${reason}`;
                        option.disabled = true;
                        option.style.color = '#999';
                        option.style.fontStyle = 'italic';
                    }
                    
                    timeSelect.appendChild(option);
                });
            }

            loadAvailableTimeSlots(date) {
                // Legacy method - now redirects to new dynamic loading
                this.loadDynamicTimeSlots(date);
            }

            resetTimeSlots() {
                const timeSelect = document.getElementById('appointment_time');
                const infoElement = document.getElementById('time-availability-info');
                
                // Reset to default static options
                timeSelect.innerHTML = `
                    <option value="">-- Select time --</option>
                    <option value="08:00">🌅 08:00 AM</option>
                    <option value="09:00">🌅 09:00 AM</option>
                    <option value="10:00">🌞 10:00 AM</option>
                    <option value="11:00">🌞 11:00 AM</option>
                    <option value="13:00">🌇 01:00 PM</option>
                    <option value="14:00">🌇 02:00 PM</option>
                    <option value="15:00">🌆 03:00 PM</option>
                    <option value="16:00">🌆 04:00 PM</option>
                `;
                
                infoElement.textContent = 'Choose your preferred time slot';
                infoElement.style.color = '';
            }

            formatDuration(minutes) {
                const hours = Math.floor(minutes / 60);
                const mins = minutes % 60;
                
                if (hours === 0) {
                    return `${mins} minutes`;
                } else if (mins === 0) {
                    return `${hours} hour${hours > 1 ? 's' : ''}`;
                } else {
                    return `${hours} hour${hours > 1 ? 's' : ''} ${mins} minutes`;
                }
            }

            calculateEndTime(startTime, durationMinutes) {
                const [hours, minutes] = startTime.split(':').map(Number);
                const startDate = new Date();
                startDate.setHours(hours, minutes, 0, 0);
                
                const endDate = new Date(startDate.getTime() + durationMinutes * 60000);
                
                return endDate.toTimeString().slice(0, 5);
            }
            
            formatTime(timeString) {
                const [hours, minutes] = timeString.split(':').map(Number);
                const date = new Date();
                date.setHours(hours, minutes);
                
                return date.toLocaleTimeString('en-US', {
                    hour: 'numeric',
                    minute: '2-digit',
                    hour12: true
                });
            }
            
            updateStats(appointments) {
                const total = appointments.length;
                const pending = appointments.filter(apt => apt.status === 'pending').length;
                const upcoming = appointments.filter(apt => {
                    const aptDate = new Date(apt.appointment_date);
                    const today = new Date();
                    const weekFromNow = new Date(today.getTime() + 7 * 24 * 60 * 60 * 1000);
                    return aptDate >= today && aptDate <= weekFromNow && 
                           (apt.status === 'approved' || apt.status === 'pending');
                }).length;
                
                // Animate numbers
                this.animateNumber('total-appointments', total);
                this.animateNumber('pending-appointments', pending);
                this.animateNumber('upcoming-appointments', upcoming);
                
                // Update appointments count badge
                document.getElementById('appointments-count').textContent = total;
            }
            
            animateNumber(elementId, targetNumber) {
                const element = document.getElementById(elementId);
                const duration = 1000;
                const start = 0;
                const increment = targetNumber / (duration / 16);
                let current = start;
                
                const timer = setInterval(() => {
                    current += increment;
                    if (current >= targetNumber) {
                        current = targetNumber;
                        clearInterval(timer);
                    }
                    element.textContent = Math.floor(current);
                }, 16);
            }
            
            displayAppointments(appointments) {
                const container = document.getElementById('appointments-list');
                
                if (appointments.length === 0) {
                    this.displayEmptyState();
                    return;
                }
                
                const table = `
                    <div class="enhanced-table">
                        <table>
                            <thead>
                                <tr>
                                    <th><i class="fas fa-calendar"></i> Date & Time</th>
                                    <th><i class="fas fa-tooth"></i> Treatment & Service</th>
                                    <th><i class="fas fa-hospital"></i> Branch & Location</th>
                                    <th><i class="fas fa-peso-sign"></i> Price</th>
                                    <th><i class="fas fa-info-circle"></i> Status</th>
                                    <th><i class="fas fa-sticky-note"></i> Notes</th>
                                    <th><i class="fas fa-cog"></i> Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${appointments.map(appointment => this.renderAppointmentRow(appointment)).join('')}
                            </tbody>
                        </table>
                    </div>
                `;
                
                container.innerHTML = table;
            }
            
            // Helper function to get treatment pricing for appointments
            async fetchAppointmentPrice(appointment) {
                // First check if price is already in appointment data
                if (appointment.treatment_price && appointment.treatment_price > 0) {
                    return appointment.treatment_price;
                }
                
                // Try to fetch from branch services if we have treatment and branch info
                if (appointment.treatment_type_id && appointment.branch_id) {
                    try {
                        const response = await fetch(`../../src/controllers/TreatmentController.php?action=getBranchServicePrice&treatment_id=${appointment.treatment_type_id}&branch_id=${appointment.branch_id}`);
                        const data = await response.json();
                        
                        if (data.success && data.price) {
                            return data.price;
                        }
                    } catch (error) {
                        }
                }
                
                return null;
            }
            
            // Helper function to build appointment price display
            // For referred appointments, shows the original treatment price for tracking purposes
            buildAppointmentPriceDisplay(appointment) {
                // Check for direct price field first
                if (appointment.treatment_price && appointment.treatment_price > 0) {
                    return `
                        <div class="price-display">
                            <span class="price-amount">${this.formatPrice(appointment.treatment_price)}</span>
                            <small class="price-label">Treatment Cost</small>
                        </div>
                    `;
                }
                
                // For referrals, show the original appointment's treatment price for tracking
                if (appointment.status === 'referred') {
                    // Check if we have the original treatment price
                    if (appointment.treatment_price && appointment.treatment_price > 0) {
                        return `
                            <div class="price-display referral-price">
                                <span class="price-amount">${this.formatPrice(appointment.treatment_price)}</span>
                                <small class="price-label">Original Price</small>
                            </div>
                        `;
                    } else {
                        // Async fetch the original price if not available
                        if (appointment.treatment_type_id && appointment.branch_id) {
                            this.fetchAppointmentPrice(appointment).then(price => {
                                if (price) {
                                    const priceElement = document.querySelector(`#appointment-${appointment.id} .price-amount`);
                                    const labelElement = document.querySelector(`#appointment-${appointment.id} .price-label`);
                                    if (priceElement && labelElement) {
                                        priceElement.textContent = this.formatPrice(price);
                                        labelElement.textContent = 'Original Price';
                                    }
                                }
                            });
                        }
                        
                        return `
                            <div id="appointment-${appointment.id}" class="price-display referral-price">
                                <span class="price-amount">₱ Loading...</span>
                                <small class="price-label">Original Price</small>
                            </div>
                        `;
                    }
                }
                
                // Async fetch and update
                if (appointment.treatment_type_id && appointment.branch_id) {
                    this.fetchAppointmentPrice(appointment).then(price => {
                        if (price) {
                            const priceElement = document.querySelector(`#appointment-${appointment.id} .price-amount`);
                            if (priceElement) {
                                priceElement.textContent = this.formatPrice(price);
                                priceElement.parentElement.querySelector('.price-label').textContent = 'Treatment Cost';
                            }
                        }
                    });
                    
                    return `
                        <div id="appointment-${appointment.id}" class="price-display">
                            <span class="price-amount">₱ Loading...</span>
                            <small class="price-label">Fetching Price</small>
                        </div>
                    `;
                }
                
                // Fallback for unknown pricing
                return `
                    <div class="price-display">
                        <span class="price-amount">₱ Consult</span>
                        <small class="price-label">For Pricing</small>
                    </div>
                `;
            }

            renderAppointmentRow(appointment) {
                const isUpcoming = new Date(appointment.appointment_date) >= new Date();
                const canCancel = appointment.status === 'pending' && isUpcoming;
                
                // Check if this appointment is a referral - ONLY highlight appointments with 'referred' status
                const isReferredAppointment = appointment.status === 'referred';
                // Check if appointment has referral info for display purposes (but not highlighting)
                const hasReferralInfo = appointment.referral_info;
                
                // Get treatment name and duration info
                const treatmentName = appointment.treatment_name || 'General Consultation';
                const duration = appointment.duration_minutes ? this.formatDuration(appointment.duration_minutes) : '';
                
                // Get branch information
                const branchName = appointment.branch_name || 'Unknown Branch';
                const branchLocation = appointment.branch_address || '';
                
                // Build price display
                const priceDisplay = this.buildAppointmentPriceDisplay(appointment);
                
                return `
                    <tr${isReferredAppointment ? ' style="background: linear-gradient(90deg, #fff3cd 0%, #ffffff 100%);"' : ''}>
                        <td>
                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                <strong>${this.formatDate(appointment.appointment_date)}</strong>
                                <span style="color: var(--secondary-500); font-size: 0.875rem;">
                                    <i class="fas fa-clock"></i> ${this.formatTime(appointment.appointment_time)}
                                </span>
                                <small style="color: var(--secondary-400); font-size: 0.70rem;">
                                    <i class="fas fa-calendar-plus"></i> Created: ${this.formatDate(appointment.created_at)}
                                </small>
                            </div>
                        </td>
                        <td>
                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                <strong style="color: var(--primary-600);">
                                    <i class="fas fa-tooth"></i> ${treatmentName}
                                </strong>
                                ${duration ? `<small style="color: var(--secondary-500);"><i class="fas fa-hourglass-half"></i> Duration: ${duration}</small>` : ''}
                                <small style="color: var(--secondary-400); font-size: 0.75rem;">
                                    <i class="fas fa-clipboard-list"></i> Service Type: ${appointment.service_category || 'General Dentistry'}
                                </small>
                            </div>
                        </td>
                        <td>
                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                <strong style="color: var(--info-600);">
                                    <i class="fas fa-hospital"></i> ${branchName}
                                </strong>
                                ${branchLocation ? `<small style="color: var(--secondary-500);"><i class="fas fa-map-marker-alt"></i> ${branchLocation}</small>` : ''}
                                ${hasReferralInfo ? `<small style="color: #856404;"><i class="fas fa-exchange-alt"></i> ${this.getReferralInfo(appointment)}</small>` : ''}
                            </div>
                        </td>
                        <td>
                            ${priceDisplay}
                        </td>
                        <td>
                            <span class="status-badge ${appointment.status}">
                                ${this.getStatusIcon(appointment.status)} ${appointment.status}
                            </span>
                        </td>
                        <td>
                            <div style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;">
                                ${appointment.notes || '<em style="color: var(--secondary-400);">No notes</em>'}
                                ${appointment.referral_reason ? `<br><small style="color: #856404;"><strong>Referral reason:</strong> ${appointment.referral_reason}</small>` : ''}
                            </div>
                        </td>
                        <td>
                            <div class="action-group">
                                ${appointment.status === 'completed' ? 
                                    `<button class="btn btn-info btn-small" onclick="dashboard.viewPrescriptions(${appointment.id})" style="margin-right: 8px;">
                                        <i class="fas fa-prescription"></i> Prescriptions
                                    </button>
                                    <button class="btn btn-success btn-small" onclick="dashboard.viewInvoice(${appointment.id}, 'Treatment Invoice')" style="margin-right: 8px;">
                                        <i class="fas fa-file-invoice"></i> Invoice
                                    </button>` : 
                                    ''
                                }
                                ${canCancel ? 
                                    `<button class="btn btn-danger btn-small" onclick="dashboard.cancelAppointment(${appointment.id})">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>` : 
                                    `<span style="color: var(--secondary-400); font-size: 0.75rem;">
                                        <i class="fas fa-info-circle"></i> ${this.getActionMessage(appointment.status, isUpcoming)}
                                    </span>`
                                }
                            </div>
                        </td>
                    </tr>
                `;
            }
            
            displayEmptyState() {
                const container = document.getElementById('appointments-list');
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-calendar-times"></i>
                        <h4>No appointments found</h4>
                        <p>Start your dental care journey by booking your first appointment!</p>
                        <a href="#book-appointment" class="quick-action-btn" style="margin-top: 16px;">
                            <i class="fas fa-calendar-plus"></i>
                            Book Your First Appointment
                        </a>
                    </div>
                `;
            }
            
            getStatusIcon(status) {
                const icons = {
                    pending: '<i class="fas fa-clock"></i>',
                    approved: '<i class="fas fa-check-circle"></i>',
                    cancelled: '<i class="fas fa-times-circle"></i>',
                    completed: '<i class="fas fa-check-double"></i>',
                    referred: '<i class="fas fa-exchange-alt"></i>'
                };
                return icons[status] || '<i class="fas fa-question-circle"></i>';
            }
            
            getReferralInfo(appointment) {
                if (appointment.status === 'referred') {
                    return `Referred to ${appointment.referred_to_branch || 'another branch'}`;
                }
                if (appointment.referral_info) {
                    return `From ${appointment.referral_info.from_branch}`;
                }
                return 'Referral';
            }
            
            getActionMessage(status, isUpcoming) {
                if (status === 'cancelled') return 'Cancelled';
                if (status === 'completed') return 'Completed';
                if (status === 'referred') return 'Referred to another branch';
                if (!isUpcoming) return 'Past appointment';
                if (status === 'approved') return 'Approved';
                return 'No actions';
            }
            
            formatDate(dateString) {
                const date = new Date(dateString);
                return date.toLocaleDateString('en-US', {
                    weekday: 'short',
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric'
                });
            }
            
            formatTime(timeString) {
                const [hours, minutes] = timeString.split(':');
                const hour = parseInt(hours);
                const ampm = hour >= 12 ? 'PM' : 'AM';
                const displayHour = hour % 12 || 12;
                return `${displayHour}:${minutes} ${ampm}`;
            }
            
            handleAppointmentSubmission(e) {
                e.preventDefault();
                
                const form = e.target;
                const formData = new FormData(form);
                const submitBtn = form.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                
                // Show loading state
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<div class="spinner"></div> Booking appointment...';
                
                fetch('../../src/controllers/AppointmentController.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    // Check if response is actually JSON
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        // Response is not JSON, likely a PHP error
                        return response.text().then(text => {
                            console.error('Server returned non-JSON response:', text);
                            throw new Error('Server error: The system encountered an internal error. Please try again or contact support.');
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Show success message with appointment tracking info
                        this.showAlert('success', `🎉 Appointment booked successfully! 
                            <br>📋 <strong>Appointment #${data.appointment_id}</strong> created with tracking
                            <br>📍 Your appointment journey has been recorded for future reference
                            <br>🔄 We'll review and confirm shortly.`);
                        
                        form.reset();
                        this.loadAppointments();
                        
                        // Scroll to appointments section to show the new appointment with tracking
                        setTimeout(() => {
                            this.navigateToSection('appointments');
                        }, 1500);
                    } else {
                        this.showAlert('error', data.message || 'Failed to book appointment');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    this.showAlert('error', 'Network error. Please check your connection and try again.');
                })
                .finally(() => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                });
            }
            
            // Helper function to view prescriptions for an appointment
            viewPrescriptions(appointmentId) {
                if (!appointmentId) {
                    this.showAlert('error', 'Invalid appointment ID');
                    return;
                }
                
                // Show loading indicator
                const modal = this.createPrescriptionModal();
                document.body.appendChild(modal);
                
                // Fetch prescriptions for this appointment
                fetch(`../api/prescriptions.php?action=getPrescriptions&appointment_id=${appointmentId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.displayPrescriptions(data.prescriptions, appointmentId);
                    } else {
                        this.showAlert('error', data.message || 'Failed to load prescriptions');
                        modal.remove();
                    }
                })
                .catch(error => {
                    console.error('Error loading prescriptions:', error);
                    this.showAlert('error', 'Failed to load prescriptions');
                    modal.remove();
                });
            }
            
            // Create prescription modal structure
            createPrescriptionModal() {
                const modal = document.createElement('div');
                modal.id = 'prescription-modal';
                modal.style.cssText = `
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.7);
                    z-index: 1000;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    animation: fadeIn 0.3s ease-out;
                `;
                
                modal.innerHTML = `
                    <div style="
                        background: white;
                        border-radius: 12px;
                        max-width: 900px;
                        max-height: 90vh;
                        width: 90%;
                        overflow-y: auto;
                        position: relative;
                        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
                        animation: slideInUp 0.3s ease-out;
                    ">
                        <div style="
                            position: sticky;
                            top: 0;
                            background: linear-gradient(135deg, #10b981, #059669);
                            color: white;
                            padding: 20px;
                            border-radius: 12px 12px 0 0;
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                            z-index: 10;
                        ">
                            <h3 style="margin: 0; font-size: 1.5rem; display: flex; align-items: center; gap: 10px;">
                                <i class="fas fa-prescription"></i> Prescriptions
                            </h3>
                            <button onclick="this.closest('#prescription-modal').remove()" style="
                                background: rgba(255, 255, 255, 0.2);
                                border: none;
                                color: white;
                                width: 35px;
                                height: 35px;
                                border-radius: 50%;
                                cursor: pointer;
                                font-size: 18px;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                transition: background 0.3s;
                            " onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">&times;</button>
                        </div>
                        <div id="prescription-content" style="padding: 20px;">
                            <div style="text-align: center; padding: 40px;">
                                <div style="font-size: 2rem; margin-bottom: 15px;">⏳</div>
                                <p>Loading prescriptions...</p>
                            </div>
                        </div>
                    </div>
                `;
                
                // Close modal when clicking outside
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) {
                        modal.remove();
                    }
                });
                
                return modal;
            }
            
            // Display prescriptions in modal
            displayPrescriptions(prescriptions, appointmentId) {
                const content = document.getElementById('prescription-content');
                
                if (!prescriptions || prescriptions.length === 0) {
                    content.innerHTML = `
                        <div style="text-align: center; padding: 40px; color: #666;">
                            <div style="font-size: 3rem; margin-bottom: 15px;">�</div>
                            <h4 style="color: #374151; margin-bottom: 10px;">No Prescriptions Available</h4>
                            <p style="color: #6b7280;">No prescriptions have been issued for this appointment yet.</p>
                        </div>
                    `;
                    return;
                }
                
                let html = '';
                
                prescriptions.forEach((prescription, index) => {
                    const prescriptionDate = prescription.prescription_date ? new Date(prescription.prescription_date).toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    }) : 'N/A';
                    
                    const followUpDate = prescription.follow_up_date ? new Date(prescription.follow_up_date).toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    }) : null;
                    
                    html += `
                        <div style="
                            background: linear-gradient(135deg, #f0fdf4, #dcfce7);
                            border: 2px solid #86efac;
                            border-radius: 12px;
                            padding: 20px;
                            margin-bottom: ${index < prescriptions.length - 1 ? '20px' : '0'};
                        ">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                                <div>
                                    <h4 style="color: #065f46; margin: 0 0 8px 0; font-size: 1.1rem;">
                                        <i class="fas fa-prescription-bottle-alt"></i> Prescription #${prescription.id}
                                    </h4>
                                    <p style="color: #047857; margin: 0; font-size: 0.9rem;">
                                        <i class="fas fa-calendar"></i> ${prescriptionDate}
                                    </p>
                                </div>
                                <span style="
                                    background: #10b981;
                                    color: white;
                                    padding: 4px 12px;
                                    border-radius: 20px;
                                    font-size: 0.75rem;
                                    font-weight: 600;
                                    text-transform: uppercase;
                                ">${prescription.status}</span>
                            </div>
                            
                            <div style="background: white; border-radius: 8px; padding: 15px; margin-bottom: 15px;">
                                <h5 style="color: #374151; margin: 0 0 10px 0; display: flex; align-items: center; gap: 8px;">
                                    <i class="fas fa-stethoscope"></i> Diagnosis
                                </h5>
                                <p style="color: #6b7280; margin: 0; line-height: 1.6;">${prescription.diagnosis || 'Not specified'}</p>
                            </div>
                            
                            ${prescription.instructions ? `
                                <div style="background: white; border-radius: 8px; padding: 15px; margin-bottom: 15px;">
                                    <h5 style="color: #374151; margin: 0 0 10px 0; display: flex; align-items: center; gap: 8px;">
                                        <i class="fas fa-notes-medical"></i> Instructions
                                    </h5>
                                    <p style="color: #6b7280; margin: 0; line-height: 1.6;">${prescription.instructions}</p>
                                </div>
                            ` : ''}
                            
                            <div style="background: white; border-radius: 8px; padding: 15px; margin-bottom: 15px;">
                                <h5 style="color: #374151; margin: 0 0 15px 0; display: flex; align-items: center; gap: 8px;">
                                    <i class="fas fa-pills"></i> Medications
                                </h5>
                                ${prescription.medications && prescription.medications.length > 0 ? `
                                    <div style="display: flex; flex-direction: column; gap: 12px;">
                                        ${prescription.medications.map(med => `
                                            <div style="
                                                background: #f9fafb;
                                                border-left: 4px solid #10b981;
                                                padding: 12px;
                                                border-radius: 6px;
                                            ">
                                                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 8px;">
                                                    <strong style="color: #065f46; font-size: 1rem;">${med.medication_name}</strong>
                                                    ${med.is_priority ? '<span style="background: #fef3c7; color: #92400e; padding: 2px 8px; border-radius: 12px; font-size: 0.7rem; font-weight: 600;">PRIORITY</span>' : ''}
                                                </div>
                                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 8px; color: #6b7280; font-size: 0.85rem;">
                                                    <div><i class="fas fa-pills" style="color: #10b981; width: 16px;"></i> <strong>Dosage:</strong> ${med.dosage}</div>
                                                    <div><i class="fas fa-capsules" style="color: #10b981; width: 16px;"></i> <strong>Form:</strong> ${med.form || 'N/A'}</div>
                                                    <div><i class="fas fa-clock" style="color: #10b981; width: 16px;"></i> <strong>Frequency:</strong> ${med.frequency}</div>
                                                    <div><i class="fas fa-calendar-day" style="color: #10b981; width: 16px;"></i> <strong>Duration:</strong> ${med.duration}</div>
                                                    ${med.quantity ? `<div><i class="fas fa-hashtag" style="color: #10b981; width: 16px;"></i> <strong>Quantity:</strong> ${med.quantity}</div>` : ''}
                                                    ${med.with_food !== null ? `<div><i class="fas fa-utensils" style="color: #10b981; width: 16px;"></i> <strong>With food:</strong> ${med.with_food ? 'Yes' : 'No'}</div>` : ''}
                                                </div>
                                                ${med.instructions ? `
                                                    <div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #e5e7eb;">
                                                        <i class="fas fa-info-circle" style="color: #3b82f6;"></i> ${med.instructions}
                                                    </div>
                                                ` : ''}
                                            </div>
                                        `).join('')}
                                    </div>
                                ` : '<p style="color: #9ca3af; font-style: italic;">No medications prescribed</p>'}
                            </div>
                            
                            <div style="background: white; border-radius: 8px; padding: 15px;">
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
                                    <div>
                                        <p style="color: #6b7280; margin: 0 0 4px 0; font-size: 0.85rem;">
                                            <i class="fas fa-user-md"></i> Prescribed by
                                        </p>
                                        <p style="color: #374151; margin: 0; font-weight: 600;">${prescription.dentist_name}</p>
                                    </div>
                                    ${prescription.treatment_name ? `
                                        <div>
                                            <p style="color: #6b7280; margin: 0 0 4px 0; font-size: 0.85rem;">
                                                <i class="fas fa-tooth"></i> Treatment
                                            </p>
                                            <p style="color: #374151; margin: 0; font-weight: 600;">${prescription.treatment_name}</p>
                                        </div>
                                    ` : ''}
                                    ${prescription.follow_up_required ? `
                                        <div>
                                            <p style="color: #6b7280; margin: 0 0 4px 0; font-size: 0.85rem;">
                                                <i class="fas fa-calendar-check"></i> Follow-up Date
                                            </p>
                                            <p style="color: #dc2626; margin: 0; font-weight: 600;">${followUpDate || 'To be scheduled'}</p>
                                        </div>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                content.innerHTML = html;
            }
            
            // View appointment history with beautiful timeline
            viewAppointmentHistory(appointmentId) {
                if (!appointmentId) {
                    this.showAlert('error', 'Invalid appointment ID');
                    return;
                }
                
                // Show loading modal
                const modal = this.createHistoryModal();
                document.body.appendChild(modal);
                
                // Fetch appointment history
                fetch(`../../src/controllers/AppointmentController.php?action=getAppointmentHistory&appointment_id=${appointmentId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.displayAppointmentHistory(data.history, appointmentId);
                    } else {
                        this.showAlert('error', data.message || 'Failed to load appointment history');
                        modal.remove();
                    }
                })
                .catch(error => {
                    console.error('Error loading appointment history:', error);
                    this.showAlert('error', 'Failed to load appointment history');
                    modal.remove();
                });
            }
            
            // Create history modal structure
            createHistoryModal() {
                const modal = document.createElement('div');
                modal.id = 'appointment-history-modal';
                modal.style.cssText = `
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.7);
                    z-index: 1000;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    animation: fadeIn 0.3s ease-out;
                `;
                
                modal.innerHTML = `
                    <div style="
                        background: white;
                        border-radius: 12px;
                        max-width: 800px;
                        max-height: 90vh;
                        width: 90%;
                        overflow-y: auto;
                        position: relative;
                        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
                        animation: slideInUp 0.3s ease-out;
                    ">
                        <div style="
                            position: sticky;
                            top: 0;
                            background: linear-gradient(135deg, #667eea, #764ba2);
                            color: white;
                            padding: 20px;
                            border-radius: 12px 12px 0 0;
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                        ">
                            <h3 style="margin: 0; font-size: 1.5rem;">
                                📋 Appointment Journey Tracker
                            </h3>
                            <button onclick="this.closest('#appointment-history-modal').remove()" style="
                                background: rgba(255, 255, 255, 0.2);
                                border: none;
                                color: white;
                                width: 35px;
                                height: 35px;
                                border-radius: 50%;
                                cursor: pointer;
                                font-size: 18px;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                            ">&times;</button>
                        </div>
                        <div id="history-content" style="padding: 20px;">
                            <div style="text-align: center; padding: 40px;">
                                <div style="font-size: 2rem; margin-bottom: 15px;">⏳</div>
                                <p>Loading appointment history...</p>
                            </div>
                        </div>
                    </div>
                `;
                
                // Close modal when clicking outside
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) {
                        modal.remove();
                    }
                });
                
                return modal;
            }
            
            // Display appointment history timeline
            displayAppointmentHistory(history, appointmentId) {
                const content = document.getElementById('history-content');
                
                if (!history || history.length === 0) {
                    content.innerHTML = `
                        <div style="text-align: center; padding: 40px; color: #666;">
                            <div style="font-size: 3rem; margin-bottom: 15px;">📋</div>
                            <h4>No History Available</h4>
                            <p>This appointment doesn't have tracking history yet.</p>
                        </div>
                    `;
                    return;
                }
                
                const timelineHtml = history.map((entry, index) => {
                    const isFirst = index === 0;
                    const isLast = index === history.length - 1;
                    
                    // Event type styling
                    const eventConfig = {
                        'created': { icon: '🆕', color: '#28a745', bg: '#d4edda', title: 'Appointment Created' },
                        'referred': { icon: '🔄', color: '#fd7e14', bg: '#fff3cd', title: 'Referred to Another Branch' },
                        'treatment_changed': { icon: '💊', color: '#6f42c1', bg: '#e2e3f0', title: 'Treatment Modified' },
                        'branch_changed': { icon: '🏥', color: '#17a2b8', bg: '#d1ecf1', title: 'Branch Changed' },
                        'completed': { icon: '✅', color: '#28a745', bg: '#d4edda', title: 'Treatment Completed' },
                        'cancelled': { icon: '❌', color: '#dc3545', bg: '#f8d7da', title: 'Appointment Cancelled' }
                    };
                    
                    const config = eventConfig[entry.event_type] || { 
                        icon: '📌', color: '#6c757d', bg: '#f8f9fa', title: 'Update' 
                    };
                    
                    const formattedDate = new Date(entry.created_at).toLocaleDateString('en-US', {
                        year: 'numeric', month: 'short', day: 'numeric',
                        hour: '2-digit', minute: '2-digit'
                    });
                    
                    return `
                        <div style="
                            display: flex; 
                            margin-bottom: ${isLast ? '0' : '30px'}; 
                            position: relative;
                        ">
                            ${!isLast ? `<div style="
                                position: absolute;
                                left: 27px;
                                top: 54px;
                                width: 2px;
                                height: calc(100% + 15px);
                                background: linear-gradient(to bottom, ${config.color}, #e9ecef);
                                z-index: 1;
                            "></div>` : ''}
                            
                            <div style="
                                width: 54px;
                                height: 54px;
                                border-radius: 50%;
                                background: ${config.bg};
                                border: 3px solid ${config.color};
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                font-size: 1.5rem;
                                margin-right: 20px;
                                flex-shrink: 0;
                                z-index: 2;
                                position: relative;
                                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                            ">
                                ${config.icon}
                            </div>
                            
                            <div style="flex: 1; min-width: 0;">
                                <div style="
                                    background: ${config.bg};
                                    border: 1px solid ${config.color}33;
                                    border-radius: 12px;
                                    padding: 16px;
                                    position: relative;
                                ">
                                    <div style="
                                        position: absolute;
                                        left: -8px;
                                        top: 15px;
                                        width: 0;
                                        height: 0;
                                        border-top: 8px solid transparent;
                                        border-bottom: 8px solid transparent;
                                        border-right: 8px solid ${config.bg};
                                    "></div>
                                    
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                                        <h5 style="margin: 0; color: ${config.color}; font-size: 1.1rem;">
                                            ${config.title}
                                        </h5>
                                        <small style="color: #6c757d; font-weight: 500;">
                                            ${formattedDate}
                                        </small>
                                    </div>
                                    
                                    ${entry.event_description ? `
                                        <p style="margin: 8px 0; color: #333; font-size: 0.95rem;">
                                            ${entry.event_description}
                                        </p>
                                    ` : ''}
                                    
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 12px; font-size: 0.875rem;">
                                        <div>
                                            <strong style="color: ${config.color};">🏥 Branch:</strong><br>
                                            <span style="color: #495057;">${entry.branch_name || 'N/A'}</span>
                                        </div>
                                        <div>
                                            <strong style="color: ${config.color};">💊 Treatment:</strong><br>
                                            <span style="color: #495057;">${entry.treatment_name || 'N/A'}</span>
                                        </div>
                                        <div>
                                            <strong style="color: ${config.color};">💰 Price:</strong><br>
                                            <span style="color: #495057;">
                                                ${entry.treatment_price ? '₱' + parseFloat(entry.treatment_price).toLocaleString() : 'Consult'}
                                            </span>
                                        </div>
                                        <div>
                                            <strong style="color: ${config.color};">📅 Date:</strong><br>
                                            <span style="color: #495057;">
                                                ${entry.appointment_date ? new Date(entry.appointment_date).toLocaleDateString() : 'TBD'}
                                                ${entry.appointment_time ? 'at ' + entry.appointment_time : ''}
                                            </span>
                                        </div>
                                    </div>
                                    
                                    ${entry.referring_staff_name || entry.referral_reason ? `
                                        <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid ${config.color}33;">
                                            ${entry.referring_staff_name ? `
                                                <small style="color: #6c757d;">
                                                    <strong>👤 Staff:</strong> ${entry.referring_staff_name}
                                                </small><br>
                                            ` : ''}
                                            ${entry.referral_reason ? `
                                                <small style="color: #6c757d;">
                                                    <strong>📝 Reason:</strong> ${entry.referral_reason}
                                                </small>
                                            ` : ''}
                                        </div>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    `;
                }).join('');
                
                content.innerHTML = `
                    <div style="margin-bottom: 20px;">
                        <div style="background: linear-gradient(135deg, #f8f9fa, #e9ecef); padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                            <h5 style="margin: 0; color: #495057;">
                                📊 Appointment #${appointmentId} Journey Summary
                            </h5>
                            <p style="margin: 8px 0 0 0; color: #6c757d; font-size: 0.9rem;">
                                This timeline shows the complete history of your appointment, including any referrals and changes.
                            </p>
                        </div>
                    </div>
                    <div style="position: relative;">
                        ${timelineHtml}
                    </div>
                    <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #dee2e6;">
                        <button onclick="this.closest('#appointment-history-modal').remove()" style="
                            background: linear-gradient(135deg, #667eea, #764ba2);
                            color: white;
                            border: none;
                            padding: 12px 30px;
                            border-radius: 25px;
                            font-size: 1rem;
                            cursor: pointer;
                            transition: all 0.3s ease;
                        " onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                            ✨ Close History
                        </button>
                    </div>
                `;
            }
            
            cancelAppointment(appointmentId) {
                if (!confirm('⚠️ Are you sure you want to cancel this appointment?\n\nThis action cannot be undone.')) {
                    return;
                }
                
                const formData = new FormData();
                formData.append('action', 'cancel');
                formData.append('appointment_id', appointmentId);
                formData.append('reason', 'Cancelled by patient');
                
                fetch('../../src/controllers/AppointmentController.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.showAlert('success', '✅ Appointment cancelled successfully');
                        this.loadAppointments();
                    } else {
                        this.showAlert('error', data.message || 'Failed to cancel appointment');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    this.showAlert('error', 'Network error. Please try again.');
                });
            }
            
            showAlert(type, message) {
                const alert = document.getElementById('alert');
                const icons = {
                    success: 'fas fa-check-circle',
                    error: 'fas fa-exclamation-triangle',
                    info: 'fas fa-info-circle'
                };
                
                alert.className = `alert alert-${type === 'error' ? 'danger' : type}`;
                alert.innerHTML = `
                    <i class="${icons[type]}"></i>
                    ${message}
                `;
                alert.style.display = 'block';
                
                // Auto hide after 6 seconds
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 6000);
                
                // Scroll to alert
                alert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
            
            startPeriodicRefresh() {
                // Sync with notification system polling (every 30 seconds for full refresh)
                // Notifications poll every 5 seconds and trigger refreshes when needed
                setInterval(() => {
                    // Only do full refresh if notification system didn't recently refresh
                    const timeSinceLastNotification = Date.now() - (this.lastNotificationRefresh || 0);
                    if (timeSinceLastNotification > 25000) { // If no notification refresh in last 25 seconds
                        this.loadAppointments();
                        this.loadReferralStatus();
                    } else {
                        }
                }, 30000);
                
                // Track when notification system triggers refreshes
                this.lastNotificationRefresh = Date.now();
            }
            
            // Method called by notification system when new notifications arrive
            onNotificationTriggeredRefresh() {
                this.lastNotificationRefresh = Date.now();
                // Show subtle visual feedback
                this.showAlert('info', '🔔 Dashboard updated with latest changes', 3000);
            }
            
            // Helper functions for referral status actions
            hideReferralStatus() {
                const referralSection = document.getElementById('referral-status');
                if (confirm('🗂️ Hide this referral status? You can refresh the page to see it again if needed.')) {
                    referralSection.style.display = 'none';
                    this.showAlert('info', 'Referral status hidden. Refresh page to show again.');
                }
            }
            
            showCompletionNotification(referral) {
                // Show a prominent notification for completed referrals
                this.showAlert('success', '🎉 Great news! Your referral treatment has been completed successfully!');
                
                // Auto-hide after 15 seconds with countdown
                let countdown = 15;
                const countdownElement = document.createElement('div');
                countdownElement.id = 'completion-countdown';
                countdownElement.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: linear-gradient(135deg, #28a745, #20c997);
                    color: white;
                    padding: 15px 20px;
                    border-radius: 10px;
                    box-shadow: 0 4px 20px rgba(40, 167, 69, 0.3);
                    z-index: 1000;
                    font-weight: bold;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    animation: slideInRight 0.5s ease-out;
                `;
                
                const updateCountdown = () => {
                    countdownElement.innerHTML = `
                        <i class="fas fa-check-circle"></i>
                        <span>Referral completed! Auto-hiding in ${countdown}s</span>
                        <button onclick="dashboard.cancelAutoHide()" style="
                            background: rgba(255,255,255,0.2);
                            border: none;
                            color: white;
                            padding: 5px 10px;
                            border-radius: 5px;
                            cursor: pointer;
                            margin-left: 10px;
                        ">Keep Visible</button>
                    `;
                };
                
                document.body.appendChild(countdownElement);
                updateCountdown();
                
                const countdownInterval = setInterval(() => {
                    countdown--;
                    updateCountdown();
                    
                    if (countdown <= 0) {
                        clearInterval(countdownInterval);
                        this.autoHideCompleted(referral.id);
                        if (countdownElement.parentNode) {
                            countdownElement.remove();
                        }
                    }
                }, 1000);
                
                // Store the interval for potential cancellation
                this.autoHideInterval = countdownInterval;
                this.autoHideCountdownElement = countdownElement;
            }
            
            cancelAutoHide() {
                if (this.autoHideInterval) {
                    clearInterval(this.autoHideInterval);
                    this.autoHideInterval = null;
                }
                if (this.autoHideCountdownElement) {
                    this.autoHideCountdownElement.remove();
                    this.autoHideCountdownElement = null;
                }
                this.showAlert('info', 'Auto-hide cancelled. Referral status will remain visible.');
            }
            
            autoHideCompleted(referralId) {
                // Hide the referral from patient view after completion
                const formData = new FormData();
                formData.append('referral_id', referralId);
                
                fetch('../../src/controllers/ReferralController.php?action=hideCompletedReferral', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('referral-status').style.display = 'none';
                        this.showAlert('success', '✅ Completed referral automatically archived. Check for new referrals...');
                        
                        // Check for new/next referrals after hiding the completed one
                        setTimeout(() => {
                            this.loadReferralStatus();
                        }, 2000);
                    } else {
                        console.error('Failed to hide referral:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error hiding referral:', error);
                });
            }
            
            showDirections(branchName) {
                // This would typically integrate with a maps service
                const mapsUrl = `https://www.google.com/maps/search/${encodeURIComponent(branchName + ' dental clinic')}`;
                if (confirm(`🗺️ Open directions to ${branchName} in Google Maps?`)) {
                    window.open(mapsUrl, '_blank');
                }
            }
            
            addToCalendar(appointmentDate, appointmentTime, branchName) {
                try {
                    // Create calendar event details
                    const startDate = new Date(`${appointmentDate}T${appointmentTime}`);
                    const endDate = new Date(startDate.getTime() + 60 * 60 * 1000); // 1 hour duration
                    
                    // Format for calendar URL
                    const formatDate = (date) => {
                        return date.toISOString().replace(/[-:]/g, '').split('.')[0] + 'Z';
                    };
                    
                    const eventDetails = {
                        title: `Dental Appointment at ${branchName}`,
                        start: formatDate(startDate),
                        end: formatDate(endDate),
                        details: `Referral appointment at ${branchName}. Please arrive 15 minutes early.`,
                        location: branchName
                    };
                    
                    // Create Google Calendar URL
                    const calendarUrl = `https://calendar.google.com/calendar/render?action=TEMPLATE&text=${encodeURIComponent(eventDetails.title)}&dates=${eventDetails.start}/${eventDetails.end}&details=${encodeURIComponent(eventDetails.details)}&location=${encodeURIComponent(eventDetails.location)}`;
                    
                    window.open(calendarUrl, '_blank');
                    this.showAlert('success', '📅 Calendar event created! Check your calendar app.');
                    
                } catch (error) {
                    console.error('Error creating calendar event:', error);
                    this.showAlert('error', 'Unable to create calendar event. Please add manually.');
                }
            }

            // Handle clinic selection from URL parameters and session storage
            handleClinicSelection() {
                const urlParams = new URLSearchParams(window.location.search);
                const clinic = urlParams.get('clinic');
                const branchId = urlParams.get('branch_id');
                const action = urlParams.get('action');
                
                // Clean up any conflicting or outdated branch data when handling new clinic selection
                if ((clinic || branchId) && action === 'book') {
                    // Clear any stored clinic/branch data before setting new ones
                    localStorage.removeItem('selectedClinic');
                    localStorage.removeItem('selectedBranch');
                    localStorage.removeItem('selectedBranchId');
                    // Keep sessionStorage for current session but clear any conflicting data
                    const existingClinic = sessionStorage.getItem('selectedClinic');
                    if (existingClinic) {
                        try {
                            const existingData = JSON.parse(existingClinic);
                            // If the new selection is different from existing, clear the old data
                            if (existingData.id !== branchId && existingData.id !== clinicData[clinic]?.id) {
                                sessionStorage.removeItem('selectedClinic');
                            }
                        } catch (e) {
                            // Clear corrupted data
                            sessionStorage.removeItem('selectedClinic');
                        }
                    }
                }
                
                // Check if there's a clinic selection from URL (Book Now flow)
                if ((clinic || branchId) && action === 'book') {
                    let clinicId = null;
                    let clinicName = null;
                    let clinicLocation = null;
                    
                    // If branch_id is provided directly, use it
                    if (branchId) {
                        clinicId = branchId;
                        // Map clinic IDs to names and locations
                        const clinicInfo = {
                            '1': { name: 'Happy Teeth Dental Center', location: 'Zone 2, Talisay City' },
                            '2': { name: 'Ardent Dental Clinic', location: 'Bonifacio, Silay City' },
                            '3': { name: 'Gamboa Dental Clinic', location: 'Poblacion I, E.B. Magalona' }
                        };
                        
                        if (clinicInfo[clinicId]) {
                            clinicName = clinicInfo[clinicId].name;
                            clinicLocation = clinicInfo[clinicId].location;
                        }
                    } else {
                        // Map clinic names to IDs (fallback for old URLs)
                        const clinicMap = {
                            'ardent': { id: '2', name: 'Ardent Dental Clinic', location: 'Bonifacio, Silay City' },
                            'gamboa': { id: '3', name: 'Gamboa Dental Clinic', location: 'Poblacion I, E.B. Magalona' },
                            'happy-teeth': { id: '1', name: 'Happy Teeth Dental Center', location: 'Zone 2, Talisay City' },
                            'happy-teeth-dental': { id: '1', name: 'Happy Teeth Dental Center', location: 'Zone 2, Talisay City' }
                        };
                        
                        if (clinicMap[clinic]) {
                            clinicId = clinicMap[clinic].id;
                            clinicName = clinicMap[clinic].name;
                            clinicLocation = clinicMap[clinic].location;
                            }
                    }
                    
                    if (clinicId && clinicName) {
                        // Store the selected clinic in session storage for persistence
                        const selectedClinicData = {
                            id: clinicId,
                            name: clinicName,
                            location: clinicLocation,
                            timestamp: Date.now()
                        };
                        
                        sessionStorage.setItem('selectedClinic', JSON.stringify(selectedClinicData));
                        // Store the selected clinic ID for time slot loading
                        this.selectedClinicId = clinicId;
                        // Update the hidden clinic_id field
                        const hiddenClinicField = document.getElementById('clinic_id');
                        if (hiddenClinicField) {
                            hiddenClinicField.value = clinicId;
                        }
                        
                        // Show and update the clinic info display
                        this.updateSelectedClinicDisplay(clinicName, clinicLocation);
                        
                        // Update dashboard to show clinic-specific data
                        this.updateDashboardForSelectedClinic(selectedClinicData);
                        
                        // Auto-load treatments for the selected clinic
                        setTimeout(() => {
                            this.loadTreatmentsForClinic(clinicId);
                            
                            // Show success message
                            this.showAlert('success', `🏥 ${clinicName} selected for booking!`);
                            
                            // Navigate to book appointment section
                            this.navigateToSection('book-appointment');
                        }, 500);
                        
                        // Clean up URL
                        const newUrl = window.location.pathname;
                        window.history.replaceState({}, document.title, newUrl);
                    }
                } else {
                    // Check if there's a previously selected clinic in session storage
                    this.loadClinicFromSessionStorage();
                }
            }
            
            // New method to update clinic display
            updateSelectedClinicDisplay(clinicName, clinicLocation) {
                // Update the selected clinic info card
                const clinicInfoDiv = document.getElementById('selected-clinic-info');
                const clinicNameEl = document.getElementById('selected-clinic-name');
                const clinicLocationEl = document.getElementById('selected-clinic-location');
                
                if (clinicInfoDiv && clinicNameEl && clinicLocationEl) {
                    clinicNameEl.textContent = clinicName;
                    clinicLocationEl.textContent = clinicLocation;
                    clinicInfoDiv.style.display = 'block';
                    
                    // Add entrance animation
                    clinicInfoDiv.style.opacity = '0';
                    clinicInfoDiv.style.transform = 'translateY(-10px)';
                    
                    setTimeout(() => {
                        clinicInfoDiv.style.transition = 'all 0.3s ease';
                        clinicInfoDiv.style.opacity = '1';
                        clinicInfoDiv.style.transform = 'translateY(0)';
                    }, 100);
                }
                
                // Update navbar branding to reflect selected clinic
                this.updateNavbarBranding(clinicName);
            }
            
            // New method to update navbar branding based on selected clinic
            updateNavbarBranding(clinicName) {
                const navbarLogoImg = document.getElementById('navbar-logo-img');
                const navbarClinicNameEl = document.getElementById('navbar-clinic-name');
                
                if (!navbarLogoImg || !navbarClinicNameEl) {
                    console.error('❌ Navbar elements not found!');
                    return;
                }
                
                // Map clinic names to their logos and display names
                const clinicBranding = {
                    'Happy Teeth Dental Center': {
                        logo: '../../assets/images/happy-teeth-dental.png',
                        displayName: 'Happy Teeth Dental Center',
                        alt: 'Happy Teeth Dental Clinic'
                    },
                    'Ardent Dental Clinic': {
                        logo: '../../assets/images/ardent-dental.png',
                        displayName: 'Ardent Dental Clinic',
                        alt: 'Ardent Dental Clinic'
                    },
                    'Gamboa Dental Clinic': {
                        logo: '../../assets/images/gamboa-dental.png',
                        displayName: 'Gamboa Dental Clinic',
                        alt: 'Gamboa Dental Clinic'
                    }
                };
                
                const branding = clinicBranding[clinicName];
                if (branding) {
                    // Store original values for comparison
                    const originalSrc = navbarLogoImg.src;
                    const originalName = navbarClinicNameEl.innerHTML;
                    
                    // Update logo with smooth transition
                    navbarLogoImg.style.opacity = '0.5';
                    setTimeout(() => {
                        navbarLogoImg.src = branding.logo;
                        navbarLogoImg.alt = branding.alt;
                        navbarClinicNameEl.innerHTML = branding.displayName;
                        
                        // Add selection indicator
                        navbarClinicNameEl.style.color = 'var(--primary-600)';
                        navbarClinicNameEl.style.fontWeight = '600';
                        
                        // Restore opacity with transition
                        navbarLogoImg.style.transition = 'opacity 0.3s ease';
                        navbarLogoImg.style.opacity = '1';
                        
                        // Verify the change took effect
                        setTimeout(() => {
                            if (!navbarLogoImg.src.includes(branding.logo.split('/').pop().split('.')[0])) {
                                console.error('⚠️ Logo change may not have taken effect!');
                            }
                        }, 100);
                        
                    }, 150);
                } else {
                    console.error('❌ No branding configuration found for clinic:', clinicName);
                    }
            }
            
            // Get selected clinic from session storage
            getSelectedClinicFromStorage() {
                try {
                    const storedClinic = sessionStorage.getItem('selectedClinic');
                    if (storedClinic) {
                        const clinicData = JSON.parse(storedClinic);
                        
                        // Check if the stored data is not too old (24 hours)
                        const ageInHours = (Date.now() - clinicData.timestamp) / (1000 * 60 * 60);
                        if (ageInHours <= 24) {
                            return clinicData;
                        } else {
                            sessionStorage.removeItem('selectedClinic');
                        }
                    }
                } catch (error) {
                    console.error('❌ Error getting clinic from session storage:', error);
                    sessionStorage.removeItem('selectedClinic');
                }
                return null;
            }
            
            // Load clinic from session storage if available
            loadClinicFromSessionStorage() {
                try {
                    const storedClinic = sessionStorage.getItem('selectedClinic');
                    if (storedClinic) {
                        const clinicData = JSON.parse(storedClinic);
                        
                        // Check if the stored data is not too old (24 hours)
                        const ageInHours = (Date.now() - clinicData.timestamp) / (1000 * 60 * 60);
                        if (ageInHours > 24) {
                            sessionStorage.removeItem('selectedClinic');
                            this.setGeneralDashboardMode();
                            return;
                        }
                        
                        // Restore clinic selection
                        this.selectedClinicId = clinicData.id;
                        
                        // Update hidden field
                        const hiddenClinicField = document.getElementById('clinic_id');
                        if (hiddenClinicField) {
                            hiddenClinicField.value = clinicData.id;
                        }
                        
                        // Update display
                        this.updateSelectedClinicDisplay(clinicData.name, clinicData.location);
                        this.updateDashboardForSelectedClinic(clinicData);
                        
                        } else {
                        this.setGeneralDashboardMode();
                    }
                } catch (error) {
                    console.error('❌ Error loading from session storage:', error);
                    sessionStorage.removeItem('selectedClinic');
                    this.setGeneralDashboardMode();
                }
            }
            
            // Update dashboard to show clinic-specific data
            updateDashboardForSelectedClinic(clinicData) {
                // Update clinic hours section to show selected clinic's hours
                this.loadClinicHours(clinicData.id);
                
                // Update navbar branding
                this.updateNavbarBranding(clinicData.name);
                
                // Load treatments for the selected clinic
                this.loadTreatmentsForClinic(clinicData.id);
                
                // Update any clinic-specific UI elements
                this.updateClinicSpecificUI(clinicData);
            }
            
            // Set general dashboard mode (no specific clinic selected)
            setGeneralDashboardMode() {
                // Clear selected clinic
                this.selectedClinicId = null;
                
                // Hide clinic selection info
                const clinicInfoDiv = document.getElementById('selected-clinic-info');
                if (clinicInfoDiv) {
                    clinicInfoDiv.style.display = 'none';
                }
                
                // Set general navbar branding
                this.setGeneralNavbarBranding();
                
                // Load general treatments (all available)
                this.loadTreatmentTypes();
                
                // Set general clinic hours view
                this.setGeneralClinicHoursView();
                
                // Set general footer information
                this.setGeneralFooter();
                
                // Clear hidden clinic field
                const hiddenClinicField = document.getElementById('clinic_id');
                if (hiddenClinicField) {
                    hiddenClinicField.value = '';
                }
            }
            
            // Set general navbar branding (dental network logo)
            setGeneralNavbarBranding() {
                const navbarLogoImg = document.getElementById('navbar-logo-img');
                const navbarClinicNameEl = document.getElementById('navbar-clinic-name');
                
                if (navbarLogoImg && navbarClinicNameEl) {
                    // Use a general dental network logo/branding
                    navbarLogoImg.src = '../../assets/images/happy-teeth-dental.png'; // Default logo
                    navbarLogoImg.alt = 'Dental Network';
                    navbarClinicNameEl.innerHTML = 'Dental Clinic Network';
                    navbarClinicNameEl.style.color = '#ffffff';
                    navbarClinicNameEl.style.fontWeight = '500';
                    
                    }
            }
            
            // Update clinic-specific UI elements
            updateClinicSpecificUI(clinicData) {
                // Update footer with clinic information
                this.updateClinicFooter(clinicData);
                
                // Update any other clinic-specific UI elements here
                }
            
            // Update clinic information footer
            updateClinicFooter(clinicData) {
                const footerClinicName = document.getElementById('footer-clinic-name');
                const footerClinicLocation = document.getElementById('footer-clinic-location');
                const footerClinicContact = document.getElementById('footer-clinic-contact');
                const footerClinicStatus = document.getElementById('footer-clinic-status');
                const footerLogoImg = document.getElementById('footer-logo-img');
                const footerDynamicMessage = document.getElementById('footer-dynamic-message');
                
                // Clinic data mapping for contact information
                const clinicContactInfo = {
                    'Happy Teeth Dental Clinic': {
                        phone: '(034) 123-4567',
                        location: 'Talisay City, Negros Occidental',
                        logo: '../../assets/images/happy-teeth-dental.png'
                    },
                    'Gamboa Dental Clinic': {
                        phone: '(034) 234-5678', 
                        location: 'Silay City, Negros Occidental',
                        logo: '../../assets/images/gamboa-dental.png'
                    },
                    'Ardent Dental Care': {
                        phone: '(034) 345-6789',
                        location: 'Sarabia Street, Silay City',
                        logo: '../../assets/images/ardent-dental.png'
                    }
                };
                
                // Get clinic info or use defaults
                const clinicInfo = clinicContactInfo[clinicData.name] || {
                    phone: '(034) 456-7890',
                    location: clinicData.location || 'Bacolod City Area',
                    logo: '../../assets/images/happy-teeth-dental.png'
                };
                
                // Update footer elements
                if (footerClinicName) {
                    footerClinicName.textContent = clinicData.name;
                }
                
                if (footerClinicLocation) {
                    footerClinicLocation.textContent = clinicInfo.location;
                }
                
                if (footerClinicContact) {
                    footerClinicContact.innerHTML = `
                        <a href="tel:${clinicInfo.phone.replace(/\D/g, '')}" style="color: var(--primary-600); text-decoration: none;">
                            ${clinicInfo.phone}
                        </a>
                    `;
                }
                
                if (footerLogoImg) {
                    footerLogoImg.src = clinicInfo.logo;
                    footerLogoImg.alt = clinicData.name + ' Logo';
                }
                
                // Update status based on current time (simple logic)
                if (footerClinicStatus) {
                    const now = new Date();
                    const currentHour = now.getHours();
                    const isWeekend = now.getDay() === 0 || now.getDay() === 6;
                    
                    let statusText = '';
                    let statusColor = '';
                    
                    if (isWeekend) {
                        statusText = '🔴 Closed - Weekend';
                        statusColor = 'var(--error-600)';
                    } else if (currentHour >= 8 && currentHour < 17) {
                        statusText = '🟢 Open Today';
                        statusColor = 'var(--success-600)';
                    } else {
                        statusText = '🔴 Closed';
                        statusColor = 'var(--error-600)';
                    }
                    
                    footerClinicStatus.innerHTML = `<span style="color: ${statusColor};">${statusText}</span>`;
                }
                
                // Update dynamic message
                if (footerDynamicMessage) {
                    footerDynamicMessage.textContent = `Experience quality dental care at ${clinicData.name}. We're here to make your smile brighter!`;
                }
            }
            
            // Set general footer information
            setGeneralFooter() {
                const footerClinicName = document.getElementById('footer-clinic-name');
                const footerClinicLocation = document.getElementById('footer-clinic-location');
                const footerClinicContact = document.getElementById('footer-clinic-contact');
                const footerClinicStatus = document.getElementById('footer-clinic-status');
                const footerLogoImg = document.getElementById('footer-logo-img');
                const footerDynamicMessage = document.getElementById('footer-dynamic-message');
                
                if (footerClinicName) {
                    footerClinicName.textContent = 'Dental Clinic Network';
                }
                
                if (footerClinicLocation) {
                    footerClinicLocation.textContent = 'Serving Bacolod & Negros Occidental';
                }
                
                if (footerClinicContact) {
                    footerClinicContact.innerHTML = `
                        <span style="color: var(--secondary-600);">
                            Visit our clinic listings for contact details
                        </span>
                    `;
                }
                
                if (footerClinicStatus) {
                    footerClinicStatus.innerHTML = `
                        <span style="color: var(--primary-600);">
                            🏥 Multiple Locations Available
                        </span>
                    `;
                }
                
                if (footerLogoImg) {
                    footerLogoImg.src = '../../assets/images/happy-teeth-dental.png';
                    footerLogoImg.alt = 'Dental Network Logo';
                }
                
                if (footerDynamicMessage) {
                    footerDynamicMessage.textContent = 'Choose from our network of trusted dental clinics and book your appointment today!';
                }
            }
            
            // Set general clinic hours view
            setGeneralClinicHoursView() {
                const statusTitle = document.getElementById('clinic-status-title');
                const statusSubtitle = document.getElementById('clinic-status-subtitle');
                const hoursSummary = document.getElementById('hours-summary');
                
                if (statusTitle) {
                    statusTitle.innerHTML = 'Select a clinic to view operating hours';
                }
                
                if (statusSubtitle) {
                    statusSubtitle.textContent = 'Click "Book Now" from clinic listing to see specific hours';
                }
                
                if (hoursSummary) {
                    hoursSummary.innerHTML = `
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-info-circle" style="color: var(--primary-600);"></i>
                            <span style="color: var(--primary-700); font-weight: 500; font-size: 0.875rem;">
                                Choose a clinic from our network to see their specific operating hours and book appointments
                            </span>
                        </div>
                    `;
                }
            }
            
            // New method to restore user's home clinic branding
            restoreHomeBranding() {
                if (this.currentUser && this.currentUser.branch_name) {
                    this.updateNavbarBranding(this.currentUser.branch_name);
                }
            }
        }
        
        // Initialize dashboard
        let dashboard;
        document.addEventListener('DOMContentLoaded', () => {
            dashboard = new PatientDashboard();
        });
        
        // Global functions
        function goBackToClinicSelection() {
            if (confirm('🏥 Are you sure you want to go back to clinic selection? This will reset your current clinic data.')) {
                // Show loading state
                const backBtn = document.querySelector('.nav-link.back-btn');
                const originalText = backBtn.innerHTML;
                backBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Loading...</span>';
                backBtn.style.pointerEvents = 'none';
                
                // Clear any stored clinic/branch data
                localStorage.removeItem('selectedClinic');
                localStorage.removeItem('selectedBranch');
                localStorage.removeItem('selectedBranchId');
                sessionStorage.removeItem('selectedClinic');
                sessionStorage.removeItem('selectedBranch');
                sessionStorage.removeItem('selectedBranchId');
                
                // Add slight delay for better UX
                setTimeout(() => {
                    // Redirect to clinic listing page
                    window.location.href = 'clinic-listing.php';
                }, 800);
            }
        }
        
        function clearForm() {
            document.getElementById('appointmentForm').reset();
            dashboard.showAlert('info', 'Form cleared successfully');
        }
        
        function logout() {
            if (confirm('🚪 Are you sure you want to logout?')) {
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
        }
        
        // Global functions for clinic hours
        function toggleScheduleDetails() {
            const detailedSchedule = document.getElementById('detailed-schedule');
            const showSchedule = document.getElementById('show-schedule');
            const toggleBtn = document.getElementById('toggle-schedule-btn');
            const toggleBtnInDetails = document.getElementById('toggle-schedule');
            
            if (detailedSchedule.style.display === 'none' || !detailedSchedule.style.display) {
                // Show detailed schedule
                detailedSchedule.style.display = 'block';
                showSchedule.style.display = 'none';
            } else {
                // Hide detailed schedule
                detailedSchedule.style.display = 'none';
                showSchedule.style.display = 'block';
            }
        }

        // =================== NOTIFICATION SYSTEM FUNCTIONS ===================
        
        let notificationQueue = [];
        let isNotificationDropdownOpen = false;
        
        function showNotificationDropdown() {
            const dropdown = document.getElementById('notification-dropdown');
            const notificationBtn = document.querySelector('.nav-notification');
            
            if (!dropdown) {
                console.warn('Notification dropdown element not found');
                return;
            }
            
            const isOpen = dropdown.style.display === 'block';
            
            if (isOpen) {
                // Hide dropdown with fade out
                dropdown.style.opacity = '0';
                dropdown.style.transform = 'translateY(-10px)';
                if (notificationBtn) notificationBtn.classList.remove('active');
                setTimeout(() => {
                    dropdown.style.display = 'none';
                    isNotificationDropdownOpen = false;
                }, 200);
            } else {
                // Show dropdown with fade in
                dropdown.style.display = 'block';
                dropdown.style.opacity = '0';
                dropdown.style.transform = 'translateY(-10px)';
                if (notificationBtn) notificationBtn.classList.add('active');
                isNotificationDropdownOpen = true;
                
                // Force reflow and animate
                setTimeout(() => {
                    dropdown.style.opacity = '1';
                    dropdown.style.transform = 'translateY(0)';
                }, 10);
                
                // Update dropdown content
                updateNotificationDropdown();
            }
        }

        function hideNotificationDropdown() {
            const dropdown = document.getElementById('notification-dropdown');
            const notificationBtn = document.querySelector('.nav-notification');
            
            if (dropdown) {
                dropdown.style.opacity = '0';
                dropdown.style.transform = 'translateY(-10px)';
                setTimeout(() => {
                    dropdown.style.display = 'none';
                    isNotificationDropdownOpen = false;
                }, 200);
            }
            
            if (notificationBtn) {
                notificationBtn.classList.remove('active');
            }
        }

        function updateNotificationDropdown() {
            const listContainer = document.getElementById('notification-list');
            
            if (!listContainer) return;
            
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
                title = 'Appointment Update';
                message = `Your appointment status: ${appt.status}`;
            } else if (notification.type === 'referral') {
                const ref = notification.data;
                icon = '<i class="fas fa-exchange-alt"></i>';
                title = 'Referral Update';
                message = `Your referral status: ${ref.status}`;
            }

            return `
                <div class="notification-item ${unreadClass}" onclick="markNotificationAsRead('${notification.id}')">
                    <div class="notification-icon">${icon}</div>
                    <div class="notification-content">
                        <div class="notification-title">${title}</div>
                        <div class="notification-message">${message}</div>
                        <div class="notification-time">${timeAgo}</div>
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

        function markNotificationAsRead(notificationId) {
            const notification = notificationQueue.find(n => n.id == notificationId);
            if (notification) {
                notification.read = true;
                updateNotificationDisplay();
            }
        }

        function updateNotificationDisplay() {
            const unreadCount = notificationQueue.filter(n => !n.read).length;
            const badge = document.getElementById('notification-count');
            const bell = document.getElementById('notification-bell');

            // Update badge count
            if (badge) {
                badge.textContent = unreadCount;
                badge.style.display = unreadCount > 0 ? 'flex' : 'none';
            }

            // Add pulse effect for unread notifications
            if (bell) {
                if (unreadCount > 0) {
                    if (badge) badge.classList.add('pulse');
                    bell.style.color = '#f59e0b';
                } else {
                    if (badge) badge.classList.remove('pulse');
                    bell.style.color = '';
                }
            }

            // Update dropdown content
            updateNotificationDropdown();
        }

        function markAllAsRead() {
            notificationQueue.forEach(n => n.read = true);
            updateNotificationDisplay();
        }

        function refreshNotifications() {
            // In a real implementation, this would check for new notifications
            // For now, just provide visual feedback
            const refreshBtn = event.target.closest('.refresh-btn');
            if (refreshBtn) {
                refreshBtn.style.transform = 'rotate(360deg)';
                setTimeout(() => {
                    refreshBtn.style.transform = '';
                }, 300);
            }
            
            // Simulate refresh - in real app this would call API endpoints
            setTimeout(() => {
                }, 500);
        }

        function requestNotificationPermission() {
            // Check if notifications are supported
            if (!('Notification' in window)) {
                showNotificationAlert('Browser does not support notifications', 'warning');
                return;
            }

            // Check current permission status
            const permission = Notification.permission;

            if (permission === 'granted') {
                showNotificationAlert('Browser notifications are enabled!', 'success');
                updateNotificationButtonState();
                return;
            }

            if (permission === 'denied') {
                showNotificationAlert('Notifications were previously denied. Please enable them in browser settings.', 'warning');
                updateNotificationButtonState();
                return;
            }

            // Request permission (only works with user gesture)
            if (permission === 'default') {
                Notification.requestPermission().then(function(result) {
                    if (result === 'granted') {
                        showNotificationAlert('Browser notifications enabled! You will now receive notifications.', 'success');
                        showTestNotification();
                    } else if (result === 'denied') {
                        showNotificationAlert('Notifications were denied. You can enable them later in browser settings.', 'warning');
                    } else {
                        showNotificationAlert('Notification permission was dismissed. Click to try again.', 'info');
                    }
                    updateNotificationButtonState();
                }).catch(function(error) {
                    console.error('Error requesting notification permission:', error);
                    showNotificationAlert('Error requesting permission. Please try again.', 'error');
                    updateNotificationButtonState();
                });
            }
        }

        function updateNotificationButtonState() {
            const btn = document.getElementById('enable-notifications-btn');
            if (!btn) return;

            if (!('Notification' in window)) {
                btn.style.display = 'none';
                return;
            }

            const permission = Notification.permission;

            if (permission === 'granted') {
                btn.classList.add('enabled');
                btn.classList.remove('denied');
                btn.innerHTML = '<i class="fas fa-bell"></i> Enabled';
                btn.title = 'Browser notifications are enabled';
            } else if (permission === 'denied') {
                btn.classList.add('denied');
                btn.classList.remove('enabled');
                btn.innerHTML = '<i class="fas fa-bell-slash"></i> Denied';
                btn.title = 'Enable notifications in browser settings';
            } else {
                btn.classList.remove('enabled', 'denied');
                btn.innerHTML = '<i class="fas fa-bell"></i> Enable';
                btn.title = 'Click to enable browser notifications';
            }
        }

        function showNotificationAlert(message, type) {
            // Simple alert implementation - can be enhanced with custom styling
            const alertStyle = type === 'success' ? '✅' : type === 'warning' ? '⚠️' : type === 'error' ? '❌' : 'ℹ️';
            // Show as browser alert for now
            alert(`${alertStyle} ${message}`);
        }

        function showTestNotification() {
            if (Notification.permission === 'granted') {
                const testNotification = new Notification('🦷 Dental Clinic Notifications', {
                    body: 'Notifications are working! You will receive updates about your appointments.',
                    icon: '../../assets/images/tooth-icon.png',
                    tag: 'test-notification',
                    requireInteraction: false
                });

                setTimeout(() => testNotification.close(), 4000);

                testNotification.onclick = function() {
                    window.focus();
                    this.close();
                };
            }
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('notification-dropdown');
            const notification = document.querySelector('.nav-notification');
            
            if (dropdown && notification && 
                !notification.contains(event.target) && 
                !dropdown.contains(event.target)) {
                if (isNotificationDropdownOpen) {
                    hideNotificationDropdown();
                }
            }
        });

        // Initialize notification system
        document.addEventListener('DOMContentLoaded', function() {
            updateNotificationDisplay();
            updateNotificationButtonState();
        });

        // Check notification bell visibility
        window.addEventListener('load', function() {
            setTimeout(function() {
                const bell = document.getElementById('notification-bell');
                if (bell) {
                    } else {
                    }
            }, 1000);
        });

        // =================== REMINDER SYSTEM ===================
        
        let reminderCheckInterval;
        
        function startReminderChecking() {
            // Check for reminders every 5 minutes
            reminderCheckInterval = setInterval(checkForReminders, 5 * 60 * 1000);
            
            // Initial check
            checkForReminders();
        }
        
        function stopReminderChecking() {
            if (reminderCheckInterval) {
                clearInterval(reminderCheckInterval);
                reminderCheckInterval = null;
            }
        }
        
        async function checkForReminders() {
            try {
                const response = await fetch('../api/reminders.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=check_reminders'
                });
                
                const data = await response.json();
                
                if (data.success && data.has_reminders) {
                    showReminderNotification(data.reminder_message);
                }
            } catch (error) {
                console.error('Error checking reminders:', error);
            }
        }
        
        function showReminderNotification(message) {
            // Remove any existing notification
            const existingNotification = document.querySelector('.reminder-notification');
            if (existingNotification) {
                existingNotification.remove();
            }
            
            const notification = document.createElement('div');
            notification.className = 'reminder-notification';
            notification.innerHTML = `
                <div class="notification-header">
                    <div class="notification-icon">🔔</div>
                    <div class="notification-title">Appointment Reminder</div>
                </div>
                <div class="notification-message">${message}</div>
                <div class="notification-actions">
                    <button type="button" class="btn-dismiss" onclick="dismissReminderNotification()">Dismiss</button>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Trigger animation
            setTimeout(() => {
                notification.classList.add('show');
            }, 100);
            
            // Auto-dismiss after 30 seconds
            setTimeout(() => {
                dismissReminderNotification();
            }, 30000);
        }
        
        function dismissReminderNotification() {
            const notification = document.querySelector('.reminder-notification');
            if (notification) {
                notification.classList.remove('show');
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }
        }
        
        // Start reminder checking when page loads
        document.addEventListener('DOMContentLoaded', function() {
            startReminderChecking();
        });
        
        // Stop reminder checking when page is hidden/unloaded
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                stopReminderChecking();
            } else {
                startReminderChecking();
            }
        });
        
        window.addEventListener('beforeunload', function() {
            stopReminderChecking();
        });
        
        // =================== END REMINDER SYSTEM ===================
        
        // =================== END NOTIFICATION SYSTEM ===================
    </script>
</body>
</html>

