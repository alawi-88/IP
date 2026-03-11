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
            $table->dropForeign(['competition_id']);
            $table->dropColumn('competition_id');
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
            $table->foreignId('competition_id')->constrained()->onDelete('cascade');
        });
        }
    }
};
