<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->nullable()->constrained('competitions')->nullOnDelete();
            $table->json('name'); // multilingual EN/AR
            $table->json('description')->nullable(); // multilingual EN/AR
            $table->json('data_sources'); // array of module types: ['applications', 'projects', 'tasks']
            $table->json('filters')->nullable(); // saved filter configuration
            $table->string('group_by')->nullable(); // field reference for grouping
            $table->integer('sort_order')->default(0);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_archived')->default(false);
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->index('competition_id');
            $table->index('sort_order');
            $table->index('is_archived');
        });

        Schema::create('dashboard_widgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dashboard_id')->constrained('dashboards')->cascadeOnDelete();
            $table->foreignId('form_field_id')->nullable()->constrained('form_fields')->nullOnDelete();
            $table->string('parameter_key'); // field reference key (slug)
            $table->string('aggregation_type'); // sum, average, count, rate, min, max, count_distinct, group_by_period
            $table->string('visualization_type'); // bar, pie, line, table, kpi
            $table->json('configuration')->nullable(); // additional widget config (colors, labels, etc.)
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('dashboard_id');
            $table->index('form_field_id');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_widgets');
        Schema::dropIfExists('dashboards');
    }
};
