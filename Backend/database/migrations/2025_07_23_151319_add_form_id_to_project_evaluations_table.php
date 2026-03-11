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
        if (Schema::hasTable('project_evaluations')) {
            Schema::table('project_evaluations', function (Blueprint $table) {
            if (!Schema::hasColumn('project_evaluations', 'form_id')) { $table->unsignedBigInteger('form_id')->nullable(); }
            $table->foreign('form_id')->references('id')->on('forms')->onDelete('cascade');
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('project_evaluations')) {
            Schema::table('project_evaluations', function (Blueprint $table) {
            //
        });
        }
    }
};
