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
            if (!Schema::hasColumn('competition_applications', 'team_name')) { $table->string('team_name')->nullable(); }
            if (!Schema::hasColumn('competition_applications', 'team_logo')) { $table->string('team_logo')->nullable(); }
            if (!Schema::hasColumn('competition_applications', 'team_serial')) { $table->string('team_serial')->nullable(); }
        });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('competition_applications')) {
            Schema::table('competition_applications', function (Blueprint $table) {
                            if (Schema::hasColumn('competition_applications', 'team_name')) { $table->dropColumn('team_name'); }
                if (Schema::hasColumn('competition_applications', 'team_logo')) { $table->dropColumn('team_logo'); }
                if (Schema::hasColumn('competition_applications', 'team_serial')) { $table->dropColumn('team_serial'); }
        });
        }
    }
};
