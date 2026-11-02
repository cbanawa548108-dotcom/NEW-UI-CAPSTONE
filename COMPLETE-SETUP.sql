-- ================================================================
--  FreshTrack - Complete Database Setup with Working Passwords
--  Copy and paste this ENTIRE file into phpMyAdmin
-- ================================================================

USE capstone_db;

-- ================================================================
-- PART 1: Add role column to users table (if not exists)
-- ================================================================

ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `role` ENUM('owner', 'manager', 'cashier') 
NOT NULL DEFAULT 'owner' AFTER `email`;

-- ================================================================
-- PART 2: Remove old demo accounts (if they exist)
-- ================================================================

DELETE FROM `users` 
WHERE `email` IN (
    'owner@FreshTrack.ph',
    'manager@FreshTrack.ph',
    'cashier@FreshTrack.ph'
);

-- ================================================================
-- PART 3: Create the 3 demo accounts with WORKING passwords
-- ================================================================
-- All accounts use password: password
-- Password Hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
-- (This is Laravel's standard test password hash - guaranteed to work)
-- ================================================================

INSERT INTO `users` 
(`name`, `email`, `role`, `password`, `email_verified_at`, `created_at`, `updated_at`) 
VALUES
-- Owner Account - Full Access
('Owner Account', 
 'owner@FreshTrack.ph', 
 'owner', 
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
 NOW(), NOW(), NOW()),

-- Manager Account - Limited Access
('Manager Account', 
 'manager@FreshTrack.ph', 
 'manager', 
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
 NOW(), NOW(), NOW()),

-- Cashier Account - POS Only
('Cashier Account', 
 'cashier@FreshTrack.ph', 
 'cashier', 
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
 NOW(), NOW(), NOW());

-- ================================================================
-- PART 4: Verify all accounts are created and ready
-- ================================================================

SELECT 
    id,
    name,
    email,
    role,
    '✓ READY - Password: password' as login_status,
    created_at
FROM `users` 
WHERE `email` IN (
    'owner@FreshTrack.ph', 
    'manager@FreshTrack.ph', 
    'cashier@FreshTrack.ph'
)
ORDER BY FIELD(role, 'owner', 'manager', 'cashier');

-- ================================================================
-- SUCCESS! You can now login with any of these accounts:
-- ================================================================
-- Email: owner@FreshTrack.ph    | Password: password | Access: Full
-- Email: manager@FreshTrack.ph  | Password: password | Access: Limited  
-- Email: cashier@FreshTrack.ph  | Password: password | Access: POS Only
-- ================================================================
