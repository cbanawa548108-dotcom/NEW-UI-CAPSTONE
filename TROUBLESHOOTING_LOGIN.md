# Troubleshooting Login Issues

## Problem
Cannot login with owner@FreshTrack.ph, manager@FreshTrack.ph, or cashier@FreshTrack.ph accounts.

Error message: "The provided credentials do not match our records."

## Root Causes
1. **Migration not run** - The `role` column doesn't exist in the `users` table yet
2. **Seeder not run** - The three demo accounts haven't been created in the database
3. **Password mismatch** - The password hash doesn't match

## Solution Options

### Option 1: Run Laravel Commands (RECOMMENDED)

Open your terminal/command prompt in the project folder and run:

```bash
cd c:\Users\christian\NEW-UI-CAPSTONE
php artisan migrate
php artisan db:seed
```

OR simply double-click the **`setup-rbac.bat`** file I created in your project folder.

### Option 2: Run SQL Directly (IF ARTISAN DOESN'T WORK)

1. Open **phpMyAdmin** or your MySQL client
2. Select database: `capstone_db`
3. Open the SQL tab
4. Copy and paste the entire contents of **`create_demo_users.sql`**
5. Click "Go" or "Execute"

### Option 3: Manual Database Check

Connect to your database and run:

```sql
-- Check if role column exists
DESCRIBE users;

-- Check if users exist
SELECT id, name, email, role FROM users 
WHERE email IN ('owner@FreshTrack.ph', 'manager@FreshTrack.ph', 'cashier@FreshTrack.ph');
```

If the role column doesn't show up in the DESCRIBE results, you need to run the migration.
If no users show up, you need to run the seeder or SQL script.

## Demo Account Credentials

After setup is complete, you should be able to login with:

| Role | Email | Password |
|------|-------|----------|
| Owner | owner@FreshTrack.ph | password |
| Manager | manager@FreshTrack.ph | password |
| Cashier | cashier@FreshTrack.ph | password |

## Expected Behavior After Login

- **Owner** → Redirects to Dashboard, sees all menu items
- **Manager** → Redirects to Point of Sale, sees limited menu (no Dashboard, Users, Decision Support)
- **Cashier** → Redirects to Point of Sale, sees only: POS, Notifications, Settings

## Still Not Working?

Check these:

1. **Database connection**: Make sure your `.env` file has correct database credentials
   - `DB_DATABASE=capstone_db`
   - `DB_USERNAME=root`
   - `DB_PASSWORD=` (blank or your MySQL password)

2. **Clear cache**:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan route:clear
   php artisan view:clear
   ```

3. **Check Laravel logs**: Look in `storage/logs/laravel.log` for error messages

4. **Verify password hash**: The password "password" is hashed as:
   ```
   $2y$12$LQv3c1yycwMV2SdFxq8oRuSQqhbZ8ggQPJ0xOvAz8cVKZJmZz0RJu
   ```

## Files Created for You

1. **`setup-rbac.bat`** - Double-click to run migrations and seeders
2. **`create_demo_users.sql`** - SQL script to manually create accounts
3. **`fix_auth.php`** - PHP script to check and fix database
4. **`RBAC_IMPLEMENTATION_COMPLETE.md`** - Full documentation

## Quick Test

After running the setup, try logging in with:
- Email: `owner@FreshTrack.ph`
- Password: `password`

You should be redirected to the Dashboard and see all menu items in the sidebar.
