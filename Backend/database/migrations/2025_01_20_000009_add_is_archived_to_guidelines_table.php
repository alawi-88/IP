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
        if (Schema::hasTable('guidelines')) {
            Schema::table('guidelines', function (Blueprint $table) {
            $table->boolean('is_archived')->default(false);
            $table->timestamp('archived_at')->nullable();
        });
        }
    Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('guidelines')) {
            Schema::table('guidelines', function (Blueprint $table) {
                            if (Schema::hasColumn('guidelines', 'is_archived')) { $table->dropColumn('is_archived'); }
                if (Schema::hasColumn('guidelines', 'archived_at')) { $table->dropColumn('archived_at'); }
        });
        }
    }
};
