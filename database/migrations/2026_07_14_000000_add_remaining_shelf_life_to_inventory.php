<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->integer('remaining_shelf_life_days')->default(0)->after('freshness_score');
        });

        Schema::table('inventory_batches', function (Blueprint $table) {
            $table->integer('remaining_shelf_life_days')->default(0)->after('expiry_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropColumn('remaining_shelf_life_days');
        });

        Schema::table('inventory_batches', function (Blueprint $table) {
            $table->dropColumn('remaining_shelf_life_days');
        });
    }
};
