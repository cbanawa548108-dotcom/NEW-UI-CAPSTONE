# 🚀 Quick Test Guide - FreshTrack System

**Use this guide to quickly verify all features are working!**

---

## ⚡ 5-Minute Complete Test

### 1. LOGIN (30 seconds)
```
URL: http://127.0.0.1:8000/login
Email: owner@freshtrack.com
Password: password123
```
✅ **Expected:** Dashboard loads without errors

---

### 2. ADD PRODUCT (1 minute)
```
1. Go to: Inventory
2. Click: "Add Product"
3. Enter:
   - Name: Test Fruit
   - Category: Tropical
   - Unit: kg
   - Reorder Level: 50
4. Click: "Save Product"
```
✅ **Expected:** 
- Success message appears
- Page reloads
- Product appears in Products table

---

### 3. ADD STOCK (1 minute)
```
1. Go to: Inventory → Stock In section
2. Click: "Add Stock In"
3. Select: Test Fruit
4. Enter:
   - Quantity: 100
   - Price: 150
   - Supplier: Test Supplier
   - Expiry: (30 days from today)
5. Click: "Save"
```
✅ **Expected:**
- Success message
- Page reloads
- Product appears in Overview with 100 kg
- Record appears in Stock In Records
- Product available in Stock Out dropdown

---

### 4. CREATE SALE (1 minute)
```
1. Go to: Sales
2. Click: "New Transaction"
3. Select: Test Fruit
4. Enter: Quantity: 25
5. Click: "Add to Cart"
6. Select: Payment Method: Cash
7. Click: "Complete Sale"
```
✅ **Expected:**
- Success message
- Page reloads
- Transaction appears at top of table
- Today's Total increases by sale amount

---

### 5. ADJUST STOCK (1 minute)
```
1. Go to: Inventory → Adjustment
2. Click: "New Adjustment"
3. Select: Test Fruit
4. Type: Add
5. Quantity: 10
6. Reason: "Inventory recount"
7. Click: "Save Adjustment"
```
✅ **Expected:**
- Success message
- Page reloads
- Adjustment appears in Adjustment Records
- Quantity in Overview increases by 10

---

### 6. VERIFY DATA PERSISTENCE (30 seconds)
```
1. Refresh page (Ctrl+F5)
2. Navigate through all sections
3. Check Test Fruit appears everywhere
```
✅ **Expected:**
- All data still present
- Quantities accurate
- Records maintained

---

## 📊 Current Status Check

### Inventory Section:
- [ ] Overview shows products with quantities
- [ ] Products table shows Test Fruit
- [ ] Stock In Records shows batch
- [ ] Stock Out Records shows sale
- [ ] Adjustment Records shows adjustment

### Sales Section:
- [ ] Stats show today's total
- [ ] Transaction table shows sale
- [ ] New Transaction button opens modal
- [ ] Can add items to cart
- [ ] Can complete sale

### After Each Action:
- [ ] Success message appears
- [ ] Page auto-reloads
- [ ] New record appears in list
- [ ] Quantities update correctly

---

## 🧪 Advanced Tests

### Multi-Item Sale:
```
1. Sales → New Transaction
2. Add: Test Fruit - 10 kg
3. Add: (Another product) - 15 kg
4. Complete Sale
Expected: Both items in one transaction
```

### Stock Out:
```
1. Inventory → Stock Out
2. Select: Test Fruit
3. Quantity: 20 kg
4. Reason: Sales Transaction
5. Save
Expected: Quantity reduced, appears in both Stock Out Records AND Sales
```

### Remove from Cart:
```
1. Sales → New Transaction
2. Add item to cart
3. Click trash icon
Expected: Item removed from cart
```

---

## 🎯 What to Look For

### ✅ GOOD Signs:
- Success messages after saves
- Page reloads automatically
- New records appear immediately
- Quantities are accurate
- No JavaScript errors in console (F12)
- No "undefined" or "null" text displayed

### ❌ BAD Signs:
- Error messages
- Page doesn't reload
- Quantities don't update
- Console errors (F12 → Console tab)
- "Failed to connect to server" alerts

---

## 🔧 If Something Doesn't Work

### Quick Fixes:

1. **Clear Cache:**
   ```powershell
   php artisan cache:clear
   php artisan view:clear
   ```

2. **Hard Refresh Browser:**
   ```
   Ctrl + F5 (Windows)
   Cmd + Shift + R (Mac)
   ```

3. **Check Console:**
   ```
   Press F12
   Go to Console tab
   Look for red errors
   ```

4. **Check Laravel Logs:**
   ```
   storage/logs/laravel.log
   Look at the bottom for recent errors
   ```

---

## 📝 Testing Checklist

Copy this checklist and mark off as you test:

### Inventory:
- [ ] Add Product → Shows in table
- [ ] Add Stock In → Creates batch
- [ ] Stock In Record → Appears in list
- [ ] Overview → Shows correct quantity
- [ ] Stock Out → Reduces quantity
- [ ] Stock Out Record → Appears in list
- [ ] Adjustment Add → Increases quantity
- [ ] Adjustment Subtract → Decreases quantity
- [ ] Adjustment Record → Appears in list

### Sales:
- [ ] View existing transactions
- [ ] Stats show correct totals
- [ ] New Transaction → Modal opens
- [ ] Select product → Shows price
- [ ] Add to cart → Item appears
- [ ] Complete sale → Success + reload
- [ ] Transaction appears → In table
- [ ] Stats update → Total increases

### Data Persistence:
- [ ] Refresh page → Data still there
- [ ] Logout and login → Data still there
- [ ] Close browser → Data still there

---

## 🎉 Success Criteria

**If ALL of the following are true, your system is PERFECT:**

✅ Login works  
✅ Can add products  
✅ Can add stock  
✅ Can create sales  
✅ Can adjust stock  
✅ Everything appears in lists  
✅ Page auto-reloads after actions  
✅ Data persists after refresh  
✅ No error messages  
✅ Quantities are accurate  

---

## 💯 Test Score

Rate your system:

- **10/10 items working** = 🎉 PERFECT! Production ready!
- **8-9/10 items working** = ✅ Great! Minor tweaks needed
- **6-7/10 items working** = ⚠️ Good! Some fixes needed
- **Below 6/10** = 🔧 Needs attention - check logs

---

## 🚀 Quick Commands Reference

### Start Server:
```powershell
php artisan serve
```

### Clear Cache:
```powershell
php artisan cache:clear
php artisan view:clear
php artisan config:clear
```

### Check Routes:
```powershell
php artisan route:list
```

### Check Database:
```sql
-- Check products
SELECT * FROM inventory_items;

-- Check batches
SELECT * FROM inventory_batches;

-- Check sales
SELECT * FROM sales_transactions;
```

---

## 📞 Need Help?

If something's not working, check these files:
- `COMPLETE-SYSTEM-SUMMARY.md` - Full documentation
- `storage/logs/laravel.log` - Error logs
- Browser Console (F12) - JavaScript errors

---

**Good luck testing! Everything should work perfectly!** 🎉
