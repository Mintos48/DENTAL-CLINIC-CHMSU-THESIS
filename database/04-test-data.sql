-- ===============================================
-- DENTAL CLINIC TEST DATA
-- Version: 1.0
-- Date: 2025-10-30
-- Description: Sample data for development and testing
-- Prerequisite: Run 01-schema.sql, 02-reference-data.sql, and 03-branch-schedules.sql first
-- ===============================================

USE dental_clinic_db;

-- ===============================================
-- TEST USERS DATA
-- ===============================================

-- Insert Admin Users
INSERT INTO users (name, email, password, role, branch_id, phone, address, date_of_birth, gender, status, email_verified) VALUES
('Dr. John Smith', 'admin@dentalclinic.com', '$2a$12$amvPWsaWtB9EmYlIKNUWKeL1u6j0v5Hvg4FLiY7sV0BIlVJd/Ti2e', 'admin', 1, '555-1001', '100 Admin St, Executive District', '1975-05-15', 'male', 'active', TRUE),
('Dr. Sarah Johnson', 'sarah.admin@dentalclinic.com', '$2a$12$amvPWsaWtB9EmYlIKNUWKeL1u6j0v5Hvg4FLiY7sV0BIlVJd/Ti2e', 'admin', 1, '555-1002', '200 Manager Ave, Leadership Zone', '1980-08-22', 'female', 'active', TRUE);

-- Insert Staff Users
INSERT INTO users (name, email, password, role, branch_id, phone, address, date_of_birth, gender, status, email_verified) VALUES
('Dr. Michael Brown', 'michael.staff@dentalclinic.com', '$2a$12$amvPWsaWtB9EmYlIKNUWKeL1u6j0v5Hvg4FLiY7sV0BIlVJd/Ti2e', 'staff', 1, '555-2001', '301 Staff St, Professional Area', '1985-03-10', 'male', 'active', TRUE),
('Dr. Emily Davis', 'emily.staff@dentalclinic.com', '$2a$12$amvPWsaWtB9EmYlIKNUWKeL1u6j0v5Hvg4FLiY7sV0BIlVJd/Ti2e', 'staff', 1, '555-2002', '302 Medical Lane, Health District', '1988-07-18', 'female', 'active', TRUE),
('Dr. Robert Wilson', 'robert.staff@dentalclinic.com', '$2a$12$amvPWsaWtB9EmYlIKNUWKeL1u6j0v5Hvg4FLiY7sV0BIlVJd/Ti2e', 'staff', 2, '555-2003', '303 North Professional Dr', '1982-11-25', 'male', 'active', TRUE),
('Dr. Lisa Anderson', 'lisa.staff@dentalclinic.com', '$2a$12$amvPWsaWtB9EmYlIKNUWKeL1u6j0v5Hvg4FLiY7sV0BIlVJd/Ti2e', 'staff', 2, '555-2004', '304 Northside Medical Plaza', '1990-04-12', 'female', 'active', TRUE),
('Dr. David Martinez', 'david.staff@dentalclinic.com', '$2a$12$amvPWsaWtB9EmYlIKNUWKeL1u6j0v5Hvg4FLiY7sV0BIlVJd/Ti2e', 'staff', 3, '555-2005', '305 South Healthcare Blvd', '1987-09-08', 'male', 'active', TRUE),
('Dr. Jennifer Taylor', 'jennifer.staff@dentalclinic.com', '$2a$12$amvPWsaWtB9EmYlIKNUWKeL1u6j0v5Hvg4FLiY7sV0BIlVJd/Ti2e', 'staff', 3, '555-2006', '306 Southside Dental Center', '1992-01-30', 'female', 'active', TRUE);

-- Insert Dentist Users
INSERT INTO users (name, email, password, role, branch_id, phone, address, date_of_birth, gender, status, email_verified) VALUES
('Dr. Alex Thompson', 'alex.dentist@dentalclinic.com', '$2a$12$amvPWsaWtB9EmYlIKNUWKeL1u6j0v5Hvg4FLiY7sV0BIlVJd/Ti2e', 'dentist', 1, '555-4001', '501 Dental Professional Dr, Main Branch', '1983-06-20', 'male', 'active', TRUE),
('Dr. Maria Rodriguez', 'maria.dentist@dentalclinic.com', '$2a$12$amvPWsaWtB9EmYlIKNUWKeL1u6j0v5Hvg4FLiY7sV0BIlVJd/Ti2e', 'dentist', 2, '555-4002', '502 Northside Dental Plaza', '1986-12-15', 'female', 'active', TRUE);

-- Insert Patient Users
INSERT INTO users (name, email, password, role, branch_id, phone, address, date_of_birth, gender, status, email_verified) VALUES
('Alice Johnson', 'alice.patient@gmail.com', '$2a$12$amvPWsaWtB9EmYlIKNUWKeL1u6j0v5Hvg4FLiY7sV0BIlVJd/Ti2e', 'patient', 1, '555-3001', '401 Patient St, Residential Area', '1990-06-15', 'female', 'active', TRUE),
('Bob Smith', 'bob.patient@gmail.com', '$2a$12$amvPWsaWtB9EmYlIKNUWKeL1u6j0v5Hvg4FLiY7sV0BIlVJd/Ti2e', 'patient', 1, '555-3002', '402 Family Ave, Suburban Zone', '1985-12-22', 'male', 'active', TRUE),
('Carol Davis', 'carol.patient@gmail.com', '$2a$12$amvPWsaWtB9EmYlIKNUWKeL1u6j0v5Hvg4FLiY7sV0BIlVJd/Ti2e', 'patient', 1, '555-3003', '403 Community Rd, Family District', '1995-03-08', 'female', 'active', TRUE),
('David Wilson', 'david.patient@gmail.com', '$2a$12$amvPWsaWtB9EmYlIKNUWKeL1u6j0v5Hvg4FLiY7sV0BIlVJd/Ti2e', 'patient', 2, '555-3004', '404 North Residential St', '1988-09-18', 'male', 'active', TRUE),
('Emma Brown', 'emma.patient@gmail.com', '$2a$12$amvPWsaWtB9EmYlIKNUWKeL1u6j0v5Hvg4FLiY7sV0BIlVJd/Ti2e', 'patient', 2, '555-3005', '405 Northside Family Lane', '1992-07-25', 'female', 'active', TRUE),
('Frank Miller', 'frank.patient@gmail.com', '$2a$12$amvPWsaWtB9EmYlIKNUWKeL1u6j0v5Hvg4FLiY7sV0BIlVJd/Ti2e', 'patient', 2, '555-3006', '406 North Community Ave', '1987-11-12', 'male', 'active', TRUE),
('Grace Taylor', 'grace.patient@gmail.com', '$2a$12$amvPWsaWtB9EmYlIKNUWKeL1u6j0v5Hvg4FLiY7sV0BIlVJd/Ti2e', 'patient', 3, '555-3007', '407 South Family Dr', '1993-04-05', 'female', 'active', TRUE),
('Henry Anderson', 'henry.patient@gmail.com', '$2a$12$amvPWsaWtB9EmYlIKNUWKeL1u6j0v5Hvg4FLiY7sV0BIlVJd/Ti2e', 'patient', 3, '555-3008', '408 Southside Residential Blvd', '1991-08-17', 'male', 'active', TRUE),
('Isabella Garcia', 'isabella.patient@gmail.com', '$2a$12$amvPWsaWtB9EmYlIKNUWKeL1u6j0v5Hvg4FLiY7sV0BIlVJd/Ti2e', 'patient', 3, '555-3009', '409 South Community Circle', '1996-02-28', 'female', 'active', TRUE),
('Jack Robinson', 'jack.patient@gmail.com', '$2a$12$amvPWsaWtB9EmYlIKNUWKeL1u6j0v5Hvg4FLiY7sV0BIlVJd/Ti2e', 'patient', 1, '555-3010', '410 Main District Ave', '1989-10-14', 'male', 'active', TRUE);

-- ===============================================
-- LOGIN CREDENTIALS FOR TESTING
-- ===============================================

/*
All passwords are: "password123"

ADMIN ACCOUNTS:
- admin@dentalclinic.com (Main Branch)
- sarah.admin@dentalclinic.com (Main Branch)

STAFF ACCOUNTS:
- michael.staff@dentalclinic.com (Main Branch)
- emily.staff@dentalclinic.com (Main Branch)  
- robert.staff@dentalclinic.com (Northside Branch)
- lisa.staff@dentalclinic.com (Northside Branch)
- david.staff@dentalclinic.com (Southside Branch)
- jennifer.staff@dentalclinic.com (Southside Branch)

DENTIST ACCOUNTS:
- alex.dentist@dentalclinic.com (Main Branch)
- maria.dentist@dentalclinic.com (Northside Branch)

PATIENT ACCOUNTS:
- alice.patient@gmail.com (Main Branch)
- bob.patient@gmail.com (Main Branch)
- carol.patient@gmail.com (Main Branch)
- david.patient@gmail.com (Northside Branch)
- emma.patient@gmail.com (Northside Branch)
- frank.patient@gmail.com (Northside Branch)
- grace.patient@gmail.com (Southside Branch)
- henry.patient@gmail.com (Southside Branch)
- isabella.patient@gmail.com (Southside Branch)
- jack.patient@gmail.com (Main Branch)
*/

-- ===============================================
-- TEST DATA COMPLETE
-- ===============================================
COMMIT;