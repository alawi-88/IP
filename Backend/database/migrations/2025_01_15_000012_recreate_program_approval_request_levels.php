<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * This is a legacy table only used for data-migration from older installs.
 * On fresh installs it will be empty; we keep it minimal and avoid FKs
 * to prevent migration ordering issues.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('program_approval_request_levels')) {
            return;
        }

        Schema::create('program_approval_request_levels', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_approval_request_levels');
    }
};
