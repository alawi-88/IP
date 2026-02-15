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
        Schema::table('approval_request_levels', function (Blueprint $table) {
            $table->json('role_ids')->nullable()->after('level_number');
            $table->integer('required_approvals')->default(1)->after('role_ids');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('approval_request_levels', function (Blueprint $table) {
            $table->dropColumn(['role_ids', 'required_approvals']);
        });
    }
};
