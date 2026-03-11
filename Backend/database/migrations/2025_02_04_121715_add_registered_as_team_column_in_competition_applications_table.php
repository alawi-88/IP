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
            $table->boolean('registered_as_team')->default(false);
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('competition_applications')) {
            Schema::table('competition_applications', function (Blueprint $table) {
            if (Schema::hasColumn('competition_applications', 'registered_as_team')) { $table->dropColumn('registered_as_team'); }
        });
        }
    }
};
