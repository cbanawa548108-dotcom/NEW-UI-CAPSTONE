# Products Backend Audit Report

**Date:** August 29, 2026  
**Auditor:** System Analysis  
**Focus:** Data Consistency in Batch-Based Inventory System  

---

## 🔍 Executive Summary

**Critical Issues Found:** 3  
**Recommendations:** 5  
**Risk Level:** ⚠️ **MEDIUM-HIGH** - Data inconsistency will occur without fixes

---

## ❌ Critical Issues Identified

### **Issue #1: Conflicting Stock Sources (CRITICAL)**

**Problem:**  
The system has **TWO competing sources of truth** for stock quantity:

1. **`inventory_items.stock_quantity`** (column in database)
2. **`sum(inventory_batches.quantity)`** (computed from batches)

**Current Implementation:**
```php
// InventoryItem Model - Line 66
public function getTotalStockAttribute()
{
    return $this->availableBatches()->sum('quantity');
}

// But status checks use database column - Lines 73, 80
public function getIsLowStockAttribute()
{
    return $this->stock_quantity <= $this->reorder_level;  // ❌ Uses DB column
}

public function getIsOutOfStockAttribute()
{
    return $this->stock_quantity <= 0;  // ❌ Uses DB column
}
```

**Scenario Where This Breaks:**
```
Product: Mango
- inventory_items.stock_quantity = 100 kg (stale data)
- Batch 1: 50 kg (available)
- Batch 2: 30 kg (sold - status changed)
- Batch 3: 20 kg (expired - status changed)

Actual available stock: 50 kg
Database column says: 100 kg
System shows: "Available" ✅ (should be "Low Stock" ⚠️)
```

**Impact:**
- Stock status will be **incorrect** after sales or batch status changes
- Low stock alerts won't trigger when they should
- Out of stock detection will fail
- Summary statistics will be wrong

---

### **Issue #2: No Synchronization Mechanism (CRITICAL)**

**Problem:**  
`inventory_items.stock_quantity` is never updated when:
- Stock In adds new batches ❌
- Sales reduce batch quantities ❌
- Batches expire and status changes ❌
- Batches are deleted ❌

**Current Code:**
```php
// ProductController - store() method
$validated['stock_quantity'] = 0; // ✅ Set to 0 on creation

// But no mechanism to update this when batches change ❌
```

**Missing Logic:**
- No observer/event listener on `InventoryBatch` model
- No method to recalculate total stock
- No automatic sync when batch operations occur

---

### **Issue #3: Ambiguous Attribute Names (MEDIUM)**

**Problem:**  
The model has TWO attributes that sound the same:

1. `$product->total_stock` (computed from batches) ✅
2. `$product->stock_quantity` (database column) ❌

**This creates confusion:**
```php
// Which one should frontend use?
$product->stock_quantity  // Database column (can be stale)
$product->total_stock     // Computed (always accurate)

// Status uses database column (inconsistent):
$product->is_low_stock    // Uses stock_quantity ❌
$product->stock_status    // Uses is_low_stock ❌
```

---

## 📊 Design Architecture Analysis

### **What the Database Schema Suggests:**

Looking at `capstone_db_structure.sql`:
```sql
CREATE TABLE `inventory_items` (
  ...
  `stock_quantity` decimal(10,2) NOT NULL DEFAULT 0.00,  -- Denormalized cache
  ...
);

CREATE TABLE `inventory_batches` (
  ...
  `quantity` decimal(10,2) NOT NULL DEFAULT 0.00,  -- Source of truth
  ...
);
```

**Interpretation:**
- `inventory_batches.quantity` = **Source of Truth** (per batch)
- `inventory_items.stock_quantity` = **Denormalized Cache** (for performance)

**This is a valid design IF:**
✅ Cache is updated every time batches change  
❌ **Currently NOT implemented**

---

## ✅ Correct Separation of Concerns

**Product Master Data (Products Module):**
```php
✅ name
✅ category
✅ unit
✅ price_per_unit (default/average price for UI)
✅ reorder_level
✅ status (active/inactive)
✅ storage_notes
❌ stock_quantity (should be computed, not editable)
❌ remaining_shelf_life (should be computed from batches)
```

**Batch Data (Stock In Module - Future):**
```php
✅ batch_code (auto-generated)
✅ quantity (per batch)
✅ price_per_unit (per batch, can differ from product default)
✅ received_date
✅ expiry_date
✅ supplier
✅ status
```

**Current Implementation Status:**
- ✅ Product creation does NOT require batch data
- ✅ `stock_quantity` defaults to 0 on new products
- ⚠️ But no mechanism to update it when Stock In is implemented

---

## 🛠️ Proposed Solutions

### **Option 1: Use Computed Values Only (Recommended)**

**Change all stock checks to use computed `total_stock`:**

```php
// InventoryItem Model - PROPOSED CHANGES

public function getIsLowStockAttribute()
{
    return $this->total_stock <= $this->reorder_level;  // ✅ Use computed
}

public function getIsOutOfStockAttribute()
{
    return $this->total_stock <= 0;  // ✅ Use computed
}

// Add method to sync cache when needed (for performance)
public function syncStockQuantity()
{
    $this->stock_quantity = $this->total_stock;
    $this->save();
}
```

**Pros:**
- ✅ Always accurate (single source of truth)
- ✅ No sync issues
- ✅ Works immediately
- ✅ No database structure changes

**Cons:**
- ⚠️ Slightly slower (requires join query)
- ⚠️ Database column becomes redundant (but harmless)

---

### **Option 2: Keep Cache, Add Sync Logic (Performance-Optimized)**

**Add automatic synchronization using Model Events:**

```php
// InventoryBatch Model - PROPOSED ADDITION

protected static function booted()
{
    // When batch is created
    static::created(function ($batch) {
        $batch->inventoryItem->syncStockQuantity();
    });

    // When batch quantity is updated
    static::updated(function ($batch) {
        if ($batch->isDirty('quantity') || $batch->isDirty('status')) {
            $batch->inventoryItem->syncStockQuantity();
        }
    });

    // When batch is deleted
    static::deleted(function ($batch) {
        $batch->inventoryItem->syncStockQuantity();
    });
}

// InventoryItem Model - PROPOSED ADDITION

public function syncStockQuantity()
{
    $this->stock_quantity = $this->availableBatches()->sum('quantity');
    $this->saveQuietly(); // Don't trigger events
}
```

**Pros:**
- ✅ Fast queries (uses indexed column)
- ✅ Always synchronized automatically
- ✅ Best for large datasets

**Cons:**
- ⚠️ More complex code
- ⚠️ Requires careful event management
- ⚠️ Must implement when Stock In is added

---

### **Option 3: Hybrid Approach (Best of Both)**

**Use computed for accuracy, keep cache for performance:**

```php
// For real-time accuracy (Product details, critical operations)
$product->total_stock              // Computed from batches

// For fast queries (Listings, dashboards)
$product->stock_quantity           // Cached value (synced periodically)

// Sync on demand
$product->syncStockQuantity();     // Call when needed
```

---

## 📋 Recommended Changes (Safest Fix)

### **Immediate Fix (No Breaking Changes):**

**1. Update Model Methods to Use Computed Stock:**
```php
// File: app/Models/InventoryItem.php

// Change lines 73-80 from:
public function getIsLowStockAttribute()
{
    return $this->stock_quantity <= $this->reorder_level;  // ❌ OLD
}

public function getIsOutOfStockAttribute()
{
    return $this->stock_quantity <= 0;  // ❌ OLD
}

// To:
public function getIsLowStockAttribute()
{
    return $this->total_stock <= $this->reorder_level;  // ✅ NEW
}

public function getIsOutOfStockAttribute()
{
    return $this->total_stock <= 0;  // ✅ NEW
}
```

**2. Update Controller to Use Computed Stock:**
```php
// File: app/Http/Controllers/ProductController.php

// In index() method, change query scopes:
// From:
$query->whereRaw('stock_quantity <= reorder_level')

// To:
$query->whereHas('availableBatches', function($q) {
    // Use subquery to compare computed total
})

// Or simpler: Load all and filter in PHP for Products (small dataset)
```

**3. Add Helper Method for Future Use:**
```php
// File: app/Models/InventoryItem.php

/**
 * Synchronize cached stock_quantity with actual batch totals.
 * Call this when batches are added/removed/updated.
 */
public function syncStockQuantity()
{
    $this->stock_quantity = $this->availableBatches()->sum('quantity');
    $this->saveQuietly();
    return $this;
}
```

**4. Update API Response:**
```php
// File: app/Http/Controllers/ProductController.php

// In show() method, add clarifying field names:
$productDetails = [
    ...
    'stock_quantity_cached' => $product->stock_quantity,     // From DB
    'stock_quantity_actual' => $product->total_stock,        // Computed
    'stock_quantity' => $product->total_stock,               // Use computed for frontend
    ...
];
```

---

## 🚨 Impact Analysis

### **If NOT Fixed:**

**Scenario 1: After Stock In Transaction**
```
1. Product "Mango" created with stock_quantity = 0
2. Stock In adds batch: 100 kg
3. inventory_batches has 100 kg ✅
4. inventory_items.stock_quantity = 0 ❌ (not updated)
5. Product page shows "Out of Stock" (WRONG!)
```

**Scenario 2: After Sales**
```
1. Product has stock_quantity = 100 kg
2. Sale reduces batch quantity to 50 kg
3. inventory_batches has 50 kg ✅
4. inventory_items.stock_quantity = 100 kg ❌ (stale)
5. System shows "Available" when it should be "Low Stock"
```

**Scenario 3: Multiple Batches**
```
Product: Mango
- Batch 1: 50 kg (available)
- Batch 2: 30 kg (available)
- Batch 3: 20 kg (expired, status changed)

Correct total: 80 kg
Database column: 100 kg
Status shown: Based on 100 kg (WRONG!)
```

---

## 🎯 Recommendations

### **Priority 1: MUST FIX BEFORE PHASE 2**
1. ✅ Change `is_low_stock` and `is_out_of_stock` to use `total_stock`
2. ✅ Update controller scopes to use batch-based queries
3. ✅ Document that `stock_quantity` is a cache (not source of truth)

### **Priority 2: ADD WITH STOCK IN MODULE**
4. ✅ Implement `syncStockQuantity()` method
5. ✅ Add model observers on `InventoryBatch` to auto-sync

### **Priority 3: FUTURE OPTIMIZATION**
6. ✅ Add database index on `inventory_batches.status`
7. ✅ Consider scheduled job to sync all products daily

---

## 📝 Summary

**Current State:**
- ❌ Products Module uses stale database column for stock checks
- ❌ Batch totals and product stock_quantity will diverge
- ❌ No synchronization mechanism exists

**Proposed Fix:**
- ✅ Use computed `total_stock` attribute (from batches) for all checks
- ✅ Keep `stock_quantity` column as optional cache
- ✅ Add sync method for when Stock In is implemented
- ✅ No database changes needed
- ✅ No UI changes needed

**Risk Mitigation:**
- Zero risk to existing data
- No breaking changes to API
- Frontend will receive accurate data
- Ready for Stock In implementation

---

## 🚀 Next Steps

**Before Phase 2 (Frontend Integration):**
1. Review and approve proposed changes
2. Apply fixes to Model and Controller
3. Test with sample data
4. Proceed with frontend integration

**When Stock In is Implemented:**
1. Add model observers to InventoryBatch
2. Call `syncStockQuantity()` after batch operations
3. Ensure frontend uses correct field

---

**Recommendation:** Apply **Option 1 (Use Computed Values)** immediately for safety and simplicity.
