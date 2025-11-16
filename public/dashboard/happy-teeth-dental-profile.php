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

// Get branch ID for Happy Teeth Dental (ID = 1)
$branch_id = 1;

// Get dentist credentials and clinic credentials
$db = Database::getConnection();

// Get dentist credentials for this branch
$dentist_stmt = $db->prepare("
    SELECT dc.*, u.name as dentist_name
    FROM dentist_credentials dc
    INNER JOIN users u ON dc.dentist_id = u.id
    WHERE u.branch_id = ? AND u.role = 'dentist'
    LIMIT 1
");
$dentist_stmt->bind_param("i", $branch_id);
$dentist_stmt->execute();
$dentist_credentials = $dentist_stmt->get_result()->fetch_assoc();

// Get clinic credentials for this branch
$clinic_stmt = $db->prepare("SELECT * FROM clinic_credentials WHERE branch_id = ?");
$clinic_stmt->bind_param("i", $branch_id);
$clinic_stmt->execute();
$clinic_credentials = $clinic_stmt->get_result()->fetch_assoc();

// Parse JSON fields
$clinic_photos = [];
$certifications = [];

if ($clinic_credentials) {
    if ($clinic_credentials['clinic_photos']) {
        $clinic_photos = json_decode($clinic_credentials['clinic_photos'], true) ?: [];
    }
    if ($clinic_credentials['certifications']) {
        $certifications = json_decode($clinic_credentials['certifications'], true) ?: [];
    }
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

        .service-price {
            color: #3E7CB1;
            font-weight: 600;
            font-size: 1.1rem;
            margin-top: 10px;
        }

        /* Specialty Section */
        .specialty-section {
            background: white;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 40px;
        }

        .specialty-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }

        .specialty-item {
            background: #f8fafc;
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            border: 2px solid #e2e8f0;
            transition: all 0.3s;
        }

        .specialty-item:hover {
            border-color: #3E7CB1;
            transform: translateY(-3px);
        }

        .specialty-icon {
            font-size: 2.5rem;
            color: #3E7CB1;
            margin-bottom: 15px;
        }

        .specialty-name {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 10px;
        }

        .specialty-desc {
            font-size: 0.9rem;
            color: #6b7280;
        }

        /* Why Choose Section */
        .why-choose-section {
            background: white;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 40px;
        }

        .why-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }

        .why-item {
            text-align: center;
            padding: 20px;
        }

        .why-icon {
            font-size: 3rem;
            color: #3E7CB1;
            margin-bottom: 15px;
        }

        .why-title {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 10px;
            font-size: 1.1rem;
        }

        .why-desc {
            color: #6b7280;
            font-size: 0.95rem;
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
            height: 400px;
            background: #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
            margin-top: 20px;
            border: 2px solid #e2e8f0;
        }

        .map-container iframe {
            width: 100%;
            height: 100%;
            border: 0;
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
                <img src="../../assets/images/happy-teeth-dental.png" alt="<?php echo htmlspecialchars($clinicData['name']); ?> Logo">
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

        <!-- Specialty Services -->
        <section class="specialty-section">
            <h3 class="card-title">
                <i class="fas fa-star"></i>
                Our Specialties
            </h3>
            <div class="specialty-grid">
                <div class="specialty-item">
                    <div class="specialty-icon">
                        <i class="fas fa-smile"></i>
                    </div>
                    <div class="specialty-name">Cosmetic Dentistry</div>
                    <div class="specialty-desc">Teeth whitening, veneers, and smile makeovers for beautiful smiles</div>
                </div>
                
                <div class="specialty-item">
                    <div class="specialty-icon">
                        <i class="fas fa-child"></i>
                    </div>
                    <div class="specialty-name">Pediatric Dentistry</div>
                    <div class="specialty-desc">Kid-friendly dental care with gentle, patient approach</div>
                </div>
                
                <div class="specialty-item">
                    <div class="specialty-icon">
                        <i class="fas fa-crown"></i>
                    </div>
                    <div class="specialty-name">Prosthodontics</div>
                    <div class="specialty-desc">Crowns, bridges, dentures, and implant restorations</div>
                </div>
                
                <div class="specialty-item">
                    <div class="specialty-icon">
                        <i class="fas fa-align-center"></i>
                    </div>
                    <div class="specialty-name">Orthodontics</div>
                    <div class="specialty-desc">Braces and aligners for perfect teeth alignment</div>
                </div>
            </div>
        </section>

        <!-- Why Choose Happy Teeth -->
        <section class="why-choose-section">
            <h3 class="card-title">
                <i class="fas fa-thumbs-up"></i>
                Why Families Trust Happy Teeth
            </h3>
            <div class="why-grid">
                <div class="why-item">
                    <div class="why-icon">
                        <i class="fas fa-user-md"></i>
                    </div>
                    <div class="why-title">Experienced Team</div>
                    <div class="why-desc">Board-certified dentists with 15+ years of experience in family dentistry</div>
                </div>
                
                <div class="why-item">
                    <div class="why-icon">
                        <i class="fas fa-laptop-medical"></i>
                    </div>
                    <div class="why-title">Modern Technology</div>
                    <div class="why-desc">State-of-the-art equipment including digital X-rays and laser dentistry</div>
                </div>
                
                <div class="why-item">
                    <div class="why-icon">
                        <i class="fas fa-hand-holding-heart"></i>
                    </div>
                    <div class="why-title">Gentle Care</div>
                    <div class="why-desc">Pain-free procedures with sedation options for anxious patients</div>
                </div>
                
                <div class="why-item">
                    <div class="why-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="why-title">Affordable Pricing</div>
                    <div class="why-desc">Competitive rates with flexible payment plans and insurance acceptance</div>
                </div>
            </div>
        </section>

        <!-- Services Section -->
        <section class="services-section">
            <h2 class="section-title">Our Dental Services</h2>
            <div class="services-grid">
                <?php if (!empty($clinicData['services'])): ?>
                    <?php foreach (array_slice($clinicData['services'], 0, 6) as $service): ?>
                        <div class="service-card">
                            <div class="service-icon">
                                <i class="fas fa-tooth"></i>
                            </div>
                            <h4 class="service-title"><?php echo htmlspecialchars($service['treatment_name']); ?></h4>
                            <p class="service-desc"><?php echo htmlspecialchars($service['description'] ?? 'Professional dental care service.'); ?></p>
                            <?php if ($service['price']): ?>
                                <p class="service-price">₱<?php echo number_format($service['price'], 2); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Default services if none in database -->
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fas fa-tooth"></i>
                        </div>
                        <h4 class="service-title">Preventive Care</h4>
                        <p class="service-desc">Regular check-ups, cleanings, fluoride treatments, and oral health education to keep your smile healthy.</p>
                        <p class="service-price">Starting at ₱600.00</p>
                    </div>
                    
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fas fa-child"></i>
                        </div>
                        <h4 class="service-title">Pediatric Dentistry</h4>
                        <p class="service-desc">Specialized dental care for children with a gentle approach to make dental visits fun and stress-free.</p>
                        <p class="service-price">Starting at ₱700.00</p>
                    </div>
                    
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fas fa-smile"></i>
                        </div>
                        <h4 class="service-title">Teeth Whitening</h4>
                        <p class="service-desc">Professional teeth whitening treatments to brighten your smile by several shades.</p>
                        <p class="service-price">₱3,500.00 per session</p>
                    </div>
                    
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fas fa-crown"></i>
                        </div>
                        <h4 class="service-title">Dental Crowns</h4>
                        <p class="service-desc">High-quality porcelain and ceramic crowns to restore damaged or decayed teeth.</p>
                        <p class="service-price">Starting at ₱5,000.00</p>
                    </div>
                    
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fas fa-align-center"></i>
                        </div>
                        <h4 class="service-title">Braces & Aligners</h4>
                        <p class="service-desc">Traditional braces and clear aligners for effective teeth straightening and alignment.</p>
                        <p class="service-price">Consultation required</p>
                    </div>
                    
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fas fa-tools"></i>
                        </div>
                        <h4 class="service-title">Restorative Care</h4>
                        <p class="service-desc">Fillings, bridges, and implants to restore your teeth's function and appearance.</p>
                        <p class="service-price">Starting at ₱1,200.00</p>
                    </div>
                <?php endif; ?>
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
                <iframe 
                    src="https://www.google.com/maps/embed/v1/place?key=AIzaSyBFw0Qbyq9zTFTd-tUY6dZWTgaQzuU17R8&q=<?php echo urlencode($clinicData['location']); ?>&zoom=15"
                    allowfullscreen="" 
                    loading="lazy" 
                    referrerpolicy="no-referrer-when-downgrade"
                    title="<?php echo htmlspecialchars($clinicData['name']); ?> Location Map">
                </iframe>
            </div>
        </section>

        <!-- Dentist Credentials Section -->
        <?php if ($dentist_credentials): ?>
        <section class="credentials-section">
            <h3 class="card-title">
                <i class="fas fa-user-md"></i>
                Our Dentist
            </h3>
            <div class="dentist-info">
                <div class="dentist-details">
                    <h4><?php echo htmlspecialchars($dentist_credentials['dentist_name']); ?></h4>
                    <?php if ($dentist_credentials['specialization']): ?>
                        <p class="specialization">
                            <i class="fas fa-star"></i>
                            <strong>Specialization:</strong> <?php echo htmlspecialchars($dentist_credentials['specialization']); ?>
                        </p>
                    <?php endif; ?>
                    <?php if ($dentist_credentials['license_number']): ?>
                        <p class="license">
                            <i class="fas fa-id-card"></i>
                            <strong>License Number:</strong> <?php echo htmlspecialchars($dentist_credentials['license_number']); ?>
                            <?php if ($dentist_credentials['license_file']): ?>
                                <button type="button" class="btn-view-license" onclick="viewCredentialFile('../../<?php echo htmlspecialchars($dentist_credentials['license_file']); ?>', 'License Document')">
                                    <i class="fas fa-eye"></i> View License
                                </button>
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                    <?php if ($dentist_credentials['years_of_experience']): ?>
                        <p class="experience">
                            <i class="fas fa-briefcase"></i>
                            <strong>Experience:</strong> <?php echo htmlspecialchars($dentist_credentials['years_of_experience']); ?> years
                        </p>
                    <?php endif; ?>
                    <?php if ($dentist_credentials['education']): ?>
                        <p class="education">
                            <i class="fas fa-graduation-cap"></i>
                            <strong>Education:</strong> <?php echo htmlspecialchars($dentist_credentials['education']); ?>
                        </p>
                    <?php endif; ?>
                    <?php if ($dentist_credentials['bio']): ?>
                        <p class="bio">
                            <i class="fas fa-info-circle"></i>
                            <?php echo nl2br(htmlspecialchars($dentist_credentials['bio'])); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- Clinic Photos Section -->
        <?php if (!empty($clinic_photos)): ?>
        <section class="clinic-photos-section">
            <h3 class="card-title">
                <i class="fas fa-images"></i>
                Clinic Gallery
            </h3>
            <div class="photo-gallery">
                <?php foreach ($clinic_photos as $photo): ?>
                    <div class="photo-item">
                        <img src="../../<?php echo htmlspecialchars($photo); ?>" 
                             alt="Clinic Photo" 
                             onclick="viewCredentialFile('../../<?php echo htmlspecialchars($photo); ?>', 'Clinic Photo')"
                             style="cursor: pointer;">
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Certifications Section -->
        <?php if (!empty($certifications)): ?>
        <section class="certifications-section">
            <h3 class="card-title">
                <i class="fas fa-certificate"></i>
                Certifications & Accreditations
            </h3>
            <div class="certifications-list">
                <?php foreach ($certifications as $cert): ?>
                    <div class="certification-item">
                        <i class="fas fa-file-pdf"></i>
                        <span><?php echo htmlspecialchars(basename($cert)); ?></span>
                        <button type="button" class="btn-view-cert" onclick="viewCredentialFile('../../<?php echo htmlspecialchars($cert); ?>', 'Certification')">
                            <i class="fas fa-eye"></i> View
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

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

    <!-- Credential File Viewer Modal -->
    <div id="credential-file-modal" class="credential-modal">
        <div class="credential-modal-content">
            <span class="credential-close" onclick="closeCredentialModal()">&times;</span>
            <h3 id="credential-modal-title">Document</h3>
            <div id="credential-modal-body"></div>
        </div>
    </div>

    <script>
        function viewCredentialFile(filePath, title) {
            const modal = document.getElementById('credential-file-modal');
            const modalTitle = document.getElementById('credential-modal-title');
            const modalBody = document.getElementById('credential-modal-body');
            
            modalTitle.textContent = title;
            
            const fileExt = filePath.split('.').pop().toLowerCase();
            
            if (fileExt === 'pdf') {
                modalBody.innerHTML = `<iframe src="${filePath}" style="width: 100%; height: 600px; border: none;"></iframe>`;
            } else if (['jpg', 'jpeg', 'png', 'gif'].includes(fileExt)) {
                modalBody.innerHTML = `<img src="${filePath}" style="max-width: 100%; height: auto;">`;
            } else {
                modalBody.innerHTML = `<p>Unable to preview this file type. <a href="${filePath}" target="_blank">Download file</a></p>`;
            }
            
            modal.style.display = 'block';
        }
        
        function closeCredentialModal() {
            const modal = document.getElementById('credential-file-modal');
            modal.style.display = 'none';
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('credential-file-modal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>

    <style>
        /* Dentist Credentials Section */
        .credentials-section {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .dentist-info {
            margin-top: 20px;
        }

        .dentist-details h4 {
            color: #2563eb;
            font-size: 24px;
            margin-bottom: 15px;
        }

        .dentist-details p {
            margin: 12px 0;
            color: #4b5563;
            line-height: 1.6;
        }

        .dentist-details p i {
            color: #2563eb;
            margin-right: 8px;
            width: 20px;
        }

        .btn-view-license {
            margin-left: 15px;
            padding: 5px 15px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-view-license:hover {
            background: #1d4ed8;
        }

        .bio {
            margin-top: 20px;
            padding: 15px;
            background: #f9fafb;
            border-left: 4px solid #2563eb;
            border-radius: 5px;
        }

        /* Clinic Photos Section */
        .clinic-photos-section {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .photo-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .photo-item img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 10px;
            transition: transform 0.3s ease;
        }

        .photo-item img:hover {
            transform: scale(1.05);
        }

        /* Certifications Section */
        .certifications-section {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .certifications-list {
            margin-top: 20px;
        }

        .certification-item {
            display: flex;
            align-items: center;
            padding: 15px;
            background: #f9fafb;
            border-radius: 10px;
            margin-bottom: 10px;
        }

        .certification-item i {
            color: #dc2626;
            font-size: 24px;
            margin-right: 15px;
        }

        .certification-item span {
            flex: 1;
            color: #374151;
        }

        .btn-view-cert {
            padding: 8px 20px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-view-cert:hover {
            background: #1d4ed8;
        }

        /* Credential Modal */
        .credential-modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.8);
        }

        .credential-modal-content {
            background-color: #fefefe;
            margin: 2% auto;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 1000px;
            position: relative;
        }

        .credential-close {
            color: #aaa;
            float: right;
            font-size: 35px;
            font-weight: bold;
            cursor: pointer;
            line-height: 20px;
        }

        .credential-close:hover,
        .credential-close:focus {
            color: #000;
        }

        #credential-modal-title {
            margin-bottom: 20px;
            color: #2563eb;
        }
    </style>
</body>
</html>