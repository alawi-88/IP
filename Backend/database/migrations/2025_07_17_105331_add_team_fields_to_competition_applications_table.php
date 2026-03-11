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
        if (Schema::hasTable('competition_applications')) {
            Schema::table('competition_applications', function (Blueprint $table) {
            $table->string('team_name')->nullable();
            $table->string('team_logo')->nullable();
            $table->string('team_serial')->nullable();
        });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('competition_applications')) {
            Schema::table('competition_applications', function (Blueprint $table) {
            try { $table->dropColumn(['team_name', 'team_logo', 'team_serial']); } catch (\Exception $e) {}
        });
        }
    }
};
