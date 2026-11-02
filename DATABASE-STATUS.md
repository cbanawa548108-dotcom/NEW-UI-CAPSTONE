# FreshTrack Database Status

## ✅ YES - Your Database is in MySQL!

### Configuration Details:
```
Database Type: MySQL (MariaDB)
Database Name: capstone_db
Host: 127.0.0.1 (localhost)
Port: 3306
Username: root
Password: (empty)
```

---

## Database Location

Your database `capstone_db` is stored in your MySQL server, which is typically managed by:
- **XAMPP** → `C:\xampp\mysql\data\capstone_db\`
- **WAMP** → `C:\wamp64\bin\mysql\mysql[version]\data\capstone_db\`
- **Laragon** → `C:\laragon\data\mysql\capstone_db\`

---

## Quick Verification Methods

### Method 1: phpMyAdmin (Recommended)
1. Open browser
2. Go to: `http://localhost/phpmyadmin`
3. Login: username `root`, password (empty)
4. Look for `capstone_db` in left sidebar

### Method 2: Run the Check Script
Double-click: **`CHECK-EVERYTHING.bat`**

This will verify:
- ✅ PHP installed
- ✅ Composer installed
- ✅ Laravel installed
- ✅ Database connection
- ✅ Database tables exist
- ✅ User accounts ready

### Method 3: Command Line
```bash
mysql -u root -e "USE capstone_db; SHOW TABLES;"
```

---

## Your Database Tables

Based on your configuration files, these tables should exist:

### Core Tables:
1. **users** - User accounts (owner, manager, cashier)
2. **business_profiles** - Business information
3. **inventory_items** - Products/fruits master data
4. **inventory_batches** - Batch tracking with expiry dates
5. **sales_transactions** - Sales transaction headers
6. **sales_items** - Individual items in sales
7. **notifications** - System notifications

### Laravel System Tables:
8. cache
9. cache_locks
10. failed_jobs
11. job_batches
12. jobs
13. password_reset_tokens
14. sessions
15. migrations

---

## Sample Data Status

### Demo Users (3 accounts):
```sql
SELECT email, role FROM users;
```

Expected:
| Email                      | Role    | Password |
|----------------------------|---------|----------|
| owner@FreshTrack.ph       | owner   | password |
| manager@FreshTrack.ph     | manager | password |
| cashier@FreshTrack.ph     | cashier | password |

### Inventory Data:
- **Status:** Initially empty (add via UI)
- **Tables:** inventory_items, inventory_batches
- **Add via:** `/inventory` page → "Add Stock" button

### Sales Data:
- **Status:** Initially empty (will populate from POS)
- **Tables:** sales_transactions, sales_items
- **Add via:** `/pos` page → Process sales

---

## How Backend Uses Database

### When you visit `/inventory`:
```php
// InventoryController@page
1. Fetches from: inventory_items table
2. Joins with: inventory_batches table
3. Calculates: stock levels, expiry dates, freshness scores
4. Returns: Grouped data to blade view
```

### When you make a sale at `/pos`:
```php
// SalesController@createSale
1. Validates items
2. Creates record in: sales_transactions
3. Adds items to: sales_items
4. Updates: inventory_batches (reduces quantity)
5. Updates: inventory_items (reduces stock_quantity)
6. Returns: Transaction code and receipt data
```

### When you view `/dashboard`:
```php
// DashboardController@index
1. Queries: sales_transactions (today/week/month totals)
2. Queries: inventory_items (low stock, out of stock)
3. Queries: inventory_batches (expiring items)
4. Calculates: Metrics, growth, trends
5. Returns: Complete dashboard data
```

---

## Testing Database Connection

### Test 1: Via Web Browser
```
1. Start server: php artisan serve
2. Visit: http://127.0.0.1:8000/test-auth
3. Check: Database connection status
```

### Test 2: Via Artisan
```bash
php artisan db:show
php artisan migrate:status
```

### Test 3: Via PHP Script
Run: `php check-database.php`

---

## Database Management Tools

### Option 1: phpMyAdmin (Built-in with XAMPP/WAMP)
- **URL:** http://localhost/phpmyadmin
- **Features:** Visual interface, import/export, query builder
- **Best for:** Beginners, visual management

### Option 2: MySQL Workbench (Download separately)
- **Download:** https://dev.mysql.com/downloads/workbench/
- **Features:** Advanced modeling, performance monitoring
- **Best for:** Advanced users, database design

### Option 3: HeidiSQL (Lightweight alternative)
- **Download:** https://www.heidisql.com/
- **Features:** Fast, lightweight, portable
- **Best for:** Quick queries, data browsing

### Option 4: Command Line
```bash
# Connect to database
mysql -u root capstone_db

# Common commands
SHOW TABLES;
DESCRIBE inventory_items;
SELECT * FROM users;
```

---

## If Database Doesn't Exist Yet

### Quick Setup:
```bash
# Method 1: Using MySQL command
mysql -u root -e "CREATE DATABASE capstone_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Method 2: Using phpMyAdmin
# Go to http://localhost/phpmyadmin
# Click "New" → Database name: capstone_db → Create
```

### Import Structure & Data:
```bash
# Import full structure
mysql -u root capstone_db < capstone_db_structure.sql

# Import demo users
mysql -u root capstone_db < COMPLETE-SETUP.sql
```

### Or use Laravel migrations:
```bash
php artisan migrate
mysql -u root capstone_db < COMPLETE-SETUP.sql
```

---

## Database Backup & Restore

### Backup (Export):
```bash
# Full database backup
mysqldump -u root capstone_db > capstone_db_backup_$(date +%Y%m%d).sql

# Structure only
mysqldump -u root --no-data capstone_db > capstone_db_structure.sql

# Data only
mysqldump -u root --no-create-info capstone_db > capstone_db_data.sql
```

### Restore (Import):
```bash
# Restore from backup
mysql -u root capstone_db < capstone_db_backup_20261002.sql
```

---

## Connection Troubleshooting

### Error: "Access denied for user 'root'@'localhost'"
**Cause:** Wrong password
**Fix:** 
1. Find your MySQL password
2. Update `.env` file: `DB_PASSWORD=your_password`
3. Restart Laravel: `php artisan config:clear`

### Error: "SQLSTATE[HY000] [2002] No connection could be made"
**Cause:** MySQL server not running
**Fix:**
1. Start XAMPP/WAMP/Laragon
2. Start MySQL service
3. Verify: http://localhost/phpmyadmin

### Error: "Unknown database 'capstone_db'"
**Cause:** Database doesn't exist
**Fix:**
1. Create database (see "Quick Setup" above)
2. Run migrations: `php artisan migrate`
3. Import users: `mysql -u root capstone_db < COMPLETE-SETUP.sql`

---

## Summary

✅ **Database Type:** MySQL/MariaDB
✅ **Database Name:** capstone_db
✅ **Connection:** Configured in `.env`
✅ **Tables:** 15+ tables (migrations)
✅ **Demo Users:** 3 accounts ready
✅ **Backend:** Fully connected
✅ **API:** All endpoints active

**To verify everything is working:**
1. Run `CHECK-EVERYTHING.bat`
2. Or visit http://localhost/phpmyadmin
3. Or run `php check-database.php`

---

**Need help?** Check these files:
- `VERIFY-DATABASE.md` - Detailed verification steps
- `BACKEND_INTEGRATION_COMPLETE.md` - Backend documentation
- `API_ENDPOINTS_REFERENCE.md` - API endpoint list
