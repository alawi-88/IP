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
            // Drop the old 'link' column
            if (Schema::hasColumn('mentors', 'link')) {
                if (Schema::hasColumn('mentors', 'link')) { $table->dropColumn('link'); }
            }
        });
        }

        if (Schema::hasTable('mentors')) {
            Schema::table('mentors', function (Blueprint $table) {
            // Add new social media fields
            if (!Schema::hasColumn('mentors', 'linkedin')) {
                $table->string('linkedin')->nullable();
            }
            if (!Schema::hasColumn('mentors', 'facebook')) {
                $table->string('facebook')->nullable();
            }
            if (!Schema::hasColumn('mentors', 'instagram')) {
                $table->string('instagram')->nullable();
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
            // Drop the new social media fields
            if (Schema::hasColumn('mentors', 'linkedin')) {
                if (Schema::hasColumn('mentors', 'linkedin')) { $table->dropColumn('linkedin'); }
            }
            if (Schema::hasColumn('mentors', 'facebook')) {
                if (Schema::hasColumn('mentors', 'facebook')) { $table->dropColumn('facebook'); }
            }
            if (Schema::hasColumn('mentors', 'instagram')) {
                if (Schema::hasColumn('mentors', 'instagram')) { $table->dropColumn('instagram'); }
            }
        });
        }

        if (Schema::hasTable('mentors')) {
            Schema::table('mentors', function (Blueprint $table) {
            // Add back the 'link' column
            if (!Schema::hasColumn('mentors', 'link')) {
                $table->string('link')->nullable();
            }
        });
        }
    }
};

