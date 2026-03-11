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
            $table->dropForeign('mentors_idea_path_id_foreign');
            $table->dropColumn('idea_path_id');
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
            //
        });
        }
    }
};
