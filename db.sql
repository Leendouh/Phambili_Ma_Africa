-- =====================================================
-- CUSTOMERS TABLE
-- =====================================================
CREATE TABLE `customers` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `Full_Name` varchar(100) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `Password` varchar(100) NOT NULL,
  `Phone` varchar(20) DEFAULT NULL,
  `Address` text DEFAULT NULL,
  `createdAt` datetime NOT NULL,
  `updatedAt` datetime NOT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Email` (`Email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- NOTIFICATIONS TABLE
-- =====================================================
CREATE TABLE `notifications` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(50) DEFAULT 'general',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`),
  KEY `idx_user` (`user_id`),
  KEY `idx_is_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- ADMINS TABLE
-- =====================================================
CREATE TABLE `admins` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `Full_Name` varchar(100) DEFAULT NULL,
  `Email` varchar(100) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `Phone` varchar(10) DEFAULT NULL,
  `Role` enum('main_admin','sub_admin') NOT NULL DEFAULT 'sub_admin',
  `first_login` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `createdAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Email` (`Email`),
  KEY `created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Seed default main admin (password: Admin@123 - will be hashed)
-- Default admin credentials: admin@phambilima-africa.co.za / 27@86Tt0.Admin
INSERT INTO `admins` (`ID`, `Full_Name`, `Email`, `Password`, `Phone`, `Role`, `first_login`, `created_by`, `createdAt`, `updatedAt`) VALUES
(1, NULL, 'admin@phambilima-africa.co.za', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 'main_admin', 1, NULL, NOW(), NOW());

-- =====================================================
-- SERVICES TABLE
-- =====================================================
CREATE TABLE `services` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `Name` varchar(100) NOT NULL,
  `Description` text DEFAULT NULL,
  `Duration` int(11) NOT NULL COMMENT 'Duration in minutes',
  `Category` varchar(100) DEFAULT NULL,
  `Is_Available` tinyint(1) NOT NULL DEFAULT 1,
  `Image_URL` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`),
  KEY `idx_category` (`Category`),
  KEY `idx_available` (`Is_Available`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- PRODUCTS TABLE
-- =====================================================
CREATE TABLE `products` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `Name` varchar(100) NOT NULL,
  `Description` text DEFAULT NULL,
  `Price` decimal(10,2) NOT NULL,
  `Stock_Quantity` int(11) NOT NULL DEFAULT 0,
  `Category` varchar(100) DEFAULT NULL,
  `Is_Available` tinyint(1) NOT NULL DEFAULT 1,
  `Image_URL` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`),
  KEY `idx_category` (`Category`),
  KEY `idx_available` (`Is_Available`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- BOOKINGS TABLE
-- =====================================================
CREATE TABLE `bookings` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `Customer_ID` int(11) NOT NULL,
  `Service_ID` int(11) NOT NULL,
  `Date` date NOT NULL,
  `Time` time DEFAULT NULL,
  `Status` enum('requested','contacted','in_progress','quoted','confirmed','completed','cancelled','expired') NOT NULL DEFAULT 'requested',
  `Address` text DEFAULT NULL,
  `Special_Instructions` text DEFAULT NULL,
  `Quoted_Amount` decimal(10,2) DEFAULT NULL,
  `Duration` int(11) DEFAULT NULL,
  `Property_Type` varchar(100) DEFAULT NULL,
  `Property_Size` varchar(100) DEFAULT NULL,
  `Cleaning_Frequency` varchar(50) DEFAULT NULL,
  `contact_date` datetime DEFAULT NULL,
  `consultation_date` datetime DEFAULT NULL,
  `consultation_type` varchar(50) DEFAULT NULL,
  `consultation_notes` text DEFAULT NULL,
  `quote_breakdown` text DEFAULT NULL,
  `quote_validity_days` int(11) DEFAULT 7,
  `quote_notes` text DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `call_notes` text DEFAULT NULL,
  `next_steps` text DEFAULT NULL,
  `completed_date` datetime DEFAULT NULL,
  `cancelled_date` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`),
  KEY `idx_customer` (`Customer_ID`),
  KEY `idx_service` (`Service_ID`),
  KEY `idx_status` (`Status`),
  KEY `idx_date` (`Date`),
  CONSTRAINT `fk_booking_customer` FOREIGN KEY (`Customer_ID`) REFERENCES `customers` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `fk_booking_service` FOREIGN KEY (`Service_ID`) REFERENCES `services` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- QUOTE MODIFICATIONS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `quote_modifications` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `Booking_ID` int(11) NOT NULL,
  `Customer_ID` int(11) NOT NULL,
  `Reason` text NOT NULL,
  `Requested_Changes` text DEFAULT NULL,
  `Status` enum('pending','approved','rejected','completed') NOT NULL DEFAULT 'pending',
  `Admin_Response` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`),
  KEY `idx_booking` (`Booking_ID`),
  KEY `idx_customer` (`Customer_ID`),
  KEY `idx_status` (`Status`),
  CONSTRAINT `fk_modification_booking` FOREIGN KEY (`Booking_ID`) REFERENCES `bookings` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `fk_modification_customer` FOREIGN KEY (`Customer_ID`) REFERENCES `customers` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- BOOKING STATUS HISTORY TABLE (for audit trail)
-- =====================================================
CREATE TABLE `booking_status_history` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `Booking_ID` int(11) NOT NULL,
  `Old_Status` varchar(50) DEFAULT NULL,
  `New_Status` varchar(50) NOT NULL,
  `Changed_By` varchar(100) DEFAULT NULL,
  `Notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`),
  KEY `idx_booking` (`Booking_ID`),
  CONSTRAINT `fk_history_booking` FOREIGN KEY (`Booking_ID`) REFERENCES `bookings` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- REMEMBER ME TOKENS TABLE (for secure "remember me" functionality)
-- =====================================================
CREATE TABLE IF NOT EXISTS remember_me_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    user_type ENUM('customer', 'admin') NOT NULL,
    selector VARCHAR(64) NOT NULL UNIQUE,
    validator VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_used_at TIMESTAMP NULL,
    INDEX idx_user (user_id, user_type),
    INDEX idx_selector (selector),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Clear old OTP rows so fresh ones use the corrected timezone
DELETE FROM otps;

-- =====================================================
-- OTP TABLE (for secure one-time password functionality)
-- =====================================================
-- If the otps table doesn't exist yet, create it without touching other tables:
CREATE TABLE IF NOT EXISTS otps (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    otp_code VARCHAR(6) NOT NULL,
    otp_type ENUM('email_verification', 'password_reset', 'contact_verification') NOT NULL,
    user_id INT NULL,
    expires_at DATETIME NOT NULL,
    is_used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_otp_code (otp_code),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- RATE LIMITS TABLE (for secure "rate limiting" functionality)
-- =====================================================
-- Rate Limits Table for authentication endpoints
CREATE TABLE IF NOT EXISTS `rate_limits` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `identifier` varchar(255) NOT NULL,
  `action` varchar(50) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`),
  KEY `idx_identifier_action` (`identifier`, `action`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Database Migration Script
-- Run this to update your database without losing existing data
-- Execute this in your MySQL database

-- =====================================================
-- Update bookings table to add 'expired' status
-- =====================================================
-- Note: MySQL doesn't allow direct ALTER of ENUM values
-- We need to modify the column definition
ALTER TABLE `bookings` 
MODIFY COLUMN `Status` enum('requested','contacted','in_progress','quoted','confirmed','completed','cancelled','expired') NOT NULL DEFAULT 'requested';

-- =====================================================
-- Create quote_modifications table (if it doesn't exist)
-- =====================================================
CREATE TABLE IF NOT EXISTS `quote_modifications` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `Booking_ID` int(11) NOT NULL,
  `Customer_ID` int(11) NOT NULL,
  `Reason` text NOT NULL,
  `Requested_Changes` text DEFAULT NULL,
  `Status` enum('pending','approved','rejected','completed') NOT NULL DEFAULT 'pending',
  `Admin_Response` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`),
  KEY `idx_booking` (`Booking_ID`),
  KEY `idx_customer` (`Customer_ID`),
  KEY `idx_status` (`Status`),
  CONSTRAINT `fk_modification_booking` FOREIGN KEY (`Booking_ID`) REFERENCES `bookings` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `fk_modification_customer` FOREIGN KEY (`Customer_ID`) REFERENCES `customers` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- Verify the changes
-- =====================================================
-- Check bookings table structure
DESCRIBE bookings;

-- Check quote_modifications table exists
SHOW TABLES LIKE 'quote_modifications';

-- Check if expired status is available
SELECT DISTINCT Status FROM bookings;
