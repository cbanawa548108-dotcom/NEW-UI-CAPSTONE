<?php

namespace App\Console\Commands;

use App\Services\MLService;
use App\Services\WeatherService;
use Illuminate\Console\Command;

class RunForecastModels extends Command
{
    protected $signature = 'ml:run
                            {model=all : sales | spoilage | all}
                            {--horizon=7 : Days to forecast}
                            {--skip-weather : Do not refresh the OpenWeather forecast first}
                            {--refit : Re-estimate the SARIMAX parameters instead of only updating with new sales}';

    protected $description = 'Train and run the SARIMAX sales forecast and/or XGBoost spoilage model';

    public function handle(MLService $ml, WeatherService $weather): int
    {
        $model = $this->argument('model');
        $horizon = (int) $this->option('horizon');
        $failed = false;

        if (!$this->option('skip-weather')) {
            try {
                $this->info('Weather: synced ' . $weather->syncForecast() . ' day(s) from OpenWeather.');
            } catch (\Throwable $e) {
                $this->warn('Weather sync skipped: ' . $e->getMessage());
            }
        }

        if (in_array($model, ['sales', 'all'], true)) {
            $this->line('Running SARIMAX sales forecast...');
            $result = $this->runSafely(fn () => $ml->runSalesForecast($horizon, (bool) $this->option('refit')));
            if ($result['success'] ?? false) {
                $this->info(sprintf(
                    'SARIMAX: %d fruit(s) modeled (%d refitted), %d on baseline, accuracy %s%%, MAE %s, RMSE %s',
                    $result['fruits_modeled'], $result['fruits_refitted'] ?? 0, $result['fruits_baseline'],
                    $result['overall_accuracy'] ?? 'n/a', $result['mean_mae'] ?? 'n/a', $result['mean_rmse'] ?? 'n/a'
                ));
                foreach ($result['baseline'] as $b) {
                    $this->warn("  {$b['fruit']}: {$b['reason']}");
                }
            } else {
                $failed = true;
                $this->error('SARIMAX: ' . ($result['message'] ?? 'failed'));
            }
        }

        if (in_array($model, ['spoilage', 'all'], true)) {
            $this->line('Running XGBoost spoilage model...');
            $result = $this->runSafely(fn () => $ml->runSpoilagePrediction($horizon));
            if (($result['success'] ?? false) && ($result['model'] ?? null) === 'ShelfLifeRule') {
                $this->warn($result['message']);
                $this->info(sprintf('Shelf-life rule: scored %d open batch(es), average risk %s%%',
                    $result['batches_scored'], $result['avg_risk']));
            } elseif ($result['success'] ?? false) {
                $this->info(sprintf(
                    'XGBoost: trained on %d batches, test MAE %s, RMSE %s, R² %s; scored %d open batch(es)',
                    $result['batches'], $result['mae'], $result['rmse'], $result['r2'] ?? 'n/a', $result['batches_scored']
                ));
            } else {
                $failed = true;
                $this->error('XGBoost: ' . ($result['message'] ?? 'failed'));
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    private function runSafely(callable $fn): array
    {
        try {
            return $fn();
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
