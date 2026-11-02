-- ============================================================
-- ANALYTICS DATA - CORRECTED FOR ACTUAL TABLE STRUCTURE
-- Database: capstone_db
-- ============================================================

USE capstone_db;

-- Update batch prices first
UPDATE inventory_batches SET price_per_unit = 28.50 WHERE id = 1;
UPDATE inventory_batches SET price_per_unit = 65.00 WHERE id = 2;
UPDATE inventory_batches SET price_per_unit = 18.00 WHERE id = 3;

-- OCTOBER 2026 SALES (Current Month)
INSERT INTO sales_transactions (user_id, transaction_code, subtotal, tax_amount, discount_amount, total_amount, payment_method, status, created_at, updated_at) VALUES
(1, 'TXN-2026100109150001', 428.57, 21.43, 0, 450, 'cash', 'completed', '2026-10-01 09:15:00', NOW()),
(1, 'TXN-2026100210300001', 647.62, 32.38, 0, 680, 'gcash', 'completed', '2026-10-02 10:30:00', NOW()),
(1, 'TXN-2026100314200001', 495.24, 24.76, 0, 520, 'card', 'completed', '2026-10-03 14:20:00', NOW()),
(1, 'TXN-2026100411450001', 714.29, 35.71, 0, 750, 'cash', 'completed', '2026-10-04 11:45:00', NOW()),
(1, 'TXN-2026100513100001', 561.90, 28.10, 0, 590, 'gcash', 'completed', '2026-10-05 13:10:00', NOW()),
(1, 'TXN-2026100615300001', 780.95, 39.05, 0, 820, 'cash', 'completed', '2026-10-06 15:30:00', NOW()),
(1, 'TXN-2026100708450001', 695.24, 34.76, 0, 730, 'card', 'completed', '2026-10-07 08:45:00', NOW());

-- SEPTEMBER 2026
INSERT INTO sales_transactions (user_id, transaction_code, subtotal, tax_amount, discount_amount, total_amount, payment_method, status, created_at, updated_at) VALUES
(1, 'TXN-2026090110000001', 400.00, 20.00, 0, 420, 'cash', 'completed', '2026-09-01 10:00:00', NOW()),
(1, 'TXN-2026090511300001', 619.05, 30.95, 0, 650, 'gcash', 'completed', '2026-09-05 11:30:00', NOW()),
(1, 'TXN-2026091014000001', 457.14, 22.86, 0, 480, 'card', 'completed', '2026-09-10 14:00:00', NOW()),
(1, 'TXN-2026091512200001', 685.71, 34.29, 0, 720, 'cash', 'completed', '2026-09-15 12:20:00', NOW()),
(1, 'TXN-2026092016400001', 533.33, 26.67, 0, 560, 'gcash', 'completed', '2026-09-20 16:40:00', NOW()),
(1, 'TXN-2026092513150001', 657.14, 32.86, 0, 690, 'cash', 'completed', '2026-09-25 13:15:00', NOW());

-- AUGUST 2026
INSERT INTO sales_transactions (user_id, transaction_code, subtotal, tax_amount, discount_amount, total_amount, payment_method, status, created_at, updated_at) VALUES
(1, 'TXN-2026080109000001', 371.43, 18.57, 0, 390, 'cash', 'completed', '2026-08-01 09:00:00', NOW()),
(1, 'TXN-2026080710300001', 590.48, 29.52, 0, 620, 'gcash', 'completed', '2026-08-07 10:30:00', NOW()),
(1, 'TXN-2026081413000001', 428.57, 21.43, 0, 450, 'card', 'completed', '2026-08-14 13:00:00', NOW()),
(1, 'TXN-2026082115300001', 647.62, 32.38, 0, 680, 'cash', 'completed', '2026-08-21 15:30:00', NOW()),
(1, 'TXN-2026082817000001', 504.76, 25.24, 0, 530, 'gcash', 'completed', '2026-08-28 17:00:00', NOW());

-- JULY 2026
INSERT INTO sales_transactions (user_id, transaction_code, subtotal, tax_amount, discount_amount, total_amount, payment_method, status, created_at, updated_at) VALUES
(1, 'TXN-2026070110000001', 342.86, 17.14, 0, 360, 'cash', 'completed', '2026-07-01 10:00:00', NOW()),
(1, 'TXN-2026070812000001', 561.90, 28.10, 0, 590, 'gcash', 'completed', '2026-07-08 12:00:00', NOW()),
(1, 'TXN-2026071514000001', 400.00, 20.00, 0, 420, 'card', 'completed', '2026-07-15 14:00:00', NOW()),
(1, 'TXN-2026072216000001', 619.05, 30.95, 0, 650, 'cash', 'completed', '2026-07-22 16:00:00', NOW());

-- JUNE 2026
INSERT INTO sales_transactions (user_id, transaction_code, subtotal, tax_amount, discount_amount, total_amount, payment_method, status, created_at, updated_at) VALUES
(1, 'TXN-2026060111000001', 323.81, 16.19, 0, 340, 'cash', 'completed', '2026-06-01 11:00:00', NOW()),
(1, 'TXN-2026061013000001', 533.33, 26.67, 0, 560, 'gcash', 'completed', '2026-06-10 13:00:00', NOW()),
(1, 'TXN-2026062015000001', 380.95, 19.05, 0, 400, 'card', 'completed', '2026-06-20 15:00:00', NOW());

-- MAY 2026
INSERT INTO sales_transactions (user_id, transaction_code, subtotal, tax_amount, discount_amount, total_amount, payment_method, status, created_at, updated_at) VALUES
(1, 'TXN-2026050110300001', 304.76, 15.24, 0, 320, 'cash', 'completed', '2026-05-01 10:30:00', NOW()),
(1, 'TXN-2026051512300001', 514.29, 25.71, 0, 540, 'gcash', 'completed', '2026-05-15 12:30:00', NOW()),
(1, 'TXN-2026052914300001', 361.90, 18.10, 0, 380, 'card', 'completed', '2026-05-29 14:30:00', NOW());

-- Get batch and transaction IDs for sales items
SET @batch1 = (SELECT MIN(id) FROM inventory_batches WHERE status = 'available');
SET @trans_start = (SELECT MAX(id) FROM sales_transactions) - 29;

-- Add sales items (Mango - inventory_item_id = 1)
INSERT INTO sales_items (sale_transaction_id, inventory_item_id, inventory_batch_id, quantity, unit_price, total_amount, created_at, updated_at)
SELECT id, 1, @batch1, 5, 35.00, 175.00, created_at, NOW()
FROM sales_transactions 
WHERE id >= @trans_start AND status = 'completed'
LIMIT 15;

-- Add sales items (Durian - inventory_item_id = 2)  
INSERT INTO sales_items (sale_transaction_id, inventory_item_id, inventory_batch_id, quantity, unit_price, total_amount, created_at, updated_at)
SELECT id, 2, @batch1, 3, 70.00, 210.00, created_at, NOW()
FROM sales_transactions 
WHERE id >= @trans_start AND status = 'completed' 
AND id NOT IN (SELECT DISTINCT sale_transaction_id FROM sales_items WHERE inventory_item_id = 1)
LIMIT 10;

-- Add sales items (Pineapple - inventory_item_id = 3)
INSERT INTO sales_items (sale_transaction_id, inventory_item_id, inventory_batch_id, quantity, unit_price, total_amount, created_at, updated_at)
SELECT id, 3, @batch1, 8, 20.00, 160.00, created_at, NOW()
FROM sales_transactions 
WHERE id >= @trans_start AND status = 'completed'
LIMIT 12;

-- Add sales items (Banana - inventory_item_id = 4)
INSERT INTO sales_items (sale_transaction_id, inventory_item_id, inventory_batch_id, quantity, unit_price, total_amount, created_at, updated_at)
SELECT id, 4, @batch1, 10, 9.00, 90.00, created_at, NOW()
FROM sales_transactions 
WHERE id >= @trans_start AND status = 'completed'
LIMIT 18;

-- Verify the data
SELECT 'DATA LOADED SUCCESSFULLY!' as status;
SELECT COUNT(*) as total_transactions FROM sales_transactions;
SELECT COUNT(*) as total_items FROM sales_items;
SELECT SUM(total_amount) as total_revenue FROM sales_transactions WHERE status = 'completed';
SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as transactions, SUM(total_amount) as revenue
FROM sales_transactions 
WHERE status = 'completed' 
GROUP BY DATE_FORMAT(created_at, '%Y-%m')
ORDER BY month DESC
LIMIT 6;
