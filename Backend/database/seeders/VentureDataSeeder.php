<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AIProvider;
use App\Models\VentureSectionConfig;

class VentureDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedAIProviders();
        $this->seedVentureSectionConfigs();
    }

    private function seedAIProviders(): void
    {
        AIProvider::updateOrCreate(
            ['provider' => 'claude'],
            ['model' => 'claude-sonnet-4', 'api_key' => 'sk-ant-placeholder-key', 'priority' => 1, 'is_active' => true]
        );
        AIProvider::updateOrCreate(
            ['provider' => 'openai'],
            ['model' => 'gpt-4o', 'api_key' => 'sk-proj-placeholder-key', 'priority' => 2, 'is_active' => true]
        );
        AIProvider::updateOrCreate(
            ['provider' => 'gemini'],
            ['model' => 'gemini-2.5-pro', 'api_key' => 'AIzaSy-placeholder-key', 'priority' => 3, 'is_active' => true]
        );
    }

    private function seedVentureSectionConfigs(): void
    {
        $configs = $this->getSectionConfigs();
        foreach ($configs as $config) {
            VentureSectionConfig::updateOrCreate(
                ['section_key' => $config['key']],
                [
                    'tab_key' => $config['tab_key'],
                    'label_en' => $config['label'],
                    'label_ar' => $config['label_ar'],
                    'icon' => $config['icon'] ?? 'document-text',
                    'color' => $config['color'] ?? 'blue',
                    'component_type' => $config['component_type'] ?? 'text_content',
                    'display_order' => $config['order'],
                    'is_active' => true,
                    'default_prompt' => null,
                ]
            );
        }
    }

    private function getSectionConfigs(): array
    {
        return [
            ['key' => 'dashboard_viability_score', 'label' => 'Viability Score', 'label_ar' => 'درجة الجدوى', 'tab_key' => 'dashboard', 'order' => 1, 'icon' => 'chart-bar', 'color' => 'blue', 'component_type' => 'viability_score'],
            ['key' => 'dashboard_executive_summary', 'label' => 'Executive Summary', 'label_ar' => 'الملخص التنفيذي', 'tab_key' => 'dashboard', 'order' => 2, 'icon' => 'chart-bar', 'color' => 'blue', 'component_type' => 'text_content'],
            ['key' => 'dashboard_key_metrics', 'label' => 'Key Metrics', 'label_ar' => 'المقاييس الرئيسية', 'tab_key' => 'dashboard', 'order' => 3, 'icon' => 'chart-bar', 'color' => 'blue', 'component_type' => 'stat_cards'],
            ['key' => 'dashboard_swot_overview', 'label' => 'SWOT Overview', 'label_ar' => 'نظرة عامة على SWOT', 'tab_key' => 'dashboard', 'order' => 4, 'icon' => 'chart-bar', 'color' => 'blue', 'component_type' => 'swot_grid'],
            ['key' => 'dashboard_next_steps', 'label' => 'Next Steps', 'label_ar' => 'الخطوات التالية', 'tab_key' => 'dashboard', 'order' => 5, 'icon' => 'chart-bar', 'color' => 'blue', 'component_type' => 'text_content'],
            ['key' => 'sf_value_proposition', 'label' => 'Value Proposition', 'label_ar' => 'عرض القيمة', 'tab_key' => 'strategic_frameworks', 'order' => 1, 'icon' => 'cube', 'color' => 'purple', 'component_type' => 'text_content'],
            ['key' => 'sf_business_model_canvas', 'label' => 'Business Model Canvas', 'label_ar' => 'نموذج الأعمال', 'tab_key' => 'strategic_frameworks', 'order' => 2, 'icon' => 'cube', 'color' => 'purple', 'component_type' => 'canvas_grid'],
            ['key' => 'sf_market_size', 'label' => 'Market Size (TAM/SAM/SOM)', 'label_ar' => 'حجم السوق', 'tab_key' => 'strategic_frameworks', 'order' => 3, 'icon' => 'cube', 'color' => 'purple', 'component_type' => 'funnel_chart'],
            ['key' => 'sf_pricing_strategy', 'label' => 'Pricing Strategy', 'label_ar' => 'استراتيجية التسعير', 'tab_key' => 'strategic_frameworks', 'order' => 4, 'icon' => 'cube', 'color' => 'purple', 'component_type' => 'pricing_table'],
            ['key' => 'sf_revenue_model', 'label' => 'Revenue Model', 'label_ar' => 'نموذج الإيرادات', 'tab_key' => 'strategic_frameworks', 'order' => 5, 'icon' => 'cube', 'color' => 'purple', 'component_type' => 'text_content'],
            ['key' => 'ma_target_audience', 'label' => 'Target Audience', 'label_ar' => 'الجمهور المستهدف', 'tab_key' => 'market_analysis', 'order' => 1, 'icon' => 'users', 'color' => 'green', 'component_type' => 'text_content'],
            ['key' => 'ma_market_trends', 'label' => 'Market Trends', 'label_ar' => 'اتجاهات السوق', 'tab_key' => 'market_analysis', 'order' => 2, 'icon' => 'users', 'color' => 'green', 'component_type' => 'text_content'],
            ['key' => 'ma_customer_personas', 'label' => 'Customer Personas', 'label_ar' => 'شخصيات العملاء', 'tab_key' => 'market_analysis', 'order' => 3, 'icon' => 'users', 'color' => 'green', 'component_type' => 'persona_cards'],
            ['key' => 'ma_market_barriers', 'label' => 'Market Entry Barriers', 'label_ar' => 'حواجز الدخول', 'tab_key' => 'market_analysis', 'order' => 4, 'icon' => 'users', 'color' => 'green', 'component_type' => 'text_content'],
            ['key' => 'fp_startup_costs', 'label' => 'Startup Costs', 'label_ar' => 'تكاليف التأسيس', 'tab_key' => 'financial_projections', 'order' => 1, 'icon' => 'currency-dollar', 'color' => 'amber', 'component_type' => 'cost_table'],
            ['key' => 'fp_revenue_forecast', 'label' => 'Revenue Forecast', 'label_ar' => 'توقعات الإيرادات', 'tab_key' => 'financial_projections', 'order' => 2, 'icon' => 'currency-dollar', 'color' => 'amber', 'component_type' => 'line_chart'],
            ['key' => 'fp_break_even', 'label' => 'Break-Even Analysis', 'label_ar' => 'تحليل نقطة التعادل', 'tab_key' => 'financial_projections', 'order' => 3, 'icon' => 'currency-dollar', 'color' => 'amber', 'component_type' => 'text_content'],
            ['key' => 'fp_funding_requirements', 'label' => 'Funding Requirements', 'label_ar' => 'متطلبات التمويل', 'tab_key' => 'financial_projections', 'order' => 4, 'icon' => 'currency-dollar', 'color' => 'amber', 'component_type' => 'text_content'],
            ['key' => 'mvp_feature_priority', 'label' => 'Feature Priority Matrix', 'label_ar' => 'مصفوفة أولويات الميزات', 'tab_key' => 'mvp_roadmap', 'order' => 1, 'icon' => 'clipboard-list', 'color' => 'red', 'component_type' => 'comparison_table'],
            ['key' => 'mvp_development_timeline', 'label' => 'Development Timeline', 'label_ar' => 'الجدول الزمني', 'tab_key' => 'mvp_roadmap', 'order' => 2, 'icon' => 'clipboard-list', 'color' => 'red', 'component_type' => 'timeline'],
            ['key' => 'mvp_tech_stack', 'label' => 'Tech Stack', 'label_ar' => 'المكدس التقني', 'tab_key' => 'mvp_roadmap', 'order' => 3, 'icon' => 'clipboard-list', 'color' => 'red', 'component_type' => 'text_content'],
            ['key' => 'mvp_kpis', 'label' => 'MVP KPIs', 'label_ar' => 'مؤشرات الأداء', 'tab_key' => 'mvp_roadmap', 'order' => 4, 'icon' => 'clipboard-list', 'color' => 'red', 'component_type' => 'stat_cards'],
            ['key' => 'risk_market_risks', 'label' => 'Market Risks', 'label_ar' => 'مخاطر السوق', 'tab_key' => 'risk_assessment', 'order' => 1, 'icon' => 'exclamation-triangle', 'color' => 'orange', 'component_type' => 'risk_matrix'],
            ['key' => 'risk_technical_risks', 'label' => 'Technical Risks', 'label_ar' => 'المخاطر التقنية', 'tab_key' => 'risk_assessment', 'order' => 2, 'icon' => 'exclamation-triangle', 'color' => 'orange', 'component_type' => 'risk_matrix'],
            ['key' => 'risk_financial_risks', 'label' => 'Financial Risks', 'label_ar' => 'المخاطر المالية', 'tab_key' => 'risk_assessment', 'order' => 3, 'icon' => 'exclamation-triangle', 'color' => 'orange', 'component_type' => 'risk_matrix'],
            ['key' => 'risk_mitigation', 'label' => 'Mitigation Strategies', 'label_ar' => 'استراتيجيات التخفيف', 'tab_key' => 'risk_assessment', 'order' => 4, 'icon' => 'exclamation-triangle', 'color' => 'orange', 'component_type' => 'text_content'],
            ['key' => 'gtm_launch_strategy', 'label' => 'Launch Strategy', 'label_ar' => 'استراتيجية الإطلاق', 'tab_key' => 'go_to_market', 'order' => 1, 'icon' => 'trending-up', 'color' => 'indigo', 'component_type' => 'text_content'],
            ['key' => 'gtm_marketing_plan', 'label' => 'Marketing Plan', 'label_ar' => 'خطة التسويق', 'tab_key' => 'go_to_market', 'order' => 2, 'icon' => 'trending-up', 'color' => 'indigo', 'component_type' => 'text_content'],
            ['key' => 'gtm_sales_strategy', 'label' => 'Sales Strategy', 'label_ar' => 'استراتيجية المبيعات', 'tab_key' => 'go_to_market', 'order' => 3, 'icon' => 'trending-up', 'color' => 'indigo', 'component_type' => 'text_content'],
            ['key' => 'gtm_partnerships', 'label' => 'Partnerships', 'label_ar' => 'الشراكات', 'tab_key' => 'go_to_market', 'order' => 4, 'icon' => 'trending-up', 'color' => 'indigo', 'component_type' => 'text_content'],
            ['key' => 'gtm_growth_metrics', 'label' => 'Growth Metrics', 'label_ar' => 'مقاييس النمو', 'tab_key' => 'go_to_market', 'order' => 5, 'icon' => 'trending-up', 'color' => 'indigo', 'component_type' => 'progress_bars'],
            ['key' => 'ca_competitor_overview', 'label' => 'Competitor Overview', 'label_ar' => 'نظرة عامة على المنافسين', 'tab_key' => 'competitive_analysis', 'order' => 1, 'icon' => 'shield-check', 'color' => 'cyan', 'component_type' => 'text_content'],
            ['key' => 'ca_feature_comparison', 'label' => 'Feature Comparison', 'label_ar' => 'مقارنة الميزات', 'tab_key' => 'competitive_analysis', 'order' => 2, 'icon' => 'shield-check', 'color' => 'cyan', 'component_type' => 'comparison_table'],
            ['key' => 'ca_market_positioning', 'label' => 'Market Positioning', 'label_ar' => 'تحديد موقع السوق', 'tab_key' => 'competitive_analysis', 'order' => 3, 'icon' => 'shield-check', 'color' => 'cyan', 'component_type' => 'key_value'],
            ['key' => 'ca_competitive_moat', 'label' => 'Competitive Moat', 'label_ar' => 'الخندق التنافسي', 'tab_key' => 'competitive_analysis', 'order' => 4, 'icon' => 'shield-check', 'color' => 'cyan', 'component_type' => 'text_content'],
        ];
    }
}
