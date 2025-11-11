-- ===============================================
-- ADD REMINDER COLUMNS TO APPOINTMENTS TABLE
-- Version: 1.0
-- Date: 2025-11-03
-- Description: Add reminder tracking columns for appointment reminder system
-- ===============================================

USE dental_clinic_db;

-- Add reminder tracking columns to appointments table
ALTER TABLE appointments 
ADD COLUMN reminder_sent TINYINT(1) DEFAULT 0 COMMENT 'Whether reminder email has been sent (0=no, 1=yes)',
ADD COLUMN reminder_sent_at TIMESTAMP NULL DEFAULT NULL COMMENT 'When the reminder email was sent';

-- Create index for efficient reminder queries
CREATE INDEX idx_appointments_reminder_check ON appointments(appointment_date, appointment_time, reminder_sent, status);

-- Update existing appointments to have reminder_sent = 0 (not sent)
UPDATE appointments SET reminder_sent = 0 WHERE reminder_sent IS NULL;

-- Show the updated table structure
DESCRIBE appointments;