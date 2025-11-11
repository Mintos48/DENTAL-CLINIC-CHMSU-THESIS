-- Enhanced Dentist Dashboard Features Migration
-- Adds support for credentials management and email logging

-- Create dentist_credentials table
CREATE TABLE IF NOT EXISTS dentist_credentials (
    id INT PRIMARY KEY AUTO_INCREMENT,
    dentist_id INT NOT NULL,
    license_number VARCHAR(255),
    specialization VARCHAR(255),
    experience_years INT DEFAULT 0,
    education VARCHAR(500),
    professional_bio TEXT,
    license_file VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (dentist_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_dentist (dentist_id)
);

-- Create clinic_credentials table
CREATE TABLE IF NOT EXISTS clinic_credentials (
    id INT PRIMARY KEY AUTO_INCREMENT,
    branch_id INT NOT NULL,
    clinic_license VARCHAR(255),
    business_permit VARCHAR(255),
    accreditations VARCHAR(500),
    established_year INT,
    services_offered TEXT,
    clinic_photos JSON,
    certifications JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    UNIQUE KEY unique_branch (branch_id)
);

-- Create email_logs table for tracking sent emails
CREATE TABLE IF NOT EXISTS email_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    appointment_id INT,
    recipient_email VARCHAR(255) NOT NULL,
    subject VARCHAR(500),
    status ENUM('sent', 'failed') DEFAULT 'sent',
    error_message TEXT,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL,
    INDEX idx_appointment (appointment_id),
    INDEX idx_recipient (recipient_email),
    INDEX idx_sent_at (sent_at)
);

-- Add indexes for better performance
ALTER TABLE dentist_credentials ADD INDEX idx_dentist_id (dentist_id);
ALTER TABLE clinic_credentials ADD INDEX idx_branch_id (branch_id);

-- Update users table to ensure better staff management
ALTER TABLE users ADD COLUMN last_login TIMESTAMP NULL DEFAULT NULL;

-- Create uploads directory structure (handled by PHP, but documented here)
-- uploads/
--   credentials/    (for license files, certificates)
--   clinic_photos/  (for clinic images)

SELECT 'ENHANCED_DENTIST_FEATURES_CREATED' as status, 'Enhanced dentist dashboard tables created successfully' as message;