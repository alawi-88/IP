<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mentor_sessions')) {
            Schema::table('mentor_sessions', function (Blueprint $table) {
            $table->text('feedback_comments')->nullable();
            $table->text('feedback_strengths')->nullable();
            $table->text('feedback_improvements')->nullable();
        });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('mentor_sessions')) {
            Schema::table('mentor_sessions', function (Blueprint $table) {
            try { $table->dropColumn(['feedback_comments', 'feedback_strengths', 'feedback_improvements']); } catch (\Exception $e) {}
        });
        }
    }
};



