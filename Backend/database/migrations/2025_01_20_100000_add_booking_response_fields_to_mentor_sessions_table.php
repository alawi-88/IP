<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mentor_sessions')) {
            Schema::table('mentor_sessions', function (Blueprint $table) {
            $table->text('declined_reason')->nullable();
            $table->datetime('proposed_time')->nullable();
        });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('mentor_sessions')) {
            Schema::table('mentor_sessions', function (Blueprint $table) {
                            if (Schema::hasColumn('mentor_sessions', 'declined_reason')) { $table->dropColumn('declined_reason'); }
                if (Schema::hasColumn('mentor_sessions', 'proposed_time')) { $table->dropColumn('proposed_time'); }
        });
        }
    }
};

