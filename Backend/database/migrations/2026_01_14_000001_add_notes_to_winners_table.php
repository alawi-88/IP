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
        Schema::table('winners', function (Blueprint $table) {
            if (!Schema::hasColumn('winners', 'notes')) {
                $table->text('notes')->nullable()->after('is_visible');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('winners', function (Blueprint $table) {
            if (Schema::hasColumn('winners', 'notes')) {
                $table->dropColumn('notes');
            }
        });
    }
};


