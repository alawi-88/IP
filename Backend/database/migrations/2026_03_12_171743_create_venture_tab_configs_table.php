<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venture_tab_configs', function (Blueprint $table) {
            $table->id();
            $table->string('tab_slug')->unique();
            $table->string('label_en');
            $table->string('label_ar')->nullable();
            $table->string('icon')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
        });

        // Seed default tab configs
        DB::table('venture_tab_configs')->insert([
            ['tab_slug' => 'dashboard', 'label_en' => 'Dashboard', 'label_ar' => 'لوحة المعلومات', 'icon' => 'squares-2x2', 'sort_order' => 0, 'is_visible' => true, 'created_at' => now(), 'updated_at' => now()],
            ['tab_slug' => 'strategic_frameworks', 'label_en' => 'Strategic Frameworks', 'label_ar' => 'الأطر الاستراتيجية', 'icon' => 'academic-cap', 'sort_order' => 1, 'is_visible' => true, 'created_at' => now(), 'updated_at' => now()],
            ['tab_slug' => 'path_to_mvp', 'label_en' => 'Path to MVP', 'label_ar' => 'المسار نحو المنتج الأولي', 'icon' => 'rocket-launch', 'sort_order' => 2, 'is_visible' => true, 'created_at' => now(), 'updated_at' => now()],
            ['tab_slug' => 'unique_selling_points', 'label_en' => 'Unique Selling Points', 'label_ar' => 'نقاط البيع الفريدة', 'icon' => 'star', 'sort_order' => 3, 'is_visible' => true, 'created_at' => now(), 'updated_at' => now()],
            ['tab_slug' => 'customer_persona', 'label_en' => 'Customer Persona', 'label_ar' => 'شخصية العميل', 'icon' => 'user-group', 'sort_order' => 4, 'is_visible' => true, 'created_at' => now(), 'updated_at' => now()],
            ['tab_slug' => 'finances', 'label_en' => 'Finances', 'label_ar' => 'المالية', 'icon' => 'banknotes', 'sort_order' => 5, 'is_visible' => true, 'created_at' => now(), 'updated_at' => now()],
            ['tab_slug' => 'go_to_market', 'label_en' => 'Go-to-Market Strategy', 'label_ar' => 'استراتيجية الذهاب إلى السوق', 'icon' => 'megaphone', 'sort_order' => 6, 'is_visible' => true, 'created_at' => now(), 'updated_at' => now()],
            ['tab_slug' => 'competitive_analysis_vrio', 'label_en' => 'Competitive Analysis (VRIO)', 'label_ar' => 'التحليل التنافسي (VRIO)', 'icon' => 'chart-bar', 'sort_order' => 7, 'is_visible' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('venture_tab_configs');
    }
};
