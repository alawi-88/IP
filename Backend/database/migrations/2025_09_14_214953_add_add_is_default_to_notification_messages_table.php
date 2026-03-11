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
        if (Schema::hasTable('notification_messages')) {
            Schema::table('notification_messages', function (Blueprint $table) {
            $table->boolean('is_default')->default(false);
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('notification_messages')) {
            Schema::table('notification_messages', function (Blueprint $table) {
            if (Schema::hasColumn('notification_messages', 'is_default')) { $table->dropColumn('is_default'); }
        });
        }
    }
};
