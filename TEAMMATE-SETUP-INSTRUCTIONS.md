# 👋 Hey Teammate! Welcome to FreshTrack

## 🎯 TL;DR (Too Long; Didn't Read)

**Just do these 3 things:**
1. Clone project and run `composer install` + `npm install`
2. Import **`FRESHTRACK-COMPLETE-DATA.sql`** in phpMyAdmin
3. Run `php artisan serve` and login with: `owner@FreshTrack.ph` / `password`

**Done!** 🎉

---

## 📝 Detailed Steps

### 1️⃣ Clone & Install Dependencies (5 minutes)

```bash
# Clone the repository
git clone <repository-url>
cd NEW-UI-CAPSTONE

# Install PHP dependencies
composer install

# Install Node dependencies
npm install

# Setup environment
copy .env.example .env

# Generate application key
php artisan key:generate
```

### 2️⃣ Configure Database (2 minutes)

**Edit `.env` file:**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=capstone_db
DB_USERNAME=root
DB_PASSWORD=
```

**Create database in phpMyAdmin:**
1. Open http://localhost/phpmyadmin
2. Click "New"
3. Database name: `capstone_db`
4. Collation: `utf8mb4_unicode_ci`
5. Click "Create"

### 3️⃣ Import Data - THE IMPORTANT PART! (2 seconds)

**⭐ Use ONLY this one file:** `FRESHTRACK-COMPLETE-DATA.sql`

**Steps:**
1. In phpMyAdmin, select database `capstone_db`
2. Click "SQL" tab at the top
3. Open `FRESHTRACK-COMPLETE-DATA.sql` in notepad
4. Copy ALL contents (Ctrl+A, Ctrl+C)
5. Paste into SQL tab (Ctrl+V)
6. Click "Go" button at the bottom
7. Wait ~2 seconds
8. **Done!** ✅

**This one file includes:**
- ✅ 3 user accounts (owner, manager, cashier)
- ✅ 11 products with realistic data
- ✅ 15 inventory batches with expiry dates
- ✅ 7 sample sales transactions
- ✅ All relationships properly linked

**DON'T import these files:**
- ❌ `COMPLETE-SETUP.sql` (old)
- ❌ `INSERT-SAMPLE-DATA.sql` (old)
- ❌ `create_demo_users.sql` (old)

### 4️⃣ Start the Application (30 seconds)

```bash
# Start Laravel server
php artisan serve

# In another terminal, start Vite
npm run dev
```

### 5️⃣ Login & Test (1 minute)

**Open browser:** http://127.0.0.1:8000

**Login with:**
| Email | Password | Access Level |
|-------|----------|-------------|
| owner@FreshTrack.ph | password | Full Access (Dashboard, Inventory, Sales) |
| manager@FreshTrack.ph | password | Inventory + Sales Only |
| cashier@FreshTrack.ph | password | POS Only |

**Test these pages:**
1. **Dashboard** - Should show revenue, low stock, expiring items
2. **Inventory** - Should show 11 products (Mango, Durian, Pomelo, etc.)
3. **Sales** - Should show 7 transactions with newest at top
4. **New Transaction** - Should show products with correct quantities (not 0 kg)

---

## ✅ Success Checklist

Your setup is **100% correct** if you see:

### In Inventory Page:
- ✅ Mango (Available: **220 kg**)
- ✅ Durian (Available: **45 kg**)
- ✅ Pomelo (Available: **508 kg**)
- ✅ Total of **11 products**

### In Sales Page:
- ✅ **7 transactions** in the table
- ✅ **Newest transaction at the top**
- ✅ Unit prices showing (e.g., **₱120.00/kg**)
- ✅ NOT showing ₱0.00/kg

### In New Transaction Modal:
- ✅ Product dropdown shows **correct quantities**
- ✅ NOT showing "Available: 0 kg"
- ✅ **Price field is editable** (not gray/disabled)
- ✅ Can change price before adding to cart
- ✅ Sale saves successfully
- ✅ New transaction appears at top of Sales table

---

## 🚨 Common Issues & Quick Fixes

### Issue 1: "Database connection refused"
```bash
# Make sure XAMPP/MySQL is running
# Check Task Manager for mysqld.exe
```

### Issue 2: "Table 'capstone_db.users' doesn't exist"
```bash
# You need to import the SQL file or run migrations
php artisan migrate

# Then import FRESHTRACK-COMPLETE-DATA.sql
```

### Issue 3: "Login not working / Invalid credentials"
```bash
# Make sure you imported FRESHTRACK-COMPLETE-DATA.sql
# Password is: password (lowercase, no spaces)
# Clear cache:
php artisan cache:clear
```

### Issue 4: "Products showing 0 kg in dropdown"
```bash
# This means you didn't import FRESHTRACK-COMPLETE-DATA.sql completely
# Re-import the file:
# 1. Drop database capstone_db
# 2. Create database capstone_db
# 3. Import FRESHTRACK-COMPLETE-DATA.sql again

# Then clear caches:
php artisan cache:clear
php artisan view:clear

# Hard refresh browser: Ctrl+F5
```

### Issue 5: "Unit price showing ₱0.00/kg"
```bash
# This is already fixed in the code!
# Clear caches and hard refresh:
php artisan cache:clear
php artisan view:clear
# Browser: Ctrl+F5
```

### Issue 6: "Latest transaction not at top"
```bash
# This is already fixed in the code!
# Transactions are sorted by created_at DESC (newest first)
# Just reload the page
```

---

## 📚 Helpful Documentation

### Quick References:
- **`QUICK-START-GUIDE.md`** - Full setup guide
- **`SQL-FILES-REFERENCE.md`** - Explanation of all SQL files
- **`API_ENDPOINTS_REFERENCE.md`** - All API routes
- **`COMPLETE-SYSTEM-SUMMARY.md`** - System architecture

### Feature Documentation:
- **`SALES-PRICE-FIX-COMPLETE.md`** - Editable prices in POS
- **`PRODUCT-DROPDOWN-QUANTITY-FIX.md`** - Accurate quantity display
- **`SALES-DROPDOWN-AND-SORT-FIX.md`** - Transaction sorting

### Testing Guides:
- **`QUICK-TEST-GUIDE.md`** - 5-minute testing checklist
- **`TEST-INVENTORY-WORKFLOW.md`** - Complete inventory testing

---

## 🧹 Reset Everything (If Needed)

**If something goes wrong and you want to start over:**

```bash
# Option 1: Full reset (recommended)
# 1. In phpMyAdmin:
DROP DATABASE capstone_db;
CREATE DATABASE capstone_db;

# 2. Import FRESHTRACK-COMPLETE-DATA.sql again

# 3. Clear all caches:
php artisan cache:clear
php artisan view:clear
php artisan config:clear
php artisan route:clear

# 4. Restart server:
php artisan serve
```

---

## 💡 Pro Tips

### 1. Use the Owner Account for Testing
The owner account has full access to everything. Use it first to make sure all features work.

### 2. Check Browser Console (F12)
If something doesn't work, press F12 to open Developer Tools and check the Console tab for JavaScript errors.

### 3. Check Laravel Logs
If there's a backend error, check:
```
storage/logs/laravel.log
```

### 4. Clear Caches Regularly
When in doubt, clear caches:
```bash
php artisan cache:clear
php artisan view:clear
php artisan config:clear
```

### 5. Hard Refresh Browser
Sometimes the browser caches old JavaScript. Use:
- **Windows:** Ctrl+F5 or Ctrl+Shift+R
- **Mac:** Cmd+Shift+R

---

## 🎯 What Each Module Does

### Dashboard (Owner Only)
- View today's revenue
- See low stock alerts
- Check expiring items
- View sales trends

### Inventory (Owner + Manager)
- View all products
- Add new products
- Stock In (receive new inventory)
- Stock Out (sell or remove stock)
- Stock Adjustment (fix discrepancies)
- View stock records

### Sales (Owner + Manager)
- View all transactions
- Create new sales (New Transaction button)
- Filter transactions
- Export reports
- See payment methods

### POS (All Users)
- Quick sales interface
- Product selection
- Cart system
- Multiple payment methods

### Notifications (All Users)
- Low stock alerts
- Expiring item alerts
- System notifications

---

## 🎉 You're All Set!

If you followed these steps and everything checks out, you're ready to:
- ✅ Develop new features
- ✅ Test existing features
- ✅ Demo the system
- ✅ Fix bugs
- ✅ Add more functionality

### Need Help?

1. **Check documentation files** (listed above)
2. **Check Laravel logs** (`storage/logs/laravel.log`)
3. **Check browser console** (F12 → Console tab)
4. **Clear all caches** (commands above)
5. **Reset database** (drop and re-import)

---

## 📞 Final Checklist

Before you start coding, verify:

- [ ] ✅ XAMPP/MySQL is running
- [ ] ✅ Database `capstone_db` exists
- [ ] ✅ Imported `FRESHTRACK-COMPLETE-DATA.sql`
- [ ] ✅ Ran `composer install`
- [ ] ✅ Ran `npm install`
- [ ] ✅ `.env` file configured correctly
- [ ] ✅ `php artisan serve` is running
- [ ] ✅ `npm run dev` is running
- [ ] ✅ Can login with owner@FreshTrack.ph
- [ ] ✅ Dashboard shows data
- [ ] ✅ Inventory shows 11 products
- [ ] ✅ Sales shows 7 transactions
- [ ] ✅ New Transaction button works
- [ ] ✅ Products show correct quantities
- [ ] ✅ Prices are editable

**All checked?** Perfect! Start coding! 🚀

---

**Welcome to the team!** 🎊  
**Happy coding!** 💻  
**Questions?** Check the documentation files! 📚

---

**Last Updated:** October 2, 2026  
**Setup Time:** ~10 minutes  
**Difficulty:** Easy ⭐  
**Status:** Production Ready ✅
