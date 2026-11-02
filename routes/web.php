<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\ForecastController;

// Landing
Route::get('/', fn() => view('landing'))->name('landing');

// Auth test page (remove this after fixing)
Route::get('/test-auth', function() {
    $results = [
        'db_connected' => false,
        'users_found' => 0,
        'users' => [],
        'test_user' => null,
        'password_works' => false,
        'all_passwords_work' => false
    ];
    
    try {
        // Test database
        DB::connection()->getPdo();
        $results['db_connected'] = true;
        
        // Get users
        $users = DB::table('users')
            ->whereIn('email', ['owner@FreshTrack.ph', 'manager@FreshTrack.ph', 'cashier@FreshTrack.ph'])
            ->get();
        
        $results['users_found'] = $users->count();
        
        // Test each password
        foreach ($users as $user) {
            $works = Hash::check('password', $user->password);
            $results['users'][] = [
                'email' => $user->email,
                'role' => $user->role,
                'hash' => substr($user->password, 0, 40),
                'works' => $works
            ];
        }
        
        // Test owner account specifically
        $testUser = \App\Models\User::where('email', 'owner@FreshTrack.ph')->first();
        if ($testUser) {
            $results['test_user'] = [
                'email' => $testUser->email,
                'name' => $testUser->name,
                'role' => $testUser->role
            ];
            $results['password_works'] = Hash::check('password', $testUser->password);
        }
        
        // Check if all passwords work
        $results['all_passwords_work'] = count(array_filter($results['users'], fn($u) => $u['works'])) === count($results['users']);
        
    } catch (\Exception $e) {
        $results['error'] = $e->getMessage();
    }
    
    return view('test-auth', ['results' => $results]);
});

// Auth routes
Route::middleware(['guest'])->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Protected routes - require authentication
Route::middleware(['auth'])->group(function () {
    
    // Owner only routes
    Route::middleware(['role:owner'])->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
        Route::get('/users', [\App\Http\Controllers\UserController::class, 'page'])->name('users');
        Route::get('/decision-support', fn() => view('pages.decision-support'))->name('decision-support');
    });
    
    // Owner + Manager routes
    Route::middleware(['role:owner,manager'])->group(function () {
        Route::get('/inventory', [\App\Http\Controllers\InventoryController::class, 'page'])->name('inventory');
        Route::get('/sales', [\App\Http\Controllers\SalesController::class, 'page'])->name('sales');
        Route::get('/forecast', [ForecastController::class, 'forecastPage'])->name('forecast');
        Route::get('/spoilage', [ForecastController::class, 'spoilagePage'])->name('spoilage');
        Route::get('/analytics', [AnalyticsController::class, 'page'])->name('analytics');
        Route::get('/reports', [\App\Http\Controllers\ReportsController::class, 'page'])->name('reports');
    });
    
    // All authenticated users (Owner + Manager + Cashier)
    Route::get('/pos', [\App\Http\Controllers\SalesController::class, 'pos'])->name('pos');
    Route::get('/notifications', [\App\Http\Controllers\NotificationsController::class, 'page'])->name('notifications');
    Route::get('/settings', fn() => view('pages.settings'))->name('settings');
});

// API Routes (AJAX endpoints for all modules)
Route::prefix('api')->middleware(['auth'])->group(function () {
    
    // Owner + Manager routes
    Route::middleware(['role:owner,manager'])->group(function () {
        
        // Products API
        Route::get('/products', [ProductController::class, 'index'])->name('api.products.index');
        Route::post('/products', [ProductController::class, 'store'])->name('api.products.store');
        Route::get('/products/{id}', [ProductController::class, 'show'])->name('api.products.show');
        Route::put('/products/{id}', [ProductController::class, 'update'])->name('api.products.update');
        Route::delete('/products/{id}', [ProductController::class, 'destroy'])->name('api.products.destroy');
        Route::get('/products/categories/list', [ProductController::class, 'categories'])->name('api.products.categories');
        
        // Inventory API
        Route::get('/inventory', [\App\Http\Controllers\InventoryController::class, 'index'])->name('api.inventory.index');
        Route::post('/inventory/stock-in', [\App\Http\Controllers\InventoryController::class, 'stockIn'])->name('api.inventory.stock-in');
        Route::post('/inventory/stock-out', [\App\Http\Controllers\InventoryController::class, 'stockOut'])->name('api.inventory.stock-out');
        Route::post('/inventory/stock-adjustment', [\App\Http\Controllers\InventoryController::class, 'stockAdjustment'])->name('api.inventory.stock-adjustment');
        Route::get('/inventory/stats', [\App\Http\Controllers\InventoryController::class, 'getStats'])->name('api.inventory.stats');
        Route::get('/inventory/low-stock', [\App\Http\Controllers\InventoryController::class, 'getLowStock'])->name('api.inventory.low-stock');
        Route::get('/inventory/expiring', [\App\Http\Controllers\InventoryController::class, 'getExpiringItems'])->name('api.inventory.expiring');
        
        // Sales Management API
        Route::get('/sales', [\App\Http\Controllers\SalesController::class, 'index'])->name('api.sales.index');
        Route::get('/sales/stats', [\App\Http\Controllers\SalesController::class, 'getStats'])->name('api.sales.stats');
        Route::get('/sales/daily', [\App\Http\Controllers\SalesController::class, 'getDailySales'])->name('api.sales.daily');
        
        // Reports API
        Route::get('/reports/sales', [\App\Http\Controllers\ReportsController::class, 'salesReport'])->name('api.reports.sales');
        Route::get('/reports/inventory', [\App\Http\Controllers\ReportsController::class, 'inventoryReport'])->name('api.reports.inventory');
        Route::get('/reports/expiry', [\App\Http\Controllers\ReportsController::class, 'expiryReport'])->name('api.reports.expiry');
        Route::get('/reports/profit-loss', [\App\Http\Controllers\ReportsController::class, 'profitLossReport'])->name('api.reports.profit-loss');
        
        // Reports PDF Export
        Route::get('/reports/sales/pdf', [\App\Http\Controllers\ReportsController::class, 'exportSalesPDF'])->name('api.reports.sales.pdf');
        
        // Analytics API
        Route::get('/analytics/kpis', [AnalyticsController::class, 'getKPIs'])->name('api.analytics.kpis');
        Route::get('/analytics/revenue-trend', [AnalyticsController::class, 'getRevenueTrend'])->name('api.analytics.revenue-trend');
        Route::get('/analytics/sales-by-fruit', [AnalyticsController::class, 'getSalesByFruit'])->name('api.analytics.sales-by-fruit');
        Route::get('/analytics/weekly-sales', [AnalyticsController::class, 'getWeeklySales'])->name('api.analytics.weekly-sales');
        Route::get('/analytics/cumulative-revenue', [AnalyticsController::class, 'getCumulativeRevenue'])->name('api.analytics.cumulative-revenue');
        Route::get('/analytics/waste-reduction', [AnalyticsController::class, 'getWasteReduction'])->name('api.analytics.waste-reduction');
        Route::get('/analytics/stacked-sales', [AnalyticsController::class, 'getStackedSales'])->name('api.analytics.stacked-sales');

        // AI models (SARIMAX sales forecast, XGBoost spoilage) + OpenWeather
        Route::get('/forecast/data', [ForecastController::class, 'forecastData'])->name('api.forecast.data');
        Route::get('/spoilage/data', [ForecastController::class, 'spoilageData'])->name('api.spoilage.data');
        Route::post('/ml/run', [ForecastController::class, 'run'])->name('api.ml.run');
        Route::get('/weather', [ForecastController::class, 'weather'])->name('api.weather');
    });
    
    // Owner only routes
    Route::middleware(['role:owner'])->group(function () {
        // User Management API
        Route::post('/users', [\App\Http\Controllers\UserController::class, 'store'])->name('api.users.store');
        Route::put('/users/{user}', [\App\Http\Controllers\UserController::class, 'update'])->name('api.users.update');
        Route::post('/users/{user}/archive', [\App\Http\Controllers\UserController::class, 'archive'])->name('api.users.archive');
        Route::post('/users/{user}/restore', [\App\Http\Controllers\UserController::class, 'restore'])->name('api.users.restore');

        // Dashboard API
        Route::get('/dashboard/metrics', [\App\Http\Controllers\DashboardController::class, 'getDashboardMetrics'])->name('api.dashboard.metrics');
        Route::get('/dashboard/sales-trend', [\App\Http\Controllers\DashboardController::class, 'getSalesTrend'])->name('api.dashboard.sales-trend');
        Route::get('/dashboard/inventory-health', [\App\Http\Controllers\DashboardController::class, 'getInventoryHealth'])->name('api.dashboard.inventory-health');
    });
    
    // All authenticated users (Owner + Manager + Cashier) - POS and Notifications
    Route::get('/pos/products', [\App\Http\Controllers\SalesController::class, 'getAvailableProducts'])->name('api.pos.products');
    Route::post('/pos/sale', [\App\Http\Controllers\SalesController::class, 'createSale'])->name('api.pos.sale');
    
    Route::get('/notifications', [\App\Http\Controllers\NotificationsController::class, 'index'])->name('api.notifications.index');
    Route::post('/notifications/generate', [\App\Http\Controllers\NotificationsController::class, 'generateNotifications'])->name('api.notifications.generate');
    Route::get('/notifications/count', [\App\Http\Controllers\NotificationsController::class, 'getCount'])->name('api.notifications.count');
    Route::get('/notifications/unread-count', [\App\Http\Controllers\NotificationsController::class, 'getUnreadCount'])->name('api.notifications.unread-count');
    Route::post('/notifications/read-all', [\App\Http\Controllers\NotificationsController::class, 'markAllRead'])->name('api.notifications.read-all');
    Route::post('/notifications/dismiss-all', [\App\Http\Controllers\NotificationsController::class, 'dismissAll'])->name('api.notifications.dismiss-all');
    Route::post('/notifications/restore', [\App\Http\Controllers\NotificationsController::class, 'restoreAll'])->name('api.notifications.restore');
    Route::post('/notifications/{id}/read', [\App\Http\Controllers\NotificationsController::class, 'markRead'])->name('api.notifications.read');
    Route::post('/notifications/{id}/dismiss', [\App\Http\Controllers\NotificationsController::class, 'dismiss'])->name('api.notifications.dismiss');
});
