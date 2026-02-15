<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('participants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('phone')->unique();
            $table->enum('gender', ['male', 'female']);
            $table->date('date_of_birth');
            $table->string('nationality');
            $table->string('country');
            $table->string('residence_city');
            $table->string('password');
            $table->enum('educational_background', ['high_school', 'diploma', 'bachelor', 'master', 'phd']);
            $table->enum('current_role', ['high_school_student', 'university_student', 'recently_graduated', 'private_sector_employee', 'government_sector_employee', 'non_profit_sector_employee', 'freelancer', 'unemployed']);
            $table->string('place_of_work_study')->nullable();
            $table->enum('years_of_experience', ['less_than_one', 'one_to_three', 'three_to_five', 'five_to_ten', 'more_than_ten', 'no_experience']);
            $table->text('experience_or_skills')->nullable();
            $table->text('key_achievements')->nullable();
            $table->string('password_reset_code')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
