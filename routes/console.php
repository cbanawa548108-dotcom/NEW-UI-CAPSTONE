<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Weather every 3 hours (matches OpenWeather's 3-hour forecast steps)
Schedule::command('weather:sync')->everyThreeHours();

// Re-estimate the SARIMAX parameters weekly (a few minutes); daily runs only update them with new sales
Schedule::command('ml:run sales --skip-weather --refit')->weeklyOn(0, '05:00')->timezone(config('services.openweather.timezone'));

// Update both models each morning before the store opens
Schedule::command('ml:run all --skip-weather')->dailyAt('05:30')->timezone(config('services.openweather.timezone'));
