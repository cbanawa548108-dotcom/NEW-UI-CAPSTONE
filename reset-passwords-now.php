<?php

/*
 * EMERGENCY PASSWORD RESET
 * This will fix the login issue by resetting all passwords to "password"
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

echo "\n";
echo "╔════════════════════════════════════════════════╗\n";
echo "║     EMERGENCY PASSWORD RESET - FreshTrack     ║\n";
echo "╚════════════════════════════════════════════════╝\n\n";

try {
    // Generate a fresh password hash
    $password = 'password';
    $hashedPassword = Hash::make($password);
    
    echo "→ Generated new password hash\n";
    echo "  Hash: " . substr($hashedPassword, 0, 30) . "...\n\n";
    
    // Update all three accounts directly via DB
    $emails = [
        'owner@FreshTrack.ph',
        'manager@FreshTrack.ph',
        'cashier@FreshTrack.ph'
    ];
    
    echo "→ Updating passwords...\n\n";
    
    foreach ($emails as $email) {
        $updated = DB::table('users')
            ->where('email', $email)
            ->update([
                'password' => $hashedPassword,
                'updated_at' => now()
            ]);
        
        if ($updated) {
            echo "  ✓ Updated: $email\n";
        } else {
            echo "  ✗ Not found: $email\n";
        }
    }
    
    echo "\n";
    echo "╔════════════════════════════════════════════════╗\n";
    echo "║              PASSWORD RESET DONE!             ║\n";
    echo "╚════════════════════════════════════════════════╝\n\n";
    
    echo "You can now login with:\n";
    echo "  • owner@FreshTrack.ph / password\n";
    echo "  • manager@FreshTrack.ph / password\n";
    echo "  • cashier@FreshTrack.ph / password\n\n";
    
    // Verify the accounts
    echo "→ Verifying accounts...\n\n";
    
    $users = DB::table('users')
        ->whereIn('email', $emails)
        ->get(['name', 'email', 'role']);
    
    foreach ($users as $user) {
        $passwordWorks = Hash::check($password, $hashedPassword);
        echo "  ✓ {$user->email} (Role: {$user->role}) - Password: " . ($passwordWorks ? "WORKS" : "ERROR") . "\n";
    }
    
    echo "\n→ Go to: http://127.0.0.1:8000/login\n\n";
    
} catch (\Exception $e) {
    echo "\n✗ ERROR: " . $e->getMessage() . "\n\n";
    echo $e->getTraceAsString() . "\n\n";
    exit(1);
}
