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
            if (!Schema::hasColumn('projects', 'type')) { $table->enum('type', ['submission', 'draft'])->default('submission'); }
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
            if (Schema::hasColumn('projects', 'type')) { $table->dropColumn('type'); }
        });
        }
    }
};
