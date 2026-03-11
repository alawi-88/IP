<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();
        if (Schema::hasTable('mentor_sessions')) {
            Schema::table('mentor_sessions', function (Blueprint $table) {
            $table->text('cancellation_reason')->nullable();
        });
        }
    Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        if (Schema::hasTable('mentor_sessions')) {
            Schema::table('mentor_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('mentor_sessions', 'cancellation_reason')) { $table->dropColumn('cancellation_reason'); }
        });
        }
    }
};

