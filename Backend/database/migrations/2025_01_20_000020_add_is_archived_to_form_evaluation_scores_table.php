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
        if (Schema::hasTable('form_evaluation_scores')) {
            Schema::table('form_evaluation_scores', function (Blueprint $table) {
            $table->boolean('is_archived')->default(false);
            $table->timestamp('archived_at')->nullable();
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('form_evaluation_scores')) {
            Schema::table('form_evaluation_scores', function (Blueprint $table) {
            $table->dropColumn(['is_archived', 'archived_at']);
        });
        }
    }
};
