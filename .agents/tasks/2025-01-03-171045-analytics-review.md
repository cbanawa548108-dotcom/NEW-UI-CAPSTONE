# Analytics Backend with Real-Time Dashboard Integration

This change implements a complete analytics backend for FreshTrack's Laravel application: a new AnalyticsController with 8 methods (1 page method + 7 data endpoints), route integration with proper auth/role middleware, and frontend Alpine.js + Chart.js wiring that fetches live data from the API. The analytics page previously rendered only hardcoded Chart.js datasets; now it computes KPIs, revenue trends, product distribution, waste metrics, and cumulative financials from actual SalesTransaction, SalesItem, InventoryBatch, and InventoryItem records.

The implementation follows the established ReportsController query patterns: uses SalesTransaction::completed() scope, eager-loads relations with with(), groups dates with DB::raw('YEAR(created_at)..., MONTH(created_at)...'), and joins via whereHas. COGS are calculated by summing SalesItem.quantity × InventoryBatch.price_per_unit. All 7 API routes are protected by auth + role:owner,manager middleware. The frontend uses Alpine.js x-data controller with Promise.all parallel fetching, dynamic KPI bindings with x-text, and Chart.js instances that destroy/rebuild after data loads.

**Watch for:** Revenue change percentage calculation uses questionable date range (last month vs 2 months ago, not last 30 days vs prior 30 days) (**confirmed**). Waste reduction KPI embeds a broken nested SQL subquery that will fail at runtime (**confirmed**). Weekly sales grouping uses mismatched YEARWEEK formats between query and matching loop, causing silent week alignment errors (**likely**). Cumulative revenue mutates Carbon instance before calculating day count (**confirmed**). Frontend fetches 7 endpoints but KPI growth badges remain hardcoded, creating inconsistent UI (**confirmed**).

**Verdict**: NEEDS_CHANGES

---

## High-level view

The controller defines all 8 required methods and follows the established query patterns from ReportsController: SalesTransaction::completed() filtering, eager loading with with(), DB::raw date grouping, and whereHas joins. The route definitions match the plan exactly — web route points to AnalyticsController::page, and all 7 API routes sit under /api/analytics/* with auth + role middleware. The frontend Alpine.js controller fetches all 7 endpoints in parallel and passes data to initCharts(), which instantiates Chart.js with the fetched arrays.

The KPI calculations have multiple structural issues. The revenue/profit change percentages compare "last month" (1 month ago) against "previous month" (2 months ago), not the current reporting period (last 30 days) against the prior 30 days, which breaks semantic expectation when a user sees "Revenue" on the dashboard. The waste reduction calculation embeds a raw SQL subquery inside a selectRaw with manual binding that won't execute correctly in Laravel's query builder context. The cumulative revenue method calls endOfDay() which mutates $today before it's used in the diffInDays calculation, producing wrong day counts.

Weekly sales uses YEARWEEK(created_at, 1) in the SQL query but then attempts to match it with $date->format('oW'), which is ISO-8601 week format and doesn't align with MySQL's YEARWEEK output. The mismatch means the loop will almost never find the correct week's data in the result set, so most weeks will show 0 even when sales exist.

The frontend correctly fetches all 7 endpoints and binds most KPIs dynamically, but the growth badges (↑ 11.2%, ↑ 23.4%, ↑ 1.2%) for sales volume, waste reduced, and forecast accuracy are still hardcoded in the blade template. This creates a disconnect where the main KPI values update but the change indicators never do.

<details>
<summary>Issues (9)</summary>

1. **KPI date range mismatch** — getKPIs calculates "last month" revenue (1 month ago) vs "previous month" (2 months ago) instead of last 30 days vs prior 30 days. Change to Carbon::now()->subDays(30) and subDays(60) for consistent rolling windows.

2. **Broken waste reduction SQL** — Lines 115-124 embed a raw subquery with manual setBindings that doesn't execute in Laravel. Rewrite using a separate subquery or calculate in PHP after fetching monthly grouped data.

3. **Weekly sales grouping mismatch** — Query uses YEARWEEK(created_at, 1) but matching loop uses $date->format('oW'). These formats are incompatible. Use consistent format or parse YEARWEEK result with Carbon::createFromFormat.

4. **Cumulative revenue date mutation** — Line 430 calls $today->endOfDay() in whereBetween, which mutates $today, then line 432 uses the mutated $today in diffInDays($startOfMonth), producing wrong day count. Move diffInDays calculation before the query or use $today->copy()->endOfDay().

5. **Hardcoded frontend growth badges** — Lines with "↑ 11.2%", "↑ 23.4%", "↑ 1.2%" are static. Bind to kpis.volume_change_pct, kpis.waste_change_pct, kpis.forecast_change_pct (add these fields to getKPIs response).

6. **COGS calculation ignores null batches silently** — Lines 48-53 use $item->inventoryBatch?->price_per_unit ?? 0, which means items with no batch relation contribute 0 COGS. This silently undercounts COGS. Filter out nulls with ->filter() before summing, or log a warning.

7. **getSalesByFruit returns unused amount field** — Method returns amount field that frontend doesn't use. Remove it or add it to the legend display.

8. **Empty data messages never show** — Frontend has no x-show conditions for "No data available" when API returns empty arrays. Charts will render blank. Add conditional messaging in blade for each chart section.

9. **Stacked sales assumes 6 months of data** — If a new product only has 2 months of data, the array still has 6 elements (with leading zeros). Document this behavior or add a data completeness note in the UI.

</details>

<details>
<summary>Details</summary>

## COGS calculation has silent null handling gap

Lines 48-53 fetch all SalesItems with completed transactions, eager-load inventoryBatch relation, then sum quantity × price_per_unit. The null-coalescing operator ($item->inventoryBatch?->price_per_unit ?? 0) means if a SalesItem has no inventoryBatch relation (orphaned FK or missing batch), it contributes 0 to COGS instead of causing an error. This is safe for runtime but wrong for accounting — a $500 sale with no batch should either error or be logged, not silently treated as zero-cost. The same pattern repeats in lastMonthCogs and previousMonthCogs calculations (lines 62-80).

## KPI revenue/profit change percentages use wrong date windows

Lines 28-35 define lastMonth as 1 month ago (subMonth()) and previousMonth as 2 months ago (subMonths(2)). This means the dashboard KPI labeled "Revenue" with change percentage is comparing month-3 to month-2, not the last 30 days to the prior 30 days. If today is January 15, the user sees December vs November revenue change, not Jan 1-15 + Dec 16-31 vs Nov 16-Dec 15. The plan document says "last 30 days completed sales" for total revenue, but the change percentage uses calendar months.

## Waste reduction KPI has broken SQL subquery

Lines 115-124 attempt to calculate 6-month average expired quantity using a selectRaw with a nested raw subquery, then call setBindings([$sixMonthsAgo, $currentMonth->startOfMonth()]). This pattern doesn't work in Laravel's query builder because setBindings() replaces all bindings globally, but the from(DB::raw('...')) isn't a valid query context for selectRaw. The query will fail with a SQL syntax error or binding count mismatch. The correct approach is either: (1) use DB::select() with raw SQL and bindings, then extract the scalar result, or (2) fetch monthly grouped data separately, then calculate average in PHP. Example fix:

```php
$monthlyExpired = InventoryBatch::where('expiry_date', '>=', $sixMonthsAgo)
    ->where('expiry_date', '<', $currentMonth->startOfMonth())
    ->where(fn($q) => $q->where('status', 'expired')->orWhere('expiry_date', '<', now()))
    ->selectRaw('YEAR(expiry_date) as y, MONTH(expiry_date) as m, SUM(quantity) as qty')
    ->groupBy('y', 'm')
    ->get();
$avgExpiredLast6Months = $monthlyExpired->avg('qty');
```

## Weekly sales grouping uses incompatible date formats

Line 398 uses YEARWEEK(created_at, 1) which returns an integer like 202448 (year 2024, week 48). Line 416 uses $date->format('oW') which returns ISO-8601 week as "202448" (string with zero-padding). The match loop at line 417-422 compares $yearWeek (string "202448") against $week (integer 202448 from DB), which works due to PHP type juggling, BUT the setISODate call on line 418 uses substr($week, 0, 4) and substr($week, 4), which assumes $week is a string. If MySQL returns an integer, substr will fail. Then isSameWeek() may produce false negatives if mode 1 (Monday-start) vs ISO-8601 (also Monday-start but different year boundary) diverge at year edges.

## Cumulative revenue mutates Carbon instance before day count calculation

Line 428 calls $today = Carbon::now(), line 429 calls $startOfMonth = Carbon::now()->startOfMonth(). Line 430 uses whereBetween('created_at', [$startOfMonth, $today->endOfDay()]) — endOfDay() mutates $today. Then line 432 calculates $daysInMonth = $today->diffInDays($startOfMonth) + 1, but $today is now end-of-day, so diffInDays produces the wrong result (likely off by 1). Fix: calculate $daysInMonth before the whereBetween call, or use $today->copy()->endOfDay() in the query.

## KPI cards bind dynamically but growth badges remain hardcoded

Lines 34, 44 use x-text="kpis ? '↑ ' + kpis.revenue_change_pct + '%' : '—'" which dynamically shows the revenue and profit change percentages from the API. But line 54 for sales volume, line 64 for waste reduced, and line 74 for forecast accuracy have static badges: <span class="badge badge-green text-[10.5px] mt-1.5">↑ 11.2%</span>, similar for 23.4% and 1.2%. These should be x-text bound to kpis.volume_change_pct, kpis.waste_change_pct, kpis.forecast_change_pct fields, which don't exist in the current getKPIs response.

## getSalesByFruit returns unused amount field

Line 344 adds 'amount' => round($item->total_sales, 2) to each result object, but the frontend pie chart only reads name, percentage, and color. Remove it or add it to the legend display if useful for debugging.

## Empty data handling gaps

The try/catch blocks return empty structures on errors, which prevents 500 errors but silently hides database issues. The frontend has no conditional "No data available" messages — if all charts return empty arrays, the user sees blank canvas elements with axes but no explanation. Add x-show="!salesByFruit.length" messages or skeleton loaders.

</details>

---

## File map

<details>
<summary>Files changed (3)</summary>

- **app/Http/Controllers/AnalyticsController.php** — New controller with page() + 7 API methods: getKPIs, getRevenueTrend, getSalesByFruit, getWeeklySales, getCumulativeRevenue, getWasteReduction, getStackedSales
- **routes/web.php** — Added AnalyticsController import, updated analytics web route to use controller, added 7 /api/analytics/* routes under auth + role:owner,manager middleware
- **resources/views/pages/analytics.blade.php** — Added Alpine.js x-data="analyticsController()" with init() fetching 7 endpoints, dynamic KPI bindings, and Chart.js initCharts() method that destroys/rebuilds charts with API data

[View full diff](git diff main)

</details>
