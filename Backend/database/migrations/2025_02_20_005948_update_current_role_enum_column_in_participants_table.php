<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
            if (!Schema::hasColumn('participants', 'current_role')) { $table->string('current_role')->change(); // Temporarily convert ENUM to VARCHAR }
        });
        }

        if (Schema::hasTable('participants')) {
            Schema::table('participants', function (Blueprint $table) {
            if (!Schema::hasColumn('participants', 'current_role')) {
                $table->enum('current_role', [ 
                'high_school_student',
                'university_student',
                'recently_graduated', // Updated value
                'private_sector_employee',
                'government_sector_employee',
                'non_profit_sector_employee',
                'freelancer',
                'unemployed'
                ])->change();
            }
        });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('participants')) {
            Schema::table('participants', function (Blueprint $table) {
            $table->string('current_role')->change(); // Convert back to VARCHAR
        });
        }

        if (Schema::hasTable('participants')) {
            Schema::table('participants', function (Blueprint $table) {
            $table->enum('current_role', [
                'high_school_student',
                'university_student',
                'recent_graduate',
                'private_sector_employee',
                'government_sector_employee',
                'non_profit_sector_employee',
                'freelancer',
                'unemployed'
            ])->change();
        });
        }
    }
};
