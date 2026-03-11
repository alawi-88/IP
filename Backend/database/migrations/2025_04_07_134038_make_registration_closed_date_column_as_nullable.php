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
        if (Schema::hasTable('competitions')) {
            Schema::table('competitions', function (Blueprint $table) {
            if (!Schema::hasColumn('competitions', 'registration_closed_date')) { $table->date('registration_closed_date')->nullable()->change(); }
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('competitions')) {
            Schema::table('competitions', function (Blueprint $table) {
            $table->date('registration_closed_date')->nullable(false)->change();
        });
        }
    }
};
