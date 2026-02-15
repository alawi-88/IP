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
        Schema::table('contact_us', function (Blueprint $table) {
            $table->enum('status', ['pending', 'resolved'])->default('pending')->after('attachments');
            $table->unsignedBigInteger('replied_by')->nullable()->after('status');
            $table->foreign('replied_by')->references('id')->on('users')->onDelete('set null');
            $table->longText('reply')->nullable()->after('status');
            $table->timestamp('replied_at')->nullable()->after('reply');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contact_us', function (Blueprint $table) {
            $table->dropForeign(['replied_by']);
            $table->dropColumn('replied_by');
            $table->dropColumn('status');
            $table->dropColumn('reply');
            $table->dropColumn('replied_at');
        });
    }
};
