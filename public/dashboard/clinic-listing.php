<?php
session_start();
require_once '../../src/config/database.php';
require_once '../../src/config/session.php';
require_once '../../src/helpers/ClinicDataHelper.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$user = $_SESSION;
$userName = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'User';

// Fetch all clinics from database
$clinics = ClinicDataHelper::getAllClinics();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Clinics - DCMS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.2/css/all.min.css" crossorigin="anonymous">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8fafc;
            line-height: 1.6;
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

        .user-dropdown {
            display: flex;
            align-items: center;
            gap: 10px;
            background: none;
            border: none;
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 8px;
            transition: background 0.3s;
        }

        .user-dropdown:hover {
            background: #f7fafc;
        }

        /* User Dropdown Menu */
        .dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            border: 1px solid #e2e8f0;
            min-width: 200px;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
        }

        .dropdown-menu.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            color: #4a5568;
            text-decoration: none;
            font-size: 0.875rem;
            transition: background 0.2s ease;
        }

        .dropdown-item:hover {
            background: #f7fafc;
            color: #667eea;
        }

        .dropdown-item i {
            width: 16px;
            text-align: center;
        }

        .dropdown-divider {
            height: 1px;
            background: #e2e8f0;
            margin: 4px 0;
        }

        .logout-link {
            color: #e53e3e !important;
        }

        .logout-link:hover {
            background: #fed7d7 !important;
            color: #c53030 !important;
        }

        .user-section {
            position: relative;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #054A91 0%, #3E7CB1 100%);
            color: white;
            padding: 80px 20px;
            text-align: center;
        }

        .hero-content {
            max-width: 800px;
            margin: 0 auto;
        }

        .hero h1 {
            font-size: 3rem;
            margin-bottom: 20px;
            font-weight: 700;
        }

        .hero p {
            font-size: 1.2rem;
            margin-bottom: 40px;
            opacity: 0.9;
        }

        .service-categories {
            display: flex;
            gap: 30px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .category-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 20px;
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }

        .category-icon {
            font-size: 1.5rem;
        }

        /* Main Content */
        .main-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 60px 20px;
        }

        .location-title {
            font-size: 2rem;
            color: #4a5568;
            margin-bottom: 30px;
            padding-bottom: 10px;
            border-bottom: 3px solid #054A91;
            display: inline-block;
            text-align: center;
            width: 100%;
        }

        /* Clinics Grid - CENTERED HORIZONTAL LAYOUT */
        .clinics-container {
            display: flex;
            justify-content: center;
            width: 100%;
        }

        .clinics-grid {
            display: flex;
            flex-direction: row;
            gap: 30px;
            overflow-x: auto;
            padding-bottom: 20px;
            scroll-behavior: smooth;
            -webkit-overflow-scrolling: touch;
            justify-content: center;
            align-items: flex-start;
            max-width: 1200px;
        }

        .clinics-grid::-webkit-scrollbar {
            height: 8px;
        }

        .clinics-grid::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .clinics-grid::-webkit-scrollbar-thumb {
            background: #054A91;
            border-radius: 4px;
        }

        .clinics-grid::-webkit-scrollbar-thumb:hover {
            background: #3E7CB1;
        }

        .clinic-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            position: relative;
            flex: 0 0 350px;
            min-width: 350px;
            max-width: 350px;
        }

        .clinic-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
        }

        .clinic-image {
            height: 200px;
            background-size: cover;
            background-position: center;
            position: relative;
        }

        .ardent-clinic {
            background-image: url('../../assets/images/ardent-dental.png');
        }

        .gamboa-clinic {
            background-image: url('../../assets/images/gamboa-dental.png');
        }

        .happy-teeth-dental-clinic {
            background-image: url('../../assets/images/happy-teeth-dental.png');
        }

        .clinic-badges {
            position: absolute;
            top: 15px;
            left: 15px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge.featured {
            background: #f17300;
            color: white;
        }

        .badge.popular {
            background: #8B5CF6;
            color: white;
        }

        .badge.verified {
            background: #3E7CB1;
            color: white;
        }

        .badge.not-verified {
            background: #9ca3af;
            color: white;
        }

        .badge.open {
            background: #3E7CB1;
            color: white;
        }

        .badge.closed {
            background: #9ca3af;
            color: white;
        }

        .badge.busy {
            background: #f17300;
            color: white;
        }

        .badge.available {
            background: #10B981;
            color: white;
        }

        .badge.fully-booked {
            background: #EF4444;
            color: white;
        }

        .clinic-info {
            padding: 25px;
        }

        .clinic-name {
            font-size: 1.4rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 8px;
        }

        .clinic-location {
            color: #718096;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .clinic-services {
            margin-bottom: 25px;
        }

        .services-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        .service-tag {
            background: #DBE4EE;
            color: #054A91;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.85rem;
            text-align: center;
        }

        .clinic-actions {
            display: flex;
            gap: 15px;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 1;
            justify-content: center;
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

        /* Responsive Design */
        @media (max-width: 1024px) {
            .clinics-grid {
                gap: 20px;
                padding: 0 10px 20px;
            }
            
            .clinic-card {
                flex: 0 0 320px;
                min-width: 320px;
                max-width: 320px;
            }
        }

        @media (max-width: 768px) {
            .nav-menu {
                display: none;
            }

            .hero h1 {
                font-size: 2rem;
            }

            .service-categories {
                flex-direction: column;
                align-items: center;
            }

            .clinics-grid {
                gap: 15px;
                padding: 0 10px 20px;
                scroll-snap-type: x mandatory;
                justify-content: flex-start;
            }
            
            .clinic-card {
                flex: 0 0 280px;
                min-width: 280px;
                max-width: 280px;
                scroll-snap-align: start;
            }

            .clinic-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="nav-container">
            <div class="logo">DCMS</div>
            
            <div class="user-section">
                <button class="user-dropdown" id="userDropdown">
                    <i class="fas fa-user-circle"></i>
                    <span><?php echo htmlspecialchars($userName); ?></span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                
                <!-- User Dropdown Menu -->
                <div class="dropdown-menu" id="dropdownMenu">
                    <a href="patient-dashboard.php#profile" class="dropdown-item" onclick="goToProfile(event)">
                        <i class="fas fa-user-cog"></i>
                        Profile Settings
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="#" class="dropdown-item logout-link" onclick="handleLogout(event)">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>Available Clinics</h1>
            <p>Browse our partner clinics by location and schedule an appointment.</p>
            
            <div class="service-categories">
                <div class="category-card">
                    <i class="fas fa-shield-alt category-icon"></i>
                    <span>Trusted Dental Care</span>
                </div>
                <div class="category-card">
                    <i class="fas fa-calendar-alt category-icon"></i>
                    <span>Appointment Scheduling</span>
                </div>
                <div class="category-card">
                    <i class="fas fa-users category-icon"></i>
                    <span>Family Dental Care</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <main class="main-content">
        <h2 class="location-title">Available Clinics</h2>
        <div class="clinics-container">
            <div class="clinics-grid">
                <?php foreach ($clinics as $clinic): ?>
                    <?php 
                        $services = ClinicDataHelper::getClinicServices($clinic['id']);
                        $serviceNames = array_slice(array_column($services, 'treatment_name'), 0, 4);
                        // Fix the image class generation to handle dashes properly
                        $imageClass = $clinic['url_name'] . '-clinic';
                    ?>
                    <div class="clinic-card">
                        <div class="clinic-image <?php echo $imageClass; ?>">
                            <div class="clinic-badges">
                                <?php if ($clinic['is_featured']): ?>
                                    <span class="badge featured">Featured</span>
                                <?php endif; ?>
                                
                                <?php if ($clinic['is_popular']): ?>
                                    <span class="badge popular">Popular</span>
                                <?php endif; ?>
                                
                                <span class="badge <?php echo $clinic['is_verified'] ? 'verified' : 'not-verified'; ?>">
                                    <?php echo $clinic['is_verified'] ? 'Verified' : 'Not Verified'; ?>
                                </span>
                                
                                <?php 
                                    $statusClass = '';
                                    $statusText = '';
                                    
                                    if ($clinic['is_open_today']) {
                                        switch ($clinic['current_status']) {
                                            case 'Busy':
                                                $statusClass = 'busy';
                                                $statusText = 'Busy Now';
                                                break;
                                            case 'Fully Booked':
                                                $statusClass = 'fully-booked';
                                                $statusText = 'Fully Booked';
                                                break;
                                            case 'Available':
                                            default:
                                                $statusClass = 'available';
                                                $statusText = 'Available';
                                                break;
                                        }
                                    } else {
                                        $statusClass = 'closed';
                                        $statusText = 'Closed Today';
                                    }
                                ?>
                                <span class="badge <?php echo $statusClass; ?>">
                                    <?php echo $statusText; ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="clinic-info">
                            <h3 class="clinic-name"><?php echo htmlspecialchars($clinic['name']); ?></h3>
                            <div class="clinic-location">
                                <i class="fas fa-map-marker-alt"></i>
                                <span><?php echo htmlspecialchars($clinic['location']); ?></span>
                            </div>
                            
                            <div class="clinic-services">
                                <div class="services-grid">
                                    <?php foreach (array_slice($serviceNames, 0, 4) as $service): ?>
                                        <span class="service-tag"><?php echo htmlspecialchars($service); ?></span>
                                    <?php endforeach; ?>
                                    <?php if (count($serviceNames) < 4): ?>
                                        <?php for ($i = count($serviceNames); $i < 4; $i++): ?>
                                            <span class="service-tag">General Care</span>
                                        <?php endfor; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="clinic-actions">
                                <a href="#" class="btn btn-secondary view-profile" data-clinic="<?php echo $clinic['url_name']; ?>">
                                    <i class="fas fa-info-circle"></i>
                                    View Profile
                                </a>
                                <a href="patient-dashboard.php?branch_id=<?php echo $clinic['id']; ?>&action=book&clinic=<?php echo $clinic['url_name']; ?>" class="btn btn-primary book-now" data-clinic="<?php echo $clinic['url_name']; ?>" data-branch-id="<?php echo $clinic['id']; ?>">
                                    <i class="fas fa-calendar-plus"></i>
                                    Book Now
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Keyboard shortcut for logout (Ctrl+L)
            document.addEventListener('keydown', function(e) {
                if (e.ctrlKey && e.key === 'l') {
                    e.preventDefault();
                    const logoutEvent = { 
                        preventDefault: () => {},
                        target: { closest: () => document.querySelector('.logout-link') }
                    };
                    handleLogout(logoutEvent);
                }
            });

            // Book Now button handlers
            const bookButtons = document.querySelectorAll('.book-now');
            bookButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    const clinic = this.getAttribute('data-clinic');
                    const branchId = this.getAttribute('data-branch-id');
                    const clinicName = this.closest('.clinic-card').querySelector('.clinic-name').textContent;
                    
                    console.log('Booking for clinic:', clinic, 'Branch ID:', branchId);
                    
                    // Show loading state
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Redirecting...';
                    
                    // Add small delay for better UX
                    setTimeout(() => {
                        window.location.href = this.href;
                    }, 500);
                });
            });

            // View Profile button handlers
            const profileButtons = document.querySelectorAll('.view-profile');
            profileButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const clinic = this.getAttribute('data-clinic');
                    
                    // Show loading state
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
                    
                    // Map clinic names to correct profile filenames
                    const profileMapping = {
                        'ardent': 'ardent-profile.php',
                        'gamboa': 'gamboa-profile.php',
                        'happy-teeth-dental': 'happy-teeth-dental-profile.php'
                    };
                    
                    const profileFile = profileMapping[clinic] || `${clinic}-profile.php`;
                    
                    // Redirect to clinic profile page
                    setTimeout(() => {
                        window.location.href = profileFile;
                    }, 500);
                });
            });

            // User dropdown handler
            const userDropdown = document.querySelector('#userDropdown');
            const dropdownMenu = document.querySelector('#dropdownMenu');
            
            userDropdown.addEventListener('click', function(e) {
                e.stopPropagation();
                dropdownMenu.classList.toggle('show');
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function() {
                dropdownMenu.classList.remove('show');
            });
            
            // Prevent dropdown from closing when clicking inside it
            dropdownMenu.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        });

        // Logout function
        function handleLogout(event) {
            event.preventDefault();
            
            // Show confirmation dialog
            if (confirm('Are you sure you want to logout?')) {
                const logoutLink = event.target.closest('.logout-link');
                
                // Show loading state
                const originalContent = logoutLink.innerHTML;
                logoutLink.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Logging out...';
                
                // Make logout request
                fetch('../api/auth.php?action=logout', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message briefly
                        logoutLink.innerHTML = '<i class="fas fa-check"></i> Logged out';
                        
                        // Redirect to login page after a short delay
                        setTimeout(() => {
                            window.location.href = '../auth/login.php';
                        }, 1000);
                    } else {
                        // Handle error
                        alert('Logout failed: ' + (data.message || 'Unknown error'));
                        logoutLink.innerHTML = originalContent;
                    }
                })
                .catch(error => {
                    console.error('Logout error:', error);
                    alert('Logout failed. Please try again.');
                    logoutLink.innerHTML = originalContent;
                });
            }
        }

        // `goToProfile` moved to external file: js/profile-nav.js to keep this file small.
    </script>
    <!-- Load small per-page scripts (kept separate to reduce clutter) -->
    <script src="js/profile-nav.js"></script>
</body>
</html>