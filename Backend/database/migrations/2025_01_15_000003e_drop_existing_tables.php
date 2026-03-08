<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Drop tables in reverse order due to foreign key constraints
        Schema::dropIfExists('program_approval_request_levels');
        Schema::dropIfExists('program_approval_requests');
    }

    public function down()
    {
        // This migration only drops tables, so down() is empty
    }
};
