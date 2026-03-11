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
        Schema::create('judges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->nullable()->constrained();
            $table->json('name');
            $table->json('experience_field')->nullable();
            $table->string('email')->unique();
            $table->string('phone_number')->nullable();
            $table->string('password');
            $table->string('otp_code')->nullable();
            $table->string('password_reset_code')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('judges');
    }
};
