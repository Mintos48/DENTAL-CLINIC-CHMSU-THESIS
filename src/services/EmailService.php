<?php
/**
 * Email Service for sending emails using PHPMailer and .env configuration
 */

// Load PHPMailer classes (you may need to install via Composer: composer require phpmailer/phpmailer)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    private $mailer;
    private $config;
    
    public function __construct() {
        $this->loadEnvConfig();
        $this->setupMailer();
    }
    
    /**
     * Load environment configuration
     */
    private function loadEnvConfig() {
        $envFile = dirname(__DIR__, 2) . '/.env';
        
        if (!file_exists($envFile)) {
            throw new Exception('Environment file not found');
        }
        
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $config = [];
        
        foreach ($lines as $line) {
            if (strpos($line, '#') === 0) continue; // Skip comments
            
            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $key = trim($parts[0]);
                $value = trim($parts[1]);
                $config[$key] = $value;
            }
        }
        
        $this->config = $config;
    }
    
    /**
     * Setup PHPMailer instance
     */
    private function setupMailer() {
        $this->mailer = new PHPMailer(true);
        
        try {
            // Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host = $this->config['MAIL_HOST'] ?? 'smtp.gmail.com';
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $this->config['MAIL_USERNAME'] ?? '';
            $this->mailer->Password = $this->config['MAIL_PASSWORD'] ?? '';
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = (int)($this->config['MAIL_PORT'] ?? 587);
            
            // Default sender
            $this->mailer->setFrom(
                $this->config['MAIL_FROM_ADDRESS'] ?? 'noreply@dentalclinic.com',
                $this->config['MAIL_FROM_NAME'] ?? 'Dental Clinic'
            );
            
            // Enable verbose debug output (disable in production)
            if (($this->config['APP_ENV'] ?? 'production') === 'development') {
                $this->mailer->SMTPDebug = SMTP::DEBUG_SERVER;
                $this->mailer->Debugoutput = function($str, $level) {
                    error_log("SMTP Debug: $str");
                };
            }
            
        } catch (Exception $e) {
            error_log("Email setup error: " . $e->getMessage());
            throw new Exception("Failed to setup email service");
        }
    }
    
    /**
     * Send a generic email
     */
    public function sendEmail($to, $subject, $body, $altBody = null) {
        try {
            // Clear any previous recipients
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            
            // Recipients
            $this->mailer->addAddress($to);
            
            // Content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;
            
            if ($altBody) {
                $this->mailer->AltBody = $altBody;
            } else {
                // Generate plain text version from HTML
                $this->mailer->AltBody = strip_tags($body);
            }
            
            // Send email
            $result = $this->mailer->send();
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Email sent successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to send email'
                ];
            }
            
        } catch (Exception $e) {
            error_log("Email sending error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Email sending failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Send verification email
     */
    public function sendVerificationEmail($to, $userName, $verificationToken) {
        try {
            // Clear any previous recipients
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            
            // Recipients
            $this->mailer->addAddress($to, $userName);
            
            // Content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Verify Your Email Address - Dental Clinic Management';
            
            $verificationLink = ($this->config['BASE_URL'] ?? 'http://localhost') . 
                              '/auth/verify-email.php?token=' . $verificationToken;
            
            $this->mailer->Body = $this->getVerificationEmailTemplate($userName, $verificationLink);
            $this->mailer->AltBody = $this->getVerificationEmailText($userName, $verificationLink);
            
            // Send email
            $result = $this->mailer->send();
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Verification email sent successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to send email'
                ];
            }
            
        } catch (Exception $e) {
            error_log("Email sending error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Email sending failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get HTML email template for verification
     */
    private function getVerificationEmailTemplate($userName, $verificationLink) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Email Verification</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; margin: 0; padding: 20px; background-color: #f4f4f4;'>
            <div style='max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1);'>
                <!-- Header -->
                <div style='background: linear-gradient(135deg, #054A91, #3E7CB1); padding: 30px; text-align: center;'>
                    <h1 style='color: white; margin: 0; font-size: 24px;'>
                        🦷 Dental Clinic Management
                    </h1>
                    <p style='color: rgba(255,255,255,0.9); margin: 10px 0 0 0; font-size: 16px;'>
                        Email Verification Required
                    </p>
                </div>
                
                <!-- Content -->
                <div style='padding: 40px 30px;'>
                    <h2 style='color: #054A91; margin: 0 0 20px 0; font-size: 20px;'>
                        Hello " . htmlspecialchars($userName) . "!
                    </h2>
                    
                    <p style='color: #333; margin: 0 0 20px 0; font-size: 16px;'>
                        Thank you for registering with our Dental Clinic Management System. To complete your registration and secure your account, please verify your email address.
                    </p>
                    
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='" . htmlspecialchars($verificationLink) . "' 
                           style='background: linear-gradient(135deg, #054A91, #3E7CB1); 
                                  color: white; 
                                  text-decoration: none; 
                                  padding: 12px 30px; 
                                  border-radius: 8px; 
                                  display: inline-block; 
                                  font-weight: bold; 
                                  font-size: 16px;
                                  transition: transform 0.2s ease;'>
                            ✉️ Verify Email Address
                        </a>
                    </div>
                    
                    <div style='background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin: 30px 0;'>
                        <p style='color: #64748b; margin: 0 0 10px 0; font-size: 14px;'>
                            <strong>Security Note:</strong>
                        </p>
                        <p style='color: #64748b; margin: 0; font-size: 14px; line-height: 1.5;'>
                            This verification link will expire in 24 hours for security reasons. If you didn't create this account, please ignore this email.
                        </p>
                    </div>
                    
                    <p style='color: #666; margin: 20px 0 0 0; font-size: 14px;'>
                        If the button doesn't work, copy and paste this link into your browser:<br>
                        <a href='" . htmlspecialchars($verificationLink) . "' style='color: #054A91; word-break: break-all;'>
                            " . htmlspecialchars($verificationLink) . "
                        </a>
                    </p>
                </div>
                
                <!-- Footer -->
                <div style='background: #f8fafc; padding: 20px 30px; border-top: 1px solid #e2e8f0; text-align: center;'>
                    <p style='color: #64748b; margin: 0; font-size: 12px;'>
                        © " . date('Y') . " Dental Clinic Management System. All rights reserved.
                    </p>
                    <p style='color: #64748b; margin: 5px 0 0 0; font-size: 12px;'>
                        This is an automated message, please do not reply to this email.
                    </p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Get plain text email for verification
     */
    private function getVerificationEmailText($userName, $verificationLink) {
        return "
Hello " . $userName . "!

Thank you for registering with our Dental Clinic Management System.

To complete your registration and secure your account, please verify your email address by clicking the link below:

" . $verificationLink . "

This verification link will expire in 24 hours for security reasons.

If you didn't create this account, please ignore this email.

---
Dental Clinic Management System
© " . date('Y') . " All rights reserved.
This is an automated message, please do not reply.
        ";
    }
    
    /**
     * Test email configuration
     */
    public function testEmailConfig() {
        try {
            // Try to connect to SMTP server
            $this->mailer->smtpConnect();
            $this->mailer->smtpClose();
            
            return [
                'success' => true,
                'message' => 'Email configuration is working correctly'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Email configuration error: ' . $e->getMessage()
            ];
        }
    }
}
?>