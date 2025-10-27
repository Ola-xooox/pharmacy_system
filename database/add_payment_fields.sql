-- Add payment fields to transactions table
ALTER TABLE `transactions` 
ADD COLUMN `payment_method` VARCHAR(20) DEFAULT 'cash' AFTER `total_amount`,
ADD COLUMN `cash_amount` DECIMAL(10,2) DEFAULT NULL AFTER `payment_method`,
ADD COLUMN `change_amount` DECIMAL(10,2) DEFAULT NULL AFTER `cash_amount`,
ADD COLUMN `subtotal` DECIMAL(10,2) DEFAULT NULL AFTER `change_amount`,
ADD COLUMN `discount_amount` DECIMAL(10,2) DEFAULT 0.00 AFTER `subtotal`;
