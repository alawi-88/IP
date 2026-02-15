<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mentor_sessions', function (Blueprint $table) {
            $table->text('feedback_comments')->nullable()->after('feedback');
            $table->text('feedback_strengths')->nullable()->after('feedback_comments');
            $table->text('feedback_improvements')->nullable()->after('feedback_strengths');
        });
    }

    public function down(): void
    {
        Schema::table('mentor_sessions', function (Blueprint $table) {
            $table->dropColumn(['feedback_comments', 'feedback_strengths', 'feedback_improvements']);
        });
    }
};



