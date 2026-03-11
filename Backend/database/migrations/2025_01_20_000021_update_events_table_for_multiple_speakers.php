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
        if (Schema::hasTable('events')) {
            Schema::table('events', function (Blueprint $table) {
            // Add speakers column to store JSON data
            $table->json('speakers')->nullable();
            
            // Remove old speaker columns
            try {                 if (Schema::hasColumn('events', 'speaker_photo')) { $table->dropColumn('speaker_photo'); }
                if (Schema::hasColumn('events', 'speaker_name')) { $table->dropColumn('speaker_name'); }
                if (Schema::hasColumn('events', 'speaker_experience')) { $table->dropColumn('speaker_experience'); }
                if (Schema::hasColumn('events', 'speaker_brief')) { $table->dropColumn('speaker_brief'); } } catch (\Exception $e) {}
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('events')) {
            Schema::table('events', function (Blueprint $table) {
            // Remove speakers column
            if (Schema::hasColumn('events', 'speakers')) { $table->dropColumn('speakers'); }
            
            // Add back old speaker columns
            $table->string('speaker_photo')->nullable();
            $table->json('speaker_name')->nullable();
            $table->json('speaker_experience')->nullable();
            $table->json('speaker_brief')->nullable();
        });
        }
    }
};
