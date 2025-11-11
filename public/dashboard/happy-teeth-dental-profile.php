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

// Get clinic data from database
$clinicData = ClinicDataHelper::getFullClinicProfile('happy-teeth-dental');

// If clinic not found, redirect to clinic listing
if (!$clinicData) {
    header('Location: clinic-listing.php');
    exit();
}

// Set default values if not in database
$clinicData['tagline'] = $clinicData['tagline'] ?? 'Creating Beautiful Smiles for the Whole Family';
$clinicData['display_phone'] = $clinicData['phone'] ?? '(034) 495-1234';
$clinicData['display_email'] = $clinicData['email'] ?? 'info@happyteeth.com';
$clinicData['display_hours'] = $clinicData['operating_hours'] ?? 'Mon-Sat: 8:00 AM - 6:00 PM';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Happy Teeth Dental Center - DCMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #3E7CB1 0%, #81A4CD 100%);
            color: white;
            padding: 80px 20px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="2" fill="white" opacity="0.1"/><circle cx="80" cy="40" r="1.5" fill="white" opacity="0.1"/><circle cx="40" cy="80" r="1" fill="white" opacity="0.1"/></svg>');
        }

        .hero-content {
            max-width: 800px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .clinic-logo {
            width: 120px;
            height: 120px;
            background: white;
            border-radius: 50%;
            margin: 0 auto 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .clinic-logo img {
            width: 80px;
            height: 80px;
            object-fit: contain;
        }

        .clinic-name {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .clinic-tagline {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 30px;
        }

        .contact-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 40px;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 10px;
            justify-content: center;
        }

        .contact-item i {
            font-size: 1.2rem;
        }

        /* Main Content */
        .main-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 60px 20px;
        }

        .verification-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 40px;
            text-align: center;
        }

        .verification-title {
            font-size: 1.8rem;
            color: #2d3748;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .verification-badges {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .badge-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 20px;
            background: #DBE4EE;
            border-radius: 12px;
            border-left: 4px solid #3E7CB1;
        }

        .badge-item i {
            color: #3E7CB1;
            font-size: 1.5rem;
        }

        .badge-text {
            text-align: left;
        }

        .badge-title {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 5px;
        }

        .badge-desc {
            font-size: 0.9rem;
            color: #6b7280;
        }

        /* Two Column Layout */
        .two-column {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 60px;
        }

        .content-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .card-title {
            font-size: 1.8rem;
            color: #2d3748;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-title i {
            color: #3E7CB1;
        }

        .feature-list {
            list-style: none;
        }

        .feature-list li {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 15px;
            color: #4a5568;
        }

        .feature-list li i {
            color: #3E7CB1;
            font-size: 1.1rem;
        }

        /* Services Section */
        .services-section {
            margin-bottom: 60px;
        }

        .section-title {
            font-size: 2.5rem;
            text-align: center;
            color: #2d3748;
            margin-bottom: 50px;
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
        }

        .service-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .service-card:hover {
            transform: translateY(-5px);
        }

        .service-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #3E7CB1, #81A4CD);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }

        .service-icon i {
            color: white;
            font-size: 2rem;
        }

        .service-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 15px;
        }

        .service-desc {
            color: #6b7280;
            line-height: 1.6;
        }

        /* Location Section */
        .location-section {
            background: white;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 40px;
        }

        .map-container {
            width: 100%;
            height: 300px;
            background: #e2e8f0;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6b7280;
            margin-top: 20px;
        }

        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, #3E7CB1, #81A4CD);
            color: white;
            padding: 60px 40px;
            border-radius: 16px;
            text-align: center;
            margin-bottom: 40px;
        }

        .cta-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .cta-desc {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 40px;
        }

        .btn {
            padding: 15px 30px;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 1.1rem;
        }

        .btn-primary {
            background: white;
            color: #3E7CB1;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255,255,255,0.3);
        }

        .btn-secondary {
            background: transparent;
            color: white;
            border: 2px solid white;
            margin-left: 20px;
        }

        .btn-secondary:hover {
            background: white;
            color: #3E7CB1;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .two-column {
                grid-template-columns: 1fr;
            }
            
            .clinic-name {
                font-size: 2rem;
            }
            
            .contact-info {
                grid-template-columns: 1fr;
            }
            
            .nav-menu {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="nav-container">
            <a href="clinic-listing.php" class="logo">DCMS</a>
            
            <nav>
                <ul class="nav-menu">
                    <li><a href="clinic-listing.php">Back to Clinics</a></li>
                    <li><a href="patient-dashboard.php">My Appointments</a></li>
                    <li><a href="#contact">Contact</a></li>
                </ul>
            </nav>
            
            <div class="user-section">
                <button class="user-dropdown">
                    <i class="fas fa-user-circle"></i>
                    <span><?php echo htmlspecialchars($userName); ?></span>
                    <i class="fas fa-chevron-down"></i>
                </button>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <div class="clinic-logo">
                <img src="../../assets/images/happy-teeth.png" alt="<?php echo htmlspecialchars($clinicData['name']); ?> Logo">
            </div>
            <h1 class="clinic-name"><?php echo htmlspecialchars($clinicData['name']); ?></h1>
            <p class="clinic-tagline"><?php echo htmlspecialchars($clinicData['tagline']); ?></p>
            
            <div class="contact-info">
                <div class="contact-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <span><?php echo htmlspecialchars($clinicData['location']); ?></span>
                </div>
                <div class="contact-item">
                    <i class="fas fa-phone"></i>
                    <span><?php echo htmlspecialchars($clinicData['display_phone']); ?></span>
                </div>
                <div class="contact-item">
                    <i class="fas fa-clock"></i>
                    <span><?php echo htmlspecialchars($clinicData['display_hours']); ?></span>
                </div>
                <div class="contact-item">
                    <i class="fas fa-envelope"></i>
                    <span><?php echo htmlspecialchars($clinicData['display_email']); ?></span>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Verification Section -->
        <div class="verification-card">
            <h3 class="verification-title">
                <i class="fas fa-shield-check"></i>
                Clinic Verification Status
            </h3>
            <div class="verification-badges">
                <div class="badge-item">
                    <i class="fas fa-certificate"></i>
                    <div class="badge-text">
                        <div class="badge-title">DOH Licensed</div>
                        <div class="badge-desc">Valid health department permit</div>
                    </div>
                </div>
                <div class="badge-item">
                    <i class="fas fa-user-md"></i>
                    <div class="badge-text">
                        <div class="badge-title">Board Certified Dentists</div>
                        <div class="badge-desc">All dentists are PRC licensed</div>
                    </div>
                </div>
                <div class="badge-item">
                    <i class="fas fa-award"></i>
                    <div class="badge-text">
                        <div class="badge-title">Excellence Award 2024</div>
                        <div class="badge-desc">Best family dental care provider</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- About & Why Choose Us -->
        <div class="two-column">
            <div class="content-card">
                <h3 class="card-title">
                    <i class="fas fa-heart"></i>
                    About Happy Teeth
                </h3>
                <p style="margin-bottom: 20px; color: #4a5568;">
                    Happy Teeth Dental Center has been serving families in Talisay City for over 15 years. We specialize in comprehensive family dental care with a focus on preventive dentistry and patient comfort.
                </p>
                <p style="color: #4a5568;">
                    Our team of experienced dentists and dental hygienists are committed to providing personalized care in a warm, welcoming environment that puts even the most anxious patients at ease.
                </p>
            </div>
            
            <div class="content-card">
                <h3 class="card-title">
                    <i class="fas fa-star"></i>
                    Why Choose Us
                </h3>
                <ul class="feature-list">
                    <li><i class="fas fa-check"></i> 15+ years of trusted family dental care</li>
                    <li><i class="fas fa-check"></i> Kid-friendly environment and staff</li>
                    <li><i class="fas fa-check"></i> Latest dental technology and equipment</li>
                    <li><i class="fas fa-check"></i> Comprehensive insurance acceptance</li>
                    <li><i class="fas fa-check"></i> Emergency dental services available</li>
                    <li><i class="fas fa-check"></i> Flexible scheduling including weekends</li>
                </ul>
            </div>
        </div>

        <!-- Services Section -->
        <section class="services-section">
            <h2 class="section-title">Our Dental Services</h2>
            <div class="services-grid">
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-tooth"></i>
                    </div>
                    <h4 class="service-title">Preventive Care</h4>
                    <p class="service-desc">Regular check-ups, cleanings, fluoride treatments, and oral health education to keep your smile healthy.</p>
                </div>
                
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-child"></i>
                    </div>
                    <h4 class="service-title">Pediatric Dentistry</h4>
                    <p class="service-desc">Specialized dental care for children with a gentle approach to make dental visits fun and stress-free.</p>
                </div>
                
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-smile"></i>
                    </div>
                    <h4 class="service-title">Cosmetic Dentistry</h4>
                    <p class="service-desc">Teeth whitening, veneers, and smile makeovers to enhance the beauty of your smile.</p>
                </div>
                
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-tools"></i>
                    </div>
                    <h4 class="service-title">Restorative Care</h4>
                    <p class="service-desc">Fillings, crowns, bridges, and implants to restore your teeth's function and appearance.</p>
                </div>
            </div>
        </section>

        <!-- Location Section -->
        <section class="location-section">
            <h3 class="card-title">
                <i class="fas fa-map-marker-alt"></i>
                Our Location
            </h3>
            <p style="color: #6b7280; margin-bottom: 20px;">
                Conveniently located in Zone 2, Talisay City with ample parking space and easy access to public transportation.
            </p>
            <div class="map-container">
                <div style="text-align: center;">
                    <i class="fas fa-map" style="font-size: 3rem; margin-bottom: 10px;"></i>
                    <p>Interactive Map</p>
                    <p style="font-size: 0.9rem;">Zone 2, Talisay City, Negros Occidental</p>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="cta-section">
            <h2 class="cta-title">Ready for Your Best Smile?</h2>
            <p class="cta-desc">Book your appointment today and experience the Happy Teeth difference!</p>
            <a href="patient-dashboard.php?clinic=<?php echo $clinicData['url_name']; ?>&action=book" class="btn btn-primary">
                <i class="fas fa-calendar-plus"></i>
                Book Appointment
            </a>
            <a href="clinic-listing.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i>
                Back to Clinics
            </a>
        </section>
    </main>
</body>
</html>