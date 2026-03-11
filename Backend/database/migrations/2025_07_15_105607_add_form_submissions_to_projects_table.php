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
        if (Schema::hasTable('projects')) {
            Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('form_id')->constrained('forms')->onDelete('cascade');
            $table->json('form_submissions')->nullable();
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('projects')) {
            Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('form_id');
            $table->dropColumn('form_submissions');
        });
        }
    }
};
