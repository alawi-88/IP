<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('project_comments')) {
            Schema::table('project_comments', function (Blueprint $table) {
            $table->nullableMorphs('author');
        });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('project_comments')) {
            Schema::table('project_comments', function (Blueprint $table) {
            $table->dropMorphs('author');
        });
        }
    }
};


