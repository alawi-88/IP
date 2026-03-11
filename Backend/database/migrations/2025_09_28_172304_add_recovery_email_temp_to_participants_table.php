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
        if (Schema::hasTable('participants')) {
            Schema::table('participants', function (Blueprint $table) {
            $table->string('recovery_email_temp')->nullable();
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('participants')) {
            Schema::table('participants', function (Blueprint $table) {
            $table->dropColumn('recovery_email_temp');
        });
        }
    }
};
