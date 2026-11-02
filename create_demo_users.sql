-- FreshTrack Demo Users Setup - WORKING VERSION
-- Run this SQL directly in phpMyAdmin or MySQL client
-- Password for all accounts: password

-- Step 1: Add role column to users table if it doesn't exist
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `role` ENUM('owner', 'manager', 'cashier') NOT NULL DEFAULT 'owner' AFTER `email`;

-- Step 2: Delete existing demo accounts if they exist
DELETE FROM `users` WHERE `email` IN (
    'owner@FreshTrack.ph',
    'manager@FreshTrack.ph',
    'cashier@FreshTrack.ph'
);

-- Step 3: Insert demo accounts with WORKING password hash
-- Password for all accounts is: password
-- Hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi

INSERT INTO `users` (`name`, `email`, `role`, `password`, `email_verified_at`, `created_at`, `updated_at`) VALUES
('Owner Account', 'owner@FreshTrack.ph', 'owner', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), NOW(), NOW()),
('Manager Account', 'manager@FreshTrack.ph', 'manager', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), NOW(), NOW()),
('Cashier Account', 'cashier@FreshTrack.ph', 'cashier', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), NOW(), NOW());

-- Step 4: Verify the accounts were created successfully
SELECT 
    id, 
    name, 
    email, 
    role,
    '✓ Account Ready - Password: password' as status,
    created_at 
FROM `users` 
WHERE `email` IN ('owner@FreshTrack.ph', 'manager@FreshTrack.ph', 'cashier@FreshTrack.ph')
ORDER BY FIELD(role, 'owner', 'manager', 'cashier');

