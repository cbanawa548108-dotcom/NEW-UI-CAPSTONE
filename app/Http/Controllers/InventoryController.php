<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\InventoryBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InventoryController extends Controller
{
    /**
     * Display the inventory page with data.
     */
    public function page()
    {
        // Get all inventory items with their batches (only available and quantity > 0)
        $inventoryItems = InventoryItem::with(['batches' => function($query) {
            $query->where('status', 'available')->where('quantity', '>', 0)->orderBy('expiry_date', 'asc');
        }])->get();

        // Group batches by fruit name for the overview cards
        $groupedItems = [];
        foreach ($inventoryItems as $item) {
            $groupedItems[$item->name] = [];
            
            foreach ($item->batches as $batch) {
                $remainingShelfLife = $batch->remaining_shelf_life ?? 0;
                
                // Determine status based on stock and shelf life
                $status = 'Available';
                $badgeClass = 'badge-green';
                
                if ($batch->quantity <= 0) {
                    $status = 'Out of Stock';
                    $badgeClass = 'badge-gray';
                } elseif ($batch->quantity <= 50) {
                    $status = 'Low Stock';
                    $badgeClass = 'badge-amber';
                } elseif ($remainingShelfLife <= 3) {
                    $status = 'Critical';
                    $badgeClass = 'badge-red';
                }
                
                // Calculate freshness percentage (based on shelf life)
                $freshnessPercentage = 100;
                if ($batch->expiry_date) {
                    $totalDays = Carbon::parse($batch->received_date)->diffInDays($batch->expiry_date);
                    $freshnessPercentage = $totalDays > 0 ? round(($remainingShelfLife / $totalDays) * 100) : 0;
                }
                
                // Determine if it's new or old stock (less than 3 days is new)
                $daysInStock = Carbon::parse($batch->received_date)->diffInDays(now());
                $stockAge = $daysInStock <= 3 ? 'New Stock' : 'Old Stock';
                
                $groupedItems[$item->name][] = [
                    $item->id, // 0: item_id
                    $batch->batch_code, // 1: batch_code
                    number_format($batch->quantity, 2) . ' kg', // 2: quantity (2 decimals for accuracy)
                    $batch->expiry_date ? $batch->expiry_date->format('M d, Y') : 'N/A', // 3: expiry_date
                    $batch->supplier ?? 'N/A', // 4: supplier
                    '₱' . number_format($batch->price_per_unit, 2) . '/kg', // 5: price
                    $status, // 6: status
                    $badgeClass, // 7: badge_class
                    $freshnessPercentage, // 8: freshness_percentage
                    $batch->received_date ? $batch->received_date->format('M d, Y') : 'N/A', // 9: received_date
                    $remainingShelfLife, // 10: remaining_shelf_life
                    $stockAge, // 11: stock_age
                ];
            }
        }

        // Prepare products table data
        $products = [];
        foreach ($inventoryItems as $item) {
            // Only sum quantities from available batches with quantity > 0
            $totalStock = $item->batches->where('status', 'available')->where('quantity', '>', 0)->sum('quantity');
            $avgPrice = $item->batches->where('quantity', '>', 0)->avg('price_per_unit') ?? 0;
            $nearestExpiry = $item->batches->where('quantity', '>', 0)->min('expiry_date');
            
            // Calculate total shelf life
            $shelfLife = 0;
            if ($nearestExpiry) {
                $shelfLife = now()->diffInDays(Carbon::parse($nearestExpiry));
            }
            
            $products[] = [
                $item->name, // 0: name
                number_format($totalStock, 2) . ' kg', // 1: total_stock (show decimals for accuracy)
                $item->batches->where('quantity', '>', 0)->count(), // 2: batch_count (only non-empty batches)
                $nearestExpiry ? Carbon::parse($nearestExpiry)->format('M d, Y') : 'N/A', // 3: nearest_expiry
                '₱' . number_format($avgPrice, 2) . '/kg', // 4: avg_price
                $shelfLife . ' days', // 5: shelf_life
            ];
        }

        // Get Stock In Records (recent batches received)
        $stockInRecords = InventoryBatch::with('inventoryItem')
            ->orderBy('received_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get()
            ->map(function($batch) {
                return [
                    $batch->batch_code, // 0: batch_code
                    $batch->inventoryItem->name ?? 'Unknown', // 1: product_name
                    number_format($batch->quantity, 0) . ' kg', // 2: quantity
                    $batch->supplier ?? 'N/A', // 3: supplier
                    $batch->received_date ? $batch->received_date->format('M d, Y') : 'N/A', // 4: date
                    '₱' . number_format($batch->quantity * $batch->price_per_unit, 2), // 5: total_value
                    'Received', // 6: status
                ];
            });

        // Get Stock Out Records (from sales transactions)
        $stockOutRecords = \App\Models\SalesTransaction::with('salesItems.inventoryBatch.inventoryItem')
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get()
            ->map(function($transaction) {
                // Get first item for display (transactions can have multiple items)
                $firstItem = $transaction->salesItems->first();
                $itemName = $firstItem && $firstItem->inventoryBatch && $firstItem->inventoryBatch->inventoryItem 
                    ? $firstItem->inventoryBatch->inventoryItem->name 
                    : 'Multiple Items';
                
                $totalQty = $transaction->salesItems->sum('quantity');
                
                // Format date
                $date = $transaction->created_at->format('M d, Y g:i A');
                if ($transaction->created_at->isToday()) {
                    $date = 'Today, ' . $transaction->created_at->format('g:i A');
                } elseif ($transaction->created_at->isYesterday()) {
                    $date = 'Yesterday, ' . $transaction->created_at->format('g:i A');
                }
                
                return [
                    $transaction->transaction_code, // 0: transaction_code
                    $itemName, // 1: product_name
                    number_format($totalQty, 0) . ' kg', // 2: quantity
                    'Sales Transaction', // 3: type
                    $date, // 4: date
                    '₱' . number_format($transaction->total_amount, 2), // 5: amount
                    ucfirst($transaction->status), // 6: status
                ];
            });

        // Get Stock Adjustment Records (from sales transactions with payment_method = 'adjustment')
        $adjustmentRecords = \App\Models\SalesTransaction::with('salesItems.inventoryBatch.inventoryItem', 'user')
            ->where('payment_method', 'adjustment')
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get()
            ->map(function($transaction) {
                // Get first item for display
                $firstItem = $transaction->salesItems->first();
                $itemName = $firstItem && $firstItem->inventoryBatch && $firstItem->inventoryBatch->inventoryItem 
                    ? $firstItem->inventoryBatch->inventoryItem->name 
                    : 'Multiple Items';
                
                $totalQty = $transaction->salesItems->sum('quantity');
                $qtyDisplay = ($totalQty >= 0 ? '+' : '') . number_format(abs($totalQty), 0) . ' kg';
                
                // Format date
                $date = $transaction->created_at->format('M d, Y g:i A');
                if ($transaction->created_at->isToday()) {
                    $date = 'Today, ' . $transaction->created_at->format('g:i A');
                } elseif ($transaction->created_at->isYesterday()) {
                    $date = 'Yesterday, ' . $transaction->created_at->format('g:i A');
                }
                
                $badgeClass = $totalQty >= 0 ? 'badge-green' : 'badge-red';
                $userName = $transaction->user ? $transaction->user->name : 'System';
                
                return [
                    $transaction->transaction_code, // 0: transaction_code
                    $itemName, // 1: product_name
                    $qtyDisplay, // 2: quantity_display (e.g., "+12 kg" or "-5 kg")
                    $transaction->notes ?? 'Stock adjustment', // 3: reason
                    $date, // 4: date
                    $userName, // 5: user_name
                    $badgeClass, // 6: badge_class
                ];
            });

        return view('pages.inventory', [
            'groupedItems' => $groupedItems,
            'products' => $products,
            'inventoryItems' => $inventoryItems,
            'stockInRecords' => $stockInRecords,
            'stockOutRecords' => $stockOutRecords,
            'adjustmentRecords' => $adjustmentRecords,
        ]);
    }
    
    /**
     * Get all inventory items with batches (API).
     */
    public function index()
    {
        // Get all inventory items with their batches
        $inventoryItems = InventoryItem::with(['batches' => function($query) {
            $query->where('status', 'available')->orderBy('expiry_date', 'asc');
        }])->get();

        // Group batches by fruit name for the overview cards
        $groupedItems = [];
        foreach ($inventoryItems as $item) {
            $groupedItems[$item->name] = [];
            
            foreach ($item->batches as $batch) {
                $remainingShelfLife = $batch->remaining_shelf_life ?? 0;
                
                // Determine status based on stock and shelf life
                $status = 'Available';
                $badgeClass = 'badge-green';
                
                if ($batch->quantity <= 0) {
                    $status = 'Out of Stock';
                    $badgeClass = 'badge-gray';
                } elseif ($batch->quantity <= 50) {
                    $status = 'Low Stock';
                    $badgeClass = 'badge-amber';
                } elseif ($remainingShelfLife <= 3) {
                    $status = 'Critical';
                    $badgeClass = 'badge-red';
                }
                
                // Calculate freshness percentage (based on shelf life)
                $freshnessPercentage = 100;
                if ($batch->expiry_date) {
                    $totalDays = Carbon::parse($batch->received_date)->diffInDays($batch->expiry_date);
                    $freshnessPercentage = $totalDays > 0 ? round(($remainingShelfLife / $totalDays) * 100) : 0;
                }
                
                // Determine if it's new or old stock (less than 3 days is new)
                $daysInStock = Carbon::parse($batch->received_date)->diffInDays(now());
                $stockAge = $daysInStock <= 3 ? 'New Stock' : 'Old Stock';
                
                $groupedItems[$item->name][] = [
                    $item->id, // 0: item_id
                    $batch->batch_code, // 1: batch_code
                    number_format($batch->quantity, 2) . ' kg', // 2: quantity (2 decimals for accuracy)
                    $batch->expiry_date ? $batch->expiry_date->format('M d, Y') : 'N/A', // 3: expiry_date
                    $batch->supplier ?? 'N/A', // 4: supplier
                    '₱' . number_format($batch->price_per_unit, 2) . '/kg', // 5: price
                    $status, // 6: status
                    $badgeClass, // 7: badge_class
                    $freshnessPercentage, // 8: freshness_percentage
                    $batch->received_date ? $batch->received_date->format('M d, Y') : 'N/A', // 9: received_date
                    $remainingShelfLife, // 10: remaining_shelf_life
                    $stockAge, // 11: stock_age
                ];
            }
        }

        // Prepare products table data
        $products = [];
        foreach ($inventoryItems as $item) {
            // Only sum quantities from available batches with quantity > 0
            $totalStock = $item->batches->where('status', 'available')->where('quantity', '>', 0)->sum('quantity');
            $avgPrice = $item->batches->where('quantity', '>', 0)->avg('price_per_unit') ?? 0;
            $nearestExpiry = $item->batches->where('quantity', '>', 0)->min('expiry_date');
            
            // Calculate total shelf life
            $shelfLife = 0;
            if ($nearestExpiry) {
                $shelfLife = now()->diffInDays(Carbon::parse($nearestExpiry));
            }
            
            $products[] = [
                $item->name, // 0: name
                number_format($totalStock, 2) . ' kg', // 1: total_stock (show decimals for accuracy)
                $item->batches->where('quantity', '>', 0)->count(), // 2: batch_count (only non-empty batches)
                $nearestExpiry ? Carbon::parse($nearestExpiry)->format('M d, Y') : 'N/A', // 3: nearest_expiry
                '₱' . number_format($avgPrice, 2) . '/kg', // 4: avg_price
                $shelfLife . ' days', // 5: shelf_life
            ];
        }

        return view('pages.inventory', [
            'groupedItems' => $groupedItems,
            'products' => $products,
        ]);
    }

    /**
     * Get inventory statistics.
     */
    public function getStats()
    {
        $totalItems = InventoryItem::count();
        $totalBatches = InventoryBatch::where('status', 'available')->count();
        $totalValue = InventoryBatch::where('status', 'available')
            ->get()
            ->sum(function($batch) {
                return $batch->quantity * $batch->price_per_unit;
            });
        
        $lowStockItems = InventoryItem::lowStock()->count();
        $expiringItems = InventoryBatch::expiringSoon(7)->count();

        return response()->json([
            'total_items' => $totalItems,
            'total_batches' => $totalBatches,
            'total_value' => $totalValue,
            'low_stock_items' => $lowStockItems,
            'expiring_items' => $expiringItems,
        ]);
    }

    /**
     * Store a new product.
     */
    public function storeProduct(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string',
            'unit' => 'required|string',
            'description' => 'nullable|string',
        ]);

        // Get shelf life based on product name
        $shelfLifeMap = [
            'Mango' => 14,
            'Durian' => 7,
            'Pomelo' => 21,
            'Mangosteen' => 14,
            'Lanzones' => 10,
            'Banana' => 7,
            'Pineapple' => 14,
        ];

        $item = InventoryItem::create([
            'name' => $validated['name'],
            'category' => $validated['category'],
            'unit' => $validated['unit'],
            'storage_notes' => $validated['description'] ?? null,
            'stock_quantity' => 0,
            'price_per_unit' => 0,
            'reorder_level' => 50,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Product added successfully',
            'data' => $item,
        ], 201);
    }

    /**
     * Store a new stock in transaction.
     */
    public function stockIn(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:inventory_items,id',
            'items.*.product_name' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.price_per_unit' => 'required|numeric|min:0',
            'items.*.batch_code' => 'required|string',
            'items.*.expiry_date' => 'required|date',
            'items.*.supplier' => 'required|string',
            'supplier' => 'nullable|string',
            'received_date' => 'required|date',
            'reference_number' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            foreach ($validated['items'] as $itemData) {
                // Find or create inventory item
                if (!empty($itemData['product_id'])) {
                    $inventoryItem = InventoryItem::find($itemData['product_id']);
                } else {
                    $inventoryItem = InventoryItem::firstOrCreate(
                        ['name' => $itemData['product_name']],
                        [
                            'category' => 'Tropical Fruit',
                            'unit' => 'kg',
                            'price_per_unit' => $itemData['price_per_unit'],
                            'stock_quantity' => 0,
                            'reorder_level' => 50,
                            'status' => 'active',
                        ]
                    );
                }

                // Create batch
                $batch = InventoryBatch::create([
                    'inventory_item_id' => $inventoryItem->id,
                    'batch_code' => $itemData['batch_code'],
                    'quantity' => $itemData['quantity'],
                    'initial_quantity' => $itemData['quantity'],
                    'price_per_unit' => $itemData['price_per_unit'],
                    'received_date' => $validated['received_date'],
                    'expiry_date' => $itemData['expiry_date'],
                    'supplier' => $itemData['supplier'],
                    'status' => 'available',
                ]);

                // Update inventory item stock and price
                $inventoryItem->increment('stock_quantity', $itemData['quantity']);
                $inventoryItem->update(['price_per_unit' => $itemData['price_per_unit']]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stock added successfully',
                'data' => [
                    'reference_number' => $validated['reference_number'] ?? 'N/A',
                    'items_count' => count($validated['items'])
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to add stock: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Stock out transaction.
     */
    public function stockOut(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.batch_id' => 'required|exists:inventory_batches,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'type' => 'required|string|in:sale,spoilage,wastage,return,transfer,sample,other',
            'notes' => 'nullable|string',
            'reference' => 'nullable|string',
            'date' => 'nullable|date',
        ]);

        DB::beginTransaction();
        try {
            $totalAmount = 0;
            $transactionItems = [];
            
            // First, validate all items and calculate total
            foreach ($validated['items'] as $itemData) {
                $batch = InventoryBatch::findOrFail($itemData['batch_id']);
                
                if ($batch->quantity < $itemData['quantity']) {
                    throw new \Exception("Insufficient stock for batch {$batch->batch_code}");
                }
                
                $itemTotal = $itemData['quantity'] * $batch->price_per_unit;
                $totalAmount += $itemTotal;
                
                $transactionItems[] = [
                    'batch' => $batch,
                    'quantity' => $itemData['quantity'],
                    'price_per_unit' => $batch->price_per_unit,
                    'total' => $itemTotal,
                ];
            }
            
            // Create sales transaction record
            $transaction = \App\Models\SalesTransaction::create([
                'user_id' => auth()->id(),
                'transaction_code' => $validated['reference'] ?? 'SO-' . now()->format('ymd') . '-' . rand(100, 999),
                'type' => $validated['type'],
                'subtotal' => $totalAmount,
                'tax_amount' => 0,
                'discount_amount' => 0,
                'total_amount' => $totalAmount,
                'payment_method' => $validated['type'] === 'sale' ? 'cash' : 'n/a',
                'status' => 'completed',
                'notes' => $validated['notes'] ?? 'Stock out transaction',
            ]);
            
            // Process each item
            foreach ($transactionItems as $itemData) {
                $batch = $itemData['batch'];
                
                // Create sales item record
                \App\Models\SalesItem::create([
                    'sale_transaction_id' => $transaction->id,
                    'inventory_item_id' => $batch->inventory_item_id,
                    'inventory_batch_id' => $batch->id,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['price_per_unit'],
                    'total_amount' => $itemData['total'],
                ]);
                
                // Reduce batch and item stock
                $batch->decrement('quantity', $itemData['quantity']);
                $batch->inventoryItem->decrement('stock_quantity', $itemData['quantity']);

                // Update batch status if quantity is 0
                if ($batch->quantity <= 0) {
                    $batch->update(['status' => 'depleted']);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stock removed successfully',
                'transaction_code' => $transaction->transaction_code,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove stock: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Stock adjustment.
     */
    public function stockAdjustment(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.batch_id' => 'required|exists:inventory_batches,id',
            'items.*.quantity' => 'required|numeric',
            'type' => 'required|string|in:add,subtract',
            'reason' => 'required|string',
            'notes' => 'nullable|string',
            'reference' => 'nullable|string',
            'date' => 'nullable|date',
        ]);

        DB::beginTransaction();
        try {
            $totalAmount = 0;
            $transactionItems = [];
            
            foreach ($validated['items'] as $itemData) {
                $batch = InventoryBatch::findOrFail($itemData['batch_id']);
                $adjustmentQty = abs($itemData['quantity']);

                // Calculate value for tracking
                $itemTotal = $adjustmentQty * $batch->price_per_unit;
                $totalAmount += $itemTotal;
                
                $transactionItems[] = [
                    'batch' => $batch,
                    'quantity' => $adjustmentQty,
                    'type' => $validated['type'],
                    'price_per_unit' => $batch->price_per_unit,
                    'total' => $itemTotal,
                ];
                
                if ($validated['type'] === 'add') {
                    $batch->increment('quantity', $adjustmentQty);
                    $batch->inventoryItem->increment('stock_quantity', $adjustmentQty);
                } else {
                    if ($batch->quantity < $adjustmentQty) {
                        throw new \Exception("Cannot subtract more than available stock for batch {$batch->batch_code}");
                    }
                    $batch->decrement('quantity', $adjustmentQty);
                    $batch->inventoryItem->decrement('stock_quantity', $adjustmentQty);

                    if ($batch->quantity <= 0) {
                        $batch->update(['status' => 'depleted']);
                    }
                }
            }
            
            // Create adjustment transaction record (using sales_transactions table)
            $transaction = \App\Models\SalesTransaction::create([
                'user_id' => auth()->id(),
                'transaction_code' => $validated['reference'] ?? 'ADJ-' . now()->format('ymd') . '-' . rand(100, 999),
                'type' => 'adjustment',
                'subtotal' => $totalAmount,
                'tax_amount' => 0,
                'discount_amount' => 0,
                'total_amount' => $validated['type'] === 'add' ? $totalAmount : -$totalAmount,
                'payment_method' => 'adjustment',
                'status' => 'completed',
                'notes' => $validated['reason'] . (!empty($validated['notes']) ? ' - ' . $validated['notes'] : ''),
            ]);
            
            // Create sales item records for tracking
            foreach ($transactionItems as $itemData) {
                \App\Models\SalesItem::create([
                    'sale_transaction_id' => $transaction->id,
                    'inventory_item_id' => $itemData['batch']->inventory_item_id,
                    'inventory_batch_id' => $itemData['batch']->id,
                    'quantity' => $itemData['type'] === 'add' ? $itemData['quantity'] : -$itemData['quantity'],
                    'unit_price' => $itemData['price_per_unit'],
                    'total_amount' => $itemData['type'] === 'add' ? $itemData['total'] : -$itemData['total'],
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stock adjusted successfully',
                'transaction_code' => $transaction->transaction_code,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to adjust stock: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get low stock items.
     */
    public function getLowStock()
    {
        $lowStockItems = InventoryItem::with('batches')
            ->lowStock()
            ->get()
            ->map(function($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'current_stock' => $item->stock_quantity,
                    'reorder_level' => $item->reorder_level,
                    'status' => $item->stock_status,
                ];
            });

        return response()->json($lowStockItems);
    }

    /**
     * Get expiring items.
     */
    public function getExpiringItems()
    {
        $expiringBatches = InventoryBatch::with('inventoryItem')
            ->expiringSoon(7)
            ->get()
            ->map(function($batch) {
                return [
                    'id' => $batch->id,
                    'product_name' => $batch->inventoryItem->name,
                    'batch_code' => $batch->batch_code,
                    'quantity' => $batch->quantity,
                    'expiry_date' => $batch->expiry_date->format('Y-m-d'),
                    'days_remaining' => $batch->remaining_shelf_life,
                ];
            });

        return response()->json($expiringBatches);
    }
}
