<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('guideline_files')) {
            return;
        }

        if (Schema::hasTable('guideline_files')) {
            Schema::table('guideline_files', function (Blueprint $table) {
            // Determine which column exists at this point in the migration timeline.
            // On older/fresh installs the column may still be `url` (renamed later to `attachment`).
            $afterColumn = null;

            if (Schema::hasColumn('guideline_files', 'attachment')) {
                $afterColumn = 'attachment';
            } elseif (Schema::hasColumn('guideline_files', 'url')) {
                $afterColumn = 'url';
            }

            // Add file_type safely
            if (!Schema::hasColumn('guideline_files', 'file_type')) {
                if ($afterColumn) {
                    $table->string('file_type')->default('video');
                } else {
                    $table->string('file_type')->default('video');
                }
            }

            // Add description safely
            if (!Schema::hasColumn('guideline_files', 'description')) {
                $table->json('description')->nullable();
            }
        });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('guideline_files')) {
            return;
        }

        if (Schema::hasTable('guideline_files')) {
            Schema::table('guideline_files', function (Blueprint $table) {
            if (Schema::hasColumn('guideline_files', 'description')) {
                $table->dropColumn('description');
            }
            if (Schema::hasColumn('guideline_files', 'file_type')) {
                $table->dropColumn('file_type');
            }
        });
        }
    }
};