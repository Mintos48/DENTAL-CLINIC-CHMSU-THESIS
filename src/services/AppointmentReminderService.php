<?php
/**
 * Appointment Reminder Service
 * Handles sending email reminders to patients 30 minutes before their appointments
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/DirectPHPMailerService.php';

class AppointmentReminderService {
    private $emailService;
    
    public function __construct() {
        $this->emailService = new DirectPHPMailerService();
    }
    
    /**
     * Check for appointments that need reminders and send them
     * Should be called every minute via cron job or periodic check
     */
    public function checkAndSendReminders() {
        try {
            $conn = Database::getConnection();
            
            // Get appointments that are exactly 30 minutes away and haven't been reminded
            $stmt = $conn->prepare("
                SELECT 
                    a.id,
                    a.appointment_date,
                    a.appointment_time,
                    a.notes,
                    a.status,
                    u.name as patient_name,
                    u.email as patient_email,
                    b.name as branch_name,
                    b.address as branch_address,
                    b.phone as branch_phone,
                    tt.name as treatment_name,
                    tt.duration_minutes,
                    tt.price
                FROM appointments a
                JOIN users u ON a.patient_id = u.id
                JOIN branches b ON a.branch_id = b.id
                JOIN treatment_types tt ON a.treatment_type_id = tt.id
                WHERE 
                    a.status IN ('approved', 'confirmed')
                    AND a.reminder_sent = 0
                    AND CONCAT(a.appointment_date, ' ', a.appointment_time) 
                        BETWEEN NOW() + INTERVAL 29 MINUTE AND NOW() + INTERVAL 31 MINUTE
            ");
            
            $stmt->execute();
            $result = $stmt->get_result();
            $appointments = [];
            
            while ($row = $result->fetch_assoc()) {
                $appointments[] = $row;
            }
            
            $remindersSent = 0;
            
            foreach ($appointments as $appointment) {
                if ($this->sendReminderEmail($appointment)) {
                    // Mark reminder as sent
                    $this->markReminderSent($appointment['id']);
                    $remindersSent++;
                }
            }
            
            return [
                'success' => true,
                'remindersSent' => $remindersSent,
                'message' => "Sent {$remindersSent} reminder(s)"
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Send reminder email to patient
     */
    private function sendReminderEmail($appointment) {
        try {
            $appointmentDateTime = date('l, F j, Y \a\t g:i A', strtotime($appointment['appointment_date'] . ' ' . $appointment['appointment_time']));
            $appointmentDate = date('F j, Y', strtotime($appointment['appointment_date']));
            $appointmentTime = date('g:i A', strtotime($appointment['appointment_time']));
            
            $subject = "🦷 Appointment Reminder - Your visit is in 30 minutes!";
            
            $emailContent = $this->generateReminderEmailTemplate([
                'patient_name' => $appointment['patient_name'],
                'appointment_datetime' => $appointmentDateTime,
                'appointment_date' => $appointmentDate,
                'appointment_time' => $appointmentTime,
                'treatment_name' => $appointment['treatment_name'],
                'duration' => $appointment['duration_minutes'],
                'branch_name' => $appointment['branch_name'],
                'branch_address' => $appointment['branch_address'],
                'branch_phone' => $appointment['branch_phone'],
                'notes' => $appointment['notes'],
                'price' => $appointment['price']
            ]);
            
            return $this->sendEmailViaDirectSendmail(
                $appointment['patient_email'],
                $appointment['patient_name'],
                $subject,
                $emailContent
            );
            
        } catch (Exception $e) {
            error_log("Failed to send reminder email: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send email using direct sendmail method
     */
    private function sendEmailViaDirectSendmail($to, $toName, $subject, $htmlContent) {
        try {
            $fromEmail = 'jkmoraca.personaluse@gmail.com';
            $fromName = 'Dental Clinic Management';
            $sendmailPath = 'C:\xampp\sendmail\sendmail.exe';
            
            // Create email headers
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "From: {$fromName} <{$fromEmail}>\r\n";
            $headers .= "Reply-To: {$fromEmail}\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
            
            // Create temporary file for email content
            $tempFile = tempnam(sys_get_temp_dir(), 'reminder_email_');
            $emailMessage = "To: {$toName} <{$to}>\r\n";
            $emailMessage .= "Subject: {$subject}\r\n";
            $emailMessage .= $headers . "\r\n";
            $emailMessage .= $htmlContent;
            
            file_put_contents($tempFile, $emailMessage);
            
            // Execute sendmail
            $command = "\"{$sendmailPath}\" \"{$to}\" < \"{$tempFile}\"";
            $output = [];
            $returnCode = 0;
            
            exec($command, $output, $returnCode);
            
            // Clean up
            unlink($tempFile);
            
            if ($returnCode === 0) {
                return true;
            } else {
                error_log("Sendmail failed with return code: {$returnCode}. Output: " . implode("\n", $output));
                return false;
            }
            
        } catch (Exception $e) {
            error_log("Failed to send email via sendmail: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mark reminder as sent in database
     */
    private function markReminderSent($appointmentId) {
        try {
            $conn = Database::getConnection();
            $stmt = $conn->prepare("UPDATE appointments SET reminder_sent = 1, reminder_sent_at = NOW() WHERE id = ?");
            $stmt->bind_param('i', $appointmentId);
            $stmt->execute();
            return true;
        } catch (Exception $e) {
            error_log("Failed to mark reminder as sent: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate professional reminder email template
     */
    private function generateReminderEmailTemplate($data) {
        $formattedPrice = $data['price'] ? '₱' . number_format($data['price'], 2) : 'Price on consultation';
        
        return "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Appointment Reminder</title>
            <style>
                body { font-family: 'Arial', sans-serif; background-color: #f5f7fa; margin: 0; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 8px 32px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
                .header h1 { margin: 0; font-size: 28px; font-weight: 600; }
                .header p { margin: 8px 0 0 0; opacity: 0.9; font-size: 16px; }
                .content { padding: 40px 30px; }
                .reminder-box { background: linear-gradient(135deg, #ff9a56 0%, #ff6b6b 100%); color: white; padding: 25px; border-radius: 10px; text-align: center; margin-bottom: 30px; }
                .reminder-box h2 { margin: 0 0 10px 0; font-size: 24px; }
                .reminder-box p { margin: 0; font-size: 16px; opacity: 0.95; }
                .appointment-details { background: #f8f9fa; border-radius: 10px; padding: 25px; margin-bottom: 25px; }
                .detail-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #e9ecef; }
                .detail-row:last-child { margin-bottom: 0; border-bottom: none; }
                .detail-label { font-weight: 600; color: #495057; display: flex; align-items: center; }
                .detail-value { color: #212529; font-weight: 500; }
                .icon { margin-right: 8px; color: #667eea; }
                .preparation-tips { background: #e3f2fd; border-left: 4px solid #2196f3; padding: 20px; margin: 25px 0; border-radius: 0 8px 8px 0; }
                .preparation-tips h3 { margin: 0 0 15px 0; color: #1976d2; font-size: 18px; }
                .preparation-tips ul { margin: 0; padding-left: 20px; }
                .preparation-tips li { margin-bottom: 8px; color: #424242; }
                .contact-info { background: #f1f8e9; padding: 20px; border-radius: 8px; margin-top: 25px; }
                .contact-info h3 { margin: 0 0 15px 0; color: #388e3c; }
                .footer { background: #f8f9fa; padding: 25px 30px; text-align: center; border-top: 1px solid #e9ecef; }
                .footer p { margin: 0; color: #6c757d; font-size: 14px; }
                @media (max-width: 600px) {
                    .container { margin: 10px; border-radius: 8px; }
                    .header, .content { padding: 20px; }
                    .detail-row { flex-direction: column; align-items: flex-start; }
                    .detail-value { margin-top: 5px; }
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🦷 Appointment Reminder</h1>
                    <p>Your dental appointment is coming up soon!</p>
                </div>
                
                <div class='content'>
                    <div class='reminder-box'>
                        <h2>⏰ 30 Minutes to Go!</h2>
                        <p>Hello {$data['patient_name']}, your appointment is scheduled in approximately 30 minutes.</p>
                    </div>
                    
                    <div class='appointment-details'>
                        <h3 style='margin: 0 0 20px 0; color: #343a40;'>📅 Appointment Details</h3>
                        
                        <div class='detail-row'>
                            <span class='detail-label'><i class='icon'>📅</i> Date & Time</span>
                            <span class='detail-value'>{$data['appointment_datetime']}</span>
                        </div>
                        
                        <div class='detail-row'>
                            <span class='detail-label'><i class='icon'>🦷</i> Treatment</span>
                            <span class='detail-value'>{$data['treatment_name']}</span>
                        </div>
                        
                        <div class='detail-row'>
                            <span class='detail-label'><i class='icon'>⏱️</i> Duration</span>
                            <span class='detail-value'>{$data['duration']} minutes</span>
                        </div>
                        
                        <div class='detail-row'>
                            <span class='detail-label'><i class='icon'>💰</i> Price</span>
                            <span class='detail-value'>{$formattedPrice}</span>
                        </div>
                        
                        <div class='detail-row'>
                            <span class='detail-label'><i class='icon'>🏥</i> Clinic</span>
                            <span class='detail-value'>{$data['branch_name']}</span>
                        </div>
                        
                        <div class='detail-row'>
                            <span class='detail-label'><i class='icon'>📍</i> Address</span>
                            <span class='detail-value'>{$data['branch_address']}</span>
                        </div>
                    </div>
                    
                    " . ($data['notes'] ? "
                    <div style='background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 0 6px 6px 0;'>
                        <h4 style='margin: 0 0 10px 0; color: #856404;'>📝 Special Notes</h4>
                        <p style='margin: 0; color: #856404;'>{$data['notes']}</p>
                    </div>
                    " : "") . "
                    
                    <div class='preparation-tips'>
                        <h3>💡 Pre-Appointment Tips</h3>
                        <ul>
                            <li>Arrive 10-15 minutes early for check-in</li>
                            <li>Bring a valid ID and any insurance cards</li>
                            <li>Brush your teeth before the appointment</li>
                            <li>Bring a list of current medications</li>
                            <li>Inform us of any allergies or health changes</li>
                        </ul>
                    </div>
                    
                    <div class='contact-info'>
                        <h3>📞 Need to Contact Us?</h3>
                        <p><strong>Phone:</strong> {$data['branch_phone']}</p>
                        <p><strong>Clinic:</strong> {$data['branch_name']}</p>
                        <p style='margin-top: 10px; font-size: 14px; color: #666;'>
                            If you need to reschedule or have any questions, please call us immediately.
                        </p>
                    </div>
                </div>
                
                <div class='footer'>
                    <p>Thank you for choosing our dental clinic. We look forward to seeing you soon! 😊</p>
                    <p style='margin-top: 10px; font-size: 12px;'>
                        © " . date('Y') . " Dental Clinic Management System. Professional Care, Personal Touch.
                    </p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Get reminder statistics
     */
    public function getReminderStats() {
        try {
            $conn = Database::getConnection();
            
            $stmt = $conn->prepare("
                SELECT 
                    COUNT(*) as total_reminders_sent,
                    COUNT(CASE WHEN DATE(reminder_sent_at) = CURDATE() THEN 1 END) as today_reminders,
                    COUNT(CASE WHEN DATE(reminder_sent_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as week_reminders
                FROM appointments 
                WHERE reminder_sent = 1
            ");
            
            $stmt->execute();
            $result = $stmt->get_result();
            $stats = $result->fetch_assoc();
            
            return [
                'success' => true,
                'stats' => $stats
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Manual trigger for testing (can be called from dashboard)
     */
    public function testReminderSystem() {
        try {
            $conn = Database::getConnection();
            
            // Get next upcoming appointment for testing
            $stmt = $conn->prepare("
                SELECT 
                    a.id,
                    a.appointment_date,
                    a.appointment_time,
                    a.notes,
                    a.status,
                    u.name as patient_name,
                    u.email as patient_email,
                    b.name as branch_name,
                    b.address as branch_address,
                    b.phone as branch_phone,
                    tt.name as treatment_name,
                    tt.duration_minutes,
                    tt.price
                FROM appointments a
                JOIN users u ON a.patient_id = u.id
                JOIN branches b ON a.branch_id = b.id
                JOIN treatment_types tt ON a.treatment_type_id = tt.id
                WHERE 
                    a.status IN ('approved', 'confirmed')
                    AND CONCAT(a.appointment_date, ' ', a.appointment_time) > NOW()
                ORDER BY a.appointment_date, a.appointment_time
                LIMIT 1
            ");
            
            $stmt->execute();
            $result = $stmt->get_result();
            $appointment = $result->fetch_assoc();
            
            if ($appointment) {
                $result = $this->sendReminderEmail($appointment);
                return [
                    'success' => true,
                    'message' => $result ? 'Test reminder sent successfully!' : 'Failed to send test reminder',
                    'appointment' => $appointment
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'No upcoming appointments found for testing'
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Send a test reminder email to verify the system is working
     */
    public function sendTestReminder($testEmail) {
        try {
            $subject = "Test Reminder - Dental Clinic System";
            $message = $this->generateTestReminderTemplate($testEmail);
            
            return $this->sendEmailViaDirectSendmail($testEmail, 'Test User', $subject, $message);
            
        } catch (Exception $e) {
            error_log("Test reminder error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate test reminder email template
     */
    private function generateTestReminderTemplate($email) {
        $currentDateTime = date('Y-m-d H:i:s');
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Test Reminder</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .highlight { background: #e3f2fd; padding: 15px; border-left: 4px solid #2196f3; margin: 20px 0; }
                .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
                .btn { display: inline-block; padding: 12px 25px; background: #007cba; color: white; text-decoration: none; border-radius: 5px; margin: 10px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🧪 Test Reminder Email</h1>
                    <p>Dental Clinic Management System</p>
                </div>
                
                <div class='content'>
                    <h2>Test Email Successful! ✅</h2>
                    
                    <p>Hello,</p>
                    
                    <p>This is a <strong>test email</strong> to verify that the reminder system is working correctly.</p>
                    
                    <div class='highlight'>
                        <strong>📧 Test Details:</strong><br>
                        <strong>Recipient:</strong> {$email}<br>
                        <strong>Test Time:</strong> {$currentDateTime}<br>
                        <strong>System Status:</strong> ✅ Operational
                    </div>
                    
                    <p>If you received this email, it means:</p>
                    <ul>
                        <li>✅ Email service is configured correctly</li>
                        <li>✅ SMTP/Sendmail is working</li>
                        <li>✅ Reminder system can send emails</li>
                        <li>✅ Email templates are rendering properly</li>
                    </ul>
                    
                    <p>The reminder system will now be able to:</p>
                    <ul>
                        <li>🔔 Send appointment reminders 30 minutes before scheduled time</li>
                        <li>📱 Display notifications on patient dashboard</li>
                        <li>📊 Track reminder statistics for admin monitoring</li>
                        <li>⚡ Automatically prevent duplicate reminders</li>
                    </ul>
                    
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='http://localhost/dental-clinic-chmsu-thesis/public/dashboard/patient-dashboard.php' class='btn'>
                            🏥 Open Patient Dashboard
                        </a>
                    </div>
                    
                    <div class='highlight'>
                        <strong>🚀 Next Steps:</strong><br>
                        1. Create test appointments 25-30 minutes in the future<br>
                        2. Monitor the system for automatic reminders<br>
                        3. Check patient dashboard for popup notifications<br>
                        4. Review reminder statistics in admin panel
                    </div>
                </div>
                
                <div class='footer'>
                    <p>This is an automated test email from the Dental Clinic Management System</p>
                    <p>Reminder System Test Suite - {$currentDateTime}</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}
?>