<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // If table doesn't exist (fresh installs before it's created), skip safely.
        if (!Schema::hasTable('guideline_files')) {
            return;
        }

        Schema::table('guideline_files', function (Blueprint $table) {
            // Determine which column exists at this point in migration timeline.
            $afterColumn = null;

            if (Schema::hasColumn('guideline_files', 'attachment')) {
                $afterColumn = 'attachment';
            } elseif (Schema::hasColumn('guideline_files', 'url')) {
                $afterColumn = 'url';
            }

            // file_type
            if (!Schema::hasColumn('guideline_files', 'file_type')) {
                if ($afterColumn) {
                    $table->string('file_type')->default('video')->after($afterColumn);
                } else {
                    $table->string('file_type')->default('video');
                }
            }

            // description
            if (!Schema::hasColumn('guideline_files', 'description')) {
                $table->json('description')->nullable()->after('file_type');
            }
        });

        // Update existing records to have video type (only if column exists)
        if (Schema::hasColumn('guideline_files', 'file_type')) {
            DB::table('guideline_files')->update(['file_type' => 'video']);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('guideline_files')) {
            return;
        }

        Schema::table('guideline_files', function (Blueprint $table) {
            if (Schema::hasColumn('guideline_files', 'description')) {
                $table->dropColumn('description');
            }
            if (Schema::hasColumn('guideline_files', 'file_type')) {
                $table->dropColumn('file_type');
            }
        });
    }
};