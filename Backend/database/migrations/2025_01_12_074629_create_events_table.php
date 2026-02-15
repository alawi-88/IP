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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->json('title');
            $table->json('brief');
            $table->enum('badge', ['upcoming', 'completed']);
            $table->date('date');
            $table->time('time');
            $table->enum('location', ['virtual', 'onsite']);
            $table->string('speaker_photo');
            $table->json('speaker_name');
            $table->json('speaker_experience');
            $table->json('speaker_brief');
            $table->string('event_link');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
