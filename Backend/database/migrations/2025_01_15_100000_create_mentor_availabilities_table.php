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
        Schema::create('mentor_availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mentor_id')->constrained()->onDelete('cascade');
            $table->date('date')->nullable(); // For one-time slots
            $table->string('day_of_week')->nullable(); // For weekly recurring slots (monday, tuesday, etc.)
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_recurring')->default(false); // true for weekly, false for one-time
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Indexes for better query performance
            $table->index(['mentor_id', 'date']);
            $table->index(['mentor_id', 'day_of_week']);
            $table->index(['mentor_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mentor_availabilities');
    }
};

