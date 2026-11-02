# Products API Reference

Quick reference guide for the Products module API endpoints.

---

## Base URL
```
http://127.0.0.1:8000/api
```

---

## Endpoints

### 1. Get All Products (with filters)

**GET** `/products`

**Query Parameters:**
- `search` (optional) - Search by product name
- `category` (optional) - Filter by category
- `status` (optional) - Filter by status: Available, Low Stock, Out of Stock

**Example Request:**
```bash
GET /api/products?search=mango&category=Tropical%20Fruit
```

**Success Response (200):**
```json
{
    "success": true,
    "products": [
        {
            "id": 1,
            "name": "Mango",
            "category": "Tropical Fruit",
            "unit": "kg",
            "price_per_unit": "120.00",
            "stock_quantity": "285.00",
            "reorder_level": "50.00",
            "freshness_score": 100,
            "spoilage_risk": "Low",
            "status": "active",
            "storage_notes": null,
            "remaining_shelf_life": 12,
            "created_at": "2026-08-29T10:30:00.000000Z",
            "updated_at": "2026-08-29T10:30:00.000000Z"
        }
    ],
    "summary": {
        "total_products": 7,
        "active_products": 6,
        "low_stock": 2,
        "out_of_stock": 0
    }
}
```

---

### 2. Create Product

**POST** `/products`

**Headers:**
```
Content-Type: application/json
X-CSRF-TOKEN: {token}
```

**Request Body:**
```json
{
    "name": "Mango",
    "category": "Tropical Fruit",
    "unit": "kg",
    "price_per_unit": 120.00,
    "reorder_level": 50,
    "storage_notes": "Store in cool, dry place"
}
```

**Required Fields:**
- `name` (string, max: 255)
- `category` (string, max: 255)
- `price_per_unit` (numeric, min: 0)

**Optional Fields:**
- `unit` (string, default: 'kg')
- `reorder_level` (numeric, default: 10)
- `storage_notes` (string)

**Success Response (201):**
```json
{
    "success": true,
    "message": "Product created successfully",
    "product": {
        "id": 8,
        "name": "Mango",
        "category": "Tropical Fruit",
        "unit": "kg",
        "price_per_unit": "120.00",
        "stock_quantity": "0.00",
        "reorder_level": "50.00",
        "freshness_score": 100,
        "spoilage_risk": "Low",
        "status": "active",
        "storage_notes": "Store in cool, dry place",
        "remaining_shelf_life": null,
        "created_at": "2026-08-29T11:45:00.000000Z",
        "updated_at": "2026-08-29T11:45:00.000000Z"
    }
}
```

**Validation Error (422):**
```json
{
    "success": false,
    "message": "Validation error",
    "errors": {
        "name": ["The name field is required."],
        "price_per_unit": ["The price per unit must be at least 0."]
    }
}
```

---

### 3. Get Single Product

**GET** `/products/{id}`

**Example Request:**
```bash
GET /api/products/1
```

**Success Response (200):**
```json
{
    "success": true,
    "product": {
        "id": 1,
        "name": "Mango",
        "category": "Tropical Fruit",
        "unit": "kg",
        "price_per_unit": "120.00",
        "stock_quantity": "285.00",
        "reorder_level": "50.00",
        "freshness_score": 92,
        "spoilage_risk": "Low",
        "status": "active",
        "storage_notes": null,
        "remaining_shelf_life": 12,
        "stock_status": "Available",
        "stock_status_badge": "badge-green",
        "batches": [
            {
                "id": 1,
                "inventory_item_id": 1,
                "batch_code": "MNG-001",
                "quantity": "285.00",
                "price_per_unit": "120.00",
                "received_date": "2026-06-16",
                "expiry_date": "2026-06-30",
                "supplier": "Davao Fresh Farms",
                "status": "available",
                "created_at": "2026-06-16T08:00:00.000000Z",
                "updated_at": "2026-06-16T08:00:00.000000Z"
            }
        ],
        "total_batches": 1,
        "created_at": "2026-06-15T10:00:00.000000Z",
        "updated_at": "2026-08-29T10:30:00.000000Z"
    }
}
```

**Not Found (404):**
```json
{
    "success": false,
    "message": "Product not found"
}
```

---

### 4. Update Product

**PUT** `/products/{id}`

**Headers:**
```
Content-Type: application/json
X-CSRF-TOKEN: {token}
```

**Request Body (Partial Update Supported):**
```json
{
    "name": "Philippine Mango",
    "price_per_unit": 125.00,
    "reorder_level": 60
}
```

**Updatable Fields:**
- `name` (string, max: 255)
- `category` (string, max: 255)
- `unit` (string, max: 255)
- `price_per_unit` (numeric, min: 0)
- `reorder_level` (numeric, min: 0)
- `storage_notes` (string)
- `status` (active/inactive)

**Success Response (200):**
```json
{
    "success": true,
    "message": "Product updated successfully",
    "product": {
        "id": 1,
        "name": "Philippine Mango",
        "category": "Tropical Fruit",
        "unit": "kg",
        "price_per_unit": "125.00",
        "stock_quantity": "285.00",
        "reorder_level": "60.00",
        "status": "active",
        "updated_at": "2026-08-29T12:00:00.000000Z"
    }
}
```

---

### 5. Delete Product

**DELETE** `/products/{id}`

**Headers:**
```
X-CSRF-TOKEN: {token}
```

**Success Response - Deleted (200):**
```json
{
    "success": true,
    "message": "Product deleted successfully"
}
```

**Success Response - Marked Inactive (200):**
```json
{
    "success": true,
    "message": "Product has sales history and has been marked as inactive instead of deleted",
    "product": {
        "id": 1,
        "name": "Mango",
        "status": "inactive",
        "updated_at": "2026-08-29T12:15:00.000000Z"
    }
}
```

**Error - Has Batches (400):**
```json
{
    "success": false,
    "message": "Cannot delete product with existing batches. Please remove all batches first."
}
```

---

### 6. Get Categories

**GET** `/products/categories/list`

**Success Response (200):**
```json
{
    "success": true,
    "categories": [
        "Citrus",
        "Seasonal",
        "Tropical Fruit"
    ]
}
```

---

## Error Responses

### Validation Error (422)
```json
{
    "success": false,
    "message": "Validation error",
    "errors": {
        "field_name": ["Error message here"]
    }
}
```

### Not Found (404)
```json
{
    "success": false,
    "message": "Product not found"
}
```

### Server Error (500)
```json
{
    "success": false,
    "message": "Error creating product",
    "error": "Detailed error message"
}
```

---

## CSRF Token

For POST, PUT, and DELETE requests, include CSRF token:

**In HTML:**
```html
<meta name="csrf-token" content="{{ csrf_token() }}">
```

**In JavaScript:**
```javascript
const token = document.querySelector('meta[name="csrf-token"]').content;

fetch('/api/products', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': token
    },
    body: JSON.stringify(data)
});
```

---

## Product Status Values

- `Available` - In stock, above reorder level
- `Low Stock` - In stock but at or below reorder level
- `Out of Stock` - No stock available (quantity = 0)

## Product Status Field Values

- `active` - Product is currently being used
- `inactive` - Product is no longer used but kept for history

---

## Testing with cURL

**Create Product:**
```bash
curl -X POST http://127.0.0.1:8000/api/products \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: your-token-here" \
  -d '{
    "name": "Mango",
    "category": "Tropical Fruit",
    "price_per_unit": 120.00,
    "reorder_level": 50
  }'
```

**Get All Products:**
```bash
curl http://127.0.0.1:8000/api/products
```

**Update Product:**
```bash
curl -X PUT http://127.0.0.1:8000/api/products/1 \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: your-token-here" \
  -d '{
    "name": "Philippine Mango",
    "price_per_unit": 125.00
  }'
```

**Delete Product:**
```bash
curl -X DELETE http://127.0.0.1:8000/api/products/1 \
  -H "X-CSRF-TOKEN: your-token-here"
```

---

## Notes

1. All decimal values are returned as strings with 2 decimal places
2. Dates are in ISO 8601 format
3. All endpoints support JSON responses
4. Products with sales history cannot be permanently deleted
5. Products with batches cannot be deleted until batches are removed
