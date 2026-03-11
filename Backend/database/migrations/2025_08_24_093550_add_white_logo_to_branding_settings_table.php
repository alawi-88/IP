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
        Schema::disableForeignKeyConstraints();
        if (Schema::hasTable('branding_settings')) {
            Schema::table('branding_settings', function (Blueprint $table) {
            $table->string('white_logo')->nullable();
        });
        }
    Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('branding_settings')) {
            Schema::table('branding_settings', function (Blueprint $table) {
            if (Schema::hasColumn('branding_settings', 'white_logo')) { $table->dropColumn('white_logo'); }
        });
        }
    }
};
