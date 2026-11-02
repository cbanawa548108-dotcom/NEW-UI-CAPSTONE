# FreshTrack API Endpoints Reference

Quick reference guide for all available API endpoints.

## Authentication Required
All API endpoints require authentication. Include session cookies or authentication token.

---

## Inventory Module
**Access:** Owner + Manager

### List Inventory
```
GET /api/inventory
Returns: All inventory items with batches, expiry dates, freshness scores
```

### Stock In
```
POST /api/inventory/stock-in
Body: {
  items: [{
    product_id: number,
    quantity: number,
    price_per_unit: number,
    batch_code: string,
    expiry_date: date,
    received_date: date,
    supplier: string
  }]
}
Returns: { success: boolean, message: string }
```

### Stock Out
```
POST /api/inventory/stock-out
Body: {
  items: [{
    product_id: number,
    batch_id: number,
    quantity: number
  }],
  type: 'sale' | 'waste' | 'damage',
  reason: string
}
Returns: { success: boolean, message: string }
```

### Stock Adjustment
```
POST /api/inventory/stock-adjustment
Body: {
  items: [{
    product_id: number,
    batch_id: number,
    quantity: number (can be negative)
  }],
  type: 'add' | 'subtract',
  reason: string
}
Returns: { success: boolean, message: string }
```

### Inventory Statistics
```
GET /api/inventory/stats
Returns: {
  total_items: number,
  total_batches: number,
  total_value: number,
  low_stock_items: number,
  expiring_items: number
}
```

### Low Stock Items
```
GET /api/inventory/low-stock
Returns: Array of items below reorder level
```

### Expiring Items
```
GET /api/inventory/expiring?days=7
Returns: Array of items expiring within specified days
```

---

## Sales Module
**Access:** Owner + Manager

### List Sales
```
GET /api/sales?page=1
Returns: Paginated sales transactions with items
```

### Sales Statistics
```
GET /api/sales/stats?period=today
Params: period = today | week | month | year
Returns: {
  total_sales: number,
  total_transactions: number,
  average_transaction: number,
  top_products: array
}
```

### Daily Sales
```
GET /api/sales/daily?days=7
Returns: Array of daily sales data for charts
```

---

## POS Module
**Access:** All Users (Owner + Manager + Cashier)

### Get Available Products
```
GET /api/pos/products
Returns: Array of products available for sale with stock
```

### Create Sale
```
POST /api/pos/sale
Body: {
  items: [{
    product_id: number,
    batch_id: number,
    quantity: number,
    price: number
  }],
  payment_method: 'cash' | 'card' | 'gcash' | 'bank_transfer',
  discount_amount?: number,
  notes?: string
}
Returns: {
  success: boolean,
  message: string,
  data: {
    transaction_code: string,
    total_amount: number,
    transaction_id: number
  }
}
```

---

## Dashboard Module
**Access:** Owner Only

### Dashboard Metrics
```
GET /api/dashboard/metrics
Returns: {
  today_sales: number,
  week_sales: number,
  month_sales: number,
  week_growth: number,
  inventory_value: number,
  active_products: number,
  total_batches: number,
  low_stock_count: number,
  expiring_count: number,
  out_of_stock_count: number,
  today_transactions: number,
  top_products: array,
  recent_sales: array,
  expiring_items: array,
  low_stock_items: array,
  sales_chart: object
}
```

### Sales Trend
```
GET /api/dashboard/sales-trend?days=30
Returns: Array of sales data for trend charts
```

### Inventory Health
```
GET /api/dashboard/inventory-health
Returns: {
  total_products: number,
  available: number,
  low_stock: number,
  out_of_stock: number,
  health_percentage: number
}
```

---

## Reports Module
**Access:** Owner + Manager

### Sales Report
```
GET /api/reports/sales
Params: {
  start_date: date (required),
  end_date: date (required),
  category?: string,
  product_id?: number
}
Returns: Detailed sales report with breakdown
```

### Inventory Report
```
GET /api/reports/inventory
Params: {
  category?: string,
  status?: 'available' | 'low_stock' | 'out_of_stock'
}
Returns: Current inventory status report
```

### Expiry Report
```
GET /api/reports/expiry
Params: {
  days?: number (default 7)
}
Returns: Report of expiring/expired items
```

### Profit/Loss Report
```
GET /api/reports/profit-loss
Params: {
  start_date: date (required),
  end_date: date (required)
}
Returns: Profit and loss analysis
```

---

## Notifications Module
**Access:** All Users

### Get Notifications
```
GET /api/notifications
Returns: Array of auto-generated notifications sorted by priority
```

### Generate Notifications
```
POST /api/notifications/generate
Returns: Array of newly generated notifications
```

### Notification Count
```
GET /api/notifications/count
Returns: {
  total: number,
  critical: number,
  warning: number,
  info: number
}
```

### Unread Count
```
GET /api/notifications/unread-count
Returns: {
  count: number,
  has_critical: boolean
}
```

---

## Products Module (Legacy)
**Access:** Owner + Manager

### List Products
```
GET /api/products
Returns: Array of all products
```

### Create Product
```
POST /api/products
Body: {
  name: string,
  category: string,
  unit: string,
  description?: string,
  sku_code: string,
  shelf_life: number
}
```

### Get Product
```
GET /api/products/{id}
Returns: Single product details
```

### Update Product
```
PUT /api/products/{id}
Body: Product fields to update
```

### Delete Product
```
DELETE /api/products/{id}
Returns: { success: boolean, message: string }
```

### Get Categories
```
GET /api/products/categories/list
Returns: Array of unique categories
```

---

## Error Responses

All endpoints follow this error format:

```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    "field_name": ["Validation error message"]
  }
}
```

## HTTP Status Codes
- `200` - Success
- `201` - Created
- `400` - Bad Request / Validation Error
- `401` - Unauthorized
- `403` - Forbidden (insufficient permissions)
- `404` - Not Found
- `500` - Internal Server Error

---

## AJAX Example (Vanilla JavaScript)

```javascript
// GET request
fetch('/api/inventory/stats', {
  method: 'GET',
  headers: {
    'Content-Type': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
  }
})
.then(response => response.json())
.then(data => console.log(data))
.catch(error => console.error('Error:', error));

// POST request
fetch('/api/pos/sale', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
  },
  body: JSON.stringify({
    items: [{
      product_id: 1,
      batch_id: 1,
      quantity: 5,
      price: 120
    }],
    payment_method: 'cash'
  })
})
.then(response => response.json())
.then(data => {
  if (data.success) {
    console.log('Sale completed:', data.data.transaction_code);
  } else {
    console.error('Sale failed:', data.message);
  }
})
.catch(error => console.error('Error:', error));
```

## jQuery Example

```javascript
// GET request
$.ajax({
  url: '/api/inventory/stats',
  type: 'GET',
  success: function(data) {
    console.log(data);
  },
  error: function(xhr) {
    console.error('Error:', xhr.responseJSON);
  }
});

// POST request
$.ajax({
  url: '/api/pos/sale',
  type: 'POST',
  data: JSON.stringify({
    items: [{
      product_id: 1,
      batch_id: 1,
      quantity: 5,
      price: 120
    }],
    payment_method: 'cash'
  }),
  contentType: 'application/json',
  success: function(data) {
    console.log('Sale completed:', data.data.transaction_code);
  },
  error: function(xhr) {
    console.error('Error:', xhr.responseJSON);
  }
});
```

---

**Note:** All POST/PUT/DELETE requests require CSRF token in headers.
Include `<meta name="csrf-token" content="{{ csrf_token() }}">` in your blade template head section.
