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
        if (Schema::hasTable('team_members')) {
            Schema::table('team_members', function (Blueprint $table) {
            try { $table->dropForeign(['participant_id']); } catch (\Exception $e) {}
            try { $table->dropColumn('participant_id'); } catch (\Exception $e) {}
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('team_members')) {
            Schema::table('team_members', function (Blueprint $table) {
            $table->foreignId('participant_id')
                
                ->constrained()->cascadeOnDelete();
        });
        }
    }
};
