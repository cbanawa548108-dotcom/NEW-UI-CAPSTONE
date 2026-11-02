<?php

namespace App\Services;

use App\Models\WeatherData;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Pulls weather from the OpenWeather API and stores one row per day in
 * `weather_data`. Temperature, humidity and rainfall are the exogenous
 * variables for the SARIMAX forecast and features for the XGBoost spoilage model.
 */
class WeatherService
{
    private const BASE_URL = 'https://api.openweathermap.org/data';

    private ?string $apiKey;
    private float $lat;
    private float $lon;
    private string $timezone;

    public function __construct()
    {
        $config = config('services.openweather');
        $this->apiKey = $config['key'];
        $this->lat = (float) $config['lat'];
        $this->lon = (float) $config['lon'];
        $this->timezone = $config['timezone'];
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Current conditions straight from OpenWeather (not stored).
     */
    public function current(): array
    {
        $data = $this->get('/2.5/weather');

        return [
            'city' => $data['name'] ?? config('services.openweather.city'),
            'temperature' => round($data['main']['temp'], 1),
            'feels_like' => round($data['main']['feels_like'], 1),
            'humidity' => $data['main']['humidity'],
            'rainfall' => $data['rain']['1h'] ?? 0,
            'condition' => $data['weather'][0]['main'] ?? null,
            'description' => $data['weather'][0]['description'] ?? null,
            'icon' => $data['weather'][0]['icon'] ?? null,
            'observed_at' => Carbon::createFromTimestamp($data['dt'])->setTimezone($this->timezone)->toDateTimeString(),
        ];
    }

    /**
     * Store today's weather and the 5-day forecast (3-hour steps aggregated per day).
     * Rows for past dates are marked as observed.
     *
     * @return int number of days written
     */
    public function syncForecast(): int
    {
        $current = $this->get('/2.5/weather');
        $forecast = $this->get('/2.5/forecast');

        $today = Carbon::now($this->timezone)->toDateString();
        $days = [];

        // The current observation counts toward today's averages
        $days[$today][] = [
            'temp' => $current['main']['temp'],
            'temp_min' => $current['main']['temp_min'],
            'temp_max' => $current['main']['temp_max'],
            'humidity' => $current['main']['humidity'],
            'rain' => $current['rain']['1h'] ?? 0,
            'condition' => $current['weather'][0]['main'] ?? null,
        ];

        foreach ($forecast['list'] as $slot) {
            $date = Carbon::createFromTimestamp($slot['dt'])->setTimezone($this->timezone)->toDateString();
            $days[$date][] = [
                'temp' => $slot['main']['temp'],
                'temp_min' => $slot['main']['temp_min'],
                'temp_max' => $slot['main']['temp_max'],
                'humidity' => $slot['main']['humidity'],
                'rain' => $slot['rain']['3h'] ?? 0,
                'condition' => $slot['weather'][0]['main'] ?? null,
            ];
        }

        foreach ($days as $date => $slots) {
            $conditions = array_count_values(array_filter(array_column($slots, 'condition')));
            arsort($conditions);

            WeatherData::updateOrCreate(['date' => $date], [
                'temperature' => round(collect($slots)->avg('temp'), 2),
                'temp_min' => round(collect($slots)->min('temp_min'), 2),
                'temp_max' => round(collect($slots)->max('temp_max'), 2),
                'humidity' => round(collect($slots)->avg('humidity'), 2),
                'rainfall' => round(collect($slots)->sum('rain'), 2),
                'condition' => array_key_first($conditions),
                'is_forecast' => $date > $today,
                'source' => 'openweather',
            ]);
        }

        // Yesterday's (and older) forecast rows are now history
        WeatherData::where('date', '<', $today)->where('is_forecast', true)->update(['is_forecast' => false]);

        return count($days);
    }

    /**
     * Fill in past daily weather so the models have weather for every historical sales day.
     * Uses OpenWeather One Call 3.0 day_summary; falls back to the Open-Meteo archive
     * if that endpoint is not part of the API key's plan. Pass $source = 'open-meteo' to skip
     * OpenWeather (one paid request per day) for long ranges.
     *
     * @return array{source: string, days: int}
     */
    public function backfill(Carbon $from, Carbon $to, bool $overwrite = false, ?string $source = null): array
    {
        $dates = [];
        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            $date = $d->toDateString();
            if ($overwrite || !WeatherData::where('date', $date)->where('is_forecast', false)->exists()) {
                $dates[] = $date;
            }
        }

        if (empty($dates)) {
            return ['source' => 'none', 'days' => 0];
        }

        if ($source === 'open-meteo') {
            return ['source' => 'open-meteo', 'days' => $this->backfillFromOpenMeteo($dates)];
        }

        if ($this->isConfigured()) {
            $probe = $this->request('/3.0/onecall/day_summary', ['date' => $dates[0]]);
            if ($probe->successful()) {
                $written = 0;
                foreach ($dates as $date) {
                    $response = $date === $dates[0] ? $probe : $this->request('/3.0/onecall/day_summary', ['date' => $date]);
                    if ($response->successful()) {
                        $this->storeDaySummary($date, $response->json());
                        $written++;
                    }
                }
                return ['source' => 'openweather', 'days' => $written];
            }
            if (!in_array($probe->status(), [401, 403], true)) {
                throw new RuntimeException('OpenWeather history request failed: ' . $probe->body());
            }
        }

        if (config('services.openweather.history_fallback') !== 'open-meteo') {
            throw new RuntimeException('Historical weather needs an OpenWeather One Call 3.0 subscription (OPENWEATHER_HISTORY_FALLBACK is disabled).');
        }

        return ['source' => 'open-meteo', 'days' => $this->backfillFromOpenMeteo($dates)];
    }

    private function storeDaySummary(string $date, array $data): void
    {
        $temps = array_filter([
            $data['temperature']['morning'] ?? null,
            $data['temperature']['afternoon'] ?? null,
            $data['temperature']['evening'] ?? null,
            $data['temperature']['night'] ?? null,
        ], fn ($t) => $t !== null);

        WeatherData::updateOrCreate(['date' => $date], [
            'temperature' => round(array_sum($temps) / max(count($temps), 1), 2),
            'temp_min' => $data['temperature']['min'] ?? null,
            'temp_max' => $data['temperature']['max'] ?? null,
            'humidity' => $data['humidity']['afternoon'] ?? 0,
            'rainfall' => $data['precipitation']['total'] ?? 0,
            'is_forecast' => false,
            'source' => 'openweather',
        ]);
    }

    private function backfillFromOpenMeteo(array $dates): int
    {
        $response = Http::timeout(30)->get('https://archive-api.open-meteo.com/v1/archive', [
            'latitude' => $this->lat,
            'longitude' => $this->lon,
            'start_date' => min($dates),
            'end_date' => max($dates),
            'daily' => 'temperature_2m_mean,temperature_2m_min,temperature_2m_max,relative_humidity_2m_mean,precipitation_sum,weather_code',
            'timezone' => $this->timezone,
        ]);

        if (!$response->successful()) {
            throw new RuntimeException('Open-Meteo archive request failed: ' . $response->body());
        }

        $daily = $response->json('daily');
        $wanted = array_flip($dates);
        $written = 0;

        foreach ($daily['time'] as $i => $date) {
            // The archive lags a few days behind; skip days it has no data for yet
            if (!isset($wanted[$date]) || $daily['temperature_2m_mean'][$i] === null) {
                continue;
            }

            WeatherData::updateOrCreate(['date' => $date], [
                'temperature' => $daily['temperature_2m_mean'][$i],
                'temp_min' => $daily['temperature_2m_min'][$i],
                'temp_max' => $daily['temperature_2m_max'][$i],
                'humidity' => $daily['relative_humidity_2m_mean'][$i] ?? 0,
                'rainfall' => $daily['precipitation_sum'][$i] ?? 0,
                'condition' => $this->conditionFromWmoCode($daily['weather_code'][$i] ?? null),
                'is_forecast' => false,
                'source' => 'open-meteo',
            ]);
            $written++;
        }

        return $written;
    }

    /** Map WMO weather codes to OpenWeather-style condition names. */
    private function conditionFromWmoCode(?int $code): ?string
    {
        return match (true) {
            $code === null => null,
            $code === 0 => 'Clear',
            $code <= 3 => 'Clouds',
            $code <= 48 => 'Fog',
            $code >= 51 && $code <= 57 => 'Drizzle',
            $code <= 67, $code >= 80 && $code <= 82 => 'Rain',
            $code >= 95 => 'Thunderstorm',
            default => 'Rain',
        };
    }

    private function get(string $path, array $params = []): array
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException('OPENWEATHER_API_KEY is not set in .env');
        }

        $response = $this->request($path, $params);
        if (!$response->successful()) {
            throw new RuntimeException("OpenWeather {$path} failed ({$response->status()}): " . $response->json('message', $response->body()));
        }

        return $response->json();
    }

    private function request(string $path, array $params = [])
    {
        return Http::timeout(20)->get(self::BASE_URL . $path, array_merge([
            'lat' => $this->lat,
            'lon' => $this->lon,
            'appid' => $this->apiKey,
            'units' => 'metric',
        ], $params));
    }
}
