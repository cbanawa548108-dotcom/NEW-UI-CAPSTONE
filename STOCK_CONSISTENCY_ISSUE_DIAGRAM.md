# Stock Consistency Issue - Visual Explanation

## 🔴 Current Problem: Two Sources of Truth

```
┌─────────────────────────────────────────────────────────────┐
│                     INVENTORY_ITEMS TABLE                   │
├─────────────────────────────────────────────────────────────┤
│ id │ name    │ stock_quantity │ reorder_level │ status     │
├────┼─────────┼────────────────┼───────────────┼────────────┤
│ 1  │ Mango   │      100       │      50       │  active    │ ❌ STALE!
└─────────────────────────────────────────────────────────────┘
                              │
                              │ What is the TRUE stock?
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                   INVENTORY_BATCHES TABLE                   │
├─────────────────────────────────────────────────────────────┤
│ id │ item_id │ batch_code │ quantity │ status    │ expiry  │
├────┼─────────┼────────────┼──────────┼───────────┼─────────┤
│ 1  │    1    │  MNG-001   │    50    │ available │ Jun 30  │ ✅
│ 2  │    1    │  MNG-002   │    30    │   sold    │ Jul 15  │ ❌ Not counted
│ 3  │    1    │  MNG-003   │    20    │  expired  │ Jun 10  │ ❌ Not counted
└─────────────────────────────────────────────────────────────┘

ACTUAL AVAILABLE STOCK: 50 kg ✅ (only available batches)
CACHED IN DB:          100 kg ❌ (never updated!)

Result: System shows "Available" when it should show "Low Stock"!
```

---

## 📊 What Happens in Different Scenarios

### Scenario 1: Product Creation (✅ Correct)
```
1. Create Product "Mango"
   └─> inventory_items.stock_quantity = 0 ✅

2. No batches yet
   └─> SUM(batches.quantity) = 0 ✅

Status: "Out of Stock" ✅ CORRECT
```

---

### Scenario 2: After Stock In (❌ Breaks)
```
1. Product "Mango" exists
   └─> inventory_items.stock_quantity = 0

2. Stock In adds batch: 100 kg
   └─> inventory_batches: +100 kg ✅
   └─> inventory_items.stock_quantity = 0 ❌ NOT UPDATED!

3. Frontend checks stock:
   ├─> Uses: product.stock_quantity = 0 ❌
   └─> Should use: SUM(batches.quantity) = 100 ✅

Status: "Out of Stock" ❌ WRONG! (Should be "Available")
```

---

### Scenario 3: After Sales (❌ Breaks Worse)
```
1. Product "Mango"
   └─> inventory_items.stock_quantity = 100

2. Sale of 50 kg
   └─> Batch status changed to "sold" ✅
   └─> inventory_items.stock_quantity = 100 ❌ STALE!

3. Frontend checks stock:
   ├─> Uses: product.stock_quantity = 100 ❌ (stale)
   └─> Should use: SUM(available batches) = 50 ✅

Status: "Available" ❌ WRONG! (Should be "Low Stock")
```

---

### Scenario 4: Multiple Active Batches (❌ Complex)
```
Product: Mango
├─> Batch 1: 50 kg (available) ✅
├─> Batch 2: 30 kg (available) ✅
└─> Batch 3: 20 kg (expired)   ❌

TRUE STOCK: 80 kg (Batch 1 + Batch 2)
CACHED:    100 kg (never recalculated)

If reorder_level = 75 kg:
├─> Using cached (100): "Available" ❌ WRONG
└─> Using computed (80): "Available" ✅ CORRECT

But what if actual is 70 kg after more sales?
├─> Using cached (100): "Available" ❌ VERY WRONG
└─> Using computed (70): "Low Stock" ✅ CORRECT
```

---

## 🛠️ The Fix: Use Computed Values

### Current (Broken) Logic:
```php
// ❌ USES DATABASE COLUMN (STALE)
public function getIsLowStockAttribute()
{
    return $this->stock_quantity <= $this->reorder_level;
    //     ^^^^^^^^^^^^^^^^^^^^ From DB column (never updated)
}
```

### Fixed Logic:
```php
// ✅ USES COMPUTED VALUE (ALWAYS ACCURATE)
public function getIsLowStockAttribute()
{
    return $this->total_stock <= $this->reorder_level;
    //     ^^^^^^^^^^^^^^^^^^ Computed from batches (SUM)
}

// Where total_stock is defined as:
public function getTotalStockAttribute()
{
    return $this->availableBatches()->sum('quantity');
    //     ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
    //     Always accurate, computed in real-time
}
```

---

## 📈 Data Flow Comparison

### ❌ Current (Broken) Flow:
```
User Action                Database                    Display
───────────────────────────────────────────────────────────────
Create Product "Mango"
   └─────────────────> stock_quantity = 0  ──────────> ✅ Correct

Stock In: +100 kg
   └─────────────────> batches: +100 kg
                       stock_quantity = 0  ──────────> ❌ Wrong!
                       (not updated)

Sale: -50 kg
   └─────────────────> batch status = sold
                       batches: 50 kg left
                       stock_quantity = 0  ──────────> ❌ Wrong!
                       (still not updated)
```

### ✅ Fixed Flow:
```
User Action                Database                    Display
───────────────────────────────────────────────────────────────
Create Product "Mango"
   └─────────────────> stock_quantity = 0
                       batches: 0 kg      ──────────> ✅ Correct

Stock In: +100 kg
   └─────────────────> batches: +100 kg
                       (stock_quantity ignored)
                       Computed: 100 kg   ──────────> ✅ Correct!

Sale: -50 kg
   └─────────────────> batch status = sold
                       batches available: 50 kg
                       Computed: 50 kg    ──────────> ✅ Correct!
```

---

## 🎯 Why This Matters

### Impact on Key Features:

#### 1. Stock Status Badge
```php
Current: Uses stock_quantity  ❌ Shows wrong color
Fixed:   Uses total_stock     ✅ Always accurate
```

#### 2. Low Stock Alerts
```php
Current: Compares stock_quantity vs reorder_level  ❌ Triggers at wrong time
Fixed:   Compares total_stock vs reorder_level     ✅ Triggers correctly
```

#### 3. Summary Cards (Dashboard)
```php
Current: Counts products where stock_quantity <= 0  ❌ Wrong count
Fixed:   Counts products where total_stock <= 0     ✅ Correct count
```

#### 4. Filtering
```php
Current: WHERE stock_quantity <= reorder_level  ❌ Shows wrong products
Fixed:   WHERE SUM(batches) <= reorder_level    ✅ Shows correct products
```

---

## 💡 Simple Rule to Remember

```
┌────────────────────────────────────────────────────────┐
│  ALWAYS USE: product.total_stock (computed)            │
│  NEVER USE:  product.stock_quantity (cached/stale)     │
│                                                         │
│  Why? Because batches are the SOURCE OF TRUTH          │
└────────────────────────────────────────────────────────┘
```

---

## 🔧 Required Changes

### File 1: `app/Models/InventoryItem.php`
```php
// Lines 73-80: Change from stock_quantity to total_stock
- return $this->stock_quantity <= $this->reorder_level;
+ return $this->total_stock <= $this->reorder_level;

- return $this->stock_quantity <= 0;
+ return $this->total_stock <= 0;
```

### File 2: `app/Http/Controllers/ProductController.php`
```php
// Line 35-38: Update filter logic
// Use batch-based queries instead of stock_quantity column
```

### File 3: Future - Stock In Implementation
```php
// Add synchronization:
public function syncStockQuantity()
{
    $this->stock_quantity = $this->total_stock;
    $this->save();
}
```

---

## ✅ Benefits of Fix

1. **Accuracy** - Always shows real-time stock levels
2. **Simplicity** - One source of truth (batches)
3. **Safety** - No risk of data corruption
4. **Future-proof** - Works with Stock In/Out modules
5. **No migrations** - Uses existing database structure
6. **No UI changes** - Frontend gets accurate data automatically

---

## ⚠️ What Happens If Not Fixed

```
Time        Event                 Cached      Actual      Frontend Shows
──────────────────────────────────────────────────────────────────────────
Day 1       Product created          0 kg        0 kg     ✅ "Out of Stock"
Day 2       Stock In: 100 kg         0 kg      100 kg     ❌ "Out of Stock" 
Day 3       Sale: 50 kg              0 kg       50 kg     ❌ "Out of Stock"
Day 4       Sale: 40 kg              0 kg       10 kg     ❌ "Out of Stock"
Day 5       Sale: 10 kg              0 kg        0 kg     ✅ "Out of Stock"

Result: Always shows "Out of Stock" regardless of actual inventory! 🚨
```

---

**Conclusion:** The fix is critical and must be applied before Phase 2 (Frontend Integration).
