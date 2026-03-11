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
                try { $table->dropColumn('link'); } catch (\Exception $e) {}
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
                try { $table->dropColumn('linkedin'); } catch (\Exception $e) {}
            }
            if (Schema::hasColumn('mentors', 'facebook')) {
                try { $table->dropColumn('facebook'); } catch (\Exception $e) {}
            }
            if (Schema::hasColumn('mentors', 'instagram')) {
                try { $table->dropColumn('instagram'); } catch (\Exception $e) {}
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

