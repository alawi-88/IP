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
        if (Schema::hasTable('project_evaluations')) {
            Schema::table('project_evaluations', function (Blueprint $table) {
            try { $table->dropForeign(['path_id']); } catch (\Exception $e) {}
            try { $table->dropColumn('path_id'); } catch (\Exception $e) {}
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('project_evaluations')) {
            Schema::table('project_evaluations', function (Blueprint $table) {
            //
        });
        }
    }
};
