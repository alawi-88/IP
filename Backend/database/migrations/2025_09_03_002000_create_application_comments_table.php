<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('competition_applications')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // admin
            $table->text('comment')->nullable();
            $table->json('attachments')->nullable(); // store multiple files
            $table->boolean('is_read')->default(false);
            $table->nullableMorphs('author'); // for participant replies
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_comments2');
    }
};
