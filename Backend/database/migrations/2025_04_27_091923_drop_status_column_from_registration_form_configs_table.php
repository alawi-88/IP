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
            try { $table->dropColumn('status'); } catch (\Exception $e) {}
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
            $table->string('status')->default('active');
        });
        }
    }
};
