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
            if (Schema::hasColumn('participants', 'nationality')) { $table->dropColumn('nationality'); }
            if (Schema::hasColumn('participants', 'country')) { $table->dropColumn('country'); }
            if (Schema::hasColumn('participants', 'residence_city')) { $table->dropColumn('residence_city'); }
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
