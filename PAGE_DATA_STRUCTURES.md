# FreshTrack Page Data Structures

Documentation of data passed to each blade view.

---

## Inventory Page (`resources/views/pages/inventory.blade.php`)
**Route:** `/inventory`
**Controller:** `InventoryController@page`

### Data Passed to View:

```php
[
    'groupedItems' => [
        'Mango' => [
            [
                0 => 1,                        // item_id
                1 => 'MNG-001',                // batch_code
                2 => '285 kg',                 // quantity (formatted)
                3 => 'Jun 26, 2026',           // expiry_date (formatted)
                4 => 'Davao Fresh Farms',      // supplier
                5 => '₱120.00/kg',             // price (formatted)
                6 => 'Available',              // status
                7 => 'badge-green',            // badge_class
                8 => 92,                       // freshness_percentage
                9 => 'Jun 16, 2026',           // received_date (formatted)
                10 => 12,                      // remaining_shelf_life (days)
                11 => 'Old Stock'              // stock_age
            ],
            // ... more batches
        ],
        'Durian' => [...],
        // ... more fruits
    ],
    
    'products' => [
        [
            0 => 'Mango',                  // name
            1 => '640 kg',                 // total_stock (formatted)
            2 => 3,                        // batch_count
            3 => 'Jun 24, 2026',           // nearest_expiry (formatted)
            4 => '₱119.33/kg',             // avg_price (formatted)
            5 => '10 days'                 // shelf_life (formatted)
        ],
        // ... more products
    ]
]
```

### Usage in Blade:
```blade
@foreach($groupedItems as $fruit => $batches)
    <h3>{{ $fruit }}</h3>
    @foreach($batches as $batch)
        <div>
            Batch: {{ $batch[1] }}
            Qty: {{ $batch[2] }}
            Expires: {{ $batch[3] }}
            Status: <span class="{{ $batch[7] }}">{{ $batch[6] }}</span>
        </div>
    @endforeach
@endforeach
```

---

## Sales Page (`resources/views/pages/sales.blade.php`)
**Route:** `/sales`
**Controller:** `SalesController@page`

### Data Passed to View:

```php
[
    'sales' => LengthAwarePaginator {
        // Paginated collection of sales transactions
        items: [
            SalesTransaction {
                id: 1,
                user_id: 1,
                transaction_code: 'TXN-20261002-0001',
                subtotal: 600.00,
                tax_amount: 0.00,
                discount_amount: 0.00,
                total_amount: 600.00,
                payment_method: 'cash',
                status: 'completed',
                notes: null,
                created_at: Carbon,
                updated_at: Carbon,
                
                // Relationships
                user: User {
                    name: 'Owner Account',
                    email: 'owner@FreshTrack.ph'
                },
                
                salesItems: Collection [
                    SalesItem {
                        id: 1,
                        inventory_item_id: 1,
                        inventory_batch_id: 1,
                        quantity: 5.00,
                        unit_price: 120.00,
                        total_amount: 600.00,
                        
                        inventoryItem: InventoryItem {
                            name: 'Mango',
                            category: 'Tropical Fruit',
                            unit: 'kg'
                        }
                    }
                ]
            }
        ],
        perPage: 20,
        currentPage: 1,
        total: 45
    }
]
```

### Usage in Blade:
```blade
@foreach($sales as $sale)
    <div>
        <p>{{ $sale->transaction_code }}</p>
        <p>₱{{ number_format($sale->total_amount, 2) }}</p>
        <p>{{ $sale->created_at->format('M d, Y h:i A') }}</p>
        <p>By: {{ $sale->user->name }}</p>
        
        <ul>
            @foreach($sale->salesItems as $item)
                <li>
                    {{ $item->inventoryItem->name }} - 
                    {{ $item->quantity }} {{ $item->inventoryItem->unit }} × 
                    ₱{{ number_format($item->unit_price, 2) }} = 
                    ₱{{ number_format($item->total_amount, 2) }}
                </li>
            @endforeach
        </ul>
    </div>
@endforeach

{{ $sales->links() }}
```

---

## POS Page (`resources/views/pages/pos.blade.php`)
**Route:** `/pos`
**Controller:** `SalesController@pos`

### Data Passed to View:

```php
[
    'products' => Collection [
        InventoryItem {
            id: 1,
            name: 'Mango',
            category: 'Tropical Fruit',
            unit: 'kg',
            price_per_unit: 120.00,
            stock_quantity: 640.00,
            status: 'active',
            
            batches: Collection [
                InventoryBatch {
                    id: 1,
                    batch_code: 'MNG-001',
                    quantity: 285.00,
                    price_per_unit: 120.00,
                    expiry_date: Carbon('2026-06-26'),
                    supplier: 'Davao Fresh Farms',
                    status: 'available'
                },
                InventoryBatch {
                    id: 2,
                    batch_code: 'MNG-002',
                    quantity: 155.00,
                    price_per_unit: 118.00,
                    expiry_date: Carbon('2026-06-24'),
                    supplier: 'Mt. Apo Growers',
                    status: 'available'
                }
            ]
        }
    ]
]
```

### Usage in Blade:
```blade
@foreach($products as $product)
    <div class="product-card" 
         data-id="{{ $product->id }}"
         data-name="{{ $product->name }}"
         data-price="{{ $product->price_per_unit }}"
         data-stock="{{ $product->stock_quantity }}"
         data-batch="{{ $product->batches->first()->id }}">
        
        <h3>{{ $product->name }}</h3>
        <p>₱{{ number_format($product->price_per_unit, 2) }}/{{ $product->unit }}</p>
        <p>Available: {{ $product->stock_quantity }} {{ $product->unit }}</p>
        
        @if($product->batches->count() > 1)
            <small>{{ $product->batches->count() }} batches available</small>
        @endif
    </div>
@endforeach
```

---

## Dashboard Page (`resources/views/pages/dashboard.blade.php`)
**Route:** `/dashboard`
**Controller:** `DashboardController@index`

### Data Passed to View:

```php
[
    'today_sales' => 12500.00,
    'week_sales' => 85000.00,
    'month_sales' => 340000.00,
    'week_growth' => 12.5,                    // percentage
    'inventory_value' => 250000.00,
    'active_products' => 10,
    'total_batches' => 25,
    'low_stock_count' => 3,
    'expiring_count' => 5,
    'out_of_stock_count' => 1,
    'today_transactions' => 45,
    
    'top_products' => [
        [
            'name' => 'Mango',
            'quantity' => 250
        ],
        [
            'name' => 'Banana',
            'quantity' => 180
        ]
    ],
    
    'recent_sales' => [
        [
            'transaction_code' => 'TXN-20261002-0045',
            'total_amount' => 600.00,
            'items_count' => 3,
            'created_at' => 'Oct 2, 2026 2:30 PM',
            'user_name' => 'Owner Account'
        ]
    ],
    
    'expiring_items' => [
        [
            'product_name' => 'Durian',
            'batch_code' => 'DUR-112',
            'quantity' => 45.00,
            'expiry_date' => 'Oct 5, 2026',
            'days_remaining' => 3
        ]
    ],
    
    'low_stock_items' => [
        [
            'name' => 'Pomelo',
            'current_stock' => 8.00,
            'reorder_level' => 50.00,
            'deficit' => 42.00
        ]
    ],
    
    'sales_chart' => [
        '2026-09-26' => 12000.00,
        '2026-09-27' => 11500.00,
        '2026-09-28' => 13000.00,
        '2026-09-29' => 12800.00,
        '2026-09-30' => 11200.00,
        '2026-10-01' => 12100.00,
        '2026-10-02' => 12500.00
    ]
]
```

### Usage in Blade:
```blade
<!-- Metrics Cards -->
<div class="metric-card">
    <h3>₱{{ number_format($today_sales, 2) }}</h3>
    <p>Today's Sales</p>
</div>

<div class="metric-card">
    <h3>₱{{ number_format($week_sales, 2) }}</h3>
    <p>This Week</p>
    <span class="badge-green">+{{ $week_growth }}%</span>
</div>

<!-- Top Products -->
@foreach($top_products as $product)
    <li>{{ $product['name'] }} - {{ $product['quantity'] }} sold</li>
@endforeach

<!-- Chart Data (for JavaScript) -->
<script>
const salesData = @json($sales_chart);
const dates = Object.keys(salesData);
const amounts = Object.values(salesData);
// Use with Chart.js or similar
</script>

<!-- Expiring Items Alert -->
@foreach($expiring_items as $item)
    <div class="alert alert-warning">
        {{ $item['product_name'] }} ({{ $item['batch_code'] }}) 
        expires in {{ $item['days_remaining'] }} days
    </div>
@endforeach
```

---

## Reports Page (`resources/views/pages/reports.blade.php`)
**Route:** `/reports`
**Controller:** `ReportsController@page`

### Data Passed to View:
```php
// No data passed initially - uses API endpoints for dynamic reports
[]
```

The reports page typically loads blank and uses AJAX to fetch report data based on user-selected parameters.

### API Usage Example:
```javascript
// Generate Sales Report
fetch('/api/reports/sales?start_date=2026-09-01&end_date=2026-09-30')
    .then(res => res.json())
    .then(data => {
        // data structure:
        {
            period: { start: '2026-09-01', end: '2026-09-30' },
            summary: {
                total_sales: 340000.00,
                total_transactions: 450,
                total_items_sold: 2500,
                average_transaction: 755.56
            },
            by_product: [
                {
                    product_name: 'Mango',
                    quantity_sold: 850,
                    total_sales: 102000.00,
                    average_price: 120.00
                }
            ],
            by_category: [...],
            daily_breakdown: [...]
        }
    });
```

---

## Notifications Page (`resources/views/pages/notifications.blade.php`)
**Route:** `/notifications`
**Controller:** `NotificationsController@page`

### Data Passed to View:

```php
[
    'notifications' => [
        [
            'id' => 'expired-5',
            'type' => 'critical',           // critical, warning, info, success
            'icon' => 'M6 18L18 6M6 6l12 12', // SVG path
            'title' => 'Expired Product',
            'message' => 'Durian (Batch: DUR-112) has expired. Quantity: 45 kg. Remove from inventory.',
            'time' => 'Expired',
            'action_text' => 'Remove Stock',
            'action_url' => '/inventory'
        ],
        [
            'id' => 'expiring-3',
            'type' => 'critical',
            'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
            'title' => 'Product Expiring Soon',
            'message' => 'Pomelo (Batch: POM-034) expires in 2 day(s). Quantity: 8 kg',
            'time' => '2 days',
            'action_text' => 'View Details',
            'action_url' => '/inventory'
        ],
        [
            'id' => 'out-of-stock-2',
            'type' => 'critical',
            'icon' => 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
            'title' => 'Out of Stock',
            'message' => 'Durian is completely out of stock. Immediate restocking required.',
            'time' => 'Just now',
            'action_text' => 'Add Stock',
            'action_url' => '/inventory'
        ],
        [
            'id' => 'low-stock-1',
            'type' => 'warning',
            'icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
            'title' => 'Low Stock Alert',
            'message' => 'Pomelo is running low on stock. Current: 8 kg, Reorder level: 50 kg',
            'time' => 'Just now',
            'action_text' => 'Restock Now',
            'action_url' => '/inventory'
        ]
    ]
]
```

### Usage in Blade:
```blade
@foreach($notifications as $notification)
    <div class="notification notification-{{ $notification['type'] }}">
        <div class="icon">
            <svg viewBox="0 0 24 24">
                <path d="{{ $notification['icon'] }}" />
            </svg>
        </div>
        
        <div class="content">
            <h4>{{ $notification['title'] }}</h4>
            <p>{{ $notification['message'] }}</p>
            <span class="time">{{ $notification['time'] }}</span>
        </div>
        
        <a href="{{ $notification['action_url'] }}" class="btn">
            {{ $notification['action_text'] }}
        </a>
    </div>
@endforeach
```

---

## Data Type Legend

### Formatting Functions
- **number_format($value, 2)** - Format currency: 120.00
- **Carbon::format('M d, Y')** - Format date: Oct 2, 2026
- **Carbon::format('M d, Y h:i A')** - Format datetime: Oct 2, 2026 2:30 PM

### Badge Classes
- `badge-green` - Available, Good status
- `badge-amber` - Low Stock, Warning
- `badge-red` - Critical, Expiring Soon
- `badge-gray` - Out of Stock, Depleted

### Status Values
- **Inventory:** Available, Low Stock, Critical, Out of Stock
- **Sales:** pending, completed, cancelled
- **Batch:** available, sold, expired, damaged, depleted
- **Notification:** critical, warning, info, success

---

## Helper Methods Available in Blade

### InventoryItem Model
```blade
{{ $item->is_low_stock }}        <!-- boolean -->
{{ $item->is_out_of_stock }}     <!-- boolean -->
{{ $item->stock_status }}         <!-- string -->
{{ $item->stock_status_badge }}   <!-- CSS class -->
{{ $item->total_stock }}          <!-- float -->
```

### InventoryBatch Model
```blade
{{ $batch->remaining_shelf_life }} <!-- int (days) -->
{{ $batch->is_expired }}           <!-- boolean -->
{{ $batch->is_expiring_soon }}     <!-- boolean -->
{{ $batch->total_value }}          <!-- float -->
```

### SalesTransaction Model
```blade
{{ $sale->total_items }}     <!-- int -->
{{ $sale->total_quantity }}  <!-- float -->
```

---

**Note:** All monetary values should be formatted with `number_format($value, 2)` before display.
All dates are Carbon instances and can be formatted with `->format()` method.
