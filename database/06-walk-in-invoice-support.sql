-- ===============================================
-- WALK-IN INVOICE SUPPORT MIGRATION
-- Date: 2025-11-03
-- Description: Add support for walk-in appointments in invoice system
-- ===============================================

USE dental_clinic_db;

-- Modify invoices table to support walk-in appointments
ALTER TABLE invoices 
MODIFY COLUMN patient_id INT DEFAULT NULL COMMENT 'NULL for walk-in appointments',
ADD COLUMN walk_in_appointment_id INT DEFAULT NULL COMMENT 'Reference to walk_in_appointments table',
ADD COLUMN patient_name VARCHAR(100) DEFAULT NULL COMMENT 'Patient name for walk-in appointments',
ADD COLUMN patient_email VARCHAR(100) DEFAULT NULL COMMENT 'Patient email for walk-in appointments',
ADD COLUMN patient_phone VARCHAR(20) DEFAULT NULL COMMENT 'Patient phone for walk-in appointments';

-- Add foreign key for walk-in appointments
ALTER TABLE invoices 
ADD CONSTRAINT fk_walk_in_appointment 
FOREIGN KEY (walk_in_appointment_id) REFERENCES walk_in_appointments(id) ON DELETE SET NULL;

-- Add index for walk-in appointment lookup
ALTER TABLE invoices 
ADD INDEX idx_walk_in_appointment_id (walk_in_appointment_id);

-- Add constraint to ensure either patient_id or walk_in_appointment_id is provided
ALTER TABLE invoices 
ADD CONSTRAINT chk_patient_or_walkin 
CHECK (
    (patient_id IS NOT NULL AND walk_in_appointment_id IS NULL) OR 
    (patient_id IS NULL AND walk_in_appointment_id IS NOT NULL)
);

-- Update the existing foreign key constraint for patient_id to allow NULL
ALTER TABLE invoices 
DROP FOREIGN KEY invoices_ibfk_1;

ALTER TABLE invoices 
ADD CONSTRAINT fk_invoice_patient 
FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE RESTRICT;

-- Add comments for clarity
ALTER TABLE invoices 
MODIFY COLUMN patient_id INT DEFAULT NULL COMMENT 'User ID for registered patients (NULL for walk-in)',
MODIFY COLUMN appointment_id INT DEFAULT NULL COMMENT 'Regular appointment ID (NULL for walk-in)',
MODIFY COLUMN walk_in_appointment_id INT DEFAULT NULL COMMENT 'Walk-in appointment ID (NULL for regular)';

-- Display success message
SELECT 'Walk-in invoice support migration completed successfully!' as Status;