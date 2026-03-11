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
                if (Schema::hasTable('stages')) {
            Schema::table('stages', function (Blueprint $table) {
            // Add JSON column to store multiple form IDs
            if (!Schema::hasColumn('stages', 'form_ids')) { $table->json('form_ids')->nullable()->comment('Array of form IDs associated with this stage'); }
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('stages')) {
            Schema::table('stages', function (Blueprint $table) {
            if (Schema::hasColumn('stages', 'form_ids')) { $table->dropColumn('form_ids'); }
        });
        }
    }
};

