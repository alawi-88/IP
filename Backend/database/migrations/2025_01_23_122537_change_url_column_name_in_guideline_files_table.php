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
        Schema::table('guideline_files', function (Blueprint $table) {
            $table->renameColumn('url', 'attachment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('guideline_files', function (Blueprint $table) {
            $table->renameColumn('attachment', 'url');
        });
    }
};
