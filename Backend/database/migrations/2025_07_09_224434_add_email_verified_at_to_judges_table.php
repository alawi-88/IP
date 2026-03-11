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
        if (Schema::hasTable('judges')) {
            Schema::table('judges', function (Blueprint $table) {
            $table->timestamp('email_verified_at')->nullable();
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('judges')) {
            Schema::table('judges', function (Blueprint $table) {
            try { $table->dropColumn('email_verified_at'); } catch (\Exception $e) {}
        });
        }
    }
};
