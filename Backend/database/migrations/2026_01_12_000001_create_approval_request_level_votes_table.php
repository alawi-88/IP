<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_request_level_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_request_level_id')
                ->constrained('approval_request_levels')
                ->onDelete('cascade');
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->enum('decision', ['approved', 'rejected']);
            $table->text('comment')->nullable();
            $table->timestamps();

            // One vote per user per level (enforces "different users")
            $table->unique(['approval_request_level_id', 'user_id'], 'ar_level_votes_level_user_unique');
            $table->index(['approval_request_level_id', 'decision'], 'ar_level_votes_level_decision_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_request_level_votes');
    }
};


