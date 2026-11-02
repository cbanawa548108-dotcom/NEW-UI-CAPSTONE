<?php

namespace App\Http\Controllers;

use App\Models\InventoryBatch;
use App\Models\InventoryItem;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class NotificationsController extends Controller
{
    private const PER_PAGE = 8;

    private const MODULES = ['forecast', 'spoilage', 'weather', 'inventory', 'sales'];

    /**
     * Display the notifications page.
     */
    public function page(Request $request)
    {
        $userId = Auth::id();
        $this->syncNotifications($userId);

        $visible = fn () => Notification::visibleTo($userId);

        $filter = $request->query('filter', 'all');
        $filter = in_array($filter, self::MODULES, true) ? $filter : 'all';
        $sort = in_array($request->query('sort'), ['recent', 'priority', 'unread'], true) ? $request->query('sort') : 'recent';
        $search = trim((string) $request->query('q', ''));

        $query = $visible();

        if ($filter !== 'all') {
            $query->whereRaw('LOWER(module) = ?', [$filter]);
        }

        if ($search !== '') {
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $search) . '%';
            $query->where(function ($q) use ($like) {
                $q->where('title', 'like', $like)
                  ->orWhere('subtitle', 'like', $like)
                  ->orWhere('message', 'like', $like);
            });
        }

        if ($sort === 'priority') {
            $query->orderByRaw("FIELD(severity, 'Critical', 'High', 'Medium', 'Low', 'Info')");
        } elseif ($sort === 'unread') {
            $query->orderBy('is_read');
        }
        $query->orderByDesc('created_at')->orderByDesc('id');

        $notifications = $query->paginate(self::PER_PAGE)->withQueryString();

        $moduleCounts = $visible()
            ->select(DB::raw('LOWER(module) as module'), DB::raw('COUNT(*) as total'))
            ->groupBy(DB::raw('LOWER(module)'))
            ->pluck('total', 'module');

        $stats = [
            'total' => $visible()->count(),
            'critical' => $visible()->where('severity', 'Critical')->count(),
            'high' => $visible()->where('severity', 'High')->count(),
            'unread' => $visible()->unread()->count(),
            'today' => $visible()->whereDate('created_at', today())->count(),
        ];

        return view('pages.notifications', [
            'notifications' => $notifications,
            'counts' => $stats,
            'moduleCounts' => $moduleCounts,
            'filter' => $filter,
            'sort' => $sort,
            'search' => $search,
        ]);
    }

    /**
     * Get notifications list (API).
     */
    public function index(): JsonResponse
    {
        $userId = Auth::id();
        $this->syncNotifications($userId);

        return response()->json(
            Notification::visibleTo($userId)->orderByDesc('created_at')->get()
        );
    }

    /**
     * Re-run the alert checks and store the results (API).
     */
    public function generateNotifications(): JsonResponse
    {
        $this->syncNotifications(Auth::id());

        return response()->json(['success' => true, 'total' => Notification::visibleTo(Auth::id())->count()]);
    }

    /**
     * Get notification counts by severity.
     */
    public function getCount(): JsonResponse
    {
        $userId = Auth::id();
        $this->syncNotifications($userId);

        $base = fn () => Notification::visibleTo($userId);

        return response()->json([
            'total' => $base()->count(),
            'critical' => $base()->where('severity', 'Critical')->count(),
            'warning' => $base()->whereIn('severity', ['High', 'Medium'])->count(),
            'info' => $base()->whereIn('severity', ['Low', 'Info'])->count(),
        ]);
    }

    /**
     * Get unread notification count for badge.
     */
    public function getUnreadCount(): JsonResponse
    {
        $userId = Auth::id();
        $this->syncNotifications($userId);

        $unread = Notification::visibleTo($userId)->unread();

        return response()->json([
            'count' => (clone $unread)->count(),
            'has_critical' => (clone $unread)->where('severity', 'Critical')->exists(),
        ]);
    }

    /**
     * Mark one notification as read.
     */
    public function markRead(int $id): JsonResponse
    {
        $notification = Notification::where('user_id', Auth::id())->findOrFail($id);
        $notification->update(['is_read' => true, 'read_at' => $notification->read_at ?? now()]);

        return response()->json(['success' => true]);
    }

    /**
     * Mark all of the user's notifications as read.
     */
    public function markAllRead(): JsonResponse
    {
        Notification::visibleTo(Auth::id())->unread()->update(['is_read' => true, 'read_at' => now()]);

        return response()->json(['success' => true]);
    }

    /**
     * Dismiss (clear) one notification.
     */
    public function dismiss(int $id): JsonResponse
    {
        Notification::where('user_id', Auth::id())->findOrFail($id)->update(['dismissed_at' => now()]);

        return response()->json(['success' => true]);
    }

    /**
     * Dismiss (clear) all of the user's notifications.
     */
    public function dismissAll(): JsonResponse
    {
        Notification::visibleTo(Auth::id())->update(['dismissed_at' => now()]);

        return response()->json(['success' => true]);
    }

    /**
     * Bring back every notification the user has cleared.
     */
    public function restoreAll(): JsonResponse
    {
        Notification::where('user_id', Auth::id())->whereNotNull('dismissed_at')->update(['dismissed_at' => null]);

        return response()->json(['success' => true]);
    }

    /**
     * Compare the current system state with the stored notifications:
     * new alerts are inserted, changed alerts are refreshed (read / cleared
     * state is kept), and alerts whose condition is gone are removed.
     */
    private function syncNotifications(int $userId): void
    {
        $alerts = collect($this->buildAlerts())->keyBy('dedupe_key');

        $existing = Notification::where('user_id', $userId)
            ->whereNotNull('dedupe_key')
            ->get()
            ->keyBy('dedupe_key');

        foreach ($alerts as $key => $alert) {
            if ($row = $existing->get($key)) {
                $row->fill($alert)->save();
            } else {
                Notification::create($alert + ['user_id' => $userId]);
            }
        }

        $resolved = $existing->keys()->diff($alerts->keys());
        if ($resolved->isNotEmpty()) {
            Notification::where('user_id', $userId)->whereIn('dedupe_key', $resolved->all())->delete();
        }
    }

    /**
     * Build the list of alerts that currently apply, from live data.
     *
     * @return array<int, array<string, string>>
     */
    private function buildAlerts(): array
    {
        $alerts = [];

        // Low stock
        foreach (InventoryItem::lowStock()->get() as $item) {
            $alerts[] = [
                'dedupe_key' => 'low-stock-' . $item->id,
                'module' => 'Inventory',
                'source' => 'Inventory System',
                'severity' => 'Medium',
                'title' => 'Low Stock Alert',
                'subtitle' => 'Replenishment Needed',
                'message' => "{$item->name} is running low on stock. Current: {$item->stock_quantity} {$item->unit}, Reorder level: {$item->reorder_level} {$item->unit}",
                'action_label' => 'Restock Now',
                'action_link' => '/inventory',
            ];
        }

        // Out of stock
        foreach (InventoryItem::outOfStock()->get() as $item) {
            $alerts[] = [
                'dedupe_key' => 'out-of-stock-' . $item->id,
                'module' => 'Inventory',
                'source' => 'Inventory System',
                'severity' => 'Critical',
                'title' => 'Out of Stock',
                'subtitle' => 'Inventory System Alert',
                'message' => "{$item->name} is completely out of stock. Immediate restocking required.",
                'action_label' => 'Add Stock',
                'action_link' => '/inventory',
            ];
        }

        // Expiring within 7 days
        $expiring = InventoryBatch::with('inventoryItem')->where('status', 'available')->expiringSoon(7)->get();
        foreach ($expiring as $batch) {
            if (!$batch->inventoryItem) {
                continue;
            }
            $days = $batch->remaining_shelf_life;
            $alerts[] = [
                'dedupe_key' => 'expiring-' . $batch->id,
                'module' => 'Spoilage',
                'source' => 'Inventory System',
                'severity' => $days <= 3 ? 'Critical' : 'High',
                'title' => 'Product Expiring Soon',
                'subtitle' => 'Shelf-Life Monitor',
                'message' => "{$batch->inventoryItem->name} (Batch: {$batch->batch_code}) expires in {$days} day(s). Quantity: {$batch->quantity} {$batch->inventoryItem->unit}",
                'action_label' => 'View Details',
                'action_link' => '/spoilage',
            ];
        }

        // Already expired
        $expired = InventoryBatch::with('inventoryItem')->where('status', 'available')->expired()->get();
        foreach ($expired as $batch) {
            if (!$batch->inventoryItem) {
                continue;
            }
            $alerts[] = [
                'dedupe_key' => 'expired-' . $batch->id,
                'module' => 'Spoilage',
                'source' => 'Inventory System',
                'severity' => 'Critical',
                'title' => 'Expired Product',
                'subtitle' => 'Shelf-Life Monitor',
                'message' => "{$batch->inventoryItem->name} (Batch: {$batch->batch_code}) has expired. Quantity: {$batch->quantity} {$batch->inventoryItem->unit}. Remove from inventory.",
                'action_label' => 'Remove Stock',
                'action_link' => '/inventory',
            ];
        }

        // High value batches expiring within 14 days
        $highValue = InventoryBatch::with('inventoryItem')
            ->where('status', 'available')
            ->expiringSoon(14)
            ->get()
            ->filter(fn ($batch) => $batch->inventoryItem && $batch->quantity * $batch->price_per_unit > 5000);

        foreach ($highValue as $batch) {
            $value = $batch->quantity * $batch->price_per_unit;
            $alerts[] = [
                'dedupe_key' => 'high-value-expiring-' . $batch->id,
                'module' => 'Spoilage',
                'source' => 'Inventory System',
                'severity' => 'Medium',
                'title' => 'High Value Item Expiring',
                'subtitle' => 'Potential Loss Alert',
                'message' => "{$batch->inventoryItem->name} worth ₱" . number_format($value, 2) . " expires in {$batch->remaining_shelf_life} days. Consider discounting for quick sale.",
                'action_label' => 'Create Discount',
                'action_link' => '/pos',
            ];
        }

        // Forecast demand that the current stock cannot cover (next 7 days)
        if (Schema::hasTable('ai_forecasts')) {
            $demand = DB::table('ai_forecasts')
                ->whereBetween('forecast_date', [today()->toDateString(), today()->addDays(7)->toDateString()])
                ->select('fruit_name', DB::raw('SUM(predicted_sales_qty) as predicted'))
                ->groupBy('fruit_name')
                ->get();

            foreach ($demand as $row) {
                $item = InventoryItem::whereRaw('LOWER(name) = ?', [Str::lower($row->fruit_name)])->first();
                if (!$item || (float) $row->predicted <= (float) $item->stock_quantity) {
                    continue;
                }
                $predicted = (float) $row->predicted;
                $stock = (float) $item->stock_quantity;
                $alerts[] = [
                    'dedupe_key' => 'forecast-demand-' . $item->id,
                    'module' => 'Forecast',
                    'source' => 'SARIMAX',
                    'severity' => $stock < $predicted / 2 ? 'Critical' : 'High',
                    'title' => 'Demand Exceeds Available Stock',
                    'subtitle' => 'SARIMAX Forecast Alert',
                    'message' => "{$item->name} demand forecasted at " . number_format($predicted, 2) . " {$item->unit} over the next 7 days, but current stock is only " . number_format($stock, 2) . " {$item->unit}.",
                    'action_label' => 'Restock Now',
                    'action_link' => '/inventory',
                ];
            }
        }

        // Batches the XGBoost spoilage model rates High Risk or Critical today
        if (Schema::hasTable('spoilage_predictions')) {
            $latest = DB::table('spoilage_predictions')->max('prediction_date');
            $risky = $latest ? DB::table('spoilage_predictions as p')
                ->join('inventory_batches as b', 'b.id', '=', 'p.inventory_batch_id')
                ->join('inventory_items as i', 'i.id', '=', 'p.inventory_item_id')
                ->where('p.prediction_date', $latest)
                ->where('p.horizon_day', 0)
                ->whereIn('p.risk_level', ['Critical', 'High Risk'])
                ->where('b.status', 'available')
                ->where('b.quantity', '>', 0)
                ->select('p.inventory_batch_id', 'p.risk_score', 'p.risk_level', 'p.recommendation', 'b.batch_code', 'b.quantity', 'i.name', 'i.unit')
                ->get() : collect();

            foreach ($risky as $row) {
                $alerts[] = [
                    'dedupe_key' => 'spoilage-risk-' . $row->inventory_batch_id,
                    'module' => 'Spoilage',
                    'source' => 'XGBoost',
                    'severity' => $row->risk_level === 'Critical' ? 'Critical' : 'High',
                    'title' => 'High Spoilage Risk',
                    'subtitle' => 'XGBoost Spoilage Prediction',
                    'message' => "{$row->name} (Batch: {$row->batch_code}) has a predicted spoilage risk of " . number_format($row->risk_score, 1) . "% on {$row->quantity} {$row->unit}. {$row->recommendation}.",
                    'action_label' => 'View Spoilage',
                    'action_link' => '/spoilage',
                ];
            }
        }

        return $alerts;
    }
}
