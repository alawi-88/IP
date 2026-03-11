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
        if (Schema::hasTable('teams')) {
            Schema::table('teams', function (Blueprint $table) {
            if (Schema::hasColumn('teams', 'team_member_previous_participation')) { $table->renameColumn('team_member_previous_participation', 'previous_participation'); }
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('teams')) {
            Schema::table('teams', function (Blueprint $table) {
            if (Schema::hasColumn('teams', 'previous_participation')) { $table->renameColumn('previous_participation', 'team_member_previous_participation'); }
        });
        }
    }
};
