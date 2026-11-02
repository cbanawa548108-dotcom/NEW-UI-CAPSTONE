<!DOCTYPE html>
<html>
<head>
    <title>Auth Test - FreshTrack</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1a1a1a; color: #0f0; }
        .success { color: #0f0; }
        .error { color: #f00; }
        .warning { color: #ff0; }
        pre { background: #000; padding: 10px; border: 1px solid #0f0; overflow-x: auto; }
        h2 { color: #0ff; border-bottom: 2px solid #0ff; padding-bottom: 5px; }
    </style>
</head>
<body>
<h1>🔧 FreshTrack Authentication Test</h1>

<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User;

echo "<h2>1. Database Connection</h2>";
try {
    DB::connection()->getPdo();
    echo "<p class='success'>✓ Connected to database: " . config('database.connections.mysql.database') . "</p>";
} catch (\Exception $e) {
    echo "<p class='error'>✗ Connection failed: " . $e->getMessage() . "</p>";
    exit;
}

echo "<h2>2. Users Table Check</h2>";
$users = DB::table('users')
    ->whereIn('email', ['owner@FreshTrack.ph', 'manager@FreshTrack.ph', 'cashier@FreshTrack.ph'])
    ->get();

if ($users->count() === 3) {
    echo "<p class='success'>✓ All 3 accounts found</p>";
} else {
    echo "<p class='error'>✗ Only {$users->count()} accounts found (expected 3)</p>";
}

echo "<h2>3. Password Hash Test</h2>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #333;'><th>Email</th><th>Role</th><th>Hash (first 40 chars)</th><th>Password Works?</th></tr>";

foreach ($users as $user) {
    $works = Hash::check('password', $user->password);
    $color = $works ? 'success' : 'error';
    echo "<tr>";
    echo "<td>{$user->email}</td>";
    echo "<td>{$user->role}</td>";
    echo "<td>" . substr($user->password, 0, 40) . "...</td>";
    echo "<td class='$color'>" . ($works ? "✓ YES" : "✗ NO") . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>4. Manual Auth Test</h2>";
$testEmail = 'owner@FreshTrack.ph';
$testPassword = 'password';

$user = User::where('email', $testEmail)->first();
if ($user) {
    echo "<p class='success'>✓ User found via Eloquent</p>";
    echo "<pre>";
    echo "Email: {$user->email}\n";
    echo "Name: {$user->name}\n";
    echo "Role: {$user->role}\n";
    echo "</pre>";
    
    $passwordWorks = Hash::check($testPassword, $user->password);
    if ($passwordWorks) {
        echo "<p class='success'>✓ Password 'password' MATCHES the hash!</p>";
        echo "<p class='success'>✓ Login SHOULD work now!</p>";
    } else {
        echo "<p class='error'>✗ Password 'password' DOES NOT MATCH the hash!</p>";
        echo "<p class='error'>✗ This is why login is failing!</p>";
        echo "<h3>FIX: Run one of these:</h3>";
        echo "<pre>";
        echo "Double-click: RUN-THIS-NOW.bat\n";
        echo "Or run: php fix-login-now.php\n";
        echo "Or in phpMyAdmin, run: INSTANT-FIX.sql\n";
        echo "</pre>";
    }
} else {
    echo "<p class='error'>✗ User not found!</p>";
}

echo "<h2>5. Laravel Auth Config</h2>";
echo "<pre>";
echo "Guard: " . config('auth.defaults.guard') . "\n";
echo "Provider: " . config('auth.guards.web.provider') . "\n";
echo "Model: " . config('auth.providers.users.model') . "\n";
echo "</pre>";

echo "<h2>6. Session Config</h2>";
echo "<pre>";
echo "Driver: " . config('session.driver') . "\n";
echo "Lifetime: " . config('session.lifetime') . " minutes\n";
echo "</pre>";

echo "<h2>7. Recommendation</h2>";
$allWork = true;
foreach ($users as $user) {
    if (!Hash::check('password', $user->password)) {
        $allWork = false;
        break;
    }
}

if ($allWork) {
    echo "<p class='success'>✓✓✓ ALL PASSWORDS WORK! ✓✓✓</p>";
    echo "<p class='success'>You should be able to login now!</p>";
    echo "<p>Go to: <a href='/login' style='color: #0ff;'>http://127.0.0.1:8000/login</a></p>";
} else {
    echo "<p class='error'>✗✗✗ PASSWORDS DON'T MATCH! ✗✗✗</p>";
    echo "<p class='warning'>Run the password fix:</p>";
    echo "<ol>";
    echo "<li>Double-click: <strong>RUN-THIS-NOW.bat</strong></li>";
    echo "<li>Or run: <code>php fix-login-now.php</code></li>";
    echo "<li>Then refresh this page</li>";
    echo "</ol>";
}
?>

<p style='margin-top: 40px; padding-top: 20px; border-top: 1px solid #0f0;'>
    <small>Test file: test-auth.php | <?= date('Y-m-d H:i:s') ?></small>
</p>

</body>
</html>
