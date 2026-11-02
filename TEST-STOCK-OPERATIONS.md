# Test Stock Operations - Quick Guide

## Before Testing

1. **Start the server:**
   ```bash
   php artisan serve
   ```

2. **Login:**
   - URL: `http://127.0.0.1:8000/login`
   - Email: `owner@FreshTrack.ph`
   - Password: `password`

3. **Open Browser Console:**
   - Press `F12` to open Developer Tools
   - Go to "Console" tab
   - Keep it open to see any errors

---

## Test 1: Stock In (Add New Stock)

### Steps:
1. Go to: `http://127.0.0.1:8000/inventory`
2. Click **"Add Stock"** button (violet button at top right)
3. **Step 1 - Supplier Information:**
   - Supplier Name: `Test Supplier`
   - Date Received: Select today's date
   - Click **"Continue"**

4. **Step 2 - Add Items:**
   - Product: Type `Mango` (select from dropdown)
   - Quantity: `50`
   - Unit Cost: `125.00`
   - Batch ID: `TEST-001` (or let it auto-generate)
   - Expiration Date: Select 14 days from today
   - Click **"Add Item"**
   - You should see item added to list
   - Click **"Review"**

5. **Step 3 - Review & Submit:**
   - Verify all information is correct
   - Click **"Submit Transaction"**

### Expected Result:
✅ Alert message: "Stock In completed successfully!"
✅ Page reloads automatically
✅ Mango stock increased by 50 kg
✅ New batch "TEST-001" appears in the batch list

### If It Fails:
- Check browser console for errors
- Verify CSRF token exists (check page source for `<meta name="csrf-token"`)
- Ensure server is running
- Check network tab in browser dev tools

---

## Test 2: Stock Out (Remove Stock)

### Steps:
1. In Inventory page, find a product with available stock (e.g., Mango)
2. Click on the product card
3. In the batch list, click **"Stock Out"** (or similar button)
4. **Fill Stock Out Form:**
   - Select Batch: Choose any available batch
   - Quantity: `10`
   - Type: Select `Sale`
   - Reason: `Test sale transaction`
   - Date: Today
   - Click **"Submit"**

### Expected Result:
✅ Alert message: "Stock Out completed successfully!"
✅ Page reloads automatically
✅ Selected batch quantity decreased by 10
✅ Product total stock reduced by 10

### If It Fails:
- Verify batch has enough quantity
- Check console for errors
- Ensure you selected a valid batch

---

## Test 3: Stock Adjustment

### Steps:
1. In Inventory page, click **"Adjustments"** in sidebar
2. Click **"New Adjustment"** button
3. **Fill Adjustment Form:**
   - Select Product: Choose any product
   - Select Batch: Choose a batch
   - Type: Select `Add` (to increase) or `Subtract` (to decrease)
   - Quantity: `5`
   - Reason: `Inventory recount`
   - Notes: `Found additional stock` (optional)
   - Date: Today
   - Click **"Submit Adjustment"**

### Expected Result:
✅ Alert message: "Stock Adjustment completed successfully!"
✅ Page reloads automatically
✅ Stock adjusted according to type (added or subtracted)

---

## Verify in Database (phpMyAdmin)

### Check inventory_items:
```sql
SELECT 
    id, 
    name, 
    stock_quantity, 
    price_per_unit,
    updated_at
FROM inventory_items 
ORDER BY updated_at DESC 
LIMIT 10;
```

### Check inventory_batches:
```sql
SELECT 
    batch_code,
    quantity,
    price_per_unit,
    expiry_date,
    supplier,
    status,
    created_at
FROM inventory_batches 
ORDER BY created_at DESC 
LIMIT 10;
```

### Check recent changes:
```sql
-- This should show your new batch
SELECT 
    b.batch_code,
    i.name as product,
    b.quantity,
    b.supplier,
    b.created_at
FROM inventory_batches b
JOIN inventory_items i ON b.inventory_item_id = i.id
WHERE b.created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
ORDER BY b.created_at DESC;
```

---

## Troubleshooting

### Problem: "CSRF token not found"
**Solution:**
1. Clear Laravel cache:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```
2. Hard refresh browser: `Ctrl + Shift + R`
3. Check page source, search for `csrf-token`

### Problem: "Failed to connect to server"
**Solution:**
1. Verify server is running: `php artisan serve`
2. Check URL is `http://127.0.0.1:8000` (not localhost)
3. Check firewall/antivirus not blocking

### Problem: Alert shows but data doesn't update
**Solution:**
1. Check browser console for JavaScript errors
2. Manually refresh page: `F5`
3. Check database directly in phpMyAdmin

### Problem: Validation error
**Solution:**
1. Ensure all required fields are filled
2. Check date format is correct
3. Verify quantity is a positive number
4. Check batch code doesn't already exist

### Problem: "Insufficient stock"
**Solution:**
1. Check the batch has enough quantity
2. Reduce the quantity you're trying to remove
3. Select a different batch with more stock

---

## Quick Verification Checklist

After each operation, verify:

**Stock In:**
- [ ] Product appears in inventory list
- [ ] New batch created
- [ ] Stock quantity increased
- [ ] Batch details correct (supplier, expiry, price)

**Stock Out:**
- [ ] Batch quantity decreased
- [ ] Product stock decreased
- [ ] If quantity = 0, batch marked as depleted

**Stock Adjustment:**
- [ ] Stock increased (if Add type)
- [ ] Stock decreased (if Subtract type)
- [ ] Changes reflected immediately

---

## Success Indicators

✅ **Everything Working:**
- Alert messages appear after each operation
- Page reloads automatically
- Stock quantities update correctly
- New batches appear in the list
- Data persists after page reload
- No errors in browser console
- Database shows new records

❌ **Something Wrong:**
- No alert message
- Page doesn't reload
- Stock doesn't change
- Errors in console
- Network tab shows 500/400 errors
- Database records not created

---

## Console Commands for Debugging

### Check current stock:
```bash
php artisan tinker
```
Then in tinker:
```php
// Check inventory items
\App\Models\InventoryItem::all();

// Check batches
\App\Models\InventoryBatch::where('status', 'available')->get();

// Check specific product
\App\Models\InventoryItem::where('name', 'Mango')->first();
```

---

## Test Data Cleanup

If you want to remove test data:

```sql
-- Remove test batches
DELETE FROM inventory_batches 
WHERE batch_code LIKE 'TEST-%';

-- Or remove all recent batches (last 5 minutes)
DELETE FROM inventory_batches 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE);
```

Then recalculate stock:
```sql
UPDATE inventory_items i
SET stock_quantity = (
    SELECT COALESCE(SUM(quantity), 0) 
    FROM inventory_batches b 
    WHERE b.inventory_item_id = i.id 
    AND b.status = 'available'
);
```

---

## Expected Console Output

### Success (Stock In):
```
Sending Stock In: {...}
✓ Stock In completed successfully!
```

### Success (Stock Out):
```
Sending Stock Out: {...}
✓ Stock Out completed successfully!
```

### Success (Adjustment):
```
Sending Stock Adjustment: {...}
✓ Stock Adjustment completed successfully!
```

### Error Example:
```
Error: Insufficient stock for batch MNG-001
```

---

## Quick Test Summary

| Operation | Input | Expected Output |
|-----------|-------|-----------------|
| Stock In | 50 kg Mango | Stock +50, New batch created |
| Stock Out | 10 kg from batch | Stock -10, Batch decreased |
| Adjustment (Add) | +5 kg | Stock +5, Batch +5 |
| Adjustment (Subtract) | -5 kg | Stock -5, Batch -5 |

---

**Ready to test!** 🧪

Start with Test 1 (Stock In) and verify each step before moving to the next test.
