<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds editable label columns (EN/AR) to competition_tabs for custom hub tab labels.
     */
    public function up(): void
    {
        Schema::table('competition_tabs', function (Blueprint $table) {
            $table->string('label_en')->nullable();
            $table->string('label_ar')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('competition_tabs', function (Blueprint $table) {
            $table->dropColumn(['label_en', 'label_ar']);
        });
    }
};
