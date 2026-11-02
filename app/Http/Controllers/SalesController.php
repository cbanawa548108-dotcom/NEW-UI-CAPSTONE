<?php

namespace App\Http\Controllers;

use App\Models\SalesTransaction;
use App\Models\SalesItem;
use App\Models\InventoryItem;
use App\Models\InventoryBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SalesController extends Controller
{
    /**
     * Display the sales page.
     */
    public function page()
    {
        // Get sales transactions
        $sales = SalesTransaction::with(['salesItems.inventoryBatch.inventoryItem', 'user'])
            ->where('payment_method', '!=', 'adjustment') // Exclude adjustments
            ->where('type', 'sale') // Exclude spoilage/wastage write-offs
            ->orderBy('created_at', 'desc')
            ->take(50)
            ->get();

        // Get today's stats
        $todayTotal = SalesTransaction::completed()
            ->where('payment_method', '!=', 'adjustment')
            ->today()
            ->sum('total_amount');
        
        $todayTransactions = SalesTransaction::completed()
            ->where('payment_method', '!=', 'adjustment')
            ->today()
            ->count();
        
        $avgSaleValue = $todayTransactions > 0 ? $todayTotal / $todayTransactions : 0;
        
        // Get top seller today
        $topSeller = \App\Models\SalesItem::select('inventory_batch_id', DB::raw('SUM(quantity) as total_qty'))
            ->whereHas('saleTransaction', function($q) {
                $q->completed()->today()->where('payment_method', '!=', 'adjustment');
            })
            ->groupBy('inventory_batch_id')
            ->orderByDesc('total_qty')
            ->with('inventoryBatch.inventoryItem')
            ->first();
        
        $topSellerName = $topSeller && $topSeller->inventoryBatch && $topSeller->inventoryBatch->inventoryItem 
            ? $topSeller->inventoryBatch->inventoryItem->name 
            : 'N/A';

        return view('pages.sales', [
            'sales' => $sales,
            'todayTotal' => $todayTotal,
            'todayTransactions' => $todayTransactions,
            'avgSaleValue' => $avgSaleValue,
            'topSellerName' => $topSellerName,
        ]);
    }
    
    /**
     * Get sales list (API).
     */
    public function index()
    {
        $sales = SalesTransaction::with(['salesItems.inventoryItem', 'user'])
            ->where('type', 'sale')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($sales);
    }

    /**
     * Display the POS page.
     */
    public function pos()
    {
        // Get available inventory items with batches
        $products = InventoryItem::with(['batches' => function($query) {
            $query->where('status', 'available')
                  ->where('quantity', '>', 0)
                  ->orderBy('expiry_date', 'asc'); // FIFO
        }])
        ->where('stock_quantity', '>', 0)
        ->get();

        return view('pages.pos', ['products' => $products]);
    }

    /**
     * Create a new sale transaction.
     */
    public function createSale(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:inventory_items,id',
            'items.*.batch_id' => 'required|exists:inventory_batches,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.price' => 'required|numeric|min:0',
            'payment_method' => 'required|string',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Calculate totals
            $subtotal = 0;
            foreach ($validated['items'] as $item) {
                $subtotal += $item['quantity'] * $item['price'];
            }

            $discountAmount = $validated['discount_amount'] ?? 0;
            $taxAmount = 0; // You can add tax calculation here if needed
            $totalAmount = $subtotal - $discountAmount + $taxAmount;

            // Generate transaction code
            $transactionCode = 'TXN-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

            // Create transaction
            $transaction = SalesTransaction::create([
                'user_id' => Auth::id(),
                'transaction_code' => $transactionCode,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'payment_method' => $validated['payment_method'],
                'status' => 'completed',
                'notes' => $validated['notes'] ?? null,
            ]);

            // Create sales items and update inventory
            foreach ($validated['items'] as $itemData) {
                $batch = InventoryBatch::findOrFail($itemData['batch_id']);
                
                // Check if batch has enough stock
                if ($batch->quantity < $itemData['quantity']) {
                    throw new \Exception("Insufficient stock for {$batch->inventoryItem->name} - Batch {$batch->batch_code}");
                }

                // Create sales item
                SalesItem::create([
                    'sale_transaction_id' => $transaction->id,
                    'inventory_item_id' => $itemData['product_id'],
                    'inventory_batch_id' => $itemData['batch_id'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['price'],
                    'total_amount' => $itemData['quantity'] * $itemData['price'],
                ]);

                // Reduce batch stock (FIFO)
                $batch->decrement('quantity', $itemData['quantity']);
                if ($batch->quantity <= 0) {
                    $batch->update(['status' => 'depleted']);
                }

                // Update inventory item stock
                $batch->inventoryItem->decrement('stock_quantity', $itemData['quantity']);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sale completed successfully',
                'data' => [
                    'transaction_code' => $transactionCode,
                    'total_amount' => $totalAmount,
                    'transaction_id' => $transaction->id,
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete sale: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get sales statistics.
     */
    public function getStats(Request $request)
    {
        $period = $request->input('period', 'today'); // today, week, month, year

        $query = SalesTransaction::completed();

        switch ($period) {
            case 'today':
                $query->today();
                break;
            case 'week':
                $query->thisWeek();
                break;
            case 'month':
                $query->thisMonth();
                break;
            case 'year':
                $query->whereYear('created_at', now()->year);
                break;
        }

        $totalSales = $query->sum('total_amount');
        $totalTransactions = $query->count();
        $averageTransaction = $totalTransactions > 0 ? $totalSales / $totalTransactions : 0;

        // Get top selling products
        $topProducts = SalesItem::select('inventory_item_id', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(total_amount) as total_sales'))
            ->whereHas('saleTransaction', function($q) use ($period) {
                $q->completed();
                switch ($period) {
                    case 'today':
                        $q->today();
                        break;
                    case 'week':
                        $q->thisWeek();
                        break;
                    case 'month':
                        $q->thisMonth();
                        break;
                    case 'year':
                        $q->whereYear('created_at', now()->year);
                        break;
                }
            })
            ->with('inventoryItem')
            ->groupBy('inventory_item_id')
            ->orderByDesc('total_sales')
            ->limit(5)
            ->get()
            ->map(function($item) {
                return [
                    'product_name' => $item->inventoryItem->name,
                    'quantity_sold' => $item->total_qty,
                    'total_sales' => $item->total_sales,
                ];
            });

        return response()->json([
            'total_sales' => $totalSales,
            'total_transactions' => $totalTransactions,
            'average_transaction' => $averageTransaction,
            'top_products' => $topProducts,
        ]);
    }

    /**
     * Get daily sales data for charts.
     */
    public function getDailySales(Request $request)
    {
        $days = $request->input('days', 7);
        
        $sales = SalesTransaction::completed()
            ->where('created_at', '>=', now()->subDays($days))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total_amount) as total_sales'),
                DB::raw('COUNT(*) as transaction_count')
            )
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        return response()->json($sales);
    }

    /**
     * Get transaction details.
     */
    public function getTransaction($id)
    {
        $transaction = SalesTransaction::with(['salesItems.inventoryItem', 'salesItems.inventoryBatch', 'user'])
            ->findOrFail($id);

        return response()->json($transaction);
    }

    /**
     * Get available products for POS.
     */
    public function getAvailableProducts()
    {
        $products = InventoryItem::with(['batches' => function($query) {
            $query->where('status', 'available')
                  ->where('quantity', '>', 0)
                  ->orderBy('expiry_date', 'asc');
        }])
        ->get()
        ->filter(function($item) {
            // Only include items that have available batches
            return $item->batches->count() > 0;
        })
        ->map(function($item) {
            // Calculate total available quantity from all batches
            $totalAvailable = $item->batches->sum('quantity');
            
            // Get the first batch (FIFO - earliest expiry)
            $firstBatch = $item->batches->first();
            
            return [
                'id' => $item->id,
                'name' => $item->name,
                'category' => $item->category,
                'unit' => $item->unit,
                'available_stock' => $totalAvailable,
                'batches' => $item->batches->map(function($batch) {
                    return [
                        'id' => $batch->id,
                        'batch_code' => $batch->batch_code,
                        'quantity' => $batch->quantity,
                        'price_per_unit' => $batch->price_per_unit,
                        'expiry_date' => $batch->expiry_date,
                    ];
                }),
                'price' => $firstBatch ? $firstBatch->price_per_unit : 0,
                'batch_id' => $firstBatch ? $firstBatch->id : null,
                'batch_code' => $firstBatch ? $firstBatch->batch_code : null,
            ];
        })
        ->values(); // Reset array keys

        return response()->json($products);
    }

    /**
     * Delete a sales transaction (only if not completed).
     */
    public function deleteSale($id)
    {
        $transaction = SalesTransaction::findOrFail($id);

        if ($transaction->status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete completed transaction',
            ], 400);
        }

        $transaction->delete();

        return response()->json([
            'success' => true,
            'message' => 'Transaction deleted successfully',
        ]);
    }
}
