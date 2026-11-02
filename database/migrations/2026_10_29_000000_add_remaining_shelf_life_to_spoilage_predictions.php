<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Shelf life minus temperature-adjusted age; negative once the batch is past its shelf life
        Schema::table('spoilage_predictions', function (Blueprint $table) {
            $table->decimal('remaining_shelf_life_days', 6, 1)->nullable()->after('stock_age_days');
        });
    }

    public function down(): void
    {
        Schema::table('spoilage_predictions', function (Blueprint $table) {
            $table->dropColumn('remaining_shelf_life_days');
        });
    }
};
