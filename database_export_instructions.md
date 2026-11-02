# Database Export Instructions for capstone_db

## Method 1: Using phpMyAdmin (Easiest)
1. Start XAMPP and ensure MySQL is running
2. Open phpMyAdmin: http://localhost/phpmyadmin
3. Click on `capstone_db` database in the left sidebar
4. Click the "Export" tab at the top
5. Select "Quick" export method
6. Format: SQL
7. Click "Go" button
8. Save the file as `capstone_db_backup.sql`

## Method 2: Using Command Line (via XAMPP)
1. Start XAMPP and ensure MySQL is running
2. Open Command Prompt (cmd)
3. Navigate to your project folder:
   ```
   cd C:\Users\christian\NEW-UI-CAPSTONE
   ```
4. Run the mysqldump command:
   ```
   C:\xampp\mysql\bin\mysqldump.exe -u root capstone_db > capstone_db_backup.sql
   ```

## Method 3: Using Laravel Artisan Command
1. Start XAMPP and ensure MySQL is running
2. Open Command Prompt in your project folder
3. Run:
   ```
   php artisan db:show
   php artisan schema:dump
   ```

## To Restore the Database Later:

### Using phpMyAdmin:
1. Open phpMyAdmin
2. Create a new database named `capstone_db`
3. Select the database
4. Click "Import" tab
5. Choose your `.sql` file
6. Click "Go"

### Using Command Line:
```
C:\xampp\mysql\bin\mysql.exe -u root capstone_db < capstone_db_backup.sql
```

## Current Database Structure (from migrations):

### Tables:
1. **users** - User accounts
2. **business_profiles** - Business information
3. **inventory_items** - Fruit inventory master data
4. **inventory_batches** - Batch tracking with expiry dates
5. **sales_transactions** - Sales records
6. **sales_items** - Individual sale line items
7. **ai_forecasts** - SARIMAX forecast data
8. **owner_settings** - User preferences
9. **notifications** - System notifications with AI alerts
10. **spoilage_predictions** - XGBoost spoilage predictions (if exists)

### Recent Changes:
- Added notification fields: module, source, severity, subtitle, action_label, action_link, read_at
- Added remaining_shelf_life to inventory
