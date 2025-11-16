<?php
/**
 * Direct PHPMailer Service using sendmail.exe
 * This directly executes sendmail.exe to bypass PHP mail() issues
 */

class DirectPHPMailerService {
    private $fromEmail;
    private $fromName;
    private $debug;
    private $sendmailPath;
    
    public function __construct() {
        $this->fromEmail = 'jkmoraca.personaluse@gmail.com';
        $this->fromName = 'Dental Clinic Management';
        $this->debug = true;
        $this->sendmailPath = 'C:\xampp\sendmail\sendmail.exe';
    }
    
    public function sendVerificationEmail($to, $userName, $verificationCode) {
        try {
            // Create verification link
            $baseUrl = $_ENV['BASE_URL'] ?? 'http://localhost:8080/dental-clinic-chmsu-thesis';
            $verificationLink = $baseUrl . '/public/auth/verify-email-code.php?email=' . urlencode($to) . '&code=' . $verificationCode;
            
            // Email content
            $subject = 'Your Email Verification Code - Dental Clinic Management';
            $htmlBody = $this->createEmailContent($userName, $verificationCode, $verificationLink);
            
            if ($this->debug) {
                echo "🔄 Direct PHPMailer: Sending email via direct sendmail.exe execution...\n";
            }
            
            // Send via direct sendmail.exe execution
            $result = $this->sendViaDirectSendmail($to, $subject, $htmlBody);
            
            if ($result['success']) {
                if ($this->debug) {
                    echo "✅ Direct PHPMailer: Email sent successfully!\n";
                }
                
                // Save backup file
                $this->saveEmailToFile($to, $htmlBody, $verificationCode);
                
                return [
                    'success' => true,
                    'message' => 'Email sent successfully via Direct PHPMailer',
                    'verification_link' => $verificationLink,
                    'method' => 'direct_phpmailer_sendmail'
                ];
            } else {
                if ($this->debug) {
                    echo "❌ Direct PHPMailer failed: " . $result['error'] . "\n";
                }
                
                // Save backup file anyway
                $filePath = $this->saveEmailToFile($to, $htmlBody, $verificationCode);
                
                return [
                    'success' => false,
                    'message' => 'Direct PHPMailer failed: ' . $result['error'],
                    'verification_link' => $verificationLink,
                    'email_file' => $filePath,
                    'fallback' => true
                ];
            }
            
        } catch (Exception $e) {
            error_log("Direct PHPMailer service error: " . $e->getMessage());
            
            $filePath = $this->saveEmailToFile($to, $htmlBody ?? '', $verificationCode);
            
            return [
                'success' => false,
                'message' => 'Direct PHPMailer error: ' . $e->getMessage(),
                'verification_link' => $verificationLink ?? '',
                'email_file' => $filePath
            ];
        }
    }
    
    private function sendViaDirectSendmail($to, $subject, $body) {
        try {
            if (!file_exists($this->sendmailPath)) {
                return ['success' => false, 'error' => 'sendmail.exe not found at: ' . $this->sendmailPath];
            }
            
            if ($this->debug) {
                echo "📧 Using sendmail.exe: {$this->sendmailPath}\n";
            }
            
            // Create the email message
            $message = $this->formatEmailMessage($to, $subject, $body);
            
            // Create temporary file for email content
            $tempFile = tempnam(sys_get_temp_dir(), 'phpmailer_');
            file_put_contents($tempFile, $message);
            
            if ($this->debug) {
                echo "📝 Email message saved to temp file: $tempFile\n";
            }
            
            // Execute sendmail.exe with the email file
            $command = "\"{$this->sendmailPath}\" -t < \"$tempFile\"";
            
            if ($this->debug) {
                echo "🚀 Executing: $command\n";
            }
            
            // Execute the command
            $output = [];
            $returnVar = 0;
            exec($command . ' 2>&1', $output, $returnVar);
            
            // Clean up temp file
            unlink($tempFile);
            
            if ($this->debug) {
                echo "📊 Return code: $returnVar\n";
                echo "📊 Output: " . implode("\n", $output) . "\n";
            }
            
            if ($returnVar === 0) {
                return ['success' => true, 'message' => 'Email sent via sendmail.exe'];
            } else {
                return ['success' => false, 'error' => 'sendmail.exe failed with code ' . $returnVar . ': ' . implode(' ', $output)];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Direct sendmail execution error: ' . $e->getMessage()];
        }
    }
    
    private function formatEmailMessage($to, $subject, $body) {
        $headers = "From: {$this->fromName} <{$this->fromEmail}>\r\n";
        $headers .= "To: $to\r\n";
        $headers .= "Subject: $subject\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "Content-Transfer-Encoding: 8bit\r\n";
        $headers .= "X-Mailer: Direct PHPMailer v1.0\r\n";
        $headers .= "Date: " . date('r') . "\r\n";
        $headers .= "\r\n";
        $headers .= $body;
        
        return $headers;
    }
    
    private function createEmailContent($userName, $verificationCode, $verificationLink) {
        return "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Email Verification - Dental Clinic Management</title>
</head>
<body style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f5f5f5;'>
    <div style='background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1);'>
        <div style='background: #054A91; color: white; padding: 30px; text-align: center;'>
            <h1 style='margin: 0; font-size: 28px;'>🦷 Dental Clinic Management</h1>
            <p style='margin: 10px 0 0 0; font-size: 18px; opacity: 0.9;'>Email Verification Required</p>
            <p style='margin: 5px 0 0 0; font-size: 14px; opacity: 0.7;'>⚡ Powered by Direct PHPMailer</p>
        </div>
        
        <div style='padding: 40px 30px;'>
            <h2 style='color: #054A91; margin-top: 0; font-size: 24px;'>Hello {$userName}!</h2>
            
            <p style='font-size: 16px; line-height: 1.6; color: #333; margin-bottom: 25px;'>
                Thank you for registering with our Dental Clinic Management System. 
                To complete your registration and secure your account, please verify your email address.
            </p>
            
            <div style='background: #f8f9ff; border: 2px solid #054A91; border-radius: 10px; padding: 25px; text-align: center; margin: 25px 0;'>
                <h3 style='margin: 0 0 15px 0; color: #054A91; font-size: 18px;'>Your verification code is:</h3>
                <div style='font-size: 36px; font-weight: bold; letter-spacing: 6px; color: #054A91; font-family: monospace; margin: 15px 0;'>
                    {$verificationCode}
                </div>
                <p style='margin: 15px 0 0 0; font-size: 14px; color: #666;'>
                    ⏰ This code will expire in 15 minutes
                </p>
            </div>
            
            <div style='text-align: center; margin: 30px 0;'>
                <a href='{$verificationLink}' style='background: linear-gradient(135deg, #054A91, #3E7CB1); color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 16px; display: inline-block; box-shadow: 0 4px 8px rgba(5,74,145,0.3);'>
                    ✅ Verify Email Address Now
                </a>
            </div>
            
            <div style='background: #e8f4f8; border-left: 4px solid #054A91; padding: 15px; margin: 25px 0;'>
                <p style='margin: 0; font-size: 14px; color: #054A91;'>
                    <strong>📧 Alternative:</strong> If the button doesn't work, copy this link:<br>
                    <a href='{$verificationLink}' style='color: #054A91; word-break: break-all;'>{$verificationLink}</a>
                </p>
            </div>
            
            <div style='background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; padding: 15px; margin: 25px 0;'>
                <p style='margin: 0; font-size: 13px; color: #856404;'>
                    <strong>📱 Direct PHPMailer:</strong> This email was sent using Direct PHPMailer with sendmail.exe execution.<br>
                    🚀 Bypassing PHP mail() for reliable Gmail delivery!
                </p>
            </div>
            
            <hr style='border: none; height: 1px; background: #e0e0e0; margin: 25px 0;'>
            
            <p style='font-size: 12px; color: #666; text-align: center; margin: 0;'>
                If you didn't create an account, you can safely ignore this email.<br>
                This message was sent from an automated system - please do not reply.<br>
                <strong>© 2025 Dental Clinic Management System - Direct PHPMailer</strong>
            </p>
        </div>
    </div>
</body>
</html>";
    }
    
    private function saveEmailToFile($to, $htmlBody, $verificationCode) {
        try {
            $emailsDir = dirname(__DIR__, 2) . '/emails';
            if (!is_dir($emailsDir)) {
                mkdir($emailsDir, 0755, true);
            }
            
            $timestamp = date('Y-m-d_H-i-s');
            $safeEmail = str_replace(['@', '.'], ['_at_', '_'], $to);
            $filename = "{$timestamp}_{$safeEmail}_direct_phpmailer.html";
            $filepath = $emailsDir . '/' . $filename;
            
            file_put_contents($filepath, $htmlBody);
            
            if ($this->debug) {
                echo "💾 Email saved to: $filepath (Code: $verificationCode)\n";
            }
            
            return $filepath;
        } catch (Exception $e) {
            error_log("Direct PHPMailer save email to file error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send Login OTP email
     */
    public function sendLoginOTP($to, $userName, $otp) {
        try {
            // Email content
            $subject = 'Your Login OTP - Dental Clinic Management';
            $htmlBody = $this->createLoginOTPContent($userName, $otp);
            
            if ($this->debug) {
                echo "🔄 Direct PHPMailer: Sending login OTP via direct sendmail.exe execution...\n";
            }
            
            // Send via direct sendmail.exe execution
            $result = $this->sendViaDirectSendmail($to, $subject, $htmlBody);
            
            if ($result['success']) {
                if ($this->debug) {
                    echo "✅ Direct PHPMailer: Login OTP sent successfully!\n";
                }
                
                // Save backup file
                $this->saveOTPEmailToFile($to, $htmlBody, $otp);
                
                return [
                    'success' => true,
                    'message' => 'Login OTP sent successfully via Direct PHPMailer',
                    'method' => 'direct_phpmailer_sendmail'
                ];
            } else {
                if ($this->debug) {
                    echo "❌ Direct PHPMailer: Login OTP failed: " . $result['message'] . "\n";
                }
                
                return [
                    'success' => false,
                    'message' => 'Failed to send login OTP: ' . $result['message'],
                    'method' => 'direct_phpmailer_sendmail'
                ];
            }
            
        } catch (Exception $e) {
            if ($this->debug) {
                echo "❌ Direct PHPMailer Exception: " . $e->getMessage() . "\n";
            }
            
            return [
                'success' => false,
                'message' => 'Exception occurred while sending login OTP: ' . $e->getMessage(),
                'method' => 'direct_phpmailer_sendmail'
            ];
        }
    }
    
    /**
     * Create login OTP email content
     */
    private function createLoginOTPContent($userName, $otp) {
        return "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Login OTP - Dental Clinic Management</title>
</head>
<body style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
    <div style='background: #054A91; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0;'>
        <h1 style='margin: 0; font-size: 24px;'>🦷 Dental Clinic Management</h1>
        <p style='margin: 10px 0 0 0; font-size: 16px;'>Login Verification Required</p>
    </div>
    
    <div style='background: #f8f9fa; padding: 30px; border-radius: 0 0 8px 8px; border: 1px solid #dee2e6;'>
        <h2 style='color: #054A91; margin-top: 0;'>Hello {$userName}!</h2>
        
        <p style='font-size: 16px; line-height: 1.5; color: #333;'>
            You are attempting to log in to your Dental Clinic Management account. 
            Please use the following One-Time Password (OTP) to complete your login.
        </p>
        
        <div style='background: white; padding: 25px; border-radius: 8px; border: 2px solid #054A91; text-align: center; margin: 20px 0;'>
            <h3 style='margin: 0 0 15px 0; color: #054A91;'>Your Login OTP is:</h3>
            <div style='font-size: 36px; font-weight: bold; letter-spacing: 8px; color: #054A91; font-family: monospace;'>
                <strong>{$otp}</strong>
            </div>
            <p style='margin: 15px 0 0 0; font-size: 14px; color: #666;'>
                This OTP will expire in 5 minutes
            </p>
        </div>
        
        <div style='background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; padding: 15px; margin: 20px 0;'>
            <p style='margin: 0; color: #856404; font-size: 14px;'>
                <strong>🔒 Security Notice:</strong> This OTP is for one-time use only. 
                Do not share this code with anyone. If you didn't attempt to log in, please ignore this email.
            </p>
        </div>
        
        <hr style='border: none; height: 1px; background: #dee2e6; margin: 20px 0;'>
        
        <p style='font-size: 12px; color: #666; text-align: center;'>
            This is an automated message from Dental Clinic Management System.
        </p>
    </div>
</body>
</html>";
    }
    
    /**
     * Save OTP email to file for backup
     */
    private function saveOTPEmailToFile($to, $htmlBody, $otp) {
        try {
            $emailsDir = dirname(__DIR__, 2) . '/emails';
            if (!is_dir($emailsDir)) {
                mkdir($emailsDir, 0755, true);
            }
            
            $timestamp = date('Y-m-d_H-i-s');
            $safeEmail = str_replace(['@', '.'], ['_at_', '_'], $to);
            $filename = "{$timestamp}_{$safeEmail}_login_otp.html";
            $filepath = $emailsDir . '/' . $filename;
            
            file_put_contents($filepath, $htmlBody);
            
            if ($this->debug) {
                echo "💾 Login OTP email saved to: $filepath (OTP: $otp)\n";
            }
            
            return $filepath;
        } catch (Exception $e) {
            error_log("Direct PHPMailer save OTP email to file error: " . $e->getMessage());
            return false;
        }
    }
}

// Test the Direct PHPMailer service
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    echo "📧 Testing Direct PHPMailer Email Service\n";
    echo "==========================================\n\n";
    
    $directPHPMailer = new DirectPHPMailerService();
    $result = $directPHPMailer->sendVerificationEmail(
        'jkmoraca.work@gmail.com',
        'John Kevin Moraca',
        '555666'
    );
    
    echo "\n📊 Direct PHPMailer Results:\n";
    echo "============================\n";
    print_r($result);
    
    echo "\n💡 Direct PHPMailer Features:\n";
    echo "=============================\n";
    echo "✅ Direct sendmail.exe execution\n";
    echo "✅ Bypasses PHP mail() issues\n";
    echo "✅ Uses configured XAMPP sendmail\n";
    echo "✅ Gmail SMTP delivery\n";
    echo "✅ File backup system\n";
    
    echo "\n🎯 Email Status:\n";
    echo "===============\n";
    if ($result['success']) {
        echo "✅ Direct PHPMailer: " . $result['message'] . "\n";
        echo "📧 Verification code: 555666\n";
        echo "🔗 Verification link ready\n";
        echo "📁 Check Gmail inbox and spam folder\n";
    } else {
        echo "❌ Direct PHPMailer failed: " . $result['message'] . "\n";
        echo "📁 Check backup file for verification code\n";
    }
    
    echo "\n✅ Direct PHPMailer test completed!\n";
}
?>