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
                if (Schema::hasTable('winners')) {
            Schema::table('winners', function (Blueprint $table) {
            $table->boolean('is_visible')->default(true);
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('winners')) {
            Schema::table('winners', function (Blueprint $table) {
            if (Schema::hasColumn('winners', 'is_visible')) { $table->dropColumn('is_visible'); }
        });
        }
    }
};
