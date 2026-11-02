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
        Schema::create('business_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('business_name');
            $table->string('owner_name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('location')->nullable();
            $table->string('currency', 10)->default('PHP');
            $table->string('role')->default('Owner');
            $table->timestamps();
        });

        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('unit')->default('kg');
            $table->decimal('price_per_unit', 10, 2)->default(0);
            $table->decimal('stock_quantity', 10, 2)->default(0);
            $table->decimal('reorder_level', 10, 2)->default(0);
            $table->integer('freshness_score')->default(100);
            $table->string('spoilage_risk')->default('Low');
            $table->string('status')->default('active');
            $table->text('storage_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->string('batch_code')->unique();
            $table->decimal('quantity', 10, 2)->default(0);
            $table->decimal('price_per_unit', 10, 2)->default(0);
            $table->date('received_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('supplier')->nullable();
            $table->string('status')->default('available');
            $table->timestamps();
        });

        Schema::create('sales_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('transaction_code')->unique();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('payment_method')->nullable();
            $table->string('status')->default('completed');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('sales_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_transaction_id')->constrained('sales_transactions')->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('inventory_batch_id')->nullable()->constrained('inventory_batches')->nullOnDelete();
            $table->decimal('quantity', 10, 2)->default(0);
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('ai_forecasts', function (Blueprint $table) {
            $table->id();
            $table->string('fruit_name');
            $table->date('forecast_date');
            $table->decimal('predicted_sales_qty', 10, 2)->default(0);
            $table->decimal('predicted_revenue', 12, 2)->default(0);
            $table->decimal('forecast_accuracy', 5, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('owner_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('email_notifications')->default(true);
            $table->boolean('sms_notifications')->default(false);
            $table->boolean('push_notifications')->default(true);
            $table->boolean('low_stock_alerts')->default(true);
            $table->boolean('spoilage_alerts')->default(true);
            $table->boolean('forecast_alerts')->default(true);
            $table->boolean('dark_mode')->default(false);
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('message');
            $table->string('type')->default('info');
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('owner_settings');
        Schema::dropIfExists('ai_forecasts');
        Schema::dropIfExists('sales_items');
        Schema::dropIfExists('sales_transactions');
        Schema::dropIfExists('inventory_batches');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('business_profiles');
    }
};
