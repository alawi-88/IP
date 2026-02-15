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
        Schema::table('events', function (Blueprint $table) {
            // Add speakers column to store JSON data
            $table->json('speakers')->nullable()->after('location');
            
            // Remove old speaker columns
            $table->dropColumn([
                'speaker_photo',
                'speaker_name',
                'speaker_experience',
                'speaker_brief'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // Remove speakers column
            $table->dropColumn('speakers');
            
            // Add back old speaker columns
            $table->string('speaker_photo')->nullable()->after('location');
            $table->json('speaker_name')->nullable()->after('speaker_photo');
            $table->json('speaker_experience')->nullable()->after('speaker_name');
            $table->json('speaker_brief')->nullable()->after('speaker_experience');
        });
    }
};
