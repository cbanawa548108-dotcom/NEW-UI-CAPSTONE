<?php

namespace App\Console\Commands;

use App\Services\WeatherService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class WeatherBackfill extends Command
{
    protected $signature = 'weather:backfill
                            {--from= : Start date (default: first sales date, or 365 days ago)}
                            {--to= : End date (default: yesterday)}
                            {--overwrite : Replace days that already have weather}
                            {--source= : Force a source: open-meteo (free, one request for the whole range)}';

    protected $description = 'Load past daily weather so the models have weather for every historical sales day';

    public function handle(WeatherService $weather): int
    {
        $tz = config('services.openweather.timezone');
        $firstSale = DB::table('sales_transactions')->min('created_at');

        $from = $this->option('from')
            ? Carbon::parse($this->option('from'), $tz)
            : ($firstSale ? Carbon::parse($firstSale)->setTimezone($tz)->subDays(3) : Carbon::now($tz)->subDays(365));
        $to = $this->option('to') ? Carbon::parse($this->option('to'), $tz) : Carbon::now($tz)->subDay();

        $source = $this->option('source');
        if ($source !== null && $source !== 'open-meteo') {
            $this->error("Unknown --source '{$source}'; the only option is open-meteo.");
            return self::FAILURE;
        }

        $this->info("Backfilling weather {$from->toDateString()} → {$to->toDateString()} ...");

        try {
            $result = $weather->backfill($from->startOfDay(), $to->startOfDay(), (bool) $this->option('overwrite'), $source);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        if ($result['source'] === 'open-meteo' && $source === null) {
            $this->warn($weather->isConfigured()
                ? 'OpenWeather history (One Call 3.0) is not enabled for this API key; used the Open-Meteo archive instead.'
                : 'OPENWEATHER_API_KEY is not set; used the Open-Meteo archive for past weather.');
        }
        $this->info("Stored {$result['days']} day(s) of weather (source: {$result['source']}).");

        return self::SUCCESS;
    }
}
