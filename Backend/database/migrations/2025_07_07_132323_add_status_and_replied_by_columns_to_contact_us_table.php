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
        if (Schema::hasTable('contact_us')) {
            Schema::table('contact_us', function (Blueprint $table) {
            $table->enum('status', ['pending', 'resolved'])->default('pending');
            $table->unsignedBigInteger('replied_by')->nullable();
            $table->foreign('replied_by')->references('id')->on('users')->onDelete('set null');
            $table->longText('reply')->nullable();
            $table->timestamp('replied_at')->nullable();

        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('contact_us')) {
            Schema::table('contact_us', function (Blueprint $table) {
            try { $table->dropForeign(['replied_by']); } catch (\Exception $e) {}
            try { $table->dropColumn('replied_by'); } catch (\Exception $e) {}
            try { $table->dropColumn('status'); } catch (\Exception $e) {}
            try { $table->dropColumn('reply'); } catch (\Exception $e) {}
            try { $table->dropColumn('replied_at'); } catch (\Exception $e) {}
        });
        }
    }
};
