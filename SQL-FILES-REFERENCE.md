# 📚 SQL Files Reference - FreshTrack Database Setup

## 🎯 **USE THIS FILE FOR COMPLETE SETUP:**

### **`FRESHTRACK-COMPLETE-DATA.sql`** ⭐ **RECOMMENDED**
**The ONE file you need!**

**Contains:**
- ✅ User accounts (owner, manager, cashier)
- ✅ Products catalog (11 items)
- ✅ Inventory batches (15 batches with expiry dates)
- ✅ Sales transactions (7 sample transactions)
- ✅ Transaction line items (all details)
- ✅ Proper data relationships

**When to use:** 
- ✅ Fresh installation
- ✅ Your teammate clones the project
- ✅ You want to reset everything
- ✅ Quick demo setup

**How to use:**
1. Open phpMyAdmin
2. Select database: `capstone_db`
3. Go to SQL tab
4. Copy and paste entire file
5. Click "Go"
6. Done! (takes ~2 seconds)

---

## 📁 Other SQL Files (Legacy - Not Needed Anymore)

### `COMPLETE-SETUP.sql`
**Old file - Only users**
- Contains: User accounts only
- Missing: Products, sales, inventory

### `INSERT-SAMPLE-DATA.sql`
**Old file - Only products**
- Contains: Products and batches only
- Missing: Users, sales transactions

### `create_demo_users.sql`
**Old file - Only users**
- Contains: 3 user accounts
- Missing: Everything else

### `capstone_db_structure.sql`
**Structure only - No data**
- Contains: Table definitions
- Missing: All data

### `database_backup.sql`
**Old backup - May be outdated**
- Use `FRESHTRACK-COMPLETE-DATA.sql` instead

---

## 🚀 Quick Start Instructions

**For you:**
```sql
-- Already have the database? Just use FRESHTRACK-COMPLETE-DATA.sql
```

**For your teammate:**
1. Clone project
2. Run `composer install`
3. Run `npm install`
4. Copy `.env.example` to `.env`
5. Create database `capstone_db`
6. Import **`FRESHTRACK-COMPLETE-DATA.sql`** ⭐
7. Run `php artisan serve`
8. Login: owner@FreshTrack.ph / password

**That's it!** Everything works.

---

## 📊 What's Included in FRESHTRACK-COMPLETE-DATA.sql

### 👥 User Accounts (3)
| Email | Password | Role | Access |
|-------|----------|------|--------|
| owner@FreshTrack.ph | password | Owner | Full |
| manager@FreshTrack.ph | password | Manager | Inventory + Sales |
| cashier@FreshTrack.ph | password | Cashier | POS Only |

### 🍎 Products (11)
- Mango (220 kg)
- Durian (45 kg)
- Pomelo (508 kg)
- Mangosteen (92 kg)
- Lanzones (22 kg)
- Pineapple (118 kg)
- Banana (210 kg)
- Avocado (35 kg)
- Coconut (150 pc)
- BEEG (589 kg)
- Dragon Fruit (250 kg)

### 📦 Inventory Batches (15)
- All with realistic expiry dates
- FIFO ordering (earliest expiry first)
- Different suppliers
- Batch codes (e.g., MNG-001, DUR-112)

### 💰 Sales Transactions (7)
- Today's sales (3 transactions)
- Yesterday's sales (2 transactions)
- Last week's sales (2 transactions)
- Different payment methods (cash, gcash, card)
- Multiple items per transaction
- **Sorted newest first** ✅

### 🛒 Transaction Details
- Line-by-line breakdown
- Quantity, price, subtotal per item
- Proper batch tracking
- FIFO compliance

---

## ✅ Features Already Working

After importing `FRESHTRACK-COMPLETE-DATA.sql`:

### Dashboard
- ✅ Today's revenue metrics
- ✅ Low stock alerts
- ✅ Expiring items
- ✅ Sales trends

### Inventory
- ✅ Product quantities accurate
- ✅ Stock In/Out working
- ✅ Adjustments tracked
- ✅ Records auto-refresh

### Sales
- ✅ Transactions sorted (newest first)
- ✅ Unit prices display correctly
- ✅ "New Transaction" button functional
- ✅ Products show available quantities
- ✅ Price editing enabled
- ✅ Auto-refresh after sale

### POS
- ✅ Products load with correct stock
- ✅ FIFO batches
- ✅ Real-time validation
- ✅ Multiple payment methods

---

## 🔄 Reset Database

If you need to start over:

```sql
-- Option 1: Quick reset
DROP DATABASE capstone_db;
CREATE DATABASE capstone_db;
-- Then import FRESHTRACK-COMPLETE-DATA.sql

-- Option 2: Keep structure, clear data
TRUNCATE TABLE sales_items;
TRUNCATE TABLE sales_transactions;
TRUNCATE TABLE inventory_batches;
TRUNCATE TABLE inventory_items;
DELETE FROM users WHERE email LIKE '%FreshTrack.ph';
-- Then import FRESHTRACK-COMPLETE-DATA.sql
```

---

## 📝 Notes

### Why One File Instead of Multiple?

**Before (Confusing):**
1. Import `COMPLETE-SETUP.sql` (users)
2. Import `INSERT-SAMPLE-DATA.sql` (products)
3. Manually create sales data
4. Hope everything links correctly

**Now (Simple):**
1. Import `FRESHTRACK-COMPLETE-DATA.sql`
2. Done!

**Benefits:**
- ✅ No confusion about which files to run
- ✅ No order dependency
- ✅ No missing relationships
- ✅ Everything works immediately
- ✅ Perfect for teammates
- ✅ Perfect for demos

### Data Consistency

`FRESHTRACK-COMPLETE-DATA.sql` ensures:
- ✅ All foreign keys valid
- ✅ Quantities match between tables
- ✅ User IDs exist
- ✅ Batch IDs exist
- ✅ Product IDs exist
- ✅ Timestamps consistent

---

## 🆘 Troubleshooting

### "Foreign key constraint fails"
**Solution:** You're using old SQL files. Use `FRESHTRACK-COMPLETE-DATA.sql` instead.

### "Duplicate entry"
**Solution:** Clear tables first or drop/recreate database.

### "Products showing 0 kg"
**Solution:** 
1. Make sure you imported `FRESHTRACK-COMPLETE-DATA.sql` completely
2. Clear cache: `php artisan view:clear`
3. Hard refresh: Ctrl+F5

### "Latest sale not at top"
**Solution:** Already fixed in `FRESHTRACK-COMPLETE-DATA.sql`! Transactions are ordered by `created_at DESC`.

---

## 📞 For Your Teammate

**Tell them:**

> "Just import `FRESHTRACK-COMPLETE-DATA.sql` in phpMyAdmin and everything will work. 
> It's the only file you need. All other SQL files are old/outdated.
> 
> Login: owner@FreshTrack.ph / password
> 
> See `QUICK-START-GUIDE.md` for step-by-step instructions."

---

**Last Updated:** October 2, 2026  
**Current File:** `FRESHTRACK-COMPLETE-DATA.sql`  
**Status:** Production Ready ✅  
**Version:** 1.0.0
