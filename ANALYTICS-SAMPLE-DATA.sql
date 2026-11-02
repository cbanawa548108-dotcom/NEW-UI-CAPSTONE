-- ============================================================
-- FreshTrack Analytics Sample Data Generator
-- Creates 6 months of realistic sales data for analytics
-- ============================================================

-- First, let's make sure we have inventory batches with proper cost data
-- Update existing batches to have realistic prices
UPDATE inventory_batches 
SET price_per_unit = CASE 
    WHEN batch_code LIKE '%MANGO%' THEN 28.50
    WHEN batch_code LIKE '%DURIAN%' THEN 65.00
    WHEN batch_code LIKE '%PINEAPPLE%' THEN 18.00
    WHEN batch_code LIKE '%BANANA%' THEN 8.50
    WHEN batch_code LIKE '%MANGOSTEEN%' THEN 35.00
    WHEN batch_code LIKE '%LANZONES%' THEN 22.00
    WHEN batch_code LIKE '%POMELO%' THEN 28.00
    ELSE 20.00
END
WHERE price_per_unit IS NULL OR price_per_unit = 0;

-- Insert more inventory batches for historical sales tracking
INSERT INTO inventory_batches (inventory_item_id, batch_code, quantity, price_per_unit, received_date, expiry_date, supplier, status, created_at, updated_at) 
SELECT 
    id,
    CONCAT('HIST-', name, '-', DATE_FORMAT(DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 180) DAY), '%Y%m%d')),
    FLOOR(50 + RAND() * 200),
    CASE 
        WHEN name = 'Mango' THEN 28.50
        WHEN name = 'Durian' THEN 65.00
        WHEN name = 'Pineapple' THEN 18.00
        WHEN name = 'Banana' THEN 8.50
        WHEN name = 'Mangosteen' THEN 35.00
        WHEN name = 'Lanzones' THEN 22.00
        WHEN name = 'Pomelo' THEN 28.00
        WHEN name = 'Dragon Fruit' THEN 45.00
        WHEN name = 'Avocado' THEN 55.00
        WHEN name = 'Papaya' THEN 15.00
        ELSE 20.00
    END as price_per_unit,
    DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 180) DAY),
    DATE_ADD(DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 180) DAY), INTERVAL 30 DAY),
    CASE 
        WHEN RAND() > 0.5 THEN 'Fresh Harvest Co.'
        ELSE 'Prime Fruits Supply'
    END,
    'available',
    DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 180) DAY),
    NOW()
FROM inventory_items
WHERE id IN (SELECT id FROM inventory_items LIMIT 10);

-- Generate 6 months of sales transactions (about 200 transactions)
-- Month 1 (6 months ago) - Lower sales
INSERT INTO sales_transactions (user_id, payment_method, total_amount, amount_paid, change_amount, status, created_at, updated_at)
SELECT 
    1, -- User ID
    CASE FLOOR(RAND() * 3)
        WHEN 0 THEN 'cash'
        WHEN 1 THEN 'gcash'
        ELSE 'card'
    END as payment_method,
    FLOOR(200 + RAND() * 800) as total_amount,
    FLOOR(200 + RAND() * 1000) as amount_paid,
    FLOOR(RAND() * 200) as change_amount,
    'completed',
    DATE_SUB(NOW(), INTERVAL 180 - seq DAY) + INTERVAL FLOOR(RAND() * 24) HOUR,
    NOW()
FROM (
    SELECT 0 as seq UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 
    UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9
    UNION ALL SELECT 10 UNION ALL SELECT 11 UNION ALL SELECT 12 UNION ALL SELECT 13 UNION ALL SELECT 14
    UNION ALL SELECT 15 UNION ALL SELECT 16 UNION ALL SELECT 17 UNION ALL SELECT 18 UNION ALL SELECT 19
    UNION ALL SELECT 20 UNION ALL SELECT 21 UNION ALL SELECT 22 UNION ALL SELECT 23 UNION ALL SELECT 24
    UNION ALL SELECT 25 UNION ALL SELECT 26 UNION ALL SELECT 27 UNION ALL SELECT 28 UNION ALL SELECT 29
) as nums;

-- Month 2 (5 months ago) - Growing sales
INSERT INTO sales_transactions (user_id, payment_method, total_amount, amount_paid, change_amount, status, created_at, updated_at)
SELECT 
    1,
    CASE FLOOR(RAND() * 3)
        WHEN 0 THEN 'cash'
        WHEN 1 THEN 'gcash'
        ELSE 'card'
    END,
    FLOOR(250 + RAND() * 900),
    FLOOR(250 + RAND() * 1100),
    FLOOR(RAND() * 250),
    'completed',
    DATE_SUB(NOW(), INTERVAL 150 - seq DAY) + INTERVAL FLOOR(RAND() * 24) HOUR,
    NOW()
FROM (
    SELECT 0 as seq UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 
    UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9
    UNION ALL SELECT 10 UNION ALL SELECT 11 UNION ALL SELECT 12 UNION ALL SELECT 13 UNION ALL SELECT 14
    UNION ALL SELECT 15 UNION ALL SELECT 16 UNION ALL SELECT 17 UNION ALL SELECT 18 UNION ALL SELECT 19
    UNION ALL SELECT 20 UNION ALL SELECT 21 UNION ALL SELECT 22 UNION ALL SELECT 23 UNION ALL SELECT 24
    UNION ALL SELECT 25 UNION ALL SELECT 26 UNION ALL SELECT 27 UNION ALL SELECT 28 UNION ALL SELECT 29
    UNION ALL SELECT 30 UNION ALL SELECT 31 UNION ALL SELECT 32 UNION ALL SELECT 33 UNION ALL SELECT 34
) as nums;

-- Month 3 (4 months ago) - More growth
INSERT INTO sales_transactions (user_id, payment_method, total_amount, amount_paid, change_amount, status, created_at, updated_at)
SELECT 
    1,
    CASE FLOOR(RAND() * 3)
        WHEN 0 THEN 'cash'
        WHEN 1 THEN 'gcash'
        ELSE 'card'
    END,
    FLOOR(300 + RAND() * 1000),
    FLOOR(300 + RAND() * 1200),
    FLOOR(RAND() * 300),
    'completed',
    DATE_SUB(NOW(), INTERVAL 120 - seq DAY) + INTERVAL FLOOR(RAND() * 24) HOUR,
    NOW()
FROM (
    SELECT 0 as seq UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 
    UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9
    UNION ALL SELECT 10 UNION ALL SELECT 11 UNION ALL SELECT 12 UNION ALL SELECT 13 UNION ALL SELECT 14
    UNION ALL SELECT 15 UNION ALL SELECT 16 UNION ALL SELECT 17 UNION ALL SELECT 18 UNION ALL SELECT 19
    UNION ALL SELECT 20 UNION ALL SELECT 21 UNION ALL SELECT 22 UNION ALL SELECT 23 UNION ALL SELECT 24
    UNION ALL SELECT 25 UNION ALL SELECT 26 UNION ALL SELECT 27 UNION ALL SELECT 28 UNION ALL SELECT 29
    UNION ALL SELECT 30 UNION ALL SELECT 31 UNION ALL SELECT 32 UNION ALL SELECT 33 UNION ALL SELECT 34
    UNION ALL SELECT 35 UNION ALL SELECT 36 UNION ALL SELECT 37 UNION ALL SELECT 38 UNION ALL SELECT 39
) as nums;

-- Month 4 (3 months ago)
INSERT INTO sales_transactions (user_id, payment_method, total_amount, amount_paid, change_amount, status, created_at, updated_at)
SELECT 
    1,
    CASE FLOOR(RAND() * 3)
        WHEN 0 THEN 'cash'
        WHEN 1 THEN 'gcash'
        ELSE 'card'
    END,
    FLOOR(280 + RAND() * 950),
    FLOOR(280 + RAND() * 1150),
    FLOOR(RAND() * 280),
    'completed',
    DATE_SUB(NOW(), INTERVAL 90 - seq DAY) + INTERVAL FLOOR(RAND() * 24) HOUR,
    NOW()
FROM (
    SELECT 0 as seq UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 
    UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9
    UNION ALL SELECT 10 UNION ALL SELECT 11 UNION ALL SELECT 12 UNION ALL SELECT 13 UNION ALL SELECT 14
    UNION ALL SELECT 15 UNION ALL SELECT 16 UNION ALL SELECT 17 UNION ALL SELECT 18 UNION ALL SELECT 19
    UNION ALL SELECT 20 UNION ALL SELECT 21 UNION ALL SELECT 22 UNION ALL SELECT 23 UNION ALL SELECT 24
    UNION ALL SELECT 25 UNION ALL SELECT 26 UNION ALL SELECT 27 UNION ALL SELECT 28 UNION ALL SELECT 29
    UNION ALL SELECT 30 UNION ALL SELECT 31 UNION ALL SELECT 32 UNION ALL SELECT 33 UNION ALL SELECT 34
) as nums;

-- Month 5 (2 months ago) - Peak growth
INSERT INTO sales_transactions (user_id, payment_method, total_amount, amount_paid, change_amount, status, created_at, updated_at)
SELECT 
    1,
    CASE FLOOR(RAND() * 3)
        WHEN 0 THEN 'cash'
        WHEN 1 THEN 'gcash'
        ELSE 'card'
    END,
    FLOOR(350 + RAND() * 1100),
    FLOOR(350 + RAND() * 1300),
    FLOOR(RAND() * 350),
    'completed',
    DATE_SUB(NOW(), INTERVAL 60 - seq DAY) + INTERVAL FLOOR(RAND() * 24) HOUR,
    NOW()
FROM (
    SELECT 0 as seq UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 
    UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9
    UNION ALL SELECT 10 UNION ALL SELECT 11 UNION ALL SELECT 12 UNION ALL SELECT 13 UNION ALL SELECT 14
    UNION ALL SELECT 15 UNION ALL SELECT 16 UNION ALL SELECT 17 UNION ALL SELECT 18 UNION ALL SELECT 19
    UNION ALL SELECT 20 UNION ALL SELECT 21 UNION ALL SELECT 22 UNION ALL SELECT 23 UNION ALL SELECT 24
    UNION ALL SELECT 25 UNION ALL SELECT 26 UNION ALL SELECT 27 UNION ALL SELECT 28 UNION ALL SELECT 29
    UNION ALL SELECT 30 UNION ALL SELECT 31 UNION ALL SELECT 32 UNION ALL SELECT 33 UNION ALL SELECT 34
    UNION ALL SELECT 35 UNION ALL SELECT 36 UNION ALL SELECT 37 UNION ALL SELECT 38 UNION ALL SELECT 39
    UNION ALL SELECT 40 UNION ALL SELECT 41 UNION ALL SELECT 42 UNION ALL SELECT 43 UNION ALL SELECT 44
) as nums;

-- Month 6 (Last month/current) - Highest sales
INSERT INTO sales_transactions (user_id, payment_method, total_amount, amount_paid, change_amount, status, created_at, updated_at)
SELECT 
    1,
    CASE FLOOR(RAND() * 3)
        WHEN 0 THEN 'cash'
        WHEN 1 THEN 'gcash'
        ELSE 'card'
    END,
    FLOOR(400 + RAND() * 1200),
    FLOOR(400 + RAND() * 1400),
    FLOOR(RAND() * 400),
    'completed',
    DATE_SUB(NOW(), INTERVAL 30 - seq DAY) + INTERVAL FLOOR(RAND() * 24) HOUR,
    NOW()
FROM (
    SELECT 0 as seq UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 
    UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9
    UNION ALL SELECT 10 UNION ALL SELECT 11 UNION ALL SELECT 12 UNION ALL SELECT 13 UNION ALL SELECT 14
    UNION ALL SELECT 15 UNION ALL SELECT 16 UNION ALL SELECT 17 UNION ALL SELECT 18 UNION ALL SELECT 19
    UNION ALL SELECT 20 UNION ALL SELECT 21 UNION ALL SELECT 22 UNION ALL SELECT 23 UNION ALL SELECT 24
    UNION ALL SELECT 25 UNION ALL SELECT 26 UNION ALL SELECT 27 UNION ALL SELECT 28 UNION ALL SELECT 29
    UNION ALL SELECT 30 UNION ALL SELECT 31 UNION ALL SELECT 32 UNION ALL SELECT 33 UNION ALL SELECT 34
    UNION ALL SELECT 35 UNION ALL SELECT 36 UNION ALL SELECT 37 UNION ALL SELECT 38 UNION ALL SELECT 39
    UNION ALL SELECT 40 UNION ALL SELECT 41 UNION ALL SELECT 42 UNION ALL SELECT 43 UNION ALL SELECT 44
    UNION ALL SELECT 45 UNION ALL SELECT 46 UNION ALL SELECT 47 UNION ALL SELECT 48 UNION ALL SELECT 49
) as nums;

-- Now create sales items for each transaction (2-4 items per transaction)
-- This is complex, so we'll create a stored procedure approach

-- For each recent transaction, add sale items
INSERT INTO sales_items (sale_transaction_id, inventory_item_id, inventory_batch_id, quantity, price_per_unit, total_amount, created_at, updated_at)
SELECT 
    st.id as sale_transaction_id,
    ii.id as inventory_item_id,
    (SELECT id FROM inventory_batches WHERE inventory_item_id = ii.id AND status = 'available' ORDER BY RAND() LIMIT 1) as inventory_batch_id,
    FLOOR(1 + RAND() * 15) as quantity,
    CASE ii.name
        WHEN 'Mango' THEN 35.00
        WHEN 'Durian' THEN 70.00
        WHEN 'Pineapple' THEN 20.00
        WHEN 'Banana' THEN 9.00
        WHEN 'Mangosteen' THEN 38.00
        WHEN 'Lanzones' THEN 25.00
        WHEN 'Pomelo' THEN 30.00
        WHEN 'Dragon Fruit' THEN 50.00
        WHEN 'Avocado' THEN 60.00
        WHEN 'Papaya' THEN 18.00
        ELSE 22.00
    END as price_per_unit,
    FLOOR(1 + RAND() * 15) * CASE ii.name
        WHEN 'Mango' THEN 35.00
        WHEN 'Durian' THEN 70.00
        WHEN 'Pineapple' THEN 20.00
        WHEN 'Banana' THEN 9.00
        WHEN 'Mangosteen' THEN 38.00
        WHEN 'Lanzones' THEN 25.00
        WHEN 'Pomelo' THEN 30.00
        WHEN 'Dragon Fruit' THEN 50.00
        WHEN 'Avocado' THEN 60.00
        WHEN 'Papaya' THEN 18.00
        ELSE 22.00
    END as total_amount,
    st.created_at,
    NOW()
FROM 
    sales_transactions st
CROSS JOIN 
    inventory_items ii
WHERE 
    st.id > 10 
    AND st.status = 'completed'
    AND RAND() > 0.6  -- Random selection so not all items in every transaction
LIMIT 600;

-- Update sales transactions total_amount to match items
UPDATE sales_transactions st
SET total_amount = (
    SELECT COALESCE(SUM(total_amount), 0)
    FROM sales_items si
    WHERE si.sale_transaction_id = st.id
)
WHERE st.id > 10;

-- Add some spoiled/expired batches for waste analytics
UPDATE inventory_batches
SET status = 'expired',
    expiry_date = DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 60) DAY)
WHERE RAND() > 0.85
LIMIT 15;

SELECT 'Analytics sample data created successfully!' as message;
SELECT COUNT(*) as total_transactions FROM sales_transactions;
SELECT COUNT(*) as total_sale_items FROM sales_items;
SELECT COUNT(*) as total_batches FROM inventory_batches;
SELECT SUM(total_amount) as total_revenue FROM sales_transactions WHERE status = 'completed';
