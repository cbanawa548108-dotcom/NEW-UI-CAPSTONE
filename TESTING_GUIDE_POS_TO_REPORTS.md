# Complete Testing Guide: POS to Reports Data Flow

## 🎯 What We're Testing
This guide will verify that:
1. Sales made in POS are recorded in database
2. Reports show real-time data from those sales
3. PDF export works correctly
4. All data flows end-to-end

---

## 📋 Pre-Test Setup

### 1. Ensure Sample Data Exists
```bash
# Check if you have inventory
mysql -u root capstone_db -e "SELECT COUNT(*) as product_count FROM inventory_items;"

# If count is 0, import sample data:
mysql -u root capstone_db < FRESHTRACK-COMPLETE-DATA.sql
```

### 2. Login as Owner or Manager
- Only Owner and Manager roles can access Reports
- Cashier can use POS but not Reports
- Use credentials from `create_demo_users.sql`

---

## 🧪 Test Scenario: Complete Sale and Verify in Reports

### STEP 1: Open POS and Check Products Load

**Action:**
1. Navigate to: `http://localhost:8000/pos`
2. Open browser DevTools (F12) → Console tab
3. Look for: `"Products loaded from API"`

**Expected Result:**
```
Fetching products from /api/pos/products...
[{id: 1, name: "Mango", stock_quantity: 150, ...}, ...]
```

**What to Check:**
- ✅ Product grid displays fruits
- ✅ Each product shows name, price, stock
- ✅ Console shows successful API call
- ❌ If empty: No inventory in database, import sample data

---

### STEP 2: Make a Test Sale

**Action:**
1. Click on a product (e.g., **Mango**)
2. Product appears in cart on the right
3. **Edit quantity** to `3.5` (decimal test)
4. Select **Payment Method**: Cash
5. Enter **Customer Name**: "Test Customer"
6. Click **"Complete Sale"** button

**Expected Result:**
```
Transaction complete! Code: TXN-20261007-1234
Sale completed successfully
```

**What to Check:**
- ✅ Success message appears
- ✅ Transaction code shown (format: TXN-YYYYMMDD-XXXX)
- ✅ Cart clears automatically
- ✅ Product stock decreases
- ❌ If error: Check console for details

**Console Output Should Show:**
```javascript
{
  "success": true,
  "message": "Sale completed successfully",
  "data": {
    "transaction_code": "TXN-20261007-1234",
    "total_amount": 420.00,
    "transaction_id": 15
  }
}
```

---

### STEP 3: Verify Database Update

**Action:**
Open phpMyAdmin or MySQL client and run:

```sql
-- Check the transaction
SELECT 
    transaction_code,
    payment_method,
    total_amount,
    status,
    created_at
FROM sales_transactions 
ORDER BY created_at DESC 
LIMIT 1;
```

**Expected Result:**
```
transaction_code: TXN-20261007-1234
payment_method: cash
total_amount: 420.00
status: completed
created_at: 2026-10-07 14:23:45
```

**What to Check:**
- ✅ Transaction exists
- ✅ Payment method is correct (cash/gcash)
- ✅ Status is 'completed'
- ✅ Timestamp is recent

---

**Action:**
Check the line items:

```sql
SELECT 
    si.id,
    ii.name as product,
    si.quantity,
    si.unit_price,
    si.total_amount,
    ib.batch_code
FROM sales_items si
JOIN inventory_items ii ON si.inventory_item_id = ii.id
LEFT JOIN inventory_batches ib ON si.inventory_batch_id = ib.id
WHERE si.sale_transaction_id = (
    SELECT id FROM sales_transactions 
    ORDER BY created_at DESC LIMIT 1
);
```

**Expected Result:**
```
product: Mango
quantity: 3.50
unit_price: 120.00
total_amount: 420.00
batch_code: BATCH-001
```

**What to Check:**
- ✅ Decimal quantity saved correctly (3.50)
- ✅ Calculation is correct (3.5 × 120 = 420)
- ✅ Batch reference exists (FIFO tracking)

---

**Action:**
Verify inventory was decremented:

```sql
SELECT 
    ii.name,
    ii.stock_quantity,
    ib.batch_code,
    ib.quantity as batch_qty,
    ib.status
FROM inventory_items ii
JOIN inventory_batches ib ON ib.inventory_item_id = ii.id
WHERE ii.name = 'Mango'
ORDER BY ib.expiry_date ASC;
```

**Expected Result:**
```
Before sale: stock_quantity: 150.00, batch_qty: 50.00
After sale:  stock_quantity: 146.50, batch_qty: 46.50
```

**What to Check:**
- ✅ Total stock decreased by 3.5
- ✅ FIFO batch quantity decreased
- ✅ If batch is empty, status = 'depleted'

---

### STEP 4: Check Reports Show the Sale

**Action:**
1. Navigate to: `http://localhost:8000/reports`
2. Keep DevTools Console open
3. Wait for sales report to load

**Expected Console Output:**
```javascript
✅ Sales Report loaded: {
  "period": {
    "start_date": "2026-09-07",
    "end_date": "2026-10-07",
    "days": 31
  },
  "summary": {
    "total_sales": 420.00,
    "total_transactions": 1,
    "average_transaction": 420.00,
    "total_items_sold": 3.5
  },
  "top_products": [
    {
      "product_name": "Mango",
      "quantity_sold": 3.5,
      "total_sales": 420.00,
      "transaction_count": 1,
      "average_price": 120.00
    }
  ]
}
```

**What to Check:**
- ✅ `total_sales` reflects your transaction
- ✅ `total_transactions` increased
- ✅ Mango appears in `top_products`
- ✅ Quantity shows as decimal (3.5)
- ❌ If showing zeros: No sales in last 30 days, make another sale

---

### STEP 5: Test CSV Export

**Action:**
1. Stay on **Sales** tab
2. Click **"Download CSV"** button (top right)

**Expected Result:**
- File downloads: `sales_report.csv`
- Opens in Excel/Google Sheets

**File Should Contain:**
```csv
Sales Report

Product,Quantity Sold,Revenue
Mango,3.5,420
```

**What to Check:**
- ✅ File downloads successfully
- ✅ Data matches what's in Reports
- ✅ Decimal quantities preserved
- ❌ If alert shows: Data not loaded, wait for loading to finish

---

### STEP 6: Test PDF Export ✨ NEW!

**Action:**
1. Stay on **Sales** tab
2. Click **"PDF"** button (top right)

**Expected Result:**
1. New browser window/tab opens
2. Shows formatted PDF report
3. Print dialog appears automatically
4. Report contains:
   - 🍎 FreshTrack header
   - 📅 Date range
   - 💰 Total Sales: ₱420.00
   - 🧾 Total Transactions: 1
   - 📊 Table with Mango, 3.50 kg, ₱420.00

**What to Check:**
- ✅ PDF window opens
- ✅ Print dialog appears (or manually press Ctrl+P)
- ✅ Data is correct
- ✅ Formatting looks professional

**Print Options:**
- **Save as PDF**: Choose "Save as PDF" printer
- **Print to Printer**: Select physical printer
- **Cancel**: Just close the window

**Troubleshooting:**
- ❌ If popup blocked: Allow popups for localhost
- ❌ If blank page: Check Laravel logs
- ❌ If wrong data: Clear cache and try again

---

### STEP 7: Test Other Report Tabs

**Action:**
Click each tab and verify data loads:

#### Inventory Tab
```javascript
✅ Inventory Report loaded: {
  "summary": {
    "total_products": 15,
    "total_stock_quantity": 5426.5,  // Decreased by 3.5
    "total_inventory_value": 283650,
    "low_stock_items": 2
  },
  "items": [...]
}
```

**What to Check:**
- ✅ Total stock quantity reflects sale
- ✅ Mango's stock is 3.5 less

#### Spoilage Tab
```javascript
✅ Expiry Report loaded: {
  "summary": {
    "total_batches_expiring": 5,
    "total_value_at_risk": 12400
  },
  "expiring_batches": [...]
}
```

**What to Check:**
- ✅ Shows batches expiring in next 30 days
- ✅ No JavaScript errors

#### Profit Tab
```javascript
✅ Profit/Loss Report loaded: {
  "summary": {
    "total_revenue": 420.00,
    "cost_of_goods_sold": 240.00,
    "gross_profit": 180.00,
    "gross_margin_percentage": 42.86
  }
}
```

**What to Check:**
- ✅ Revenue matches your sale
- ✅ COGS calculated from batch cost
- ✅ Profit = Revenue - COGS

---

## 🎯 Complete End-to-End Test Checklist

Run through this entire flow to verify everything works:

### ✅ POS Module
- [ ] Products load from database
- [ ] Can add product to cart
- [ ] Can enter decimal quantity (3.5)
- [ ] Can select payment method (Cash/GCash)
- [ ] Complete Sale button works
- [ ] Transaction code generated
- [ ] Success message shows
- [ ] Cart clears after sale

### ✅ Database
- [ ] `sales_transactions` record created
- [ ] `payment_method` saved correctly
- [ ] `sales_items` created with decimal quantity
- [ ] `inventory_batches` quantity decreased (FIFO)
- [ ] `inventory_items` stock_quantity decreased
- [ ] All timestamps are current

### ✅ Reports Module
- [ ] Sales tab loads and shows transaction
- [ ] Top products includes sold item
- [ ] Inventory tab shows decreased stock
- [ ] All tabs load without errors
- [ ] Console shows ✅ success messages
- [ ] No ❌ error messages

### ✅ Export Functions
- [ ] CSV download works
- [ ] CSV data is correct
- [ ] **PDF button opens report** ✅ NEW!
- [ ] **PDF shows correct data** ✅ NEW!
- [ ] **Print dialog appears** ✅ NEW!
- [ ] Print function works

---

## 🐛 Common Issues & Solutions

### Issue: POS Products Empty
**Symptom**: No products in POS grid
**Cause**: No inventory in database
**Solution**:
```bash
mysql -u root capstone_db < FRESHTRACK-COMPLETE-DATA.sql
```

### Issue: "Insufficient stock" Error
**Symptom**: Sale fails with stock error
**Cause**: Batch quantity less than requested
**Solution**: Check batch quantities in database or reduce quantity

### Issue: Reports Show Zero Values
**Symptom**: All reports show 0 or empty
**Cause**: No sales in last 30 days
**Solution**: Make a sale in POS, then refresh Reports

### Issue: PDF Popup Blocked
**Symptom**: Click PDF button, nothing happens
**Cause**: Browser blocking popups
**Solution**: 
1. Look for popup blocker icon in address bar
2. Click "Allow popups from localhost"
3. Try PDF button again

### Issue: Console Shows ❌ Error
**Symptom**: Red error in console
**Solutions**:
- Check if logged in (session active)
- Check role (Owner/Manager only for Reports)
- Check Laravel logs: `storage/logs/laravel.log`
- Clear cache: `php artisan cache:clear`

---

## 📊 Expected Results Summary

After completing all tests, you should have:

1. ✅ **1+ completed sale** in `sales_transactions`
2. ✅ **Decreased inventory** in `inventory_items`
3. ✅ **Reports showing real data** from your sales
4. ✅ **Working CSV export** with correct data
5. ✅ **Working PDF export** with print capability
6. ✅ **No JavaScript errors** in console
7. ✅ **All ✅ checkmarks** in console logs

---

## 🎉 Success Criteria

**Your system is working correctly if:**
- You can make a sale in POS
- Database updates immediately
- Reports reflect the sale within seconds
- CSV downloads with correct data
- **PDF opens and prints** ✅ NEW!
- All console logs show ✅ green checkmarks

---

**Testing Date**: _______________  
**Tester**: _______________  
**Result**: ☐ PASS  ☐ FAIL  
**Notes**: _________________________________

---

**System Version**: v1.0-beta  
**Last Updated**: October 7, 2026  
**Status**: ✅ READY FOR TESTING
