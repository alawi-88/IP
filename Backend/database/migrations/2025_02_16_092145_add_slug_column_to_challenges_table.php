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
        Schema::disableForeignKeyConstraints();
        if (Schema::hasTable('challenges')) {
            Schema::table('challenges', function (Blueprint $table) {
            $table->string('slug')->unique()->nullable();
        });
        }
    Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('challenges')) {
            Schema::table('challenges', function (Blueprint $table) {
            if (Schema::hasColumn('challenges', 'slug')) { $table->dropColumn('slug'); }
        });
        }
    }
};
