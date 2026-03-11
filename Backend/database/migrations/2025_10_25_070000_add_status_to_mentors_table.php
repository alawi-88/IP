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
        if (Schema::hasTable('mentors')) {
            Schema::table('mentors', function (Blueprint $table) {
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->text('rejection_reason')->nullable();
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('mentors')) {
            Schema::table('mentors', function (Blueprint $table) {
                if (Schema::hasColumn('mentors', 'status')) { $table->dropColumn('status'); }
                if (Schema::hasColumn('mentors', 'approved_at')) { $table->dropColumn('approved_at'); }
                if (Schema::hasColumn('mentors', 'rejected_at')) { $table->dropColumn('rejected_at'); }
                if (Schema::hasColumn('mentors', 'approved_by')) { $table->dropColumn('approved_by'); }
                if (Schema::hasColumn('mentors', 'rejection_reason')) { $table->dropColumn('rejection_reason'); }
        });
        }
    }
};
