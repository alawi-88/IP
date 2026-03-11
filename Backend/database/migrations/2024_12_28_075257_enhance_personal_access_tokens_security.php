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
        if (Schema::hasTable('personal_access_tokens')) {
            Schema::table('personal_access_tokens', function (Blueprint $table) {
            // Add index for better performance on token lookups
            $table->index(['tokenable_type', 'tokenable_id'], 'idx_tokenable');
            $table->index('expires_at', 'idx_expires_at');
            $table->index('last_used_at', 'idx_last_used_at');
            
            // Add column to track token creation IP
            $table->string('created_from_ip', 45)->nullable();
            
            // Add column to track last used IP
            $table->string('last_used_from_ip', 45)->nullable();
            
            // Add column to track user agent
            $table->text('last_used_user_agent')->nullable();
            
            // Add column to track token revocation reason
            $table->string('revoked_reason')->nullable();
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('personal_access_tokens')) {
            Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->dropIndex('idx_tokenable');
            $table->dropIndex('idx_expires_at');
            $table->dropIndex('idx_last_used_at');
            try { $table->dropColumn([
                'created_from_ip',
                'last_used_from_ip',
                'last_used_user_agent',
                'revoked_reason'
            ]); } catch (\Exception $e) {}
        });
        }
    }
};
