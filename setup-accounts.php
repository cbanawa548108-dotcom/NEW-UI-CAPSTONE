#!/usr/bin/env php
<?php

/*
 * FreshTrack - Setup Demo Accounts
 * This script creates the three demo accounts for testing RBAC
 */

define('LARAVEL_START', microtime(true));

// Register the Composer autoloader
require __DIR__.'/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n╔══════════════════════════════════════════╗\n";
echo "║  FreshTrack RBAC Demo Accounts Setup   ║\n";
echo "╚══════════════════════════════════════════╝\n\n";

use Illuminate\Support\Facades\Hash;
use App\Models\User;

try {
    // Test database connection
    echo "→ Testing database connection... ";
    DB::connection()->getPdo();
    echo "✓\n";

    // Check if users table exists
    echo "→ Checking users table... ";
    if (!Schema::hasTable('users')) {
        echo "✗\n";
        echo "  ERROR: Users table doesn't exist!\n";
        echo "  Please run: php artisan migrate\n\n";
        exit(1);
    }
    echo "✓\n";

    // Check if role column exists
    echo "→ Checking role column... ";
    if (!Schema::hasColumn('users', 'role')) {
        echo "✗\n";
        echo "  ERROR: Role column doesn't exist!\n";
        echo "  Please run: php artisan migrate\n\n";
        exit(1);
    }
    echo "✓\n";

    echo "\n→ Creating demo accounts...\n";

    $password = Hash::make('password');

    // Owner account
    User::updateOrCreate(
        ['email' => 'owner@FreshTrack.ph'],
        [
            'name' => 'Owner Account',
            'email' => 'owner@FreshTrack.ph',
            'role' => 'owner',
            'password' => $password,
            'email_verified_at' => now(),
        ]
    );
    echo "  ✓ owner@FreshTrack.ph (Role: Owner)\n";

    // Manager account
    User::updateOrCreate(
        ['email' => 'manager@FreshTrack.ph'],
        [
            'name' => 'Manager Account',
            'email' => 'manager@FreshTrack.ph',
            'role' => 'manager',
            'password' => $password,
            'email_verified_at' => now(),
        ]
    );
    echo "  ✓ manager@FreshTrack.ph (Role: Manager)\n";

    // Cashier account
    User::updateOrCreate(
        ['email' => 'cashier@FreshTrack.ph'],
        [
            'name' => 'Cashier Account',
            'email' => 'cashier@FreshTrack.ph',
            'role' => 'cashier',
            'password' => $password,
            'email_verified_at' => now(),
        ]
    );
    echo "  ✓ cashier@FreshTrack.ph (Role: Cashier)\n";

    echo "\n╔══════════════════════════════════════════╗\n";
    echo "║           SETUP COMPLETE! ✓             ║\n";
    echo "╚══════════════════════════════════════════╝\n\n";

    echo "Demo Accounts:\n";
    echo "  • owner@FreshTrack.ph    → password: password\n";
    echo "  • manager@FreshTrack.ph  → password: password\n";
    echo "  • cashier@FreshTrack.ph  → password: password\n\n";

    echo "You can now login at: http://127.0.0.1:8000/login\n\n";

} catch (\Exception $e) {
    echo "✗\n\n";
    echo "ERROR: " . $e->getMessage() . "\n\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n\n";
    exit(1);
}
