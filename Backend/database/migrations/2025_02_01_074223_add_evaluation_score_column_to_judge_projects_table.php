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
        Schema::table('judge_projects', function (Blueprint $table) {
            $table->float('evaluation_score')->default(0)->after('project_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('judge_projects', function (Blueprint $table) {
            $table->dropColumn('evaluation_score');
        });
    }
};
