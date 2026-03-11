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
            $table->float('total_score')->default(0);
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
            if (Schema::hasColumn('projects', 'total_score')) { $table->dropColumn('total_score'); }
        });
        }
    }
};
