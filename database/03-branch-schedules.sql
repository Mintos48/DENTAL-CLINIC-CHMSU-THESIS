-- ===============================================
-- DENTAL CLINIC BRANCH SCHEDULES
-- Version: 1.0
-- Date: 2025-10-30
-- Description: Operating hours for all branches
-- Prerequisite: Run 01-schema.sql and 02-reference-data.sql first
-- ===============================================

USE dental_clinic_db;

-- ===============================================
-- CLINIC OPERATING HOURS:
-- Monday - Saturday: 9:00 AM - 5:00 PM
-- Sunday: 1:00 PM - 5:00 PM
-- ===============================================

-- -- Clear existing schedule data (if any)
-- DELETE FROM branch_schedules;

-- Insert operating hours for all active branches
-- Talisay Branch (ID: 1)
INSERT INTO branch_schedules (branch_id, day_of_week, open_time, close_time, is_open) VALUES
(1, 'monday', '09:00:00', '17:00:00', 1),
(1, 'tuesday', '09:00:00', '17:00:00', 1),
(1, 'wednesday', '09:00:00', '17:00:00', 1),
(1, 'thursday', '09:00:00', '17:00:00', 1),
(1, 'friday', '09:00:00', '17:00:00', 1),
(1, 'saturday', '09:00:00', '17:00:00', 1),
(1, 'sunday', '13:00:00', '17:00:00', 1);

-- Silay Branch (ID: 2)
INSERT INTO branch_schedules (branch_id, day_of_week, open_time, close_time, is_open) VALUES
(2, 'monday', '09:00:00', '17:00:00', 1),
(2, 'tuesday', '09:00:00', '17:00:00', 1),
(2, 'wednesday', '09:00:00', '17:00:00', 1),
(2, 'thursday', '09:00:00', '17:00:00', 1),
(2, 'friday', '09:00:00', '17:00:00', 1),
(2, 'saturday', '09:00:00', '17:00:00', 1),
(2, 'sunday', '13:00:00', '17:00:00', 1);

-- Sarabia Branch (ID: 3)
INSERT INTO branch_schedules (branch_id, day_of_week, open_time, close_time, is_open) VALUES
(3, 'monday', '09:00:00', '17:00:00', 1),
(3, 'tuesday', '09:00:00', '17:00:00', 1),
(3, 'wednesday', '09:00:00', '17:00:00', 1),
(3, 'thursday', '09:00:00', '17:00:00', 1),
(3, 'friday', '09:00:00', '17:00:00', 1),
(3, 'saturday', '09:00:00', '17:00:00', 1),
(3, 'sunday', '13:00:00', '17:00:00', 1);

-- Update branches table with formatted operating hours summary
UPDATE branches SET operating_hours = 'Mon-Sat: 9:00 AM - 5:00 PM, Sun: 1:00 PM - 5:00 PM' WHERE id IN (1, 2, 3);

-- ===============================================
-- BRANCH SCHEDULES COMPLETE
-- ===============================================
COMMIT;