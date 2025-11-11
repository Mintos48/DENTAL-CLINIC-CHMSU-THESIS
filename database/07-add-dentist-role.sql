-- ===============================================
-- Add Dentist Role Migration
-- Date: 2025-11-07
-- Description: Add 'dentist' role to the user roles enum
-- ===============================================

USE dental_clinic_db;

-- Add dentist role to the users table enum
ALTER TABLE users MODIFY COLUMN role ENUM('patient', 'staff', 'dentist', 'admin', 'super_admin') NOT NULL;

-- Optional: Update existing staff users to dentist if they have dental specializations
-- Uncomment the following lines if you want to automatically convert specialized staff to dentists:

-- UPDATE users SET role = 'dentist' 
-- WHERE role = 'staff' 
-- AND id IN (
--     SELECT DISTINCT staff_id 
--     FROM staff_specializations 
--     WHERE treatment_type_id IN (
--         SELECT id FROM treatment_types 
--         WHERE category_id IN (
--             SELECT id FROM treatment_categories 
--             WHERE name IN ('General Dentistry', 'Restorative', 'Oral Surgery', 'Orthodontics', 'Cosmetic', 'Periodontics', 'Endodontics', 'Prosthodontics')
--         )
--     )
-- );

-- Show success message
SELECT 'DENTIST_ROLE_ADDED' as status, 'Dentist role has been successfully added to the database' as message;