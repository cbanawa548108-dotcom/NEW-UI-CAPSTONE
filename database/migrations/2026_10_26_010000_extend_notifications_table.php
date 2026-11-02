<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The module / source / severity lists grew beyond the original enums
        // (Sales, Info, "Inventory System", ...), so store them as plain strings.
        DB::statement('ALTER TABLE notifications MODIFY module VARCHAR(50) NOT NULL');
        DB::statement('ALTER TABLE notifications MODIFY source VARCHAR(50) NOT NULL');
        DB::statement('ALTER TABLE notifications MODIFY severity VARCHAR(20) NOT NULL');

        Schema::table('notifications', function (Blueprint $table) {
            $table->string('dedupe_key', 120)->nullable()->after('action_link');
            $table->datetime('dismissed_at')->nullable()->after('read_at');
            $table->unique(['user_id', 'dedupe_key']);
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'dedupe_key']);
            $table->dropColumn(['dedupe_key', 'dismissed_at']);
        });
    }
};
