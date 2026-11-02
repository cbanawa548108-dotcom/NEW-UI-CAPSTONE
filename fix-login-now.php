<?php

// EMERGENCY LOGIN FIX - Run this with: php fix-login-now.php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n=== EMERGENCY LOGIN FIX ===\n\n";

// Use Laravel's default test password hash
// This is the standard Laravel test password hash for "password"
$testHash = '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

echo "Using Laravel default test hash...\n";
echo "Hash: " . substr($testHash, 0, 30) . "...\n\n";

// Verify this hash works with Laravel
if (Hash::check('password', $testHash)) {
    echo "✓ Hash verified - 'password' matches!\n\n";
} else {
    echo "✗ Hash verification failed - trying fresh hash...\n\n";
    $testHash = Hash::make('password');
    echo "Generated fresh hash: " . substr($testHash, 0, 30) . "...\n\n";
}

// Update all accounts
$updated = DB::table('users')
    ->whereIn('email', [
        'owner@FreshTrack.ph',
        'manager@FreshTrack.ph',
        'cashier@FreshTrack.ph'
    ])
    ->update(['password' => $testHash]);

echo "Updated $updated accounts\n\n";

// Verify each account
echo "=== VERIFICATION ===\n";
$users = DB::table('users')
    ->whereIn('email', ['owner@FreshTrack.ph', 'manager@FreshTrack.ph', 'cashier@FreshTrack.ph'])
    ->get();

foreach ($users as $user) {
    $works = Hash::check('password', $user->password);
    echo ($works ? "✓" : "✗") . " {$user->email} ({$user->role}) - Password: " . ($works ? "WORKS" : "FAILED") . "\n";
}

echo "\n=== TRY LOGGING IN NOW ===\n";
echo "Email: owner@FreshTrack.ph\n";
echo "Password: password\n\n";
