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
        Schema::create('nafath_nationality_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('Nafath NationalityCode');
            $table->string('name_en')->comment('English name');
            $table->string('name_ar')->comment('Arabic name');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nafath_nationality_codes');
    }
};
