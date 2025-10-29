-- Add missing columns to transactions table for payment processing
-- Run this SQL script to fix the "Unknown column 'payment_method' in 'field list'" error

ALTER TABLE `transactions` 
ADD COLUMN `payment_method` VARCHAR(20) DEFAULT 'cash' AFTER `total_amount`,
ADD COLUMN `cash_amount` DECIMAL(10,2) NULL AFTER `payment_method`,
ADD COLUMN `change_amount` DECIMAL(10,2) NULL AFTER `cash_amount`,
ADD COLUMN `subtotal` DECIMAL(10,2) NULL AFTER `change_amount`,
ADD COLUMN `discount_amount` DECIMAL(10,2) DEFAULT 0.00 AFTER `subtotal`;

-- Update existing records to have default values
UPDATE `transactions` 
SET 
    `payment_method` = 'cash',
    `cash_amount` = `total_amount`,
    `change_amount` = 0.00,
    `subtotal` = `total_amount`,
    `discount_amount` = 0.00
WHERE `payment_method` IS NULL;
