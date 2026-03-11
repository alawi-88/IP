<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('form_fields')) {
            Schema::table('form_fields', function (Blueprint $table) {
            $table->foreignId('section_id')
                ->nullable()
                
                ->constrained('form_sections')
                ->onDelete('cascade');
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
            //
        });
        }
    }
};
