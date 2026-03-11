<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('project_comments')) {
            Schema::table('project_comments', function (Blueprint $table) {
            // Drop the existing foreign key to alter nullability
});
        }

        if (Schema::hasTable('project_comments')) {
            Schema::table('project_comments', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('project_comments')) {
            Schema::table('project_comments', function (Blueprint $table) {
});
        }

        if (Schema::hasTable('project_comments')) {
            Schema::table('project_comments', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
        }
    }
};


