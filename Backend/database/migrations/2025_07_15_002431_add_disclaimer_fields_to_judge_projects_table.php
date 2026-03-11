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
            if (!Schema::hasColumn('judge_projects', 'disclaimer_accepted')) { $table->boolean('disclaimer_accepted')->default(false); }
            if (!Schema::hasColumn('judge_projects', 'disclaimer_accepted_at')) { $table->timestamp('disclaimer_accepted_at')->nullable(); }
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
                            if (Schema::hasColumn('judge_projects', 'disclaimer_accepted')) { $table->dropColumn('disclaimer_accepted'); }
                if (Schema::hasColumn('judge_projects', 'disclaimer_accepted_at')) { $table->dropColumn('disclaimer_accepted_at'); }
        });
         }
    }
};