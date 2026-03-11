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
        if (Schema::hasTable('nafath_settings')) {
            Schema::table('nafath_settings', function (Blueprint $table) {
            $table->string('login_method')->default('both')->comment('nafath, credentials, or both');
        });
        }
    Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('nafath_settings')) {
            Schema::table('nafath_settings', function (Blueprint $table) {
            if (Schema::hasColumn('nafath_settings', 'login_method')) { $table->dropColumn('login_method'); }
        });
        }
    }
};
