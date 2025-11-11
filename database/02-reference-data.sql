-- ===============================================
-- DENTAL CLINIC REFERENCE DATA
-- Version: 1.0
-- Date: 2025-10-30
-- Description: Static reference data and system settings
-- Prerequisite: Run 01-schema.sql first
-- ===============================================

USE dental_clinic_db;

-- ===============================================
-- BRANCH DATA
-- ===============================================

-- Insert Branches
INSERT INTO branches (id, name, location, phone, email, operating_hours, status) VALUES
(1, 'Happy Teeth Dental', '#22 Capt. Sabi St., Brgy. Zone 12, Talisay City, Negros Occidental, Philippines, Talisay, Philippines', '0994-382-1331', 'main@dentalclinic.com', '8:00 AM - 6:00 PM', 'active'),
(2, 'Ardent Dental Clinic', 'Door #9 Montinola Bldg., Bonifacio St., Brgy. 4, Silay City, Philippines', '0961-108-4393', 'northside@dentalclinic.com', '9:00 AM - 7:00 PM', 'active'),
(3, 'Gamboa Dental Clinic', 'Corner Luis Cuaycong Felix Montinola St., Poblacion II, Enrique B. Magalona, Philippines', '0939-466-3147', 'southside@dentalclinic.com', '8:30 AM - 5:30 PM', 'active');

-- ===============================================
-- TREATMENT TYPES DATA
-- ===============================================

-- Insert Treatment Types
INSERT INTO treatment_types (name, description, duration_minutes, color_code, is_active) VALUES
-- Basic Services
('Dental Consultation', 'Initial dental examination and consultation', 30, '#607D8B', 1),
('Dental Cleaning', 'Professional teeth cleaning and oral prophylaxis', 60, '#4CAF50', 1),
('Fluoride Treatment', 'Preventive fluoride application for cavity protection', 30, '#C5E1A5', 1),

-- Restorative Procedures
('Tooth Filling', 'Composite or amalgam dental filling procedure', 90, '#2196F3', 1),
('Dental Crown', 'Crown placement procedure for damaged teeth', 150, '#9C27B0', 1),
('Dental Bridge', 'Bridge placement for missing teeth restoration', 120, '#FFC107', 1),
('Dental Inlay/Onlay', 'Custom-fitted dental restoration', 105, '#FF9800', 1),

-- Endodontic Procedures
('Root Canal Treatment', 'Root canal therapy for infected tooth pulp', 180, '#F44336', 1),
('Root Canal Retreatment', 'Retreatment of previously treated root canal', 210, '#E57373', 1),
('Apicoectomy', 'Surgical root-end resection procedure', 120, '#D32F2F', 1),

-- Oral Surgery
('Simple Tooth Extraction', 'Basic tooth removal procedure', 60, '#FF5722', 1),
('Surgical Tooth Extraction', 'Complex surgical tooth removal', 90, '#BF360C', 1),
('Wisdom Tooth Extraction', 'Removal of impacted wisdom teeth', 120, '#E91E63', 1),
('Dental Implant Placement', 'Surgical placement of dental implant', 240, '#795548', 1),
('Bone Grafting', 'Bone augmentation procedure for implants', 180, '#8D6E63', 1),

-- Periodontal Procedures
('Periodontal Scaling', 'Deep cleaning for gum disease treatment', 90, '#3F51B5', 1),
('Root Planing', 'Smoothing of tooth root surfaces', 120, '#5C6BC0', 1),
('Gingivectomy', 'Surgical removal of gum tissue', 90, '#9FA8DA', 1),
('Gum Grafting', 'Soft tissue grafting procedure', 150, '#7986CB', 1),

-- Prosthodontic Services
('Complete Denture', 'Full removable denture fitting', 180, '#009688', 1),
('Partial Denture', 'Partial removable denture fitting', 150, '#4DB6AC', 1),
('Denture Repair', 'Repair and adjustment of existing dentures', 60, '#80CBC4', 1),
('Denture Reline', 'Relining of ill-fitting dentures', 90, '#B2DFDB', 1),

-- Orthodontic Services
('Orthodontic Consultation', 'Initial braces and alignment consultation', 60, '#673AB7', 1),
('Braces Installation', 'Initial placement of orthodontic braces', 120, '#9575CD', 1),
('Braces Adjustment', 'Monthly orthodontic adjustment appointment', 45, '#B39DDB', 1),
('Braces Removal', 'Final removal of orthodontic appliances', 90, '#D1C4E9', 1),
('Retainer Fitting', 'Custom retainer fabrication and fitting', 60, '#EDE7F6', 1),

-- Cosmetic Dentistry
('Teeth Whitening', 'Professional teeth bleaching procedure', 90, '#00BCD4', 1),
('Dental Veneers', 'Porcelain veneer placement', 150, '#26C6DA', 1),
('Cosmetic Bonding', 'Aesthetic tooth bonding procedure', 75, '#4DD0E1', 1),
('Smile Makeover', 'Comprehensive cosmetic dental treatment', 240, '#81D4FA', 1),

-- Pediatric Dentistry
('Pediatric Checkup', 'Children dental examination and cleaning', 45, '#E1BEE7', 1),
('Pediatric Filling', 'Child-friendly cavity filling procedure', 60, '#F8BBD9', 1),
('Sealant Application', 'Protective sealants for children teeth', 30, '#FCE4EC', 1),
('Space Maintainer', 'Appliance to maintain space for permanent teeth', 60, '#F3E5F5', 1),

-- Specialized Procedures
('TMJ Treatment', 'Treatment for temporomandibular joint disorders', 90, '#FFCCBC', 1),
('Sleep Apnea Appliance', 'Custom oral appliance for sleep disorders', 120, '#FFF3E0', 1),
('Oral Cancer Screening', 'Comprehensive oral cancer examination', 45, '#EFEBE9', 1),
('Dental X-Ray', 'Digital radiographic examination', 30, '#CDDC39', 1),

-- Emergency Services
('Emergency Consultation', 'Urgent dental problem assessment', 45, '#FF1744', 1),
('Emergency Pain Relief', 'Immediate treatment for dental pain', 60, '#FF5252', 1),
('Broken Tooth Repair', 'Emergency repair of fractured tooth', 90, '#FF8A80', 1),

-- Maintenance and Follow-up
('Post-Op Follow-up', 'Post-treatment examination and monitoring', 30, '#E0E0E0', 1),
('Suture Removal', 'Removal of surgical sutures', 20, '#BDBDBD', 1),
('Adjustment Visit', 'Minor adjustments to dental work', 30, '#9E9E9E', 1);

-- ===============================================
-- BRANCH SERVICES PRICING
-- ===============================================

-- Insert Branch Services (linking treatment types to branches with pricing)
-- Talisay Branch (Branch ID 1) - General dentistry focus
INSERT INTO branch_services (branch_id, treatment_type_id, is_available, price) VALUES
(1, 1, 1, 1500.00),   -- Dental Consultation
(1, 2, 1, 2500.00),   -- Dental Cleaning
(1, 3, 1, 800.00),    -- Fluoride Treatment
(1, 4, 1, 3500.00),   -- Tooth Filling
(1, 5, 1, 20000.00),  -- Dental Crown
(1, 6, 1, 18000.00),  -- Dental Bridge
(1, 8, 1, 15000.00),  -- Root Canal Treatment
(1, 11, 1, 2000.00),  -- Simple Tooth Extraction
(1, 12, 1, 4000.00),  -- Surgical Tooth Extraction
(1, 16, 1, 4000.00),  -- Periodontal Scaling
(1, 29, 1, 8000.00),  -- Teeth Whitening
(1, 33, 1, 2000.00),  -- Pediatric Checkup
(1, 38, 1, 1000.00),  -- Dental X-Ray
(1, 39, 1, 2000.00),  -- Emergency Consultation
(1, 42, 1, 1000.00);  -- Post-Op Follow-up

-- Silay Branch (Branch ID 2) - Orthodontics and surgery focus
INSERT INTO branch_services (branch_id, treatment_type_id, is_available, price) VALUES
(2, 1, 1, 1500.00),   -- Dental Consultation
(2, 2, 1, 2500.00),   -- Dental Cleaning
(2, 4, 1, 3500.00),   -- Tooth Filling
(2, 5, 1, 20000.00),  -- Dental Crown
(2, 8, 1, 15000.00),  -- Root Canal Treatment
(2, 11, 1, 2000.00),  -- Simple Tooth Extraction
(2, 12, 1, 4000.00),  -- Surgical Tooth Extraction
(2, 13, 1, 8000.00),  -- Wisdom Tooth Extraction
(2, 14, 1, 50000.00), -- Dental Implant Placement
(2, 15, 1, 30000.00), -- Bone Grafting
(2, 24, 1, 1500.00),  -- Orthodontic Consultation
(2, 25, 1, 80000.00), -- Braces Installation
(2, 26, 1, 3000.00),  -- Braces Adjustment
(2, 27, 1, 5000.00),  -- Braces Removal
(2, 28, 1, 8000.00),  -- Retainer Fitting
(2, 38, 1, 1000.00),  -- Dental X-Ray
(2, 39, 1, 2000.00);  -- Emergency Consultation

-- Sarabia Branch (Branch ID 3) - Cosmetic and specialized procedures
INSERT INTO branch_services (branch_id, treatment_type_id, is_available, price) VALUES
(3, 1, 1, 1500.00),   -- Dental Consultation
(3, 2, 1, 2500.00),   -- Dental Cleaning
(3, 4, 1, 3500.00),   -- Tooth Filling
(3, 5, 1, 20000.00),  -- Dental Crown
(3, 6, 1, 18000.00),  -- Dental Bridge
(3, 8, 1, 15000.00),  -- Root Canal Treatment
(3, 14, 1, 50000.00), -- Dental Implant Placement
(3, 20, 1, 25000.00), -- Complete Denture
(3, 21, 1, 18000.00), -- Partial Denture
(3, 29, 1, 8000.00),  -- Teeth Whitening
(3, 30, 1, 25000.00), -- Dental Veneers
(3, 31, 1, 6000.00),  -- Cosmetic Bonding
(3, 32, 1, 100000.00),-- Smile Makeover
(3, 35, 1, 6000.00),  -- TMJ Treatment
(3, 36, 1, 35000.00), -- Sleep Apnea Appliance
(3, 37, 1, 2500.00),  -- Oral Cancer Screening
(3, 38, 1, 1000.00),  -- Dental X-Ray
(3, 39, 1, 2000.00);  -- Emergency Consultation

-- ===============================================
-- SYSTEM SETTINGS
-- ===============================================

-- Insert System Settings
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('system_name', 'Dental Clinic Management System', 'Name of the dental clinic system'),
('appointment_duration', '30', 'Default appointment duration in minutes'),
('timezone', 'Asia/Manila', 'System timezone'),
('email_verification_required', '1', 'Require email verification for new accounts'),
('two_factor_auth', '0', 'Enable two-factor authentication'),
('password_expiry_days', '90', 'Password expiry period in days'),
('email_notifications', '1', 'Send email notifications'),
('sms_notifications', '0', 'Send SMS notifications'),
('notification_email', 'admin@dentalclinic.com', 'System notification email'),
('max_appointments_per_day', '20', 'Maximum appointments per day per branch'),
('booking_advance_days', '30', 'How many days in advance patients can book'),
('session_timeout', '3600', 'Session timeout in seconds');

-- ===============================================
-- REFERENCE DATA COMPLETE
-- ===============================================
COMMIT;