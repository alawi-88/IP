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
        if (Schema::hasTable('project_form_configs')) {
            Schema::table('project_form_configs', function (Blueprint $table) {
            $table->dropForeign(['competition_id']);
            $table->dropColumn('competition_id');
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('project_form_configs')) {
            Schema::table('project_form_configs', function (Blueprint $table) {
            //
        });
        }
    }
};
