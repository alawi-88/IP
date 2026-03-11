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
        if (Schema::hasTable('mentors')) {
            Schema::table('mentors', function (Blueprint $table) {
            if (!Schema::hasColumn('mentors', 'password_reset_code_expires_at')) {
                $table->timestamp('password_reset_code_expires_at')->nullable();
            }
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('mentors')) {
            Schema::table('mentors', function (Blueprint $table) {
            if (Schema::hasColumn('mentors', 'password_reset_code_expires_at')) {
                if (Schema::hasColumn('mentors', 'password_reset_code_expires_at')) { $table->dropColumn('password_reset_code_expires_at'); }
            }
        });
        }
    }
};

