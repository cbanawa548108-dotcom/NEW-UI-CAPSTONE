<?php

namespace App\Http\Controllers;

use App\Models\SalesTransaction;
use App\Models\SalesItem;
use App\Models\InventoryItem;
use App\Models\InventoryBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportsController extends Controller
{
    /**
     * Display the reports page.
     */
    public function page()
    {
        return view('pages.reports');
    }

    /**
     * Generate sales report.
     */
    public function salesReport(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'group_by' => 'nullable|in:day,week,month',
        ]);

        $startDate = Carbon::parse($validated['start_date'])->startOfDay();
        $endDate = Carbon::parse($validated['end_date'])->endOfDay();
        $groupBy = $validated['group_by'] ?? 'day';

        // Get sales data
        $query = SalesTransaction::completed()
            ->whereBetween('created_at', [$startDate, $endDate]);

        // Summary statistics
        $totalSales = $query->sum('total_amount');
        $totalTransactions = $query->count();
        $averageTransaction = $totalTransactions > 0 ? $totalSales / $totalTransactions : 0;
        $totalItemsSold = SalesItem::whereHas('saleTransaction', function($q) use ($startDate, $endDate) {
            $q->completed()->whereBetween('created_at', [$startDate, $endDate]);
        })->sum('quantity');

        // Sales by date
        $salesByDate = SalesTransaction::completed()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total_amount) as total_sales'),
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('AVG(total_amount) as avg_transaction')
            )
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        // Top selling products
        $topProducts = SalesItem::select(
                'inventory_item_id',
                DB::raw('SUM(quantity) as total_quantity'),
                DB::raw('SUM(total_amount) as total_sales'),
                DB::raw('COUNT(DISTINCT sale_transaction_id) as transaction_count')
            )
            ->whereHas('saleTransaction', function($q) use ($startDate, $endDate) {
                $q->completed()->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->with('inventoryItem')
            ->groupBy('inventory_item_id')
            ->orderByDesc('total_sales')
            ->get()
            ->map(function($item) {
                return [
                    'product_name' => $item->inventoryItem->name,
                    'quantity_sold' => $item->total_quantity,
                    'total_sales' => $item->total_sales,
                    'transaction_count' => $item->transaction_count,
                    'average_price' => $item->total_quantity > 0 ? $item->total_sales / $item->total_quantity : 0,
                ];
            });

        // Payment method breakdown
        $paymentMethods = SalesTransaction::completed()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(
                'payment_method',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total_amount) as total')
            )
            ->groupBy('payment_method')
            ->get();

        return response()->json([
            'period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'days' => $startDate->diffInDays($endDate) + 1,
            ],
            'summary' => [
                'total_sales' => round($totalSales, 2),
                'total_transactions' => $totalTransactions,
                'average_transaction' => round($averageTransaction, 2),
                'total_items_sold' => $totalItemsSold,
            ],
            'sales_by_date' => $salesByDate,
            'top_products' => $topProducts,
            'payment_methods' => $paymentMethods,
        ]);
    }

    /**
     * Generate inventory report.
     */
    public function inventoryReport(Request $request)
    {
        // Get all inventory items with batches
        $items = InventoryItem::with(['batches' => function($query) {
            $query->where('status', 'available');
        }])->get();

        $report = $items->map(function($item) {
            $totalValue = 0;
            $oldestBatch = null;
            $newestBatch = null;
            $expiringBatches = 0;

            foreach ($item->batches as $batch) {
                $totalValue += $batch->quantity * $batch->price_per_unit;
                
                if (!$oldestBatch || $batch->received_date < $oldestBatch->received_date) {
                    $oldestBatch = $batch;
                }
                
                if (!$newestBatch || $batch->received_date > $newestBatch->received_date) {
                    $newestBatch = $batch;
                }

                if ($batch->remaining_shelf_life !== null && $batch->remaining_shelf_life <= 7) {
                    $expiringBatches++;
                }
            }

            return [
                'product_name' => $item->name,
                'category' => $item->category,
                'total_stock' => $item->stock_quantity,
                'unit' => $item->unit,
                'batch_count' => $item->batches->count(),
                'total_value' => $totalValue,
                'average_price' => $item->batches->avg('price_per_unit'),
                'reorder_level' => $item->reorder_level,
                'stock_status' => $item->stock_status,
                'expiring_batches' => $expiringBatches,
                'oldest_batch_date' => $oldestBatch ? $oldestBatch->received_date->format('Y-m-d') : null,
                'newest_batch_date' => $newestBatch ? $newestBatch->received_date->format('Y-m-d') : null,
            ];
        });

        // Summary
        $totalProducts = $items->count();
        $totalValue = $report->sum('total_value');
        $totalStock = $report->sum('total_stock');
        $lowStockCount = $items->filter(function($item) {
            return $item->stock_quantity <= $item->reorder_level;
        })->count();

        return response()->json([
            'summary' => [
                'total_products' => $totalProducts,
                'total_stock_quantity' => $totalStock,
                'total_inventory_value' => round($totalValue, 2),
                'low_stock_items' => $lowStockCount,
            ],
            'items' => $report,
        ]);
    }

    /**
     * Generate expiry report.
     */
    public function expiryReport(Request $request)
    {
        $days = $request->input('days', 30);

        $expiringBatches = InventoryBatch::with('inventoryItem')
            ->where('status', 'available')
            ->where('expiry_date', '<=', now()->addDays($days))
            ->where('expiry_date', '>=', now())
            ->orderBy('expiry_date', 'asc')
            ->get()
            ->map(function($batch) {
                $value = $batch->quantity * $batch->price_per_unit;
                $urgency = 'low';
                
                if ($batch->remaining_shelf_life <= 3) {
                    $urgency = 'critical';
                } elseif ($batch->remaining_shelf_life <= 7) {
                    $urgency = 'high';
                } elseif ($batch->remaining_shelf_life <= 14) {
                    $urgency = 'medium';
                }

                return [
                    'product_name' => $batch->inventoryItem->name,
                    'batch_code' => $batch->batch_code,
                    'quantity' => $batch->quantity,
                    'unit' => $batch->inventoryItem->unit,
                    'expiry_date' => $batch->expiry_date->format('Y-m-d'),
                    'days_remaining' => $batch->remaining_shelf_life,
                    'value_at_risk' => $value,
                    'urgency' => $urgency,
                    'supplier' => $batch->supplier,
                ];
            });

        $totalValueAtRisk = $expiringBatches->sum('value_at_risk');
        $criticalCount = $expiringBatches->where('urgency', 'critical')->count();
        $highCount = $expiringBatches->where('urgency', 'high')->count();

        return response()->json([
            'summary' => [
                'total_batches_expiring' => $expiringBatches->count(),
                'total_value_at_risk' => round($totalValueAtRisk, 2),
                'critical_items' => $criticalCount,
                'high_priority_items' => $highCount,
            ],
            'expiring_batches' => $expiringBatches,
        ]);
    }

    /**
     * Export Sales Report as PDF.
     */
    public function exportSalesPDF(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = Carbon::parse($validated['start_date'])->startOfDay();
        $endDate = Carbon::parse($validated['end_date'])->endOfDay();

        // Get report data
        $totalSales = SalesTransaction::completed()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('total_amount');
        
        $totalTransactions = SalesTransaction::completed()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $topProducts = SalesItem::select(
                'inventory_item_id',
                DB::raw('SUM(quantity) as total_quantity'),
                DB::raw('SUM(total_amount) as total_sales')
            )
            ->whereHas('saleTransaction', function($q) use ($startDate, $endDate) {
                $q->completed()->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->with('inventoryItem')
            ->groupBy('inventory_item_id')
            ->orderByDesc('total_sales')
            ->limit(10)
            ->get()
            ->filter(function($item) {
                // Filter out items with null inventoryItem
                return $item->inventoryItem !== null;
            });

        $html = view('reports.sales-pdf', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'totalSales' => $totalSales,
            'totalTransactions' => $totalTransactions,
            'topProducts' => $topProducts,
        ])->render();

        return response($html)
            ->header('Content-Type', 'text/html');
    }

    /**
     * Generate profit/loss report.
     */
    public function profitLossReport(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = Carbon::parse($validated['start_date'])->startOfDay();
        $endDate = Carbon::parse($validated['end_date'])->endOfDay();

        // Calculate revenue from sales
        $revenue = SalesTransaction::completed()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('total_amount');

        // Calculate cost of goods sold
        $costOfGoodsSold = SalesItem::whereHas('saleTransaction', function($q) use ($startDate, $endDate) {
                $q->completed()->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->with('inventoryBatch')
            ->get()
            ->sum(function($item) {
                return $item->quantity * ($item->inventoryBatch ? $item->inventoryBatch->price_per_unit : 0);
            });

        // Gross profit
        $grossProfit = $revenue - $costOfGoodsSold;
        $grossMargin = $revenue > 0 ? ($grossProfit / $revenue) * 100 : 0;

        // Product-wise profit
        $productProfits = SalesItem::select('inventory_item_id')
            ->whereHas('saleTransaction', function($q) use ($startDate, $endDate) {
                $q->completed()->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->with(['inventoryItem', 'inventoryBatch'])
            ->get()
            ->groupBy('inventory_item_id')
            ->map(function($items, $productId) {
                $productName = $items->first()->inventoryItem->name;
                $revenue = $items->sum('total_amount');
                $cost = $items->sum(function($item) {
                    return $item->quantity * ($item->inventoryBatch ? $item->inventoryBatch->price_per_unit : 0);
                });
                $profit = $revenue - $cost;
                $margin = $revenue > 0 ? ($profit / $revenue) * 100 : 0;

                return [
                    'product_name' => $productName,
                    'revenue' => $revenue,
                    'cost' => $cost,
                    'profit' => $profit,
                    'margin_percentage' => round($margin, 2),
                ];
            })
            ->sortByDesc('profit')
            ->values();

        return response()->json([
            'period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ],
            'summary' => [
                'total_revenue' => round($revenue, 2),
                'cost_of_goods_sold' => round($costOfGoodsSold, 2),
                'gross_profit' => round($grossProfit, 2),
                'gross_margin_percentage' => round($grossMargin, 2),
            ],
            'product_profits' => $productProfits,
        ]);
    }
}
