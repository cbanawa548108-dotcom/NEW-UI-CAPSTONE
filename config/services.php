<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'openweather' => [
        'key' => env('OPENWEATHER_API_KEY'),
        'city' => env('OPENWEATHER_CITY', 'Davao City'),
        'lat' => env('OPENWEATHER_LAT', 7.0731),
        'lon' => env('OPENWEATHER_LON', 125.6128),
        'timezone' => env('OPENWEATHER_TIMEZONE', 'Asia/Manila'),
        // Past weather for training: OpenWeather One Call 3.0 "day_summary" needs a
        // One Call subscription. When it is unavailable, fall back to the free
        // Open-Meteo archive (set to "none" to disable the fallback).
        'history_fallback' => env('OPENWEATHER_HISTORY_FALLBACK', 'open-meteo'),
    ],

    'ml' => [
        'python' => env('ML_PYTHON', 'python'),
        'timeout' => env('ML_TIMEOUT', 600),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
