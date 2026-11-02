#!/usr/bin/env php
<?php

/*
 * Test Password Hash - Debug authentication issue
 */

define('LARAVEL_START', microtime(true));

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Hash;
use App\Models\User;

echo "\n=== Password Hash Testing ===\n\n";

// Test the password
$testPassword = 'password';
$newHash = Hash::make($testPassword);

echo "Test password: $testPassword\n";
echo "New hash: $newHash\n\n";

// Get the users from database
$users = User::whereIn('email', [
    'owner@FreshTrack.ph',
    'manager@FreshTrack.ph',
    'cashier@FreshTrack.ph'
])->get();

echo "Checking existing users:\n";
echo str_repeat("-", 80) . "\n";

foreach ($users as $user) {
    echo "Email: {$user->email}\n";
    echo "Role: {$user->role}\n";
    echo "Stored hash: {$user->password}\n";
    
    $match = Hash::check($testPassword, $user->password);
    echo "Password 'password' matches: " . ($match ? "✓ YES" : "✗ NO") . "\n";
    
    if (!$match) {
        echo "→ UPDATING PASSWORD...\n";
        $user->password = $newHash;
        $user->save();
        echo "✓ Password updated!\n";
    }
    
    echo "\n";
}

echo "=== Testing Complete ===\n";
echo "Try logging in again with:\n";
echo "  Email: owner@FreshTrack.ph\n";
echo "  Password: password\n\n";
