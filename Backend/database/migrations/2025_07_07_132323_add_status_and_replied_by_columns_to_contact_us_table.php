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
            $table->dropForeign(['replied_by']);
            $table->dropColumn('replied_by');
            $table->dropColumn('status');
            $table->dropColumn('reply');
            $table->dropColumn('replied_at');
        });
        }
    }
};
