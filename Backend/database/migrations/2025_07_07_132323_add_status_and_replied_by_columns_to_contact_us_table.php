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
        Schema::disableForeignKeyConstraints();
        if (Schema::hasTable('contact_us')) {
            Schema::table('contact_us', function (Blueprint $table) {
            $table->enum('status', ['pending', 'resolved'])->default('pending');
            $table->unsignedBigInteger('replied_by')->nullable();
            $table->foreign('replied_by')->references('id')->on('users')->onDelete('set null');
            $table->longText('reply')->nullable();
            $table->timestamp('replied_at')->nullable();

        });
        }
    Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('contact_us')) {
            Schema::table('contact_us', function (Blueprint $table) {
if (Schema::hasColumn('contact_us', 'replied_by')) { $table->dropColumn('replied_by'); }
            if (Schema::hasColumn('contact_us', 'status')) { $table->dropColumn('status'); }
            if (Schema::hasColumn('contact_us', 'reply')) { $table->dropColumn('reply'); }
            if (Schema::hasColumn('contact_us', 'replied_at')) { $table->dropColumn('replied_at'); }
        });
        }
    }
};
