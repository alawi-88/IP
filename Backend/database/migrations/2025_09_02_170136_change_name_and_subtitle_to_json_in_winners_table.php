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
        // Ensure 'name' column contains valid JSON
        DB::table('winners')->whereRaw('JSON_VALID(name) = 0')
            ->update(['name' => DB::raw('JSON_QUOTE(name)')]);

        // Ensure 'subtitle' column contains valid JSON
        DB::table('winners')->whereRaw('JSON_VALID(subtitle) = 0')
            ->update(['subtitle' => DB::raw('JSON_QUOTE(subtitle)')]);

        if (Schema::hasTable('winners')) {
            Schema::table('winners', function (Blueprint $table) {
            $table->json('name')->change();
            $table->json('subtitle')->nullable()->change();
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
            $table->string('name')->change();
            $table->string('subtitle')->nullable()->change();
        });
        }
    }
};
