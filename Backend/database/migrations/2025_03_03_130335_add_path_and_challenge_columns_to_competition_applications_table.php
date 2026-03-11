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
        if (Schema::hasTable('competition_applications')) {
            Schema::table('competition_applications', function (Blueprint $table) {
            $table->foreignId('track_id')->nullable()->constrained('paths')->cascadeOnDelete();
            $table->foreignId('idea_challenge_id')->nullable()->constrained('challenges')->cascadeOnDelete();
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('competition_applications')) {
            Schema::table('competition_applications', function (Blueprint $table) {
            try { $table->dropForeign(['track_id']); } catch (\Exception $e) {}
            try { $table->dropColumn('track_id'); } catch (\Exception $e) {}
            try { $table->dropForeign(['idea_challenge_id']); } catch (\Exception $e) {}
            try { $table->dropColumn('idea_challenge_id'); } catch (\Exception $e) {}
        });
        }
    }
};
