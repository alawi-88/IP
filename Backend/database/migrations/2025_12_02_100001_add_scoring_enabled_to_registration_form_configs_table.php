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
        if (Schema::hasTable('registration_form_configs')) {
            Schema::table('registration_form_configs', function (Blueprint $table) {
            $table->boolean('scoring_enabled')->default(false)->comment('Enable scoring for registration form submissions');
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('registration_form_configs')) {
            Schema::table('registration_form_configs', function (Blueprint $table) {
            $table->dropColumn('scoring_enabled');
        });
        }
    }
};

