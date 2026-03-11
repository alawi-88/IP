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
        if (Schema::hasTable('projects')) {
            Schema::table('projects', function (Blueprint $table) {
            try {                 if (Schema::hasColumn('projects', 'name')) { $table->dropColumn('name'); }
                if (Schema::hasColumn('projects', 'summary')) { $table->dropColumn('summary'); }
                if (Schema::hasColumn('projects', 'description')) { $table->dropColumn('description'); }
                if (Schema::hasColumn('projects', 'presentation_file')) { $table->dropColumn('presentation_file'); }
                if (Schema::hasColumn('projects', 'link')) { $table->dropColumn('link'); }
                if (Schema::hasColumn('projects', 'references')) { $table->dropColumn('references'); }
                if (Schema::hasColumn('projects', 'documents')) { $table->dropColumn('documents'); } } catch (\Exception $e) {}
        });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('projects')) {
            Schema::table('projects', function (Blueprint $table) {
            $table->string('name');
            $table->text('summary');
            $table->text('description');
            $table->string('presentation_file');
            $table->string('link')->nullable();
            $table->json('references')->nullable();
            $table->json('documents')->nullable();
        });
        }
    }
};
