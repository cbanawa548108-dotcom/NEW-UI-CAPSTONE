# Implementation Plan: Analytics Backend

This plan implements a complete Analytics backend with real database integration for the FreshTrack Laravel application. The analytics page currently has only hardcoded Chart.js data and no controller. This implementation will add a full AnalyticsController with 7 API endpoints that compute metrics from actual sales, inventory, and batch data.

## Data Sources & Calculation Rules

**Revenue** = SUM(SalesTransaction.total_amount) WHERE status='completed'  
**COGS** = SUM(SalesItem.quantity × InventoryBatch.price_per_unit) via inventoryBatch relation  
**Gross Profit** = Revenue - COGS  
**Sales Volume** = SUM(SalesItem.quantity) in kg  
**Waste Reduced %** = compare expired batch quantity current month vs 6-month average  
**Forecast Accuracy** = placeholder static value (no forecast model exists yet)  

All date grouping uses Carbon. Monthly aggregations use `DB::raw('YEAR(created_at) as year, MONTH(created_at) as month')` pattern from ReportsController.

---

## Implementation Steps

- [ ] 1. Create the AnalyticsController at app/Http/Controllers/AnalyticsController.php with page() method and all 7 data methods (getKPIs, getRevenueTrend, getSalesByFruit, getWeeklySales, getCumulativeRevenue, getWasteReduction, getStackedSales). Each method computes metrics from real database queries following the exact patterns in ReportsController.php: use SalesTransaction::completed(), whereHas for joins, Carbon for date math, DB::raw for grouping, and relations (inventoryBatch, inventoryItem). getKPIs calculates: total revenue (last 30 days completed sales sum), gross profit (revenue - COGS using inventoryBatch.price_per_unit), sales volume (SalesItem quantity sum), waste reduced % (compare current month expired batch qty vs 6-month average), and forecast accuracy (static 96.4% placeholder). getRevenueTrend returns last 6 months grouped by YEAR/MONTH with revenue, profit, cogs per month. getSalesByFruit groups SalesItem by inventory_item_id with inventoryItem relation, sums total_amount, returns top 4 + 'Others' with percentages. getWeeklySales groups last 8 weeks by YEARWEEK(). getCumulativeRevenue returns day-by-day cumulative sum for current month. getWasteReduction calculates monthly spoilage rate (expired batch qty / total batch qty) for last 6 months using InventoryBatch::expired() scope. getStackedSales returns last 6 months of revenue grouped by top 4 products (stacked format: array per month with product breakdown).  
      Files: app/Http/Controllers/AnalyticsController.php  
      Verify: Run `php artisan route:list --path=analytics` and confirm the controller is recognized (not required for validation but good sanity check). No automated tests exist for controllers in this project.

- [ ] 2. Update routes/web.php to replace the analytics closure route with AnalyticsController@page (using array syntax `[AnalyticsController::class, 'page']`) inside the existing `Route::middleware(['auth'])->group()` > `Route::middleware(['role:owner,manager'])->group()` block. The route should remain `Route::get('/analytics', ...)` with name 'analytics'. Add the use statement `use App\Http\Controllers\AnalyticsController;` at the top with the other controller imports.  
      Files: routes/web.php  
      Verify: Run `php artisan route:list --name=analytics` and confirm the route points to AnalyticsController@page with middleware auth,role:owner,manager.

- [ ] 3. Add 7 API routes in routes/web.php under the existing `Route::prefix('api')->middleware(['auth'])->group()` > `Route::middleware(['role:owner,manager'])->group()` block (same location as reports API routes). Add these routes: `GET /api/analytics/kpis` → getKPIs, `GET /api/analytics/revenue-trend` → getRevenueTrend, `GET /api/analytics/sales-by-fruit` → getSalesByFruit, `GET /api/analytics/weekly-sales` → getWeeklySales, `GET /api/analytics/cumulative-revenue` → getCumulativeRevenue, `GET /api/analytics/waste-reduction` → getWasteReduction, `GET /api/analytics/stacked-sales` → getStackedSales. Use route names like `api.analytics.kpis`, etc. Follow the exact pattern of the existing reports API routes (e.g., `Route::get('/reports/sales', [ReportsController::class, 'salesReport'])->name('api.reports.sales')`).  
      Files: routes/web.php  
      Verify: Run `php artisan route:list --path=api/analytics` and confirm all 7 routes exist with correct middleware (auth, role:owner,manager).

- [ ] 4. Update resources/views/pages/analytics.blade.php to add an Alpine.js x-data controller at the root div that fetches all 7 API endpoints in parallel on init using axios. The controller should have a data object with properties: loading (true initially), kpis, revenueTrend, salesByFruit, weeklySales, cumulativeRevenue, wasteReduction, stackedSales. The init() method calls all 7 endpoints using Promise.all, stores responses, sets loading=false, then initializes all Chart.js charts with the fetched data. Add a loading overlay (hidden when loading=false) using Alpine.js x-show="loading" with a spinner. Replace all hardcoded KPI values with Alpine.js bindings (x-text for values, computed properties for growth percentages). Replace all hardcoded Chart.js datasets in the @push('scripts') section with dynamic data from the Alpine.js controller. Use chart.data.labels = ... and chart.data.datasets[0].data = ... to update charts, or destroy and recreate charts after data loads. Add error handling: if an API call fails, show 'No data available' in that chart section. Keep the existing Chart.js configuration (colors, tension, borderRadius, etc.) but replace data arrays with fetched values. The Alpine.js controller should be named 'analyticsData' and accessed via $wire or direct property access in the Chart.js initialization. Follow the pattern: create charts in a function called after data loads, not in @push('scripts') DOMContentLoaded.  
      Files: resources/views/pages/analytics.blade.php  
      Verify: Start the Laravel dev server with `php artisan serve`, navigate to /analytics (login as owner@FreshTrack.ph / password), open browser DevTools Network tab, confirm all 7 API calls fire and return JSON (even if empty), confirm KPI cards show dynamic values (or 0 if no data), confirm charts render without JavaScript errors, confirm loading spinner shows briefly then hides.

- [ ] 5. Test the complete analytics flow with real data by seeding the database (if FRESHTRACK-COMPLETE-DATA.sql is already loaded, this step may be skipped) and verifying that: (1) KPIs display non-zero values computed from actual sales/inventory/batch records, (2) Revenue Trend chart shows last 6 months with real monthly totals, (3) Sales by Fruit pie chart shows actual product distribution, (4) Weekly Sales bar chart shows last 8 weeks of sales, (5) Cumulative Revenue line chart shows current month day-by-day accumulation, (6) Waste Reduction chart shows monthly spoilage rate trend, (7) Stacked Sales chart shows revenue breakdown by product per month. If any endpoint returns empty data due to missing records, add sample transactions/batches via the app's POS and Inventory pages, then refresh /analytics. If any calculation is incorrect (e.g., negative profit, >100% percentages), debug the SQL queries in AnalyticsController by adding `->toSql()` or `DB::enableQueryLog()` and fix the aggregation logic. This step is exploratory verification, not an automated test.  
      Files: (no file changes, testing only)  
      Verify: Manual testing as described above. Document any data issues found and their resolutions in the commit message. No automated test command exists for this step.

---

## Technical Notes

- **Controller Pattern**: Follow DashboardController.php and ReportsController.php exactly — use model scopes (completed(), expired(), scopeDateRange()), eager load relations (with()), use DB::raw() for date grouping, return response()->json() with rounded decimals.
- **Query Efficiency**: Use whereHas for filtering joined tables, groupBy for aggregations, limit() for top-N queries. Avoid N+1 by eager loading inventoryItem and inventoryBatch relations.
- **Date Handling**: All Carbon date math follows Laravel conventions: `now()->subMonths(6)`, `startOfMonth()`, `endOfMonth()`, `diffInDays()`, `format('Y-m-d')`.
- **Null Safety**: Filter out null relations (e.g., `$item->inventoryItem !== null`) after queries to prevent blade errors, following the pattern in DashboardController@getDashboardMetrics.
- **Frontend Integration**: Alpine.js is already included in the project (used in other blade files). Axios is available globally. Chart.js is already loaded in analytics.blade.php.
- **No Tests Required**: This Laravel project has no controller tests (only example tests exist). Verification is manual via browser testing and route:list commands.
- **Data Availability**: If the database has insufficient sales/inventory data, some charts will show empty states. This is expected and handled gracefully with 'No data available' messages.
- **Forecast Accuracy**: Currently a placeholder static value (96.4%) because no forecasting model exists. This can be replaced later when forecasting is implemented.

---

## Verification Commands

After each step, run the specified verification command. The project uses:
- **Build**: No build step required (Laravel auto-reloads)
- **Test**: `composer test` (runs PHPUnit, but no controller tests exist)
- **Routes**: `php artisan route:list` to verify route registration
- **Serve**: `php artisan serve` (runs on http://127.0.0.1:8000)

The final verification is manual: navigate to http://127.0.0.1:8000/analytics and confirm all charts load with real data.
