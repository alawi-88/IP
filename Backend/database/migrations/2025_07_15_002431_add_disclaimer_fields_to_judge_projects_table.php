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
        if (Schema::hasTable('judge_projects')) {
            Schema::table('judge_projects', function (Blueprint $table) {
            $table->boolean('disclaimer_accepted')->default(false);
            $table->timestamp('disclaimer_accepted_at')->nullable();
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         if (Schema::hasTable('judge_projects')) {
             Schema::table('judge_projects', function (Blueprint $table) {
            try { $table->dropColumn(['disclaimer_accepted', 'disclaimer_accepted_at']); } catch (\Exception $e) {}
        });
         }
    }
};