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
            try { $table->dropForeign(['competition_id']); } catch (\Exception $e) {}
            try { $table->dropColumn('competition_id'); } catch (\Exception $e) {}
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
