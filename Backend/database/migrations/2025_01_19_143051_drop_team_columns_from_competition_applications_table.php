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
        Schema::disableForeignKeyConstraints();
        if (Schema::hasTable('competition_applications')) {
            Schema::table('competition_applications', function (Blueprint $table) {
            if (Schema::hasColumn('competition_applications', 'team_name')) { $table->dropColumn('team_name'); }
            if (Schema::hasColumn('competition_applications', 'team_logo')) { $table->dropColumn('team_logo'); }
            if (Schema::hasColumn('competition_applications', 'team_strength')) { $table->dropColumn('team_strength'); }
if (Schema::hasColumn('competition_applications', 'track_id')) { $table->dropColumn('track_id'); }
if (Schema::hasColumn('competition_applications', 'idea_challenge_id')) { $table->dropColumn('idea_challenge_id'); }

            if (Schema::hasColumn('competition_applications', 'idea_description')) { $table->dropColumn('idea_description'); }
            if (Schema::hasColumn('competition_applications', 'team_member_previous_participation')) { $table->dropColumn('team_member_previous_participation'); }
        });
        }
    Schema::enableForeignKeyConstraints();
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
