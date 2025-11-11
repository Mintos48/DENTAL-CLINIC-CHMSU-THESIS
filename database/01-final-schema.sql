-- ===============================================
-- DENTAL CLINIC DATABASE SCHEMA - FINAL VERSION
-- Date: 2025-11-03
-- Description: Complete consolidated schema with all features
-- Version: Final (incorporates v2.0, v3.0, and all migrations)
-- ===============================================

-- Features Included:
-- ✅ Multi-branch clinic management with enhanced details
-- ✅ Patient appointment booking and tracking with priorities
-- ✅ Walk-in appointment system (no user account required)
-- ✅ Advanced patient referral system with two-step approval
-- ✅ Enhanced authentication (email verification, OTP, sessions)
-- ✅ Medical records and document management
-- ✅ Billing and payment integration
-- ✅ Staff management with specializations and scheduling
-- ✅ Advanced notification system with templates
-- ✅ Business intelligence and reporting capabilities
-- ✅ Complete audit trail and security features

-- Database setup
DROP DATABASE IF EXISTS dental_clinic_db;
CREATE DATABASE dental_clinic_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE dental_clinic_db;

-- Set timezone for consistent timestamps
SET time_zone = '+08:00';

-- ===============================================
-- CORE ORGANIZATION TABLES
-- ===============================================

-- Users table - Complete with all security and preference features (created first for FK references)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    uuid VARCHAR(36) UNIQUE DEFAULT (UUID()) COMMENT 'Public user identifier',
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('patient', 'staff', 'admin', 'super_admin') NOT NULL,
    branch_id INT,
    phone VARCHAR(20),
    address TEXT,
    date_of_birth DATE,
    gender ENUM('male', 'female', 'other', 'prefer_not_to_say'),
    emergency_contact_name VARCHAR(100),
    emergency_contact_phone VARCHAR(20),
    
    -- Authentication and Security
    email_verified BOOLEAN DEFAULT FALSE,
    phone_verified BOOLEAN DEFAULT FALSE,
    verification_token VARCHAR(64),
    verification_code VARCHAR(6),
    verification_code_expires_at TIMESTAMP NULL,
    login_otp VARCHAR(6) DEFAULT NULL COMMENT 'OTP code for login verification',
    login_otp_expires_at TIMESTAMP NULL DEFAULT NULL COMMENT 'When the login OTP expires',
    login_otp_attempts INT DEFAULT 0 COMMENT 'Number of failed OTP attempts',
    login_otp_created_at TIMESTAMP NULL DEFAULT NULL COMMENT 'When the OTP was generated',
    last_login_at TIMESTAMP NULL,
    failed_login_attempts INT DEFAULT 0,
    locked_until TIMESTAMP NULL,
    password_changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- User Preferences
    receive_notifications BOOLEAN DEFAULT TRUE,
    receive_email_reminders BOOLEAN DEFAULT TRUE,
    receive_sms_reminders BOOLEAN DEFAULT FALSE,
    preferred_language VARCHAR(5) DEFAULT 'en',
    
    status ENUM('active', 'inactive', 'suspended', 'pending_verification') DEFAULT 'pending_verification',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL COMMENT 'Soft delete timestamp',
    
    INDEX idx_uuid (uuid),
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_branch_id (branch_id),
    INDEX idx_role_branch (role, branch_id),
    INDEX idx_verification_token (verification_token),
    INDEX idx_verification_code (verification_code),
    INDEX idx_login_otp (login_otp),
    INDEX idx_login_otp_expires (login_otp_expires_at),
    INDEX idx_last_login (last_login_at),
    INDEX idx_deleted_at (deleted_at)
);

-- Branches table - Enhanced with comprehensive details
CREATE TABLE branches (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(10) UNIQUE,
    location VARCHAR(255) NOT NULL,
    address TEXT,
    phone VARCHAR(20),
    email VARCHAR(100),
    manager_id INT DEFAULT NULL,
    operating_hours VARCHAR(100),
    timezone VARCHAR(50) DEFAULT 'Asia/Manila',
    capacity_per_hour INT DEFAULT 4 COMMENT 'Max appointments per hour',
    geo_latitude DECIMAL(10, 8) DEFAULT NULL,
    geo_longitude DECIMAL(11, 8) DEFAULT NULL,
    status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (manager_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_code (code),
    INDEX idx_manager (manager_id)
);

-- Add foreign key constraint for users -> branches relationship
ALTER TABLE users ADD CONSTRAINT fk_users_branch FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL;

-- User sessions table - For session management
CREATE TABLE user_sessions (
    id VARCHAR(128) PRIMARY KEY COMMENT 'Session ID',
    user_id INT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    device_info JSON DEFAULT NULL,
    location_info JSON DEFAULT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL DEFAULT '2025-01-01 00:00:00',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_active (user_id, is_active),
    INDEX idx_expires (expires_at),
    INDEX idx_last_activity (last_activity)
);

-- ===============================================
-- TREATMENT AND SERVICE MANAGEMENT
-- ===============================================

-- Treatment categories - For better organization
CREATE TABLE treatment_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    color_code VARCHAR(7) DEFAULT '#607D8B',
    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_display_order (display_order),
    INDEX idx_is_active (is_active)
);

-- Treatment types table - Enhanced with categories and pricing
CREATE TABLE treatment_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT DEFAULT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    duration_minutes INT NOT NULL DEFAULT 30,
    base_price DECIMAL(10,2) DEFAULT 0.00,
    color_code VARCHAR(7) DEFAULT '#2196F3',
    requires_specialist BOOLEAN DEFAULT FALSE,
    preparation_instructions TEXT,
    post_treatment_instructions TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (category_id) REFERENCES treatment_categories(id) ON DELETE SET NULL,
    INDEX idx_category (category_id),
    INDEX idx_is_active (is_active),
    INDEX idx_duration (duration_minutes),
    INDEX idx_requires_specialist (requires_specialist)
);

-- Branch services table - Enhanced with pricing and availability
CREATE TABLE branch_services (
    id INT PRIMARY KEY AUTO_INCREMENT,
    branch_id INT NOT NULL,
    treatment_type_id INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    estimated_duration_minutes INT,
    is_available BOOLEAN DEFAULT TRUE,
    requires_advance_booking BOOLEAN DEFAULT FALSE,
    max_daily_appointments INT DEFAULT NULL,
    effective_from DATE DEFAULT (CURDATE()),
    effective_until DATE DEFAULT NULL,
    special_instructions TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    FOREIGN KEY (treatment_type_id) REFERENCES treatment_types(id) ON DELETE CASCADE,
    UNIQUE KEY unique_branch_treatment (branch_id, treatment_type_id),
    INDEX idx_branch_id (branch_id),
    INDEX idx_treatment_type_id (treatment_type_id),
    INDEX idx_is_available (is_available),
    INDEX idx_effective_dates (effective_from, effective_until)
);

-- ===============================================
-- STAFF MANAGEMENT AND SCHEDULING
-- ===============================================

-- Staff specializations - Track expertise
CREATE TABLE staff_specializations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    staff_id INT NOT NULL,
    treatment_type_id INT NOT NULL,
    proficiency_level ENUM('beginner', 'intermediate', 'advanced', 'expert') DEFAULT 'intermediate',
    certification_date DATE DEFAULT NULL,
    certification_expires DATE DEFAULT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (staff_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (treatment_type_id) REFERENCES treatment_types(id) ON DELETE CASCADE,
    UNIQUE KEY unique_staff_treatment (staff_id, treatment_type_id),
    INDEX idx_staff_id (staff_id),
    INDEX idx_treatment_type (treatment_type_id),
    INDEX idx_proficiency (proficiency_level),
    INDEX idx_treatment_specialists (treatment_type_id, proficiency_level)
);

-- Staff schedules - Individual availability
CREATE TABLE staff_schedules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    staff_id INT NOT NULL,
    day_of_week ENUM('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    break_start_time TIME DEFAULT NULL,
    break_end_time TIME DEFAULT NULL,
    max_appointments_per_hour INT DEFAULT 2,
    effective_from DATE DEFAULT (CURDATE()),
    effective_until DATE DEFAULT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (staff_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_staff_id (staff_id),
    INDEX idx_day_of_week (day_of_week),
    INDEX idx_is_active (is_active),
    INDEX idx_effective_dates (effective_from, effective_until)
);

-- ===============================================
-- APPOINTMENT SYSTEM - ENHANCED
-- ===============================================

-- Appointments table - Complete with all enhancements
CREATE TABLE appointments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    uuid VARCHAR(36) UNIQUE DEFAULT (UUID()) COMMENT 'Public appointment identifier',
    patient_id INT NOT NULL,
    staff_id INT,
    branch_id INT NOT NULL,
    treatment_type_id INT,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    end_time TIME,
    duration_minutes INT DEFAULT 30,
    buffer_minutes INT DEFAULT 10,
    
    -- Status and Priority
    status ENUM('pending', 'approved', 'checked_in', 'in_progress', 'completed', 'cancelled', 'no_show', 'referred') DEFAULT 'pending',
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    
    -- Clinical Information
    reason VARCHAR(255),
    notes TEXT,
    staff_notes TEXT,
    clinical_notes TEXT,
    
    -- Cost and Billing
    estimated_cost DECIMAL(10,2) DEFAULT 0.00,
    actual_cost DECIMAL(10,2) DEFAULT NULL,
    payment_status ENUM('pending', 'partial', 'paid', 'refunded') DEFAULT 'pending',
    
    -- Workflow Timestamps
    confirmed_at TIMESTAMP NULL,
    checked_in_at TIMESTAMP NULL,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    cancelled_at TIMESTAMP NULL,
    
    -- Cancellation and Follow-up
    cancellation_reason TEXT,
    cancelled_by_id INT DEFAULT NULL,
    follow_up_required BOOLEAN DEFAULT FALSE,
    follow_up_date DATE DEFAULT NULL,
    follow_up_notes TEXT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (staff_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    FOREIGN KEY (treatment_type_id) REFERENCES treatment_types(id) ON DELETE SET NULL,
    FOREIGN KEY (cancelled_by_id) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_uuid (uuid),
    INDEX idx_patient_id (patient_id),
    INDEX idx_staff_id (staff_id),
    INDEX idx_branch_id (branch_id),
    INDEX idx_treatment_type_id (treatment_type_id),
    INDEX idx_appointment_date (appointment_date),
    INDEX idx_appointment_time (appointment_time),
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_payment_status (payment_status),
    INDEX idx_date_time (appointment_date, appointment_time),
    INDEX idx_created_at (created_at)
);

-- Walk-in appointments table - Enhanced with verification
CREATE TABLE walk_in_appointments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    uuid VARCHAR(36) UNIQUE DEFAULT (UUID()) COMMENT 'Public appointment identifier',
    
    -- Patient Information
    patient_name VARCHAR(100) NOT NULL,
    patient_phone VARCHAR(20),
    patient_email VARCHAR(100),
    patient_birthdate DATE,
    patient_address TEXT,
    patient_gender ENUM('male', 'female', 'other', 'prefer_not_to_say'),
    government_id_type ENUM('national_id', 'passport', 'drivers_license', 'other'),
    government_id_number VARCHAR(50),
    
    -- Appointment Details
    staff_id INT NOT NULL,
    branch_id INT NOT NULL,
    treatment_type_id INT,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    end_time TIME,
    duration_minutes INT DEFAULT 30,
    
    -- Status and Priority
    status ENUM('pending', 'approved', 'checked_in', 'in_progress', 'completed', 'cancelled', 'referred') DEFAULT 'pending',
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    
    -- Clinical Information
    reason VARCHAR(255),
    notes TEXT,
    staff_notes TEXT,
    clinical_notes TEXT,
    
    -- Cost and Billing
    estimated_cost DECIMAL(10,2) DEFAULT 0.00,
    actual_cost DECIMAL(10,2) DEFAULT NULL,
    payment_status ENUM('pending', 'partial', 'paid', 'refunded') DEFAULT 'pending',
    
    -- Verification and Conversion
    verified_by INT,
    verified_at TIMESTAMP NULL,
    converted_to_user_id INT DEFAULT NULL COMMENT 'If converted to regular patient',
    conversion_date TIMESTAMP NULL,
    
    -- Workflow Timestamps
    checked_in_at TIMESTAMP NULL,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    cancelled_at TIMESTAMP NULL,
    cancellation_reason TEXT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (staff_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    FOREIGN KEY (treatment_type_id) REFERENCES treatment_types(id) ON DELETE SET NULL,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (converted_to_user_id) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_uuid (uuid),
    INDEX idx_staff_id (staff_id),
    INDEX idx_branch_id (branch_id),
    INDEX idx_treatment_type (treatment_type_id),
    INDEX idx_appointment_date (appointment_date),
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_patient_phone (patient_phone),
    INDEX idx_patient_name (patient_name),
    INDEX idx_converted_user (converted_to_user_id),
    INDEX idx_created_at (created_at)
);

-- ===============================================
-- PATIENT REFERRAL SYSTEM - ENHANCED WITH TWO-STEP APPROVAL
-- ===============================================

-- Patient referrals table - Complete with patient approval workflow
CREATE TABLE patient_referrals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    uuid VARCHAR(36) UNIQUE DEFAULT (UUID()) COMMENT 'Public referral identifier',
    patient_id INT NOT NULL,
    from_branch_id INT NOT NULL,
    to_branch_id INT NOT NULL,
    from_staff_id INT NOT NULL,
    to_staff_id INT DEFAULT NULL,
    original_appointment_id INT DEFAULT NULL,
    new_appointment_id INT DEFAULT NULL,
    
    -- Referral Information
    reason TEXT NOT NULL,
    urgency ENUM('routine', 'urgent', 'emergency') DEFAULT 'routine',
    treatment_type_id INT,
    preferred_date DATE DEFAULT NULL,
    preferred_time TIME DEFAULT NULL,
    clinical_notes TEXT,
    
    -- Two-Step Approval Status
    status ENUM(
        'pending_patient_approval',
        'patient_approved', 
        'patient_rejected',
        'pending',           -- Now means "pending branch approval"
        'accepted', 
        'rejected', 
        'completed', 
        'cancelled'
    ) DEFAULT 'pending_patient_approval',
    
    -- Patient Response Tracking
    patient_approved_at TIMESTAMP NULL,
    patient_rejected_at TIMESTAMP NULL,
    patient_response_notes TEXT,
    patient_hidden_at TIMESTAMP NULL COMMENT 'When patient hid the referral notification',
    
    -- Branch Response Tracking
    responded_at TIMESTAMP NULL,
    response_notes TEXT,
    responding_staff_id INT DEFAULT NULL,
    
    -- Completion Tracking
    completed_at TIMESTAMP NULL,
    completing_staff_id INT DEFAULT NULL,
    completion_notes TEXT,
    
    -- Cancellation
    cancelled_at TIMESTAMP NULL,
    cancellation_reason TEXT,
    cancelled_by_id INT DEFAULT NULL,
    
    -- Expiration
    expires_at TIMESTAMP NULL DEFAULT (DATE_ADD(NOW(), INTERVAL 30 DAY)),
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (from_branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    FOREIGN KEY (to_branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    FOREIGN KEY (from_staff_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (to_staff_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (original_appointment_id) REFERENCES appointments(id) ON DELETE SET NULL,
    FOREIGN KEY (new_appointment_id) REFERENCES appointments(id) ON DELETE SET NULL,
    FOREIGN KEY (treatment_type_id) REFERENCES treatment_types(id) ON DELETE SET NULL,
    FOREIGN KEY (responding_staff_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (completing_staff_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (cancelled_by_id) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_uuid (uuid),
    INDEX idx_patient_id (patient_id),
    INDEX idx_from_branch (from_branch_id),
    INDEX idx_to_branch (to_branch_id),
    INDEX idx_status (status),
    INDEX idx_urgency (urgency),
    INDEX idx_patient_approved_at (patient_approved_at),
    INDEX idx_patient_rejected_at (patient_rejected_at),
    INDEX idx_responded_at (responded_at),
    INDEX idx_expires_at (expires_at),
    INDEX idx_created_at (created_at)
);

-- ===============================================
-- MEDICAL RECORDS AND DOCUMENTATION
-- ===============================================

-- Patient medical records
CREATE TABLE patient_medical_records (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    appointment_id INT DEFAULT NULL,
    staff_id INT NOT NULL,
    record_type ENUM('examination', 'treatment', 'follow_up', 'emergency', 'consultation') NOT NULL,
    chief_complaint TEXT,
    history_present_illness TEXT,
    medical_history TEXT,
    dental_history TEXT,
    allergies TEXT,
    medications TEXT,
    vital_signs JSON DEFAULT NULL,
    examination_findings TEXT,
    diagnosis TEXT,
    treatment_plan TEXT,
    treatment_provided TEXT,
    recommendations TEXT,
    next_visit_date DATE DEFAULT NULL,
    is_confidential BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL,
    FOREIGN KEY (staff_id) REFERENCES users(id) ON DELETE RESTRICT,
    
    INDEX idx_patient_id (patient_id),
    INDEX idx_appointment_id (appointment_id),
    INDEX idx_staff_id (staff_id),
    INDEX idx_record_type (record_type),
    INDEX idx_created_at (created_at)
);

-- Document management
CREATE TABLE patient_documents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    medical_record_id INT DEFAULT NULL,
    appointment_id INT DEFAULT NULL,
    document_name VARCHAR(255) NOT NULL,
    document_type ENUM('xray', 'photo', 'scan', 'report', 'form', 'other') NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT DEFAULT NULL,
    mime_type VARCHAR(100),
    file_hash VARCHAR(64) COMMENT 'SHA-256 hash for integrity',
    uploaded_by_id INT NOT NULL,
    is_confidential BOOLEAN DEFAULT TRUE,
    access_level ENUM('patient_only', 'staff_only', 'branch_staff', 'all_staff') DEFAULT 'staff_only',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (medical_record_id) REFERENCES patient_medical_records(id) ON DELETE SET NULL,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL,
    FOREIGN KEY (uploaded_by_id) REFERENCES users(id) ON DELETE RESTRICT,
    
    INDEX idx_patient_id (patient_id),
    INDEX idx_medical_record_id (medical_record_id),
    INDEX idx_document_type (document_type),
    INDEX idx_file_hash (file_hash),
    INDEX idx_created_at (created_at)
);

-- ===============================================
-- BILLING AND PAYMENT SYSTEM
-- ===============================================

-- Invoices table
CREATE TABLE invoices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    invoice_number VARCHAR(50) UNIQUE NOT NULL,
    patient_id INT NOT NULL,
    appointment_id INT DEFAULT NULL,
    branch_id INT NOT NULL,
    staff_id INT NOT NULL,
    invoice_date DATE NOT NULL,
    due_date DATE NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    tax_rate DECIMAL(5,2) DEFAULT 0.00,
    tax_amount DECIMAL(10,2) DEFAULT 0.00,
    discount_amount DECIMAL(10,2) DEFAULT 0.00,
    total_amount DECIMAL(10,2) NOT NULL,
    paid_amount DECIMAL(10,2) DEFAULT 0.00,
    balance_due DECIMAL(10,2) GENERATED ALWAYS AS (total_amount - paid_amount) STORED,
    status ENUM('draft', 'sent', 'paid', 'partial', 'overdue', 'cancelled') DEFAULT 'draft',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE RESTRICT,
    FOREIGN KEY (staff_id) REFERENCES users(id) ON DELETE RESTRICT,
    
    INDEX idx_invoice_number (invoice_number),
    INDEX idx_patient_id (patient_id),
    INDEX idx_appointment_id (appointment_id),
    INDEX idx_branch_id (branch_id),
    INDEX idx_invoice_date (invoice_date),
    INDEX idx_due_date (due_date),
    INDEX idx_status (status)
);

-- Invoice line items
CREATE TABLE invoice_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    invoice_id INT NOT NULL,
    treatment_type_id INT DEFAULT NULL,
    description VARCHAR(255) NOT NULL,
    quantity DECIMAL(8,2) DEFAULT 1.00,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) GENERATED ALWAYS AS (quantity * unit_price) STORED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (treatment_type_id) REFERENCES treatment_types(id) ON DELETE SET NULL,
    
    INDEX idx_invoice_id (invoice_id),
    INDEX idx_treatment_type_id (treatment_type_id)
);

-- Payments table
CREATE TABLE payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    invoice_id INT NOT NULL,
    payment_method ENUM('cash', 'card', 'bank_transfer', 'check', 'insurance', 'other') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reference_number VARCHAR(100),
    notes TEXT,
    processed_by_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE RESTRICT,
    FOREIGN KEY (processed_by_id) REFERENCES users(id) ON DELETE RESTRICT,
    
    INDEX idx_invoice_id (invoice_id),
    INDEX idx_payment_method (payment_method),
    INDEX idx_payment_date (payment_date),
    INDEX idx_reference_number (reference_number)
);

-- ===============================================
-- NOTIFICATION SYSTEM - ENHANCED
-- ===============================================

-- Notification templates
CREATE TABLE notification_templates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    type ENUM('email', 'sms', 'push', 'in_app') NOT NULL,
    subject VARCHAR(255),
    body_template TEXT NOT NULL,
    variables JSON DEFAULT NULL COMMENT 'Available template variables',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_name (name),
    INDEX idx_type (type),
    INDEX idx_is_active (is_active)
);

-- Notification queue
CREATE TABLE notification_queue (
    id INT PRIMARY KEY AUTO_INCREMENT,
    template_id INT DEFAULT NULL,
    recipient_id INT NOT NULL,
    type ENUM('email', 'sms', 'push', 'in_app') NOT NULL,
    recipient_address VARCHAR(255) NOT NULL COMMENT 'Email, phone, or device token',
    subject VARCHAR(255),
    message TEXT NOT NULL,
    variables JSON DEFAULT NULL,
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    status ENUM('pending', 'processing', 'sent', 'failed', 'cancelled') DEFAULT 'pending',
    scheduled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sent_at TIMESTAMP NULL,
    failed_at TIMESTAMP NULL,
    error_message TEXT,
    retry_count INT DEFAULT 0,
    max_retries INT DEFAULT 3,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (template_id) REFERENCES notification_templates(id) ON DELETE SET NULL,
    FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_template_id (template_id),
    INDEX idx_recipient_id (recipient_id),
    INDEX idx_type (type),
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_scheduled_at (scheduled_at),
    INDEX idx_sent_at (sent_at)
);

-- ===============================================
-- LEGACY NOTIFICATION SYSTEM (Compatibility)
-- ===============================================

-- Appointment history - For notification tracking
CREATE TABLE appointment_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    appointment_id INT,
    walkin_appointment_id INT DEFAULT NULL,
    patient_name VARCHAR(100) NOT NULL,
    patient_email VARCHAR(100),
    action VARCHAR(50) NOT NULL,
    previous_data JSON,
    new_data JSON,
    changed_by_id INT,
    branch_name VARCHAR(100),
    treatment_type VARCHAR(100),
    appointment_date DATE,
    appointment_time TIME,
    message TEXT,
    is_read BOOLEAN DEFAULT FALSE,
    notification_sent BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
    FOREIGN KEY (walkin_appointment_id) REFERENCES walk_in_appointments(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by_id) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_appointment_id (appointment_id),
    INDEX idx_walkin_appointment_id (walkin_appointment_id),
    INDEX idx_patient_email (patient_email),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at)
);

-- ===============================================
-- SYSTEM MANAGEMENT TABLES
-- ===============================================

-- System settings
CREATE TABLE system_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_setting_key (setting_key)
);

-- System logs - Enhanced with more context
CREATE TABLE system_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT DEFAULT NULL,
    level ENUM('info', 'warning', 'error', 'critical') DEFAULT 'info',
    category VARCHAR(50) DEFAULT 'general',
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    request_data JSON DEFAULT NULL,
    response_data JSON DEFAULT NULL,
    execution_time DECIMAL(8,3) DEFAULT NULL COMMENT 'Execution time in seconds',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_user_id (user_id),
    INDEX idx_level (level),
    INDEX idx_category (category),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
);

-- ===============================================
-- SCHEDULING AND TIME MANAGEMENT
-- ===============================================

-- Appointment time blocks - For preventing conflicts
CREATE TABLE appointment_time_blocks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    branch_id INT NOT NULL,
    staff_id INT DEFAULT NULL,
    appointment_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    appointment_id INT DEFAULT NULL,
    walkin_appointment_id INT DEFAULT NULL,
    is_blocked BOOLEAN DEFAULT FALSE,
    block_reason VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    FOREIGN KEY (staff_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
    FOREIGN KEY (walkin_appointment_id) REFERENCES walk_in_appointments(id) ON DELETE CASCADE,
    
    INDEX idx_branch_id (branch_id),
    INDEX idx_staff_id (staff_id),
    INDEX idx_appointment_date (appointment_date),
    INDEX idx_appointment_id (appointment_id),
    INDEX idx_walkin_appointment_id (walkin_appointment_id),
    INDEX idx_time_range (appointment_date, start_time, end_time)
);

-- Branch schedules - Operating hours
CREATE TABLE branch_schedules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    branch_id INT NOT NULL,
    day_of_week ENUM('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday') NOT NULL,
    open_time TIME NOT NULL,
    close_time TIME NOT NULL,
    break_start_time TIME DEFAULT NULL,
    break_end_time TIME DEFAULT NULL,
    is_open BOOLEAN DEFAULT TRUE,
    effective_from DATE DEFAULT (CURDATE()),
    effective_until DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    
    INDEX idx_branch_id (branch_id),
    INDEX idx_day_of_week (day_of_week),
    INDEX idx_is_open (is_open),
    INDEX idx_effective_dates (effective_from, effective_until)
);

-- ===============================================
-- BUSINESS INTELLIGENCE AND REPORTING
-- ===============================================

-- Appointment statistics - For reporting
CREATE TABLE appointment_statistics (
    id INT PRIMARY KEY AUTO_INCREMENT,
    date DATE NOT NULL,
    branch_id INT NOT NULL,
    treatment_type_id INT DEFAULT NULL,
    total_appointments INT DEFAULT 0,
    completed_appointments INT DEFAULT 0,
    cancelled_appointments INT DEFAULT 0,
    no_show_appointments INT DEFAULT 0,
    total_revenue DECIMAL(10,2) DEFAULT 0.00,
    average_duration_minutes INT DEFAULT 0,
    patient_satisfaction_score DECIMAL(3,2) DEFAULT NULL,
    staff_utilization_percentage DECIMAL(5,2) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    FOREIGN KEY (treatment_type_id) REFERENCES treatment_types(id) ON DELETE SET NULL,
    
    UNIQUE KEY unique_date_branch_treatment (date, branch_id, treatment_type_id),
    INDEX idx_date (date),
    INDEX idx_branch_id (branch_id),
    INDEX idx_treatment_type_id (treatment_type_id)
);

-- ===============================================
-- DEFAULT DATA INSERTION
-- ===============================================

-- Insert default treatment categories
INSERT INTO treatment_categories (name, description, color_code, display_order) VALUES
('General Dentistry', 'Basic dental care and maintenance', '#4CAF50', 1),
('Restorative', 'Repair and restoration procedures', '#2196F3', 2),
('Oral Surgery', 'Surgical dental procedures', '#F44336', 3),
('Orthodontics', 'Teeth alignment and braces', '#9C27B0', 4),
('Cosmetic', 'Aesthetic dental procedures', '#FF9800', 5),
('Periodontics', 'Gum and supporting structure treatment', '#607D8B', 6),
('Endodontics', 'Root canal and pulp treatments', '#795548', 7),
('Prosthodontics', 'Dental prosthetics and implants', '#009688', 8);

-- Insert default system settings
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('appointment_booking_advance_days', '30', 'How many days in advance patients can book appointments'),
('appointment_cancellation_hours', '24', 'Minimum hours before appointment for cancellation'),
('default_appointment_duration', '30', 'Default appointment duration in minutes'),
('email_notifications_enabled', 'true', 'Enable email notifications'),
('sms_notifications_enabled', 'false', 'Enable SMS notifications'),
('auto_confirm_appointments', 'false', 'Automatically confirm new appointments'),
('patient_portal_enabled', 'true', 'Enable patient portal access'),
('walk_in_appointments_enabled', 'true', 'Allow walk-in appointments'),
('referral_expiration_days', '30', 'Days before referrals expire'),
('max_daily_appointments_per_patient', '3', 'Maximum appointments per patient per day');

-- Insert default notification templates
INSERT INTO notification_templates (name, type, subject, body_template, variables) VALUES
('appointment_confirmation', 'email', 'Appointment Confirmation - {{clinic_name}}', 
 'Dear {{patient_name}}, your appointment has been confirmed for {{appointment_date}} at {{appointment_time}}. Location: {{branch_name}}. Please arrive 15 minutes early.',
 '{"patient_name": "string", "appointment_date": "date", "appointment_time": "time", "branch_name": "string", "clinic_name": "string"}'),
('appointment_reminder', 'email', 'Appointment Reminder - Tomorrow',
 'Dear {{patient_name}}, this is a reminder of your appointment tomorrow ({{appointment_date}}) at {{appointment_time}} at {{branch_name}}.',
 '{"patient_name": "string", "appointment_date": "date", "appointment_time": "time", "branch_name": "string"}'),
('referral_patient_approval', 'email', 'Referral Request - Action Required',
 'Dear {{patient_name}}, you have been referred to {{to_branch_name}} for {{treatment_type}}. Please review and approve this referral.',
 '{"patient_name": "string", "to_branch_name": "string", "treatment_type": "string", "reason": "string"}');

-- ===============================================
-- INDEXES FOR PERFORMANCE OPTIMIZATION
-- ===============================================

-- Additional composite indexes for common queries
ALTER TABLE appointments ADD INDEX idx_branch_date_status (branch_id, appointment_date, status);
ALTER TABLE appointments ADD INDEX idx_staff_date (staff_id, appointment_date);
ALTER TABLE walk_in_appointments ADD INDEX idx_branch_date_status (branch_id, appointment_date, status);
ALTER TABLE patient_referrals ADD INDEX idx_patient_status (patient_id, status);
ALTER TABLE patient_referrals ADD INDEX idx_to_branch_status (to_branch_id, status);

-- ===============================================
-- FINAL SETUP COMPLETION
-- ===============================================

-- Enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Commit all changes
COMMIT;

-- Display success message
SELECT 
    'DATABASE_SCHEMA_COMPLETE' as status,
    'Final consolidated schema with all features has been created successfully' as message,
    COUNT(*) as total_tables,
    NOW() as completed_at
FROM information_schema.tables 
WHERE table_schema = 'dental_clinic_db';