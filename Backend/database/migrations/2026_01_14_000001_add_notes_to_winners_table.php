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
        if (Schema::hasTable('winners')) {
            Schema::table('winners', function (Blueprint $table) {
            if (!Schema::hasColumn('winners', 'notes')) {
                $table->text('notes')->nullable();
            }
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('winners')) {
            Schema::table('winners', function (Blueprint $table) {
            if (Schema::hasColumn('winners', 'notes')) {
                if (Schema::hasColumn('winners', 'notes')) { $table->dropColumn('notes'); }
            }
        });
        }
    }
};


