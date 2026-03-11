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
        if (Schema::hasTable('participants')) {
            Schema::table('participants', function (Blueprint $table) {
            $table->string('login_by')->default('credentials');
            $table->json('nafath_data')->nullable();
        });
        }
    Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('participants')) {
            Schema::table('participants', function (Blueprint $table) {
                            if (Schema::hasColumn('participants', 'login_by')) { $table->dropColumn('login_by'); }
                if (Schema::hasColumn('participants', 'nafath_data')) { $table->dropColumn('nafath_data'); }
        });
        }
    }
};
