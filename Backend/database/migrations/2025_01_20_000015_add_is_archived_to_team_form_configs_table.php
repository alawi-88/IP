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
        if (Schema::hasTable('team_form_configs')) {
            Schema::table('team_form_configs', function (Blueprint $table) {
            $table->boolean('is_archived')->default(false);
            $table->timestamp('archived_at')->nullable();
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('team_form_configs')) {
            Schema::table('team_form_configs', function (Blueprint $table) {
            try { $table->dropColumn(['is_archived', 'archived_at']); } catch (\Exception $e) {}
        });
        }
    }
};
