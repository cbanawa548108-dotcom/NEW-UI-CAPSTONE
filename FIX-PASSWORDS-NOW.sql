-- COPY AND RUN THIS IN PHPMYADMIN RIGHT NOW
-- This will fix all 3 accounts immediately

USE capstone_db;

-- Update owner account
UPDATE users 
SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE email = 'owner@FreshTrack.ph';

-- Update manager account
UPDATE users 
SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE email = 'manager@FreshTrack.ph';

-- Update cashier account
UPDATE users 
SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE email = 'cashier@FreshTrack.ph';

-- Verify the fix
SELECT email, role, 'PASSWORD UPDATED - Use: password' as status 
FROM users 
WHERE email IN ('owner@FreshTrack.ph', 'manager@FreshTrack.ph', 'cashier@FreshTrack.ph');
