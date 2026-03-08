<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::dropIfExists('program_approval_requests');
    }

    public function down()
    {
        // This migration only drops the table, so down() is empty
    }
};
