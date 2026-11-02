# Verify Your FreshTrack Database

## Quick Answer: YES! Your database is configured for MySQL

### Your Database Configuration (from .env):
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=capstone_db
DB_USERNAME=root
DB_PASSWORD=(empty)
```

---

## How to Verify Database Exists

### Option 1: Using phpMyAdmin (Easiest)
1. Open phpMyAdmin (usually at: http://localhost/phpmyadmin)
2. Login with:
   - Username: `root`
   - Password: (leave empty)
3. Look for database named: **`capstone_db`** in the left sidebar

**Expected Tables:**
- ✅ users
- ✅ business_profiles
- ✅ inventory_items
- ✅ inventory_batches
- ✅ sales_transactions
- ✅ sales_items
- ✅ notifications
- ✅ cache
- ✅ cache_locks
- ✅ failed_jobs
- ✅ job_batches
- ✅ jobs
- ✅ password_reset_tokens
- ✅ sessions
- ✅ migrations

---

### Option 2: Using Command Line
Open Command Prompt and run:

```bash
# Check if MySQL is running
mysql -u root -e "SHOW DATABASES LIKE 'capstone_db';"

# If database exists, show all tables
mysql -u root capstone_db -e "SHOW TABLES;"

# Check users
mysql -u root capstone_db -e "SELECT email, role FROM users;"

# Check inventory items
mysql -u root capstone_db -e "SELECT name, stock_quantity FROM inventory_items;"
```

---

### Option 3: Using Laravel Artisan
In your project folder, run:

```bash
# Show database info
php artisan db:show

# Show tables
php artisan db:table users
php artisan db:table inventory_items
php artisan db:table inventory_batches
php artisan db:table sales_transactions
```

---

### Option 4: Using the Test Page
Visit this URL in your browser after starting the server:
```
http://127.0.0.1:8000/test-auth
```

This page will show:
- ✅ Database connection status
- ✅ Users found
- ✅ Password verification

---

## If Database Doesn't Exist - Create It

### Method 1: Using phpMyAdmin
1. Open phpMyAdmin
2. Click "New" in left sidebar
3. Database name: `capstone_db`
4. Collation: `utf8mb4_unicode_ci`
5. Click "Create"
6. Go to "Import" tab
7. Upload: `COMPLETE-SETUP.sql`
8. Click "Go"

### Method 2: Using Command Line
```bash
# Create database
mysql -u root -e "CREATE DATABASE IF NOT EXISTS capstone_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Import structure
mysql -u root capstone_db < capstone_db_structure.sql

# Import demo users
mysql -u root capstone_db < COMPLETE-SETUP.sql
```

### Method 3: Using Laravel Migrations
```bash
# Create all tables from migrations
php artisan migrate

# Then run setup SQL for demo users
mysql -u root capstone_db < COMPLETE-SETUP.sql
```

---

## Check If Data Exists

### Check Users:
```sql
SELECT id, name, email, role FROM users;
```

**Expected Result:**
| id | name            | email                      | role    |
|----|-----------------|----------------------------|---------|
| 1  | Owner Account   | owner@FreshTrack.ph       | owner   |
| 2  | Manager Account | manager@FreshTrack.ph     | manager |
| 3  | Cashier Account | cashier@FreshTrack.ph     | cashier |

### Check Inventory:
```sql
SELECT COUNT(*) as total_products FROM inventory_items;
SELECT COUNT(*) as total_batches FROM inventory_batches;
```

### Check Sales:
```sql
SELECT COUNT(*) as total_sales FROM sales_transactions;
SELECT COUNT(*) as total_items FROM sales_items;
```

---

## Common Issues & Solutions

### ❌ "Access denied for user 'root'@'localhost'"
**Solution:** Check your MySQL password
```bash
# Try with password
mysql -u root -p
```
Then update `.env`:
```
DB_PASSWORD=your_actual_password
```

### ❌ "Unknown database 'capstone_db'"
**Solution:** Create the database
```sql
CREATE DATABASE capstone_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### ❌ "SQLSTATE[HY000] [2002] No connection"
**Solution:** Start MySQL server
- **XAMPP:** Start Apache & MySQL from XAMPP Control Panel
- **WAMP:** Start WAMP server
- **Laragon:** Start Laragon
- **Service:** `net start MySQL80` (or your MySQL version)

### ❌ Tables don't exist
**Solution:** Run migrations
```bash
php artisan migrate
```
Or import SQL:
```bash
mysql -u root capstone_db < capstone_db_structure.sql
```

---

## Start Development Server

After verifying database:

```bash
# Start Laravel server
php artisan serve

# Visit in browser
http://127.0.0.1:8000
```

**Login with:**
- Email: `owner@FreshTrack.ph`
- Password: `password`

---

## Database Status Summary

| Component | Status | Location |
|-----------|--------|----------|
| Database Name | `capstone_db` | MySQL Server |
| Host | `127.0.0.1:3306` | Localhost |
| Username | `root` | MySQL User |
| Tables | 15+ tables | See structure |
| Demo Users | 3 accounts | Ready to login |
| Inventory | Empty | Add via UI |
| Sales | Empty | Will populate via POS |

---

## Next Steps After Verification

1. ✅ Verify database exists
2. ✅ Check all tables are created
3. ✅ Confirm demo users exist
4. ✅ Test login at `/login`
5. 🔄 Add sample inventory data
6. 🔄 Test POS transactions
7. 🔄 View dashboard metrics
8. 🔄 Generate reports

---

## Quick Database Check Script

Save this as `quick-db-check.php` and run `php quick-db-check.php`:

```php
<?php
$db = new PDO('mysql:host=127.0.0.1;dbname=capstone_db', 'root', '');
$tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "Database: capstone_db\n";
echo "Tables: " . count($tables) . "\n";
foreach ($tables as $table) {
    $count = $db->query("SELECT COUNT(*) FROM $table")->fetchColumn();
    echo "  • $table ($count rows)\n";
}
```

---

**Your database is configured for MySQL and should be working!** 🎉

If you're still having issues, check:
1. MySQL is running (XAMPP/WAMP/Laragon)
2. Visit `http://localhost/phpmyadmin` to verify
3. Run `php artisan serve` to start Laravel
4. Visit `http://127.0.0.1:8000/test-auth` to test connection
