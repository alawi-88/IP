<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('venture_sections', function (Blueprint $table) {
            $table->longText('prompt_sent')->nullable()->after('content_ar');
            $table->longText('raw_response')->nullable()->after('prompt_sent');
        });
    }

    public function down(): void
    {
        Schema::table('venture_sections', function (Blueprint $table) {
            $table->dropColumn(['prompt_sent', 'raw_response']);
        });
    }
};
