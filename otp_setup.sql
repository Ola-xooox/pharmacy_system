-- OTP Authentication Setup for Pharmacy System
-- Run this SQL script to add OTP functionality

-- Add email column to users table (SKIP - column already exists)
-- ALTER TABLE `users` ADD COLUMN `email` VARCHAR(255) DEFAULT NULL AFTER `username`;

-- Create OTP verification table
CREATE TABLE `otp_verification` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `otp_code` varchar(6) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL,
  `is_used` tinyint(1) DEFAULT 0,
  `attempts` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `email` (`email`),
  KEY `otp_code` (`otp_code`),
  KEY `expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Update admin user with new email and password
UPDATE `users` SET 
  `email` = 'lhandelpamisa0@gmail.com',
  `password` = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' -- This is bcrypt hash for 'admin24'
WHERE `username` = 'admin';

-- If admin user doesn't exist, create it
INSERT IGNORE INTO `users` (`last_name`, `first_name`, `middle_name`, `username`, `password`, `email`, `role`, `profile_image`) 
VALUES ('Admin', 'System', NULL, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'lhandelpamisa0@gmail.com', 'admin', NULL);
