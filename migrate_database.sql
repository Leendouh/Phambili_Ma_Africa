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
