<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('startups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->string('tagline')->nullable();
            $table->longText('description')->nullable();
            $table->string('logo_path')->nullable();
            $table->enum('status', ['draft', 'active', 'archived'])->default('draft');
            $table->string('sector')->nullable();
            $table->string('stage')->nullable();
            $table->date('founding_date')->nullable();
            $table->integer('team_size')->nullable();
            $table->decimal('completion_percentage', 5, 2)->default(0);
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('user_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('startups');
    }
};
