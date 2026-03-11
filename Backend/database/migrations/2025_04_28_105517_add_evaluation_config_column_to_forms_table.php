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
        if (Schema::hasTable('forms')) {
            Schema::table('forms', function (Blueprint $table) {
            $table->json('evaluation_config')->nullable();
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('forms')) {
            Schema::table('forms', function (Blueprint $table) {
            if (Schema::hasColumn('forms', 'evaluation_config')) { $table->dropColumn('evaluation_config'); }
        });
        }
    }
};
