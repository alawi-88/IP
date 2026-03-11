<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();
        if (Schema::hasTable('judges')) {
            Schema::table('judges', function (Blueprint $table) {
            $table->string('activation_code')->nullable();
        });
        }
    Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        if (Schema::hasTable('judges')) {
            Schema::table('judges', function (Blueprint $table) {
            if (Schema::hasColumn('judges', 'activation_code')) { $table->dropColumn('activation_code'); }
        });
        }
    }
};
