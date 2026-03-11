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
        if (Schema::hasTable('participants')) {
            Schema::table('participants', function (Blueprint $table) {
            $table->dropColumn('nationality');
            $table->dropColumn('country');
            $table->dropColumn('residence_city');
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('participants')) {
            Schema::table('participants', function (Blueprint $table) {
            $table->string('nationality')->nullable();
            $table->string('country')->nullable();
            $table->string('residence_city')->nullable();
        });
        }
    }
};
