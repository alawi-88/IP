<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Judge;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('judges', function (Blueprint $table) {
            $table->enum('registration_method', [
                Judge::REGISTRATION_METHOD_SELF,
                Judge::REGISTRATION_METHOD_ADMIN,
            ])->default(Judge::REGISTRATION_METHOD_ADMIN)->after('activation_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('judges', function (Blueprint $table) {
            $table->dropColumn('registration_method');
        });
    }
};
