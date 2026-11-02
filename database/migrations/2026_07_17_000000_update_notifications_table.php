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
        Schema::table('notifications', function (Blueprint $table) {
            // Drop old type column if it exists
            if (Schema::hasColumn('notifications', 'type')) {
                $table->dropColumn('type');
            }
            
            // Add new columns based on ERD only if they don't exist
            if (!Schema::hasColumn('notifications', 'module')) {
                $table->enum('module', ['SARIMAX-Forecast', 'Spoilage', 'Weather', 'Inventory'])->after('user_id');
            }
            
            if (!Schema::hasColumn('notifications', 'source')) {
                $table->enum('source', ['SARIMAX', 'XGBoost', 'Weather API'])->after('module');
            }
            
            if (!Schema::hasColumn('notifications', 'severity')) {
                $table->enum('severity', ['Low', 'Medium', 'High', 'Critical'])->after('source');
            }
            
            if (!Schema::hasColumn('notifications', 'subtitle')) {
                $table->string('subtitle', 150)->nullable()->after('title');
            }
            
            $table->text('message')->nullable()->change();
            
            if (!Schema::hasColumn('notifications', 'action_label')) {
                $table->string('action_label', 50)->nullable()->after('message');
            }
            
            if (!Schema::hasColumn('notifications', 'action_link')) {
                $table->string('action_link', 255)->nullable()->after('action_label');
            }
            
            if (!Schema::hasColumn('notifications', 'read_at')) {
                $table->datetime('read_at')->nullable()->after('is_read');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn([
                'module',
                'source',
                'severity',
                'subtitle',
                'action_label',
                'action_link',
                'read_at'
            ]);
            
            $table->string('type')->default('info')->after('message');
        });
    }
};
