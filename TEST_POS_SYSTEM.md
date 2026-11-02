# POS System Testing Guide

## Quick Test Steps

### 1. Login to the System
```
URL: /login
Credentials:
- Owner: owner@FreshTrack.ph / password
- Manager: manager@FreshTrack.ph / password  
- Cashier: cashier@FreshTrack.ph / password
```

### 2. Navigate to POS
```
URL: /pos
Or click "Point of Sale" from the sidebar
```

### 3. Verify Product Loading
**Expected Results:**
- [ ] Products display in grid layout
- [ ] Product names match inventory items
- [ ] Stock quantities show real numbers
- [ ] Prices display correctly (₱XX.XX/unit)
- [ ] Categories appear in filter bar
- [ ] Search box is functional

### 4. Test Product Selection
1. Click on any product card
2. **Expected**: Product detail modal opens
3. **Should Show**:
   - Product emoji/icon
   - Product name
   - Price per unit
   - Available stock
   - Category badge
   - Freshness percentage
   - Storage recommendations

### 5. Test Add to Cart
1. Click "Add to Cart" button
2. **Expected**:
   - Product appears in right cart panel
   - Quantity shows as 1
   - Subtotal calculated correctly
   - Cart item count updates
3. Click the product again
4. **Expected**: Quantity increases to 2

### 6. Test Quantity Controls
1. Click "+" button on cart item
   - **Expected**: Quantity increases
2. Click "-" button
   - **Expected**: Quantity decreases
3. Try to exceed available stock
   - **Expected**: Alert shows "Maximum stock available: X units"

### 7. Test Cart Summary
**Verify Calculations:**
- [ ] Subtotal = Sum of (quantity × price) for all items
- [ ] Discount = Subtotal × discount% / 100
- [ ] Tax = (Subtotal - Discount) × 12%
- [ ] Total = Subtotal - Discount + Tax

### 8. Test Discount Feature
1. Click "Discount" button or [Edit] link
2. Select 10% or enter custom percentage
3. **Expected**:
   - Discount amount updates
   - Total recalculates
   - Shows "Saving ₱XX.XX on ₱XX.XX"

### 9. Test Payment Methods
1. Select each payment method:
   - [ ] Cash (default)
   - [ ] GCash
   - [ ] Maya
   - [ ] Card

**For Cash Payment:**
1. Click quick amount buttons (₱100, ₱200, ₱500, ₱1000, Exact)
2. **Expected**: Cash received field updates
3. **Expected**: Change calculates automatically
4. Try amount less than total
   - **Expected**: Button disabled with "Insufficient" warning

### 10. Test Sale Completion
1. Add 2-3 products to cart
2. Select payment method (Cash)
3. Enter customer name (optional)
4. For cash: enter amount >= total
5. Click "Complete Sale"

**Expected Flow:**
- [ ] Button shows "Processing..." with spinner
- [ ] API call to `/api/pos/sale`
- [ ] Success modal appears
- [ ] Shows transaction code (TXN-YYYYMMDD-XXXX)
- [ ] Displays customer name
- [ ] Shows items count
- [ ] Shows payment method
- [ ] Shows total paid
- [ ] Shows change (if cash)

### 11. Verify Database Updates
After completing a sale, check:

**Sales Transaction Created:**
```sql
SELECT * FROM sales_transactions 
ORDER BY created_at DESC 
LIMIT 1;
```
Should show your transaction with:
- transaction_code
- subtotal, tax_amount, discount_amount, total_amount
- payment_method
- status = 'completed'

**Sales Items Created:**
```sql
SELECT si.*, ii.name 
FROM sales_items si
JOIN inventory_items ii ON si.inventory_item_id = ii.id
WHERE sale_transaction_id = [your_transaction_id];
```
Should show each product you sold with:
- quantity
- unit_price
- total_amount

**Inventory Updated:**
```sql
SELECT * FROM inventory_batches 
WHERE id IN (SELECT DISTINCT inventory_batch_id FROM sales_items);
```
Should show:
- Decreased quantity
- Status = 'depleted' if quantity = 0

**Inventory Items Updated:**
```sql
SELECT id, name, stock_quantity 
FROM inventory_items 
WHERE id IN (SELECT DISTINCT inventory_item_id FROM sales_items);
```
Should show decreased stock_quantity

### 12. Test Stock Refresh
1. After completing a sale, click "New Sale"
2. **Expected**: 
   - Cart clears
   - Products reload from API
   - Stock quantities reflect the sale

### 13. Test Search Functionality
1. Type product name in search box
2. **Expected**: Products filter in real-time
3. Clear search
4. **Expected**: All products show again

### 14. Test Category Filter
1. Click different category pills
2. **Expected**: Only products in that category show
3. Click "All"
4. **Expected**: All products show

### 15. Test Edge Cases

**Out of Stock:**
1. Find/create a product with 0 stock
2. **Expected**: 
   - Button shows "✕ Out of Stock"
   - Button is disabled
   - Cannot add to cart

**Low Stock Warning:**
1. Add all available stock to cart
2. Try to add more
3. **Expected**: Alert prevents over-selling

**Empty Cart Checkout:**
1. Clear cart
2. **Expected**: Checkout button area shows "Add items to begin checkout"

**Negative Discount:**
1. Try entering negative discount
2. **Expected**: Should handle gracefully (min: 0)

**Invalid Cash Amount:**
1. Enter cash less than total
2. **Expected**: 
   - Change shows "— Insufficient"
   - Complete Sale button disabled

### 16. Test Error Handling

**Network Error Simulation:**
1. Disable internet/server
2. Try to complete sale
3. **Expected**: Error alert shows "Failed to complete sale: [error]"

**Invalid Product:**
1. Open browser console
2. Try to add product with invalid batch
3. **Expected**: Backend returns error

---

## Console Verification

### Check API Calls
Open browser DevTools (F12) > Network tab

**On Page Load:**
- GET `/api/pos/products` → 200 OK
- Response should contain array of products

**On Complete Sale:**
- POST `/api/pos/sale` → 201 Created
- Request body contains items, payment_method, etc.
- Response contains transaction_code and total_amount

### Check Console Logs
Look for:
- "Loading products..." (if any)
- No JavaScript errors
- API response data

---

## Performance Checks

### Page Load
- [ ] POS page loads in < 2 seconds
- [ ] Products display without delay
- [ ] Images/icons render quickly

### Cart Operations
- [ ] Add to cart: instant feedback
- [ ] Quantity changes: immediate update
- [ ] Calculations: real-time

### Sale Completion
- [ ] Submit sale: < 3 seconds
- [ ] Success modal: appears quickly
- [ ] Product reload: < 2 seconds

---

## Mobile Responsiveness

Test on different screen sizes:
- [ ] Desktop (1920×1080)
- [ ] Laptop (1366×768)
- [ ] Tablet (768×1024)
- [ ] Mobile (375×667)

**Check**:
- [ ] Grid adjusts (2/3/4 columns)
- [ ] Cart panel accessible
- [ ] Buttons touchable
- [ ] Text readable

---

## Accessibility Checks

- [ ] Keyboard navigation works
- [ ] Focus indicators visible
- [ ] Button labels clear
- [ ] Error messages descriptive
- [ ] Color contrast sufficient

---

## Integration Points Verified

### ✅ Frontend → Backend
- [x] Product loading: `/api/pos/products`
- [x] Sale submission: `/api/pos/sale`
- [x] CSRF token included
- [x] JSON request/response
- [x] Error handling

### ✅ Backend → Database
- [x] Read inventory items
- [x] Read inventory batches
- [x] Create sales transactions
- [x] Create sales items
- [x] Update batch quantities
- [x] Update item stock
- [x] Transaction rollback on error

### ✅ Database → Frontend
- [x] Stock levels displayed
- [x] Prices shown
- [x] Categories listed
- [x] Batch information
- [x] Expiry dates

---

## Common Issues & Solutions

### Products Not Loading
**Symptoms**: Empty product grid, "No products found"
**Check**:
1. API endpoint accessible: `/api/pos/products`
2. Inventory items exist in database
3. Items have available batches (status='available', quantity>0)
4. Browser console for errors

### Sale Not Completing
**Symptoms**: Button disabled, error on submission
**Check**:
1. Cart not empty
2. All items have batch_id
3. Quantities within stock limits
4. CSRF token present
5. User authenticated
6. Network connection

### Stock Not Updating
**Symptoms**: Stock same after sale
**Check**:
1. Sale completed successfully
2. Database transaction committed
3. Product reload triggered
4. Cache cleared (Ctrl+F5)

### Wrong Calculations
**Symptoms**: Total doesn't match expectation
**Check**:
1. Subtotal = sum of (qty × price)
2. Discount = subtotal × discount% / 100
3. Tax = 12% of (subtotal - discount)
4. Total = subtotal - discount + tax
5. Rounding to 2 decimal places

---

## Test Scenarios

### Scenario 1: Basic Sale
1. Login as Cashier
2. Add Mango (2 kg) to cart
3. Add Banana (3 kg) to cart
4. Select Cash payment
5. Enter ₱1000
6. Complete sale
7. **Verify**: Transaction created, stock updated

### Scenario 2: Sale with Discount
1. Add products worth ₱500
2. Apply 10% discount
3. Verify discount = ₱50
4. Complete with GCash
5. **Verify**: Correct total saved

### Scenario 3: Multiple Items Same Product
1. Add Mango to cart (1 kg)
2. Add Mango again (1 kg)
3. **Verify**: Cart shows 2 kg, not 2 items
4. Increase quantity to 5 kg
5. Complete sale
6. **Verify**: 5 kg deducted from stock

### Scenario 4: FIFO Verification
1. Check product with multiple batches
2. Note earliest expiry batch
3. Sell that product
4. **Verify**: Earliest batch quantity decreased first

### Scenario 5: Out of Stock
1. Sell all available stock of a product
2. Reload products
3. **Verify**: Product shows "Out of Stock"
4. Try to add to cart
5. **Verify**: Button disabled

---

## Success Criteria

✅ **All products load from database**  
✅ **Stock quantities accurate**  
✅ **Prices match batch records**  
✅ **Sales process without errors**  
✅ **Database updates correctly**  
✅ **Stock reflects after sale**  
✅ **Calculations are accurate**  
✅ **Payment methods work**  
✅ **Error handling graceful**  
✅ **UI responsive and fast**

---

## Next Steps After Testing

If all tests pass:
1. ✅ Mark POS as production-ready
2. 📋 Train staff on usage
3. 🖨️ Implement receipt printing
4. 📄 Add PDF generation
5. 📊 Monitor transaction logs
6. 🔄 Setup automated backups

If issues found:
1. 🐛 Document the bug
2. 🔍 Check error logs
3. 💻 Debug with browser tools
4. 🔧 Fix and retest
5. ✅ Verify fix works

---

**Test Date**: _______________  
**Tester**: _______________  
**Result**: ⬜ PASS | ⬜ FAIL  
**Notes**: _______________________________________________
