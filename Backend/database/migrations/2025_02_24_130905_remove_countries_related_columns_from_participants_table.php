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
            try { $table->dropColumn('nationality'); } catch (\Exception $e) {}
            try { $table->dropColumn('country'); } catch (\Exception $e) {}
            try { $table->dropColumn('residence_city'); } catch (\Exception $e) {}
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
