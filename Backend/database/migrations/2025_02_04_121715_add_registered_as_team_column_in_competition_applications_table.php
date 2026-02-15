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
            $table->boolean('registered_as_team')->default(false)->after('participant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('competition_applications', function (Blueprint $table) {
            $table->dropColumn('registered_as_team');
        });
    }
};
