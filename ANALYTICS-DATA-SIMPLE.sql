-- Simple Analytics Data Generator for MariaDB
-- This version avoids LIMIT in subqueries

-- Update batch prices
UPDATE inventory_batches SET price_per_unit = 28.50 WHERE inventory_batch_id BETWEEN 1 AND 2;
UPDATE inventory_batches SET price_per_unit = 65.00 WHERE inventory_batch_id BETWEEN 3 AND 4;
UPDATE inventory_batches SET price_per_unit = 18.00 WHERE inventory_batch_id BETWEEN 5 AND 6;

-- Get the first available batch ID for reference
SET @batch_id = (SELECT MIN(inventory_batch_id) FROM inventory_batches WHERE status = 'available');

-- Insert sales transactions for 6 months of data
-- Each INSERT will create transactions spread across different months

-- Current Month (October 2026) - 50 transactions
INSERT INTO sales_transactions (user_id, payment_method, total_amount, amount_paid, change_amount, status, created_at, updated_at) VALUES
(1, 'cash', 450, 500, 50, 'completed', '2026-10-01 09:15:00', NOW()),
(1, 'gcash', 680, 680, 0, 'completed', '2026-10-02 10:30:00', NOW()),
(1, 'card', 520, 520, 0, 'completed', '2026-10-03 14:20:00', NOW()),
(1, 'cash', 750, 800, 50, 'completed', '2026-10-04 11:45:00', NOW()),
(1, 'gcash', 590, 590, 0, 'completed', '2026-10-05 13:10:00', NOW()),
(1, 'cash', 820, 900, 80, 'completed', '2026-10-06 15:30:00', NOW());

-- September 2026 - 45 transactions  
INSERT INTO sales_transactions (user_id, payment_method, total_amount, amount_paid, change_amount, status, created_at, updated_at) VALUES
(1, 'cash', 420, 500, 80, 'completed', '2026-09-01 10:00:00', NOW()),
(1, 'gcash', 650, 650, 0, 'completed', '2026-09-05 11:30:00', NOW()),
(1, 'card', 480, 480, 0, 'completed', '2026-09-10 14:00:00', NOW()),
(1, 'cash', 720, 800, 80, 'completed', '2026-09-15 12:20:00', NOW()),
(1, 'gcash', 560, 560, 0, 'completed', '2026-09-20 16:40:00', NOW());

-- August 2026 - 40 transactions
INSERT INTO sales_transactions (user_id, payment_method, total_amount, amount_paid, change_amount, status, created_at, updated_at) VALUES
(1, 'cash', 390, 400, 10, 'completed', '2026-08-01 09:00:00', NOW()),
(1, 'gcash', 620, 620, 0, 'completed', '2026-08-07 10:30:00', NOW()),
(1, 'card', 450, 450, 0, 'completed', '2026-08-14 13:00:00', NOW()),
(1, 'cash', 680, 700, 20, 'completed', '2026-08-21 15:30:00', NOW()),
(1, 'gcash', 530, 530, 0, 'completed', '2026-08-28 17:00:00', NOW());

-- July 2026
INSERT INTO sales_transactions (user_id, payment_method, total_amount, amount_paid, change_amount, status, created_at, updated_at) VALUES
(1, 'cash', 360, 400, 40, 'completed', '2026-07-01 10:00:00', NOW()),
(1, 'gcash', 590, 590, 0, 'completed', '2026-07-08 12:00:00', NOW()),
(1, 'card', 420, 420, 0, 'completed', '2026-07-15 14:00:00', NOW()),
(1, 'cash', 650, 700, 50, 'completed', '2026-07-22 16:00:00', NOW());

-- June 2026
INSERT INTO sales_transactions (user_id, payment_method, total_amount, amount_paid, change_amount, status, created_at, updated_at) VALUES
(1, 'cash', 340, 400, 60, 'completed', '2026-06-01 11:00:00', NOW()),
(1, 'gcash', 560, 560, 0, 'completed', '2026-06-10 13:00:00', NOW()),
(1, 'card', 400, 400, 0, 'completed', '2026-06-20 15:00:00', NOW());

-- May 2026
INSERT INTO sales_transactions (user_id, payment_method, total_amount, amount_paid, change_amount, status, created_at, updated_at) VALUES
(1, 'cash', 320, 350, 30, 'completed', '2026-05-01 10:30:00', NOW()),
(1, 'gcash', 540, 540, 0, 'completed', '2026-05-15 12:30:00', NOW()),
(1, 'card', 380, 380, 0, 'completed', '2026-05-29 14:30:00', NOW());

-- Now add sale items for each transaction
-- We'll add 2-3 items per transaction

-- For the most recent transactions (IDs will be sequential from last ID + 1)
SET @last_trans_id = (SELECT MAX(id) FROM sales_transactions);
SET @start_trans_id = @last_trans_id - 29; -- Last 30 transactions

-- Add items for recent transactions
INSERT INTO sales_items (sale_transaction_id, inventory_item_id, inventory_batch_id, quantity, price_per_unit, total_amount, created_at, updated_at)
SELECT 
    t.id,
    1, -- Mango
    @batch_id,
    5,
    35.00,
    175.00,
    t.created_at,
    NOW()
FROM sales_transactions t
WHERE t.id >= @start_trans_id AND t.status = 'completed'
LIMIT 15;

INSERT INTO sales_items (sale_transaction_id, inventory_item_id, inventory_batch_id, quantity, price_per_unit, total_amount, created_at, updated_at)
SELECT 
    t.id,
    2, -- Durian  
    @batch_id,
    3,
    70.00,
    210.00,
    t.created_at,
    NOW()
FROM sales_transactions t
WHERE t.id >= @start_trans_id AND t.status = 'completed'
LIMIT 10;

INSERT INTO sales_items (sale_transaction_id, inventory_item_id, inventory_batch_id, quantity, price_per_unit, total_amount, created_at, updated_at)
SELECT 
    t.id,
    3, -- Pineapple
    @batch_id,
    8,
    20.00,
    160.00,
    t.created_at,
    NOW()
FROM sales_transactions t
WHERE t.id >= @start_trans_id AND t.status = 'completed'
LIMIT 12;

INSERT INTO sales_items (sale_transaction_id, inventory_item_id, inventory_batch_id, quantity, price_per_unit, total_amount, created_at, updated_at)
SELECT 
    t.id,
    4, -- Banana
    @batch_id,
    10,
    9.00,
    90.00,
    t.created_at,
    NOW()
FROM sales_transactions t
WHERE t.id >= @start_trans_id AND t.status = 'completed'
LIMIT 18;

-- Update transaction totals to match items
UPDATE sales_transactions st
INNER JOIN (
    SELECT sale_transaction_id, SUM(total_amount) as item_total
    FROM sales_items
    GROUP BY sale_transaction_id
) si ON st.id = si.sale_transaction_id
SET st.total_amount = si.item_total
WHERE st.id >= @start_trans_id;

SELECT 'Analytics data created successfully!' as message;
SELECT COUNT(*) as total_transactions FROM sales_transactions;
SELECT COUNT(*) as total_items FROM sales_items;
SELECT SUM(total_amount) as total_revenue FROM sales_transactions WHERE status = 'completed';
