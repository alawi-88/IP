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
        if (Schema::hasTable('mentors')) {
            Schema::table('mentors', function (Blueprint $table) {
            $table->boolean('is_visible')->default(true);

        });
        }
    Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('mentors')) {
            Schema::table('mentors', function (Blueprint $table) {
            if (Schema::hasColumn('mentors', 'is_visible')) { $table->dropColumn('is_visible'); }
        });
        }
    }
};
