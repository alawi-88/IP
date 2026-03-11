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
        Schema::table('participants', function (Blueprint $table) {
            $table->foreignId('nationality_id')
                ->nullable()
                
                ->constrained('nationalities');
            $table->foreignId('country_id')
                ->nullable()
                
                ->constrained('countries');
            $table->foreignId('residence_city_id')
                ->nullable()
                
                ->constrained('cities');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->dropForeign(['nationality_id']);
            $table->dropColumn('nationality_id');
            $table->dropForeign(['country_id']);
            $table->dropColumn('country_id');
            $table->dropForeign(['residence_city_id']);
            $table->dropColumn('residence_city_id');
        });
    }
};
