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
                if (Schema::hasTable('competition_tabs')) {
            Schema::table('competition_tabs', function (Blueprint $table) {
            if (!Schema::hasColumn('competition_tabs', 'label_en')) { $table->string('label_en')->nullable(); }
            if (!Schema::hasColumn('competition_tabs', 'label_ar')) { $table->string('label_ar')->nullable(); }
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('competition_tabs')) {
            Schema::table('competition_tabs', function (Blueprint $table) {
                            if (Schema::hasColumn('competition_tabs', 'label_en')) { $table->dropColumn('label_en'); }
                if (Schema::hasColumn('competition_tabs', 'label_ar')) { $table->dropColumn('label_ar'); }
        });
        }
    }
};
