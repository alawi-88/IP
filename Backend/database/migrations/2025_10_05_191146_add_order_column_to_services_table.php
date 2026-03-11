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
                if (Schema::hasTable('services')) {
            Schema::table('services', function (Blueprint $table) {
            if (!Schema::hasColumn('services', 'order')) { $table->integer('order')->default(0); }
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('services')) {
            Schema::table('services', function (Blueprint $table) {
            if (Schema::hasColumn('services', 'order')) { $table->dropColumn('order'); }
        });
        }
    }
};
