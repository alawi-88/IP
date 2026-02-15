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
        Schema::table('competition_applications', function (Blueprint $table) {
            $table->string('status')->default('pending')->after('team_member_previous_participation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('competition_applications', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
