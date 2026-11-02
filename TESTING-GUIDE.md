# FreshTrack Backend Testing Guide

## Step 1: Insert Sample Data

### Option A: Using phpMyAdmin (Recommended)
1. Open: `http://localhost/phpmyadmin`
2. Select database: `capstone_db`
3. Click "SQL" tab at the top
4. Open file: `INSERT-SAMPLE-DATA.sql`
5. Copy ALL content
6. Paste into SQL box
7. Click "Go"
8. ✅ You should see success messages

### Option B: Using Command Line
```bash
mysql -u root capstone_db < INSERT-SAMPLE-DATA.sql
```

---

## Step 2: Start Laravel Server

```bash
# In your project folder
php artisan serve
```

You should see:
```
Starting Laravel development server: http://127.0.0.1:8000
```

---

## Step 3: Login

1. Open browser: `http://127.0.0.1:8000`
2. Click "Login" or go to: `http://127.0.0.1:8000/login`
3. Use these credentials:

**Owner Account:**
- Email: `owner@FreshTrack.ph`
- Password: `password`

---

## Step 4: Test Each Module

### 🧪 Test 1: Dashboard (Owner Only)
**URL:** `http://127.0.0.1:8000/dashboard`

**What to Check:**
- ✅ Today's Sales: Should show ₱5,736.00
- ✅ Week's Sales: Should show total from last 7 days
- ✅ Inventory Value: Should show total stock value
- ✅ Low Stock Alerts: Should show 3 items (Pomelo, Lanzones, Papaya)
- ✅ Expiring Items: Should show 1 item (Pomelo - expires in 2 days)
- ✅ Sales Chart: Should display 7-day trend
- ✅ Top Products: Should list best sellers
- ✅ Recent Transactions: Should show latest sales

**Expected Data:**
```
Today's Sales: ₱5,736.00
This Week: ₱11,886.00
This Month: ₱11,886.00
Active Products: 10
Low Stock: 3 items
Expiring Soon: 1 item
Out of Stock: 1 item (Papaya)
```

---

### 🧪 Test 2: Inventory Page
**URL:** `http://127.0.0.1:8000/inventory`

**What to Check:**
- ✅ Product Cards: Should show 10 fruits grouped by type
- ✅ Batch Information: Each product shows batch details
- ✅ Status Badges:
  - Green (Available): Mango, Durian, Mangosteen, etc.
  - Amber (Low Stock): Pomelo, Lanzones
  - Red (Critical): Pomelo
  - Gray (Out of Stock): Papaya
- ✅ Expiration Dates: Should show correctly
- ✅ Freshness Scores: Should display percentages
- ✅ Stock Quantities: Should match sample data

**Sample Products You Should See:**
```
Mango - 3 batches (640 kg total)
├─ MNG-001: 285 kg (Expires in 12 days)
├─ MNG-002: 155 kg (Expires in 10 days)
└─ MNG-003: 200 kg (Expires in 14 days)

Durian - 2 batches (145 kg available)
├─ DUR-112: 145 kg (Expires in 6 days)
└─ DUR-113: 0 kg (Depleted)

Pomelo - CRITICAL LOW STOCK
└─ POM-034: 8 kg (Expires in 2 days) ⚠️

Papaya - OUT OF STOCK
└─ PAP-022: 0 kg (Depleted) ⚠️
```

---

### 🧪 Test 3: Stock Operations

#### Test 3a: Stock In
1. Click "Add Stock" button
2. Fill form:
   - Product: Select "Mango"
   - Quantity: 100 kg
   - Batch Code: MNG-004
   - Supplier: Test Supplier
   - Price: ₱115.00/kg
   - Received Date: Today
   - Expiry Date: 14 days from now
3. Click "Save"
4. ✅ Check: Mango stock should increase to 740 kg

#### Test 3b: Stock Out
1. Click product → "Stock Out"
2. Select batch: MNG-001
3. Quantity: 10 kg
4. Reason: "Sales"
5. ✅ Check: MNG-001 should decrease to 275 kg

#### Test 3c: Stock Adjustment
1. Click product → "Adjust Stock"
2. Select batch
3. Type: Add or Subtract
4. Quantity: 5 kg
5. Reason: "Recount correction"
6. ✅ Check: Stock should adjust accordingly

---

### 🧪 Test 4: POS (Point of Sale)
**URL:** `http://127.0.0.1:8000/pos`

**What to Check:**
- ✅ Product List: Should show all available products
- ✅ Stock Display: Shows available quantity
- ✅ Price Display: Shows price per unit
- ✅ Add to Cart: Click to add items
- ✅ Quantity Input: Can adjust quantities
- ✅ Cart Total: Calculates correctly
- ✅ Payment Methods: Cash, Card, GCash, Bank Transfer

**Test a Sale:**
1. Add to cart:
   - Mango: 5 kg × ₱120 = ₱600
   - Banana: 10 kg × ₱42 = ₱420
2. Check subtotal: ₱1,020
3. Select payment: Cash
4. Click "Complete Sale"
5. ✅ Check:
   - Transaction code generated (TXN-YYYYMMDD-XXXX)
   - Receipt displayed
   - Inventory automatically reduced
   - Sale appears in Sales page

---

### 🧪 Test 5: Sales Page
**URL:** `http://127.0.0.1:8000/sales`

**What to Check:**
- ✅ Transaction List: Should show 7 sample transactions
- ✅ Transaction Details:
  - Transaction Code
  - Date & Time
  - Total Amount
  - Payment Method
  - Cashier Name
  - Items Sold
- ✅ Filters: By date, payment method, etc.
- ✅ Pagination: If more than 20 transactions

**Sample Transactions You Should See:**
```
TXN-20261002-0001 | Today | ₱600.00 | Cash | 5kg Mango
TXN-20261002-0002 | Today | ₱1,860.00 | GCash | 6kg Durian + 2kg Mangosteen
TXN-20261002-0003 | Today | ₱3,276.00 | Cash | 78kg Banana
```

---

### 🧪 Test 6: Reports
**URL:** `http://127.0.0.1:8000/reports`

**What to Check:**

#### Sales Report
1. Select date range: Last 7 days
2. Click "Generate Report"
3. ✅ Check:
   - Total Sales: ₱11,886.00
   - Total Transactions: 7
   - Top Products listed
   - Daily breakdown shown

#### Inventory Report
1. Click "Inventory Report"
2. ✅ Check:
   - All products listed
   - Current stock levels
   - Batch information
   - Status indicators

#### Expiry Report
1. Click "Expiry Report"
2. Set: "Next 7 days"
3. ✅ Check:
   - Shows Pomelo (2 days remaining)
   - Shows other items expiring soon
   - Sorted by urgency

#### Profit/Loss Report
1. Select date range
2. ✅ Check:
   - Revenue calculated
   - Cost of goods shown
   - Profit margin displayed

---

### 🧪 Test 7: Notifications
**URL:** `http://127.0.0.1:8000/notifications`

**What to Check:**
- ✅ Critical Alerts (Red):
  - Pomelo expiring in 2 days
  - Papaya out of stock
- ✅ Warning Alerts (Amber):
  - Low stock items (Pomelo, Lanzones)
- ✅ Notification Count: Badge shows total
- ✅ Action Buttons: Link to relevant pages
- ✅ Priority Sorting: Critical first

**Expected Notifications:**
```
🔴 CRITICAL: Pomelo expires in 2 days (8 kg)
🔴 CRITICAL: Papaya is out of stock
🟡 WARNING: Pomelo is low on stock (8 kg / 50 kg reorder level)
🟡 WARNING: Lanzones is low on stock (22 kg / 40 kg reorder level)
```

---

## Step 5: Test API Endpoints

### Using Browser (GET requests)
Open in browser:

```
http://127.0.0.1:8000/api/inventory/stats
http://127.0.0.1:8000/api/dashboard/metrics
http://127.0.0.1:8000/api/notifications/count
http://127.0.0.1:8000/api/sales/stats?period=today
```

### Using Postman or cURL

#### Get Inventory Stats
```bash
curl http://127.0.0.1:8000/api/inventory/stats
```

Expected Response:
```json
{
  "total_items": 10,
  "total_batches": 15,
  "total_value": 250000.00,
  "low_stock_items": 3,
  "expiring_items": 1
}
```

#### Get Dashboard Metrics
```bash
curl http://127.0.0.1:8000/api/dashboard/metrics
```

#### Create a Sale (POST)
```bash
curl -X POST http://127.0.0.1:8000/api/pos/sale \
-H "Content-Type: application/json" \
-d '{
  "items": [{
    "product_id": 1,
    "batch_id": 1,
    "quantity": 3,
    "price": 120
  }],
  "payment_method": "cash"
}'
```

---

## Step 6: Test Role-Based Access

### Test as Manager
1. Logout
2. Login as: `manager@FreshTrack.ph` / `password`
3. ✅ Can access: Inventory, Sales, POS, Reports, Notifications
4. ❌ Cannot access: Dashboard, Users, Decision Support

### Test as Cashier
1. Logout
2. Login as: `cashier@FreshTrack.ph` / `password`
3. ✅ Can access: POS, Notifications
4. ❌ Cannot access: Dashboard, Inventory, Sales, Reports, Users

---

## Expected Results Summary

### ✅ Sample Data Inserted:
- **10 Products** (Mango, Durian, Pomelo, Mangosteen, Lanzones, Pineapple, Banana, Papaya, Avocado, Coconut)
- **15 Batches** (with varied expiry dates)
- **7 Sales Transactions** (from today and past week)
- **3 User Accounts** (Owner, Manager, Cashier)

### ✅ Backend Features Working:
- Real-time inventory tracking
- FIFO batch management
- Automatic stock deduction on sales
- Expiry date calculations
- Freshness score computation
- Low stock alerts
- Expiring item warnings
- Sales statistics
- Dashboard metrics
- Role-based access control

### ✅ Key Metrics to Verify:
```
Total Stock Value: ~₱150,000+
Today's Sales: ₱5,736.00
Total Transactions: 7
Low Stock Items: 3 (Pomelo, Lanzones, Papaya)
Critical Alerts: 2 (Pomelo expiring, Papaya out of stock)
Active Batches: 13
```

---

## Troubleshooting

### Issue: "No data showing"
**Solution:**
1. Check if you ran `INSERT-SAMPLE-DATA.sql`
2. Verify in phpMyAdmin: Check `inventory_items` table has 10 rows
3. Clear cache: `php artisan config:clear`
4. Restart server: `php artisan serve`

### Issue: "Page not found"
**Solution:**
1. Make sure server is running: `php artisan serve`
2. Check URL: `http://127.0.0.1:8000` (not localhost)
3. Clear route cache: `php artisan route:clear`

### Issue: "Unauthorized / Access denied"
**Solution:**
1. Make sure you're logged in
2. Check role permissions (Owner → Dashboard, Manager → Inventory, etc.)
3. Clear session: Logout and login again

### Issue: "Database connection error"
**Solution:**
1. Check MySQL is running
2. Verify `.env` settings
3. Run: `php artisan config:clear`
4. Test connection: Visit `/test-auth`

---

## What You Should See After Testing

### ✅ Dashboard:
- Live sales metrics
- Sales trend chart
- Top selling products
- Recent transactions
- Alert counts

### ✅ Inventory:
- 10 product cards
- Multiple batches per product
- Color-coded status badges
- Expiration warnings
- Stock levels

### ✅ POS:
- Product grid
- Shopping cart
- Quantity selectors
- Payment methods
- Transaction completion

### ✅ Sales:
- Transaction history
- Item details
- Payment information
- Date filters

### ✅ Reports:
- Sales analysis
- Inventory status
- Expiry tracking
- Profit calculations

### ✅ Notifications:
- Critical alerts
- Low stock warnings
- Expiring items
- Out of stock alerts

---

## Next Steps After Verification

1. ✅ Verify all pages load correctly
2. ✅ Test CRUD operations (Create, Read, Update, Delete)
3. ✅ Test sales workflow (Add to cart → Checkout → Receipt)
4. ✅ Verify stock deduction after sales
5. ✅ Check alert generation
6. ✅ Test role-based access
7. 🔄 Add more real data
8. 🔄 Test edge cases
9. 🔄 Implement frontend AJAX
10. 🔄 Add export/print features

---

**Ready to test!** 🚀

Run these commands:
```bash
# 1. Insert sample data (in phpMyAdmin)
# 2. Start server
php artisan serve

# 3. Visit
http://127.0.0.1:8000

# 4. Login
owner@FreshTrack.ph / password
```
