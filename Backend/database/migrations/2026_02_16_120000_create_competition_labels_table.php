<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competition_labels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->constrained()->cascadeOnDelete();
            $table->string('key')->index();
            $table->string('category')->default('general');
            $table->string('label_en');
            $table->string('label_ar')->nullable();
            $table->timestamps();

            $table->unique(['competition_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_labels');
    }
};
