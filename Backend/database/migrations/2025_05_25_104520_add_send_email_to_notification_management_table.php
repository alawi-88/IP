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
        if (Schema::hasTable('notification_management')) {
            Schema::table('notification_management', function (Blueprint $table) {
            $table->boolean('send_email')->default(false);

        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('notification_management')) {
            Schema::table('notification_management', function (Blueprint $table) {
            if (Schema::hasColumn('notification_management', 'send_email')) { $table->dropColumn('send_email'); }
        });
        }
    }
};
