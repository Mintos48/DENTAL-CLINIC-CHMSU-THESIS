<?php
/**
 * Authentication Controller - Database Enabled with DirectPHPMailer Service
 */

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/config/session.php';

class AuthController {
    private $db;
    private $config;
    
    public function __construct() {
        $this->db = Database::getConnection();
        $this->loadEnvConfig();
    }
    
    /**
     * Load environment configuration
     */
    private function loadEnvConfig() {
        $envFile = dirname(__DIR__, 2) . '/.env';
        
        if (!file_exists($envFile)) {
            $this->config = [];
            return;
        }
        
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $config = [];
        
        foreach ($lines as $line) {
            if (strpos($line, '#') === 0) continue;
            
            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $key = trim($parts[0]);
                $value = trim($parts[1], '"');
                $config[$key] = $value;
            }
        }
        
        $this->config = $config;
    }
    
    /**
     * Handle registration - Database Version
     */
    public function register() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(400);
            return array('success' => false, 'message' => 'Invalid request method');
        }
        
        // Get form data
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
        $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
        $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
        $address = isset($_POST['address']) ? trim($_POST['address']) : '';
        $date_of_birth = isset($_POST['date_of_birth']) ? $_POST['date_of_birth'] : '';
        $gender = isset($_POST['gender']) ? $_POST['gender'] : '';
        
        // Validation
        if (empty($email) || empty($password) || empty($full_name) || empty($phone) || empty($address) || empty($date_of_birth) || empty($gender)) {
            return array('success' => false, 'message' => 'All required fields must be filled');
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return array('success' => false, 'message' => 'Invalid email format');
        }
        
        if ($password !== $confirm_password) {
            return array('success' => false, 'message' => 'Passwords do not match');
        }
        
        if (strlen($password) < 6) {
            return array('success' => false, 'message' => 'Password must be at least 6 characters');
        }
        
        try {
            // Check if user already exists
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                return array('success' => false, 'message' => 'Email already registered');
            }
            
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user (default role is patient, no branch assignment)
            $stmt = $this->db->prepare("INSERT INTO users (name, email, password, role, phone, address, date_of_birth, gender, status, email_verified) VALUES (?, ?, ?, 'patient', ?, ?, ?, ?, 'active', 0)");
            $stmt->bind_param("sssssss", $full_name, $email, $hashed_password, $phone, $address, $date_of_birth, $gender);
            
            if ($stmt->execute()) {
                $user_id = $this->db->insert_id;
                $this->logActivity($user_id, 'REGISTER', 'New user registered');
                
                return array('success' => true, 'message' => 'Registration successful! You can now login.');
            } else {
                return array('success' => false, 'message' => 'Registration failed. Please try again.');
            }
            
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            return array('success' => false, 'message' => 'Registration failed. Please try again.');
        }
    }
    
    /**
     * Handle login - Database Version
     */
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(400);
            return array('success' => false, 'message' => 'Invalid request method');
        }
        
        // Get form data
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        
        // Validation
        if (empty($email) || empty($password)) {
            return array('success' => false, 'message' => 'Email and password are required');
        }
        
        try {
            // Query user from database including email_verified status
            $stmt = $this->db->prepare("SELECT id, name, email, password, role, branch_id, status, email_verified FROM users WHERE email = ? AND status = 'active'");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                return array('success' => false, 'message' => 'Invalid email or password');
            }
            
            $user = $result->fetch_assoc();
            
            // Verify password
            if (!password_verify($password, $user['password'])) {
                return array('success' => false, 'message' => 'Invalid email or password');
            }
            
            // Check if email is verified
            if (!$user['email_verified']) {
                // Check if there's already an active verification code that hasn't expired
                $recentVerificationCheck = $this->db->prepare("
                    SELECT verification_code_expires_at 
                    FROM users 
                    WHERE id = ? 
                    AND verification_code IS NOT NULL 
                    AND verification_code_expires_at > NOW()
                ");
                $recentVerificationCheck->bind_param("i", $user['id']);
                $recentVerificationCheck->execute();
                $recentResult = $recentVerificationCheck->get_result();
                
                if ($recentResult->num_rows > 0) {
                    // Active verification code exists, don't send another one
                    return array(
                        'success' => false, 
                        'message' => 'Email not verified. A verification code is already active. Please check your email or wait for the current code to expire before requesting a new one.',
                        'email_verification_required' => true,
                        'verification_sent' => false,
                        'user_email' => $user['email'],
                        'user_id' => $user['id'],
                        'code_expires_minutes' => 15,
                        'rate_limited' => true
                    );
                }
                
                // Send verification email
                try {
                    $verificationResult = $this->triggerEmailVerification($user['id'], $user['email'], $user['name']);
                    
                    if ($verificationResult['success']) {
                        return array(
                            'success' => false, 
                            'message' => 'Email not verified. A verification code has been sent to your email address.',
                            'email_verification_required' => true,
                            'verification_sent' => true,
                            'user_email' => $user['email'],
                            'user_id' => $user['id'],
                            'code_expires_minutes' => 15
                        );
                    } else {
                        return array(
                            'success' => false, 
                            'message' => 'Email not verified. Failed to send verification code. Please contact support.',
                            'email_verification_required' => true,
                            'verification_sent' => false,
                            'user_email' => $user['email'],
                            'user_id' => $user['id']
                        );
                    }
                } catch (Exception $e) {
                    error_log("Email verification trigger error: " . $e->getMessage());
                    return array(
                        'success' => false, 
                        'message' => 'Email not verified. Unable to send verification code at this time.',
                        'email_verification_required' => true,
                        'verification_sent' => false,
                        'user_email' => $user['email'],
                        'user_id' => $user['id']
                    );
                }
            }
            
            // Email is verified, now send OTP for login verification
            $otpResult = $this->generateLoginOTP($user['id'], $user['email'], $user['name']);
            
            // Log the OTP generation for debugging
            error_log("Login OTP generated for user {$user['id']} ({$user['email']}): " . ($otpResult['success'] ? 'SUCCESS' : 'FAILED'));
            
            if ($otpResult['success']) {
                return array(
                    'success' => false, // Not fully logged in yet, need OTP
                    'message' => 'OTP sent to your email. Please verify to complete login.',
                    'otp_verification_required' => true,
                    'user_email' => $user['email'],
                    'user_id' => $user['id'],
                    'expires_in_minutes' => 5
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Failed to send OTP. Please try again.',
                    'otp_failed' => true
                );
            }
            
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return array('success' => false, 'message' => 'Login failed. Please try again.');
        }
    }
    
    /**
     * Handle logout
     */
    public function logout() {
        if (!defined('NO_SESSION') && !defined('API_ENDPOINT')) {
            if (isLoggedIn()) {
                $this->logActivity(getSessionUserId(), 'LOGOUT', 'User logged out');
            }
            destroySession();
        }
        return array('success' => true, 'message' => 'Logged out successfully');
    }
    
    /**
     * Trigger email verification for a specific user during login
     */
    private function triggerEmailVerification($userId, $email, $name) {
        try {
            // Simulate the POST data for sendEmailVerification method
            $originalPost = $_POST;
            $_POST['email'] = $email;
            $_POST['user_id'] = $userId;
            
            $result = $this->sendEmailVerification();
            
            // Restore original POST data
            $_POST = $originalPost;
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Trigger email verification error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to send verification email'];
        }
    }

    /**
     * Send email verification
     */
    public function sendEmailVerification() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(400);
            return array('success' => false, 'message' => 'Invalid request method');
        }
        
        $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        
        if (empty($user_id) || empty($email)) {
            return array('success' => false, 'message' => 'Invalid user data');
        }
        
        try {
            // Verify user exists and email matches
            $stmt = $this->db->prepare("SELECT id, name, email FROM users WHERE id = ? AND email = ? AND status = 'active'");
            $stmt->bind_param("is", $user_id, $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                return array('success' => false, 'message' => 'User not found');
            }
            
            $user = $result->fetch_assoc();
            
            // Rate limiting: Check if there's already an active verification code
            $recentVerificationCheck = $this->db->prepare("
                SELECT verification_code_expires_at 
                FROM users 
                WHERE id = ? 
                AND verification_code IS NOT NULL 
                AND verification_code_expires_at > NOW()
            ");
            $recentVerificationCheck->bind_param("i", $user_id);
            $recentVerificationCheck->execute();
            $recentResult = $recentVerificationCheck->get_result();
            
            if ($recentResult->num_rows > 0) {
                return array(
                    'success' => false, 
                    'message' => 'A verification code is already active and has not expired. Please check your email or wait for the current code to expire.',
                    'rate_limited' => true,
                    'wait_time_minutes' => 15
                );
            }
            
            // Generate verification code
            $verification_code = sprintf('%06d', mt_rand(100000, 999999));
            
            // Store verification code with MySQL time to avoid timezone issues
            $stmt = $this->db->prepare("UPDATE users SET verification_code = ?, verification_code_expires_at = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE id = ?");
            $stmt->bind_param("si", $verification_code, $user_id);
            $stmt->execute();
            
            // Send email using Direct PHPMailer (WORKING!)
            try {
                // Force production mode for Direct PHPMailer since it's working
                // Check if we should use development mode (only if explicitly disabled)
                $forceProductionMode = true; // Force Direct PHPMailer usage
                $isDevelopmentMode = !$forceProductionMode && 
                                   ($_ENV['DEVELOPMENT_MODE'] ?? 'true') === 'true' && 
                                   (empty($_ENV['SMTP_USERNAME']) || empty($_ENV['SMTP_PASSWORD']));
                
                if ($isDevelopmentMode) {
                    // Development fallback: just create the email file without trying SMTP
                    $baseUrl = $_ENV['BASE_URL'] ?? 'http://localhost:8080/dental-clinic-chmsu-thesis';
                    $verification_link = $baseUrl . '/public/auth/verify-email-code.php?email=' . urlencode($email) . '&code=' . $verification_code;
                    
                    // Save email to file for development
                    $this->saveEmailToFile($email, $user['name'], $verification_code, $verification_link);
                    
                    $this->logActivity($user_id, 'EMAIL_VERIFICATION_DEV', 'Email verification code generated (development mode)');
                    
                    return [
                        'success' => true, 
                        'message' => 'Verification code generated. Check the emails folder for the code.',
                        'code_expires_minutes' => 15,
                        'verification_link' => $verification_link,
                        'development_mode' => true
                    ];
                }
                
                // Production mode: use PHPMailer library
                require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
                
                try {
                    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                    
                    // Load SMTP configuration
                    $configFile = dirname(__DIR__, 2) . '/sendmail-config.ini';
                    
                    if (!file_exists($configFile)) {
                        throw new Exception("SMTP configuration file not found: $configFile");
                    }
                    
                    $config = parse_ini_file($configFile, true);
                    if ($config === false) {
                        throw new Exception("Failed to parse SMTP configuration file");
                    }
                    
                    if (!isset($config['sendmail'])) {
                        throw new Exception("Missing [sendmail] section in configuration file");
                    }
                    
                    $smtpConfig = $config['sendmail'];
                    
                    // Server settings
                    $mail->isSMTP();
                    $mail->Host = $smtpConfig['smtp_server'];
                    $mail->SMTPAuth = true;
                    $mail->Username = $smtpConfig['auth_username'];
                    $mail->Password = $smtpConfig['auth_password'];
                    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = $smtpConfig['smtp_port'];
                    
                    // Recipients
                    $mail->setFrom($smtpConfig['force_sender'], 'Dental Clinic Management');
                    $mail->addAddress($email, $user['name']);
                    
                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = 'Your Email Verification Code - Dental Clinic Management';
                    $baseUrl = $_ENV['BASE_URL'] ?? 'http://localhost:8080/dental-clinic-chmsu-thesis';
                    $verificationLink = $baseUrl . '/public/auth/verify-email-code.php?email=' . urlencode($email) . '&code=' . $verification_code;
                    $mail->Body = $this->generateEmailHtml($user['name'], $verification_code, $verificationLink);
                    $mail->AltBody = "Your verification code: $verification_code\n\nVerification link: $verificationLink";
                    
                    $mail->send();
                    $emailResult = ['success' => true, 'message' => 'Email sent via PHPMailer', 'verification_link' => $verificationLink];
                    
                } catch (\PHPMailer\PHPMailer\Exception $e) {
                    $emailResult = ['success' => false, 'message' => 'PHPMailer error: ' . $e->getMessage()];
                }
                
                if ($emailResult['success']) {
                    $this->logActivity($user_id, 'EMAIL_VERIFICATION_SENT', 'Email verification code sent successfully via SMTP');
                    
                    $response = [
                        'success' => true, 
                        'message' => 'Verification code sent successfully. Please check your email and enter the 6-digit code.',
                        'code_expires_minutes' => 15
                    ];
                    
                    // Include verification link if provided
                    if (isset($emailResult['verification_link'])) {
                        $response['verification_link'] = $emailResult['verification_link'];
                    }
                    
                    return $response;
                } else if (isset($emailResult['fallback']) && $emailResult['fallback'] && isset($emailResult['verification_link'])) {
                    // SMTP failed but fallback email file was created - treat as partial success
                    $this->logActivity($user_id, 'EMAIL_VERIFICATION_FALLBACK', 'SMTP failed, email saved to file as fallback');
                    
                    $response = [
                        'success' => true, 
                        'message' => 'Verification code prepared. You can verify your email using the verification link.',
                        'code_expires_minutes' => 15,
                        'verification_link' => $emailResult['verification_link'],
                        'fallback_mode' => true
                    ];
                    
                    return $response;
                } else {
                    $this->logActivity($user_id, 'EMAIL_VERIFICATION_FAILED', 'Email verification code failed to send: ' . $emailResult['message']);
                    
                    return array(
                        'success' => false, 
                        'message' => 'Failed to send verification code. Please try again later.',
                        'error_details' => $emailResult['message']
                    );
                }
                
            } catch (Exception $emailEx) {
                // Final fallback
                $baseUrl = $_ENV['BASE_URL'] ?? 'http://localhost:8080/dental-clinic-chmsu-thesis';
                $verification_link = $baseUrl . '/auth/verify-email-code.php?email=' . urlencode($email) . '&code=' . $verification_code;
                error_log("DirectPHPMailerService failed. Verification link for {$email}: {$verification_link}");
                error_log("DirectPHPMailerService error: " . $emailEx->getMessage());
                
                $this->logActivity($user_id, 'EMAIL_VERIFICATION_FALLBACK', 'Email service unavailable, fallback to logging');
                
                return array(
                    'success' => false, 
                    'message' => 'Email service temporarily unavailable. Please contact support.',
                    'error_details' => 'Email service configuration issue'
                );
            }
            
        } catch (Exception $e) {
            error_log("Send email verification error: " . $e->getMessage());
            return array('success' => false, 'message' => 'Failed to send verification email');
        }
    }
    
    /**
     * Verify email with token
     */
    public function verifyEmail() {
        $token = isset($_GET['token']) ? trim($_GET['token']) : '';
        
        if (empty($token)) {
            return array('success' => false, 'message' => 'Invalid verification token');
        }
        
        try {
            // Find user with this token
            $stmt = $this->db->prepare("SELECT id, name, email FROM users WHERE verification_token = ? AND status = 'active'");
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                return array('success' => false, 'message' => 'Invalid or expired verification token');
            }
            
            $user = $result->fetch_assoc();
            
            // Mark email as verified and clear token
            $stmt = $this->db->prepare("UPDATE users SET email_verified = 1, verification_token = NULL WHERE id = ?");
            $stmt->bind_param("i", $user['id']);
            $stmt->execute();
            
            $this->logActivity($user['id'], 'EMAIL_VERIFIED', 'Email address verified successfully');
            
            return array(
                'success' => true, 
                'message' => 'Email verified successfully! You can now login.',
                'user_name' => $user['name']
            );
            
        } catch (Exception $e) {
            error_log("Email verification error: " . $e->getMessage());
            return array('success' => false, 'message' => 'Email verification failed');
        }
    }
    
    /**
     * Get branches for dropdown
     */
    public function getBranches() {
        try {
            $stmt = $this->db->prepare("SELECT id, name, location FROM branches WHERE status = 'active' ORDER BY name");
            $stmt->execute();
            $result = $stmt->get_result();
            
            $branches = array();
            while ($row = $result->fetch_assoc()) {
                $branches[] = $row;
            }
            
            return array('success' => true, 'branches' => $branches);
            
        } catch (Exception $e) {
            error_log("Get branches error: " . $e->getMessage());
            return array('success' => false, 'message' => 'Failed to load branches');
        }
    }
    
    /**
     * Log user activity
     */
    private function logActivity($user_id, $action, $description) {
        try {
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $stmt = $this->db->prepare("INSERT INTO system_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $user_id, $action, $description, $ip_address);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("Log activity error: " . $e->getMessage());
        }
    }
    
    /**
     * Save email to file for development mode
     */
    private function saveEmailToFile($email, $userName, $verificationCode, $verificationLink) {
        try {
            $emailsDir = dirname(__DIR__, 2) . '/emails';
            if (!is_dir($emailsDir)) {
                mkdir($emailsDir, 0755, true);
            }
            
            $timestamp = date('Y-m-d_H-i-s');
            $safeEmail = str_replace(['@', '.'], ['_at_', '_'], $email);
            $filename = "{$timestamp}_{$safeEmail}.html";
            $filepath = $emailsDir . '/' . $filename;
            
            $emailContent = $this->generateEmailHtml($userName, $verificationCode, $verificationLink);
            
            file_put_contents($filepath, $emailContent);
            
            return $filepath;
        } catch (Exception $e) {
            error_log("Save email to file error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate and send OTP for login verification
     */
    public function generateLoginOTP($userId, $email, $userName) {
        // Generate unique tracking ID for this OTP generation
        $trackingId = uniqid('otp_', true);
        
        // Debug: Log when this method is called with stack trace
        $debugFile = dirname(__DIR__, 2) . '/debug_otp.log';
        $stackTrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        $caller = isset($stackTrace[1]) ? $stackTrace[1]['function'] : 'unknown';
        file_put_contents($debugFile, "[" . date('Y-m-d H:i:s.u') . "] [$trackingId] generateLoginOTP called for user $userId ($email) from $caller\n", FILE_APPEND);
        
        try {
            // Generate 6-digit OTP
            $otp = sprintf('%06d', random_int(100000, 999999));
            $expiresAt = date('Y-m-d H:i:s', time() + 300); // 5 minutes from now
            
            // Store OTP in database
            $stmt = $this->db->prepare("
                UPDATE users 
                SET login_otp = ?, 
                    login_otp_expires_at = ?, 
                    login_otp_attempts = 0, 
                    login_otp_created_at = NOW() 
                WHERE id = ?
            ");
            $stmt->bind_param('ssi', $otp, $expiresAt, $userId);
            $stmt->execute();
            
            // Send OTP via email using PHPMailer
            require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
            
            try {
                $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                
                // Load SMTP configuration
                $configFile = dirname(__DIR__, 2) . '/sendmail-config.ini';
                
                if (!file_exists($configFile)) {
                    throw new Exception("SMTP configuration file not found: $configFile");
                }
                
                $config = parse_ini_file($configFile, true);
                if ($config === false) {
                    throw new Exception("Failed to parse SMTP configuration file");
                }
                
                if (!isset($config['sendmail'])) {
                    throw new Exception("Missing [sendmail] section in configuration file");
                }
                
                $smtpConfig = $config['sendmail'];
                
                // Server settings
                $mail->isSMTP();
                $mail->Host = $smtpConfig['smtp_server'];
                $mail->SMTPAuth = true;
                $mail->Username = $smtpConfig['auth_username'];
                $mail->Password = $smtpConfig['auth_password'];
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = $smtpConfig['smtp_port'];
                
                // Recipients
                $mail->setFrom($smtpConfig['force_sender'], 'Dental Clinic Management');
                $mail->addAddress($email, $userName);
                
                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Your Login OTP - Dental Clinic Management';
                $mail->Body = $this->generateLoginOTPEmailHtml($userName, $otp);
                $mail->AltBody = "Your Login OTP: $otp\n\nThis OTP will expire in 5 minutes.";
                
                // Debug: Log before sending
                $debugFile = dirname(__DIR__, 2) . '/debug_otp.log';
                file_put_contents($debugFile, "[" . date('Y-m-d H:i:s.u') . "] [$trackingId] About to send OTP email to $email with OTP: $otp (from PHPMailer)\n", FILE_APPEND);
                
                $mail->send();
                
                // Debug: Log after successful send
                file_put_contents($debugFile, "[" . date('Y-m-d H:i:s.u') . "] [$trackingId] OTP email sent successfully to $email via PHPMailer\n", FILE_APPEND);
                
                $this->logActivity($userId, 'LOGIN_OTP_SENT', 'Login OTP sent successfully to ' . $email . ' via PHPMailer');
                return array(
                    'success' => true,
                    'message' => 'OTP sent to your email address',
                    'expires_in_minutes' => 5
                );
                
            } catch (\PHPMailer\PHPMailer\Exception $e) {
                error_log("PHPMailer OTP sending error: " . $e->getMessage());
                file_put_contents($debugFile, "[" . date('Y-m-d H:i:s.u') . "] [$trackingId] PHPMailer error: " . $e->getMessage() . "\n", FILE_APPEND);
                $this->logActivity($userId, 'LOGIN_OTP_FAILED', 'Failed to send login OTP to ' . $email . ': ' . $e->getMessage());
                return array(
                    'success' => false,
                    'message' => 'Failed to send OTP. Please try again.'
                );
            }
            
        } catch (Exception $e) {
            error_log("Generate login OTP error: " . $e->getMessage());
            file_put_contents($debugFile, "[" . date('Y-m-d H:i:s.u') . "] [$trackingId] General error: " . $e->getMessage() . "\n", FILE_APPEND);
            return array(
                'success' => false,
                'message' => 'Error generating OTP. Please try again.'
            );
        }
    }
    
    /**
     * Verify login OTP
     */
    public function verifyLoginOTP() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return array('success' => false, 'message' => 'Invalid request method');
        }
        
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $otp = isset($_POST['otp']) ? trim($_POST['otp']) : '';
        
        if (empty($email) || empty($otp)) {
            return array('success' => false, 'message' => 'Email and OTP are required');
        }
        
        try {
            // Get user and OTP data
            $stmt = $this->db->prepare("
                SELECT id, name, role, branch_id, login_otp, login_otp_expires_at, login_otp_attempts 
                FROM users 
                WHERE email = ? AND status = 'active' AND email_verified = 1
            ");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                return array('success' => false, 'message' => 'Invalid request');
            }
            
            $user = $result->fetch_assoc();
            
            // Check if OTP exists and hasn't expired
            if (empty($user['login_otp']) || empty($user['login_otp_expires_at'])) {
                return array('success' => false, 'message' => 'No valid OTP found. Please request a new one.');
            }
            
            // Check if OTP has expired
            $now = new DateTime();
            $expiresAt = new DateTime($user['login_otp_expires_at']);
            if ($now > $expiresAt) {
                // Clear expired OTP
                $this->clearLoginOTP($user['id']);
                return array('success' => false, 'message' => 'OTP has expired. Please request a new one.');
            }
            
            // Check attempt limit (max 3 attempts)
            if ($user['login_otp_attempts'] >= 3) {
                $this->clearLoginOTP($user['id']);
                return array('success' => false, 'message' => 'Too many failed attempts. Please request a new OTP.');
            }
            
            // Verify OTP
            if ($otp !== $user['login_otp']) {
                // Increment failed attempts
                $stmt = $this->db->prepare("UPDATE users SET login_otp_attempts = login_otp_attempts + 1 WHERE id = ?");
                $stmt->bind_param('i', $user['id']);
                $stmt->execute();
                
                $attemptsLeft = 3 - ($user['login_otp_attempts'] + 1);
                return array(
                    'success' => false, 
                    'message' => "Invalid OTP. You have $attemptsLeft attempts left."
                );
            }
            
            // OTP is valid - clear it and create session
            $this->clearLoginOTP($user['id']);
            
            // Create session
            if (!defined('NO_SESSION')) {
                if (session_status() === PHP_SESSION_NONE) {
                    @session_start();
                }
                createSession($user['id'], $user['branch_id'] ?? null, $user['role'], $email, $user['name']);
            }
            
            // Log successful login
            $this->logActivity($user['id'], 'LOGIN_OTP_VERIFIED', 'User logged in successfully with OTP');
            
            // Return redirect based on role
            $redirect = '';
            if ($user['role'] === ROLE_PATIENT) {
                $redirect = '../dashboard/clinic-listing.php';
            } elseif ($user['role'] === ROLE_STAFF) {
                $redirect = '../dashboard/staff-dashboard.php';
            } elseif ($user['role'] === ROLE_DENTIST) {
                $redirect = '../dashboard/dentist-dashboard.php';
            } elseif ($user['role'] === ROLE_ADMIN) {
                $redirect = '../dashboard/admin-dashboard.php';
            }
            
            return array(
                'success' => true,
                'message' => 'Login successful',
                'redirect' => $redirect,
                'user' => array(
                    'id' => $user['id'],
                    'role' => $user['role'],
                    'branch_id' => $user['branch_id'],
                    'name' => $user['name']
                )
            );
            
        } catch (Exception $e) {
            error_log("Verify login OTP error: " . $e->getMessage());
            return array('success' => false, 'message' => 'Error verifying OTP. Please try again.');
        }
    }
    
    /**
     * Clear login OTP from database
     */
    private function clearLoginOTP($userId) {
        try {
            $stmt = $this->db->prepare("
                UPDATE users 
                SET login_otp = NULL, 
                    login_otp_expires_at = NULL, 
                    login_otp_attempts = 0 
                WHERE id = ?
            ");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("Clear login OTP error: " . $e->getMessage());
        }
    }
    
    /**
     * Resend login OTP - dedicated endpoint to avoid duplicate sending
     */
    public function resendLoginOTP() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return array('success' => false, 'message' => 'Invalid request method');
        }
        
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        
        if (empty($email)) {
            return array('success' => false, 'message' => 'Email is required');
        }
        
        try {
            // Get user details
            $stmt = $this->db->prepare("
                SELECT id, name, email, login_otp_created_at 
                FROM users 
                WHERE email = ? AND status = 'active' AND email_verified = 1
            ");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                return array('success' => false, 'message' => 'Invalid request');
            }
            
            $user = $result->fetch_assoc();
            
            // Rate limiting: prevent too frequent resend requests (minimum 30 seconds between requests)
            if (!empty($user['login_otp_created_at'])) {
                $lastOtpTime = new DateTime($user['login_otp_created_at']);
                $now = new DateTime();
                $timeDiff = $now->getTimestamp() - $lastOtpTime->getTimestamp();
                
                if ($timeDiff < 30) {
                    $waitTime = 30 - $timeDiff;
                    return array(
                        'success' => false, 
                        'message' => "Please wait {$waitTime} seconds before requesting a new OTP.",
                        'rate_limited' => true,
                        'wait_seconds' => $waitTime
                    );
                }
            }
            
            // Generate new OTP using existing method
            $otpResult = $this->generateLoginOTP($user['id'], $user['email'], $user['name']);
            
            // Log the OTP resend for debugging
            error_log("Resend Login OTP for user {$user['id']} ({$user['email']}): " . ($otpResult['success'] ? 'SUCCESS' : 'FAILED'));
            
            return $otpResult;
            
        } catch (Exception $e) {
            error_log("Resend login OTP error: " . $e->getMessage());
            return array('success' => false, 'message' => 'Error resending OTP. Please try again.');
        }
    }
    
    /**
     * Generate email HTML content
     */
    private function generateEmailHtml($userName, $verificationCode, $verificationLink) {
        return "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Email Verification - Dental Clinic Management</title>
</head>
<body style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
    <div style='background: #054A91; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0;'>
        <h1 style='margin: 0; font-size: 24px;'>🦷 Dental Clinic Management</h1>
        <p style='margin: 10px 0 0 0; font-size: 16px;'>Email Verification Required</p>
    </div>
    
    <div style='background: #f8f9fa; padding: 30px; border-radius: 0 0 8px 8px; border: 1px solid #dee2e6;'>
        <h2 style='color: #054A91; margin-top: 0;'>Hello {$userName}!</h2>
        
        <p style='font-size: 16px; line-height: 1.5; color: #333;'>
            Thank you for registering with our Dental Clinic Management System. 
            To complete your registration, please verify your email address.
        </p>
        
        <div style='background: white; padding: 20px; border-radius: 8px; border: 2px solid #054A91; text-align: center; margin: 20px 0;'>
            <h3 style='margin: 0 0 10px 0; color: #054A91;'>Your verification code is:</h3>
            <div style='font-size: 32px; font-weight: bold; letter-spacing: 8px; color: #054A91; font-family: monospace;'>
                <strong>{$verificationCode}</strong>
            </div>
            <p style='margin: 10px 0 0 0; font-size: 14px; color: #666;'>
                This code will expire in 15 minutes
            </p>
        </div>
        
        <p style='font-size: 14px; color: #666; text-align: center;'>
            <a href='{$verificationLink}' style='color: #054A91; text-decoration: none; font-weight: bold;'>
                Or click here to verify directly
            </a>
        </p>
        
        <hr style='border: none; height: 1px; background: #dee2e6; margin: 20px 0;'>
        
        <p style='font-size: 12px; color: #666; text-align: center;'>
            If you didn't create an account, you can safely ignore this email.
        </p>
    </div>
</body>
</html>";
    }
    
    /**
     * Generate login OTP email HTML content
     */
    private function generateLoginOTPEmailHtml($userName, $otp) {
        return "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Login OTP - Dental Clinic Management</title>
</head>
<body style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f5f5f5;'>
    <div style='background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1);'>
        <div style='background: #054A91; color: white; padding: 30px; text-align: center;'>
            <h1 style='margin: 0; font-size: 28px;'>🦷 Dental Clinic Management</h1>
            <p style='margin: 10px 0 0 0; font-size: 18px; opacity: 0.9;'>Login Verification Required</p>
            <p style='margin: 5px 0 0 0; font-size: 14px; opacity: 0.7;'>⚡ Powered by PHPMailer</p>
        </div>
        
        <div style='padding: 40px 30px;'>
            <h2 style='color: #054A91; margin-top: 0; font-size: 24px;'>Hello {$userName}!</h2>
            
            <p style='font-size: 16px; line-height: 1.6; color: #333; margin-bottom: 25px;'>
                You are attempting to log in to your Dental Clinic Management account. 
                Please use the following One-Time Password (OTP) to complete your login.
            </p>
            
            <div style='background: #f8f9ff; border: 2px solid #054A91; border-radius: 10px; padding: 25px; text-align: center; margin: 25px 0;'>
                <h3 style='margin: 0 0 15px 0; color: #054A91; font-size: 18px;'>Your Login OTP is:</h3>
                <div style='font-size: 36px; font-weight: bold; letter-spacing: 6px; color: #054A91; font-family: monospace; margin: 15px 0;'>
                    {$otp}
                </div>
                <p style='margin: 15px 0 0 0; font-size: 14px; color: #666;'>
                    ⏰ This OTP will expire in 5 minutes
                </p>
            </div>
            
            <div style='background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; padding: 15px; margin: 25px 0;'>
                <p style='margin: 0; color: #856404; font-size: 14px;'>
                    <strong>🔒 Security Notice:</strong> This OTP is for one-time use only. 
                    Do not share this code with anyone. If you didn't attempt to log in, please ignore this email.
                </p>
            </div>
            
            <div style='background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; padding: 15px; margin: 25px 0;'>
                <p style='margin: 0; font-size: 13px; color: #155724;'>
                    <strong>📧 PHPMailer:</strong> This email was sent using PHPMailer with SMTP authentication.<br>
                    🚀 Reliable Gmail delivery with proper error handling!
                </p>
            </div>
            
            <hr style='border: none; height: 1px; background: #e0e0e0; margin: 25px 0;'>
            
            <p style='font-size: 12px; color: #666; text-align: center; margin: 0;'>
                This is an automated message from Dental Clinic Management System.<br>
                Do not reply to this email.<br>
                <strong>© 2025 Dental Clinic Management System - PHPMailer</strong>
            </p>
        </div>
    </div>
</body>
</html>";
    }
}
?>