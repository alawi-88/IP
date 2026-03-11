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
        Schema::disableForeignKeyConstraints();
        if (Schema::hasTable('projects')) {
            Schema::table('projects', function (Blueprint $table) {
            $table->json('ai_evaluation_response')->nullable();
            $table->timestamp('ai_evaluated_at')->nullable();
        });
        }
    Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('projects')) {
            Schema::table('projects', function (Blueprint $table) {
                            if (Schema::hasColumn('projects', 'ai_evaluation_response')) { $table->dropColumn('ai_evaluation_response'); }
                if (Schema::hasColumn('projects', 'ai_evaluated_at')) { $table->dropColumn('ai_evaluated_at'); }
        });
        }
    }
};
