-- ================================================================
--  FreshTrack - Sample Data for Backend Testing
--  Run this AFTER COMPLETE-SETUP.sql
--  Copy and paste into phpMyAdmin SQL tab
-- ================================================================

USE capstone_db;

-- ================================================================
-- PART 1: Insert Inventory Items (Products)
-- ================================================================

INSERT INTO `inventory_items` 
(`name`, `category`, `unit`, `price_per_unit`, `stock_quantity`, `reorder_level`, `freshness_score`, `status`, `created_at`, `updated_at`) 
VALUES
-- Tropical Fruits
('Mango', 'Tropical Fruit', 'kg', 120.00, 640.00, 100.00, 92, 'active', NOW(), NOW()),
('Durian', 'Tropical Fruit', 'kg', 320.00, 145.00, 50.00, 78, 'active', NOW(), NOW()),
('Pomelo', 'Citrus Fruit', 'kg', 70.00, 8.00, 50.00, 16, 'active', NOW(), NOW()),
('Mangosteen', 'Tropical Fruit', 'kg', 170.00, 92.00, 30.00, 85, 'active', NOW(), NOW()),
('Lanzones', 'Tropical Fruit', 'kg', 85.00, 22.00, 40.00, 44, 'active', NOW(), NOW()),
('Pineapple', 'Tropical Fruit', 'kg', 75.00, 118.00, 60.00, 88, 'active', NOW(), NOW()),
('Banana', 'Tropical Fruit', 'kg', 42.00, 210.00, 80.00, 95, 'active', NOW(), NOW()),
('Papaya', 'Tropical Fruit', 'kg', 65.00, 0.00, 50.00, 0, 'active', NOW(), NOW()),
('Avocado', 'Tropical Fruit', 'kg', 180.00, 35.00, 20.00, 72, 'active', NOW(), NOW()),
('Coconut', 'Tropical Fruit', 'pc', 45.00, 150.00, 100.00, 90, 'active', NOW(), NOW());

-- ================================================================
-- PART 2: Insert Inventory Batches (with realistic expiry dates)
-- ================================================================

-- Get inventory item IDs (assuming they start from 1)
SET @mango_id = (SELECT id FROM inventory_items WHERE name = 'Mango' LIMIT 1);
SET @durian_id = (SELECT id FROM inventory_items WHERE name = 'Durian' LIMIT 1);
SET @pomelo_id = (SELECT id FROM inventory_items WHERE name = 'Pomelo' LIMIT 1);
SET @mangosteen_id = (SELECT id FROM inventory_items WHERE name = 'Mangosteen' LIMIT 1);
SET @lanzones_id = (SELECT id FROM inventory_items WHERE name = 'Lanzones' LIMIT 1);
SET @pineapple_id = (SELECT id FROM inventory_items WHERE name = 'Pineapple' LIMIT 1);
SET @banana_id = (SELECT id FROM inventory_items WHERE name = 'Banana' LIMIT 1);
SET @papaya_id = (SELECT id FROM inventory_items WHERE name = 'Papaya' LIMIT 1);
SET @avocado_id = (SELECT id FROM inventory_items WHERE name = 'Avocado' LIMIT 1);
SET @coconut_id = (SELECT id FROM inventory_items WHERE name = 'Coconut' LIMIT 1);

INSERT INTO `inventory_batches` 
(`inventory_item_id`, `batch_code`, `quantity`, `price_per_unit`, `received_date`, `expiry_date`, `supplier`, `status`, `created_at`, `updated_at`) 
VALUES
-- Mango batches (3 batches) - Shelf life: 14 days
(@mango_id, 'MNG-001', 285.00, 120.00, DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_ADD(NOW(), INTERVAL 12 DAY), 'Davao Fresh Farms', 'available', NOW(), NOW()),
(@mango_id, 'MNG-002', 155.00, 118.00, DATE_SUB(NOW(), INTERVAL 4 DAY), DATE_ADD(NOW(), INTERVAL 10 DAY), 'Mt. Apo Growers', 'available', NOW(), NOW()),
(@mango_id, 'MNG-003', 200.00, 120.00, NOW(), DATE_ADD(NOW(), INTERVAL 14 DAY), 'Davao Fresh Farms', 'available', NOW(), NOW()),

-- Durian batches (2 batches) - Shelf life: 7 days
(@durian_id, 'DUR-112', 145.00, 320.00, DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_ADD(NOW(), INTERVAL 6 DAY), 'Mt. Apo Growers', 'available', NOW(), NOW()),
(@durian_id, 'DUR-113', 0.00, 320.00, DATE_SUB(NOW(), INTERVAL 8 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY), 'Mt. Apo Growers', 'depleted', NOW(), NOW()),

-- Pomelo batches (1 batch - CRITICAL LOW STOCK, EXPIRING SOON) - Shelf life: 21 days
(@pomelo_id, 'POM-034', 8.00, 70.00, DATE_SUB(NOW(), INTERVAL 19 DAY), DATE_ADD(NOW(), INTERVAL 2 DAY), 'Sta. Cruz Orchards', 'available', NOW(), NOW()),

-- Mangosteen batches (1 batch) - Shelf life: 14 days
(@mangosteen_id, 'MGS-078', 92.00, 170.00, DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_ADD(NOW(), INTERVAL 12 DAY), 'Davao Fresh Farms', 'available', NOW(), NOW()),

-- Lanzones batches (1 batch - LOW STOCK) - Shelf life: 10 days
(@lanzones_id, 'LNZ-055', 22.00, 85.00, DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_ADD(NOW(), INTERVAL 7 DAY), 'Mt. Apo Growers', 'available', NOW(), NOW()),

-- Pineapple batches (1 batch) - Shelf life: 14 days
(@pineapple_id, 'PNA-019', 118.00, 75.00, NOW(), DATE_ADD(NOW(), INTERVAL 14 DAY), 'Sta. Cruz Orchards', 'available', NOW(), NOW()),

-- Banana batches (1 batch) - Shelf life: 7 days
(@banana_id, 'BNA-041', 210.00, 42.00, DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_ADD(NOW(), INTERVAL 6 DAY), 'Davao Fresh Farms', 'available', NOW(), NOW()),

-- Papaya batches (1 batch - OUT OF STOCK) - Shelf life: 7 days
(@papaya_id, 'PAP-022', 0.00, 65.00, DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_ADD(NOW(), INTERVAL 2 DAY), 'Local Farmers', 'depleted', NOW(), NOW()),

-- Avocado batches (1 batch) - Shelf life: 10 days
(@avocado_id, 'AVO-067', 35.00, 180.00, DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_ADD(NOW(), INTERVAL 7 DAY), 'Benguet Highlands', 'available', NOW(), NOW()),

-- Coconut batches (2 batches) - Shelf life: 30 days
(@coconut_id, 'COC-089', 80.00, 45.00, DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_ADD(NOW(), INTERVAL 25 DAY), 'Quezon Coconut Farm', 'available', NOW(), NOW()),
(@coconut_id, 'COC-090', 70.00, 45.00, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), 'Quezon Coconut Farm', 'available', NOW(), NOW());

-- ================================================================
-- PART 3: Insert Sample Sales Transactions
-- ================================================================

-- Get user IDs
SET @owner_id = (SELECT id FROM users WHERE email = 'owner@FreshTrack.ph' LIMIT 1);
SET @manager_id = (SELECT id FROM users WHERE email = 'manager@FreshTrack.ph' LIMIT 1);
SET @cashier_id = (SELECT id FROM users WHERE email = 'cashier@FreshTrack.ph' LIMIT 1);

-- Sales from today
INSERT INTO `sales_transactions` 
(`user_id`, `transaction_code`, `subtotal`, `tax_amount`, `discount_amount`, `total_amount`, `payment_method`, `status`, `notes`, `created_at`, `updated_at`) 
VALUES
-- Today's sales
(@cashier_id, 'TXN-20261002-0001', 600.00, 0.00, 0.00, 600.00, 'cash', 'completed', NULL, NOW(), NOW()),
(@cashier_id, 'TXN-20261002-0002', 1960.00, 0.00, 100.00, 1860.00, 'gcash', 'completed', 'Regular customer discount', DATE_SUB(NOW(), INTERVAL 2 HOUR), DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(@owner_id, 'TXN-20261002-0003', 3276.00, 0.00, 0.00, 3276.00, 'cash', 'completed', NULL, DATE_SUB(NOW(), INTERVAL 4 HOUR), DATE_SUB(NOW(), INTERVAL 4 HOUR)),

-- Yesterday's sales
(@manager_id, 'TXN-20261001-0001', 1350.00, 0.00, 50.00, 1300.00, 'card', 'completed', NULL, DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)),
(@cashier_id, 'TXN-20261001-0002', 850.00, 0.00, 0.00, 850.00, 'cash', 'completed', NULL, DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)),

-- Last week's sales
(@cashier_id, 'TXN-20260926-0001', 2400.00, 0.00, 0.00, 2400.00, 'cash', 'completed', NULL, DATE_SUB(NOW(), INTERVAL 6 DAY), DATE_SUB(NOW(), INTERVAL 6 DAY)),
(@manager_id, 'TXN-20260927-0001', 1840.00, 0.00, 40.00, 1800.00, 'gcash', 'completed', NULL, DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 5 DAY));

-- ================================================================
-- PART 4: Insert Sales Items (Details of each transaction)
-- ================================================================

-- Get batch IDs
SET @mng001_batch = (SELECT id FROM inventory_batches WHERE batch_code = 'MNG-001' LIMIT 1);
SET @dur112_batch = (SELECT id FROM inventory_batches WHERE batch_code = 'DUR-112' LIMIT 1);
SET @bna041_batch = (SELECT id FROM inventory_batches WHERE batch_code = 'BNA-041' LIMIT 1);
SET @pna019_batch = (SELECT id FROM inventory_batches WHERE batch_code = 'PNA-019' LIMIT 1);
SET @lnz055_batch = (SELECT id FROM inventory_batches WHERE batch_code = 'LNZ-055' LIMIT 1);

-- Get transaction IDs
SET @txn1 = (SELECT id FROM sales_transactions WHERE transaction_code = 'TXN-20261002-0001' LIMIT 1);
SET @txn2 = (SELECT id FROM sales_transactions WHERE transaction_code = 'TXN-20261002-0002' LIMIT 1);
SET @txn3 = (SELECT id FROM sales_transactions WHERE transaction_code = 'TXN-20261002-0003' LIMIT 1);
SET @txn4 = (SELECT id FROM sales_transactions WHERE transaction_code = 'TXN-20261001-0001' LIMIT 1);
SET @txn5 = (SELECT id FROM sales_transactions WHERE transaction_code = 'TXN-20261001-0002' LIMIT 1);
SET @txn6 = (SELECT id FROM sales_transactions WHERE transaction_code = 'TXN-20260926-0001' LIMIT 1);
SET @txn7 = (SELECT id FROM sales_transactions WHERE transaction_code = 'TXN-20260927-0001' LIMIT 1);

INSERT INTO `sales_items` 
(`sale_transaction_id`, `inventory_item_id`, `inventory_batch_id`, `quantity`, `unit_price`, `total_amount`, `created_at`, `updated_at`) 
VALUES
-- TXN-20261002-0001: 5kg Mango
(@txn1, @mango_id, @mng001_batch, 5.00, 120.00, 600.00, NOW(), NOW()),

-- TXN-20261002-0002: 6kg Durian + 2kg Mangosteen
(@txn2, @durian_id, @dur112_batch, 6.00, 320.00, 1920.00, DATE_SUB(NOW(), INTERVAL 2 HOUR), DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(@txn2, @mangosteen_id, (SELECT id FROM inventory_batches WHERE batch_code = 'MGS-078'), 2.00, 170.00, 340.00, DATE_SUB(NOW(), INTERVAL 2 HOUR), DATE_SUB(NOW(), INTERVAL 2 HOUR)),

-- TXN-20261002-0003: 78kg Banana
(@txn3, @banana_id, @bna041_batch, 78.00, 42.00, 3276.00, DATE_SUB(NOW(), INTERVAL 4 HOUR), DATE_SUB(NOW(), INTERVAL 4 HOUR)),

-- TXN-20261001-0001: 10kg Mango + 3kg Pineapple
(@txn4, @mango_id, @mng001_batch, 10.00, 120.00, 1200.00, DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)),
(@txn4, @pineapple_id, @pna019_batch, 2.00, 75.00, 150.00, DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)),

-- TXN-20261001-0002: 10kg Lanzones
(@txn5, @lanzones_id, @lnz055_batch, 10.00, 85.00, 850.00, DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)),

-- TXN-20260926-0001: 20kg Mango
(@txn6, @mango_id, @mng001_batch, 20.00, 120.00, 2400.00, DATE_SUB(NOW(), INTERVAL 6 DAY), DATE_SUB(NOW(), INTERVAL 6 DAY)),

-- TXN-20260927-0001: 4kg Durian + 8kg Banana
(@txn7, @durian_id, @dur112_batch, 4.00, 320.00, 1280.00, DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 5 DAY)),
(@txn7, @banana_id, @bna041_batch, 14.00, 42.00, 588.00, DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 5 DAY));

-- ================================================================
-- PART 5: Verify Data Insertion
-- ================================================================

SELECT '=== INVENTORY ITEMS ===' as '';
SELECT 
    id,
    name,
    category,
    CONCAT('₱', FORMAT(price_per_unit, 2), '/', unit) as price,
    CONCAT(stock_quantity, ' ', unit) as stock,
    status
FROM inventory_items
ORDER BY category, name;

SELECT '=== INVENTORY BATCHES ===' as '';
SELECT 
    b.batch_code,
    i.name as product,
    CONCAT(b.quantity, ' ', i.unit) as quantity,
    CONCAT('₱', FORMAT(b.price_per_unit, 2)) as price,
    b.supplier,
    DATE_FORMAT(b.expiry_date, '%b %d, %Y') as expires,
    DATEDIFF(b.expiry_date, NOW()) as days_remaining,
    b.status
FROM inventory_batches b
JOIN inventory_items i ON b.inventory_item_id = i.id
ORDER BY b.expiry_date ASC;

SELECT '=== SALES TRANSACTIONS ===' as '';
SELECT 
    t.transaction_code,
    u.name as cashier,
    CONCAT('₱', FORMAT(t.total_amount, 2)) as total,
    t.payment_method,
    DATE_FORMAT(t.created_at, '%b %d, %Y %h:%i %p') as date_time,
    t.status
FROM sales_transactions t
JOIN users u ON t.user_id = u.id
ORDER BY t.created_at DESC;

SELECT '=== SUMMARY ===' as '';
SELECT 
    (SELECT COUNT(*) FROM inventory_items) as total_products,
    (SELECT COUNT(*) FROM inventory_batches WHERE status = 'available') as active_batches,
    (SELECT CONCAT('₱', FORMAT(SUM(total_amount), 2)) FROM sales_transactions WHERE status = 'completed') as total_sales,
    (SELECT COUNT(*) FROM sales_transactions WHERE status = 'completed') as total_transactions,
    (SELECT COUNT(*) FROM inventory_items WHERE stock_quantity <= reorder_level) as low_stock_items,
    (SELECT COUNT(*) FROM inventory_batches WHERE expiry_date <= DATE_ADD(NOW(), INTERVAL 7 DAY) AND status = 'available') as expiring_items;

-- ================================================================
-- SUCCESS MESSAGE
-- ================================================================
SELECT '✓✓✓ SAMPLE DATA INSERTED SUCCESSFULLY! ✓✓✓' as '';
SELECT 'You can now test the backend:' as '';
SELECT '1. Start server: php artisan serve' as '';
SELECT '2. Login: owner@FreshTrack.ph / password' as '';
SELECT '3. Check Dashboard, Inventory, Sales, POS' as '';

