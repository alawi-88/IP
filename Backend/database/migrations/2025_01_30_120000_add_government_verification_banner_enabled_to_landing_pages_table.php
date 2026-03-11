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
                if (Schema::hasTable('landing_pages')) {
            Schema::table('landing_pages', function (Blueprint $table) {
            $table->boolean('government_verification_banner_enabled')->default(false);
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('landing_pages')) {
            Schema::table('landing_pages', function (Blueprint $table) {
            if (Schema::hasColumn('landing_pages', 'government_verification_banner_enabled')) { $table->dropColumn('government_verification_banner_enabled'); }
        });
        }
    }
};

