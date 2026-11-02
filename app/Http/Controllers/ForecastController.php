<?php

namespace App\Http\Controllers;

use App\Models\WeatherData;
use App\Services\MLService;
use App\Services\WeatherService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Serves the SARIMAX sales forecast and XGBoost spoilage predictions to the
 * Forecast and Spoilage pages, and lets owners/managers retrain on demand.
 */
class ForecastController extends Controller
{
    public function forecastPage()
    {
        return view('pages.forecast');
    }

    public function spoilagePage()
    {
        return view('pages.spoilage');
    }

    /**
     * Retrain and run the models (and refresh the OpenWeather forecast first).
     */
    public function run(Request $request, MLService $ml, WeatherService $weather): JsonResponse
    {
        $validated = $request->validate(['model' => 'required|in:sales,spoilage,all']);
        set_time_limit(600);

        $weatherError = null;
        if ($weather->isConfigured()) {
            try {
                $weather->syncForecast();
                Cache::forget('weather.current');
            } catch (\Throwable $e) {
                $weatherError = $e->getMessage();
            }
        } else {
            $weatherError = 'OPENWEATHER_API_KEY is not set; using stored/average weather.';
        }

        $results = [];
        foreach (['sales' => 'runSalesForecast', 'spoilage' => 'runSpoilagePrediction'] as $key => $method) {
            if ($validated['model'] === $key || $validated['model'] === 'all') {
                try {
                    $results[$key] = $ml->{$method}();
                } catch (\Throwable $e) {
                    $results[$key] = ['success' => false, 'message' => $e->getMessage()];
                }
            }
        }

        $success = collect($results)->every(fn ($r) => $r['success'] ?? false);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Models updated' : collect($results)->pluck('message')->filter()->implode(' | '),
            'weather_warning' => $weatherError,
            'results' => $results,
        ], $success ? 200 : 422);
    }

    /**
     * Data for the AI Sales Forecast page.
     */
    public function forecastData(): JsonResponse
    {
        $today = $this->today();
        $run = $this->latestRun('SARIMAX');

        $forecasts = DB::table('ai_forecasts as f')
            ->leftJoin('inventory_items as i', 'i.id', '=', 'f.inventory_item_id')
            ->where('f.forecast_date', '>=', $today->toDateString())
            ->orderBy('f.forecast_date')
            ->select('f.*', 'i.price_per_unit', 'i.stock_quantity', 'i.unit')
            ->get();

        if ($forecasts->isEmpty()) {
            return response()->json(['success' => true, 'has_forecast' => false, 'run' => $run, 'weather' => $this->weatherBlock($today)]);
        }

        // Daily totals with revenue confidence band
        $daily = $forecasts->groupBy('forecast_date')->map(fn ($rows, $date) => [
            'date' => $date,
            'label' => Carbon::parse($date)->format('D M j'),
            'qty' => round($rows->sum('predicted_sales_qty'), 2),
            'revenue' => round($rows->sum('predicted_revenue'), 2),
            'revenue_lower' => round($rows->sum(fn ($r) => $r->lower_bound * $r->price_per_unit), 2),
            'revenue_upper' => round($rows->sum(fn ($r) => $r->upper_bound * $r->price_per_unit), 2),
            'temperature' => $this->floatOrNull($rows->first()->temperature),
            'humidity' => $this->floatOrNull($rows->first()->humidity),
            'rainfall' => $this->floatOrNull($rows->first()->rainfall),
        ])->values();

        $horizon = $daily->count();

        // Actual sales for the same number of days before today, aligned to the forecast days
        $actuals = $this->dailySales($today->copy()->subDays($horizon), $today->copy()->subDay());
        $lastPeriod = collect(range($horizon, 1))->map(fn ($n) => round($actuals['revenue'][$today->copy()->subDays($n)->toDateString()] ?? 0, 2));
        $recentQty = $this->recentQtyByItem($today, 7);

        $fruits = $forecasts->groupBy('fruit_name')->map(function ($rows, $name) use ($recentQty) {
            $first = $rows->first();
            $current = round(($recentQty[$first->inventory_item_id] ?? 0) / 7, 2);
            $predicted = round($rows->avg('predicted_sales_qty'), 2);
            $total = round($rows->sum('predicted_sales_qty'), 2);
            $delta = round($predicted - $current, 2);
            $pct = $current > 0 ? $delta / $current * 100 : ($predicted > 0 ? 100 : 0);

            return [
                'fruit' => $name,
                'unit' => $first->unit ?? 'kg',
                'current_per_day' => $current,
                'predicted_per_day' => $predicted,
                'predicted_total' => $total,
                'lower_total' => round($rows->sum('lower_bound'), 2),
                'upper_total' => round($rows->sum('upper_bound'), 2),
                'delta' => $delta,
                'trend' => $pct > 5 ? 'up' : ($pct < -5 ? 'down' : 'stable'),
                'accuracy' => (float) $first->forecast_accuracy,
                'model' => $first->model,
                'stock' => (float) $first->stock_quantity,
                'recommendation' => $this->stockRecommendation($total, (float) $first->stock_quantity, $pct, $first->unit ?? 'kg'),
            ];
        })->sortByDesc('predicted_total')->values();

        $predictedRevenue = $daily->sum('revenue');
        $lastRevenue = $lastPeriod->sum();
        $change = $lastRevenue > 0 ? ($predictedRevenue - $lastRevenue) / $lastRevenue * 100 : null;

        return response()->json([
            'success' => true,
            'has_forecast' => true,
            'run' => $run,
            'summary' => [
                'horizon' => $horizon,
                'predicted_qty' => round($daily->sum('qty'), 2),
                'predicted_revenue' => round($predictedRevenue, 2),
                'revenue_change_pct' => $change === null ? null : round($change, 1),
                'demand_level' => $change === null ? 'New' : ($change > 10 ? 'High' : ($change < -10 ? 'Low' : 'Normal')),
                'accuracy' => $run['metrics']['overall_accuracy'] ?? null,
                'generated_at' => Carbon::parse($forecasts->max('created_at'))->toIso8601String(),
            ],
            'daily' => $daily,
            'last_period' => $lastPeriod,
            'fruits' => $fruits,
            'weather' => $this->weatherBlock($today),
        ]);
    }

    /**
     * Data for the Spoilage Prediction page.
     */
    public function spoilageData(): JsonResponse
    {
        $run = $this->latestRun('XGBoost');
        $date = DB::table('spoilage_predictions')->max('prediction_date');

        if (!$date) {
            return response()->json(['success' => true, 'has_predictions' => false, 'run' => $run, 'weather' => $this->weatherBlock($this->today())]);
        }

        $rows = DB::table('spoilage_predictions as p')
            ->join('inventory_batches as b', 'b.id', '=', 'p.inventory_batch_id')
            ->join('inventory_items as i', 'i.id', '=', 'p.inventory_item_id')
            ->where('p.prediction_date', $date)
            ->where('b.status', 'available')
            ->where('b.quantity', '>', 0)
            ->select('p.*', 'b.batch_code', 'b.quantity', 'b.expiry_date', 'b.received_date', 'i.name as fruit', 'i.unit', 'i.price_per_unit')
            ->orderBy('p.horizon_day')
            ->get();

        $current = $rows->where('horizon_day', 0)->sortByDesc('risk_score')->values()->map(fn ($r) => [
            'batch_id' => $r->inventory_batch_id,
            'fruit' => $r->fruit,
            'batch_code' => $r->batch_code,
            'quantity' => (float) $r->quantity,
            'unit' => $r->unit,
            'value_at_risk' => round($r->quantity * $r->price_per_unit * $r->risk_score / 100, 2),
            'expiry_date' => $r->expiry_date,
            'stock_age_days' => (float) $r->stock_age_days,
            'remaining_shelf_life_days' => $this->floatOrNull($r->remaining_shelf_life_days),
            'turnover_rate' => (float) $r->turnover_rate,
            'sales_trend' => (float) $r->sales_trend,
            'temperature' => $this->floatOrNull($r->temperature),
            'humidity' => $this->floatOrNull($r->humidity),
            'rainfall' => $this->floatOrNull($r->rainfall),
            'risk_score' => (float) $r->risk_score,
            'risk_level' => $r->risk_level,
            'recommendation' => $r->recommendation,
        ]);

        $levels = ['Critical', 'High Risk', 'Moderate', 'Low Risk', 'Fresh'];
        $counts = collect($levels)->mapWithKeys(fn ($l) => [$l => $current->where('risk_level', $l)->count()]);

        // 7-day projection for the riskiest batches
        $timeline = $current->take(5)->map(fn ($b) => [
            'label' => "{$b['fruit']} {$b['batch_code']}",
            'data' => $rows->where('inventory_batch_id', $b['batch_id'])->sortBy('horizon_day')->pluck('risk_score')->map(fn ($v) => (float) $v)->values(),
        ])->values();

        return response()->json([
            'success' => true,
            'has_predictions' => true,
            'run' => $run,
            'prediction_date' => $date,
            'summary' => [
                'counts' => $counts,
                'avg_risk' => round($current->avg('risk_score') ?? 0, 1),
                'value_at_risk' => round($current->sum('value_at_risk'), 2),
                'batches' => $current->count(),
            ],
            'batches' => $current,
            'timeline' => $timeline,
            'horizon_labels' => $rows->pluck('horizon_day')->unique()->sort()->values()
                ->map(fn ($h) => $h === 0 ? 'Today' : Carbon::parse($date)->addDays($h)->format('D M j')),
            'weather' => $this->weatherBlock($this->today()),
        ]);
    }

    public function weather(): JsonResponse
    {
        return response()->json(['success' => true] + $this->weatherBlock($this->today()));
    }

    private function weatherBlock(Carbon $today): array
    {
        $current = null;
        $error = null;
        $service = app(WeatherService::class);

        if ($service->isConfigured()) {
            try {
                $current = Cache::remember('weather.current', now()->addMinutes(30), fn () => $service->current());
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }
        } else {
            $error = 'OPENWEATHER_API_KEY is not set';
        }

        $days = WeatherData::where('date', '>=', $today->toDateString())->orderBy('date')->limit(7)->get()
            ->map(fn ($w) => [
                'date' => $w->date->toDateString(),
                'label' => $w->date->format('D'),
                'temperature' => $w->temperature,
                'humidity' => $w->humidity,
                'rainfall' => $w->rainfall,
                'condition' => $w->condition,
            ]);

        return ['current' => $current, 'forecast' => $days, 'error' => $error, 'city' => config('services.openweather.city')];
    }

    private function latestRun(string $model): ?array
    {
        $run = DB::table('ml_model_runs')->where('model', $model)->latest('id')->first();
        if (!$run) {
            return null;
        }

        return [
            'status' => $run->status,
            'message' => $run->message,
            'training_rows' => $run->training_rows,
            'metrics' => json_decode($run->metrics ?? 'null', true),
            'trained_at' => Carbon::parse($run->created_at)->toIso8601String(),
        ];
    }

    /** Actual daily sales (local dates) between two dates inclusive. */
    private function dailySales(Carbon $from, Carbon $to): array
    {
        $offset = Carbon::now(config('services.openweather.timezone'))->format('P');
        $appOffset = Carbon::now(config('app.timezone'))->format('P');

        $rows = DB::table('sales_transactions as st')
            ->join('sales_items as si', 'si.sale_transaction_id', '=', 'st.id')
            ->where('st.type', 'sale')->where('st.status', 'completed')->where('si.quantity', '>', 0)
            ->whereRaw('DATE(CONVERT_TZ(st.created_at, ?, ?)) BETWEEN ? AND ?', [$appOffset, $offset, $from->toDateString(), $to->toDateString()])
            ->selectRaw('DATE(CONVERT_TZ(st.created_at, ?, ?)) as d, SUM(si.total_amount) as revenue', [$appOffset, $offset])
            ->groupBy('d')
            ->pluck('revenue', 'd');

        return ['revenue' => $rows->map(fn ($v) => (float) $v)->all()];
    }

    private function recentQtyByItem(Carbon $today, int $days): array
    {
        $since = $today->copy()->subDays($days)->setTimezone(config('app.timezone'));
        $until = $today->copy()->setTimezone(config('app.timezone'));

        return DB::table('sales_transactions as st')
            ->join('sales_items as si', 'si.sale_transaction_id', '=', 'st.id')
            ->where('st.type', 'sale')->where('st.status', 'completed')->where('si.quantity', '>', 0)
            ->whereBetween('st.created_at', [$since, $until])
            ->groupBy('si.inventory_item_id')
            ->selectRaw('si.inventory_item_id, SUM(si.quantity) as qty')
            ->pluck('qty', 'inventory_item_id')
            ->map(fn ($v) => (float) $v)
            ->all();
    }

    private function stockRecommendation(float $demand, float $stock, float $changePct, string $unit): array
    {
        if ($stock < $demand) {
            $short = ceil($demand - $stock);
            return ['text' => "Restock {$short} {$unit} to cover demand", 'tone' => $stock < $demand / 2 ? 'red' : 'amber'];
        }
        if ($changePct < -10) {
            return ['text' => 'Demand falling: reduce next order', 'tone' => 'amber'];
        }
        if ($stock > $demand * 1.5 && $demand > 0) {
            return ['text' => 'Overstocked: consider a promo', 'tone' => 'amber'];
        }
        return ['text' => $changePct > 10 ? 'Demand rising: stock is sufficient' : 'Maintain current stock', 'tone' => 'green'];
    }

    private function floatOrNull($value): ?float
    {
        return $value === null ? null : (float) $value;
    }

    private function today(): Carbon
    {
        return Carbon::now(config('services.openweather.timezone'))->startOfDay();
    }
}
