<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
            $table->string('role_type')
                ->nullable()
                ;
        });
        }

        // any user with email starting with admin will be assigned the role of admin
        DB::table('users')->where('email', 'like', 'admin%')->update(['role' => 'admin']);
    Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'role_type')) { $table->dropColumn('role_type'); }
        });
        }
    }
};
