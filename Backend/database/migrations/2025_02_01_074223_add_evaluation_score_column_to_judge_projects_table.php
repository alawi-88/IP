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
        if (Schema::hasTable('judge_projects')) {
            Schema::table('judge_projects', function (Blueprint $table) {
            $table->float('evaluation_score')->default(0);
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('judge_projects')) {
            Schema::table('judge_projects', function (Blueprint $table) {
            if (Schema::hasColumn('judge_projects', 'evaluation_score')) { $table->dropColumn('evaluation_score'); }
        });
        }
    }
};
