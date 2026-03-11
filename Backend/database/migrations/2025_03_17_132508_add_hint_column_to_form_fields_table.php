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
        if (Schema::hasTable('form_fields')) {
            Schema::table('form_fields', function (Blueprint $table) {
            $table->json('hint')->nullable();
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('form_fields')) {
            Schema::table('form_fields', function (Blueprint $table) {
            $table->dropColumn('hint');
        });
        }
    }
};
