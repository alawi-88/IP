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
        if (Schema::hasTable('branding_settings')) {
            Schema::table('branding_settings', function (Blueprint $table) {
            $table->string('email_logo')->nullable();
            $table->string('email_footer_footer')->nullable();
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('branding_settings')) {
            Schema::table('branding_settings', function (Blueprint $table) {
            $table->dropColumn('email_logo');
            $table->dropColumn('email_footer_footer');
        });
        }
    }
};
