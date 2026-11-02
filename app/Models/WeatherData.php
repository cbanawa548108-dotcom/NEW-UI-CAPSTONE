<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeatherData extends Model
{
    protected $table = 'weather_data';

    protected $fillable = [
        'date',
        'temperature',
        'temp_min',
        'temp_max',
        'humidity',
        'rainfall',
        'condition',
        'is_forecast',
        'source',
    ];

    protected $casts = [
        'date' => 'date',
        'temperature' => 'float',
        'temp_min' => 'float',
        'temp_max' => 'float',
        'humidity' => 'float',
        'rainfall' => 'float',
        'is_forecast' => 'boolean',
    ];
}
