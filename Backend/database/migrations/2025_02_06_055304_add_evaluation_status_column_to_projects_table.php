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
            if (!Schema::hasColumn('projects', 'evaluation_status')) { $table->boolean('evaluation_status')->default(0); }
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
            if (Schema::hasColumn('projects', 'evaluation_status')) { $table->dropColumn('evaluation_status'); }
        });
        }
    }
};
