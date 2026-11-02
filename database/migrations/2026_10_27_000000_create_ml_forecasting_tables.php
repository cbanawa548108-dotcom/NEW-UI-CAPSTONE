<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables and columns used by the SARIMAX sales forecast and the
     * XGBoost spoilage model.
     */
    public function up(): void
    {
        // Daily weather (observed history + OpenWeather forecast) used as model features
        Schema::create('weather_data', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->decimal('temperature', 5, 2);      // daily mean, °C
            $table->decimal('temp_min', 5, 2)->nullable();
            $table->decimal('temp_max', 5, 2)->nullable();
            $table->decimal('humidity', 5, 2);         // daily mean, %
            $table->decimal('rainfall', 7, 2)->default(0); // daily total, mm
            $table->string('condition')->nullable();   // e.g. "Rain", "Clouds"
            $table->boolean('is_forecast')->default(false);
            $table->string('source', 30)->default('openweather');
            $table->timestamps();
        });

        // Distinguish real sales from spoilage/wastage write-offs
        Schema::table('sales_transactions', function (Blueprint $table) {
            $table->string('type', 20)->default('sale')->after('transaction_code');
            $table->index('type');
        });

        // Original received quantity, needed to compute spoilage and turnover rates
        Schema::table('inventory_batches', function (Blueprint $table) {
            $table->decimal('initial_quantity', 10, 2)->default(0)->after('quantity');
        });
        DB::table('inventory_batches')->update(['initial_quantity' => DB::raw('quantity')]);

        // SARIMAX output: per-fruit daily demand with confidence interval
        Schema::table('ai_forecasts', function (Blueprint $table) {
            $table->foreignId('inventory_item_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->decimal('lower_bound', 10, 2)->default(0)->after('predicted_sales_qty');
            $table->decimal('upper_bound', 10, 2)->default(0)->after('lower_bound');
            $table->decimal('temperature', 5, 2)->nullable()->after('forecast_accuracy');
            $table->decimal('humidity', 5, 2)->nullable()->after('temperature');
            $table->decimal('rainfall', 7, 2)->nullable()->after('humidity');
            $table->string('model', 50)->default('SARIMAX')->after('rainfall');
        });

        // XGBoost output: spoilage risk per batch, today and the next few days
        Schema::create('spoilage_predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->date('prediction_date');
            $table->unsignedTinyInteger('horizon_day')->default(0); // 0 = today
            $table->decimal('risk_score', 5, 2);                   // 0–100 (% of remaining stock expected to spoil)
            $table->string('risk_level', 20);
            $table->decimal('stock_age_days', 6, 1);
            $table->decimal('turnover_rate', 8, 4)->default(0);
            $table->decimal('sales_trend', 8, 4)->default(0);
            $table->decimal('temperature', 5, 2)->nullable();
            $table->decimal('humidity', 5, 2)->nullable();
            $table->decimal('rainfall', 7, 2)->nullable();
            $table->string('recommendation')->nullable();
            $table->timestamps();
            $table->index(['prediction_date', 'horizon_day']);
        });

        // Training history and evaluation metrics for both models
        Schema::create('ml_model_runs', function (Blueprint $table) {
            $table->id();
            $table->string('model', 50);   // SARIMAX | XGBoost
            $table->string('status', 20);  // success | failed | skipped
            $table->unsignedInteger('training_rows')->default(0);
            $table->json('metrics')->nullable();
            $table->text('message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ml_model_runs');
        Schema::dropIfExists('spoilage_predictions');

        Schema::table('ai_forecasts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('inventory_item_id');
            $table->dropColumn(['lower_bound', 'upper_bound', 'temperature', 'humidity', 'rainfall', 'model']);
        });

        Schema::table('inventory_batches', function (Blueprint $table) {
            $table->dropColumn('initial_quantity');
        });

        Schema::table('sales_transactions', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropColumn('type');
        });

        Schema::dropIfExists('weather_data');
    }
};
