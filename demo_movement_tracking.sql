-- ============================================
-- Demo Data for Movement Tracking Presentation
-- ============================================
-- This SQL file creates sample products to demonstrate:
-- 1. Fast-moving products (Lagundi) - Low stock at ≤50 units
-- 2. Medium-moving products (Solmux) - Low stock at ≤30 units  
-- 3. Slow-moving products - Low stock at ≤15 units
-- ============================================

-- First, let's make sure we have a General category (adjust ID if needed)
INSERT INTO categories (id, name) VALUES 
(1, 'General Medicine')
ON DUPLICATE KEY UPDATE name = name;

-- ============================================
-- FAST-MOVING PRODUCTS (Will show in Low Stock when ≤50)
-- ============================================

INSERT INTO products (name, lot_number, category_id, price, cost, stock, expiration_date, supplier) VALUES
('Lagundi 300mg Tablet', 'LAG-2024-001', 1, 85.50, 50.00, 45, '2026-12-31', 'PhilPharma Inc.'),
('Lagundi Syrup 60ml', 'LAG-2024-002', 1, 125.00, 75.00, 35, '2026-11-30', 'PhilPharma Inc.'),
('Lagundi Forte 600mg', 'LAG-2024-003', 1, 150.00, 90.00, 40, '2026-10-31', 'PhilPharma Inc.');

-- ============================================
-- MEDIUM-MOVING PRODUCTS (Will show in Low Stock when ≤30)
-- ============================================

INSERT INTO products (name, lot_number, category_id, price, cost, stock, expiration_date, supplier) VALUES
('Solmux 500mg Capsule', 'SOL-2024-001', 1, 95.75, 60.00, 28, '2026-09-30', 'HealthCare Pharma'),
('Solmux Forte 200mg/5ml Syrup', 'SOL-2024-002', 1, 180.00, 120.00, 22, '2026-08-31', 'HealthCare Pharma'),
('Solmux Advance 600mg', 'SOL-2024-003', 1, 220.00, 150.00, 25, '2026-07-31', 'HealthCare Pharma'),
('Solmux Plus with Vitamin C', 'SOL-2024-004', 1, 135.50, 85.00, 20, '2026-06-30', 'HealthCare Pharma');

-- ============================================
-- SLOW-MOVING PRODUCTS (Will show in Low Stock when ≤15)
-- ============================================

INSERT INTO products (name, lot_number, category_id, price, cost, stock, expiration_date, supplier) VALUES
('Paracetamol 500mg Generic', 'PAR-2024-001', 1, 45.00, 25.00, 12, '2026-05-31', 'Generic Pharma Co.'),
('Ibuprofen 400mg', 'IBU-2024-001', 1, 65.00, 40.00, 10, '2026-04-30', 'Generic Pharma Co.'),
('Cetirizine 10mg', 'CET-2024-001', 1, 55.00, 35.00, 14, '2026-03-31', 'Generic Pharma Co.');

-- ============================================
-- SAMPLE TRANSACTIONS (Purchase History)
-- ============================================
-- These create the sales history needed for movement calculation

-- Fast-moving: Lagundi products (high frequency sales)
INSERT INTO purchase_history (product_name, quantity, total_price, transaction_date) VALUES
('Lagundi 300mg Tablet', 10, 855.00, DATE_SUB(NOW(), INTERVAL 1 DAY)),
('Lagundi 300mg Tablet', 8, 684.00, DATE_SUB(NOW(), INTERVAL 2 DAY)),
('Lagundi 300mg Tablet', 12, 1026.00, DATE_SUB(NOW(), INTERVAL 3 DAY)),
('Lagundi Syrup 60ml', 15, 1875.00, DATE_SUB(NOW(), INTERVAL 1 DAY)),
('Lagundi Syrup 60ml', 10, 1250.00, DATE_SUB(NOW(), INTERVAL 2 DAY)),
('Lagundi Forte 600mg', 7, 1050.00, DATE_SUB(NOW(), INTERVAL 1 DAY)),
('Lagundi Forte 600mg', 9, 1350.00, DATE_SUB(NOW(), INTERVAL 3 DAY));

-- Medium-moving: Solmux products (moderate frequency sales)
INSERT INTO purchase_history (product_name, quantity, total_price, transaction_date) VALUES
('Solmux 500mg Capsule', 5, 478.75, DATE_SUB(NOW(), INTERVAL 2 DAY)),
('Solmux 500mg Capsule', 4, 383.00, DATE_SUB(NOW(), INTERVAL 5 DAY)),
('Solmux Forte 200mg/5ml Syrup', 6, 1080.00, DATE_SUB(NOW(), INTERVAL 3 DAY)),
('Solmux Forte 200mg/5ml Syrup', 3, 540.00, DATE_SUB(NOW(), INTERVAL 6 DAY)),
('Solmux Advance 600mg', 4, 880.00, DATE_SUB(NOW(), INTERVAL 4 DAY)),
('Solmux Plus with Vitamin C', 5, 677.50, DATE_SUB(NOW(), INTERVAL 3 DAY));

-- Slow-moving: Generic products (low frequency sales)
INSERT INTO purchase_history (product_name, quantity, total_price, transaction_date) VALUES
('Paracetamol 500mg Generic', 2, 90.00, DATE_SUB(NOW(), INTERVAL 10 DAY)),
('Ibuprofen 400mg', 1, 65.00, DATE_SUB(NOW(), INTERVAL 12 DAY)),
('Cetirizine 10mg', 3, 165.00, DATE_SUB(NOW(), INTERVAL 8 DAY));

-- ============================================
-- VERIFICATION QUERIES
-- ============================================
-- Run these to verify the data was inserted correctly

-- View all products with their stock levels
-- SELECT name, stock, category_id FROM products WHERE name LIKE '%Lagundi%' OR name LIKE '%Solmux%' OR name LIKE '%Paracetamol%' OR name LIKE '%Ibuprofen%' OR name LIKE '%Cetirizine%';

-- View purchase history
-- SELECT product_name, SUM(quantity) as total_sold, COUNT(*) as transaction_count FROM purchase_history GROUP BY product_name;

-- ============================================
-- EXPECTED RESULTS IN LOW STOCK SECTION:
-- ============================================
-- FAST (RED badge, stock ≤50):
--   ✓ Lagundi 300mg Tablet (45 units)
--   ✓ Lagundi Syrup 60ml (35 units)
--   ✓ Lagundi Forte 600mg (40 units)
--
-- MEDIUM (YELLOW badge, stock ≤30):
--   ✓ Solmux 500mg Capsule (28 units)
--   ✓ Solmux Forte Syrup (22 units)
--   ✓ Solmux Advance 600mg (25 units)
--   ✓ Solmux Plus with Vitamin C (20 units)
--
-- SLOW (BLUE badge, stock ≤15):
--   ✓ Paracetamol 500mg Generic (12 units)
--   ✓ Ibuprofen 400mg (10 units)
--   ✓ Cetirizine 10mg (14 units)
-- ============================================
