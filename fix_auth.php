<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== FreshTrack RBAC Fix Script ===\n\n";

// Check database connection
try {
    DB::connection()->getPdo();
    echo "✓ Database connected successfully\n";
} catch (\Exception $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Check if users table has role column
try {
    $hasRoleColumn = Schema::hasColumn('users', 'role');
    if ($hasRoleColumn) {
        echo "✓ Users table has 'role' column\n";
    } else {
        echo "✗ Users table missing 'role' column\n";
        echo "  → Running migration...\n";
        Artisan::call('migrate', ['--force' => true]);
        echo "  ✓ Migration completed\n";
    }
} catch (\Exception $e) {
    echo "✗ Error checking users table: " . $e->getMessage() . "\n";
    echo "  → Running all migrations...\n";
    Artisan::call('migrate', ['--force' => true]);
    echo "  ✓ Migrations completed\n";
}

// Check if users exist
$ownerExists = DB::table('users')->where('email', 'owner@FreshTrack.ph')->exists();
$managerExists = DB::table('users')->where('email', 'manager@FreshTrack.ph')->exists();
$cashierExists = DB::table('users')->where('email', 'cashier@FreshTrack.ph')->exists();

if ($ownerExists && $managerExists && $cashierExists) {
    echo "✓ All three demo accounts exist\n";
} else {
    echo "✗ Demo accounts missing. Creating them...\n";
    
    // Delete existing accounts if any
    DB::table('users')->whereIn('email', [
        'owner@FreshTrack.ph',
        'manager@FreshTrack.ph', 
        'cashier@FreshTrack.ph'
    ])->delete();
    
    // Create accounts
    $password = Hash::make('password');
    
    DB::table('users')->insert([
        [
            'name' => 'Owner Account',
            'email' => 'owner@FreshTrack.ph',
            'role' => 'owner',
            'password' => $password,
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'name' => 'Manager Account',
            'email' => 'manager@FreshTrack.ph',
            'role' => 'manager',
            'password' => $password,
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'name' => 'Cashier Account',
            'email' => 'cashier@FreshTrack.ph',
            'role' => 'cashier',
            'password' => $password,
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);
    
    echo "  ✓ Created owner@FreshTrack.ph (password: password)\n";
    echo "  ✓ Created manager@FreshTrack.ph (password: password)\n";
    echo "  ✓ Created cashier@FreshTrack.ph (password: password)\n";
}

// Verify all accounts
echo "\n=== Account Verification ===\n";
$users = DB::table('users')
    ->whereIn('email', ['owner@FreshTrack.ph', 'manager@FreshTrack.ph', 'cashier@FreshTrack.ph'])
    ->get(['name', 'email', 'role']);

foreach ($users as $user) {
    echo "✓ {$user->email} → Role: {$user->role}\n";
}

echo "\n=== Setup Complete ===\n";
echo "You can now login with any of these accounts using password: password\n";
