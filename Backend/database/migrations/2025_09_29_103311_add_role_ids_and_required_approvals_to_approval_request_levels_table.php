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
                if (Schema::hasTable('approval_request_levels')) {
            Schema::table('approval_request_levels', function (Blueprint $table) {
            $table->json('role_ids')->nullable();
            $table->integer('required_approvals')->default(1);
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('approval_request_levels')) {
            Schema::table('approval_request_levels', function (Blueprint $table) {
                            if (Schema::hasColumn('approval_request_levels', 'role_ids')) { $table->dropColumn('role_ids'); }
                if (Schema::hasColumn('approval_request_levels', 'required_approvals')) { $table->dropColumn('required_approvals'); }
        });
        }
    }
};
