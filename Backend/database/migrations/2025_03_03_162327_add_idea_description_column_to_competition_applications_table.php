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
        Schema::table('competition_applications', function (Blueprint $table) {
            $table->text('idea_description')->nullable()->after('has_idea');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('competition_applications', function (Blueprint $table) {
            $table->dropColumn('idea_description');
        });
    }
};
