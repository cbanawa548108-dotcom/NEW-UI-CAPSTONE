<?php

namespace App\Console\Commands;

use App\Services\WeatherService;
use Illuminate\Console\Command;

class WeatherSync extends Command
{
    protected $signature = 'weather:sync';

    protected $description = 'Store today\'s weather and the 5-day OpenWeather forecast';

    public function handle(WeatherService $weather): int
    {
        try {
            $days = $weather->syncForecast();
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->info("Weather synced for {$days} day(s).");
        return self::SUCCESS;
    }
}
