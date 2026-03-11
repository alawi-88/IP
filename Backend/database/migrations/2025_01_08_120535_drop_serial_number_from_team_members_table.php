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
                if (Schema::hasTable('team_members')) {
            Schema::table('team_members', function (Blueprint $table) {
            if (Schema::hasColumn('team_members', 'serial_number')) { $table->dropColumn('serial_number'); }
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('team_members')) {
            Schema::table('team_members', function (Blueprint $table) {
            $table->string('serial_number')->nullable();
        });
        }
    }
};
