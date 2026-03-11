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
                if (Schema::hasTable('participants')) {
            Schema::table('participants', function (Blueprint $table) {
            $table->string('activation_code')->nullable();
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('participants')) {
            Schema::table('participants', function (Blueprint $table) {
            if (Schema::hasColumn('participants', 'activation_code')) { $table->dropColumn('activation_code'); }
        });
        }
    }
};
