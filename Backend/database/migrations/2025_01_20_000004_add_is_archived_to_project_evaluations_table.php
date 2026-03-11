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
                if (Schema::hasTable('project_evaluations')) {
            Schema::table('project_evaluations', function (Blueprint $table) {
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
        if (Schema::hasTable('project_evaluations')) {
            Schema::table('project_evaluations', function (Blueprint $table) {
                            if (Schema::hasColumn('project_evaluations', 'is_archived')) { $table->dropColumn('is_archived'); }
                if (Schema::hasColumn('project_evaluations', 'archived_at')) { $table->dropColumn('archived_at'); }
        });
        }
    }
};
