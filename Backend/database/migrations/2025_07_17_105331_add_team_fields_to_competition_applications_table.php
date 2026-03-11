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
        Schema::disableForeignKeyConstraints();
        if (Schema::hasTable('competition_applications')) {
            Schema::table('competition_applications', function (Blueprint $table) {
            $table->string('team_name')->nullable();
            $table->string('team_logo')->nullable();
            $table->string('team_serial')->nullable();
        });
        }
    Schema::enableForeignKeyConstraints();
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
