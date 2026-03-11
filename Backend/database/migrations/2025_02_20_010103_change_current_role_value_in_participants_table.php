<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('participants')
            ->where('current_role', '')
            ->update(['current_role' => 'recently_graduated']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('participants')) {
            Schema::table('participants', function (Blueprint $table) {
            //
        });
        }
    }
};
