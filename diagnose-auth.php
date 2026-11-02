<?php

/*
 * Authentication Diagnostics
 * This will check everything related to authentication
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\User;

echo "\n";
echo "╔════════════════════════════════════════════════╗\n";
echo "║     AUTHENTICATION DIAGNOSTICS - FreshTrack   ║\n";
echo "╚════════════════════════════════════════════════╝\n\n";

// 1. Check database connection
echo "1. DATABASE CONNECTION\n";
echo str_repeat("-", 50) . "\n";
try {
    DB::connection()->getPdo();
    echo "✓ Connected to database: " . config('database.connections.mysql.database') . "\n";
} catch (\Exception $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}
echo "\n";

// 2. Check users table structure
echo "2. USERS TABLE STRUCTURE\n";
echo str_repeat("-", 50) . "\n";
if (Schema::hasTable('users')) {
    echo "✓ Users table exists\n";
    
    $columns = ['id', 'name', 'email', 'password', 'role', 'email_verified_at', 'remember_token'];
    foreach ($columns as $column) {
        $exists = Schema::hasColumn('users', $column);
        echo ($exists ? "✓" : "✗") . " Column '$column' " . ($exists ? "exists" : "MISSING") . "\n";
    }
} else {
    echo "✗ Users table does not exist!\n";
    exit(1);
}
echo "\n";

// 3. Check demo accounts
echo "3. DEMO ACCOUNTS\n";
echo str_repeat("-", 50) . "\n";
$emails = ['owner@FreshTrack.ph', 'manager@FreshTrack.ph', 'cashier@FreshTrack.ph'];
$users = DB::table('users')->whereIn('email', $emails)->get();

if ($users->count() === 3) {
    echo "✓ All 3 demo accounts exist\n\n";
    foreach ($users as $user) {
        echo "  • {$user->email}\n";
        echo "    Name: {$user->name}\n";
        echo "    Role: {$user->role}\n";
        echo "    Password hash: " . substr($user->password, 0, 20) . "...\n";
        
        // Test password
        $passwordWorks = Hash::check('password', $user->password);
        echo "    Password 'password' works: " . ($passwordWorks ? "✓ YES" : "✗ NO") . "\n\n";
    }
} else {
    echo "✗ Only {$users->count()} demo accounts found (expected 3)\n";
}

// 4. Check User model
echo "4. USER MODEL\n";
echo str_repeat("-", 50) . "\n";
try {
    $testUser = User::where('email', 'owner@FreshTrack.ph')->first();
    if ($testUser) {
        echo "✓ Can retrieve user via Eloquent\n";
        echo "  Name: {$testUser->name}\n";
        echo "  Email: {$testUser->email}\n";
        echo "  Role: {$testUser->role}\n";
        echo "  Has isOwner method: " . (method_exists($testUser, 'isOwner') ? "✓" : "✗") . "\n";
        echo "  Has isManager method: " . (method_exists($testUser, 'isManager') ? "✓" : "✗") . "\n";
        echo "  Has isCashier method: " . (method_exists($testUser, 'isCashier') ? "✓" : "✗") . "\n";
    } else {
        echo "✗ Cannot find owner@FreshTrack.ph via Eloquent\n";
    }
} catch (\Exception $e) {
    echo "✗ Error with User model: " . $e->getMessage() . "\n";
}
echo "\n";

// 5. Check auth configuration
echo "5. AUTH CONFIGURATION\n";
echo str_repeat("-", 50) . "\n";
echo "Auth guard: " . config('auth.defaults.guard') . "\n";
echo "Auth provider: " . config('auth.guards.web.provider') . "\n";
echo "Provider driver: " . config('auth.providers.users.driver') . "\n";
echo "User model: " . config('auth.providers.users.model') . "\n";
echo "\n";

// 6. Test authentication manually
echo "6. MANUAL AUTHENTICATION TEST\n";
echo str_repeat("-", 50) . "\n";
try {
    $credentials = [
        'email' => 'owner@FreshTrack.ph',
        'password' => 'password'
    ];
    
    $user = User::where('email', $credentials['email'])->first();
    
    if ($user) {
        echo "✓ User found: {$user->email}\n";
        
        $passwordMatch = Hash::check($credentials['password'], $user->password);
        echo "Password check result: " . ($passwordMatch ? "✓ MATCH" : "✗ NO MATCH") . "\n";
        
        if (!$passwordMatch) {
            echo "\n⚠ PASSWORD MISMATCH DETECTED!\n";
            echo "This is why login is failing.\n";
            echo "Run: php reset-passwords-now.php\n";
        }
    } else {
        echo "✗ User not found\n";
    }
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
echo "\n";

echo "╔════════════════════════════════════════════════╗\n";
echo "║            DIAGNOSTICS COMPLETE               ║\n";
echo "╚════════════════════════════════════════════════╝\n\n";
