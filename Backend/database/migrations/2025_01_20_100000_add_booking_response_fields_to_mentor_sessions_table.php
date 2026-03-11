<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mentor_sessions')) {
            Schema::table('mentor_sessions', function (Blueprint $table) {
            $table->text('declined_reason')->nullable();
            $table->datetime('proposed_time')->nullable();
        });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('mentor_sessions')) {
            Schema::table('mentor_sessions', function (Blueprint $table) {
            try { $table->dropColumn(['declined_reason', 'proposed_time']); } catch (\Exception $e) {}
        });
        }
    }
};

