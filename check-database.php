<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== FreshTrack Database Check ===\n\n";

try {
    // Test connection
    DB::connection()->getPdo();
    $dbName = DB::connection()->getDatabaseName();
    echo "✅ Connected to database: {$dbName}\n\n";
    
    // Get all tables
    $tables = DB::select('SHOW TABLES');
    $tableKey = 'Tables_in_' . $dbName;
    
    echo "📋 Tables found: " . count($tables) . "\n\n";
    
    foreach ($tables as $table) {
        $tableName = $table->$tableKey;
        
        // Get row count
        $count = DB::table($tableName)->count();
        
        echo "  • {$tableName} ({$count} rows)\n";
    }
    
    echo "\n--- Inventory Items ---\n";
    $items = DB::table('inventory_items')->get();
    if ($items->count() > 0) {
        foreach ($items as $item) {
            echo "  • {$item->name} - Stock: {$item->stock_quantity} {$item->unit}\n";
        }
    } else {
        echo "  (No inventory items found)\n";
    }
    
    echo "\n--- Inventory Batches ---\n";
    $batches = DB::table('inventory_batches')->get();
    if ($batches->count() > 0) {
        foreach ($batches as $batch) {
            echo "  • Batch {$batch->batch_code} - Qty: {$batch->quantity}, Expires: {$batch->expiry_date}\n";
        }
    } else {
        echo "  (No batches found)\n";
    }
    
    echo "\n--- Sales Transactions ---\n";
    $sales = DB::table('sales_transactions')->get();
    if ($sales->count() > 0) {
        foreach ($sales as $sale) {
            echo "  • {$sale->transaction_code} - Total: ₱{$sale->total_amount}\n";
        }
    } else {
        echo "  (No sales transactions found)\n";
    }
    
    echo "\n--- Users ---\n";
    $users = DB::table('users')->get();
    if ($users->count() > 0) {
        foreach ($users as $user) {
            echo "  • {$user->email} ({$user->role})\n";
        }
    } else {
        echo "  (No users found)\n";
    }
    
    echo "\n✅ Database check complete!\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\nTroubleshooting:\n";
    echo "1. Make sure MySQL/MariaDB is running\n";
    echo "2. Check your .env file settings:\n";
    echo "   DB_HOST=127.0.0.1\n";
    echo "   DB_DATABASE=capstone_db\n";
    echo "   DB_USERNAME=root\n";
    echo "   DB_PASSWORD=(empty or your password)\n";
    echo "3. Run: php artisan migrate\n";
    echo "4. Import SQL file if available\n";
}
