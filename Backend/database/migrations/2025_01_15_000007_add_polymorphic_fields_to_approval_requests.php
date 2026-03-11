<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('approval_requests')) {
            Schema::table('approval_requests', function (Blueprint $table) {
            $table->string('target_type')->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->timestamp('executed_at')->nullable();
            
            $table->index(['target_type', 'target_id']);
            $table->index(['executed_at']);
        });
        }
    }

    public function down()
    {
        if (Schema::hasTable('approval_requests')) {
            Schema::table('approval_requests', function (Blueprint $table) {
            try { $table->dropIndex(['target_type', 'target_id']); } catch (\Exception $e) {}
            try { $table->dropIndex(['executed_at']); } catch (\Exception $e) {}
            try { $table->dropColumn(['target_type', 'target_id', 'executed_at']); } catch (\Exception $e) {}
        });
        }
    }
};
