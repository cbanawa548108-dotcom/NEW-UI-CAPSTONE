<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

echo "\n";
echo "=== CHECKING OWNER ACCOUNT PASSWORD ===\n\n";

// Get the owner account
$owner = DB::table('users')->where('email', 'owner@FreshTrack.ph')->first();

if (!$owner) {
    echo "ERROR: Owner account not found!\n";
    echo "Run: php artisan db:seed\n\n";
    exit(1);
}

echo "Owner account found:\n";
echo "  Email: {$owner->email}\n";
echo "  Name: {$owner->name}\n";
echo "  Role: {$owner->role}\n";
echo "  Password hash: " . substr($owner->password, 0, 50) . "...\n\n";

// Test the password
$testPassword = 'password';
echo "Testing password: '$testPassword'\n\n";

$match = Hash::check($testPassword, $owner->password);

if ($match) {
    echo "✓✓✓ PASSWORD WORKS! ✓✓✓\n";
    echo "Login should work now.\n\n";
    echo "If it still doesn't work, the issue is elsewhere.\n";
    echo "Check:\n";
    echo "1. Clear browser cache (Ctrl+Shift+Delete)\n";
    echo "2. Try incognito/private window\n";
    echo "3. Run: php artisan config:clear\n";
    echo "4. Run: php artisan cache:clear\n\n";
} else {
    echo "✗✗✗ PASSWORD DOES NOT MATCH! ✗✗✗\n\n";
    echo "Fixing it now...\n\n";
    
    // Generate fresh hash
    $newHash = Hash::make($testPassword);
    
    // Update the password
    DB::table('users')
        ->where('email', 'owner@FreshTrack.ph')
        ->update(['password' => $newHash]);
    
    echo "✓ Password updated!\n";
    echo "New hash: " . substr($newHash, 0, 50) . "...\n\n";
    
    // Verify it works now
    $owner = DB::table('users')->where('email', 'owner@FreshTrack.ph')->first();
    $nowWorks = Hash::check($testPassword, $owner->password);
    
    if ($nowWorks) {
        echo "✓✓✓ FIXED! Password now works! ✓✓✓\n\n";
    } else {
        echo "✗ Still not working. Something is wrong with Laravel's Hash facade.\n";
        echo "Check your .env file: BCRYPT_ROUNDS should be 12\n\n";
    }
}

echo "Now try logging in:\n";
echo "  http://127.0.0.1:8000/login\n";
echo "  Email: owner@FreshTrack.ph\n";
echo "  Password: password\n\n";
