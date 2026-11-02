<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * DEMO DATA ONLY — simulates one year of fruit stall operations so the SARIMAX
 * and XGBoost models have history to train on before real sales accumulate.
 *
 * Demand and spoilage are driven by the real past weather in `weather_data`
 * (run `php artisan weather:backfill --from=<365 days ago>` first), with weekly
 * and seasonal patterns plus random noise. Refuses to run if sales already exist.
 *
 *   php artisan db:seed --class=MLDemoDataSeeder
 */
class MLDemoDataSeeder extends Seeder
{
    private const DAYS = 365;

    // name => [price/kg, base kg/day, shelf life days, temp sensitivity of demand, peak months]
    private const FRUITS = [
        'Mango'      => [120, 22, 6, 0.03, [4, 5, 6]],
        'Banana'     => [80, 30, 7, 0.01, []],
        'Pineapple'  => [60, 14, 8, 0.03, [3, 4, 5]],
        'Papaya'     => [50, 12, 5, 0.02, []],
        'Durian'     => [150, 10, 4, 0.01, [8, 9, 10]],
        'Lanzones'   => [90, 9, 5, 0.01, [9, 10]],
        'Mangosteen' => [140, 8, 6, 0.01, [7, 8, 9]],
        'Pomelo'     => [100, 7, 14, 0.02, [8, 9]],
        'Watermelon' => [40, 16, 10, 0.08, [3, 4, 5]],
        'Rambutan'   => [85, 9, 4, 0.01, [8, 9, 10]],
    ];

    private array $txRows = [];
    private array $itemRows = [];
    private int $txId;
    private int $itemRowId;

    public function run(): void
    {
        if (DB::table('sales_transactions')->exists()) {
            $this->command->error('sales_transactions already has data; demo seeding skipped to protect real records.');
            return;
        }

        mt_srand(42);
        $tz = config('services.openweather.timezone');
        $start = Carbon::now($tz)->startOfDay()->subDays(self::DAYS);
        $weather = DB::table('weather_data')->where('date', '>=', $start->toDateString())->get()->keyBy(fn ($w) => substr($w->date, 0, 10));

        if ($weather->count() < self::DAYS * 0.8) {
            $this->command->warn("Only {$weather->count()} days of weather found. Run `php artisan weather:backfill --from={$start->toDateString()}` first so demand and spoilage follow real weather.");
        }

        $userId = DB::table('users')->where('role', 'owner')->value('id');
        $this->txId = 1;
        $this->itemRowId = 1;

        DB::transaction(function () use ($start, $weather, $userId) {
            foreach (self::FRUITS as $name => [$price, $base, $shelf, $tempSens, $peak]) {
                $itemId = DB::table('inventory_items')->insertGetId([
                    'name' => $name, 'category' => 'Fruit', 'unit' => 'kg', 'price_per_unit' => $price,
                    'stock_quantity' => 0, 'reorder_level' => round($base * 2), 'freshness_score' => 100,
                    'spoilage_risk' => 'Low', 'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
                ]);

                $batches = []; // open batches, oldest first
                $batchNo = 0;

                for ($d = 0; $d < self::DAYS; $d++) {
                    $day = $start->copy()->addDays($d);
                    $w = $weather->get($day->toDateString());
                    $temp = $w->temperature ?? 27.5;
                    $hum = $w->humidity ?? 80;
                    $rain = $w->rainfall ?? 4;

                    // Restock when on-hand stock covers less than ~2 days of base demand
                    $onHand = array_sum(array_column($batches, 'qty'));
                    if ($onHand < $base * 2) {
                        $qty = round($base * min($shelf, 5) * $this->rand(0.8, 1.2), 1);
                        $batchNo++;
                        $id = DB::table('inventory_batches')->insertGetId([
                            'inventory_item_id' => $itemId,
                            'batch_code' => strtoupper(substr($name, 0, 3)) . $itemId . '-' . $day->format('ymd') . '-' . $batchNo,
                            'quantity' => $qty, 'initial_quantity' => $qty, 'price_per_unit' => $price,
                            'received_date' => $day->toDateString(),
                            'expiry_date' => $day->copy()->addDays($shelf)->toDateString(),
                            'supplier' => 'Demo Supplier', 'status' => 'available',
                            'created_at' => $day->copy()->setTime(6, 0)->utc(), 'updated_at' => now(),
                        ]);
                        $batches[] = ['id' => $id, 'qty' => $qty, 'received' => $day->copy()];
                    }

                    // Demand: weekday + season + weather effects, with noise
                    $dow = [0 => 1.30, 5 => 1.10, 6 => 1.35][$day->dayOfWeek] ?? 1.0;
                    $season = in_array($day->month, $peak, true) ? 1.45 : 1.0;
                    $weatherEffect = (1 + $tempSens * ($temp - 27)) * (1 - 0.35 * min($rain, 30) / 30);
                    $demand = max(0, $base * $dow * $season * $weatherEffect * (1 + 0.18 * $this->gauss()));

                    // Sell FIFO, split across 1–3 transactions
                    $sales = [];
                    foreach ($batches as &$b) {
                        if ($demand <= 0) {
                            break;
                        }
                        $take = min($b['qty'], $demand);
                        if ($take > 0.05) {
                            $sales[] = [$b['id'], round($take, 2)];
                            $b['qty'] -= $take;
                            $demand -= $take;
                        }
                    }
                    unset($b);
                    foreach ($sales as [$batchId, $qty]) {
                        $parts = $qty > 6 ? mt_rand(1, 3) : 1;
                        for ($p = 0; $p < $parts; $p++) {
                            $at = $day->copy()->setTime(mt_rand(8, 19), mt_rand(0, 59))->utc();
                            $this->addMovement('sale', $userId, $itemId, $batchId, round($qty / $parts, 2), $price, $at);
                        }
                    }

                    // Spoilage: hazard rises with age and with heat, humidity and rain
                    foreach ($batches as &$b) {
                        if ($b['qty'] <= 0.05) {
                            continue;
                        }
                        $age = $b['received']->diffInDays($day);
                        $ageFactor = pow(max($age, 0) / $shelf, 2.2);
                        $env = max(0.2, (1 + 0.09 * ($temp - 27)) * (1 + 0.015 * ($hum - 78)) * (1 + 0.012 * min($rain, 40)));
                        $rate = min(1, 0.015 + 0.22 * $ageFactor * $env * $this->rand(0.7, 1.3));
                        $spoiled = $age >= $shelf ? $b['qty'] : $b['qty'] * $rate;
                        if ($spoiled > 0.05) {
                            $at = $day->copy()->setTime(20, 0)->utc();
                            $this->addMovement('spoilage', $userId, $itemId, $b['id'], round($spoiled, 2), $price, $at);
                            $b['qty'] -= $spoiled;
                        }
                    }
                    unset($b);

                    // Close finished batches
                    foreach ($batches as $i => $b) {
                        if ($b['qty'] <= 0.05) {
                            DB::table('inventory_batches')->where('id', $b['id'])->update(['quantity' => 0, 'status' => 'depleted']);
                            unset($batches[$i]);
                        }
                    }
                    $batches = array_values($batches);

                    if (count($this->itemRows) > 1500) {
                        $this->flush();
                    }
                }

                foreach ($batches as $b) {
                    DB::table('inventory_batches')->where('id', $b['id'])->update(['quantity' => round($b['qty'], 2)]);
                }
                DB::table('inventory_items')->where('id', $itemId)->update([
                    'stock_quantity' => round(array_sum(array_column($batches, 'qty')), 2),
                ]);
            }
            $this->flush();
        });

        $this->command->info(sprintf(
            'Demo data: %d items, %d batches, %d transactions (%d spoilage write-offs).',
            DB::table('inventory_items')->count(), DB::table('inventory_batches')->count(),
            DB::table('sales_transactions')->count(), DB::table('sales_transactions')->where('type', 'spoilage')->count()
        ));
    }

    private function addMovement(string $type, ?int $userId, int $itemId, int $batchId, float $qty, float $price, Carbon $at): void
    {
        $total = round($qty * $price, 2);
        $prefix = $type === 'sale' ? 'TXN' : 'SPL';
        $this->txRows[] = [
            'id' => $this->txId, 'user_id' => $userId, 'type' => $type,
            'transaction_code' => sprintf('%s-%s-%06d', $prefix, $at->format('Ymd'), $this->txId),
            'subtotal' => $total, 'tax_amount' => 0, 'discount_amount' => 0, 'total_amount' => $total,
            'payment_method' => $type === 'sale' ? 'cash' : 'n/a', 'status' => 'completed',
            'notes' => $type === 'sale' ? 'Demo sale' : 'Demo spoilage write-off',
            'created_at' => $at, 'updated_at' => $at,
        ];
        $this->itemRows[] = [
            'id' => $this->itemRowId++, 'sale_transaction_id' => $this->txId++, 'inventory_item_id' => $itemId,
            'inventory_batch_id' => $batchId, 'quantity' => $qty, 'unit_price' => $price, 'total_amount' => $total,
            'created_at' => $at, 'updated_at' => $at,
        ];
    }

    private function flush(): void
    {
        foreach (array_chunk($this->txRows, 500) as $chunk) {
            DB::table('sales_transactions')->insert($chunk);
        }
        foreach (array_chunk($this->itemRows, 500) as $chunk) {
            DB::table('sales_items')->insert($chunk);
        }
        $this->txRows = $this->itemRows = [];
    }

    private function rand(float $min, float $max): float
    {
        return $min + mt_rand() / mt_getrandmax() * ($max - $min);
    }

    /** Standard normal sample (Box–Muller). */
    private function gauss(): float
    {
        $u = max(mt_rand() / mt_getrandmax(), 1e-9);
        $v = mt_rand() / mt_getrandmax();
        return sqrt(-2 * log($u)) * cos(2 * M_PI * $v);
    }
}
