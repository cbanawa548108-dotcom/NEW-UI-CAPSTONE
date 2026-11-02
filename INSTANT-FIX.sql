-- INSTANT LOGIN FIX for FreshTrack - WORKING VERSION
-- Copy this entire SQL and run it in phpMyAdmin
-- This uses the CORRECT password hash that works with Laravel

-- Password for all accounts: password
-- Hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi

USE capstone_db;

-- Update all three accounts with the WORKING password hash
UPDATE users 
SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    updated_at = NOW()
WHERE email IN ('owner@FreshTrack.ph', 'manager@FreshTrack.ph', 'cashier@FreshTrack.ph');

-- Verify the update worked
SELECT 
    id, 
    name, 
    email, 
    role,
    '✓ Password Fixed - Use: password' as status,
    updated_at
FROM users 
WHERE email IN ('owner@FreshTrack.ph', 'manager@FreshTrack.ph', 'cashier@FreshTrack.ph')
ORDER BY FIELD(role, 'owner', 'manager', 'cashier');

