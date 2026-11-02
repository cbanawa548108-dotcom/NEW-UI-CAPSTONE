<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\InventoryBatch;
use App\Models\SalesTransaction;
use App\Models\SalesItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Display the dashboard.
     */
    public function index()
    {
        // Get real-time metrics
        $metrics = $this->getDashboardMetrics();
        
        return view('pages.dashboard', $metrics);
    }

    /**
     * Get dashboard metrics.
     */
    public function getDashboardMetrics()
    {
        // Today's sales
        $todaySales = SalesTransaction::today()->completed()->sum('total_amount');
        
        // This week's sales
        $weekSales = SalesTransaction::thisWeek()->completed()->sum('total_amount');
        
        // This month's sales
        $monthSales = SalesTransaction::thisMonth()->completed()->sum('total_amount');
        
        // Calculate week vs last week growth
        $lastWeekSales = SalesTransaction::completed()
            ->whereBetween('created_at', [
                now()->subWeek()->startOfWeek(),
                now()->subWeek()->endOfWeek()
            ])
            ->sum('total_amount');
        
        $weekGrowth = 0;
        if ($lastWeekSales > 0) {
            $weekGrowth = (($weekSales - $lastWeekSales) / $lastWeekSales) * 100;
        } elseif ($weekSales > 0) {
            $weekGrowth = 100;
        }

        // Total inventory value
        $inventoryValue = InventoryBatch::where('status', 'available')
            ->get()
            ->sum(function($batch) {
                return $batch->quantity * $batch->price_per_unit;
            });

        // Active products
        $activeProducts = InventoryItem::active()->count();
        
        // Total batches
        $totalBatches = InventoryBatch::where('status', 'available')->count();

        // Low stock alerts
        $lowStockCount = InventoryItem::lowStock()->count();
        
        // Expiring soon (within 7 days)
        $expiringCount = InventoryBatch::expiringSoon(7)->count();
        
        // Out of stock
        $outOfStockCount = InventoryItem::outOfStock()->count();

        // Today's transactions
        $todayTransactions = SalesTransaction::today()->completed()->count();

        // Top selling products this week
        $topProducts = SalesItem::select('inventory_item_id', DB::raw('SUM(quantity) as total_qty'))
            ->whereHas('saleTransaction', function($q) {
                $q->completed()->thisWeek();
            })
            ->with('inventoryItem')
            ->groupBy('inventory_item_id')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get()
            ->filter(function($item) {
                return $item->inventoryItem !== null; // Filter out items with no inventory item
            })
            ->map(function($item) {
                return [
                    'name' => $item->inventoryItem->name,
                    'quantity' => $item->total_qty,
                ];
            })
            ->values(); // Reset array keys after filter

        // Recent sales (last 10)
        $recentSales = SalesTransaction::with(['salesItems.inventoryItem', 'user'])
            ->completed()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function($sale) {
                return [
                    'transaction_code' => $sale->transaction_code,
                    'total_amount' => $sale->total_amount,
                    'items_count' => $sale->salesItems->count(),
                    'created_at' => $sale->created_at->format('M d, Y h:i A'),
                    'user_name' => $sale->user ? $sale->user->name : 'N/A',
                ];
            });

        // Expiring items list
        $expiringItems = InventoryBatch::with('inventoryItem')
            ->expiringSoon(7)
            ->where('status', 'available')
            ->where('quantity', '>', 0) // Only include batches with stock
            ->orderBy('expiry_date', 'asc')
            ->limit(10)
            ->get()
            ->filter(function($batch) {
                return $batch->inventoryItem !== null; // Filter out batches with no inventory item
            })
            ->map(function($batch) {
                return [
                    'product_name' => $batch->inventoryItem->name,
                    'batch_code' => $batch->batch_code,
                    'quantity' => $batch->quantity,
                    'expiry_date' => $batch->expiry_date->format('M d, Y'),
                    'days_remaining' => $batch->remaining_shelf_life,
                ];
            })
            ->values(); // Reset array keys after filter

        // Low stock items
        $lowStockItems = InventoryItem::lowStock()
            ->orderBy('stock_quantity', 'asc')
            ->limit(10)
            ->get()
            ->map(function($item) {
                return [
                    'name' => $item->name,
                    'current_stock' => $item->stock_quantity,
                    'reorder_level' => $item->reorder_level,
                    'deficit' => $item->reorder_level - $item->stock_quantity,
                ];
            });

        // Daily sales for the last 7 days (for charts)
        $dailySales = SalesTransaction::completed()
            ->where('created_at', '>=', now()->subDays(7))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total_amount) as total')
            )
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get()
            ->pluck('total', 'date')
            ->toArray();

        // Fill in missing dates with 0
        $salesChart = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $salesChart[$date] = $dailySales[$date] ?? 0;
        }

        return [
            'today_sales' => $todaySales,
            'week_sales' => $weekSales,
            'month_sales' => $monthSales,
            'week_growth' => round($weekGrowth, 1),
            'inventory_value' => $inventoryValue,
            'active_products' => $activeProducts,
            'total_batches' => $totalBatches,
            'low_stock_count' => $lowStockCount,
            'expiring_count' => $expiringCount,
            'out_of_stock_count' => $outOfStockCount,
            'today_transactions' => $todayTransactions,
            'top_products' => $topProducts,
            'recent_sales' => $recentSales,
            'expiring_items' => $expiringItems,
            'low_stock_items' => $lowStockItems,
            'sales_chart' => $salesChart,
        ];
    }

    /**
     * Get dashboard data as JSON (for AJAX updates).
     */
    public function getData()
    {
        $metrics = $this->getDashboardMetrics();
        return response()->json($metrics);
    }

    /**
     * Get sales trend data.
     */
    public function getSalesTrend(Request $request)
    {
        $days = $request->input('days', 30);
        
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
     * Get inventory health summary.
     */
    public function getInventoryHealth()
    {
        $total = InventoryItem::count();
        $available = InventoryItem::where('stock_quantity', '>', 0)->count();
        $lowStock = InventoryItem::lowStock()->count();
        $outOfStock = InventoryItem::outOfStock()->count();

        $healthPercentage = $total > 0 ? round((($available - $lowStock) / $total) * 100, 1) : 0;

        return response()->json([
            'total_products' => $total,
            'available' => $available,
            'low_stock' => $lowStock,
            'out_of_stock' => $outOfStock,
            'health_percentage' => $healthPercentage,
        ]);
    }
}
