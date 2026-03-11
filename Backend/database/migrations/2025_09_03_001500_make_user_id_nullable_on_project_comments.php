<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('project_comments')) {
            // Drop the existing foreign key first
            $__fks = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'project_comments' AND CONSTRAINT_TYPE = 'FOREIGN KEY'");
            $__fkNames = array_map(fn($fk) => $fk->CONSTRAINT_NAME, $__fks);
            if (in_array('project_comments_user_id_foreign', $__fkNames)) {
                Schema::table('project_comments', fn(Blueprint $t) => $t->dropForeign(['user_id']));
            }

            // Make user_id nullable
            if (Schema::hasColumn('project_comments', 'user_id')) {
                Schema::table('project_comments', function (Blueprint $table) {
                    $table->unsignedBigInteger('user_id')->nullable()->change();
                });
            }

            // Re-add foreign key with nullOnDelete
            Schema::table('project_comments', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('project_comments')) {
            $__fks = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'project_comments' AND CONSTRAINT_TYPE = 'FOREIGN KEY'");
            $__fkNames = array_map(fn($fk) => $fk->CONSTRAINT_NAME, $__fks);
            if (in_array('project_comments_user_id_foreign', $__fkNames)) {
                Schema::table('project_comments', fn(Blueprint $t) => $t->dropForeign(['user_id']));
            }

            Schema::table('project_comments', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable(false)->change();
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }
    }
};
