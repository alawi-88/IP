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
            try { $table->dropColumn('team_name'); } catch (\Exception $e) {}
            try { $table->dropColumn('team_logo'); } catch (\Exception $e) {}
            try { $table->dropColumn('team_strength'); } catch (\Exception $e) {}

            try { $table->dropForeign(['track_id']); } catch (\Exception $e) {}
            try { $table->dropColumn('track_id'); } catch (\Exception $e) {}

            try { $table->dropForeign(['idea_challenge_id']); } catch (\Exception $e) {}
            try { $table->dropColumn('idea_challenge_id'); } catch (\Exception $e) {}

            try { $table->dropColumn('idea_description'); } catch (\Exception $e) {}
            try { $table->dropColumn('team_member_previous_participation'); } catch (\Exception $e) {}
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
            $table->string('team_name')->nullable();
            $table->string('team_logo')->nullable();

            $table->text('team_strength')->nullable();

            $table->foreignId('track_id')->constrained('paths')->onDelete('cascade');
            $table->foreignId('idea_challenge_id')->constrained('challenges')->onDelete('cascade');

            $table->text('idea_description')->nullable();

            $table->text('team_member_previous_participation')->nullable();
        });
        }
    }
};
