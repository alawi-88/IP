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
        if (Schema::hasTable('guidelines')) {
            Schema::table('guidelines', function (Blueprint $table) {
            $table->boolean('is_visible')->default(true);

        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('guidelines')) {
            Schema::table('guidelines', function (Blueprint $table) {
            try { $table->dropColumn('is_visible'); } catch (\Exception $e) {}
        });
        }
    }
};
