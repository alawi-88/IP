<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('guideline_files', function (Blueprint $table) {
            $table->string('file_type')->default('video')->after('attachment');
            $table->json('description')->nullable()->after('file_type');
        });
        
        // Update existing records to have video type
        DB::table('guideline_files')->update(['file_type' => 'video']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('guideline_files', function (Blueprint $table) {
            $table->dropColumn(['file_type', 'description']);
        });
    }
};
