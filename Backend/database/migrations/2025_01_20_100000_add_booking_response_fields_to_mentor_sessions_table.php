<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mentor_sessions', function (Blueprint $table) {
            $table->text('declined_reason')->nullable()->after('notes');
            $table->datetime('proposed_time')->nullable()->after('declined_reason');
        });
    }

    public function down(): void
    {
        Schema::table('mentor_sessions', function (Blueprint $table) {
            $table->dropColumn(['declined_reason', 'proposed_time']);
        });
    }
};

