# 🚀 FreshTrack - Quick Start Guide for Your Teammate

## 📋 What Your Teammate Needs to Do

### Step 1: Clone the Project
```bash
git clone <repository-url>
cd NEW-UI-CAPSTONE
```

### Step 2: Install Dependencies
```bash
# Install PHP dependencies
composer install

# Install Node dependencies
npm install
```

### Step 3: Setup Environment File
```bash
# Copy the example environment file
copy .env.example .env

# Generate application key
php artisan key:generate
```

### Step 4: Configure Database
Edit `.env` file and update these lines:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=capstone_db
DB_USERNAME=root
DB_PASSWORD=
```

### Step 5: Create Database
1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Click "New" to create a database
3. Name it: `capstone_db`
4. Collation: `utf8mb4_unicode_ci`
5. Click "Create"

### Step 6: **🎯 ONE-FILE DATABASE SETUP** (This is the important part!)

**Instead of multiple SQL files, use ONLY this one file:**

1. Open phpMyAdmin
2. Select database: `capstone_db`
3. Click "SQL" tab
4. Open file: **`FRESHTRACK-COMPLETE-DATA.sql`**
5. Copy ALL contents
6. Paste into SQL tab
7. Click "Go" button
8. **Done!** ✅

**This single file contains:**
- ✅ User accounts (owner, manager, cashier)
- ✅ All products (11 items)
- ✅ All inventory batches (15 batches with expiry dates)
- ✅ Sample sales transactions (7 transactions)
- ✅ All transaction line items
- ✅ Proper relationships and foreign keys

**Time needed:** ~2 seconds

### Step 7: Run Migrations (Optional)
```bash
php artisan migrate
```

**Note:** If you already imported `FRESHTRACK-COMPLETE-DATA.sql`, migrations should already exist. This step ensures the structure is correct.

### Step 8: Start the Application
```bash
# Terminal 1: Start Laravel server
php artisan serve

# Terminal 2: Start Vite (for hot reload)
npm run dev
```

### Step 9: Login and Test
1. Open browser: http://127.0.0.1:8000
2. Login with any of these accounts:

| Email | Password | Role | Access |
|-------|----------|------|--------|
| owner@FreshTrack.ph | password | Owner | Full Access |
| manager@FreshTrack.ph | password | Manager | Inventory + Sales |
| cashier@FreshTrack.ph | password | Cashier | POS Only |

### Step 10: Verify Everything Works
1. **Dashboard** - Check if metrics show correctly
2. **Inventory** - See 11 products with correct quantities
3. **Sales** - See 7 transactions (newest first)
4. **POS** - Test "New Transaction" button
   - Products should show correct available quantities
   - Price should be editable
   - Sale should save and appear in Sales page

---

## ❓ Common Issues & Solutions

### Issue 1: "Database connection refused"
**Solution:** Make sure MySQL/XAMPP is running

### Issue 2: "Table not found"
**Solution:** Run `php artisan migrate` or re-import `FRESHTRACK-COMPLETE-DATA.sql`

### Issue 3: "Login failed"
**Solution:** 
- Check if you imported `FRESHTRACK-COMPLETE-DATA.sql`
- Password is: `password` (lowercase, no spaces)
- Clear cache: `php artisan cache:clear`

### Issue 4: "Products showing 0 kg available"
**Solution:**
- Make sure you imported `FRESHTRACK-COMPLETE-DATA.sql` completely
- Clear cache: `php artisan view:clear`
- Hard refresh browser: Ctrl+F5

### Issue 5: "Latest transaction not showing at top"
**Solution:** Already fixed! Transactions are sorted by `created_at DESC` (newest first)

---

## 📁 Important Files

### SQL Files (You only need ONE!)
- **`FRESHTRACK-COMPLETE-DATA.sql`** ⭐ **USE THIS ONE!** (Complete setup)
- ~~`COMPLETE-SETUP.sql`~~ (Old - only users)
- ~~`INSERT-SAMPLE-DATA.sql`~~ (Old - only products)
- ~~`create_demo_users.sql`~~ (Old - only users)

### Documentation Files
- `API_ENDPOINTS_REFERENCE.md` - All API routes
- `COMPLETE-SYSTEM-SUMMARY.md` - System overview
- `QUICK-TEST-GUIDE.md` - Testing checklist
- `SALES-PRICE-FIX-COMPLETE.md` - Price editing feature
- `PRODUCT-DROPDOWN-QUANTITY-FIX.md` - Quantity display fix

### Code Structure
```
app/
├── Http/Controllers/
│   ├── AuthController.php        (Login/Logout)
│   ├── DashboardController.php   (Dashboard metrics)
│   ├── InventoryController.php   (Inventory CRUD + Stock operations)
│   ├── SalesController.php       (Sales + POS)
│   ├── NotificationsController.php
│   └── ReportsController.php
├── Models/
│   ├── User.php
│   ├── InventoryItem.php
│   ├── InventoryBatch.php
│   ├── SalesTransaction.php
│   └── SalesItem.php
resources/views/
├── pages/
│   ├── dashboard.blade.php
│   ├── inventory.blade.php
│   ├── sales.blade.php
│   ├── pos.blade.php
│   └── notifications.blade.php
routes/
└── web.php                       (All routes + API endpoints)
```

---

## 🎯 Testing Checklist for Your Teammate

### ✅ Authentication
- [ ] Can login as owner@FreshTrack.ph
- [ ] Can login as manager@FreshTrack.ph
- [ ] Can login as cashier@FreshTrack.ph
- [ ] Can logout successfully

### ✅ Dashboard (Owner only)
- [ ] Shows today's revenue
- [ ] Shows low stock items
- [ ] Shows expiring items
- [ ] Charts display correctly

### ✅ Inventory (Owner + Manager)
- [ ] Overview shows 11 products
- [ ] Product quantities are accurate (not 0 kg)
- [ ] Can add new stock (Stock In)
- [ ] Can remove stock (Stock Out)
- [ ] Can adjust stock (Stock Adjustment)
- [ ] Records appear after operations

### ✅ Sales (Owner + Manager)
- [ ] Shows 7 existing transactions
- [ ] Newest transactions appear at top
- [ ] Unit prices display correctly (not ₱0.00/kg)
- [ ] "New Transaction" button opens modal
- [ ] Products show correct available quantities
- [ ] Price field is editable
- [ ] Can change price before adding to cart
- [ ] Can add multiple items to cart
- [ ] Sale saves successfully
- [ ] New transaction appears at top of table

### ✅ POS (All users)
- [ ] Products load correctly
- [ ] Can create sales
- [ ] Transactions save to database

---

## 🆘 Need Help?

### Clear All Caches
```bash
php artisan cache:clear
php artisan view:clear
php artisan config:clear
php artisan route:clear
```

### Reset Database (Start Over)
1. Drop database `capstone_db` in phpMyAdmin
2. Create new database `capstone_db`
3. Import `FRESHTRACK-COMPLETE-DATA.sql` again
4. Run `php artisan migrate` (optional)
5. Done!

### Check Laravel Logs
```
storage/logs/laravel.log
```

### Browser Console
Press F12 to open browser console and check for JavaScript errors

---

## 🎉 Success Indicators

Your setup is correct if you see:
- ✅ 11 products in Inventory
- ✅ Mango (Available: 220 kg)
- ✅ Durian (Available: 45 kg)
- ✅ 7 transactions in Sales
- ✅ Latest transaction at top
- ✅ Unit prices showing (e.g., ₱120.00/kg)
- ✅ Editable price field in New Transaction modal
- ✅ No "0 kg" in product dropdown

---

## 📞 Contact

If your teammate encounters issues, they can:
1. Check `storage/logs/laravel.log`
2. Clear all caches (see commands above)
3. Re-import `FRESHTRACK-COMPLETE-DATA.sql`
4. Hard refresh browser (Ctrl+F5)

---

**Last Updated:** October 2, 2026  
**Version:** 1.0.0  
**Status:** Production Ready ✅
