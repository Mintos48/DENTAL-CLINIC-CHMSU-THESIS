<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/session.php';

class InvoiceController {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    /**
     * Generate invoice for completed appointment
     */
    public function generateInvoiceForAppointment($appointment_id, $staff_id = null) {
        try {
            $this->db->begin_transaction();
            
            // Get appointment details
            $appointment = $this->getAppointmentDetails($appointment_id);
            if (!$appointment) {
                throw new Exception('Appointment not found');
            }
            
            // Check if invoice already exists for this appointment
            $existing_invoice = $this->getInvoiceByAppointmentId($appointment_id);
            if ($existing_invoice) {
                error_log("Invoice already exists for appointment $appointment_id: " . $existing_invoice['invoice_number']);
                return [
                    'success' => true,
                    'message' => 'Invoice already exists',
                    'invoice' => $existing_invoice
                ];
            }
            
            // Generate invoice number
            $invoice_number = $this->generateInvoiceNumber($appointment['branch_id']);
            
            // Use provided staff_id or get from appointment
            $creating_staff_id = $staff_id ?: $appointment['staff_id'];
            
            // Calculate dates
            $invoice_date = date('Y-m-d');
            $due_date = date('Y-m-d', strtotime('+30 days'));
            
            // Get treatment pricing
            $treatment_price = $this->getTreatmentPrice($appointment['treatment_type_id'], $appointment['branch_id']);
            
            // Calculate totals
            $subtotal = $treatment_price ?: 0;
            $tax_rate = 0.00; // Adjust as needed
            $tax_amount = $subtotal * ($tax_rate / 100);
            $total_amount = $subtotal + $tax_amount;
            
            // Create invoice - automatically paid since patients pay cash upon completion
            $invoice_id = $this->createInvoice([
                'invoice_number' => $invoice_number,
                'patient_id' => $appointment['patient_id'],
                'appointment_id' => $appointment_id,
                'branch_id' => $appointment['branch_id'],
                'staff_id' => $creating_staff_id,
                'invoice_date' => $invoice_date,
                'due_date' => $due_date,
                'subtotal' => $subtotal,
                'tax_rate' => $tax_rate,
                'tax_amount' => $tax_amount,
                'total_amount' => $total_amount,
                'paid_amount' => $total_amount, // Full payment received in cash
                'status' => 'paid', // Automatically paid upon completion
                'notes' => 'Invoice for completed appointment - Paid in cash upon treatment completion - ' . ($appointment['treatment_name'] ?: 'Dental consultation')
            ]);
            
            // Add invoice line items
            $this->addInvoiceItem($invoice_id, [
                'treatment_type_id' => $appointment['treatment_type_id'],
                'description' => $appointment['treatment_name'] ?: 'Dental consultation',
                'quantity' => 1,
                'unit_price' => $treatment_price ?: 0
            ]);
            
            $this->db->commit();
            
            // Get complete invoice details
            $invoice = $this->getInvoiceById($invoice_id);
            
            // Create payment record for cash payment
            $this->createCashPaymentRecord($invoice_id, $total_amount, $creating_staff_id);
            
            // Send notification to patient
            $this->sendInvoiceNotification($invoice);
            
            error_log("Invoice generated successfully: {$invoice_number} for appointment {$appointment_id}");
            
            return [
                'success' => true,
                'message' => 'Invoice generated successfully',
                'invoice' => $invoice
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Error generating invoice for appointment {$appointment_id}: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to generate invoice: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Generate invoice for completed walk-in appointment
     */
    public function generateInvoiceForWalkInAppointment($appointment_id, $staff_id = null) {
        try {
            $this->db->begin_transaction();
            
            // Get walk-in appointment details
            $appointment = $this->getWalkInAppointmentDetails($appointment_id);
            if (!$appointment) {
                throw new Exception('Walk-in appointment not found');
            }
            
            // Check if invoice already exists for this walk-in appointment
            $existing_invoice = $this->getWalkInInvoiceByAppointmentId($appointment_id);
            if ($existing_invoice) {
                error_log("Invoice already exists for walk-in appointment $appointment_id: " . $existing_invoice['invoice_number']);
                return [
                    'success' => true,
                    'message' => 'Invoice already exists',
                    'invoice' => $existing_invoice
                ];
            }
            
            // Generate invoice number
            $invoice_number = $this->generateInvoiceNumber($appointment['branch_id']);
            
            // Use provided staff_id or get from appointment
            $creating_staff_id = $staff_id ?: $appointment['staff_id'];
            
            // Calculate dates
            $invoice_date = date('Y-m-d');
            $due_date = date('Y-m-d', strtotime('+30 days'));
            
            // Get treatment pricing
            $treatment_price = $this->getTreatmentPrice($appointment['treatment_type_id'], $appointment['branch_id']);
            
            // Calculate totals
            $subtotal = $treatment_price ?: 0;
            $tax_rate = 0.00; // Adjust as needed
            $tax_amount = $subtotal * ($tax_rate / 100);
            $total_amount = $subtotal + $tax_amount;
            
            // Create invoice for walk-in - automatically paid since patients pay cash upon completion
            $invoice_id = $this->createWalkInInvoice([
                'invoice_number' => $invoice_number,
                'walk_in_appointment_id' => $appointment_id,
                'patient_name' => $appointment['patient_name'],
                'patient_email' => $appointment['patient_email'],
                'patient_phone' => $appointment['patient_phone'],
                'branch_id' => $appointment['branch_id'],
                'staff_id' => $creating_staff_id,
                'invoice_date' => $invoice_date,
                'due_date' => $due_date,
                'subtotal' => $subtotal,
                'tax_rate' => $tax_rate,
                'tax_amount' => $tax_amount,
                'total_amount' => $total_amount,
                'paid_amount' => $total_amount, // Full payment received in cash
                'status' => 'paid', // Automatically paid upon completion
                'notes' => 'Invoice for completed walk-in appointment - Paid in cash upon treatment completion - ' . ($appointment['treatment_name'] ?: 'Dental consultation')
            ]);
            
            // Add invoice line items
            $this->addWalkInInvoiceItem($invoice_id, [
                'treatment_type_id' => $appointment['treatment_type_id'],
                'description' => $appointment['treatment_name'] ?: 'Dental consultation',
                'quantity' => 1,
                'unit_price' => $treatment_price ?: 0
            ]);
            
            $this->db->commit();
            
            // Get complete invoice details
            $invoice = $this->getWalkInInvoiceById($invoice_id);
            
            // Create payment record for cash payment
            $this->createCashPaymentRecord($invoice_id, $total_amount, $creating_staff_id);
            
            // Send notification to walk-in patient if email provided
            if (!empty($appointment['patient_email'])) {
                $this->sendWalkInInvoiceNotification($invoice);
            } else {
                error_log("No email provided for walk-in appointment {$appointment_id}, skipping email notification");
            }
            
            error_log("Walk-in invoice generated successfully: {$invoice_number} for appointment {$appointment_id}");
            
            return [
                'success' => true,
                'message' => 'Walk-in invoice generated successfully',
                'invoice' => $invoice
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Error generating invoice for walk-in appointment {$appointment_id}: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to generate walk-in invoice: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get appointment details for invoice generation
     */
    private function getAppointmentDetails($appointment_id) {
        $sql = "SELECT a.*, 
                       p.name as patient_name, p.email as patient_email,
                       b.name as branch_name,
                       tt.name as treatment_name, tt.base_price as treatment_base_price,
                       bt.price as branch_treatment_price
                FROM appointments a
                JOIN users p ON a.patient_id = p.id
                JOIN branches b ON a.branch_id = b.id
                LEFT JOIN treatment_types tt ON a.treatment_type_id = tt.id
                LEFT JOIN branch_services bt ON (tt.id = bt.treatment_type_id AND a.branch_id = bt.branch_id)
                WHERE a.id = ? AND a.status = 'completed'";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $appointment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    /**
     * Check if invoice already exists for appointment
     */
    private function getInvoiceByAppointmentId($appointment_id) {
        $sql = "SELECT * FROM invoices WHERE appointment_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $appointment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    /**
     * Generate unique invoice number
     */
    private function generateInvoiceNumber($branch_id) {
        $prefix = "INV-" . str_pad($branch_id, 2, '0', STR_PAD_LEFT) . "-";
        $date_part = date('Ymd');
        
        // Try to find unique sequence number (max 100 attempts to avoid infinite loop)
        for ($attempt = 1; $attempt <= 100; $attempt++) {
            // Get next sequence number by finding max existing + 1
            $sql = "SELECT IFNULL(MAX(CAST(SUBSTRING_INDEX(invoice_number, '-', -1) AS UNSIGNED)), 0) + 1 as next_seq 
                    FROM invoices 
                    WHERE branch_id = ? AND invoice_number LIKE ?";
            $stmt = $this->db->prepare($sql);
            $pattern = $prefix . $date_part . "-%";
            $stmt->bind_param("is", $branch_id, $pattern);
            $stmt->execute();
            $result = $stmt->get_result();
            $seq = $result->fetch_assoc()['next_seq'];
            
            $invoice_number = $prefix . $date_part . "-" . str_pad($seq, 3, '0', STR_PAD_LEFT);
            
            // Check if this number already exists
            $check_sql = "SELECT id FROM invoices WHERE invoice_number = ?";
            $check_stmt = $this->db->prepare($check_sql);
            $check_stmt->bind_param("s", $invoice_number);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows === 0) {
                // Number is unique, use it
                return $invoice_number;
            }
            
            // Number exists, try again
            error_log("Invoice number $invoice_number already exists, retrying... (attempt $attempt)");
        }
        
        // Fallback to timestamp-based unique number if we couldn't find a unique sequence
        $timestamp_seq = time() % 1000;
        return $prefix . $date_part . "-" . str_pad($timestamp_seq, 3, '0', STR_PAD_LEFT);
    }
    
    /**
     * Get treatment price for branch
     */
    private function getTreatmentPrice($treatment_type_id, $branch_id) {
        if (!$treatment_type_id) return 0;
        
        // First try branch-specific pricing
        $sql = "SELECT price FROM branch_services 
                WHERE treatment_type_id = ? AND branch_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ii", $treatment_type_id, $branch_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            return $row['price'];
        }
        
        // Fallback to base price
        $sql = "SELECT base_price FROM treatment_types WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $treatment_type_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            return $row['base_price'];
        }
        
        return 0;
    }
    
    /**
     * Create invoice record
     */
    private function createInvoice($data) {
        $sql = "INSERT INTO invoices (
                    invoice_number, patient_id, appointment_id, branch_id, staff_id,
                    invoice_date, due_date, subtotal, tax_rate, tax_amount, 
                    total_amount, paid_amount, status, notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $paid_amount = isset($data['paid_amount']) ? $data['paid_amount'] : 0.00;
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            "siiiissdddddss",
            $data['invoice_number'],
            $data['patient_id'],
            $data['appointment_id'],
            $data['branch_id'],
            $data['staff_id'],
            $data['invoice_date'],
            $data['due_date'],
            $data['subtotal'],
            $data['tax_rate'],
            $data['tax_amount'],
            $data['total_amount'],
            $paid_amount,
            $data['status'],
            $data['notes']
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to create invoice: ' . $stmt->error);
        }
        
        return $this->db->insert_id;
    }
    
    /**
     * Add invoice line item
     */
    private function addInvoiceItem($invoice_id, $data) {
        $sql = "INSERT INTO invoice_items (
                    invoice_id, treatment_type_id, description, quantity, unit_price
                ) VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            "iisdd",
            $invoice_id,
            $data['treatment_type_id'],
            $data['description'],
            $data['quantity'],
            $data['unit_price']
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to add invoice item: ' . $stmt->error);
        }
        
        return $this->db->insert_id;
    }
    
    /**
     * Get complete invoice details
     */
    private function getInvoiceById($invoice_id) {
        $sql = "SELECT i.*, 
                       p.name as patient_name, p.email as patient_email,
                       b.name as branch_name,
                       s.name as staff_name
                FROM invoices i
                JOIN users p ON i.patient_id = p.id
                JOIN branches b ON i.branch_id = b.id
                JOIN users s ON i.staff_id = s.id
                WHERE i.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $invoice_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $invoice = $result->fetch_assoc();
        
        if ($invoice) {
            // Get invoice items
            $items_sql = "SELECT ii.*, tt.name as treatment_name
                         FROM invoice_items ii
                         LEFT JOIN treatment_types tt ON ii.treatment_type_id = tt.id
                         WHERE ii.invoice_id = ?";
            $items_stmt = $this->db->prepare($items_sql);
            $items_stmt->bind_param("i", $invoice_id);
            $items_stmt->execute();
            $items_result = $items_stmt->get_result();
            
            $invoice['items'] = [];
            while ($item = $items_result->fetch_assoc()) {
                $invoice['items'][] = $item;
            }
        }
        
        return $invoice;
    }
    
    /**
     * Send invoice notification to patient
     */
    private function sendInvoiceNotification($invoice) {
        try {
            error_log("Sending invoice notification for invoice: " . $invoice['invoice_number']);
            
            // Send email invoice to patient
            $email_sent = $this->sendInvoiceEmail($invoice);
            
            if ($email_sent) {
                error_log("Invoice email sent successfully for patient ID: " . $invoice['patient_id']);
            } else {
                error_log("Failed to send invoice email for patient ID: " . $invoice['patient_id']);
            }
            
        } catch (Exception $e) {
            error_log("Failed to send invoice notification: " . $e->getMessage());
        }
    }
    
    /**
     * Send invoice email to patient
     */
    private function sendInvoiceEmail($invoice) {
        try {
            // Get patient email
            $patient_email = $this->getPatientEmail($invoice['patient_id']);
            if (!$patient_email) {
                error_log("No email found for patient ID: " . $invoice['patient_id']);
                return false;
            }
            
            // Get patient name
            $patient_name = $this->getPatientName($invoice['patient_id']);
            
            // Load PHPMailer
            require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
            
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
            $mail->addAddress($patient_email, $patient_name);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Your Invoice - ' . $invoice['invoice_number'];
            $mail->Body = $this->generateInvoiceEmailHtml($invoice, $patient_name);
            $mail->AltBody = $this->generateInvoiceEmailText($invoice, $patient_name);
            
            $mail->send();
            
            error_log("Invoice email sent successfully to: " . $patient_email);
            
            // Log the email activity
            $this->logActivity(
                $invoice['patient_id'], 
                'INVOICE_EMAIL_SENT', 
                "Invoice email sent for invoice {$invoice['invoice_number']} to {$patient_email}"
            );
            
            return true;
            
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            error_log("PHPMailer error sending invoice: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("Error sending invoice email: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get patient email
     */
    private function getPatientEmail($patient_id) {
        try {
            $stmt = $this->db->prepare("SELECT email FROM users WHERE id = ?");
            $stmt->bind_param("i", $patient_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                return $row['email'];
            }
            
            return null;
        } catch (Exception $e) {
            error_log("Error getting patient email: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get patient name
     */
    private function getPatientName($patient_id) {
        try {
            $stmt = $this->db->prepare("SELECT name FROM users WHERE id = ?");
            $stmt->bind_param("i", $patient_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                return $row['name'];
            }
            
            return 'Valued Patient';
        } catch (Exception $e) {
            error_log("Error getting patient name: " . $e->getMessage());
            return 'Valued Patient';
        }
    }
    
    /**
     * Generate HTML email template for invoice
     */
    private function generateInvoiceEmailHtml($invoice, $patient_name) {
        $invoiceDate = date('F d, Y', strtotime($invoice['invoice_date']));
        $dueDate = date('F d, Y', strtotime($invoice['due_date']));
        $totalAmount = number_format($invoice['total_amount'], 2);
        $subtotal = number_format($invoice['subtotal'], 2);
        $taxAmount = number_format($invoice['tax_amount'], 2);
        
        return "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Invoice - {$invoice['invoice_number']}</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .header { background: #054A91; color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f8f9fa; padding: 30px; border: 1px solid #dee2e6; }
        .footer { background: #e9ecef; padding: 20px; text-align: center; border-radius: 0 0 8px 8px; }
        .invoice-details { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; }
        .amount-box { background: #054A91; color: white; padding: 15px; text-align: center; border-radius: 8px; margin: 20px 0; }
        .item-row { border-bottom: 1px solid #dee2e6; padding: 10px 0; }
        .total-row { font-weight: bold; background: #f8f9fa; padding: 10px; }
    </style>
</head>
<body>
    <div class='header'>
        <h1>🦷 Dental Clinic Management</h1>
        <h2>Invoice Generated</h2>
    </div>
    
    <div class='content'>
        <h2>Hello {$patient_name}!</h2>
        
        <p>Your appointment has been completed and we've generated your invoice. Thank you for choosing our dental services!</p>
        
        <div class='invoice-details'>
            <h3>Invoice Details</h3>
            <p><strong>Invoice Number:</strong> {$invoice['invoice_number']}</p>
            <p><strong>Branch:</strong> {$invoice['branch_name']}</p>
            <p><strong>Invoice Date:</strong> {$invoiceDate}</p>
            <p><strong>Due Date:</strong> {$dueDate}</p>
            <p><strong>Status:</strong> " . ucfirst($invoice['status']) . "</p>
        </div>
        
        <div class='invoice-details'>
            <h3>Treatment Details</h3>
            <div class='item-row'>
                <strong>Service:</strong> " . ($invoice['treatment_name'] ?: 'Dental Consultation') . "<br>
                <strong>Amount:</strong> ₱{$subtotal}
            </div>
        </div>
        
        <div class='invoice-details'>
            <h3>Amount Summary</h3>
            <div class='item-row'>
                <strong>Subtotal:</strong> ₱{$subtotal}
            </div>
            <div class='item-row'>
                <strong>Tax:</strong> ₱{$taxAmount}
            </div>
            <div class='total-row'>
                <strong>Total Amount:</strong> ₱{$totalAmount}
            </div>
        </div>
        
        <div class='amount-box'>
            <h3 style='margin: 0;'>Total Paid: ₱{$totalAmount}</h3>
            <p style='margin: 5px 0 0 0; color: #28a745;'>✅ PAID IN FULL - Cash Payment Received</p>
        </div>
        
        <div class='invoice-details'>
            <h3>Payment Confirmation</h3>
            <p><strong>✅ Payment Status: PAID IN FULL</strong></p>
            <p>Payment Method: Cash</p>
            <p>Payment Date: {$invoiceDate}</p>
            <p>This invoice serves as your receipt for the completed dental treatment.</p>
            <p>Thank you for your prompt payment!</p>
        </div>
    </div>
    
    <div class='footer'>
        <p><strong>Thank you for your trust in our dental services!</strong></p>
        <p>Dental Clinic Management System</p>
    </div>
</body>
</html>";
    }
    
    /**
     * Generate plain text email for invoice
     */
    private function generateInvoiceEmailText($invoice, $patient_name) {
        $invoiceDate = date('F d, Y', strtotime($invoice['invoice_date']));
        $dueDate = date('F d, Y', strtotime($invoice['due_date']));
        $totalAmount = number_format($invoice['total_amount'], 2);
        
        return "DENTAL CLINIC MANAGEMENT - INVOICE

Hello {$patient_name}!

Your appointment has been completed and we've generated your invoice.

INVOICE DETAILS:
- Invoice Number: {$invoice['invoice_number']}
- Branch: {$invoice['branch_name']}
- Invoice Date: {$invoiceDate}
- Total Amount: ₱{$totalAmount}

PAYMENT CONFIRMATION:
✅ PAYMENT STATUS: PAID IN FULL
- Payment Method: Cash
- Payment Date: {$invoiceDate}

This invoice serves as your receipt for the completed dental treatment.
Thank you for your prompt payment and for choosing our dental services!

For questions, please contact us at your branch location.

Dental Clinic Management System";
    }
    
    /**
     * Log activity
     */
    private function logActivity($user_id, $action, $description) {
        try {
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'system';
            $stmt = $this->db->prepare("INSERT INTO system_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $user_id, $action, $description, $ip_address);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("Log activity error: " . $e->getMessage());
        }
    }
    
    /**
     * Get walk-in appointment details for invoice generation
     */
    private function getWalkInAppointmentDetails($appointment_id) {
        $sql = "SELECT w.*, 
                       b.name as branch_name,
                       tt.name as treatment_name, tt.base_price as treatment_base_price,
                       bt.price as branch_treatment_price
                FROM walk_in_appointments w
                JOIN branches b ON w.branch_id = b.id
                LEFT JOIN treatment_types tt ON w.treatment_type_id = tt.id
                LEFT JOIN branch_services bt ON w.treatment_type_id = bt.treatment_type_id AND w.branch_id = bt.branch_id
                WHERE w.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $appointment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->num_rows > 0 ? $result->fetch_assoc() : null;
    }
    
    /**
     * Check if walk-in invoice already exists
     */
    private function getWalkInInvoiceByAppointmentId($appointment_id) {
        $sql = "SELECT * FROM invoices WHERE walk_in_appointment_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $appointment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->num_rows > 0 ? $result->fetch_assoc() : null;
    }
    
    /**
     * Create walk-in invoice
     */
    private function createWalkInInvoice($data) {
        $sql = "INSERT INTO invoices (
                    invoice_number, walk_in_appointment_id, patient_name, patient_email, patient_phone,
                    branch_id, staff_id, invoice_date, due_date, subtotal, tax_rate, tax_amount, 
                    discount_amount, total_amount, paid_amount, status, notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $paid_amount = isset($data['paid_amount']) ? $data['paid_amount'] : 0.00;
        $discount_amount = isset($data['discount_amount']) ? $data['discount_amount'] : 0.00;
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            "sisssssssddddddss",
            $data['invoice_number'],
            $data['walk_in_appointment_id'],
            $data['patient_name'],
            $data['patient_email'],
            $data['patient_phone'],
            $data['branch_id'],
            $data['staff_id'],
            $data['invoice_date'],
            $data['due_date'],
            $data['subtotal'],
            $data['tax_rate'],
            $data['tax_amount'],
            $discount_amount,
            $data['total_amount'],
            $paid_amount,
            $data['status'],
            $data['notes']
        );
        
        $stmt->execute();
        return $this->db->insert_id;
    }
    
    /**
     * Add walk-in invoice item
     */
    private function addWalkInInvoiceItem($invoice_id, $data) {
        $sql = "INSERT INTO invoice_items (
                    invoice_id, treatment_type_id, description, quantity, unit_price, 
                    total_price, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)";
        
        $total_price = $data['quantity'] * $data['unit_price'];
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            "iisidd",
            $invoice_id,
            $data['treatment_type_id'],
            $data['description'],
            $data['quantity'],
            $data['unit_price'],
            $total_price
        );
        
        return $stmt->execute();
    }
    
    /**
     * Get walk-in invoice by ID
     */
    private function getWalkInInvoiceById($invoice_id) {
        $sql = "SELECT i.*, b.name as branch_name, 
                       COALESCE(i.patient_name, 'Walk-in Patient') as patient_name
                FROM invoices i
                JOIN branches b ON i.branch_id = b.id
                WHERE i.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $invoice_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $invoice = $result->fetch_assoc();
            
            // Get line items
            $items_sql = "SELECT ii.*, tt.name as treatment_name 
                         FROM invoice_items ii
                         LEFT JOIN treatment_types tt ON ii.treatment_type_id = tt.id
                         WHERE ii.invoice_id = ?";
            $items_stmt = $this->db->prepare($items_sql);
            $items_stmt->bind_param("i", $invoice_id);
            $items_stmt->execute();
            $items_result = $items_stmt->get_result();
            
            $invoice['items'] = [];
            while ($item = $items_result->fetch_assoc()) {
                $invoice['items'][] = $item;
                // Set treatment_name for email template
                if (!isset($invoice['treatment_name']) && !empty($item['treatment_name'])) {
                    $invoice['treatment_name'] = $item['treatment_name'];
                }
            }
            
            return $invoice;
        }
        
        return null;
    }
    
    /**
     * Send walk-in invoice notification
     */
    private function sendWalkInInvoiceNotification($invoice) {
        try {
            error_log("Sending walk-in invoice notification for invoice: " . $invoice['invoice_number']);
            
            // Send email invoice to walk-in patient
            $email_sent = $this->sendWalkInInvoiceEmail($invoice);
            
            if ($email_sent) {
                error_log("Walk-in invoice email sent successfully to: " . $invoice['patient_email']);
            } else {
                error_log("Failed to send walk-in invoice email to: " . $invoice['patient_email']);
            }
            
        } catch (Exception $e) {
            error_log("Failed to send walk-in invoice notification: " . $e->getMessage());
        }
    }
    
    /**
     * Send walk-in invoice email
     */
    private function sendWalkInInvoiceEmail($invoice) {
        try {
            if (empty($invoice['patient_email'])) {
                error_log("No email provided for walk-in invoice: " . $invoice['invoice_number']);
                return false;
            }
            
            $patient_name = $invoice['patient_name'] ?: 'Valued Patient';
            
            // Load PHPMailer
            require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
            
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
            $mail->addAddress($invoice['patient_email'], $patient_name);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Your Invoice - ' . $invoice['invoice_number'] . ' (Walk-in Visit)';
            $mail->Body = $this->generateWalkInInvoiceEmailHtml($invoice, $patient_name);
            $mail->AltBody = $this->generateWalkInInvoiceEmailText($invoice, $patient_name);
            
            $mail->send();
            
            error_log("Walk-in invoice email sent successfully to: " . $invoice['patient_email']);
            
            // Log the email activity (no user_id for walk-in)
            $this->logActivitySystem(
                'WALK_IN_INVOICE_EMAIL_SENT', 
                "Walk-in invoice email sent for invoice {$invoice['invoice_number']} to {$invoice['patient_email']}"
            );
            
            return true;
            
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            error_log("PHPMailer error sending walk-in invoice: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("Error sending walk-in invoice email: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate HTML email template for walk-in invoice
     */
    private function generateWalkInInvoiceEmailHtml($invoice, $patient_name) {
        $invoiceDate = date('F d, Y', strtotime($invoice['invoice_date']));
        $dueDate = date('F d, Y', strtotime($invoice['due_date']));
        $totalAmount = number_format($invoice['total_amount'], 2);
        $subtotal = number_format($invoice['subtotal'], 2);
        $taxAmount = number_format($invoice['tax_amount'], 2);
        
        return "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Invoice - {$invoice['invoice_number']} (Walk-in Visit)</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .header { background: #054A91; color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f8f9fa; padding: 30px; border: 1px solid #dee2e6; }
        .footer { background: #e9ecef; padding: 20px; text-align: center; border-radius: 0 0 8px 8px; }
        .invoice-details { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; }
        .amount-box { background: #054A91; color: white; padding: 15px; text-align: center; border-radius: 8px; margin: 20px 0; }
        .item-row { border-bottom: 1px solid #dee2e6; padding: 10px 0; }
        .total-row { font-weight: bold; background: #f8f9fa; padding: 10px; }
        .walk-in-badge { background: #28a745; color: white; padding: 5px 10px; border-radius: 4px; font-size: 12px; }
    </style>
</head>
<body>
    <div class='header'>
        <h1>🦷 Dental Clinic Management</h1>
        <h2>Invoice Generated</h2>
        <span class='walk-in-badge'>Walk-in Visit</span>
    </div>
    
    <div class='content'>
        <h2>Hello {$patient_name}!</h2>
        
        <p>Thank you for visiting our clinic as a walk-in patient! Your treatment has been completed and we've generated your invoice.</p>
        
        <div class='invoice-details'>
            <h3>Invoice Details</h3>
            <p><strong>Invoice Number:</strong> {$invoice['invoice_number']}</p>
            <p><strong>Branch:</strong> {$invoice['branch_name']}</p>
            <p><strong>Invoice Date:</strong> {$invoiceDate}</p>
            <p><strong>Due Date:</strong> {$dueDate}</p>
            <p><strong>Status:</strong> " . ucfirst($invoice['status']) . "</p>
            <p><strong>Visit Type:</strong> <span class='walk-in-badge'>Walk-in Patient</span></p>
        </div>
        
        <div class='invoice-details'>
            <h3>Treatment Details</h3>
            <div class='item-row'>
                <strong>Service:</strong> " . ($invoice['treatment_name'] ?: 'Dental Consultation') . "<br>
                <strong>Amount:</strong> ₱{$subtotal}
            </div>
        </div>
        
        <div class='invoice-details'>
            <h3>Amount Summary</h3>
            <div class='item-row'>
                <strong>Subtotal:</strong> ₱{$subtotal}
            </div>
            <div class='item-row'>
                <strong>Tax:</strong> ₱{$taxAmount}
            </div>
            <div class='total-row'>
                <strong>Total Amount:</strong> ₱{$totalAmount}
            </div>
        </div>
        
        <div class='amount-box'>
            <h3 style='margin: 0;'>Total Paid: ₱{$totalAmount}</h3>
            <p style='margin: 5px 0 0 0; color: #28a745;'>✅ PAID IN FULL - Cash Payment Received</p>
        </div>
        
        <div class='invoice-details'>
            <h3>Payment Confirmation</h3>
            <p><strong>✅ Payment Status: PAID IN FULL</strong></p>
            <p>Payment Method: Cash</p>
            <p>Payment Date: {$invoiceDate}</p>
            <p>This invoice serves as your receipt for the completed dental treatment.</p>
            <p>Thank you for your prompt payment!</p>
            <p><strong>Note:</strong> This invoice is for a walk-in visit. If you'd like to schedule future appointments, please consider registering for an account on our website.</p>
        </div>
    </div>
    
    <div class='footer'>
        <p><strong>Thank you for choosing our dental services!</strong></p>
        <p>Dental Clinic Management System</p>
    </div>
</body>
</html>";
    }
    
    /**
     * Generate plain text email for walk-in invoice
     */
    private function generateWalkInInvoiceEmailText($invoice, $patient_name) {
        $invoiceDate = date('F d, Y', strtotime($invoice['invoice_date']));
        $dueDate = date('F d, Y', strtotime($invoice['due_date']));
        $totalAmount = number_format($invoice['total_amount'], 2);
        
        return "DENTAL CLINIC MANAGEMENT - INVOICE (WALK-IN VISIT)

Hello {$patient_name}!

Thank you for visiting our clinic as a walk-in patient! Your treatment has been completed and we've generated your invoice.

INVOICE DETAILS:
- Invoice Number: {$invoice['invoice_number']}
- Branch: {$invoice['branch_name']}
- Invoice Date: {$invoiceDate}
- Total Amount: ₱{$totalAmount}
- Visit Type: Walk-in Patient

PAYMENT CONFIRMATION:
✅ PAYMENT STATUS: PAID IN FULL
- Payment Method: Cash
- Payment Date: {$invoiceDate}

This invoice serves as your receipt for the completed dental treatment.
Thank you for your prompt payment and for choosing our dental services!

Note: This invoice is for a walk-in visit. If you'd like to schedule future appointments, please consider registering for an account on our website.

For questions, please contact us at your branch location.

Dental Clinic Management System";
    }
    
    /**
     * Log system activity (for walk-in operations without user_id)
     */
    private function logActivitySystem($action, $description) {
        try {
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'system';
            $stmt = $this->db->prepare("INSERT INTO system_logs (user_id, action, description, ip_address) VALUES (NULL, ?, ?, ?)");
            $stmt->bind_param("sss", $action, $description, $ip_address);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("Log system activity error: " . $e->getMessage());
        }
    }
    
    /**
     * Get patient invoices
     */
    public function getPatientInvoices($patient_id) {
        $sql = "SELECT i.*, b.name as branch_name
                FROM invoices i
                JOIN branches b ON i.branch_id = b.id
                WHERE i.patient_id = ?
                ORDER BY i.invoice_date DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $patient_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $invoices = [];
        while ($row = $result->fetch_assoc()) {
            $invoices[] = $row;
        }
        
        return $invoices;
    }
    
    /**
     * Create cash payment record
     */
    private function createCashPaymentRecord($invoice_id, $amount, $staff_id) {
        try {
            $sql = "INSERT INTO payments (
                        invoice_id, amount, payment_method, payment_date, 
                        status, processed_by, transaction_id, notes, created_at
                    ) VALUES (?, ?, 'cash', CURRENT_DATE, 'completed', ?, ?, ?, CURRENT_TIMESTAMP)";
            
            $transaction_id = 'CASH-' . date('Ymd') . '-' . str_pad($invoice_id, 6, '0', STR_PAD_LEFT);
            $notes = 'Cash payment received upon treatment completion';
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("ddiss", $invoice_id, $amount, $staff_id, $transaction_id, $notes);
            
            if ($stmt->execute()) {
                error_log("Cash payment record created for invoice {$invoice_id}, amount: ₱{$amount}");
                return $this->db->insert_id;
            } else {
                error_log("Failed to create cash payment record for invoice {$invoice_id}: " . $stmt->error);
                return false;
            }
            
        } catch (Exception $e) {
            error_log("Error creating cash payment record: " . $e->getMessage());
            return false;
        }
    }
}

// Handle direct API calls - only for invoice-specific actions
if (isset($_GET['action']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'];
    
    // Only handle invoice-specific actions to avoid conflicts with other controllers
    $invoice_actions = ['getPatientInvoices', 'getInvoiceDetails', 'generateInvoice'];
    
    if (in_array($action, $invoice_actions)) {
        header('Content-Type: application/json');
        
        try {
            $controller = new InvoiceController();
            
            switch ($action) {
            case 'getPatientInvoices':
                $patient_id = getSessionUserId();
                if (!$patient_id) {
                    throw new Exception('Patient session not found');
                }
                
                $invoices = $controller->getPatientInvoices($patient_id);
                echo json_encode([
                    'success' => true,
                    'invoices' => $invoices
                ]);
                break;
                
            default:
                throw new Exception('Invalid action');
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    } // Close the invoice actions check
}
?>