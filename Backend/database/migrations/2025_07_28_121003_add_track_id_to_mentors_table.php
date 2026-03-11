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
        if (Schema::hasTable('mentors')) {
            Schema::table('mentors', function (Blueprint $table) {
            $table->foreignId('track_id')
                ->nullable()
                ->constrained('tracks')
                ->nullOnDelete()
                ;
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('mentors')) {
            Schema::table('mentors', function (Blueprint $table) {
            try { $table->dropForeign(['track_id']); } catch (\Exception $e) {}
            try { $table->dropColumn('track_id'); } catch (\Exception $e) {}
        });
        }
    }
};
