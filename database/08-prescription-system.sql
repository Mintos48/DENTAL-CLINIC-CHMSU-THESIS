-- ===============================================
-- PRESCRIPTION SYSTEM FOR DENTIST DASHBOARD
-- Date: 2025-11-07
-- Description: Add prescription functionality for dentists
-- ===============================================

USE dental_clinic_db;

-- Prescriptions table - stores prescription header information
CREATE TABLE prescriptions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    uuid VARCHAR(36) UNIQUE DEFAULT (UUID()) COMMENT 'Public prescription identifier',
    appointment_id INT NOT NULL,
    patient_id INT NOT NULL,
    dentist_id INT NOT NULL,
    branch_id INT NOT NULL,
    
    -- Prescription Details
    prescription_date DATE NOT NULL DEFAULT (CURRENT_DATE),
    diagnosis TEXT,
    instructions TEXT,
    follow_up_required BOOLEAN DEFAULT FALSE,
    follow_up_date DATE DEFAULT NULL,
    
    -- Status and Workflow
    status ENUM('draft', 'active', 'completed', 'cancelled') DEFAULT 'draft',
    is_referred BOOLEAN DEFAULT FALSE COMMENT 'True if this prescription is for a referred patient',
    referring_prescription_id INT DEFAULT NULL COMMENT 'Link to original prescription if this is from a referral',
    
    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT DEFAULT NULL,
    updated_by INT DEFAULT NULL,
    
    -- Foreign Keys
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (dentist_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    FOREIGN KEY (referring_prescription_id) REFERENCES prescriptions(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    
    -- Indexes
    INDEX idx_appointment_id (appointment_id),
    INDEX idx_patient_id (patient_id),
    INDEX idx_dentist_id (dentist_id),
    INDEX idx_branch_id (branch_id),
    INDEX idx_prescription_date (prescription_date),
    INDEX idx_status (status),
    INDEX idx_is_referred (is_referred),
    INDEX idx_referring_prescription (referring_prescription_id)
);

-- Prescription medications table - stores individual medication items
CREATE TABLE prescription_medications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    prescription_id INT NOT NULL,
    
    -- Medication Information
    medication_name VARCHAR(255) NOT NULL,
    dosage VARCHAR(100) NOT NULL COMMENT 'e.g., 500mg, 10ml, etc.',
    form ENUM('tablet', 'capsule', 'syrup', 'injection', 'cream', 'gel', 'mouthwash', 'drops', 'other') DEFAULT 'tablet',
    
    -- Dosing Instructions
    frequency VARCHAR(100) NOT NULL COMMENT 'e.g., twice daily, every 8 hours, etc.',
    duration VARCHAR(100) NOT NULL COMMENT 'e.g., 7 days, 2 weeks, until symptoms resolve',
    quantity VARCHAR(50) NOT NULL COMMENT 'Total quantity to dispense',
    
    -- Administration Instructions
    instructions TEXT COMMENT 'Special instructions for taking the medication',
    with_food BOOLEAN DEFAULT NULL COMMENT 'NULL=not specified, TRUE=with food, FALSE=without food',
    
    -- Priority and Notes
    is_priority BOOLEAN DEFAULT FALSE COMMENT 'Priority medication that must be taken',
    pharmacy_notes TEXT COMMENT 'Special notes for pharmacist',
    
    -- Metadata
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign Keys
    FOREIGN KEY (prescription_id) REFERENCES prescriptions(id) ON DELETE CASCADE,
    
    -- Indexes
    INDEX idx_prescription_id (prescription_id),
    INDEX idx_medication_name (medication_name),
    INDEX idx_is_priority (is_priority),
    INDEX idx_sort_order (sort_order)
);

-- Add prescription_id to invoices table to link prescriptions to billing
ALTER TABLE invoices 
ADD COLUMN prescription_id INT DEFAULT NULL AFTER appointment_id,
ADD FOREIGN KEY (prescription_id) REFERENCES prescriptions(id) ON DELETE SET NULL,
ADD INDEX idx_prescription_id (prescription_id);

-- Add prescription tracking to patient referrals
ALTER TABLE patient_referrals 
ADD COLUMN original_prescription_id INT DEFAULT NULL COMMENT 'Prescription from referring branch',
ADD COLUMN transferred_prescription_id INT DEFAULT NULL COMMENT 'New prescription at receiving branch',
ADD FOREIGN KEY (original_prescription_id) REFERENCES prescriptions(id) ON DELETE SET NULL,
ADD FOREIGN KEY (transferred_prescription_id) REFERENCES prescriptions(id) ON DELETE SET NULL,
ADD INDEX idx_original_prescription (original_prescription_id),
ADD INDEX idx_transferred_prescription (transferred_prescription_id);

-- Success message
SELECT 'PRESCRIPTION_SYSTEM_CREATED' as status, 'Prescription system tables have been successfully created' as message;