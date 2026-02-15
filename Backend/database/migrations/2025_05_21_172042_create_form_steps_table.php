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
         Schema::create('form_steps', function (Blueprint $table) {
             $table->id();
             $table->foreignId('form_id')->constrained()->cascadeOnDelete();
             $table->string('name');
             $table->integer('step_order')->default(1);
             $table->json('field_ids')->nullable();
             $table->timestamps();
         });
     }

    public function down()
    {
        Schema::dropIfExists('form_steps');
    }
};
