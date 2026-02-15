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
        Schema::table('project_evaluations', function (Blueprint $table) {
            $table->dropForeign(['path_id']);
            $table->dropColumn('path_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_evaluations', function (Blueprint $table) {
            //
        });
    }
};
