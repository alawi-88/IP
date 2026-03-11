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
        if (Schema::hasTable('registration_form_configs')) {
            Schema::table('registration_form_configs', function (Blueprint $table) {
            $table->boolean('is_active')->default(true);
        });
        }
    Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('registration_form_configs')) {
            Schema::table('registration_form_configs', function (Blueprint $table) {
            if (Schema::hasColumn('registration_form_configs', 'is_active')) { $table->dropColumn('is_active'); }
        });
        }
    }
};
