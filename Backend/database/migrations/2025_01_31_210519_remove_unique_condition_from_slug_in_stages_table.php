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
        if (Schema::hasTable('stages')) {
            Schema::table('stages', function (Blueprint $table) {
            $table->dropUnique(['slug']);
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('stages')) {
            Schema::table('stages', function (Blueprint $table) {
            $table->unique(['slug']);
        });
        }
    }
};
