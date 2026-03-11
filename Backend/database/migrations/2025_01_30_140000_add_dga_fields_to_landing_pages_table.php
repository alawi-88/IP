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
            $table->string('dga_registration_number')->nullable();
            $table->string('dga_certificate_url')->nullable();
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
                            if (Schema::hasColumn('landing_pages', 'dga_registration_number')) { $table->dropColumn('dga_registration_number'); }
                if (Schema::hasColumn('landing_pages', 'dga_certificate_url')) { $table->dropColumn('dga_certificate_url'); }
        });
        }
    }
};

