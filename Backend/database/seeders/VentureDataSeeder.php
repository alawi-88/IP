<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AIProvider;
use App\Models\VentureSectionConfig;
use App\Models\Venture;
use App\Models\VentureTab;
use App\Models\VentureSection;
use App\Models\VentureCompetitor;
use App\Models\VenturePromptTemplate;

class VentureDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedAIProviders();
        $this->seedVentureSectionConfigs();
        $this->seedVentures();
    }

    private function seedAIProviders(): void
    {
        AIProvider::truncate();
        AIProvider::create(['provider' => 'claude', 'model' => 'claude-sonnet-4', 'api_key' => 'sk-ant-d7d48871c5da58e881aa4921e95c3d8d7e8f9g0h1i2j3k4l5m6n7o8p9q0r', 'priority' => 1, 'is_active' => true]);
        AIProvider::create(['provider' => 'openai', 'model' => 'gpt-4o', 'api_key' => 'sk-proj-7r8s9t0u1v2w3x4y5z6a7b8c9d0e1f2g3h4i5j6k7l8m9n0o1p2q3r4s5t6u7v', 'priority' => 2, 'is_active' => true]);
        AIProvider::create(['provider' => 'gemini', 'model' => 'gemini-2.5-pro', 'api_key' => 'AIzaSyDm7r8s9t0u1v2w3x4y5z6a7b8c9d0e1f2g3h4i5j6k7l8m9n0o1p2q3', 'priority' => 3, 'is_active' => true]);
    }

    private function seedVentureSectionConfigs(): void
    {
        VentureSectionConfig::truncate();
        $configs = $this->getSectionConfigs();
        foreach ($configs as $config) {
            VentureSectionConfig::create([
                'section_key' => $config['key'],
                'tab_key' => $config['tab_key'],
                'label_en' => $config['label'],
                'label_ar' => $config['label_ar'],
                'icon' => $config['icon'] ?? 'document-text',
                'color' => $config['color'] ?? 'blue',
                'component_type' => $config['component_type'] ?? 'text_content',
                'display_order' => $config['order'],
                'is_active' => true,
                'default_prompt' => null,
            ]);
        }
    }
    }

    private function getSectionConfigs(): array
    {
        return [
            ['key' => 'dashboard_viability_score', 'label' => 'Viability Score', 'label_ar' => 'درجة الجدوى', 'tab_key' => 'dashboard', 'group' => 'Dashboard', 'order' => 1, 'is_public' => true, 'icon' => 'chart-bar', 'color' => 'blue', 'component_type' => 'viability_score'],
            ['key' => 'dashboard_executive_summary', 'label' => 'Executive Summary', 'label_ar' => 'الملخص التنفيذي', 'tab_key' => 'dashboard', 'group' => 'Dashboard', 'order' => 2, 'is_public' => true, 'icon' => 'chart-bar', 'color' => 'blue', 'component_type' => 'text_content'],
            ['key' => 'dashboard_key_metrics', 'label' => 'Key Metrics', 'label_ar' => 'المقاييس الرئيسية', 'tab_key' => 'dashboard', 'group' => 'Dashboard', 'order' => 3, 'is_public' => true, 'icon' => 'chart-bar', 'color' => 'blue', 'component_type' => 'stat_cards'],
            ['key' => 'dashboard_swot_overview', 'label' => 'SWOT Overview', 'label_ar' => 'نظرة عامة على SWOT', 'tab_key' => 'dashboard', 'group' => 'Dashboard', 'order' => 4, 'is_public' => true, 'icon' => 'chart-bar', 'color' => 'blue', 'component_type' => 'swot_grid'],
            ['key' => 'dashboard_next_steps', 'label' => 'Next Steps', 'label_ar' => 'الخطوات التالية', 'tab_key' => 'dashboard', 'group' => 'Dashboard', 'order' => 5, 'is_public' => false, 'icon' => 'chart-bar', 'color' => 'blue', 'component_type' => 'text_content'],
            ['key' => 'sf_value_proposition', 'label' => 'Value Proposition', 'label_ar' => 'عرض القيمة', 'tab_key' => 'strategic_frameworks', 'group' => 'Strategic Frameworks', 'order' => 1, 'is_public' => true, 'icon' => 'cube', 'color' => 'purple', 'component_type' => 'text_content'],
            ['key' => 'sf_business_model_canvas', 'label' => 'Business Model Canvas', 'label_ar' => 'لوحة نموذج الأعمال', 'tab_key' => 'strategic_frameworks', 'group' => 'Strategic Frameworks', 'order' => 2, 'is_public' => true, 'icon' => 'cube', 'color' => 'purple', 'component_type' => 'key_value'],
            ['key' => 'sf_pestel_analysis', 'label' => 'PESTEL Analysis', 'label_ar' => 'تحليل بيستل', 'tab_key' => 'strategic_frameworks', 'group' => 'Strategic Frameworks', 'order' => 3, 'is_public' => true, 'icon' => 'cube', 'color' => 'purple', 'component_type' => 'swot_grid'],
            ['key' => 'sf_porters_five_forces', 'label' => "Porter's Five Forces", 'label_ar' => 'قوى بورتر الخمس', 'tab_key' => 'strategic_frameworks', 'group' => 'Strategic Frameworks', 'order' => 4, 'is_public' => true, 'icon' => 'cube', 'color' => 'purple', 'component_type' => 'key_value'],
            ['key' => 'sf_market_size', 'label' => 'Market Size (TAM/SAM/SOM)', 'label_ar' => 'حجم السوق', 'tab_key' => 'strategic_frameworks', 'group' => 'Strategic Frameworks', 'order' => 5, 'is_public' => true, 'icon' => 'cube', 'color' => 'purple', 'component_type' => 'stat_cards'],
            ['key' => 'mvp_feature_priority', 'label' => 'MVP Feature Priority', 'label_ar' => 'أولويات ميزات MVP', 'tab_key' => 'path_to_mvp', 'group' => 'Path to MVP', 'order' => 1, 'is_public' => true, 'icon' => 'rocket', 'color' => 'green', 'component_type' => 'comparison_table'],
            ['key' => 'mvp_development_roadmap', 'label' => 'MVP Development Roadmap', 'label_ar' => 'خريطة الطريق لتطوير MVP', 'tab_key' => 'path_to_mvp', 'group' => 'Path to MVP', 'order' => 2, 'is_public' => true, 'icon' => 'rocket', 'color' => 'green', 'component_type' => 'journey_timeline'],
            ['key' => 'mvp_tech_stack', 'label' => 'Tech Stack', 'label_ar' => 'مجموعة التكنولوجيا', 'tab_key' => 'path_to_mvp', 'group' => 'Path to MVP', 'order' => 3, 'is_public' => true, 'icon' => 'rocket', 'color' => 'green', 'component_type' => 'key_value'],
            ['key' => 'mvp_resource_requirements', 'label' => 'Resource Requirements', 'label_ar' => 'متطلبات الموارد', 'tab_key' => 'path_to_mvp', 'group' => 'Path to MVP', 'order' => 4, 'is_public' => false, 'icon' => 'rocket', 'color' => 'green', 'component_type' => 'stat_cards'],
            ['key' => 'mvp_risk_mitigation', 'label' => 'Risk Mitigation', 'label_ar' => 'تخفيف المخاطر', 'tab_key' => 'path_to_mvp', 'group' => 'Path to MVP', 'order' => 5, 'is_public' => false, 'icon' => 'rocket', 'color' => 'green', 'component_type' => 'text_content'],
            ['key' => 'usp_unique_selling_points', 'label' => 'Unique Selling Points', 'label_ar' => 'نقاط البيع الفريدة', 'tab_key' => 'usp', 'group' => 'USP & Differentiation', 'order' => 1, 'is_public' => true, 'icon' => 'star', 'color' => 'orange', 'component_type' => 'text_content'],
            ['key' => 'usp_competitive_advantage', 'label' => 'Competitive Advantage', 'label_ar' => 'الميزة التنافسية', 'tab_key' => 'usp', 'group' => 'USP & Differentiation', 'order' => 2, 'is_public' => true, 'icon' => 'star', 'color' => 'orange', 'component_type' => 'comparison_table'],
            ['key' => 'usp_differentiation_strategy', 'label' => 'Differentiation Strategy', 'label_ar' => 'استراتيجية التمايز', 'tab_key' => 'usp', 'group' => 'USP & Differentiation', 'order' => 3, 'is_public' => true, 'icon' => 'star', 'color' => 'orange', 'component_type' => 'text_content'],
            ['key' => 'usp_value_chain', 'label' => 'Value Chain', 'label_ar' => 'سلسلة القيمة', 'tab_key' => 'usp', 'group' => 'USP & Differentiation', 'order' => 4, 'is_public' => false, 'icon' => 'star', 'color' => 'orange', 'component_type' => 'key_value'],
            ['key' => 'cp_primary_persona', 'label' => 'Primary Persona', 'label_ar' => 'الشخصية الأساسية', 'tab_key' => 'customer_persona', 'group' => 'Customer Persona', 'order' => 1, 'is_public' => true, 'icon' => 'users', 'color' => 'pink', 'component_type' => 'persona_card'],
            ['key' => 'cp_secondary_persona', 'label' => 'Secondary Persona', 'label_ar' => 'الشخصية الثانوية', 'tab_key' => 'customer_persona', 'group' => 'Customer Persona', 'order' => 2, 'is_public' => true, 'icon' => 'users', 'color' => 'pink', 'component_type' => 'persona_card'],
            ['key' => 'cp_buyer_journey', 'label' => 'Buyer Journey', 'label_ar' => 'رحلة المشتري', 'tab_key' => 'customer_persona', 'group' => 'Customer Persona', 'order' => 3, 'is_public' => true, 'icon' => 'users', 'color' => 'pink', 'component_type' => 'journey_timeline'],
            ['key' => 'cp_pain_points_analysis', 'label' => 'Pain Points Analysis', 'label_ar' => 'تحليل نقاط الألم', 'tab_key' => 'customer_persona', 'group' => 'Customer Persona', 'order' => 4, 'is_public' => true, 'icon' => 'users', 'color' => 'pink', 'component_type' => 'text_content'],
            ['key' => 'fin_revenue_model', 'label' => 'Revenue Model', 'label_ar' => 'نموذج الإيرادات', 'tab_key' => 'finances', 'group' => 'Finances', 'order' => 1, 'is_public' => true, 'icon' => 'currency-dollar', 'color' => 'red', 'component_type' => 'pricing_cards'],
            ['key' => 'fin_cost_structure', 'label' => 'Cost Structure', 'label_ar' => 'هيكل التكاليف', 'tab_key' => 'finances', 'group' => 'Finances', 'order' => 2, 'is_public' => true, 'icon' => 'currency-dollar', 'color' => 'red', 'component_type' => 'progress_bars'],
            ['key' => 'fin_financial_projections', 'label' => 'Financial Projections', 'label_ar' => 'التوقعات المالية', 'tab_key' => 'finances', 'group' => 'Finances', 'order' => 3, 'is_public' => false, 'icon' => 'currency-dollar', 'color' => 'red', 'component_type' => 'stat_cards'],
            ['key' => 'fin_funding_requirements', 'label' => 'Funding Requirements', 'label_ar' => 'متطلبات التمويل', 'tab_key' => 'finances', 'group' => 'Finances', 'order' => 4, 'is_public' => false, 'icon' => 'currency-dollar', 'color' => 'red', 'component_type' => 'text_content'],
            ['key' => 'fin_unit_economics', 'label' => 'Unit Economics', 'label_ar' => 'اقتصاديات الوحدة', 'tab_key' => 'finances', 'group' => 'Finances', 'order' => 5, 'is_public' => false, 'icon' => 'currency-dollar', 'color' => 'red', 'component_type' => 'key_value'],
            ['key' => 'gtm_launch_strategy', 'label' => 'Launch Strategy', 'label_ar' => 'استراتيجية الإطلاق', 'tab_key' => 'go_to_market', 'group' => 'Go-to-Market', 'order' => 1, 'is_public' => true, 'icon' => 'trending-up', 'color' => 'indigo', 'component_type' => 'text_content'],
            ['key' => 'gtm_marketing_channels', 'label' => 'Marketing Channels', 'label_ar' => 'قنوات التسويق', 'tab_key' => 'go_to_market', 'group' => 'Go-to-Market', 'order' => 2, 'is_public' => true, 'icon' => 'trending-up', 'color' => 'indigo', 'component_type' => 'stat_cards'],
            ['key' => 'gtm_sales_funnel', 'label' => 'Sales Funnel', 'label_ar' => 'قمع المبيعات', 'tab_key' => 'go_to_market', 'group' => 'Go-to-Market', 'order' => 3, 'is_public' => true, 'icon' => 'trending-up', 'color' => 'indigo', 'component_type' => 'journey_timeline'],
            ['key' => 'gtm_partnerships', 'label' => 'Partnerships', 'label_ar' => 'الشراكات', 'tab_key' => 'go_to_market', 'group' => 'Go-to-Market', 'order' => 4, 'is_public' => true, 'icon' => 'trending-up', 'color' => 'indigo', 'component_type' => 'text_content'],
            ['key' => 'gtm_growth_metrics', 'label' => 'Growth Metrics', 'label_ar' => 'مقاييس النمو', 'tab_key' => 'go_to_market', 'group' => 'Go-to-Market', 'order' => 5, 'is_public' => false, 'icon' => 'trending-up', 'color' => 'indigo', 'component_type' => 'progress_bars'],
            ['key' => 'ca_competitor_overview', 'label' => 'Competitor Overview', 'label_ar' => 'نظرة عامة على المنافسين', 'tab_key' => 'competitive_analysis', 'group' => 'Competitive Analysis', 'order' => 1, 'is_public' => true, 'icon' => 'shield-check', 'color' => 'cyan', 'component_type' => 'text_content'],
            ['key' => 'ca_feature_comparison', 'label' => 'Feature Comparison', 'label_ar' => 'مقارنة الميزات', 'tab_key' => 'competitive_analysis', 'group' => 'Competitive Analysis', 'order' => 2, 'is_public' => true, 'icon' => 'shield-check', 'color' => 'cyan', 'component_type' => 'comparison_table'],
            ['key' => 'ca_market_positioning', 'label' => 'Market Positioning', 'label_ar' => 'تحديد موقع السوق', 'tab_key' => 'competitive_analysis', 'group' => 'Competitive Analysis', 'order' => 3, 'is_public' => true, 'icon' => 'shield-check', 'color' => 'cyan', 'component_type' => 'key_value'],
            ['key' => 'ca_competitive_moat', 'label' => 'Competitive Moat', 'label_ar' => 'الخندق التنافسي', 'tab_key' => 'competitive_analysis', 'group' => 'Competitive Analysis', 'order' => 4, 'is_public' => false, 'icon' => 'shield-check', 'color' => 'cyan', 'component_type' => 'text_content'],
        ];
    }php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AIProvider;
use App\Models\VentureSectionConfig;
use App\Models\Venture;
use App\Models\VentureTab;
use App\Models\VentureSection;
use App\Models\VentureCompetitor;
use App\Models\VenturePromptTemplate;

class VentureDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedAIProviders();
        $this->seedVentureSectionConfigs();
        $this->seedVentures();
    }

    private function seedAIProviders(): void
    {
        AIProvider::truncate();
        AIProvider::create(['provider' => 'claude', 'model' => 'claude-sonnet-4', 'api_key' => 'sk-ant-d7d48871c5da58e881aa4921e95c3d8d7e8f9g0h1i2j3k4l5m6n7o8p9q0r', 'priority' => 1, 'is_active' => true]);
        AIProvider::create(['provider' => 'openai', 'model' => 'gpt-4o', 'api_key' => 'sk-proj-7r8s9t0u1v2w3x4y5z6a7b8c9d0e1f2g3h4i5j6k7l8m9n0o1p2q3r4s5t6u7v', 'priority' => 2, 'is_active' => true]);
        AIProvider::create(['provider' => 'gemini', 'model' => 'gemini-2.5-pro', 'api_key' => 'AIzaSyDm7r8s9t0u1v2w3x4y5z6a7b8c9d0e1f2g3h4i5j6k7l8m9n0o1p2q3', 'priority' => 3, 'is_active' => true]);
    }

    private function seedVentureSectionConfigs(): void
    {
        VentureSectionConfig::truncate();
        $configs = $this->getSectionConfigs();
        foreach ($configs as $config) {
            VentureSectionConfig::create($config);
        }
    }

    private function getSectionConfigs(): array
    {
        return [
            ['key' => 'dashboard_viability_score', 'label' => 'Viability Score', 'label_ar' => 'درجة الجدوى', 'tab_key' => 'dashboard', 'group' => 'Dashboard', 'order' => 1, 'is_public' => true],
            ['key' => 'dashboard_executive_summary', 'label' => 'Executive Summary', 'label_ar' => 'الملخص التنفيذي', 'tab_key' => 'dashboard', 'group' => 'Dashboard', 'order' => 2, 'is_public' => true],
            ['key' => 'dashboard_key_metrics', 'label' => 'Key Metrics', 'label_ar' => 'المقاييس الرئيسية', 'tab_key' => 'dashboard', 'group' => 'Dashboard', 'order' => 3, 'is_public' => true],
            ['key' => 'dashboard_swot_overview', 'label' => 'SWOT Overview', 'label_ar' => 'نظرة عامة على SWOT', 'tab_key' => 'dashboard', 'group' => 'Dashboard', 'order' => 4, 'is_public' => true],
            ['key' => 'dashboard_next_steps', 'label' => 'Next Steps', 'label_ar' => 'الخطوات التالية', 'tab_key' => 'dashboard', 'group' => 'Dashboard', 'order' => 5, 'is_public' => false],
            ['key' => 'sf_value_proposition', 'label' => 'Value Proposition', 'label_ar' => 'عرض القيمة', 'tab_key' => 'strategic_frameworks', 'group' => 'Strategic Frameworks', 'order' => 1, 'is_public' => true],
            ['key' => 'sf_business_model_canvas', 'label' => 'Business Model Canvas', 'label_ar' => 'لوحة نموذج الأعمال', 'tab_key' => 'strategic_frameworks', 'group' => 'Strategic Frameworks', 'order' => 2, 'is_public' => true],
            ['key' => 'sf_pestel_analysis', 'label' => 'PESTEL Analysis', 'label_ar' => 'تحليل بيستل', 'tab_key' => 'strategic_frameworks', 'group' => 'Strategic Frameworks', 'order' => 3, 'is_public' => true],
            ['key' => 'sf_porters_five_forces', 'label' => "Porter's Five Forces", 'label_ar' => 'قوى بورتر الخمس', 'tab_key' => 'strategic_frameworks', 'group' => 'Strategic Frameworks', 'order' => 4, 'is_public' => true],
            ['key' => 'sf_market_size', 'label' => 'Market Size (TAM/SAM/SOM)', 'label_ar' => 'حجم السوق', 'tab_key' => 'strategic_frameworks', 'group' => 'Strategic Frameworks', 'order' => 5, 'is_public' => true],
            ['key' => 'mvp_feature_priority', 'label' => 'MVP Feature Priority', 'label_ar' => 'أولويات ميزات MVP', 'tab_key' => 'path_to_mvp', 'group' => 'Path to MVP', 'order' => 1, 'is_public' => true],
            ['key' => 'mvp_development_roadmap', 'label' => 'MVP Development Roadmap', 'label_ar' => 'خريطة الطريق لتطوير MVP', 'tab_key' => 'path_to_mvp', 'group' => 'Path to MVP', 'order' => 2, 'is_public' => true],
            ['key' => 'mvp_tech_stack', 'label' => 'Tech Stack', 'label_ar' => 'مجموعة التكنولوجيا', 'tab_key' => 'path_to_mvp', 'group' => 'Path to MVP', 'order' => 3, 'is_public' => true],
            ['key' => 'mvp_resource_requirements', 'label' => 'Resource Requirements', 'label_ar' => 'متطلبات الموارد', 'tab_key' => 'path_to_mvp', 'group' => 'Path to MVP', 'order' => 4, 'is_public' => false],
            ['key' => 'mvp_risk_mitigation', 'label' => 'Risk Mitigation', 'label_ar' => 'تخفيف المخاطر', 'tab_key' => 'path_to_mvp', 'group' => 'Path to MVP', 'order' => 5, 'is_public' => false],
            ['key' => 'usp_unique_selling_points', 'label' => 'Unique Selling Points', 'label_ar' => 'نقاط البيع الفريدة', 'tab_key' => 'usp', 'group' => 'USP & Differentiation', 'order' => 1, 'is_public' => true],
            ['key' => 'usp_competitive_advantage', 'label' => 'Competitive Advantage', 'label_ar' => 'الميزة التنافسية', 'tab_key' => 'usp', 'group' => 'USP & Differentiation', 'order' => 2, 'is_public' => true],
            ['key' => 'usp_differentiation_strategy', 'label' => 'Differentiation Strategy', 'label_ar' => 'استراتيجية التمايز', 'tab_key' => 'usp', 'group' => 'USP & Differentiation', 'order' => 3, 'is_public' => true],
            ['key' => 'usp_value_chain', 'label' => 'Value Chain', 'label_ar' => 'سلسلة القيمة', 'tab_key' => 'usp', 'group' => 'USP & Differentiation', 'order' => 4, 'is_public' => false],
            ['key' => 'cp_primary_persona', 'label' => 'Primary Persona', 'label_ar' => 'الشخصية الأساسية', 'tab_key' => 'customer_persona', 'group' => 'Customer Persona', 'order' => 1, 'is_public' => true],
            ['key' => 'cp_secondary_persona', 'label' => 'Secondary Persona', 'label_ar' => 'الشخصية الثانوية', 'tab_key' => 'customer_persona', 'group' => 'Customer Persona', 'order' => 2, 'is_public' => true],
            ['key' => 'cp_buyer_journey', 'label' => 'Buyer Journey', 'label_ar' => 'رحلة المشتري', 'tab_key' => 'customer_persona', 'group' => 'Customer Persona', 'order' => 3, 'is_public' => true],
            ['key' => 'cp_pain_points_analysis', 'label' => 'Pain Points Analysis', 'label_ar' => 'تحليل نقاط الألم', 'tab_key' => 'customer_persona', 'group' => 'Customer Persona', 'order' => 4, 'is_public' => true],
            ['key' => 'fin_revenue_model', 'label' => 'Revenue Model', 'label_ar' => 'نموذج الإيرادات', 'tab_key' => 'finances', 'group' => 'Finances', 'order' => 1, 'is_public' => true],
            ['key' => 'fin_cost_structure', 'label' => 'Cost Structure', 'label_ar' => 'هيكل التكاليف', 'tab_key' => 'finances', 'group' => 'Finances', 'order' => 2, 'is_public' => true],
            ['key' => 'fin_financial_projections', 'label' => 'Financial Projections', 'label_ar' => 'التوقعات المالية', 'tab_key' => 'finances', 'group' => 'Finances', 'order' => 3, 'is_public' => false],
            ['key' => 'fin_funding_requirements', 'label' => 'Funding Requirements', 'label_ar' => 'متطلبات التمويل', 'tab_key' => 'finances', 'group' => 'Finances', 'order' => 4, 'is_public' => false],
            ['key' => 'fin_unit_economics', 'label' => 'Unit Economics', 'label_ar' => 'اقتصاديات الوحدة', 'tab_key' => 'finances', 'group' => 'Finances', 'order' => 5, 'is_public' => false],
            ['key' => 'gtm_launch_strategy', 'label' => 'Launch Strategy', 'label_ar' => 'استراتيجية الإطلاق', 'tab_key' => 'go_to_market', 'group' => 'Go-to-Market', 'order' => 1, 'is_public' => true],
            ['key' => 'gtm_marketing_channels', 'label' => 'Marketing Channels', 'label_ar' => 'قنوات التسويق', 'tab_key' => 'go_to_market', 'group' => 'Go-to-Market', 'order' => 2, 'is_public' => true],
            ['key' => 'gtm_sales_funnel', 'label' => 'Sales Funnel', 'label_ar' => 'قمع المبيعات', 'tab_key' => 'go_to_market', 'group' => 'Go-to-Market', 'order' => 3, 'is_public' => true],
            ['key' => 'gtm_partnerships', 'label' => 'Partnerships', 'label_ar' => 'الشراكات', 'tab_key' => 'go_to_market', 'group' => 'Go-to-Market', 'order' => 4, 'is_public' => true],
            ['key' => 'gtm_growth_metrics', 'label' => 'Growth Metrics', 'label_ar' => 'مقاييس النمو', 'tab_key' => 'go_to_market', 'group' => 'Go-to-Market', 'order' => 5, 'is_public' => false],
            ['key' => 'ca_competitor_overview', 'label' => 'Competitor Overview', 'label_ar' => 'نظرة عامة على المنافسين', 'tab_key' => 'competitive_analysis', 'group' => 'Competitive Analysis', 'order' => 1, 'is_public' => true],
            ['key' => 'ca_feature_comparison', 'label' => 'Feature Comparison', 'label_ar' => 'مقارنة الميزات', 'tab_key' => 'competitive_analysis', 'group' => 'Competitive Analysis', 'order' => 2, 'is_public' => true],
            ['key' => 'ca_market_positioning', 'label' => 'Market Positioning', 'label_ar' => 'تحديد موقع السوق', 'tab_key' => 'competitive_analysis', 'group' => 'Competitive Analysis', 'order' => 3, 'is_public' => true],
            ['key' => 'ca_competitive_moat', 'label' => 'Competitive Moat', 'label_ar' => 'الخندق التنافسي', 'tab_key' => 'competitive_analysis', 'group' => 'Competitive Analysis', 'order' => 4, 'is_public' => false],
        ];
    }

    private function seedVentures(): void
    {
        Venture::truncate();
        VentureTab::truncate();
        VentureSection::truncate();
        VentureCompetitor::truncate();
        VenturePromptTemplate::truncate();

        $venturesData = $this->getVenturesData();

        foreach ($venturesData as $ventureData) {
            $venture = Venture::create([
                'name' => $ventureData['name'],
                'name_ar' => $ventureData['name_ar'],
                'description' => $ventureData['description'],
                'description_ar' => $ventureData['description_ar'],
                'industry' => $ventureData['industry'],
                'industry_ar' => $ventureData['industry_ar'],
                'status' => 'completed',
                'viability_score' => $ventureData['viability_score'],
            ]);

            $tabs = [
                ['key' => 'dashboard', 'label' => 'Dashboard', 'label_ar' => 'لوحة التحكم', 'order' => 1],
                ['key' => 'strategic_frameworks', 'label' => 'Strategic Frameworks', 'label_ar' => 'الأطر الاستراتيجية', 'order' => 2],
                ['key' => 'path_to_mvp', 'label' => 'Path to MVP', 'label_ar' => 'المسار إلى MVP', 'order' => 3],
                ['key' => 'usp', 'label' => 'USP & Differentiation', 'label_ar' => 'USP والتمايز', 'order' => 4],
                ['key' => 'customer_persona', 'label' => 'Customer Persona', 'label_ar' => 'شخصية العميل', 'order' => 5],
                ['key' => 'finances', 'label' => 'Finances', 'label_ar' => 'المالية', 'order' => 6],
                ['key' => 'go_to_market', 'label' => 'Go-to-Market', 'label_ar' => 'الدخول إلى السوق', 'order' => 7],
                ['key' => 'competitive_analysis', 'label' => 'Competitive Analysis', 'label_ar' => 'التحليل التنافسي', 'order' => 8],
            ];

            foreach ($tabs as $tabData) {
                VentureTab::create([
                    'venture_id' => $venture->id,
                    'tab_key' => $tabData['key'],
                    'label' => $tabData['label'],
                    'label_ar' => $tabData['label_ar'],
                    'display_order' => $tabData['order'],
                ]);
            }

            // Create sections - map section_key to the correct tab
            $sectionConfigs = $this->getSectionConfigs();
            $tabKeyMap = []; // section_key => tab_key
            foreach ($sectionConfigs as $cfg) {
                $tabKeyMap[$cfg['key']] = $cfg['tab_key'];
            }
            $createdTabs = VentureTab::where('venture_id', $venture->id)->get()->keyBy('tab_key');

            foreach ($ventureData['sections'] as $sectionKey => $sectionData) {
                $tabKey = $tabKeyMap[$sectionKey] ?? null;
                $tab = $tabKey ? ($createdTabs[$tabKey] ?? null) : null;
                if (!$tab) continue;

                VentureSection::create([
                    'venture_tab_id' => $tab->id,
                    'section_key' => $sectionKey,
                    'content' => $sectionData['en'] ?? null,
                    'content_ar' => $sectionData['ar'] ?? null,
                    'status' => 'completed',
                    'is_visible' => true,
                    'sort_order' => collect($sectionConfigs)->firstWhere('key', $sectionKey)['order'] ?? 1,
                    'generated_at' => now(),
                ]);
            }

            // Create competitors
            if (isset($ventureData['competitors'])) {
                foreach ($ventureData['competitors'] as $competitorData) {
                    VentureCompetitor::create([
                        'venture_id' => $venture->id,
                        'name' => $competitorData['name'],
                        'website' => $competitorData['website'],
                        'description' => $competitorData['description'],
                        'description_ar' => $competitorData['description_ar'],
                        'strengths' => json_encode($competitorData['strengths']),
                        'weaknesses' => json_encode($competitorData['weaknesses']),
                    ]);
                }
            }

            // Create prompt templates
            if (isset($ventureData['prompts'])) {
                foreach ($ventureData['prompts'] as $promptData) {
                    VenturePromptTemplate::create([
                        'venture_id' => $venture->id,
                        'section_key' => $promptData['section_key'],
                        'template' => $promptData['template'],
                        'template_ar' => $promptData['template_ar'],
                    ]);
                }
            }
        }
    }

    private function getVenturesData(): array
    {
        return [$this->getHackifyData(), $this->getSalisData(), $this->getConnectAIData(), $this->getBoudPlatformData()];
    }

    private function getHackifyData(): array
    {
        return ['name' => 'Hackify', 'name_ar' => 'هاكيفاي', 'description' => 'White-labeled innovation management platform for hackathons, competitions, and innovation events', 'description_ar' => 'منصة إدارة الابتكار المخصصة للهاكاثونات والمسابقات وفعاليات الابتكار', 'industry' => 'EdTech / Innovation Management', 'industry_ar' => 'تكنولوجيا التعليم / إدارة الابتكار', 'viability_score' => 87, 'sections' => ['dashboard_viability_score' => ['en' => ['overall' => 87, 'dimensions' => [['label' => 'Market Opportunity', 'score' => 92], ['label' => 'Product-Market Fit', 'score' => 85], ['label' => 'Business Model', 'score' => 88], ['label' => 'Team & Execution', 'score' => 82], ['label' => 'Financial Viability', 'score' => 86]]], 'ar' => ['overall' => 87, 'dimensions' => [['label' => 'فرصة السوق', 'score' => 92], ['label' => 'توافق المنتج مع السوق', 'score' => 85], ['label' => 'نموذج الأعمال', 'score' => 88], ['label' => 'الفريق والتنفيذ', 'score' => 82], ['label' => 'الجدوى المالية', 'score' => 86]]]], 'dashboard_executive_summary' => ['en' => ['title' => 'Executive Summary', 'summary' => 'Hackify is a white-labeled innovation management platform enabling organizations to run, manage, and scale hackathons and innovation competitions seamlessly. The platform addresses the growing demand for structured innovation programs in enterprises across the GCC region.', 'sections' => [['heading' => 'Market Opportunity', 'content' => 'Global hackathon market projected to reach $1.5B by 2028 at 18% CAGR. GCC corporate innovation spending increased 35% YoY. Organizations recognize hackathons as critical for innovation, talent acquisition, and engagement. Market leaders charge $15K-$50K per event, with white-label providers capturing 40% market share.'], ['heading' => 'Solution', 'content' => 'Hackify provides end-to-end management: participant registration with advanced filtering, intelligent team matching algorithms, judging workflow automation, real-time dashboards, mentor communication, post-event analytics. Fully white-labeled, API-first platform enabling seamless enterprise integration.'], ['heading' => 'Traction', 'content' => 'Serving 15+ organizations including government entities and Fortune 500 companies in MENA. Facilitated 250+ hackathons with 50K+ participants. 92% customer retention, $2.3M ARR with 40% YoY growth.'], ['heading' => 'Business Model', 'content' => 'SaaS with 3 tiers: Starter ($2K/mo for 1K participants), Professional ($5K/mo for 5K), Enterprise (custom with white-label support). Additional revenue from premium add-ons: AI judging ($XX/event) and custom workflows.']]], 'ar' => ['title' => 'الملخص التنفيذي', 'summary' => 'هاكيفاي هي منصة إدارة الابتكار المخصصة التي تمكن المنظمات من تنظيم وإدارة وتوسيع نطاق الهاكاثونات بسهولة.', 'sections' => [['heading' => 'فرصة السوق', 'content' => 'من المتوقع أن يصل سوق الهاكاثون العالمي إلى 1.5 مليار دولار بحلول عام 2028 بمعدل نمو 18% سنويًا. زادت نفقات الابتكار المؤسسي في الخليج بنسبة 35% سنويًا.'], ['heading' => 'الحل', 'content' => 'توفر هاكيفاي إدارة شاملة: تسجيل المشاركين مع التصفية المتقدمة، خوارزميات تشكيل الفريق الذكية، أتمتة سير عمل التقييم، لوحات المعلومات في الوقت الفعلي.'], ['heading' => 'الجذب', 'content' => 'تخدم 15+ منظمة بما في ذلك الكيانات الحكومية وشركات Fortune 500. سهلت 250+ هاكاثون مع 50K+ مشارك. 92% احتفاظ بالعملاء، 2.3 مليون دولار ARR مع نمو 40% سنويًا.'], ['heading' => 'نموذج الأعمال', 'content' => 'SaaS مع 3 طبقات: Starter بـ 2K دولار/شهر و Professional بـ 5K، و Enterprise بسعر مخصص. إيرادات إضافية من الإضافات المميزة.']]]], 'dashboard_key_metrics' => ['en' => [['label' => 'Current ARR', 'value' => '$2.3M', 'description' => 'Annual recurring revenue from SaaS subscriptions', 'trend' => '+40% YoY'], ['label' => 'Customer Retention', 'value' => '92%', 'description' => 'Monthly churn rate less than 1%', 'trend' => '+5% improvement'], ['label' => 'Active Customers', 'value' => '15+', 'description' => 'Enterprise and government organizations', 'trend' => '+3 Q/Q'], ['label' => 'Event Facilitation', 'value' => '250+', 'description' => 'Hackathons successfully managed', 'trend' => '+60% YoY']], 'ar' => [['label' => 'الإيراد السنوي المتكرر', 'value' => '2.3 مليون دولار', 'description' => 'من اشتراكات SaaS', 'trend' => '+40% سنويًا'], ['label' => 'احتفاظ العملاء', 'value' => '92%', 'description' => 'معدل الفقد الشهري أقل من 1%', 'trend' => '+5% تحسن'], ['label' => 'العملاء النشطون', 'value' => '15+', 'description' => 'المنظمات الحكومية والمؤسسية', 'trend' => '+3 ربع'], ['label' => 'تسهيل الفعاليات', 'value' => '250+', 'description' => 'الهاكاثونات التي تم إدارتها', 'trend' => '+60% سنويًا']]], 'dashboard_swot_overview' => ['en' => ['strengths' => ['Proprietary AI-driven team matching algorithm', 'Strong GCC/MENA presence', 'White-label flexibility', '92% customer retention', 'Predictable recurring revenue'], 'weaknesses' => ['Limited brand awareness outside MENA', 'Key person dependency', 'High CAC ($8K-$12K)', 'Enterprise integration complexity'], 'opportunities' => ['APAC/Europe expansion', 'AI judging assistance', 'Skills training programs', 'Accelerator partnerships', 'Integration marketplace'], 'threats' => ['Well-funded competition', 'Economic downturn', 'Customer consolidation', 'Open-source alternatives']], 'ar' => ['strengths' => ['خوارزمية تشكيل فريق محسّنة بالذكاء الاصطناعي', 'حضور قوي في الخليج', 'مرونة التخصيص', '92% احتفاظ بالعملاء', 'إيرادات متكررة متنبأ بها'], 'weaknesses' => ['وعي محدود بالعلامة التجارية خارج الشرق الأوسط وشمال أفريقيا', 'اعتماد على الشخصيات الرئيسية', 'CAC عالي', 'تعقيد التكامل الحكومي والمؤسسي'], 'opportunities' => ['توسع آسيا وأوروبا', 'مساعدة التقييم بالذكاء الاصطناعي', 'برامج تدريب المهارات', 'شراكات المسرعات', 'سوق التكامل'], 'threats' => ['منافسة ممولة جيدًا', 'انكماش اقتصادي', 'توحيد العملاء', 'بدائل مفتوحة المصدر']]], 'dashboard_next_steps' => ['en' => ['title' => 'Next Steps', 'summary' => 'Market expansion and product enhancement while maintaining satisfaction', 'sections' => [['heading' => 'Q1 2024', 'content' => 'Launch GTM targeting top 50 GCC enterprises. Develop case studies showing 40% cost reduction. Initiate 5 major accelerator partnerships.'], ['heading' => 'Q2 2024', 'content' => 'Release AI judging assistant. Launch advanced analytics dashboard. Release mobile app for participant management.'], ['heading' => 'H2 2024', 'content' => 'Enter APAC market with localized platform. Establish Singapore regional office. Achieve $4.5M ARR.']]], 'ar' => ['title' => 'الخطوات التالية', 'summary' => 'توسع السوق وتحسين المنتج', 'sections' => [['heading' => 'Q1 2024', 'content' => 'إطلاق GTM يستهدف أكبر 50 مؤسسة في الخليج. تطوير دراسات الحالة. بدء 5 شراكات مسرعة.'], ['heading' => 'Q2 2024', 'content' => 'إطلاق مساعد التقييم بالذكاء الاصطناعي. إطلاق لوحة معلومات متقدمة. إطلاق تطبيق الهاتف المحمول.'], ['heading' => 'H2 2024', 'content' => 'دخول سوق آسيا والمحيط الهادئ. إنشاء مكتب إقليمي في سنغافورة. تحقيق 4.5 مليون دولار ARR.']]]], 'sf_value_proposition' => ['en' => ['title' => 'Value Proposition', 'summary' => 'Eliminates complexity of organizing innovation events', 'sections' => [['heading' => 'For Enterprises', 'content' => 'Reduce event organization time by 75%. Scale from 50 to 5K participants without proportional cost increase. Gain insights from participant data. Maintain brand consistency with white-label solution.'], ['heading' => 'For Event Organizers', 'content' => 'Access enterprise infrastructure without development costs. Seamless integration. Flexible monetization models. Serve multiple organizations from single dashboard.'], ['heading' => 'For Participants', 'content' => 'Discover vetted teams through intelligent matching. Access mentorship and resources. Receive real-time feedback. Network with peers and employers.']]], 'ar' => ['title' => 'عرض القيمة', 'summary' => 'تلغي تعقيد تنظيم فعاليات الابتكار', 'sections' => [['heading' => 'للمؤسسات', 'content' => 'تقليل وقت التنظيم 75%. توسيع من 50 إلى 5K مشارك. الحصول على رؤى. الحفاظ على اتساق العلامة التجارية.'], ['heading' => 'لمنظمي الفعاليات', 'content' => 'الوصول إلى البنية التحتية دون تكاليف التطوير. التكامل السلس. نماذج التسعير المرنة.'], ['heading' => 'للمشاركين', 'content' => 'اكتشاف الفرق من خلال المطابقة الذكية. الوصول إلى الإرشاد والموارد. تلقي التعليقات في الوقت الفعلي.']]]], 'sf_business_model_canvas' => ['en' => ['Key Partners' => 'Enterprise IT, Accelerators/VCs, Government, Universities, Tech providers', 'Key Activities' => 'Platform development, Customer support, AI improvement, Market expansion', 'Key Resources' => 'AI/ML team, Cloud, Proprietary algorithm, MENA relationships', 'Value Proposition' => 'Reduce time 75%, Scale to 5K, White-label, Enterprise integration', 'Customer Relationships' => 'Dedicated account managers, Community forum, Training webinars', 'Channels' => 'Direct sales, Partner referrals, Industry events, Digital marketing', 'Customer Segments' => 'Large enterprises, Government, Accelerators, Hackathon organizers', 'Cost Structure' => 'Cloud 15%, Personnel 45%, Sales/Marketing 25%, R&D 15%', 'Revenue Streams' => 'Subscriptions 80%, Add-ons 15%, Integration services 5%'], 'ar' => ['Key Partners' => 'تكنولوجيا المعلومات الحكومية والمؤسسية، المسرعات، الوكالات الحكومية', 'Key Activities' => 'تطوير، دعم العملاء، تحسين الذكاء الاصطناعي، توسع السوق', 'Key Resources' => 'فريق الذكاء الاصطناعي، السحابة، الخوارزمية المملكية، العلاقات', 'Value Proposition' => 'تقليل الوقت، التوسع، التخصيص، التكامل الحكومي والمؤسسي', 'Customer Relationships' => 'مديرو الحسابات المخصصون، منتدى المجتمع', 'Channels' => 'المبيعات المباشرة، الإحالات، الأحداث الصناعية', 'Customer Segments' => 'المؤسسات الكبرى، الحكومة، المسرعات', 'Cost Structure' => 'السحابة 15%، الموظفون 45%، المبيعات والتسويق 25%', 'Revenue Streams' => 'الاشتراكات 80%، الإضافات 15%، خدمات التكامل 5%']], 'sf_pestel_analysis' => ['en' => ['strengths' => ['Vision 2030 support for innovation', 'GCC entrepreneurship initiatives', 'Digital transformation mandates', 'Strong tech regulatory support'], 'weaknesses' => ['Oil market volatility', 'High digital adoption costs', 'Limited local venture capital', 'Multi-jurisdiction compliance'], 'opportunities' => ['MENA startup culture shift', 'AI-powered solution advancement', 'Sustainable innovation focus', 'IP legal improvements'], 'threats' => ['Global economic slowdown', 'Protectionist policies', 'Climate change logistics impact', 'Data privacy regulatory changes']], 'ar' => ['strengths' => ['دعم رؤية 2030 للابتكار', 'مبادرات ريادة الأعمال الخليجية', 'تفويضات التحول الرقمي', 'دعم تنظيمي قوي'], 'weaknesses' => ['تقلب سوق النفط', 'تكاليف اعتماد رقمية عالية', 'رأس مال محلي محدود', 'امتثال متعدد الاختصاص'], 'opportunities' => ['تحول ثقافة الشركات الناشئة', 'تقدم حلول الذكاء الاصطناعي', 'التركيز على الابتكار المستدام', 'تحسينات القانون الفكري'], 'threats' => ['التراجع الاقتصادي العالمي', 'السياسات الحمائية', 'تأثير المناخ على الخدمات اللوجستية', 'تغييرات تنظيمية للبيانات']]], 'sf_porters_five_forces' => ['en' => ['Competitive Rivalry' => 'Moderate-high: 3-4 global competitors, fragmented by geography. Regional dominance possible.', 'Supplier Power' => 'Low: Multiple cloud providers, easily replaceable. Talent acquisition competitive.', 'Buyer Power' => 'Moderate: Enterprise bargaining power, high switching costs from data/training.', 'Threat of Substitutes' => 'Moderate: In-house possible for large firms, high opportunity cost.', 'Barriers to Entry' => 'High: Proprietary AI, customer relationships, regulatory expertise, brand.'], 'ar' => ['Competitive Rivalry' => 'متوسطة إلى عالية: 3-4 منافسين عالميين مجزأين جغرافيًا.', 'Supplier Power' => 'منخفضة: موفرو سحابة متعددون، قابلون للاستبدال بسهولة.', 'Buyer Power' => 'متوسطة: قوة تفاوضية للمؤسسات، تكاليف انتقال عالية.', 'Threat of Substitutes' => 'متوسطة: التطوير الداخلي ممكن، تكلفة الفرصة عالية.', 'Barriers to Entry' => 'عالية: خوارزميات ملكية، علاقات عملاء، خبرة تنظيمية.']], 'sf_market_size' => ['en' => [['label' => 'TAM', 'value' => '$1.5B', 'description' => 'Global hackathon market by 2028', 'trend' => '+18% CAGR'], ['label' => 'SAM', 'value' => '$450M', 'description' => 'GCC/MENA corporate innovation', 'trend' => '+35% YoY'], ['label' => 'SOM', 'value' => '$75M', 'description' => 'Conservative 5-year MENA target', 'trend' => 'By 2029'], ['label' => 'Market Share', 'value' => '3.1%', 'description' => 'Current with $2.3M ARR', 'trend' => 'Target 8% by 2026']], 'ar' => [['label' => 'إجمالي السوق', 'value' => '1.5 مليار دولار', 'description' => 'سوق الهاكاثون العالمي بحلول 2028', 'trend' => '+18% سنويًا'], ['label' => 'السوق الممكنة', 'value' => '450 مليون دولار', 'description' => 'الابتكار المؤسسي الخليجي', 'trend' => '+35% سنويًا'], ['label' => 'السوق المحققة', 'value' => '75 مليون دولار', 'description' => 'هدف محافظ 5 سنوات', 'trend' => 'بحلول 2029'], ['label' => 'حصة السوق', 'value' => '3.1%', 'description' => 'الحالية بـ 2.3 مليون دولار ARR', 'trend' => 'هدف 8% بحلول 2026']]],

    'mvp_feature_priority' => [
        'en' => [
            'headers' => ['Feature', 'Priority', 'Effort', 'Impact'],
            'rows' => [
                ['Event Creation & Management', 'High', '2 sprints', 'High - Core functionality'],
                ['Participant Registration System', 'High', '2 sprints', 'High - Essential for users'],
                ['Team Formation Tools', 'High', '1 sprint', 'High - Enables collaboration'],
                ['Real-time Leaderboard', 'Medium', '1 sprint', 'High - Engagement driver'],
                ['Idea Submission & Voting', 'High', '2 sprints', 'High - Critical workflow'],
                ['Integration with Slack/Teams', 'Medium', '1 sprint', 'Medium - Nice to have'],
                ['Analytics Dashboard', 'Medium', '2 sprints', 'Medium - Post-event insights'],
                ['Mobile App (iOS/Android)', 'Low', '4 sprints', 'Medium - Future roadmap'],
                ['API for Third-party Integration', 'Low', '3 sprints', 'Medium - Extended platform'],
                ['Advanced Judging Workflows', 'Medium', '1 sprint', 'High - Complex requirements']
            ]
        ],
        'ar' => [
            'headers' => ['الميزة', 'الأولوية', 'الجهد المطلوب', 'التأثير'],
            'rows' => [
                ['إنشاء وإدارة الفعاليات', 'عالية', 'سبرينتين', 'عالي - الوظيفة الأساسية'],
                ['نظام تسجيل المشاركين', 'عالية', 'سبرينتين', 'عالي - ضروري للمستخدمين'],
                ['أدوات تشكيل الفريق', 'عالية', 'سبرينت واحد', 'عالي - يمكن التعاون'],
                ['لوحة الترتيب الفوري', 'متوسطة', 'سبرينت واحد', 'عالي - محرك الانخراط'],
                ['تقديم والتصويت على الأفكار', 'عالية', 'سبرينتين', 'عالي - سير عمل حرج'],
                ['التكامل مع Slack/Teams', 'متوسطة', 'سبرينت واحد', 'متوسط - إضافة مفيدة'],
                ['لوحة معلومات التحليلات', 'متوسطة', 'سبرينتين', 'متوسط - رؤى ما بعد الفعالية'],
                ['تطبيق الهاتف المحمول', 'منخفضة', '4 سبرينتات', 'متوسط - خريطة الطريق المستقبلية'],
                ['واجهة برمجية للتكامل من طرف ثالث', 'منخفضة', '3 سبرينتات', 'متوسط - منصة موسعة'],
                ['سير عمل الحكام المتقدم', 'متوسطة', 'سبرينت واحد', 'عالي - متطلبات معقدة']
            ]
        ]
    ],
    'mvp_development_roadmap' => [
        'en' => [
            'stages' => [
                [
                    'title' => 'Phase 1: Foundation (Sprints 1-2)',
                    'description' => 'Build core event management and participant registration',
                    'touchpoints' => ['Event organizers', 'Participants'],
                    'actions' => ['Database schema design', 'API endpoint development', 'Authentication system'],
                    'duration' => '4 weeks'
                ],
                [
                    'title' => 'Phase 2: Engagement (Sprints 3-4)',
                    'description' => 'Launch team formation and idea submission features',
                    'touchpoints' => ['Teams', 'Judges'],
                    'actions' => ['Team matching algorithm', 'Submission workflow', 'Real-time notifications'],
                    'duration' => '4 weeks'
                ],
                [
                    'title' => 'Phase 3: Visibility (Sprints 5-6)',
                    'description' => 'Deploy leaderboards and analytics dashboard',
                    'touchpoints' => ['All users', 'Event organizers'],
                    'actions' => ['Real-time scoring engine', 'Analytics pipeline', 'Visualization UI'],
                    'duration' => '4 weeks'
                ],
                [
                    'title' => 'Phase 4: Polish & Launch (Sprint 7)',
                    'description' => 'QA, testing, and production deployment',
                    'touchpoints' => ['All stakeholders'],
                    'actions' => ['Performance optimization', 'Security hardening', 'UAT with beta users'],
                    'duration' => '2 weeks'
                ]
            ]
        ],
        'ar' => [
            'stages' => [
                [
                    'title' => 'المرحلة 1: الأساس (السبرينتات 1-2)',
                    'description' => 'بناء إدارة الفعاليات الأساسية وتسجيل المشاركين',
                    'touchpoints' => ['منظمو الفعاليات', 'المشاركون'],
                    'actions' => ['تصميم مخطط قاعدة البيانات', 'تطوير نقاط نهاية واجهة برمجية', 'نظام المصادقة'],
                    'duration' => '4 أسابيع'
                ],
                [
                    'title' => 'المرحلة 2: الانخراط (السبرينتات 3-4)',
                    'description' => 'إطلاق ميزات تشكيل الفريق وتقديم الأفكار',
                    'touchpoints' => ['الفرق', 'الحكام'],
                    'actions' => ['خوارزمية مطابقة الفريق', 'سير عمل الإرسال', 'إشعارات فورية'],
                    'duration' => '4 أسابيع'
                ],
                [
                    'title' => 'المرحلة 3: الرؤية (السبرينتات 5-6)',
                    'description' => 'نشر لوحات الترتيب ولوحة معلومات التحليلات',
                    'touchpoints' => ['جميع المستخدمين', 'منظمو الفعاليات'],
                    'actions' => ['محرك التسجيل الفوري', 'خط أنابيب التحليلات', 'واجهة مستخدم التصور'],
                    'duration' => '4 أسابيع'
                ],
                [
                    'title' => 'المرحلة 4: الصقل والإطلاق (السبرينت 7)',
                    'description' => 'اختبار الجودة والاختبار والنشر في الإنتاج',
                    'touchpoints' => ['جميع أصحاب المصلحة'],
                    'actions' => ['تحسين الأداء', 'تعزيز الأمان', 'اختبار المستخدمين مع المستخدمين التجريبيين'],
                    'duration' => '3 أسابيع'
                ]
            ]
        ]
    ],
    'mvp_tech_stack' => [
        'en' => [
            'items' => [
                ['key' => 'Frontend Framework', 'value' => 'Next.js 15 / React 19'],
                ['key' => 'Backend', 'value' => 'Node.js / Express.js'],
                ['key' => 'Database', 'value' => 'PostgreSQL 15'],
                ['key' => 'Real-time Communication', 'value' => 'WebSocket / Socket.io'],
                ['key' => 'Authentication', 'value' => 'JWT + OAuth 2.0'],
                ['key' => 'Caching', 'value' => 'Redis'],
                ['key' => 'Search', 'value' => 'Elasticsearch / Algolia'],
                ['key' => 'File Storage', 'value' => 'AWS S3 / MinIO'],
                ['key' => 'Monitoring', 'value' => 'Datadog / New Relic'],
                ['key' => 'CI/CD', 'value' => 'GitHub Actions / GitLab CI']
            ]
        ],
        'ar' => [
            'items' => [
                ['key' => 'إطار عمل الواجهة الأمامية', 'value' => 'Next.js 15 / React 19'],
                ['key' => 'الخادم الخلفي', 'value' => 'Node.js / Express.js'],
                ['key' => 'قاعدة البيانات', 'value' => 'PostgreSQL 15'],
                ['key' => 'الاتصالات الفورية', 'value' => 'WebSocket / Socket.io'],
                ['key' => 'المصادقة', 'value' => 'JWT + OAuth 2.0'],
                ['key' => 'التخزين المؤقت', 'value' => 'Redis'],
                ['key' => 'البحث', 'value' => 'Elasticsearch / Algolia'],
                ['key' => 'تخزين الملفات', 'value' => 'AWS S3 / MinIO'],
                ['key' => 'المراقبة', 'value' => 'Datadog / New Relic'],
                ['key' => 'التكامل والنشر المستمر', 'value' => 'GitHub Actions / GitLab CI']
            ]
        ]
    ],
    'mvp_resource_requirements' => [
        'en' => [
            'metrics' => [
                ['label' => 'Backend Engineers', 'value' => '3', 'description' => 'API development and architecture'],
                ['label' => 'Frontend Engineers', 'value' => '2', 'description' => 'UI/UX implementation'],
                ['label' => 'DevOps Engineer', 'value' => '1', 'description' => 'Infrastructure and deployment'],
                ['label' => 'QA Engineer', 'value' => '1', 'description' => 'Testing and quality assurance'],
                ['label' => 'Product Manager', 'value' => '1', 'description' => 'Direction and prioritization'],
                ['label' => 'Total Budget (7 weeks)', 'value' => '$280K', 'description' => 'Development and deployment costs'],
                ['label' => 'Infrastructure Monthly Cost', 'value' => '$5K', 'description' => 'Cloud services and hosting'],
                ['label' => 'Third-party Services', 'value' => '$2K', 'description' => 'APIs and integrations']
            ]
        ],
        'ar' => [
            'metrics' => [
                ['label' => 'مهندسو الخادم الخلفي', 'value' => '3', 'description' => 'تطوير واجهة برمجية والعمارة'],
                ['label' => 'مهندسو الواجهة الأمامية', 'value' => '2', 'description' => 'تنفيذ الواجهة والتجربة'],
                ['label' => 'مهندس DevOps', 'value' => '1', 'description' => 'البنية الأساسية والنشر'],
                ['label' => 'مهندس اختبار الجودة', 'value' => '1', 'description' => 'الاختبار وضمان الجودة'],
                ['label' => 'مدير المنتج', 'value' => '1', 'description' => 'التوجيه والأولويات'],
                ['label' => 'إجمالي الميزانية (7 أسابيع)', 'value' => '$280K', 'description' => 'تكاليف التطوير والنشر'],
                ['label' => 'تكلفة البنية الأساسية الشهرية', 'value' => '$5K', 'description' => 'خدمات السحابة والاستضافة'],
                ['label' => 'خدمات الطرف الثالث', 'value' => '$2K', 'description' => 'واجهات برمجية والتكاملات']
            ]
        ]
    ],
    'mvp_risk_mitigation' => [
        'en' => [
            'title' => 'MVP Risk Mitigation Strategy',
            'sections' => [
                [
                    'heading' => 'Technical Risks',
                    'content' => 'Scalability concerns addressed through microservices architecture and horizontal scaling. Real-time features validated through load testing with 10K concurrent users. Database performance optimized with proper indexing and query optimization. API rate limiting implemented to prevent abuse.'
                ],
                [
                    'heading' => 'Market Risks',
                    'content' => 'Early customer validation with 5 beta hackathons before full launch. Competitor analysis shows clear differentiation in mobile-first approach and superior judging workflows. Partnership discussions ongoing with university innovation centers and corporate innovation labs.'
                ],
                [
                    'heading' => 'Operational Risks',
                    'content' => 'Dedicated support team prepared with knowledge base and automated responses. SLA targets: 99.9% uptime, <2 hour support response. Incident response playbooks documented. Automated backups and disaster recovery procedures in place.'
                ],
                [
                    'heading' => 'Adoption Risks',
                    'content' => 'Comprehensive onboarding program with video tutorials and live support. Community-building features enable network effects. Integration with popular tools (Slack, Google Meet) reduces friction. Freemium model allows risk-free trial for organizers.'
                ]
            ]
        ],
        'ar' => [
            'title' => 'استراتيجية تخفيف مخاطر MVP',
            'sections' => [
                [
                    'heading' => 'المخاطر التقنية',
                    'content' => 'تم التعامل مع مخاوف القابلية للتوسع من خلال بنية الخدمات الدقيقة والتوسع الأفقي. تم التحقق من صحة الميزات الفورية من خلال اختبار الحمل مع 10 آلاف مستخدم متزامن. تم تحسين أداء قاعدة البيانات باستخدام الفهرسة الصحيحة وتحسين الاستعلام. تم تنفيذ حد معدل واجهة برمجية لمنع الإساءة.'
                ],
                [
                    'heading' => 'مخاطر السوق',
                    'content' => 'التحقق المبكر من العملاء مع 5 أكاديميات بيتا قبل الإطلاق الكامل. يُظهر تحليل المنافسين تمايزًا واضحًا في النهج المركز على الجوال وسير عمل الحكام الفائق. المناقشات الشراكة جارية مع مراكز الابتكار بالجامعات والمختبرات الابتكار للشركات.'
                ],
                [
                    'heading' => 'المخاطر التشغيلية',
                    'content' => 'فريق دعم مخصص مستعد مع قاعدة معرفية وردود آلية. أهداف مستويات الخدمة: توفر 99.9٪، استجابة الدعم <ساعتين. تم توثيق كتيبات الاستجابة للحوادث. النسخ الاحتياطية الآلية وإجراءات استرجاع الكوارث في محلها.'
                ],
                [
                    'heading' => 'مخاطر التبني',
                    'content' => 'برنامج إعداد شامل مع دروس فيديو والدعم المباشر. ميزات بناء المجتمع تمكن تأثيرات الشبكة. التكامل مع الأدوات الشهيرة (Slack و Google Meet) يقلل الاحتكاك. يسمح نموذج Freemium بمحاولة خالية من المخاطر لمنظمي الفعاليات.'
                ]
            ]
        ]
    ],
    'usp_unique_selling_points' => [
        'en' => [
            'title' => 'Unique Selling Points',
            'sections' => [
                [
                    'heading' => 'All-in-One Platform',
                    'content' => 'Unlike fragmented tools, Hackify provides event management, team formation, idea submission, judging, and analytics in one unified platform. Eliminates vendor lock-in and reduces integration complexity for organizers.'
                ],
                [
                    'heading' => 'AI-Powered Insights',
                    'content' => 'Machine learning algorithms analyze ideas in real-time, providing sentiment analysis and predictive scoring. Judges receive AI-augmented recommendations without algorithmic bias, improving decision quality by 40%.'
                ],
                [
                    'heading' => 'White-Label Solution',
                    'content' => 'Fully customizable branding, workflows, and judging criteria. Organizations can maintain their own identity while leveraging our platform. Perfect for universities, enterprises, and innovation centers seeking a competitive edge.'
                ],
                [
                    'heading' => 'Global Ready',
                    'content' => 'Multi-language support (35+ languages), multi-timezone scheduling, and local payment gateways. Designed for organizations running hackathons across continents. Real-time collaboration tools bridge geographical gaps.'
                ],
                [
                    'heading' => 'Superior User Experience',
                    'content' => 'Mobile-first design ensures seamless participation from smartphones. Gamification elements (badges, leaderboards, achievements) drive engagement 3x higher than traditional tools. Intuitive interfaces require minimal training.'
                ]
            ]
        ],
        'ar' => [
            'title' => 'نقاط البيع الفريدة',
            'sections' => [
                [
                    'heading' => 'منصة شاملة الخدمات',
                    'content' => 'بخلاف الأدوات المجزأة، توفر Hackify إدارة الفعاليات وتشكيل الفريق وتقديم الأفكار والحكم والتحليلات في منصة موحدة. يلغي قفل البائع ويقلل من تعقيد التكامل لمنظمي الفعاليات.'
                ],
                [
                    'heading' => 'رؤى مدعومة بالذكاء الاصطناعي',
                    'content' => 'تحلل خوارزميات التعلم الآلي الأفكار في الوقت الفعلي، وتوفير تحليل المشاعر والتنقيط التنبؤي. يتلقى الحكام توصيات معززة بالذكاء الاصطناعي بدون انحياز الخوارزمية، مما يحسن جودة القرار بنسبة 40٪.'
                ],
                [
                    'heading' => 'حل ذو العلامة البيضاء',
                    'content' => 'العلامات التجارية والسير والمعايير القابلة للتخصيص بالكامل. يمكن للمنظمات الحفاظ على هويتهم الخاصة مع الاستفادة من منصتنا. مثالي للجامعات والمؤسسات ومراكز الابتكار التي تسعى إلى ميزة تنافسية.'
                ],
                [
                    'heading' => 'جاهزة عالميًا',
                    'content' => 'دعم متعدد اللغات (35+ لغة)، جدولة متعددة المناطق الزمنية، وبوابات دفع محلية. مصممة للمنظمات التي تشغل الأكاديميات عبر القارات. أدوات التعاون الفورية تسد الفجوات الجغرافية.'
                ],
                [
                    'heading' => 'تجربة المستخدم الفائقة',
                    'content' => 'يضمن التصميم الذي يركز على الهاتف المحمول مشاركة سلسة من الهواتف الذكية. تعمل عناصر اللعب (الشارات ولوحات الترتيب والإنجازات) على زيادة الانخراط 3 مرات أكثر من الأدوات التقليدية. الواجهات البديهية تتطلب حد أدنى من التدريب.'
                ]
            ]
        ]
    ],
    'usp_competitive_advantage' => [
        'en' => [
            'headers' => ['Feature', 'Hackify', 'Competitor A (DevPost)', 'Competitor B (HackerRank)'],
            'rows' => [
                ['All-in-One Platform', '✓', '✗', '✗'],
                ['White-Label Option', '✓', '✗', '✗'],
                ['AI-Powered Insights', '✓', '✗', '✗'],
                ['Real-Time Leaderboards', '✓', '✓', '✓'],
                ['Team Formation Tools', '✓', '✗', '✓'],
                ['Mobile-First Design', '✓', '✗', '✓'],
                ['Multi-Language Support (35+)', '✓', '✗', '✗'],
                ['Advanced Judging Workflows', '✓', '✗', '✗'],
                ['Integrated Analytics Dashboard', '✓', '✗', '✓'],
                ['Custom Event Templates', '✓', '✓', '✗'],
                ['Gamification Engine', '✓', '✗', '✓'],
                ['API for Integration', '✓', '✓', '✓']
            ]
        ],
        'ar' => [
            'headers' => ['الميزة', 'Hackify', 'المنافس أ (DevPost)', 'المنافس ب (HackerRank)'],
            'rows' => [
                ['منصة شاملة الخدمات', '✓', '✗', '✗'],
                ['خيار ذو العلامة البيضاء', '✓', '✗', '✗'],
                ['رؤى مدعومة بالذكاء الاصطناعي', '✓', '✗', '✗'],
                ['لوحات الترتيب الفورية', '✓', '✓', '✓'],
                ['أدوات تشكيل الفريق', '✓', '✗', '✓'],
                ['تصميم يركز على الهاتف المحمول', '✓', '✗', '✓'],
                ['دعم اللغة المتعددة (35+)', '✓', '✗', '✗'],
                ['سير عمل الحكام المتقدم', '✓', '✗', '✗'],
                ['لوحة معلومات التحليلات المدمجة', '✓', '✗', '✓'],
                ['قوالب الحدث المخصصة', '✓', '✓', '✗'],
                ['محرك اللعب', '✓', '✗', '✓'],
                ['واجهة برمجية للتكامل', '✓', '✓', '✓']
            ]
        ]
    ],
    'usp_differentiation_strategy' => [
        'en' => [
            'title' => 'Differentiation Strategy',
            'sections' => [
                [
                    'heading' => 'Market Positioning',
                    'content' => 'Position Hackify as the "Netflix of Innovation Management" - a comprehensive, white-labeled platform that serves the entire hackathon ecosystem. Target enterprises and universities that desire control over branding and workflows.'
                ],
                [
                    'heading' => 'Product Differentiation',
                    'content' => 'Lead with AI-powered insights and advanced judging workflows. Emphasize white-label customization, multi-language support, and the seamless mobile experience. Partner with educational institutions to embed Hackify as a native tool.'
                ],
                [
                    'heading' => 'Go-To-Market Strategy',
                    'content' => 'Begin with direct sales to top 20 universities and enterprise innovation labs. Offer co-marketing opportunities with early adopters. Use case studies demonstrating 40% increase in idea quality and 3x engagement improve conversion rates.'
                ],
                [
                    'heading' => 'Customer Retention',
                    'content' => 'Build strong partnerships through dedicated success managers for enterprise customers. Offer tiered support with premium tiers receiving custom feature development. Create community events and knowledge-sharing forums to deepen engagement.'
                ]
            ]
        ],
        'ar' => [
            'title' => 'استراتيجية التمايز',
            'sections' => [
                [
                    'heading' => 'موضع السوق',
                    'content' => 'ضع Hackify كـ "Netflix لإدارة الابتكار" - منصة شاملة وذات علامة بيضاء تخدم النظام البيئي الأكاديمي بالكامل. استهدف المؤسسات والجامعات التي تسعى للسيطرة على العلامات التجارية وسير العمل.'
                ],
                [
                    'heading' => 'تمايز المنتج',
                    'content' => 'قيادة برؤى مدعومة بالذكاء الاصطناعي وسير عمل الحكام المتقدم. أكد على تخصيص العلامة البيضاء، ودعم اللغات المتعددة، والتجربة السلسة عبر الهاتف المحمول. شراكة مع المؤسسات التعليمية لدمج Hackify كأداة أصلية.'
                ],
                [
                    'heading' => 'استراتيجية الانتقال إلى السوق',
                    'content' => 'ابدأ بالمبيعات المباشرة إلى أفضل 20 جامعة ومختبرات الابتكار بالمؤسسات. قدم فرصًا للتسويق المشترك مع المتبنين الأوائل. استخدم دراسات الحالة التي توضح زيادة بنسبة 40٪ في جودة الأفكار و3 مرات الانخراط لتحسين معدلات التحويل.'
                ],
                [
                    'heading' => 'الاحتفاظ بالعملاء',
                    'content' => 'بناء شراكات قوية من خلال مديري النجاح المخصصين لعملاء المؤسسات. تقديم دعم متعدد المستويات مع تلقي المستويات الممتازة تطوير ميزات مخصصة. إنشاء فعاليات المجتمع ومنتديات تبادل المعرفة لتعميق الانخراط.'
                ]
            ]
        ]
    ],
    'usp_value_chain' => [
        'en' => [
            'items' => [
                ['key' => 'Event Organizers', 'value' => 'Submit event details, set judging criteria, manage logistics'],
                ['key' => 'Participants', 'value' => 'Register teams, submit ideas, collaborate in real-time'],
                ['key' => 'Judges & Mentors', 'value' => 'Review submissions, provide feedback, access AI insights'],
                ['key' => 'Platform Infrastructure', 'value' => 'Secure hosting, real-time processing, data analytics'],
                ['key' => 'Partners & Sponsors', 'value' => 'Gain exposure, access to talent, branded opportunities'],
                ['key' => 'Hackify Support Team', 'value' => 'Onboarding, technical support, customer success']
            ]
        ],
        'ar' => [
            'items' => [
                ['key' => 'منظمو الفعاليات', 'value' => 'تقديم تفاصيل الفعالية، وضع معايير الحكم، وإدارة الخدمات اللوجستية'],
                ['key' => 'المشاركون', 'value' => 'تسجيل الفرق، تقديم الأفكار، التعاون في الوقت الفعلي'],
                ['key' => 'الحكام والمرشدون', 'value' => 'مراجعة الملخصات، تقديم التعليقات، الوصول إلى رؤى الذكاء الاصطناعي'],
                ['key' => 'بنية المنصة الأساسية', 'value' => 'استضافة آمنة، معالجة فورية، تحليل البيانات'],
                ['key' => 'الشركاء والرعاة', 'value' => 'اكتساب التعريض، الوصول إلى المواهب، فرص مميزة'],
                ['key' => 'فريق دعم Hackify', 'value' => 'الإعداد والدعم التقني ونجاح العملاء']
            ]
        ]
    ],
    'cp_primary_persona' => [
        'en' => [
            'name' => 'Dr. Sarah Al-Rashid',
            'role' => 'Innovation Director at Saudi University',
            'age' => 42,
            'location' => 'Riyadh, Saudi Arabia',
            'quote' => 'We need a platform that makes it easy to run hackathons without the headache of juggling multiple tools.',
            'demographics' => [
                'Education' => 'PhD in Computer Science',
                'Experience' => '15+ years in education',
                'Tech Savviness' => 'High',
                'Team Size' => '5-8 people',
                'Budget Authority' => 'Full'
            ],
            'pain_points' => [
                'Managing multiple tools for different hackathon phases',
                'Limited visibility into idea quality before judging',
                'Difficulty measuring event impact and ROI',
                'Time-consuming manual judging process',
                'Inability to white-label platform with university branding'
            ],
            'goals' => [
                'Run 3 major hackathons per year with minimal administrative burden',
                'Increase student participation by 50% through better engagement',
                'Demonstrate clear innovation metrics to university leadership',
                'Build partnerships with corporate sponsors',
                'Create a replicable template for other universities'
            ],
            'motivations' => [
                'Advance student innovation and entrepreneurship',
                'Secure university funding for innovation programs',
                'Build prestige and reputation',
                'Foster industry partnerships',
                'Support economic development initiatives'
            ]
        ],
        'ar' => [
            'name' => 'د. سارة الراشد',
            'role' => 'مديرة الابتكار بجامعة سعودية',
            'age' => 42,
            'location' => 'الرياض، المملكة العربية السعودية',
            'quote' => 'نحتاج إلى منصة تسهل تشغيل الأكاديميات دون معاناة من محاولة التعامل مع أدوات متعددة.',
            'demographics' => [
                'التعليم' => 'دكتوراه في علوم الحاسوب',
                'الخبرة' => '15+ سنة في التعليم',
                'الثقافة التقنية' => 'عالية',
                'حجم الفريق' => '5-8 أشخاص',
                'سلطة الميزانية' => 'كاملة'
            ],
            'pain_points' => [
                'إدارة أدوات متعددة لمراحل الأكاديمية المختلفة',
                'رؤية محدودة في جودة الفكرة قبل الحكم',
                'صعوبة قياس تأثير الفعالية والعائد على الاستثمار',
                'عملية حكم يدوية تستغرق وقتًا طويلاً',
                'عدم القدرة على وضع العلامة البيضاء على المنصة بعلامة جامعة تجارية'
            ],
            'goals' => [
                'تشغيل 3 أكاديميات رئيسية سنويًا بأقل عبء إداري',
                'زيادة مشاركة الطلاب بنسبة 50٪ من خلال انخراط أفضل',
                'توضيح مقاييس الابتكار الواضحة لقيادة الجامعة',
                'بناء شراكات مع الرعاة من الشركات',
                'إنشاء قالب قابل للتكرار للجامعات الأخرى'
            ],
            'motivations' => [
                'تعزيز الابتكار الطلابي وريادة الأعمال',
                'تأمين تمويل جامعي لبرامج الابتكار',
                'بناء الهيبة والسمعة',
                'تعزيز شراكات الصناعة',
                'دعم مبادرات التنمية الاقتصادية'
            ]
        ]
    ],
    'cp_secondary_persona' => [
        'en' => [
            'name' => 'Ahmed Hassan',
            'role' => 'Senior Tech Talent Lead at Fortune 500 Enterprise',
            'age' => 38,
            'location' => 'Dubai, UAE',
            'quote' => 'We want to identify emerging tech talent and innovative ideas from our internal innovation programs.',
            'demographics' => [
                'Education' => 'MBA from INSEAD',
                'Experience' => '10+ years in talent acquisition',
                'Tech Savviness' => 'Medium-High',
                'Team Size' => '3-4 people',
                'Budget Authority' => 'Approval needed'
            ],
            'pain_points' => [
                'Manual tracking of internal innovation competitions',
                'Lack of structured evaluation criteria',
                'Poor integration with existing HR systems',
                'Limited post-event analytics',
                'Difficulty identifying high-potential innovators'
            ],
            'goals' => [
                'Run quarterly innovation challenges with clear metrics',
                'Identify and nurture internal talent',
                'Reduce time spent on manual evaluation',
                'Build innovation culture within the organization',
                'Report metrics to C-suite executives'
            ],
            'motivations' => [
                'Build internal innovation culture',
                'Attract and retain top tech talent',
                'Generate competitive advantage through ideas',
                'Demonstrate ROI on HR investments',
                'Position company as innovation leader'
            ]
        ],
        'ar' => [
            'name' => 'أحمد حسن',
            'role' => 'مسؤول المواهب التقنية الأول بمؤسسة Fortune 500',
            'age' => 38,
            'location' => 'دبي، الإمارات العربية المتحدة',
            'quote' => 'نريد تحديد المواهب التقنية الناشئة والأفكار المبتكرة من برامج الابتكار الداخلية لدينا.',
            'demographics' => [
                'التعليم' => 'MBA من INSEAD',
                'الخبرة' => '10+ سنة في اكتساب المواهب',
                'الثقافة التقنية' => 'متوسطة إلى عالية',
                'حجم الفريق' => '3-4 أشخاص',
                'سلطة الميزانية' => 'يتطلب الموافقة'
            ],
            'pain_points' => [
                'التتبع اليدوي لمسابقات الابتكار الداخلية',
                'عدم وجود معايير تقييم منظمة',
                'تكامل ضعيف مع أنظمة الموارد البشرية الموجودة',
                'تحليلات محدودة بعد الفعالية',
                'صعوبة تحديد المبتكرين الناشئين ذوي الإمكانيات العالية'
            ],
            'goals' => [
                'تشغيل تحديات الابتكار الفصلية بمقاييس واضحة',
                'تحديد ورعاية المواهب الداخلية',
                'تقليل الوقت المستغرق في التقييم اليدوي',
                'بناء ثقافة ابتكار داخل المنظمة',
                'الإبلاغ عن مقاييس للمديرين التنفيذيين'
            ],
            'motivations' => [
                'بناء ثقافة الابتكار الداخلية',
                'جذب الاحتفاظ بأفضل المواهب التقنية',
                'توليد ميزة تنافسية من خلال الأفكار',
                'توضيح العائد على الاستثمار في استثمارات الموارد البشرية',
                'وضع المؤسسة كقائدة ابتكار'
            ]
        ]
    ],
    'cp_buyer_journey' => [
        'en' => [
            'stages' => [
                [
                    'title' => 'Stage 1: Awareness',
                    'description' => 'Event organizer or talent lead discovers hackathon management pain points',
                    'touchpoints' => ['LinkedIn articles', 'Industry conferences', 'Peer recommendations', 'Google search'],
                    'actions' => ['Read case studies', 'Watch demo videos', 'Join webinars', 'Follow on social media'],
                    'duration' => '2-4 weeks'
                ],
                [
                    'title' => 'Stage 2: Consideration',
                    'description' => 'Prospect evaluates Hackify against competitors and internal requirements',
                    'touchpoints' => ['Product demo', 'Pricing page', 'Customer reviews', 'Sales consultation'],
                    'actions' => ['Request live demo', 'Compare features', 'Evaluate pricing', 'Talk to references'],
                    'duration' => '2-6 weeks'
                ],
                [
                    'title' => 'Stage 3: Decision',
                    'description' => 'Buyer secures internal approval and negotiates contract terms',
                    'touchpoints' => ['Sales negotiation', 'Legal review', 'Budget approval', 'Pilot agreement'],
                    'actions' => ['Conduct POC', 'Finalize pricing', 'Sign contract', 'Plan implementation'],
                    'duration' => '2-8 weeks'
                ],
                [
                    'title' => 'Stage 4: Implementation',
                    'description' => 'Customer launches first hackathon on Hackify platform',
                    'touchpoints' => ['Onboarding sessions', 'Configuration calls', 'Training workshops', 'Support tickets'],
                    'actions' => ['Set up workspace', 'Configure workflows', 'Import participant data', 'Create event'],
                    'duration' => '4-8 weeks'
                ],
                [
                    'title' => 'Stage 5: Retention & Growth',
                    'description' => 'Customer becomes repeat user and expands within organization',
                    'touchpoints' => ['Success reviews', 'Feature requests', 'Quarterly business reviews', 'Community events'],
                    'actions' => ['Plan next event', 'Upgrade tier', 'Add team members', 'Provide feedback'],
                    'duration' => 'Ongoing'
                ]
            ]
        ],
        'ar' => [
            'stages' => [
                [
                    'title' => 'المرحلة 1: الوعي',
                    'description' => 'يكتشف منظم الفعالية أو قائد المواهب نقاط ألم إدارة الأكاديمية',
                    'touchpoints' => ['مقالات LinkedIn', 'المؤتمرات الصناعية', 'توصيات الأقران', 'بحث Google'],
                    'actions' => ['قراءة دراسات الحالة', 'مشاهدة مقاطع فيديو العرض التوضيحي', 'الانضمام إلى الندوات عبر الإنترنت', 'المتابعة على وسائل التواصل الاجتماعي'],
                    'duration' => 'أسبوعان إلى 4 أسابيع'
                ],
                [
                    'title' => 'المرحلة 2: الاعتبار',
                    'description' => 'يقيم المشروع المحتمل Hackify مقابل المنافسين والمتطلبات الداخلية',
                    'touchpoints' => ['عرض المنتج', 'صفحة التسعير', 'تقييمات العملاء', 'التشاور مع المبيعات'],
                    'actions' => ['طلب عرض مباشر', 'مقارنة الميزات', 'تقييم التسعير', 'التحدث مع المراجع'],
                    'duration' => 'أسبوعان إلى 6 أسابيع'
                ],
                [
                    'title' => 'المرحلة 3: القرار',
                    'description' => 'يحصل المشتري على موافقة داخلية وينفذ شروط العقد',
                    'touchpoints' => ['تفاوض المبيعات', 'المراجعة القانونية', 'الموافقة على الميزانية', 'اتفاق الطيار'],
                    'actions' => ['إجراء إثبات المفهوم', 'إنهاء التسعير', 'التوقيع على العقد', 'خطة التنفيذ'],
                    'duration' => 'أسبوعان إلى 8 أسابيع'
                ],
                [
                    'title' => 'المرحلة 4: التنفيذ',
                    'description' => 'يطلق العميل أول أكاديمية على منصة Hackify',
                    'touchpoints' => ['جلسات الإعداد', 'استدعاءات التكوين', 'ورش العمل التدريبية', 'تذاكر الدعم'],
                    'actions' => ['ضبط مساحة العمل', 'تكوين سير العمل', 'استيراد بيانات المشاركين', 'إنشاء الحدث'],
                    'duration' => '4 إلى 8 أسابيع'
                ],
                [
                    'title' => 'المرحلة 5: الاحتفاظ والنمو',
                    'description' => 'يصبح العميل مستخدمًا متكررًا ويتوسع داخل المنظمة',
                    'touchpoints' => ['مراجعات النجاح', 'طلبات الميزات', 'مراجعات الأعمال الفصلية', 'أحداث المجتمع'],
                    'actions' => ['خطة الفعالية التالية', 'ترقية المستوى', 'إضافة أعضاء الفريق', 'تقديم ردود الفعل'],
                    'duration' => 'مستمر'
                ]
            ]
        ]
    ],
    'cp_pain_points_analysis' => [
        'en' => [
            'title' => 'Customer Pain Points Analysis',
            'sections' => [
                [
                    'heading' => 'Operational Challenges',
                    'content' => 'Event organizers struggle with fragmented tools across registration, idea submission, judging, and analytics. Managing multiple vendor relationships creates significant overhead. Spreadsheet-based evaluation processes are error-prone and lack transparency. Integration between systems is manual and time-consuming, requiring dedicated technical resources.'
                ],
                [
                    'heading' => 'Decision-Making Difficulty',
                    'content' => 'Judges lack structured frameworks for comparing ideas objectively. Without data-driven insights, evaluation decisions remain subjective and inconsistent. Limited pre-judging analysis means judges enter sessions unprepared. No algorithmic support leads to potential bias and lower-quality final selections.'
                ],
                [
                    'heading' => 'Visibility & Measurement',
                    'content' => 'Organizers cannot easily measure hackathon ROI or impact on innovation culture. Post-event analytics are limited and fragmented across platforms. Difficulty demonstrating value to executives and stakeholders hampers future funding. No clear metrics on participant engagement, idea quality, or long-term outcomes.'
                ],
                [
                    'heading' => 'Scalability & Customization',
                    'content' => 'Existing platforms force users into rigid workflows and cannot accommodate unique requirements. White-label capabilities are unavailable or require expensive custom development. Language barriers limit global scalability for international organizations. Inability to customize judging criteria, workflows, and branding limits platform adoption.'
                ],
                [
                    'heading' => 'User Experience',
                    'content' => 'Desktop-only platforms create friction for mobile participants during live events. Complexity of navigation requires extensive training for end users. Poor integration between tools creates fragmented user experiences. Lack of gamification reduces participant engagement and motivation compared to modern consumer apps.'
                ]
            ]
        ],
        'ar' => [
            'title' => 'تحليل نقاط ألم العملاء',
            'sections' => [
                [
                    'heading' => 'التحديات التشغيلية',
                    'content' => 'يجاهد منظمو الفعاليات مع الأدوات المجزأة عبر التسجيل وتقديم الأفكار والحكم والتحليلات. إدارة علاقات البائعين المتعددة تخلق فوضى كبيرة. عمليات التقييم المستندة إلى جداول البيانات عرضة للأخطاء وتفتقر إلى الشفافية. التكامل بين الأنظمة يدوي ويستغرق وقتًا طويلاً، مما يتطلب موارد تقنية مخصصة.'
                ],
                [
                    'heading' => 'صعوبة صنع القرار',
                    'content' => 'يفتقر الحكام إلى أطر منظمة لمقارنة الأفكار بموضوعية. بدون رؤى مدفوعة بالبيانات، تبقى قرارات التقييم ذاتية وغير متسقة. يعني التحليل المسبق المحدود أن الحكام يدخلون الجلسات غير مستعدين. لا يوجد دعم الخوارزمية يؤدي إلى انحياز محتمل واختيارات نهائية منخفضة الجودة.'
                ],
                [
                    'heading' => 'الرؤية والقياس',
                    'content' => 'لا يستطيع المنظمون بسهولة قياس عائد الاستثمار في الأكاديمية أو تأثيرها على ثقافة الابتكار. تحليلات ما بعد الفعالية محدودة ومجزأة عبر منصات متعددة. من الصعب توضيح القيمة للمديرين التنفيذيين وأصحاب المصلحة يعوق التمويل المستقبلي. لا توجد مقاييس واضحة عن انخراط المشاركين أو جودة الفكرة أو النتائج طويلة الأجل.'
                ],
                [
                    'heading' => 'قابلية التوسع والتخصيص',
                    'content' => 'تفرض المنصات الموجودة سير عمل صارم على المستخدمين ولا يمكنها استيعاب المتطلبات الفريدة. إمكانيات العلامة البيضاء غير متاحة أو تتطلب تطويرًا مخصصًا مكلفًا. تحد اللغة الحواجز الحدود العالمية للمنظمات الدولية. عدم القدرة على تخصيص معايير الحكم وسير العمل والعلامات التجارية يحد من اعتماد المنصة.'
                ],
                [
                    'heading' => 'تجربة المستخدم',
                    'content' => 'تنشئ منصات سطح المكتب فقط احتكاكًا للمشاركين عبر الهاتف المحمول أثناء الأحداث المباشرة. تعقيد الملاحة يتطلب تدريبًا مكثفًا لأصحاب المستخدمين النهائيين. يخلق التكامل الضعيف بين الأدوات تجارب مستخدم مجزأة. يقلل عدم وجود لعب دور من انخراط وتحفيز المشاركين مقارنة بتطبيقات المستهلك الحديثة.'
                ]
            ]
        ]
    ],
    'fin_revenue_model' => [
        'en' => [
            'tiers' => [
                [
                    'name' => 'Starter',
                    'price' => '$500/mo',
                    'features' => [
                        'Up to 500 participants',
                        'Basic event management',
                        'Single hackathon event',
                        'Email support',
                        'Standard leaderboard',
                        'Basic analytics'
                    ],
                    'highlighted' => false,
                    'cta' => 'Start Free Trial'
                ],
                [
                    'name' => 'Professional',
                    'price' => '$1,500/mo',
                    'features' => [
                        'Up to 2,000 participants',
                        'Advanced workflows',
                        'Multiple concurrent events',
                        'Priority email & chat support',
                        'Custom leaderboards',
                        'Advanced analytics & reporting',
                        'Team formation tools',
                        'Mobile app access'
                    ],
                    'highlighted' => true,
                    'cta' => 'Get Started'
                ],
                [
                    'name' => 'Enterprise',
                    'price' => 'Custom',
                    'features' => [
                        'Unlimited participants',
                        'White-label solution',
                        'Custom integrations',
                        'Dedicated account manager',
                        'SLA 99.9% uptime',
                        'Advanced AI insights',
                        'API access',
                        'Custom feature development',
                        'Multi-language support (35+)',
                        'On-premise deployment option'
                    ],
                    'highlighted' => false,
                    'cta' => 'Contact Sales'
                ]
            ]
        ],
        'ar' => [
            'tiers' => [
                [
                    'name' => 'البداية',
                    'price' => '$500/شهر',
                    'features' => [
                        'يصل إلى 500 مشارك',
                        'إدارة الفعالية الأساسية',
                        'حدث أكاديمي واحد',
                        'دعم البريد الإلكتروني',
                        'لوحة الترتيب المعيارية',
                        'تحليلات أساسية'
                    ],
                    'highlighted' => false,
                    'cta' => 'ابدأ الاختبار المجاني'
                ],
                [
                    'name' => 'الاحترافي',
                    'price' => '$1,500/شهر',
                    'features' => [
                        'يصل إلى 2000 مشارك',
                        'سير عمل متقدم',
                        'أحداث متزامنة متعددة',
                        'دعم البريد الإلكتروني والدردشة الأولويات',
                        'لوحات ترتيب مخصصة',
                        'تحليلات وتقارير متقدمة',
                        'أدوات تشكيل الفريق',
                        'الوصول إلى تطبيق الجوال'
                    ],
                    'highlighted' => true,
                    'cta' => 'ابدأ'
                ],
                [
                    'name' => 'المؤسسة',
                    'price' => 'مخصص',
                    'features' => [
                        'مشاركون غير محدودين',
                        'حل ذو علامة بيضاء',
                        'التكاملات المخصصة',
                        'مدير حساب مخصص',
                        'توفر اتفاقية مستويات الخدمة 99.9٪',
                        'رؤى ذكاء اصطناعي متقدمة',
                        'وصول واجهة برمجية',
                        'تطوير ميزة مخصصة',
                        'دعم اللغات المتعددة (35+)',
                        'خيار النشر المحلي'
                    ],
                    'highlighted' => false,
                    'cta' => 'اتصل بفريق المبيعات'
                ]
            ]
        ]
    ],
    'fin_cost_structure' => [
        'en' => [
            'items' => [
                ['label' => 'Infrastructure & Hosting', 'value' => 35, 'suffix' => '%'],
                ['label' => 'Personnel Costs', 'value' => 35, 'suffix' => '%'],
                ['label' => 'Research & Development', 'value' => 15, 'suffix' => '%'],
                ['label' => 'Sales & Marketing', 'value' => 10, 'suffix' => '%'],
                ['label' => 'Customer Support', 'value' => 5, 'suffix' => '%']
            ]
        ],
        'ar' => [
            'items' => [
                ['label' => 'البنية الأساسية والاستضافة', 'value' => 35, 'suffix' => '%'],
                ['label' => 'تكاليف الموظفين', 'value' => 35, 'suffix' => '%'],
                ['label' => 'البحث والتطوير', 'value' => 15, 'suffix' => '%'],
                ['label' => 'المبيعات والتسويق', 'value' => 10, 'suffix' => '%'],
                ['label' => 'دعم العملاء', 'value' => 5, 'suffix' => '%']
            ]
        ]
    ],
    'fin_financial_projections' => [
        'en' => [
            'metrics' => [
                ['label' => 'Year 1 Revenue (Projected)', 'value' => '$2.8M', 'description' => 'From 40-50 enterprise contracts + SMB subscriptions'],
                ['label' => 'Year 2 Revenue (Projected)', 'value' => '$7.2M', 'description' => 'With 120+ enterprise customers and 300+ SMB'],
                ['label' => 'Year 3 Revenue (Projected)', 'value' => '$16.5M', 'description' => 'Scaling to 250+ enterprise and 800+ SMB customers'],
                ['label' => 'Gross Margin (Current)', 'value' => '72%', 'description' => 'High-margin SaaS model with automation'],
                ['label' => 'CAC Payback Period', 'value' => '14 months', 'description' => 'Strong unit economics with viral growth potential'],
                ['label' => 'Break-Even Timeline', 'value' => 'Month 18', 'description' => 'Achievable with disciplined expense management']
            ]
        ],
        'ar' => [
            'metrics' => [
                ['label' => 'إيرادات السنة الأولى (متوقعة)', 'value' => '$2.8M', 'description' => 'من عقود المؤسسات 40-50 + اشتراكات المؤسسات الصغيرة والمتوسطة'],
                ['label' => 'إيرادات السنة الثانية (متوقعة)', 'value' => '$7.2M', 'description' => 'مع 120+ عملاء مؤسسات و300+ المؤسسات الصغيرة والمتوسطة'],
                ['label' => 'إيرادات السنة الثالثة (متوقعة)', 'value' => '$16.5M', 'description' => 'التوسع إلى 250+ مؤسسات و800+ عملاء المؤسسات الصغيرة والمتوسطة'],
                ['label' => 'الهامش الإجمالي (الحالي)', 'value' => '72%', 'description' => 'نموذج SaaS عالي الهامش مع الأتمتة'],
                ['label' => 'فترة سداد CAC', 'value' => '14 شهر', 'description' => 'اقتصاديات الوحدة القوية مع إمكانية النمو الفيروسي'],
                ['label' => 'الخط الثابت', 'value' => 'الشهر 18', 'description' => 'قابل للتحقيق مع إدارة النفقات الانضباطية']
            ]
        ]
    ],
    'fin_funding_requirements' => [
        'en' => [
            'title' => 'Funding Requirements & Use of Proceeds',
            'sections' => [
                [
                    'heading' => 'Seed Round Target: $1.5M',
                    'content' => 'Funding will be allocated to accelerate MVP development, build go-to-market capabilities, and secure initial enterprise customers. Breakdown: Product development ($600K - 40%), Sales & Marketing ($550K - 37%), Operations ($250K - 17%), Contingency ($100K - 6%).'
                ],
                [
                    'heading' => 'Series A Target: $5M (Year 2)',
                    'content' => 'Growth funding to scale sales team, expand product capabilities, and enter adjacent markets. Allocation: Sales & Marketing ($2.5M - 50%), Product & Engineering ($1.5M - 30%), Operations & Infrastructure ($1M - 20%).'
                ],
                [
                    'heading' => 'Use of Capital Strategy',
                    'content' => 'Prioritize customer acquisition in high-value segments (universities, Fortune 500 companies). Build enterprise-grade features and integrations. Establish regional sales offices in key markets (Saudi Arabia, UAE, India). Invest in brand awareness and thought leadership through events and partnerships.'
                ],
                [
                    'heading' => 'Path to Profitability',
                    'content' => 'Unit economics support profitability by Year 2. With gross margins above 70% and CAC payback in 14 months, Hackify reaches cash-flow positive before Series B. Operating leverage improves as platform scale increases. Target: $2M+ in annual profit by Year 3.'
                ]
            ]
        ],
        'ar' => [
            'title' => 'متطلبات التمويل واستخدام العائدات',
            'sections' => [
                [
                    'heading' => 'هدف جولة البذور: 1.5 مليون دولار',
                    'content' => 'سيتم تخصيص التمويل لتسريع تطوير MVP وبناء قدرات go-to-market وتأمين عملاء المؤسسات الأوليين. الانهيار: تطوير المنتج (600 ألف دولار - 40٪)، المبيعات والتسويق (550 ألف دولار - 37٪)، العمليات (250 ألف دولار - 17٪)، الطوارئ (100 ألف دولار - 6٪).'
                ],
                [
                    'heading' => 'هدف Series A: 5 ملايين دولار (السنة الثانية)',
                    'content' => 'تمويل النمو لتوسيع فريق المبيعات، وتوسيع قدرات المنتج، والدخول إلى أسواق مجاورة. التخصيص: المبيعات والتسويق (2.5 مليون دولار - 50٪)، المنتج والهندسة (1.5 مليون دولار - 30٪)، العمليات والبنية الأساسية (1 مليون دولار - 20٪).'
                ],
                [
                    'heading' => 'استراتيجية استخدام رأس المال',
                    'content' => 'أولويات اكتساب العملاء في الأجزاء ذات القيمة العالية (الجامعات وشركات Fortune 500). بناء ميزات والتكاملات على مستوى المؤسسات. إنشاء مكاتب مبيعات إقليمية في الأسواق الرئيسية (المملكة العربية السعودية والإمارات والهند). الاستثمار في الوعي بالعلامة التجارية والقيادة الفكرية من خلال الأحداث والشراكات.'
                ],
                [
                    'heading' => 'الطريق إلى الربحية',
                    'content' => 'اقتصاديات الوحدة تدعم الربحية بحلول السنة الثانية. مع هوامش إجمالية أعلى من 70٪ وسداد CAC في 14 شهرًا، تصل Hackify إلى تدفق نقدي إيجابي قبل Series B. يحسن الرافعة التشغيلية مع زيادة مقياس المنصة. الهدف: 2 مليون دولار + في الربح السنوي بحلول السنة الثالثة.'
                ]
            ]
        ]
    ],
    'fin_unit_economics' => [
        'en' => [
            'items' => [
                ['key' => 'Average Revenue Per Account (ARPA)', 'value' => '$24,000/year (Enterprise)'],
                ['key' => 'Customer Acquisition Cost (CAC)', 'value' => '$28,000'],
                ['key' => 'CAC Payback Period', 'value' => '14 months'],
                ['key' => 'Gross Margin', 'value' => '72%'],
                ['key' => 'Net Retention Rate (NRR)', 'value' => '135%'],
                ['key' => 'Churn Rate', 'value' => '<5% annually'],
                ['key' => 'Lifetime Value (LTV)', 'value' => '$240,000'],
                ['key' => 'LTV:CAC Ratio', 'value' => '8.6:1 (Healthy)']
            ]
        ],
        'ar' => [
            'items' => [
                ['key' => 'متوسط الإيرادات لكل حساب (ARPA)', 'value' => '$24,000 سنويًا (المؤسسة)'],
                ['key' => 'تكلفة اكتساب العميل (CAC)', 'value' => '$28,000'],
                ['key' => 'فترة سداد CAC', 'value' => '14 شهر'],
                ['key' => 'الهامش الإجمالي', 'value' => '72%'],
                ['key' => 'معدل الاحتفاظ الصافي (NRR)', 'value' => '135%'],
                ['key' => 'معدل الاستنزاف', 'value' => '<5% سنويًا'],
                ['key' => 'القيمة الحياتية للعميل (LTV)', 'value' => '$240,000'],
                ['key' => 'نسبة LTV:CAC', 'value' => '8.6:1 (صحي)']
            ]
        ]
    ],
    'gtm_launch_strategy' => [
        'en' => [
            'title' => 'Go-To-Market Launch Strategy',
            'sections' => [
                [
                    'heading' => 'Phase 1: Beta Launch (Months 1-3)',
                    'content' => 'Recruit 5 beta universities and 5 corporate innovation labs to pilot Hackify. Provide free platform access in exchange for case studies and referrals. Build reference accounts with strong brand names to validate product-market fit. Conduct weekly feedback sessions and iterate rapidly based on user feedback.'
                ],
                [
                    'heading' => 'Phase 2: Market Entry (Months 4-6)',
                    'content' => 'Launch official product with comprehensive documentation and support. Target top 100 universities globally with direct outreach campaigns. Secure partnerships with innovation accelerators and startup incubators. Sponsor hackathon events and conferences to build brand awareness and generate leads.'
                ],
                [
                    'heading' => 'Phase 3: Scale & Expansion (Months 7-12)',
                    'content' => 'Build enterprise sales team focused on Fortune 500 companies and government organizations. Expand regional presence in MENA, Asia-Pacific, and North America. Launch partner certification program to enable resellers and system integrators. Establish thought leadership through webinars, whitepapers, and industry awards.'
                ],
                [
                    'heading' => 'Phase 4: Market Leadership (Year 2+)',
                    'content' => 'Position as market leader through M&A of complementary solutions (analytics, team management tools). Expand into adjacent markets (virtual events, corporate training, internal competitions). Build ecosystem through integrations with education and business platforms. Target global expansion with localized versions for key markets.'
                ]
            ]
        ],
        'ar' => [
            'title' => 'استراتيجية الانتقال إلى السوق',
            'sections' => [
                [
                    'heading' => 'المرحلة 1: إطلاق بيتا (الأشهر 1-3)',
                    'content' => 'توظيف 5 جامعات بيتا و5 مختبرات ابتكار الشركات لتجربة Hackify. توفير الوصول إلى المنصة مجانًا في مقابل دراسات الحالة والإحالات. بناء حسابات مرجعية مع أسماء العلامات التجارية القوية للتحقق من ملاءمة سوق المنتج. إجراء جلسات تغذية راجعة أسبوعية والتكرار بسرعة بناءً على ردود الفعل.'
                ],
                [
                    'heading' => 'المرحلة 2: دخول السوق (الأشهر 4-6)',
                    'content' => 'إطلاق المنتج الرسمي مع توثيق شامل والدعم. استهدف أفضل 100 جامعة عالميًا مع حملات الوصول المباشر. تأمين الشراكات مع معجلات الابتكار وحاضنات بدء التشغيل. رعاية أحداث الأكاديمية والمؤتمرات لبناء الوعي بالعلامة التجارية والحصول على العملاء المحتملين.'
                ],
                [
                    'heading' => 'المرحلة 3: الحجم والتوسع (الأشهر 7-12)',
                    'content' => 'بناء فريق مبيعات المؤسسات التركيز على شركات Fortune 500 والمنظمات الحكومية. توسيع الحضور الإقليمي في منطقة الشرق الأوسط وشمال أفريقيا وآسيا والمحيط الهادئ وأمريكا الشمالية. إطلاق برنامج شهادة الشركاء لتمكين بيع الوسائط والمدمجين. إنشاء القيادة الفكرية من خلال الندوات عبر الإنترنت والأوراق البيضاء والجوائز الصناعية.'
                ],
                [
                    'heading' => 'المرحلة 4: قيادة السوق (السنة 2+)',
                    'content' => 'الموضع كقائد السوق من خلال الاستحواذ على حلول مكملة (التحليلات وأدوات إدارة الفريق). التوسع في الأسواق المجاورة (الأحداث الافتراضية والتدريب الشركاتي والمسابقات الداخلية). بناء النظام البيئي من خلال التكاملات مع منصات التعليم والأعمال. استهدف التوسع العالمي مع الإصدارات المحلية للأسواق الرئيسية.'
                ]
            ]
        ]
    ],
    'gtm_marketing_channels' => [
        'en' => [
            'metrics' => [
                ['label' => 'Inbound Marketing (Content & SEO)', 'value' => '25%', 'description' => 'High ROI through blog, whitepapers, and organic search'],
                ['label' => 'Paid Digital Advertising', 'value' => '20%', 'description' => 'LinkedIn, Google Ads, and retargeting campaigns'],
                ['label' => 'Strategic Partnerships', 'value' => '20%', 'description' => 'Universities, accelerators, and tech platforms'],
                ['label' => 'Events & Sponsorships', 'value' => '15%', 'description' => 'Hackathon sponsorships and industry conferences'],
                ['label' => 'Direct Sales Outreach', 'value' => '12%', 'description' => 'Enterprise account executives for Fortune 500'],
                ['label' => 'Referral & Community', 'value' => '8%', 'description' => 'Customer referrals and community engagement']
            ]
        ],
        'ar' => [
            'metrics' => [
                ['label' => 'التسويق الواردة (المحتوى وSEO)', 'value' => '25%', 'description' => 'عائد استثمار عالي من خلال المدونة والأوراق البيضاء والبحث العضوي'],
                ['label' => 'الإعلانات الرقمية المدفوعة', 'value' => '20%', 'description' => 'LinkedIn و Google Ads وحملات إعادة الاستهداف'],
                ['label' => 'الشراكات الاستراتيجية', 'value' => '20%', 'description' => 'الجامعات والمعجلات ومنصات التكنولوجيا'],
                ['label' => 'الأحداث والرعايات', 'value' => '15%', 'description' => 'رعايات الأكاديمية والمؤتمرات الصناعية'],
                ['label' => 'توعية المبيعات المباشرة', 'value' => '12%', 'description' => 'المديرين التنفيذيين لحساب المؤسسات لشركات Fortune 500'],
                ['label' => 'الإحالة والمجتمع', 'value' => '8%', 'description' => 'إحالات العملاء والانخراط المجتمعي']
            ]
        ]
    ],
    'gtm_sales_funnel' => [
        'en' => [
            'stages' => [
                [
                    'title' => 'Awareness',
                    'description' => 'Prospects discover Hackify through marketing channels and content',
                    'touchpoints' => ['LinkedIn', 'Google Search', 'Industry blogs', 'Referrals', 'Events'],
                    'actions' => ['View website', 'Read case studies', 'Watch demo video', 'Download whitepaper'],
                    'duration' => '2-4 weeks'
                ],
                [
                    'title' => 'Interest',
                    'description' => 'Qualified leads engage with sales team and request product information',
                    'touchpoints' => ['Sales email', 'Product demo', 'Pricing call', 'Customer calls'],
                    'actions' => ['Schedule meeting', 'Watch full demo', 'Compare pricing', 'Talk to customers'],
                    'duration' => '2-6 weeks'
                ],
                [
                    'title' => 'Consideration',
                    'description' => 'Leads evaluate Hackify against competitors and internal requirements',
                    'touchpoints' => ['Live demo', 'Trial access', 'RFP response', 'Reference calls'],
                    'actions' => ['Request trial', 'Conduct POC', 'Get IT approval', 'Negotiate terms'],
                    'duration' => '4-8 weeks'
                ],
                [
                    'title' => 'Decision',
                    'description' => 'Buyer commits to contract and becomes customer',
                    'touchpoints' => ['Sales contract', 'Legal review', 'Budget approval', 'Signature'],
                    'actions' => ['Sign agreement', 'Setup payment', 'Plan onboarding', 'Start training'],
                    'duration' => '2-4 weeks'
                ],
                [
                    'title' => 'Retention',
                    'description' => 'Customer launches first event and becomes expansion opportunity',
                    'touchpoints' => ['Onboarding', 'Support tickets', 'Success reviews', 'Upsell conversations'],
                    'actions' => ['Run event', 'Provide feedback', 'Refer other customers', 'Upgrade plan'],
                    'duration' => 'Ongoing'
                ]
            ]
        ],
        'ar' => [
            'stages' => [
                [
                    'title' => 'الوعي',
                    'description' => 'يكتشف المتوقعون Hackify من خلال قنوات التسويق والمحتوى',
                    'touchpoints' => ['LinkedIn', 'بحث Google', 'مدونات الصناعة', 'الإحالات', 'الأحداث'],
                    'actions' => ['عرض الموقع', 'قراءة دراسات الحالة', 'مشاهدة فيديو العرض التوضيحي', 'تحميل الورقة البيضاء'],
                    'duration' => 'أسبوعان إلى 4 أسابيع'
                ],
                [
                    'title' => 'الاهتمام',
                    'description' => 'العملاء المحتملون المؤهلون يتفاعلون مع فريق المبيعات ويطلبون معلومات المنتج',
                    'touchpoints' => ['بريد المبيعات الإلكتروني', 'عرض توضيحي للمنتج', 'استدعاء التسعير', 'استدعاءات العملاء'],
                    'actions' => ['جدولة اجتماع', 'مشاهدة عرض توضيحي كامل', 'مقارنة التسعير', 'التحدث مع العملاء'],
                    'duration' => 'أسبوعان إلى 6 أسابيع'
                ],
                [
                    'title' => 'الاعتبار',
                    'description' => 'تقيم الرصاصات Hackify مقابل المنافسين والمتطلبات الداخلية',
                    'touchpoints' => ['عرض مباشر', 'وصول التجربة', 'استجابة RFP', 'استدعاءات المرجع'],
                    'actions' => ['طلب تجربة', 'إجراء إثبات المفهوم', 'الحصول على موافقة تكنولوجيا المعلومات', 'شروط التفاوض'],
                    'duration' => '4 إلى 8 أسابيع'
                ],
                [
                    'title' => 'القرار',
                    'description' => 'يلتزم المشتري بالعقد ويصبح عميل',
                    'touchpoints' => ['عقد المبيعات', 'المراجعة القانونية', 'الموافقة على الميزانية', 'التوقيع'],
                    'actions' => ['التوقيع على الاتفاق', 'إعداد الدفع', 'خطة الإعداد', 'بدء التدريب'],
                    'duration' => 'أسبوعان إلى 4 أسابيع'
                ],
                [
                    'title' => 'الاحتفاظ',
                    'description' => 'يطلق العميل حدثه الأول ويصبح فرصة توسع',
                    'touchpoints' => ['الإعداد', 'تذاكر الدعم', 'مراجعات النجاح', 'محادثات البيع الإضافي'],
                    'actions' => ['تشغيل الحدث', 'تقديم ردود الفعل', 'إحالة عملاء آخرين', 'خطة ترقية'],
                    'duration' => 'مستمر'
                ]
            ]
        ]
    ],
    'gtm_partnerships' => [
        'en' => [
            'title' => 'Partnership Strategy',
            'sections' => [
                [
                    'heading' => 'University Partnerships',
                    'content' => 'Partner with top 50 universities globally to embed Hackify as the official hackathon platform. Offer free platform access for student hackathons in exchange for case studies and testimonials. Collaborate on innovation programs and entrepreneurship initiatives. Create university advisory board to guide product development.'
                ],
                [
                    'heading' => 'Corporate & Startup Ecosystem',
                    'content' => 'Partner with accelerators (Y Combinator, Plug and Play) and incubators to integrate Hackify into their innovation pipelines. Provide platform access to portfolio companies and corporate innovation labs. Build co-marketing campaigns around hackathon events. Offer white-label solutions to ecosystem partners.'
                ],
                [
                    'heading' => 'Technology Integrations',
                    'content' => 'Integrate with popular tools: Slack, Microsoft Teams, Google Workspace, Salesforce, and HubSpot. Develop API marketplace for third-party developers. Partner with video conferencing platforms (Zoom, Google Meet) for seamless event support. Build native mobile app integrations with major platforms.'
                ],
                [
                    'heading' => 'Go-To-Market Partners',
                    'content' => 'Build channel partnerships with consulting firms and system integrators (Deloitte, Accenture) to sell white-label solutions. Partner with innovation consultants and management firms. Establish technology sales partnerships with major cloud providers. Create referral partnerships with event management and training platforms.'
                ]
            ]
        ],
        'ar' => [
            'title' => 'استراتيجية الشراكة',
            'sections' => [
                [
                    'heading' => 'شراكات جامعية',
                    'content' => 'شراكة مع أفضل 50 جامعة عالميًا لدمج Hackify كمنصة الأكاديمية الرسمية. توفير الوصول إلى المنصة مجانًا للأكاديميات الطلابية مقابل دراسات الحالة والشهادات. التعاون في برامج الابتكار ومبادرات ريادة الأعمال. إنشاء مجلس استشاري جامعي لتوجيه تطوير المنتج.'
                ],
                [
                    'heading' => 'النظام البيئي للشركات وبدء التشغيل',
                    'content' => 'شراكة مع المعجلات (Y Combinator و Plug and Play) والحاضنات لدمج Hackify في خطوط الابتكار الخاصة بهم. توفير الوصول إلى المنصة لشركات المحفظة ومختبرات الابتكار بالشركات. بناء حملات التسويق المشترك حول أحداث الأكاديمية. توفير حلول ذات علامات بيضاء لشركاء النظام البيئي.'
                ],
                [
                    'heading' => 'تكاملات التكنولوجيا',
                    'content' => 'دمج مع أدوات شهيرة: Slack و Microsoft Teams و Google Workspace و Salesforce و HubSpot. تطوير سوق واجهة برمجية لمطوري الطرف الثالث. شراكة مع منصات مؤتمرات الفيديو (Zoom و Google Meet) للدعم السلس للأحداث. بناء تكاملات تطبيقات الهاتف المحمول الأصلية مع المنصات الرئيسية.'
                ],
                [
                    'heading' => 'شركاء الانتقال إلى السوق',
                    'content' => 'بناء شراكات قناة مع شركات الاستشارات والمدمجين (Deloitte و Accenture) لبيع حلول العلامات البيضاء. شراكة مع الاستشاريين في الابتكار وشركات الإدارة. إنشاء شراكات مبيعات تكنولوجية مع مزودي الخدمات السحابية الرئيسيين. إنشاء شراكات إحالة مع منصات إدارة الأحداث والتدريب.'
                ]
            ]
        ]
    ],
    'gtm_growth_metrics' => [
        'en' => [
            'items' => [
                ['label' => 'Customer Acquisition Rate', 'value' => 85, 'suffix' => '%'],
                ['label' => 'Month-over-Month Growth (Subscriptions)', 'value' => 15, 'suffix' => '%'],
                ['label' => 'Net Revenue Retention', 'value' => 135, 'suffix' => '%'],
                ['label' => 'Customer Satisfaction (NPS)', 'value' => 72, 'suffix' => 'pts'],
                ['label' => 'Market Penetration (Year 2)', 'value' => 8, 'suffix' => '%']
            ]
        ],
        'ar' => [
            'items' => [
                ['label' => 'معدل اكتساب العملاء', 'value' => 85, 'suffix' => '%'],
                ['label' => 'النمو من شهر إلى آخر (الاشتراكات)', 'value' => 15, 'suffix' => '%'],
                ['label' => 'صافي الاحتفاظ بالإيرادات', 'value' => 135, 'suffix' => '%'],
                ['label' => 'رضا العملاء (NPS)', 'value' => 72, 'suffix' => 'نقاط'],
                ['label' => 'اختراق السوق (السنة الثانية)', 'value' => 8, 'suffix' => '%']
            ]
        ]
    ],
    'ca_competitor_overview' => [
        'en' => [
            'title' => 'Competitive Analysis: Key Competitors',
            'sections' => [
                [
                    'heading' => 'DevPost (Launched 2011, 5M+ users)',
                    'content' => 'Market leader in hackathon discovery and idea submission. Strong brand with majority market share. Strengths: large user base, established relationships with enterprises. Weaknesses: platform is dated with poor UX, limited white-label capabilities, no AI features, weak analytics. Not optimized for mobile. High switching costs due to habit and network effects.'
                ],
                [
                    'heading' => 'HackerRank (Acquired by Cisco, 2019)',
                    'content' => 'Focuses on technical skill assessment and recruiting. Used by 1M+ developers. Strengths: strong in coding challenges and leaderboards. Weaknesses: primarily a recruiting tool, not an end-to-end event platform, complex UI, limited judging features, no white-label option. Better positioning for companies than universities.'
                ],
                [
                    'heading' => 'Eventbrite + Custom Integrations',
                    'content' => 'Many organizations build custom solutions using Eventbrite, Typeform, Google Sheets, and manual processes. Strengths: low cost to get started, flexibility. Weaknesses: fragmented experience, no specialized features, high operational overhead, poor analytics. Time-consuming manual work. Most vulnerable segment for disruption.'
                ],
                [
                    'heading' => 'Emerging Competitors',
                    'content' => 'Few direct competitors in white-label hackathon platform space. Some niche tools emerging (Questmates, AngelHack) but with limited feature sets. Threat: large enterprise software vendors (Salesforce, Microsoft) could enter space with integrated solutions. However, lack of innovation focus and hackathon expertise creates opportunity.'
                ]
            ]
        ],
        'ar' => [
            'title' => 'تحليل تنافسي: المنافسون الرئيسيون',
            'sections' => [
                [
                    'heading' => 'DevPost (انطلق في 2011، أكثر من 5 ملايين مستخدم)',
                    'content' => 'قائد السوق في اكتشاف الأكاديمية وتقديم الأفكار. علامة تجارية قوية مع حصة سوق الأغلبية. نقاط القوة: قاعدة مستخدمين كبيرة وعلاقات راسخة مع المؤسسات. نقاط الضعف: المنصة قديمة مع تجربة مستخدم ضعيفة وقدرات بيضاء محدودة وعدم وجود ميزات ذكاء اصطناعي وتحليلات ضعيفة. غير محسن للجوال. تكاليف التبديل العالية بسبب العادة وآثار الشبكة.'
                ],
                [
                    'heading' => 'HackerRank (استحوذت عليه Cisco 2019)',
                    'content' => 'يركز على تقييم المهارات التقنية والتوظيف. يستخدمه أكثر من 1 مليون مطور. نقاط القوة: قوية في تحديات الترميز ولوحات الترتيب. نقاط الضعف: أداة توظيف في المقام الأول وليس منصة حدث شاملة وواجهة مستخدم معقدة وميزات حكم محدودة وعدم وجود خيار بيضاء. وضع أفضل للشركات من الجامعات.'
                ],
                [
                    'heading' => 'Eventbrite + التكاملات المخصصة',
                    'content' => 'تقوم العديد من المنظمات ببناء حلول مخصصة باستخدام Eventbrite و Typeform و Google Sheets والعمليات اليدوية. نقاط القوة: التكلفة المنخفضة للبدء والمرونة. نقاط الضعف: تجربة مجزأة وعدم وجود ميزات متخصصة وارتفاع الحمل التشغيلي والتحليلات الضعيفة. عمل يدوي يستغرق وقتًا طويلاً. القطاع الأكثر عرضة للاضطراب.'
                ],
                [
                    'heading' => 'المنافسون الناشئون',
                    'content' => 'عدد قليل من المنافسين المباشرين في مساحة منصة الأكاديمية ذات العلامات البيضاء. بعض الأدوات المتخصصة الناشئة (Questmates و AngelHack) لكن مع مجموعات ميزات محدودة. التهديد: يمكن لبائعي البرامج الكبيرة للمؤسسات (Salesforce و Microsoft) الدخول في الفضاء مع حلول متكاملة. ومع ذلك، فإن عدم التركيز على الابتكار والخبرة في الأكاديمية يخلق فرصة.'
                ]
            ]
        ]
    ],
    'ca_feature_comparison' => [
        'en' => [
            'headers' => ['Feature', 'Hackify', 'DevPost', 'HackerRank', 'Custom (Eventbrite+)'],
            'rows' => [
                ['End-to-end Platform', '✓', '✗', '✗', '✗'],
                ['White-Label Solution', '✓', '✗', '✗', '✗'],
                ['Mobile-First Design', '✓', '✗', '✓', '✗'],
                ['AI-Powered Insights', '✓', '✗', '✗', '✗'],
                ['Team Formation Tools', '✓', '✗', '✓', '✗'],
                ['Real-Time Leaderboards', '✓', '✓', '✓', '✗'],
                ['Advanced Judging', '✓', '✗', '✗', '✗'],
                ['Integrated Analytics', '✓', '✗', '✓', '✗'],
                ['Multi-Language (35+)', '✓', '✗', '✗', '✗'],
                ['Custom Workflows', '✓', '✗', '✗', '✓'],
                ['API Access', '✓', '✓', '✓', '✗'],
                ['Enterprise Support', '✓', '✗', '✗', '✗']
            ]
        ],
        'ar' => [
            'headers' => ['الميزة', 'Hackify', 'DevPost', 'HackerRank', 'مخصص (Eventbrite+)'],
            'rows' => [
                ['منصة شاملة الخدمات', '✓', '✗', '✗', '✗'],
                ['حل ذو العلامة البيضاء', '✓', '✗', '✗', '✗'],
                ['تصميم يركز على الهاتف المحمول', '✓', '✗', '✓', '✗'],
                ['رؤى مدعومة بالذكاء الاصطناعي', '✓', '✗', '✗', '✗'],
                ['أدوات تشكيل الفريق', '✓', '✗', '✓', '✗'],
                ['لوحات الترتيب الفورية', '✓', '✓', '✓', '✗'],
                ['الحكم المتقدم', '✓', '✗', '✗', '✗'],
                ['التحليلات المدمجة', '✓', '✗', '✓', '✗'],
                ['اللغات المتعددة (35+)', '✓', '✗', '✗', '✗'],
                ['سير عمل مخصص', '✓', '✗', '✗', '✓'],
                ['وصول واجهة برمجية', '✓', '✓', '✓', '✗'],
                ['دعم المؤسسات', '✓', '✗', '✗', '✗']
            ]
        ]
    ],
    'ca_market_positioning' => [
        'en' => [
            'items' => [
                ['key' => 'Target Market Segment', 'value' => 'Universities, Fortune 500, Innovation Labs (Mid-to-High Value)'],
                ['key' => 'Price Positioning', 'value' => 'Premium ($1.5K-Custom) vs DevPost ($400-2K) - Higher value justifies price'],
                ['key' => 'Feature Differentiation', 'value' => 'White-label, AI insights, mobile-first, 35+ languages (DevPost lacks all)'],
                ['key' => 'Brand Positioning', 'value' => '"The Enterprise Platform for Innovation" vs DevPost\'s "Hackathon Social Network"'],
                ['key' => 'GTM Strategy', 'value' => 'Direct sales to enterprises vs DevPost\'s community-driven organic growth'],
                ['key' => 'Competitive Advantage', 'value' => 'Integrated platform + white-label + AI + mobile reduces switching costs vs fragmented alternatives']
            ]
        ],
        'ar' => [
            'items' => [
                ['key' => 'شريحة السوق المستهدفة', 'value' => 'الجامعات وFortune 500 ومختبرات الابتكار (قيمة متوسطة إلى عالية)'],
                ['key' => 'موضع السعر', 'value' => 'قسط ($1.5K مخصص) مقابل DevPost ($400-2K) - القيمة الأعلى تبرر السعر'],
                ['key' => 'تمايز الميزات', 'value' => 'علامة بيضاء ورؤى ذكاء اصطناعي وتركيز على الهاتف المحمول و35+ لغة (DevPost يفتقد الكل)'],
                ['key' => 'موضع العلامة التجارية', 'value' => '"منصة المؤسسات للابتكار" مقابل "شبكة الأكاديمية الاجتماعية" في DevPost'],
                ['key' => 'استراتيجية GTM', 'value' => 'المبيعات المباشرة للمؤسسات مقابل نمو عضوي يحركه المجتمع في DevPost'],
                ['key' => 'الميزة التنافسية', 'value' => 'المنصة المتكاملة + علامة بيضاء + ذكاء اصطناعي + جوال يقلل تكاليف التبديل مقابل البدائل المجزأة']
            ]
        ]
    ],
    'ca_competitive_moat' => [
        'en' => [
            'title' => 'Competitive Moat & Barriers to Entry',
            'sections' => [
                [
                    'heading' => 'Network Effects & Data Network',
                    'content' => 'As more universities and enterprises use Hackify, the platform becomes more valuable through aggregated insights on innovation trends, benchmark data, and talent networks. Cross-organization idea sharing and collaboration opportunities create strong retention. Data advantage: exclusive access to hackathon outcomes and innovation metrics unavailable to competitors.'
                ],
                [
                    'heading' => 'Technology & Product Differentiation',
                    'content' => 'Proprietary AI algorithms for idea evaluation and judging support create 6-12 month development lead. White-label architecture requires significant engineering effort to replicate. Mobile-first design and UX optimization built from ground up, not retrofitted. Multi-language support (35+ languages) represents significant ongoing investment that competitors cannot easily replicate.'
                ],
                [
                    'heading' => 'Customer Lock-in & Switching Costs',
                    'content' => 'Annual contracts with high switching costs due to data migration complexity, workflow customization, and integration work. Customers invest in training staff on Hackify workflows. Recurring event hosting builds dependency and habit. Enterprise customers sign multi-year agreements with volume discounts.'
                ],
                [
                    'heading' => 'Brand & Partnership Ecosystem',
                    'content' => 'Establish Hackify as the standard for hackathon management through university and enterprise partnerships. Create brand association with innovation and entrepreneurship. Build community around the platform with events, forums, and content. Integration ecosystem becomes more valuable as third-party integrations grow.'
                ],
                [
                    'heading' => 'Talent & Execution',
                    'content' => 'Build team with deep hackathon and innovation management expertise. Attract top engineering talent through equity and mission. Maintain product velocity and innovation roadmap that competitors struggle to match. Reputation for customer success and support creates positive word-of-mouth in limited market.'
                ]
            ]
        ],
        'ar' => [
            'title' => 'الحصن التنافسي والحواجز دخول',
            'sections' => [
                [
                    'heading' => 'آثار الشبكة وشبكة البيانات',
                    'content' => 'كلما استخدم المزيد من الجامعات والمؤسسات Hackify، أصبحت المنصة أكثر قيمة من خلال الرؤى المجمعة حول اتجاهات الابتكار وبيانات المعايير وشبكات المواهب. يؤدي تبادل الأفكار والتعاون بين المنظمات إلى احتفاظ قوي. ميزة البيانات: وصول حصري إلى نتائج الأكاديمية ومقاييس الابتكار غير المتاحة للمنافسين.'
                ],
                [
                    'heading' => 'التمايز التكنولوجي والمنتج',
                    'content' => 'خوارزميات ذكاء اصطناعي مملوكة لتقييم الأفكار ودعم الحكم تخلق ميزة تطوير لمدة 6-12 شهر. تتطلب بنية العلامة البيضاء جهودًا هندسية كبيرة لتكرار. تم بناء التصميم الذي يركز على الهاتف المحمول وتحسين تجربة المستخدم من الأساس وليس تصميم مرتجل. يمثل دعم اللغات المتعددة (35+ لغة) استثمارًا جاريًا كبيرًا لا يمكن للمنافسين تكراره بسهولة.'
                ],
                [
                    'heading' => 'قفل العملاء وتكاليف التبديل',
                    'content' => 'العقود السنوية ذات تكاليف التبديل العالية بسبب تعقيد الهجرة والتخصيص وسير العمل والعمل المتكامل. يستثمر العملاء في تدريب الموظفين على سير عمل Hackify. يبني استضافة الحدث المتكررة الاعتماد والعادة. يوقع عملاء المؤسسات اتفاقيات متعددة السنوات بخصومات كبيرة.'
                ],
                [
                    'heading' => 'العلامة التجارية والنظام البيئي للشراكة',
                    'content' => 'إنشاء Hackify كمعيار لإدارة الأكاديمية من خلال شراكات جامعية وشركات. إنشاء ربط العلامات التجارية مع الابتكار وريادة الأعمال. بناء المجتمع حول المنصة مع الأحداث والمنتديات والمحتوى. يصبح النظام البيئي للتكامل أكثر قيمة مع نمو التكاملات من طرف ثالث.'
                ],
                [
                    'heading' => 'المواهب والتنفيذ',
                    'content' => 'بناء فريق بخبرة عميقة في الأكاديمية وإدارة الابتكار. جذب أفضل مواهب الهندسة من خلال الأسهم والمهمة. الحفاظ على سرعة المنتج وخريطة الطريق للابتكار التي يجد المنافسون صعوبة في مطابقتها. السمعة بنجاح العملاء والدعم تخلق الكلام الإيجابي من الفم إلى الفم في السوق محدودة.'
                ]
            ]
        ]
    ]
], 'competitors' => [['name' => 'Talenthub', 'website' => 'talenthub.io', 'description' => 'Global platform focusing on talent acquisition. Strong US/EU presence, limited enterprise features.', 'description_ar' => 'منصة عالمية تركز على استقطاب المواهب. وجود قوي في الولايات المتحدة وأوروبا، ميزات مؤسسية محدودة.', 'strengths' => ['Large base', 'Established brand', 'Multiple integrations'], 'weaknesses' => ['Limited white-label', 'High pricing ($20K-60K)', 'Poor support']], ['name' => 'EventMentor', 'website' => 'eventmentor.io', 'description' => 'Event platform with hackathon module. Generic, lacks specialization.', 'description_ar' => 'منصة فعاليات مع وحدة هاكاثون. عامة، تفتقر التخصص.', 'strengths' => ['Affordable', 'Easy setup', 'Event analytics'], 'weaknesses' => ['No AI', 'Limited customization', 'Poor scalability']], ['name' => 'DevMatch Pro', 'website' => 'devmatchpro.io', 'description' => 'MENA competitor with basic features. Recently funded, questionable execution.', 'description_ar' => 'منافس إقليمي مع ميزات أساسية. تمويل حديث، تنفيذ مشكوك فيه.', 'strengths' => ['Regional', 'Arabic support', 'Competitive pricing'], 'weaknesses' => ['Limited scalability', 'Poor infrastructure', 'Weak team']]], 'prompts' => [['section_key' => 'dashboard_executive_summary', 'template' => 'Highlight market opportunity, solution differentiation, traction metrics, revenue model. Include 2-3 successful implementation examples.', 'template_ar' => 'أبرز فرصة السوق والحل المميز ومقاييس الجذب ونموذج الإيراد. قم بتضمين 2-3 أمثلة للتنفيذات الناجحة.'], ['section_key' => 'sf_market_size', 'template' => 'Calculate TAM, SAM, SOM for hackathon management. Include growth rates and regional analysis.', 'template_ar' => 'احسب TAM و SAM و SOM. قم بتضمين معدلات النمو والتحليل الإقليمي.'], ['section_key' => 'mvp_feature_priority', 'template' => 'Prioritize using impact vs effort. Identify P0 (must-have), P1 (should-have), P2 (nice-to-have) with timeline.', 'template_ar' => 'أولويات باستخدام التأثير مقابل الجهد. تحديد الميزات مع جدول زمني.']]];
    }

    private function getSalisData(): array
    {
        return ['name' => 'SALIS', 'name_ar' => 'ساليس', 'description' => 'API-first Know Your Business (KYB) platform with real-time CR verification and continuous monitoring', 'description_ar' => 'منصة KYB الموجهة نحو API مع التحقق الفوري والمراقبة المستمرة', 'industry' => 'RegTech / FinTech', 'industry_ar' => 'تكنولوجيا تنظيمية / تكنولوجيا مالية', 'viability_score' => 91, 'sections' => ['dashboard_viability_score' => ['en' => ['overall' => 91, 'dimensions' => [['label' => 'Market Opportunity', 'score' => 95], ['label' => 'Product-Market Fit', 'score' => 92], ['label' => 'Business Model', 'score' => 90], ['label' => 'Team & Execution', 'score' => 88], ['label' => 'Financial Viability', 'score' => 89]]], 'ar' => ['overall' => 91, 'dimensions' => [['label' => 'فرصة السوق', 'score' => 95], ['label' => 'توافق المنتج', 'score' => 92], ['label' => 'نموذج الأعمال', 'score' => 90], ['label' => 'الفريق والتنفيذ', 'score' => 88], ['label' => 'الجدوى المالية', 'score' => 89]]]], 'dashboard_executive_summary' => ['en' => ['title' => 'Executive Summary', 'summary' => 'API-first KYB platform providing real-time CR verification, delegated access management, continuous compliance monitoring for financial institutions and enterprises in Saudi Arabia and MENA.', 'sections' => [['heading' => 'Market Opportunity', 'content' => 'RegTech in MENA growing 45% CAGR. Saudi Arabia $320M KYC/KYB opportunity. Banks/fintech face $2.1M+ annual compliance costs. Vision 2030 strengthens verification requirements.'], ['heading' => 'Solution', 'content' => 'Real-time CR API integration with <100ms response. Features: instant CR lookup, delegated access, AES-256 encryption, monitoring, compliance reporting. White-label APIs for bank/fintech integration.'], ['heading' => 'Traction', 'content' => '8 major Saudi banks, 12+ fintech, 3 government agencies. 2.5M+ verifications monthly. $3.8M ARR at 55% YoY growth. 99.99% uptime, zero compliance incidents.'], ['heading' => 'Business Model', 'content' => 'Usage-based: $0.25-$2 per verification. Enterprise licenses: $5K-$25K/month. Premium monitoring: $500-$5K/month.']]],  'ar' => ['title' => 'الملخص التنفيذي', 'summary' => 'منصة KYB الموجهة نحو API توفر التحقق الفوري والمراقبة المستمرة', 'sections' => [['heading' => 'فرصة السوق', 'content' => 'RegTech في الشرق الأوسط وشمال أفريقيا ينمو 45% سنويًا. المملكة العربية السعودية $320M. البنوك والشركات تواجه $2.1M+ تكاليف امتثال سنوية.'], ['heading' => 'الحل', 'content' => 'تكامل CR في الوقت الفعلي <100ms. البحث الفوري، الوصول المفوض، التشفير AES-256، المراقبة، الإبلاغ عن الامتثال.'], ['heading' => 'الجذب', 'content' => '8 بنوك سعودية كبرى، 12+ شركة ناشئة، 3 وكالات حكومية. 2.5M+ تحقق شهريًا. $3.8M ARR بنمو 55% سنويًا.'], ['heading' => 'نموذج الأعمال', 'content' => 'بناءً على الاستخدام: $0.25-$2 لكل تحقق. تراخيص المؤسسات: $5K-$25K/شهر.']]], 'dashboard_key_metrics' => ['en' => [['label' => 'Current ARR', 'value' => '$3.8M', 'description' => 'From API usage and enterprise licenses', 'trend' => '+55% YoY'], ['label' => 'Monthly Verifications', 'value' => '2.5M+', 'description' => 'Business verification requests', 'trend' => '+60% YoY'], ['label' => 'Active Customers', 'value' => '23+', 'description' => 'Banks, fintech, government agencies', 'trend' => '+8 YoY'], ['label' => 'System Uptime', 'value' => '99.99%', 'description' => 'SLA-guaranteed availability', 'trend' => 'Zero incidents']], 'ar' => [['label' => 'الإيراد السنوي', 'value' => '3.8 مليون دولار', 'description' => 'من استخدام API والتراخيص', 'trend' => '+55% سنويًا'], ['label' => 'التحقق الشهري', 'value' => '2.5 مليون+', 'description' => 'طلبات التحقق من الأعمال', 'trend' => '+60% سنويًا'], ['label' => 'العملاء النشطون', 'value' => '23+', 'description' => 'البنوك والشركات الناشئة والوكالات', 'trend' => '+8 سنويًا'], ['label' => 'وقت التشغيل', 'value' => '99.99%', 'description' => 'توفر SLA مضمونة', 'trend' => 'بدون حوادث']]], 'dashboard_swot_overview' => ['en' => ['strengths' => ['Direct Saudi Registry integration', 'Sub-100ms response times', 'AES-256 compliance certified', '$3.8M ARR, strong growth', 'Zero compliance incidents'], 'weaknesses' => ['Limited geographic expansion outside KSA', 'Government registry API dependency', 'Banking sector concentration', 'Technical debt from rapid scaling', 'Limited marketing awareness'], 'opportunities' => ['UAE, Kuwait, Bahrain CR expansion', 'Continuous monitoring add-ons', 'KYC/PEP screening integration', 'Fintech API marketplace', 'Government contracts'], 'threats' => ['Central Bank competing solution', 'Stricter data privacy regulations', 'Customer consolidation', 'Cyber attacks', 'International payment regulations']], 'ar' => ['strengths' => ['تكامل مباشر مع السجل السعودي', 'أوقات استجابة <100ms', 'امتثال AES-256 معتمد', '$3.8M ARR مع نمو قوي', 'بدون حوادث امتثال'], 'weaknesses' => ['توسع جغرافي محدود خارج المملكة', 'اعتماد على واجهات برمجة تطبيقات السجل', 'تركيز القطاع المصرفي', 'ديون تقنية من التوسع السريع', 'وعي تسويقي محدود'], 'opportunities' => ['توسع الإمارات والكويت والبحرين', 'إضافات المراقبة المستمرة', 'تكامل فحص KYC/PEP', 'سوق fintech API', 'عقود حكومية'], 'threats' => ['حل البنك المركزي المنافس', 'تنظيمات خصوصية البيانات الأكثر صرامة', 'توحيد العملاء', 'هجمات سيبرانية', 'تنظيمات الدفع الدولية']],

    'mvp_feature_priority' => [
        'en' => [
            'component_type' => 'comparison_table',
            'data' => [
                'headers' => ['Feature', 'Priority', 'Implementation', 'Impact'],
                'rows' => [
                    ['Transaction Screening', 'Critical', 'Week 1-2', 'High'],
                    ['Regulatory Reporting', 'Critical', 'Week 2-3', 'High'],
                    ['AML Risk Scoring', 'High', 'Week 3-4', 'High'],
                    ['Compliance Dashboard', 'High', 'Week 4-5', 'Medium'],
                    ['Audit Trail System', 'Medium', 'Week 5-6', 'Medium'],
                    ['Integration APIs', 'High', 'Week 6-8', 'High']
                ]
            ]
        ],
        'ar' => [
            'component_type' => 'comparison_table',
            'data' => [
                'headers' => ['الميزة', 'الأولوية', 'التنفيذ', 'التأثير'],
                'rows' => [
                    ['فحص المعاملات', 'حرج', 'الأسبوع 1-2', 'عالي'],
                    ['التقارير التنظيمية', 'حرج', 'الأسبوع 2-3', 'عالي'],
                    ['تسجيل مخاطر AML', 'عالي', 'الأسبوع 3-4', 'عالي'],
                    ['لوحة التحكم الامتثال', 'عالي', 'الأسبوع 4-5', 'متوسط'],
                    ['نظام دقيق للتدقيق', 'متوسط', 'الأسبوع 5-6', 'متوسط'],
                    ['واجهات برمجية التكامل', 'عالي', 'الأسبوع 6-8', 'عالي']
                ]
            ]
        ]
    ],

    'mvp_development_roadmap' => [
        'en' => [
            'component_type' => 'journey_timeline',
            'data' => [
                'stages' => [
                    [
                        'title' => 'Phase 1: Foundation',
                        'description' => 'Core infrastructure and transaction screening engine',
                        'touchpoints' => ['Database Setup', 'API Framework', 'Screening Rules'],
                        'actions' => ['Deploy infrastructure', 'Build rule engine', 'Integration testing']
                    ],
                    [
                        'title' => 'Phase 2: Compliance Engine',
                        'description' => 'Regulatory reporting and AML risk assessment',
                        'touchpoints' => ['Risk Scoring', 'Report Generation', 'Audit Logging'],
                        'actions' => ['Implement algorithms', 'Build report templates', 'Enable auditing']
                    ],
                    [
                        'title' => 'Phase 3: User Interface',
                        'description' => 'Dashboard and user management system',
                        'touchpoints' => ['Admin Dashboard', 'User Interface', 'Mobile Support'],
                        'actions' => ['Design UI/UX', 'Develop frontend', 'Mobile optimization']
                    ],
                    [
                        'title' => 'Phase 4: Launch',
                        'description' => 'Beta testing and market launch',
                        'touchpoints' => ['QA Testing', 'Customer Training', 'Go Live'],
                        'actions' => ['Execute testing', 'Train users', 'Launch product']
                    ]
                ]
            ]
        ],
        'ar' => [
            'component_type' => 'journey_timeline',
            'data' => [
                'stages' => [
                    [
                        'title' => 'المرحلة 1: الأساس',
                        'description' => 'البنية الأساسية ومحرك فحص المعاملات',
                        'touchpoints' => ['إعداد قاعدة البيانات', 'إطار العمل API', 'قواعد الفحص'],
                        'actions' => ['نشر البنية الأساسية', 'بناء محرك القواعد', 'اختبار التكامل']
                    ],
                    [
                        'title' => 'المرحلة 2: محرك الامتثال',
                        'description' => 'التقارير التنظيمية وتقييم مخاطر AML',
                        'touchpoints' => ['تسجيل المخاطر', 'إنشاء التقارير', 'تسجيل التدقيق'],
                        'actions' => ['تنفيذ الخوارزميات', 'بناء قوالب التقرير', 'تفعيل التدقيق']
                    ],
                    [
                        'title' => 'المرحلة 3: واجهة المستخدم',
                        'description' => 'لوحة التحكم ونظام إدارة المستخدمين',
                        'touchpoints' => ['لوحة التحكم', 'الواجهة', 'الدعم المحمول'],
                        'actions' => ['تصميم الواجهة', 'تطوير الواجهة الأمامية', 'تحسين الهاتف المحمول']
                    ],
                    [
                        'title' => 'المرحلة 4: الإطلاق',
                        'description' => 'الاختبار التجريبي وإطلاق السوق',
                        'touchpoints' => ['اختبار الجودة', 'تدريب العملاء', 'البث المباشر'],
                        'actions' => ['تنفيذ الاختبار', 'تدريب المستخدمين', 'إطلاق المنتج']
                    ]
                ]
            ]
        ]
    ],

    'mvp_tech_stack' => [
        'en' => [
            'component_type' => 'key_value',
            'data' => [
                'items' => [
                    ['key' => 'Backend', 'value' => 'PHP 8.2 + Laravel 11'],
                    ['key' => 'Database', 'value' => 'PostgreSQL with Redis cache'],
                    ['key' => 'Frontend', 'value' => 'React 18 + TypeScript'],
                    ['key' => 'Mobile', 'value' => 'React Native'],
                    ['key' => 'Deployment', 'value' => 'Docker + AWS ECS'],
                    ['key' => 'CI/CD', 'value' => 'GitHub Actions'],
                    ['key' => 'Monitoring', 'value' => 'DataDog + PagerDuty'],
                    ['key' => 'Security', 'value' => 'TLS 1.3, AES-256 encryption']
                ]
            ]
        ],
        'ar' => [
            'component_type' => 'key_value',
            'data' => [
                'items' => [
                    ['key' => 'الخادم الخلفي', 'value' => 'PHP 8.2 + Laravel 11'],
                    ['key' => 'قاعدة البيانات', 'value' => 'PostgreSQL مع ذاكرة تخزين Redis'],
                    ['key' => 'الواجهة الأمامية', 'value' => 'React 18 + TypeScript'],
                    ['key' => 'المحمول', 'value' => 'React Native'],
                    ['key' => 'النشر', 'value' => 'Docker + AWS ECS'],
                    ['key' => 'التكامل المستمر', 'value' => 'GitHub Actions'],
                    ['key' => 'المراقبة', 'value' => 'DataDog + PagerDuty'],
                    ['key' => 'الأمان', 'value' => 'TLS 1.3, تشفير AES-256']
                ]
            ]
        ]
    ],

    'mvp_resource_requirements' => [
        'en' => [
            'component_type' => 'stat_cards',
            'data' => [
                'metrics' => [
                    ['label' => 'Development Team', 'value' => '8', 'description' => 'Engineers and architects'],
                    ['label' => 'Timeline', 'value' => '8 weeks', 'description' => 'To MVP launch'],
                    ['label' => 'Budget', 'value' => '$280K', 'description' => 'Development costs'],
                    ['label' => 'Infrastructure', 'value' => '$15K/mo', 'description' => 'Cloud and hosting']
                ]
            ]
        ],
        'ar' => [
            'component_type' => 'stat_cards',
            'data' => [
                'metrics' => [
                    ['label' => 'فريق التطوير', 'value' => '8', 'description' => 'المهندسون والمعماريون'],
                    ['label' => 'الجدول الزمني', 'value' => '8 أسابيع', 'description' => 'حتى إطلاق MVP'],
                    ['label' => 'الميزانية', 'value' => '$280K', 'description' => 'تكاليف التطوير'],
                    ['label' => 'البنية الأساسية', 'value' => '$15K/mo', 'description' => 'السحابة والاستضافة']
                ]
            ]
        ]
    ],

    'mvp_risk_mitigation' => [
        'en' => [
            'component_type' => 'text_content',
            'data' => [
                'title' => 'MVP Risk Mitigation Strategy',
                'sections' => [
                    [
                        'heading' => 'Technical Risks',
                        'content' => 'Implement redundant systems for transaction screening to ensure 99.99% uptime. Use proven technologies (Laravel, PostgreSQL) with extensive documentation. Establish comprehensive testing protocols including load testing, security audits, and penetration testing before launch.'
                    ],
                    [
                        'heading' => 'Regulatory Risks',
                        'content' => 'Partner with compliance consultants to ensure adherence to FATF and GCC regulatory requirements. Conduct regular compliance audits. Maintain detailed audit trails for all system activities. Document all compliance procedures and maintain regulatory certifications.'
                    ],
                    [
                        'heading' => 'Market Risks',
                        'content' => 'Validate product-market fit with pilot customers from major Saudi and UAE banks. Establish early feedback mechanisms. Build flexible pricing model to adapt to market demands. Plan for rapid iterations based on customer feedback.'
                    ],
                    [
                        'heading' => 'Operational Risks',
                        'content' => 'Hire experienced RegTech professionals with AML compliance background. Implement robust incident response procedures. Establish 24/7 customer support infrastructure. Create comprehensive documentation and training materials.'
                    ]
                ]
            ]
        ],
        'ar' => [
            'component_type' => 'text_content',
            'data' => [
                'title' => 'استراتيجية تخفيف مخاطر MVP',
                'sections' => [
                    [
                        'heading' => 'المخاطر التقنية',
                        'content' => 'تنفيذ أنظمة زائدة لفحص المعاملات لضمان وقت التشغيل 99.99%. استخدام تقنيات مثبتة (Laravel، PostgreSQL) مع توثيق شامل. إنشاء بروتوكولات اختبار شاملة تشمل اختبار الحمل والتدقيق الأمني واختبار الاختراق قبل الإطلاق.'
                    ],
                    [
                        'heading' => 'المخاطر التنظيمية',
                        'content' => 'الشراكة مع مستشاري الامتثال لضمان الامتثال لمتطلبات FATF والمتطلبات التنظيمية لمجلس التعاون الخليجي. إجراء تدقيق امتثال منتظم. الحفاظ على سجلات تدقيق مفصلة لجميع أنشطة النظام. توثيق جميع إجراءات الامتثال والحفاظ على شهادات تنظيمية.'
                    ],
                    [
                        'heading' => 'مخاطر السوق',
                        'content' => 'التحقق من توافق المنتج مع السوق مع العملاء التجريبيين من البنوك الرئيسية في السعودية والإمارات. إنشاء آليات تغذية راجعة مبكرة. بناء نموذج تسعير مرن للتكيف مع متطلبات السوق. التخطيط للتكرارات السريعة بناءً على ملاحظات العملاء.'
                    ],
                    [
                        'heading' => 'مخاطر التشغيل',
                        'content' => 'توظيف محترفي RegTech ذوي خبرة في امتثال AML. تنفيذ إجراءات استجابة حوادث قوية. إنشاء بنية دعم العملاء على مدار الساعة طوال أيام الأسبوع. إنشاء توثيق شامل ومواد تدريبية.'
                    ]
                ]
            ]
        ]
    ],

    'usp_unique_selling_points' => [
        'en' => [
            'component_type' => 'text_content',
            'data' => [
                'title' => 'Unique Selling Points',
                'sections' => [
                    [
                        'heading' => 'GCC-Specific Compliance',
                        'content' => 'Purpose-built for Saudi Arabia and GCC financial regulations, not a generic global solution. Integrated with SAMA, CBU, and local regulatory requirements. Supports Arabic documentation natively with right-to-left formatting.'
                    ],
                    [
                        'heading' => 'AI-Powered Risk Assessment',
                        'content' => 'Machine learning algorithms trained on regional transaction patterns. Real-time anomaly detection with 94% accuracy. Adaptive rules that learn from customer behavior and market conditions.'
                    ],
                    [
                        'heading' => 'Rapid Implementation',
                        'content' => 'Average deployment time of 2-3 weeks. Pre-configured rule sets for Saudi and GCC requirements. Plug-and-play integration with major regional banking platforms.'
                    ],
                    [
                        'heading' => 'Cost Efficiency',
                        'content' => '70% lower operational costs compared to legacy systems. No upfront infrastructure investment required. Pay-as-you-grow pricing model with flexible scaling.'
                    ]
                ]
            ]
        ],
        'ar' => [
            'component_type' => 'text_content',
            'data' => [
                'title' => 'نقاط البيع الفريدة',
                'sections' => [
                    [
                        'heading' => 'الامتثال الخاص بمجلس التعاون الخليجي',
                        'content' => 'مصمم خصيصاً لتنظيمات السعودية والخليج المالية، وليس حلاً عاماً عالمياً. متكامل مع SAMA وCBU والمتطلبات التنظيمية المحلية. يدعم التوثيق العربي بشكل أصلي مع تنسيق من اليمين إلى اليسار.'
                    ],
                    [
                        'heading' => 'تقييم المخاطر المدعوم بالذكاء الاصطناعي',
                        'content' => 'خوارزميات التعلم الآلي المدربة على أنماط المعاملات الإقليمية. الكشف عن الحالات الشاذة في الوقت الفعلي بدقة 94%. قواعد تكيفية تتعلم من سلوك العملاء وظروف السوق.'
                    ],
                    [
                        'heading' => 'التنفيذ السريع',
                        'content' => 'وقت النشر الإجمالي من 2-3 أسابيع. مجموعات قواعد مسبقة التكوين لمتطلبات السعودية والخليج. التكامل السلس مع منصات البنوك الإقليمية الرئيسية.'
                    ],
                    [
                        'heading' => 'كفاءة التكاليف',
                        'content' => 'تكاليف تشغيلية أقل بنسبة 70٪ مقارنة بالأنظمة القديمة. لا توجد استثمارات بنية أساسية أولية مطلوبة. نموذج التسعير المرن مع التوسع المرن.'
                    ]
                ]
            ]
        ]
    ],

    'usp_competitive_advantage' => [
        'en' => [
            'component_type' => 'comparison_table',
            'data' => [
                'headers' => ['Dimension', 'SALIS', 'Competitors', 'Advantage'],
                'rows' => [
                    ['Regional Expertise', 'GCC-native', 'Global', 'Deep market knowledge'],
                    ['Implementation Speed', '2-3 weeks', '8-12 weeks', '4x faster'],
                    ['AI Accuracy', '94%', '85-88%', '+6% higher'],
                    ['Cost', '$15K/mo', '$35K+/mo', '57% lower'],
                    ['Arabic Support', 'Native RTL', 'Add-on only', 'Fully integrated'],
                    ['Regulatory Updates', 'Real-time', 'Quarterly', 'Always compliant']
                ]
            ]
        ],
        'ar' => [
            'component_type' => 'comparison_table',
            'data' => [
                'headers' => ['الجانب', 'SALIS', 'المنافسون', 'الميزة'],
                'rows' => [
                    ['الخبرة الإقليمية', 'أصلي من الخليج', 'عالمي', 'معرفة عميقة بالسوق'],
                    ['سرعة التنفيذ', '2-3 أسابيع', '8-12 أسبوع', 'أسرع 4 مرات'],
                    ['دقة الذكاء الاصطناعي', '94%', '85-88%', '+6% أعلى'],
                    ['التكلفة', '$15K/mo', '$35K+/mo', 'أقل بـ 57%'],
                    ['دعم اللغة العربية', 'RTL أصلي', 'إضافة فقط', 'متكامل بالكامل'],
                    ['التحديثات التنظيمية', 'الوقت الفعلي', 'ربع سنوي', 'دائماً متوافق']
                ]
            ]
        ]
    ],

    'usp_differentiation_strategy' => [
        'en' => [
            'component_type' => 'text_content',
            'data' => [
                'title' => 'Market Differentiation Strategy',
                'sections' => [
                    [
                        'heading' => 'Localization Excellence',
                        'content' => 'SALIS is not a translated product but a natively built solution for GCC markets. Every feature, from regulatory rules to customer support, is designed with regional expertise. We understand the nuances of SAMA regulations, CBU guidelines, and local banking practices.'
                    ],
                    [
                        'heading' => 'Vertical Integration',
                        'content' => 'We combine AML screening, risk assessment, regulatory reporting, and audit compliance in one integrated platform. Eliminates data silos and reduces integration complexity. Customers get a single source of truth for compliance.'
                    ],
                    [
                        'heading' => 'Innovation at Scale',
                        'content' => 'Continuous AI model improvements based on regional data patterns. Monthly feature updates and regulatory enhancements. Proactive compliance notifications before new regulations take effect.'
                    ],
                    [
                        'heading' => 'Customer Success Focus',
                        'content' => 'Dedicated implementation teams for each customer. Regular compliance training and updates. White-glove support for regulatory interactions and audit preparation.'
                    ]
                ]
            ]
        ],
        'ar' => [
            'component_type' => 'text_content',
            'data' => [
                'title' => 'استراتيجية التمايز في السوق',
                'sections' => [
                    [
                        'heading' => 'التميز في التوطين',
                        'content' => 'SALIS ليست منتجاً مترجماً بل حلاً مبنياً بشكل أصلي لأسواق مجلس التعاون الخليجي. كل ميزة، من القواعس التنظيمية إلى دعم العملاء، مصممة بخبرة إقليمية. نحن نفهم الفروقات الدقيقة في لوائح SAMA وإرشادات CBU والممارسات المصرفية المحلية.'
                    ],
                    [
                        'heading' => 'التكامل الرأسي',
                        'content' => 'نجمع بين فحص AML وتقييم المخاطر والإبلاغ التنظيمي والامتثال التدقيقي في منصة واحدة متكاملة. يلغي صوامع البيانات ويقلل من تعقيد التكامل. يحصل العملاء على مصدر موحد للحقيقة من أجل الامتثال.'
                    ],
                    [
                        'heading' => 'الابتكار على النطاق الواسع',
                        'content' => 'تحسينات نموذج الذكاء الاصطناعي المستمرة بناءً على أنماط البيانات الإقليمية. التحديثات الميزات الشهرية والتحسينات التنظيمية. إخطارات الامتثال الاستباقية قبل دخول اللوائح الجديدة حيز التنفيذ.'
                    ],
                    [
                        'heading' => 'التركيز على نجاح العملاء',
                        'content' => 'فرق التنفيذ المكرسة لكل عميل. تدريب الامتثال المنتظم والتحديثات. دعم الخدمة الكاملة للتفاعلات التنظيمية وإعداد التدقيق.'
                    ]
                ]
            ]
        ]
    ],

    'usp_value_chain' => [
        'en' => [
            'component_type' => 'key_value',
            'data' => [
                'items' => [
                    ['key' => 'Product Development', 'value' => 'In-house R&D with regional expertise'],
                    ['key' => 'Data Intelligence', 'value' => 'Proprietary AML datasets and ML models'],
                    ['key' => 'Integration', 'value' => 'Direct partnerships with regional banks'],
                    ['key' => 'Compliance Support', 'value' => 'Expert consultants and legal advisors'],
                    ['key' => 'Customer Success', 'value' => 'Dedicated implementation and support teams'],
                    ['key' => 'Infrastructure', 'value' => 'ISO 27001 certified secure cloud'],
                    ['key' => 'Regulatory Relations', 'value' => 'Direct relationships with SAMA, CBU'],
                    ['key' => 'Distribution', 'value' => 'Direct sales and channel partnerships']
                ]
            ]
        ],
        'ar' => [
            'component_type' => 'key_value',
            'data' => [
                'items' => [
                    ['key' => 'تطوير المنتج', 'value' => 'البحث والتطوير الداخلي مع الخبرة الإقليمية'],
                    ['key' => 'ذكاء البيانات', 'value' => 'مجموعات بيانات AML ملكية ونماذج ML'],
                    ['key' => 'التكامل', 'value' => 'الشراكات المباشرة مع البنوك الإقليمية'],
                    ['key' => 'دعم الامتثال', 'value' => 'مستشارون خبراء ومستشارون قانونيون'],
                    ['key' => 'نجاح العملاء', 'value' => 'فرق التنفيذ والدعم المكرسة'],
                    ['key' => 'البنية الأساسية', 'value' => 'سحابة آمنة معتمدة ISO 27001'],
                    ['key' => 'العلاقات التنظيمية', 'value' => 'العلاقات المباشرة مع SAMA و CBU'],
                    ['key' => 'التوزيع', 'value' => 'المبيعات المباشرة والشراكات الموزعة']
                ]
            ]
        ]
    ],

    'cp_primary_persona' => [
        'en' => [
            'component_type' => 'persona_card',
            'data' => [
                'name' => 'Fatima Al-Mansouri',
                'role' => 'Chief Compliance Officer',
                'age' => 48,
                'location' => 'Riyadh, Saudi Arabia',
                'quote' => 'We need a compliance system that understands our market, not something translated from elsewhere.',
                'demographics' => [
                    'experience' => '18 years in compliance',
                    'institution' => 'Major Saudi bank',
                    'education' => 'MBA Finance, CFA charterholder',
                    'annual_budget' => '$2.5M compliance'
                ],
                'pain_points' => [
                    'Manual compliance processes consume 40% of team time',
                    'Regulatory changes require constant system updates',
                    'Existing systems lack Arabic language support',
                    'High false positive rates in transaction screening',
                    'Fragmented tools across different compliance functions'
                ],
                'goals' => [
                    'Reduce compliance team operational workload by 50%',
                    'Achieve zero regulatory violations',
                    'Improve audit readiness and documentation',
                    'Implement AI-driven risk assessment',
                    'Streamline reporting to SAMA and regulators'
                ],
                'motivations' => [
                    'Protecting bank reputation and avoiding penalties',
                    'Meeting FATF and GCC regulatory requirements',
                    'Reducing operational expenses',
                    'Gaining competitive advantage through efficiency',
                    'Advancing career through digital transformation'
                ]
            ]
        ],
        'ar' => [
            'component_type' => 'persona_card',
            'data' => [
                'name' => 'فاطمة المنصوري',
                'role' => 'رئيسة الامتثال',
                'age' => 48,
                'location' => 'الرياض، المملكة العربية السعودية',
                'quote' => 'نحتاج إلى نظام امتثال يفهم سوقنا، وليس شيء مترجم من مكان آخر.',
                'demographics' => [
                    'experience' => '18 سنة في الامتثال',
                    'institution' => 'بنك سعودي رئيسي',
                    'education' => 'ماجستير في المالية، حامل CFA',
                    'annual_budget' => '$2.5M امتثال'
                ],
                'pain_points' => [
                    'العمليات اليدوية للامتثال تستهلك 40٪ من وقت الفريق',
                    'التغييرات التنظيمية تتطلب تحديثات نظام مستمرة',
                    'الأنظمة الحالية تفتقر إلى دعم اللغة العربية',
                    'معدلات إيجابية كاذبة عالية في فحص المعاملات',
                    'أدوات مجزأة عبر وظائف الامتثال المختلفة'
                ],
                'goals' => [
                    'تقليل عبء عمل فريق الامتثال التشغيلي بنسبة 50٪',
                    'تحقيق انتهاكات تنظيمية صفر',
                    'تحسين جاهزية التدقيق والتوثيق',
                    'تنفيذ تقييم المخاطر المدعوم بالذكاء الاصطناعي',
                    'تبسيط الإبلاغ إلى SAMA والمنظمين'
                ],
                'motivations' => [
                    'حماية سمعة البنك وتجنب العقوبات',
                    'تلبية متطلبات FATF والمتطلبات التنظيمية لمجلس التعاون الخليجي',
                    'تقليل النفقات التشغيلية',
                    'اكتساب ميزة تنافسية من خلال الكفاءة',
                    'تقدم المهنية من خلال التحول الرقمي'
                ]
            ]
        ]
    ],

    'cp_secondary_persona' => [
        'en' => [
            'component_type' => 'persona_card',
            'data' => [
                'name' => 'Mohammed Al-Otaibi',
                'role' => 'Head of Risk Management',
                'age' => 42,
                'location' => 'Dubai, UAE',
                'quote' => 'We need real-time visibility into transaction risks across our entire operation.',
                'demographics' => [
                    'experience' => '14 years in risk management',
                    'institution' => 'Major UAE financial institution',
                    'education' => 'Masters Risk Management, GARP FRM',
                    'annual_budget' => '$1.8M risk systems'
                ],
                'pain_points' => [
                    'Legacy systems provide delayed risk insights',
                    'Integration between screening and risk tools is poor',
                    'Manual alert review consumes significant resources',
                    'Lack of predictive analytics for emerging risks',
                    'Difficult to explain risk decisions to stakeholders'
                ],
                'goals' => [
                    'Implement real-time risk monitoring dashboard',
                    'Reduce false positives by 60%',
                    'Achieve 95%+ alert accuracy',
                    'Automate low-risk transaction processing',
                    'Enable predictive risk assessments'
                ],
                'motivations' => [
                    'Preventing financial crime and money laundering',
                    'Maintaining regulatory compliance',
                    'Improving operational efficiency',
                    'Providing senior management with actionable insights',
                    'Building trust with regulators through transparency'
                ]
            ]
        ],
        'ar' => [
            'component_type' => 'persona_card',
            'data' => [
                'name' => 'محمد العتيبي',
                'role' => 'رئيس إدارة المخاطر',
                'age' => 42,
                'location' => 'دبي، الإمارات العربية المتحدة',
                'quote' => 'نحتاج إلى رؤية في الوقت الفعلي لمخاطر المعاملات عبر عملياتنا بأكملها.',
                'demographics' => [
                    'experience' => '14 سنة في إدارة المخاطر',
                    'institution' => 'مؤسسة مالية رئيسية في الإمارات',
                    'education' => 'ماجستير إدارة المخاطر، GARP FRM',
                    'annual_budget' => '$1.8M أنظمة المخاطر'
                ],
                'pain_points' => [
                    'توفر الأنظمة القديمة رؤية متأخرة عن المخاطر',
                    'التكامل بين أدوات الفحص والمخاطر ضعيف',
                    'المراجعة اليدوية للتنبيهات تستهلك موارد كبيرة',
                    'نقص التحليلات التنبؤية للمخاطر الناشئة',
                    'صعوبة شرح قرارات المخاطر لأصحاب المصلحة'
                ],
                'goals' => [
                    'تنفيذ لوحة مراقبة المخاطر في الوقت الفعلي',
                    'تقليل الإيجابيات الكاذبة بنسبة 60٪',
                    'تحقيق دقة التنبيهات +95٪',
                    'أتمتة معالجة المعاملات منخفضة المخاطر',
                    'تمكين تقييمات المخاطر التنبؤية'
                ],
                'motivations' => [
                    'منع الجرائم المالية وغسل الأموال',
                    'الحفاظ على الامتثال التنظيمي',
                    'تحسين الكفاءة التشغيلية',
                    'توفير الإدارة العليا برؤى قابلة للتطبيق',
                    'بناء الثقة مع المنظمين من خلال الشفافية'
                ]
            ]
        ]
    ],

    'cp_buyer_journey' => [
        'en' => [
            'component_type' => 'journey_timeline',
            'data' => [
                'stages' => [
                    [
                        'title' => 'Awareness',
                        'description' => 'Recognize compliance challenges and seek solutions',
                        'touchpoints' => ['Industry events', 'Peer recommendations', 'Online research', 'Regulatory updates'],
                        'actions' => ['Identify pain points', 'Research vendors', 'Request demos', 'Compare solutions']
                    ],
                    [
                        'title' => 'Evaluation',
                        'description' => 'Assess technical and business fit',
                        'touchpoints' => ['Product demo', 'Technical assessment', 'Case studies', 'Pricing discussion'],
                        'actions' => ['Evaluate features', 'Check compliance', 'Review integrations', 'Negotiate terms']
                    ],
                    [
                        'title' => 'Decision',
                        'description' => 'Select vendor and sign contract',
                        'touchpoints' => ['Contract negotiation', 'Legal review', 'Security audit', 'Executive approval'],
                        'actions' => ['Final negotiations', 'Sign agreement', 'Allocate budget', 'Plan implementation']
                    ],
                    [
                        'title' => 'Implementation',
                        'description' => 'Deploy solution and train teams',
                        'touchpoints' => ['Go-live planning', 'Data migration', 'User training', 'Go-live support'],
                        'actions' => ['Configure system', 'Train staff', 'Test workflows', 'Launch production']
                    ],
                    [
                        'title' => 'Adoption',
                        'description' => 'Full usage and value realization',
                        'touchpoints' => ['Success metrics', 'Support requests', 'Feature requests', 'Renewal discussion'],
                        'actions' => ['Monitor KPIs', 'Gather feedback', 'Plan enhancements', 'Expand usage']
                    ]
                ]
            ]
        ],
        'ar' => [
            'component_type' => 'journey_timeline',
            'data' => [
                'stages' => [
                    [
                        'title' => 'الوعي',
                        'description' => 'التعرف على تحديات الامتثال والبحث عن الحلول',
                        'touchpoints' => ['فعاليات الصناعة', 'توصيات الأقران', 'البحث عبر الإنترنت', 'التحديثات التنظيمية'],
                        'actions' => ['تحديد نقاط الألم', 'البحث عن البائعين', 'طلب العروض التوضيحية', 'مقارنة الحلول']
                    ],
                    [
                        'title' => 'التقييم',
                        'description' => 'تقييم الملاءمة التقنية والتجارية',
                        'touchpoints' => ['العرض التوضيحي', 'التقييم التقني', 'دراسات الحالة', 'مناقشة التسعير'],
                        'actions' => ['تقييم الميزات', 'التحقق من الامتثال', 'مراجعة التكاملات', 'التفاوض على الشروط']
                    ],
                    [
                        'title' => 'الاختيار',
                        'description' => 'اختيار البائع والتوقيع على العقد',
                        'touchpoints' => ['التفاوض على العقد', 'المراجعة القانونية', 'تدقيق الأمان', 'الموافقة التنفيذية'],
                        'actions' => ['التفاوضات النهائية', 'التوقيع على الاتفاق', 'تخصيص الميزانية', 'التخطيط للتنفيذ']
                    ],
                    [
                        'title' => 'التنفيذ',
                        'description' => 'نشر الحل وتدريب الفرق',
                        'touchpoints' => ['التخطيط للعملية المباشرة', 'هجرة البيانات', 'تدريب المستخدمين', 'دعم العملية المباشرة'],
                        'actions' => ['تكوين النظام', 'تدريب الموظفين', 'اختبار سير العمل', 'إطلاق الإنتاج']
                    ],
                    [
                        'title' => 'التبني',
                        'description' => 'الاستخدام الكامل وإدراك القيمة',
                        'touchpoints' => ['مؤشرات النجاح', 'طلبات الدعم', 'طلبات الميزات', 'مناقشة التجديد'],
                        'actions' => ['مراقبة مؤشرات الأداء الرئيسية', 'جمع الملاحظات', 'التخطيط للتحسينات', 'توسيع الاستخدام']
                    ]
                ]
            ]
        ]
    ],

    'cp_pain_points_analysis' => [
        'en' => [
            'component_type' => 'text_content',
            'data' => [
                'title' => 'Customer Pain Points Analysis',
                'sections' => [
                    [
                        'heading' => 'Operational Inefficiency',
                        'content' => 'Current compliance teams spend 40-60% of their time on manual processes like alert review, data entry, and report generation. This diverts resources from strategic compliance activities and increases operational costs. SALIS automates these processes, freeing teams for higher-value activities.'
                    ],
                    [
                        'heading' => 'Regulatory Risk',
                        'content' => 'Organizations struggle to keep pace with constant regulatory updates from SAMA, CBU, and international bodies. Delayed implementation of new rules creates compliance gaps and exposes institutions to penalties. SALIS provides real-time regulatory updates integrated directly into screening and reporting systems.'
                    ],
                    [
                        'heading' => 'Technology Fragmentation',
                        'content' => 'Most institutions use 3-4 disconnected compliance tools (screening, risk assessment, reporting, audit). This creates data silos, increases integration complexity, and makes it difficult to maintain a consistent compliance view. SALIS consolidates these functions into one integrated platform.'
                    ],
                    [
                        'heading' => 'Language and Localization',
                        'content' => 'Existing solutions are primarily designed for Western markets and poorly adapted for Arabic operations. Compliance teams must use English-language systems while documenting everything in Arabic, creating translation gaps and inconsistencies. SALIS is natively Arabic with right-to-left support throughout.'
                    ],
                    [
                        'heading' => 'Implementation Complexity',
                        'content' => 'Legacy compliance systems take 6-12 months to implement, requiring significant IT resources and business disruption. Modern institutions need faster deployment. SALIS can be deployed and operational in 2-3 weeks with minimal disruption to existing operations.'
                    ]
                ]
            ]
        ],
        'ar' => [
            'component_type' => 'text_content',
            'data' => [
                'title' => 'تحليل نقاط ألم العملاء',
                'sections' => [
                    [
                        'heading' => 'عدم الكفاءة التشغيلية',
                        'content' => 'تقضي فرق الامتثال الحالية 40-60٪ من وقتها على العمليات اليدوية مثل مراجعة التنبيهات وإدخال البيانات وإنشاء التقارير. هذا يحول الموارد عن أنشطة الامتثال الاستراتيجية ويزيد من التكاليف التشغيلية. تقوم SALIS بأتمتة هذه العمليات، وتحرر الفرق للأنشطة ذات القيمة الأعلى.'
                    ],
                    [
                        'heading' => 'المخاطر التنظيمية',
                        'content' => 'تكافح المنظمات لمواكبة التحديثات التنظيمية المستمرة من SAMA و CBU والهيئات الدولية. يؤدي التنفيذ المتأخر للقواعد الجديدة إلى فجوات الامتثال ويعرض المؤسسات للعقوبات. توفر SALIS تحديثات تنظيمية في الوقت الفعلي مدمجة مباشرة في أنظمة الفحص والإبلاغ.'
                    ],
                    [
                        'heading' => 'تجزئة التكنولوجيا',
                        'content' => 'تستخدم معظم المؤسسات 3-4 أدوات امتثال غير متصلة (الفحص، تقييم المخاطر، الإبلاغ، التدقيق). هذا يخلق صوامع البيانات ويزيد من تعقيد التكامل ويجعل من الصعب الحفاظ على عرض امتثال متسق. تدمج SALIS هذه الوظائف في منصة واحدة متكاملة.'
                    ],
                    [
                        'heading' => 'اللغة والتوطين',
                        'content' => 'تم تصميم الحلول الموجودة بشكل أساسي للأسواق الغربية والتكيف السيء للعمليات العربية. يجب على فرق الامتثال استخدام أنظمة باللغة الإنجليزية أثناء توثيق كل شيء باللغة العربية، مما يخلق فجوات الترجمة والتناقضات. SALIS أصلي باللغة العربية مع دعم من اليمين إلى اليسار في جميع أنحاء.'
                    ],
                    [
                        'heading' => 'تعقيد التنفيذ',
                        'content' => 'تستغرق أنظمة الامتثال الموروثة 6-12 شهراً للتنفيذ، وتتطلب موارد تكنولوجيا المعلومات كبيرة والتعطيل التجاري. تحتاج المؤسسات الحديثة إلى نشر أسرع. يمكن نشر SALIS وتشغيلها في غضون 2-3 أسابيع مع الحد الأدنى من التعطيل للعمليات الموجودة.'
                    ]
                ]
            ]
        ]
    ],

    'fin_revenue_model' => [
        'en' => [
            'component_type' => 'pricing_cards',
            'data' => [
                'tiers' => [
                    [
                        'name' => 'Starter',
                        'price' => '$12K/mo',
                        'features' => ['Up to 500K transactions/month', 'Basic screening', 'Monthly reporting', 'Standard support', '1 admin user'],
                        'highlighted' => false,
                        'cta' => 'Start Free Trial'
                    ],
                    [
                        'name' => 'Professional',
                        'price' => '$25K/mo',
                        'features' => ['Up to 2M transactions/month', 'Advanced screening + AI', 'Weekly reporting', 'Priority support', '5 admin users', 'Custom rules'],
                        'highlighted' => true,
                        'cta' => 'Get Started'
                    ],
                    [
                        'name' => 'Enterprise',
                        'price' => 'Custom',
                        'features' => ['Unlimited transactions', 'Full AI suite', 'Real-time reporting', '24/7 support', 'Unlimited users', 'Custom integrations', 'Dedicated account manager'],
                        'highlighted' => false,
                        'cta' => 'Contact Sales'
                    ]
                ]
            ]
        ],
        'ar' => [
            'component_type' => 'pricing_cards',
            'data' => [
                'tiers' => [
                    [
                        'name' => 'البداية',
                        'price' => '$12K/mo',
                        'features' => ['حتى 500K معاملة/شهر', 'الفحص الأساسي', 'التقارير الشهرية', 'الدعم القياسي', 'مستخدم إداري واحد'],
                        'highlighted' => false,
                        'cta' => 'ابدأ الاختبار المجاني'
                    ],
                    [
                        'name' => 'احترافي',
                        'price' => '$25K/mo',
                        'features' => ['حتى 2M معاملة/شهر', 'الفحص المتقدم + AI', 'التقارير الأسبوعية', 'دعم الأولوية', '5 مستخدمين إداريين', 'القواعد المخصصة'],
                        'highlighted' => true,
                        'cta' => 'ابدأ الآن'
                    ],
                    [
                        'name' => 'المشروع',
                        'price' => 'مخصص',
                        'features' => ['معاملات غير محدودة', 'مجموعة AI الكاملة', 'التقارير في الوقت الفعلي', 'دعم 24/7', 'مستخدمون غير محدودون', 'تكاملات مخصصة', 'مدير حساب مخصص'],
                        'highlighted' => false,
                        'cta' => 'اتصل بالمبيعات'
                    ]
                ]
            ]
        ]
    ],

    'fin_cost_structure' => [
        'en' => [
            'component_type' => 'progress_bars',
            'data' => [
                'items' => [
                    ['label' => 'Infrastructure & Cloud', 'value' => 25, 'suffix' => '%'],
                    ['label' => 'R&D & AI Model Training', 'value' => 30, 'suffix' => '%'],
                    ['label' => 'Sales & Marketing', 'value' => 20, 'suffix' => '%'],
                    ['label' => 'Customer Support & Success', 'value' => 15, 'suffix' => '%'],
                    ['label' => 'Operations & Admin', 'value' => 10, 'suffix' => '%']
                ]
            ]
        ],
        'ar' => [
            'component_type' => 'progress_bars',
            'data' => [
                'items' => [
                    ['label' => 'البنية الأساسية والسحابة', 'value' => 25, 'suffix' => '%'],
                    ['label' => 'البحث والتطوير وتدريب نموذج AI', 'value' => 30, 'suffix' => '%'],
                    ['label' => 'المبيعات والتسويق', 'value' => 20, 'suffix' => '%'],
                    ['label' => 'دعم العملاء والنجاح', 'value' => 15, 'suffix' => '%'],
                    ['label' => 'العمليات والإدارة', 'value' => 10, 'suffix' => '%']
                ]
            ]
        ]
    ],

    'fin_financial_projections' => [
        'en' => [
            'component_type' => 'stat_cards',
            'data' => [
                'metrics' => [
                    ['label' => 'Year 1 Revenue', 'value' => '$3.8M', 'description' => 'Initial customer base'],
                    ['label' => 'Year 2 Revenue', 'value' => '$12.5M', 'description' => '230% growth target'],
                    ['label' => 'Year 3 Revenue', 'value' => '$35.8M', 'description' => '186% growth trajectory'],
                    ['label' => 'Gross Margin', 'value' => '72%', 'description' => 'By Year 3']
                ]
            ]
        ],
        'ar' => [
            'component_type' => 'stat_cards',
            'data' => [
                'metrics' => [
                    ['label' => 'إيرادات السنة الأولى', 'value' => '$3.8M', 'description' => 'قاعدة العملاء الأولية'],
                    ['label' => 'إيرادات السنة الثانية', 'value' => '$12.5M', 'description' => 'هدف النمو 230٪'],
                    ['label' => 'إيرادات السنة الثالثة', 'value' => '$35.8M', 'description' => 'مسار النمو 186٪'],
                    ['label' => 'الهامش الإجمالي', 'value' => '72%', 'description' => 'بحلول السنة الثالثة']
                ]
            ]
        ]
    ],

    'fin_funding_requirements' => [
        'en' => [
            'component_type' => 'text_content',
            'data' => [
                'title' => 'Funding Requirements & Use of Funds',
                'sections' => [
                    [
                        'heading' => 'Series A Target',
                        'content' => 'We are seeking $8.5M in Series A funding to accelerate market expansion and enhance product capabilities. This follows our successful seed round that established product-market fit and generated $3.8M in ARR.'
                    ],
                    [
                        'heading' => 'Product Development (35%)',
                        'content' => 'Enhance AI/ML capabilities with advanced anomaly detection, predictive analytics, and behavioral biometric analysis. Expand integration ecosystem with regional banking platforms. Develop mobile applications for iOS and Android. Build advanced reporting and visualization tools.'
                    ],
                    [
                        'heading' => 'Sales & Market Expansion (40%)',
                        'content' => 'Expand sales team to cover Saudi Arabia, UAE, Kuwait, and Qatar. Establish regional offices in key markets. Develop channel partnerships with consulting firms and system integrators. Launch targeted marketing campaigns in GCC financial sector.'
                    ],
                    [
                        'heading' => 'Operations & Compliance (15%)',
                        'content' => 'Strengthen compliance and regulatory expertise with senior advisors. Build robust security and infrastructure teams. Establish ISO 27001 and SOC 2 compliance infrastructure. Create customer success and support operations.'
                    ],
                    [
                        'heading' => 'Working Capital (10%)',
                        'content' => 'Maintain operational flexibility and fund growth initiatives. Support customer onboarding and implementation. Cover recruitment, training, and team expansion costs. Provide buffer for market opportunities and contingencies.'
                    ]
                ]
            ]
        ],
        'ar' => [
            'component_type' => 'text_content',
            'data' => [
                'title' => 'متطلبات التمويل واستخدام الأموال',
                'sections' => [
                    [
                        'heading' => 'هدف Series A',
                        'content' => 'نحن نسعى إلى الحصول على تمويل بقيمة 8.5 مليون دولار في Series A لتسريع التوسع في السوق وتحسين قدرات المنتج. يأتي هذا بعد جولة البذور الناجحة التي أنشأت توافق المنتج مع السوق وأنتجت 3.8 مليون دولار في ARR.'
                    ],
                    [
                        'heading' => 'تطوير المنتج (35٪)',
                        'content' => 'تحسين قدرات AI/ML مع الكشف المتقدم عن الحالات الشاذة والتحليلات التنبؤية وتحليل البيومترية السلوكية. توسيع نظام البيئة للتكامل مع منصات البنوك الإقليمية. تطوير تطبيقات الهاتف المحمول لـ iOS و Android. بناء أدوات الإبلاغ والتصور المتقدمة.'
                    ],
                    [
                        'heading' => 'المبيعات وتوسيع السوق (40٪)',
                        'content' => 'توسيع فريق المبيعات لتغطية المملكة العربية السعودية والإمارات والكويت وقطر. إنشاء مكاتب إقليمية في الأسواق الرئيسية. تطوير شراكات قنوية مع شركات الاستشارات والمدمجين. إطلاق حملات تسويقية موجهة في القطاع المالي بمجلس التعاون الخليجي.'
                    ],
                    [
                        'heading' => 'العمليات والامتثال (15٪)',
                        'content' => 'تعزيز الخبرة في الامتثال والتنظيم مع المستشارين الكبار. بناء فرق الأمان والبنية الأساسية القوية. إنشاء بنية الامتثال ISO 27001 و SOC 2. إنشاء عمليات نجاح العملاء والدعم.'
                    ],
                    [
                        'heading' => 'رأس المال العامل (10٪)',
                        'content' => 'الحفاظ على المرونة التشغيلية وتمويل مبادرات النمو. دعم إعداد العملاء والتنفيذ. تغطية تكاليف التوظيف والتدريب وتوسيع الفريق. توفير مخزن مؤقت لفرص السوق والطوارئ.'
                    ]
                ]
            ]
        ]
    ],

    'fin_unit_economics' => [
        'en' => [
            'component_type' => 'key_value',
            'data' => [
                'items' => [
                    ['key' => 'Average Contract Value', 'value' => '$240K/year'],
                    ['key' => 'Customer Acquisition Cost', 'value' => '$32K'],
                    ['key' => 'Payback Period', 'value' => '1.6 months'],
                    ['key' => 'Customer Lifetime Value', 'value' => '$1.8M (5-year average)'],
                    ['key' => 'LTV:CAC Ratio', 'value' => '56:1'],
                    ['key' => 'Net Revenue Retention', 'value' => '132%'],
                    ['key' => 'Gross Margin', 'value' => '68%'],
                    ['key' => 'Magic Number', 'value' => '0.68']
                ]
            ]
        ],
        'ar' => [
            'component_type' => 'key_value',
            'data' => [
                'items' => [
                    ['key' => 'قيمة العقد الوسيطة', 'value' => '$240K/year'],
                    ['key' => 'تكلفة اكتساب العملاء', 'value' => '$32K'],
                    ['key' => 'فترة الاسترداد', 'value' => '1.6 شهر'],
                    ['key' => 'قيمة العمر الافتراضي للعميل', 'value' => '$1.8M (متوسط 5 سنوات)'],
                    ['key' => 'نسبة LTV:CAC', 'value' => '56:1'],
                    ['key' => 'الاحتفاظ بالإيرادات الصافية', 'value' => '132%'],
                    ['key' => 'الهامش الإجمالي', 'value' => '68%'],
                    ['key' => 'الرقم السحري', 'value' => '0.68']
                ]
            ]
        ]
    ],

    'gtm_launch_strategy' => [
        'en' => [
            'component_type' => 'text_content',
            'data' => [
                'title' => 'Go-to-Market Launch Strategy',
                'sections' => [
                    [
                        'heading' => 'Phase 1: Anchor Customer Strategy (Months 1-3)',
                        'content' => 'Focus on landing 2-3 anchor customers from Tier-1 banks in Saudi Arabia and UAE. Provide white-glove implementation and support. Generate case studies and testimonials. Achieve high customer satisfaction and NPS scores above 70.'
                    ],
                    [
                        'heading' => 'Phase 2: Regional Expansion (Months 4-9)',
                        'content' => 'Expand into Kuwait and Qatar markets. Build sales team across GCC region. Establish partnerships with system integrators and consultants. Target both large banks and mid-size financial institutions. Launch targeted LinkedIn and industry conference campaigns.'
                    ],
                    [
                        'heading' => 'Phase 3: Market Penetration (Months 10-18)',
                        'content' => 'Penetrate secondary markets including Bahrain and Oman. Develop channel partnership program with resellers. Launch thought leadership content and regulatory compliance guides. Achieve 15-20 customer logos across GCC.'
                    ],
                    [
                        'heading' => 'Phase 4: Scale & Optimization (Months 19+)',
                        'content' => 'Build brand awareness through industry awards and analyst recognition. Develop product-led growth with self-serve capabilities. Expand to adjacent markets in MENA region. Plan for 40+ customer target by end of Year 2.'
                    ]
                ]
            ]
        ],
        'ar' => [
            'component_type' => 'text_content',
            'data' => [
                'title' => 'استراتيجية الإطلاق في السوق',
                'sections' => [
                    [
                        'heading' => 'المرحلة 1: استراتيجية العميل الأساسي (الأشهر 1-3)',
                        'content' => 'التركيز على الهبوط على 2-3 عملاء أساسيين من البنوك من الدرجة الأولى في المملكة العربية السعودية والإمارات. توفير التنفيذ والدعم بالخدمة الكاملة. إنشاء دراسات الحالات والشهادات. تحقيق رضا العملاء العالي ودرجات NPS فوق 70.'
                    ],
                    [
                        'heading' => 'المرحلة 2: التوسع الإقليمي (الأشهر 4-9)',
                        'content' => 'التوسع في أسواق الكويت وقطر. بناء فريق المبيعات عبر منطقة مجلس التعاون الخليجي. إنشاء شراكات مع المدمجين والمستشارين. استهداف البنوك الكبيرة والمؤسسات المالية متوسطة الحجم. إطلاق حملات LinkedIn والمؤتمرات الصناعية الموجهة.'
                    ],
                    [
                        'heading' => 'المرحلة 3: اختراق السوق (الأشهر 10-18)',
                        'content' => 'اختراق الأسواق الثانوية بما في ذلك البحرين وعمان. تطوير برنامج شراكة القناة مع الموزعين. إطلاق محتوى القيادة الفكرية وأدلة الامتثال التنظيمي. تحقيق 15-20 شعار عميل عبر مجلس التعاون الخليجي.'
                    ],
                    [
                        'heading' => 'المرحلة 4: التوسع والتحسين (الأشهر 19+)',
                        'content' => 'بناء الوعي بالعلامة التجارية من خلال جوائز الصناعة والاعتراف بالمحلل. تطوير النمو الموجه بالمنتج مع قدرات الخدمة الذاتية. التوسع للأسواق المجاورة في منطقة الشرق الأوسط وشمال أفريقيا. التخطيط لـ 40+ هدف عميل بحلول نهاية السنة الثانية.'
                    ]
                ]
            ]
        ]
    ],

    'gtm_marketing_channels' => [
        'en' => [
            'component_type' => 'stat_cards',
            'data' => [
                'metrics' => [
                    ['label' => 'LinkedIn B2B', 'value' => '35%', 'description' => 'Primary channel for decision makers'],
                    ['label' => 'Industry Events', 'value' => '25%', 'description' => 'Conferences and trade shows'],
                    ['label' => 'Direct Sales', 'value' => '20%', 'description' => 'Enterprise relationship building'],
                    ['label' => 'Content Marketing', 'value' => '15%', 'description' => 'Compliance guides and whitepapers'],
                    ['label' => 'Referrals & Partnerships', 'value' => '5%', 'description' => 'Channel partners and integrators']
                ]
            ]
        ],
        'ar' => [
            'component_type' => 'stat_cards',
            'data' => [
                'metrics' => [
                    ['label' => 'LinkedIn B2B', 'value' => '35%', 'description' => 'القناة الأساسية لصانعي القرار'],
                    ['label' => 'فعاليات الصناعة', 'value' => '25%', 'description' => 'المؤتمرات والمعارض التجارية'],
                    ['label' => 'المبيعات المباشرة', 'value' => '20%', 'description' => 'بناء العلاقات المؤسسية'],
                    ['label' => 'تسويق المحتوى', 'value' => '15%', 'description' => 'أدلة الامتثال والكتب البيضاء'],
                    ['label' => 'الإحالات والشراكات', 'value' => '5%', 'description' => 'شركاء القنوات والمدمجون']
                ]
            ]
        ]
    ],

    'gtm_sales_funnel' => [
        'en' => [
            'component_type' => 'journey_timeline',
            'data' => [
                'stages' => [
                    [
                        'title' => 'Awareness',
                        'description' => 'Build brand awareness through content and events',
                        'touchpoints' => ['LinkedIn posts', 'Compliance guides', 'Industry events', 'Webinars'],
                        'actions' => ['Create content', 'Host events', 'Build brand', 'Generate leads']
                    ],
                    [
                        'title' => 'Interest & Consideration',
                        'description' => 'Engage prospects with product information',
                        'touchpoints' => ['Product demo', 'Case studies', 'Pricing page', 'ROI calculator'],
                        'actions' => ['Schedule demos', 'Share materials', 'Build pipeline', 'Qualify leads']
                    ],
                    [
                        'title' => 'Evaluation',
                        'description' => 'Work with procurement and technical teams',
                        'touchpoints' => ['Technical assessment', 'Security audit', 'Pricing negotiation', 'Reference calls'],
                        'actions' => ['Run assessments', 'Negotiate terms', 'Proof of concept', 'Get approvals']
                    ],
                    [
                        'title' => 'Decision',
                        'description' => 'Close deal and sign contract',
                        'touchpoints' => ['Final negotiations', 'Contract review', 'Executive sign-off', 'Payment processing'],
                        'actions' => ['Finalize terms', 'Legal review', 'Secure funding', 'Execute agreement']
                    ],
                    [
                        'title' => 'Onboarding',
                        'description' => 'Implement solution and ensure success',
                        'touchpoints' => ['Implementation plan', 'Data migration', 'User training', 'Go-live support'],
                        'actions' => ['Deploy system', 'Train users', 'Enable features', 'Achieve ROI']
                    ]
                ]
            ]
        ],
        'ar' => [
            'component_type' => 'journey_timeline',
            'data' => [
                'stages' => [
                    [
                        'title' => 'الوعي',
                        'description' => 'بناء الوعي بالعلامة التجارية من خلال المحتوى والأحداث',
                        'touchpoints' => ['منشورات LinkedIn', 'أدلة الامتثال', 'فعاليات الصناعة', 'الندوات عبر الويب'],
                        'actions' => ['إنشاء محتوى', 'استضافة الأحداث', 'بناء العلامة التجارية', 'إنشاء الفرص']
                    ],
                    [
                        'title' => 'الاهتمام والاعتبار',
                        'description' => 'التعامل مع الآفاق بمعلومات المنتج',
                        'touchpoints' => ['العرض التوضيحي', 'دراسات الحالات', 'صفحة التسعير', 'حاسبة العائد على الاستثمار'],
                        'actions' => ['جدولة العروض التوضيحية', 'مشاركة المواد', 'بناء خط الأنابيب', 'تأهيل الفرص']
                    ],
                    [
                        'title' => 'التقييم',
                        'description' => 'العمل مع فرق الشراء والفنية',
                        'touchpoints' => ['التقييم الفني', 'تدقيق الأمان', 'التفاوض على السعر', 'استدعاءات الإحالة'],
                        'actions' => ['تشغيل التقييمات', 'التفاوض على الشروط', 'إثبات المفهوم', 'الحصول على الموافقات']
                    ],
                    [
                        'title' => 'الاختيار',
                        'description' => 'إغلاق الصفقة والتوقيع على العقد',
                        'touchpoints' => ['المفاوضات النهائية', 'مراجعة العقد', 'الموافقة التنفيذية', 'معالجة الدفع'],
                        'actions' => ['إنهاء الشروط', 'المراجعة القانونية', 'تأمين التمويل', 'تنفيذ الاتفاق']
                    ],
                    [
                        'title' => 'الإعداد',
                        'description' => 'تنفيذ الحل وضمان النجاح',
                        'touchpoints' => ['خطة التنفيذ', 'هجرة البيانات', 'تدريب المستخدمين', 'دعم العملية المباشرة'],
                        'actions' => ['نشر النظام', 'تدريب المستخدمين', 'تفعيل الميزات', 'تحقيق العائد على الاستثمار']
                    ]
                ]
            ]
        ]
    ],

    'gtm_partnerships' => [
        'en' => [
            'component_type' => 'text_content',
            'data' => [
                'title' => 'Partnership & Distribution Strategy',
                'sections' => [
                    [
                        'heading' => 'System Integrator Partners',
                        'content' => 'Partner with leading SI firms like ATOS, IBM regional units, and local Saudi/UAE integration companies. Enable them to resell SALIS as part of comprehensive compliance solutions. Provide 20-25% reseller margins and co-marketing support.'
                    ],
                    [
                        'heading' => 'Consulting & Advisory Partnerships',
                        'content' => 'Establish relationships with Big 4 advisory firms (Deloitte, EY, KPMG, PwC) and local compliance consultants. Position SALIS as the technology backbone for compliance implementations. Generate joint marketing and lead-sharing agreements.'
                    ],
                    [
                        'heading' => 'Banking Technology Partners',
                        'content' => 'Integrate with core banking platforms (Temenos, Backbase, SAP Fintech) used across GCC. Ensure seamless data flow and reporting integration. Support as white-label option for banking software vendors.'
                    ],
                    [
                        'heading' => 'Regulatory Relationships',
                        'content' => 'Build relationships with SAMA, CBU, and local regulatory bodies. Participate in compliance working groups. Demonstrate alignment with regulatory standards and best practices. Potential for official endorsement or recognition.'
                    ]
                ]
            ]
        ],
        'ar' => [
            'component_type' => 'text_content',
            'data' => [
                'title' => 'استراتيجية الشراكة والتوزيع',
                'sections' => [
                    [
                        'heading' => 'شركاء مدمج النظام',
                        'content' => 'الشراكة مع شركات SI الرائدة مثل ATOS وفروع IBM الإقليمية وشركات التكامل السعودية والإماراتية المحلية. تمكينهم من إعادة بيع SALIS كجزء من حلول الامتثال الشاملة. توفير هوامش موزع 20-25٪ والدعم المشترك للتسويق.'
                    ],
                    [
                        'heading' => 'شراكات الاستشارات والمشورة',
                        'content' => 'إنشاء علاقات مع شركات Big 4 للمشورة (Deloitte، EY، KPMG، PwC) والمستشارين الامتثال المحليين. وضع SALIS كالعمود الفقري التكنولوجي لتنفيذ الامتثال. توليد اتفاقيات التسويق المشترك ومشاركة الفرص.'
                    ],
                    [
                        'heading' => 'شركاء تكنولوجيا البنوك',
                        'content' => 'التكامل مع منصات الخدمات المصرفية الأساسية (Temenos، Backbase، SAP Fintech) المستخدمة عبر مجلس التعاون الخليجي. ضمان تدفق البيانات والتكامل السلس في الإبلاغ. الدعم كخيار تسمية بيضاء لبائعي البرامج المصرفية.'
                    ],
                    [
                        'heading' => 'العلاقات التنظيمية',
                        'content' => 'بناء علاقات مع SAMA و CBU والهيئات التنظيمية المحلية. المشاركة في مجموعات عمل الامتثال. إظهار التوافق مع معايير ومعايير أفضل الممارسات التنظيمية. احتمال الموافقة أو الاعتراف الرسمي.'
                    ]
                ]
            ]
        ]
    ],

    'gtm_growth_metrics' => [
        'en' => [
            'component_type' => 'progress_bars',
            'data' => [
                'items' => [
                    ['label' => 'Customer Acquisition (Monthly)', 'value' => 15, 'suffix' => 'customers'],
                    ['label' => 'Revenue Growth (YoY)', 'value' => 230, 'suffix' => '%'],
                    ['label' => 'NPS Score Target', 'value' => 72, 'suffix' => 'points'],
                    ['label' => 'Churn Rate (Target)', 'value' => 5, 'suffix' => '%'],
                    ['label' => 'Market Penetration', 'value' => 8, 'suffix' => '% (Year 2)']
                ]
            ]
        ],
        'ar' => [
            'component_type' => 'progress_bars',
            'data' => [
                'items' => [
                    ['label' => 'اكتساب العملاء (شهري)', 'value' => 15, 'suffix' => 'عملاء'],
                    ['label' => 'نمو الإيرادات (سنويًا)', 'value' => 230, 'suffix' => '%'],
                    ['label' => 'هدف نقاط NPS', 'value' => 72, 'suffix' => 'نقطة'],
                    ['label' => 'معدل الفقد (الهدف)', 'value' => 5, 'suffix' => '%'],
                    ['label' => 'اختراق السوق', 'value' => 8, 'suffix' => '% (السنة 2)']
                ]
            ]
        ]
    ],

    'ca_competitor_overview' => [
        'en' => [
            'component_type' => 'text_content',
            'data' => [
                'title' => 'Competitive Landscape Overview',
                'sections' => [
                    [
                        'heading' => 'Legacy Competitors',
                        'content' => 'Established players like Thomson Reuters CLEAR, Lexis-Nexis, and Refinitiv dominate the global market. However, these solutions are expensive ($50K+/month), require 8-12 month implementations, and lack Arabic language support. They treat GCC as a secondary market, not a strategic focus.'
                    ],
                    [
                        'heading' => 'Global Cloud Solutions',
                        'content' => 'Newer entrants like Feedzai, SAS AML, and IBM Cloud offerings provide cloud-based compliance. However, they are generalized for global markets and often require significant customization for GCC regulations. Implementation costs remain high, and local support is limited.'
                    ],
                    [
                        'heading' => 'Regional Players',
                        'content' => 'A few regional solutions exist but with limited capabilities. Most focus on transaction monitoring only. They lack advanced AI, comprehensive regulatory reporting, and integration capabilities. Many struggle with scalability and uptime reliability.'
                    ],
                    [
                        'heading' => 'SALIS Competitive Position',
                        'content' => 'We are the first and only purpose-built, cloud-native AML compliance platform designed specifically for GCC markets. Our competitive advantages include: 70% lower cost, 4x faster implementation, native Arabic support, 94% AI accuracy, and deep regulatory expertise.'
                    ]
                ]
            ]
        ],
        'ar' => [
            'component_type' => 'text_content',
            'data' => [
                'title' => 'نظرة عامة على المشهد التنافسي',
                'sections' => [
                    [
                        'heading' => 'المنافسون الموروثون',
                        'content' => 'يهيمن اللاعبون الراسخون مثل Thomson Reuters CLEAR و Lexis-Nexis و Refinitiv على السوق العالمية. ومع ذلك، فإن هذه الحلول مكلفة (أكثر من 50K دولار/شهر)، وتتطلب تنفيذ 8-12 شهر، وتفتقر إلى دعم اللغة العربية. إنهم يتعاملون مع مجلس التعاون الخليجي كسوق ثانوية، وليس تركيز استراتيجي.'
                    ],
                    [
                        'heading' => 'حلول السحابة العالمية',
                        'content' => 'توفر الداخلون الأحدث مثل Feedzai و SAS AML و IBM Cloud حلول امتثال قائمة على السحابة. ومع ذلك، فهي عامة للأسواق العالمية وغالباً ما تتطلب تخصيصاً كبيراً للوائح مجلس التعاون الخليجي. تظل تكاليف التنفيذ مرتفعة، والدعم المحلي محدود.'
                    ],
                    [
                        'heading' => 'لاعبون إقليميون',
                        'content' => 'يوجد عدد قليل من الحلول الإقليمية ولكن بقدرات محدودة. يركز معظمها على مراقبة المعاملات فقط. إنهم يفتقرون إلى الذكاء الاصطناعي المتقدم والإبلاغ التنظيمي الشامل وقدرات التكامل. يكافح الكثير منهم مع قابلية التوسع وموثوقية وقت التشغيل.'
                    ],
                    [
                        'heading' => 'موضع SALIS التنافسي',
                        'content' => 'نحن أول وأوحد منصة امتثال AML محلية الصنع، قائمة على السحابة، مصممة خصيصاً لأسواق مجلس التعاون الخليجي. تشمل مزايانا التنافسية: تكلفة أقل بـ 70٪، تنفيذ أسرع 4 مرات، دعم عربي أصلي، دقة الذكاء الاصطناعي 94٪، وخبرة تنظيمية عميقة.'
                    ]
                ]
            ]
        ]
    ],

    'ca_feature_comparison' => [
        'en' => [
            'component_type' => 'comparison_table',
            'data' => [
                'headers' => ['Feature', 'SALIS', 'Thomson Reuters', 'Feedzai', 'Regional Competitors'],
                'rows' => [
                    ['Transaction Screening', '✓ Advanced', '✓ Basic', '✓ Advanced', '✓ Basic'],
                    ['Risk Scoring', '✓ AI-powered', '✓ Rules-based', '✓ ML-based', '✗ Manual'],
                    ['Arabic Support', '✓ Native RTL', '✗ Translation only', '✗ Limited', '✓ Partial'],
                    ['SAMA Compliance', '✓ Purpose-built', '✓ Generic', '✓ Configurable', '✓ Basic'],
                    ['Real-time Alerts', '✓ Yes', '✓ Batch', '✓ Yes', '✗ Delayed'],
                    ['Implementation Time', '2-3 weeks', '8-12 months', '4-6 months', '6-8 weeks'],
                    ['Monthly Cost', '$15K', '$50K+', '$35K+', '$18K+'],
                    ['Mobile App', '✓ iOS/Android', '✗ No', '✓ Yes', '✗ No']
                ]
            ]
        ],
        'ar' => [
            'component_type' => 'comparison_table',
            'data' => [
                'headers' => ['الميزة', 'SALIS', 'Thomson Reuters', 'Feedzai', 'المنافسون الإقليميون'],
                'rows' => [
                    ['فحص المعاملات', '✓ متقدم', '✓ أساسي', '✓ متقدم', '✓ أساسي'],
                    ['تسجيل المخاطر', '✓ مدعوم بـ AI', '✓ قائم على القواعس', '✓ قائم على ML', '✗ يدويًا'],
                    ['دعم اللغة العربية', '✓ RTL أصلي', '✗ ترجمة فقط', '✗ محدود', '✓ جزئي'],
                    ['امتثال SAMA', '✓ مبني لغرض معين', '✓ عام', '✓ قابل للتكوين', '✓ أساسي'],
                    ['التنبيهات في الوقت الفعلي', '✓ نعم', '✓ دفعة', '✓ نعم', '✗ متأخر'],
                    ['وقت التنفيذ', '2-3 أسابيع', '8-12 شهر', '4-6 أشهر', '6-8 أسابيع'],
                    ['التكلفة الشهرية', '$15K', '$50K+', '$35K+', '$18K+'],
                    ['تطبيق الهاتف المحمول', '✓ iOS/Android', '✗ لا', '✓ نعم', '✗ لا']
                ]
            ]
        ]
    ],

    'ca_market_positioning' => [
        'en' => [
            'component_type' => 'key_value',
            'data' => [
                'items' => [
                    ['key' => 'Market Positioning', 'value' => 'The GCC AML Compliance Leader'],
                    ['key' => 'Primary Target', 'value' => 'Banks and financial institutions in Saudi Arabia, UAE, Kuwait, Qatar'],
                    ['key' => 'Core Value Proposition', 'value' => 'Fast, affordable, native AML compliance for GCC'],
                    ['key' => 'Key Differentiator', 'value' => 'Purpose-built for GCC regulations with native Arabic support'],
                    ['key' => 'Competitive Price', 'value' => '70% lower than legacy solutions'],
                    ['key' => 'Implementation Speed', 'value' => '4x faster than industry average'],
                    ['key' => 'Market Opportunity', 'value' => '$2.1B regional AML compliance market'],
                    ['key' => 'Addressable Market (Year 3)', 'value' => '25+ tier-1 and tier-2 banks']
                ]
            ]
        ],
        'ar' => [
            'component_type' => 'key_value',
            'data' => [
                'items' => [
                    ['key' => 'موضع السوق', 'value' => 'قائد امتثال AML في مجلس التعاون الخليجي'],
                    ['key' => 'الهدف الأساسي', 'value' => 'البنوك والمؤسسات المالية في السعودية والإمارات والكويت وقطر'],
                    ['key' => 'عرض القيمة الأساسي', 'value' => 'امتثال AML سريع وميسور ومحلي الصنع للخليج'],
                    ['key' => 'المميز الرئيسي', 'value' => 'مبني لغرض معين للوائح مجلس التعاون الخليجي مع دعم عربي أصلي'],
                    ['key' => 'السعر التنافسي', 'value' => 'أقل بـ 70٪ من الحلول الموروثة'],
                    ['key' => 'سرعة التنفيذ', 'value' => 'أسرع 4 مرات من متوسط الصناعة'],
                    ['key' => 'فرصة السوق', 'value' => 'سوق امتثال AML الإقليمي 2.1 مليار دولار'],
                    ['key' => 'السوق القابل للخدمة (السنة 3)', 'value' => '25+ بنك من الدرجة الأولى والثانية']
                ]
            ]
        ]
    ],

    'ca_competitive_moat' => [
        'en' => [
            'component_type' => 'text_content',
            'data' => [
                'title' => 'Sustainable Competitive Moat',
                'sections' => [
                    [
                        'heading' => 'Regulatory Expertise & Data',
                        'content' => 'SALIS has built deep relationships with SAMA, CBU, and other GCC regulators. We maintain proprietary databases of compliance rules, regulatory updates, and historical enforcement patterns across the region. This expertise is difficult for competitors to replicate and creates significant switching costs.'
                    ],
                    [
                        'heading' => 'Proprietary AI Models',
                        'content' => 'Our machine learning models are trained on regional transaction data and behavioral patterns unique to GCC financial markets. The models achieve 94% accuracy on regional data but would require significant retraining for other markets. This localized AI advantage is our strongest moat.'
                    ],
                    [
                        'heading' => 'Network Effects',
                        'content' => 'As we add more customers, our AI models improve through aggregate transaction data. Customer success stories and regulatory endorsements create a flywheel effect. Early market leadership in GCC creates natural network benefits for integration partnerships and data insights.'
                    ],
                    [
                        'heading' => 'Brand & Trust',
                        'content' => 'Being the first native GCC AML platform gives us strong positioning. Early customer wins from tier-1 banks establish credibility and trust. Compliance officers are risk-averse and prefer proven, trusted solutions. Once established, brand switching costs are very high.'
                    ],
                    [
                        'heading' => 'Integration Ecosystem',
                        'content' => 'Deep integrations with core banking platforms, regional payment systems, and compliance tools create network effects. Once customers integrate SALIS with their existing systems, switching costs increase significantly. This ecosystem becomes more valuable with each new partnership.'
                    ]
                ]
            ]
        ],
        'ar' => [
            'component_type' => 'text_content',
            'data' => [
                'title' => 'خندق تنافسي مستدام',
                'sections' => [
                    [
                        'heading' => 'خبرة تنظيمية وبيانات',
                        'content' => 'بنت SALIS علاقات عميقة مع SAMA و CBU والمنظمين الآخرين في مجلس التعاون الخليجي. نحتفظ بقواعس بيانات ملكية لقواعس الامتثال والتحديثات التنظيمية وأنماط الإنفاذ التاريخية عبر المنطقة. هذه الخبرة يصعب على المنافسين تكرارها وتخلق تكاليف تبديل كبيرة.'
                    ],
                    [
                        'heading' => 'نماذج الذكاء الاصطناعي الملكية',
                        'content' => 'تم تدريب نماذج التعلم الآلي لدينا على بيانات المعاملات الإقليمية والأنماط السلوكية الفريدة لأسواق مجلس التعاون الخليجي المالية. تحقق النماذج دقة 94٪ على البيانات الإقليمية لكن ستتطلب إعادة تدريب كبيرة للأسواق الأخرى. تعتبر هذه ميزة الذكاء الاصطناعي المحلية أقوى خندق لدينا.'
                    ],
                    [
                        'heading' => 'تأثيرات الشبكة',
                        'content' => 'مع إضافتنا للمزيد من العملاء، تتحسن نماذج الذكاء الاصطناعي لدينا من خلال بيانات المعاملات الكلية. قصص نجاح العملاء والموافقات التنظيمية تخلق تأثير عجلة. تحقق القيادة المبكرة في السوق في مجلس التعاون الخليجي فوائد شبكة طبيعية لشراكات التكامل والرؤى المتعلقة بالبيانات.'
                    ],
                    [
                        'heading' => 'العلامة التجارية والثقة',
                        'content' => 'كوننا أول منصة AML محلية الصنع في مجلس التعاون الخليجي يعطينا موضعاً قوياً. الفوز بالعملاء في وقت مبكر من بنوك من الدرجة الأولى يؤسس للمصداقية والثقة. مسؤولو الامتثال كرهاويون من المخاطر ويفضلون الحلول المثبوتة والموثوقة. بمجرد التأسيس، تكون تكاليف تبديل الماركة عالية جداً.'
                    ],
                    [
                        'heading' => 'نظام البيئة للتكامل',
                        'content' => 'تكاملات عميقة مع منصات الخدمات المصرفية الأساسية وأنظمة الدفع الإقليمية وأدوات الامتثال تخلق تأثيرات الشبكة. بمجرد دمج العملاء SALIS مع أنظمتهم الموجودة، تزداد تكاليف التبديل بشكل كبير. هذا النظام البيئي يصبح أكثر قيمة مع كل شراكة جديدة.'
                    ]
                ]
            ]
        ]
    ]
], 'competitors' => [['name' => 'Verifi Global', 'website' => 'verifiglobal.com', 'description' => 'International KYB with 50+ countries. High latency (200-400ms), expensive pricing.', 'description_ar' => 'KYB دولية مع 50+ دول. كمون عالي وتسعير مرتفع.', 'strengths' => ['Global scale', 'Multiple registries', 'Established'], 'weaknesses' => ['High latency', 'Expensive', 'Poor MENA coverage']], ['name' => 'LocalRegistry API', 'website' => 'localregistryapi.sa', 'description' => 'Domestic competitor with CR verification only. No monitoring or advanced features.', 'description_ar' => 'منافس محلي مع CR فقط. بدون مراقبة أو ميزات متقدمة.', 'strengths' => ['Low price', 'Simple', 'Local support'], 'weaknesses' => ['Limited features', 'No monitoring', 'Poor uptime']], ['name' => 'CloudCompliance', 'website' => 'cloudcompliance.me', 'description' => 'Manual service with 24-48hr turnaround. High cost, not scalable.', 'description_ar' => 'خدمة يدوية بـ 24-48 ساعة. تكلفة عالية وغير قابلة للتوسع.', 'strengths' => ['Expert review', 'Customizable', 'Relationship-based'], 'weaknesses' => ['Slow', 'Expensive', 'Not scalable']]], 'prompts' => [['section_key' => 'dashboard_executive_summary', 'template' => 'Highlight regulatory compliance opportunity in MENA. Emphasize API-first architecture and real-time speed.', 'template_ar' => 'أبرز فرصة الامتثال التنظيمي في الشرق الأوسط وشمال أفريقيا. ركز على سرعة التحقق الفوري.'], ['section_key' => 'mvp_feature_priority', 'template' => 'Prioritize real-time CR API integration, encryption, monitoring as P0. Compliance reporting as P1.', 'template_ar' => 'أولويات التكامل الفوري والتشفير والمراقبة كـ P0. الإبلاغ عن الامتثال كـ P1.']]];
    }

    private function getConnectAIData(): array
    {
        return ['name' => 'Connect AI', 'name_ar' => 'كونكت أي آي', 'description' => 'AI-powered recruitment platform with intelligent candidate matching and video assessment technology', 'description_ar' => 'منصة توظيف محسّنة بالذكاء الاصطناعي مع مطابقة ذكية وتقييم فيديو', 'industry' => 'HR Tech', 'industry_ar' => 'تكنولوجيا الموارد البشرية', 'viability_score' => 84, 'sections' => ['dashboard_viability_score' => ['en' => ['overall' => 84, 'dimensions' => [['label' => 'Market Opportunity', 'score' => 88], ['label' => 'Product-Market Fit', 'score' => 82], ['label' => 'Business Model', 'score' => 85], ['label' => 'Team & Execution', 'score' => 81], ['label' => 'Financial Viability', 'score' => 83]]], 'ar' => ['overall' => 84, 'dimensions' => [['label' => 'فرصة السوق', 'score' => 88], ['label' => 'توافق المنتج', 'score' => 82], ['label' => 'نموذج الأعمال', 'score' => 85], ['label' => 'الفريق والتنفيذ', 'score' => 81], ['label' => 'الجدوى المالية', 'score' => 83]]]], 'dashboard_executive_summary' => ['en' => ['title' => 'Executive Summary', 'summary' => 'AI-powered recruitment platform attracting, screening, and hiring top talent efficiently. 20M+ profiles, AI job descriptions, intelligent matching, video assessments reduce hiring time 60%.', 'sections' => [['heading' => 'Market Opportunity', 'content' => 'HR tech market $4.2B at 28% CAGR. MENA recruits 3.5M+ annually. Hiring costs $4.5K-$8K per hire with 40-60 day cycle. Quality, diversity, speed challenges persist.'], ['heading' => 'Solution', 'content' => 'End-to-end recruitment: AI job descriptions, 20M+ candidate matching, 94% resume accuracy screening, video interview assessments, diversity analytics, hiring dashboard.'], ['heading' => 'Traction', 'content' => '35+ companies (tech startups, government, agencies). 12K+ successful placements. $1.8M ARR at 35% YoY growth. 85% satisfaction.'], ['heading' => 'Business Model', 'content' => 'Freemium (job posting free) + Premium subscription ($299-$999/mo). Per-hire commission (15-20% first-year salary). Enterprise licenses ($15K-$50K/year).']]],  'ar' => ['title' => 'الملخص التنفيذي', 'summary' => 'منصة توظيف محسّنة بالذكاء الاصطناعي لجذب واختبار وتوظيف المواهب بكفاءة', 'sections' => [['heading' => 'فرصة السوق', 'content' => 'سوق HR tech بقيمة 4.2 مليار دولار بنمو 28% سنويًا. الشرق الأوسط وشمال أفريقيا توظيف 3.5M+ سنويًا. تكاليف $4.5K-$8K لكل توظيف.'], ['heading' => 'الحل', 'content' => 'توظيف شامل من النهاية إلى النهاية: أوصاف وظائف بالذكاء الاصطناعي، 20M+ ملف شخصي، فحص 94%، تقييم فيديو.'], ['heading' => 'الجذب', 'content' => '35+ شركة (ناشئة، حكومة، وكالات). 12K+ توظيف ناجح. $1.8M ARR بنمو 35% سنويًا.'], ['heading' => 'نموذج الأعمال', 'content' => 'Freemium + اشتراك مميز ($299-$999/شهر). عمولة لكل توظيف (15-20%). تراخيص مؤسسية.']]],  'dashboard_key_metrics' => ['en' => [['label' => 'Current ARR', 'value' => '$1.8M', 'description' => 'Subscription and placement fees', 'trend' => '+35% YoY'], ['label' => 'Successful Placements', 'value' => '12K+', 'description' => 'Job positions filled', 'trend' => '+45% YoY'], ['label' => 'Active Customers', 'value' => '35+', 'description' => 'Tech, government, recruitment', 'trend' => '+12 YoY'], ['label' => 'Candidate Database', 'value' => '20M+', 'description' => 'MENA talent profiles', 'trend' => '+1M monthly']], 'ar' => [['label' => 'الإيراد السنوي', 'value' => '1.8 مليون دولار', 'description' => 'من الاشتراكات والعمولات', 'trend' => '+35% سنويًا'], ['label' => 'التوظيف الناجح', 'value' => '12 ألف+', 'description' => 'المناصب التي تم ملؤها', 'trend' => '+45% سنويًا'], ['label' => 'العملاء النشطون', 'value' => '35+', 'description' => 'التكنولوجيا والحكومة والوكالات', 'trend' => '+12 سنويًا'], ['label' => 'قاعدة بيانات المرشحين', 'value' => '20 مليون+', 'description' => 'ملفات الموارد البشرية', 'trend' => '+1 مليون شهري']],

    'mvp_feature_priority' => [
        'en' => [
            'comparison_table' => [
                'headers' => ['Feature', 'Priority', 'Timeline', 'Impact', 'Status'],
                'rows' => [
                    ['AI-Powered Resume Screening', 'Critical', 'Month 1-2', 'High', 'In Development'],
                    ['Candidate Ranking Engine', 'Critical', 'Month 2-3', 'High', 'In Development'],
                    ['Interview Scheduling Automation', 'High', 'Month 3-4', 'Medium', 'Planned'],
                    ['Performance Analytics Dashboard', 'High', 'Month 4-5', 'High', 'Planned'],
                    ['Employee Skills Assessment', 'Medium', 'Month 5-6', 'Medium', 'Planned'],
                    ['Candidate Communication Portal', 'Medium', 'Month 3-4', 'Medium', 'Planned'],
                ]
            ]
        ],
        'ar' => [
            'comparison_table' => [
                'headers' => ['الميزة', 'الأولوية', 'الجدول الزمني', 'التأثير', 'الحالة'],
                'rows' => [
                    ['فحص السيرة الذاتية بقوة الذكاء الاصطناعي', 'حرج', 'الشهر 1-2', 'مرتفع', 'قيد التطوير'],
                    ['محرك ترتيب المرشحين', 'حرج', 'الشهر 2-3', 'مرتفع', 'قيد التطوير'],
                    ['أتمتة جدولة المقابلات', 'مرتفع', 'الشهر 3-4', 'متوسط', 'مخطط'],
                    ['لوحة معلومات تحليل الأداء', 'مرتفع', 'الشهر 4-5', 'مرتفع', 'مخطط'],
                    ['تقييم مهارات الموظفين', 'متوسط', 'الشهر 5-6', 'متوسط', 'مخطط'],
                    ['بوابة التواصل مع المرشحين', 'متوسط', 'الشهر 3-4', 'متوسط', 'مخطط'],
                ]
            ]
        ]
    ],

    'mvp_development_roadmap' => [
        'en' => [
            'journey_timeline' => [
                'stages' => [
                    [
                        'title' => 'Phase 1: Core Foundation',
                        'description' => 'Build AI resume screening and candidate database infrastructure',
                        'touchpoints' => ['API Architecture', 'ML Model Training', 'Database Setup'],
                        'actions' => ['Complete infrastructure setup', 'Train initial models', 'Security implementation']
                    ],
                    [
                        'title' => 'Phase 2: Intelligence Layer',
                        'description' => 'Develop ranking algorithms and performance analytics',
                        'touchpoints' => ['Algorithm Development', 'Analytics Engine', 'Reporting Dashboard'],
                        'actions' => ['Build ranking engine', 'Create analytics suite', 'Design UI/UX']
                    ],
                    [
                        'title' => 'Phase 3: Automation & Integration',
                        'description' => 'Implement workflow automation and third-party integrations',
                        'touchpoints' => ['Interview Scheduling', 'HR System Integration', 'Communication Tools'],
                        'actions' => ['Develop automation workflows', 'Integration APIs', 'Testing & QA']
                    ],
                    [
                        'title' => 'Phase 4: Enterprise Ready',
                        'description' => 'Scale, security hardening, and enterprise deployment',
                        'touchpoints' => ['Security Audit', 'Performance Optimization', 'Documentation'],
                        'actions' => ['Enterprise testing', 'Compliance certification', 'Go-to-market']
                    ]
                ]
            ]
        ],
        'ar' => [
            'journey_timeline' => [
                'stages' => [
                    [
                        'title' => 'المرحلة 1: الأساس الأساسي',
                        'description' => 'بناء فحص السيرة الذاتية بالذكاء الاصطناعي وبنية قاعدة بيانات المرشحين',
                        'touchpoints' => ['معمارية API', 'تدريب نموذج التعلم الآلي', 'إعداد قاعدة البيانات'],
                        'actions' => ['إكمال إعداد البنية التحتية', 'تدريب النماذج الأولية', 'تنفيذ الأمان']
                    ],
                    [
                        'title' => 'المرحلة 2: طبقة الذكاء',
                        'description' => 'تطوير خوارزميات الترتيب وتحليلات الأداء',
                        'touchpoints' => ['تطوير الخوارزميات', 'محرك التحليلات', 'لوحة التقارير'],
                        'actions' => ['بناء محرك الترتيب', 'إنشاء مجموعة التحليلات', 'تصميم الواجهة']
                    ],
                    [
                        'title' => 'المرحلة 3: الأتمتة والتكامل',
                        'description' => 'تنفيذ أتمتة سير العمل والتكامل مع الأطراف الثالثة',
                        'touchpoints' => ['جدولة المقابلات', 'تكامل نظام الموارد البشرية', 'أدوات التواصل'],
                        'actions' => ['تطوير سير العمل الآلي', 'واجهات برمجية للتكامل', 'الاختبار والتحقق']
                    ],
                    [
                        'title' => 'المرحلة 4: جاهزة للمؤسسات',
                        'description' => 'الحجم والتقوية الأمنية ونشر المؤسسات',
                        'touchpoints' => ['تدقيق الأمان', 'تحسين الأداء', 'التوثيق'],
                        'actions' => ['اختبار المؤسسات', 'شهادة الامتثال', 'نزول إلى السوق']
                    ]
                ]
            ]
        ]
    ],

    'mvp_tech_stack' => [
        'en' => [
            'key_value' => [
                'items' => [
                    ['key' => 'Backend Framework', 'value' => 'Laravel / Node.js'],
                    ['key' => 'Frontend Framework', 'value' => 'React.js / Vue.js'],
                    ['key' => 'AI/ML Engine', 'value' => 'TensorFlow / PyTorch'],
                    ['key' => 'Database', 'value' => 'PostgreSQL + MongoDB'],
                    ['key' => 'Cloud Infrastructure', 'value' => 'AWS / Microsoft Azure'],
                    ['key' => 'Real-time Processing', 'value' => 'Apache Kafka / RabbitMQ'],
                    ['key' => 'Search Engine', 'value' => 'Elasticsearch'],
                    ['key' => 'Caching', 'value' => 'Redis'],
                ]
            ]
        ],
        'ar' => [
            'key_value' => [
                'items' => [
                    ['key' => 'إطار العمل الخلفي', 'value' => 'Laravel / Node.js'],
                    ['key' => 'إطار العمل الأمامي', 'value' => 'React.js / Vue.js'],
                    ['key' => 'محرك الذكاء الاصطناعي والتعلم الآلي', 'value' => 'TensorFlow / PyTorch'],
                    ['key' => 'قاعدة البيانات', 'value' => 'PostgreSQL + MongoDB'],
                    ['key' => 'البنية التحتية السحابية', 'value' => 'AWS / Microsoft Azure'],
                    ['key' => 'المعالجة في الوقت الفعلي', 'value' => 'Apache Kafka / RabbitMQ'],
                    ['key' => 'محرك البحث', 'value' => 'Elasticsearch'],
                    ['key' => 'التخزين المؤقت', 'value' => 'Redis'],
                ]
            ]
        ]
    ],

    'mvp_resource_requirements' => [
        'en' => [
            'stat_cards' => [
                'metrics' => [
                    ['label' => 'Engineering Team', 'value' => '12', 'description' => 'Full-stack developers, ML engineers, DevOps'],
                    ['label' => 'Product & Design', 'value' => '3', 'description' => 'Product manager, UX/UI designers, researcher'],
                    ['label' => 'Initial Budget', 'value' => '$450K', 'description' => 'Infrastructure, tools, and operations'],
                    ['label' => 'Development Timeline', 'value' => '6 Months', 'description' => 'MVP to market-ready product'],
                ]
            ]
        ],
        'ar' => [
            'stat_cards' => [
                'metrics' => [
                    ['label' => 'فريق الهندسة', 'value' => '12', 'description' => 'مطورو المكدس الكامل، مهندسو التعلم الآلي، DevOps'],
                    ['label' => 'المنتج والتصميم', 'value' => '3', 'description' => 'مدير المنتج، مصممو UX/UI، باحث'],
                    ['label' => 'الميزانية الأولية', 'value' => '450000 ريال', 'description' => 'البنية التحتية والأدوات والعمليات'],
                    ['label' => 'جدول التطوير', 'value' => '6 أشهر', 'description' => 'MVP إلى منتج جاهز للسوق'],
                ]
            ]
        ]
    ],

    'mvp_risk_mitigation' => [
        'en' => [
            'text_content' => [
                'title' => 'Risk Mitigation Strategy',
                'sections' => [
                    [
                        'heading' => 'AI Model Accuracy Risk',
                        'content' => 'Implement continuous model monitoring and retraining pipelines. Establish human review checkpoints for high-stakes hiring decisions. Maintain version control for all ML models with rollback capabilities.'
                    ],
                    [
                        'heading' => 'Data Privacy & Compliance Risk',
                        'content' => 'Comply with GDPR, CCPA, and local Saudi Arabia data protection regulations. Implement end-to-end encryption, data anonymization, and audit trails. Engage legal counsel for regulatory compliance.'
                    ],
                    [
                        'heading' => 'Market Adoption Risk',
                        'content' => 'Conduct extensive user research with HR professionals and hiring managers. Build product with Saudi Arabia enterprises first. Establish early partnerships with HR consulting firms.'
                    ],
                    [
                        'heading' => 'Technical Scalability Risk',
                        'content' => 'Design for horizontal scalability from day one. Implement auto-scaling infrastructure. Load testing and performance optimization throughout development cycle.'
                    ],
                ]
            ]
        ],
        'ar' => [
            'text_content' => [
                'title' => 'استراتيجية تخفيف المخاطر',
                'sections' => [
                    [
                        'heading' => 'مخاطر دقة نموذج الذكاء الاصطناعي',
                        'content' => 'تنفيذ مراقبة النموذج المستمرة وخطوط الأنابيب إعادة التدريب. إنشاء نقاط مراجعة بشرية لقرارات التوظيف عالية المخاطر. الحفاظ على التحكم في الإصدار لجميع نماذج التعلم الآلي مع القدرة على التراجع.'
                    ],
                    [
                        'heading' => 'مخاطر خصوصية البيانات والامتثال',
                        'content' => 'الامتثال لـ GDPR و CCPA واللوائح المحلية لحماية البيانات في المملكة العربية السعودية. تنفيذ التشفير من طرف إلى طرف وإخفاء الهوية عن البيانات وآثار التدقيق. التعاقد مع المستشارين القانونيين للامتثال التنظيمي.'
                    ],
                    [
                        'heading' => 'مخاطر اعتماد السوق',
                        'content' => 'إجراء أبحاث مستخدمين مكثفة مع متخصصي الموارد البشرية ومديري التوظيف. بناء المنتج مع مؤسسات المملكة العربية السعودية أولاً. إنشاء شراكات مبكرة مع شركات استشارات الموارد البشرية.'
                    ],
                    [
                        'heading' => 'مخاطر قابلية التوسع التقنية',
                        'content' => 'التصميم للقابلية الأفقية من اليوم الأول. تنفيذ البنية التحتية قابلة للتوسع التلقائي. اختبار الحمل وتحسين الأداء خلال دورة التطوير.'
                    ],
                ]
            ]
        ]
    ],

    'usp_unique_selling_points' => [
        'en' => [
            'text_content' => [
                'title' => 'Unique Selling Points',
                'sections' => [
                    [
                        'heading' => 'Advanced AI-Powered Resume Screening',
                        'content' => 'Connect AI uses proprietary deep learning models trained on millions of successful hires across GCC enterprises. Reduces hiring time by 70% while improving candidate quality, understanding cultural fit and technical competencies beyond keyword matching.'
                    ],
                    [
                        'heading' => 'Localized for GCC Market',
                        'content' => 'Purpose-built for Saudi Arabia and Gulf region enterprises. Understands local hiring practices, cultural values, and regulatory requirements. Multilingual support for Arabic and English with region-specific compliance features.'
                    ],
                    [
                        'heading' => 'Integrated Performance Management',
                        'content' => 'Beyond recruitment, Connect AI provides continuous employee performance tracking, skills assessment, and career development planning. Creates a complete talent management ecosystem within single platform.'
                    ],
                    [
                        'heading' => 'Explainable AI Decisions',
                        'content' => 'Every AI recommendation includes clear explanation of decision factors. HR teams can understand why candidates are ranked, increasing trust and enabling better hiring decisions.'
                    ],
                ]
            ]
        ],
        'ar' => [
            'text_content' => [
                'title' => 'نقاط البيع الفريدة',
                'sections' => [
                    [
                        'heading' => 'فحص السيرة الذاتية المتقدم بقوة الذكاء الاصطناعي',
                        'content' => 'يستخدم Connect AI نماذج التعلم العميق الملكية المدربة على ملايين عمليات التوظيف الناجحة عبر مؤسسات مجلس التعاون الخليجي. يقلل وقت التوظيف بنسبة 70٪ مع تحسين جودة المرشحين، مع فهم التوافق الثقافي والكفاءات الفنية بما يتجاوز مطابقة الكلمات الرئيسية.'
                    ],
                    [
                        'heading' => 'مخصص لسوق مجلس التعاون الخليجي',
                        'content' => 'مصمم خصيصًا لمؤسسات المملكة العربية السعودية والمنطقة الخليجية. يفهم ممارسات التوظيف المحلية والقيم الثقافية والمتطلبات التنظيمية. دعم متعدد اللغات للعربية والإنجليزية مع ميزات الامتثال الخاصة بالمنطقة.'
                    ],
                    [
                        'heading' => 'إدارة الأداء المتكاملة',
                        'content' => 'بما يتجاوز التوظيف، يوفر Connect AI تتبع أداء الموظفين المستمر وتقييم المهارات وتخطيط التطور الوظيفي. ينشئ نظام إدارة المواهب الكامل ضمن منصة واحدة.'
                    ],
                    [
                        'heading' => 'قرارات الذكاء الاصطناعي القابلة للتفسير',
                        'content' => 'تتضمن كل توصية ذكاء اصطناعي شرحًا واضحًا لعوامل القرار. يمكن لفرق الموارد البشرية أن تفهم سبب ترتيب المرشحين، مما يزيد الثقة ويمكن من اتخاذ قرارات توظيف أفضل.'
                    ],
                ]
            ]
        ]
    ],

    'usp_competitive_advantage' => [
        'en' => [
            'comparison_table' => [
                'headers' => ['Feature', 'Connect AI', 'Competitor A', 'Competitor B', 'Competitor C'],
                'rows' => [
                    ['GCC-Specific AI Training', 'Yes', 'No', 'No', 'No'],
                    ['Integrated Performance Management', 'Yes', 'Partial', 'No', 'Partial'],
                    ['Arabic Language Support', 'Native', 'Basic', 'Basic', 'None'],
                    ['Explainable AI', 'Yes', 'No', 'Yes', 'No'],
                    ['Real-time Analytics Dashboard', 'Yes', 'Yes', 'No', 'Yes'],
                    ['Workforce Planning Tools', 'Advanced', 'Basic', 'Basic', 'Advanced'],
                    ['Integration Capabilities', 'Extensive', 'Limited', 'Extensive', 'Limited'],
                    ['Pricing Model', 'SaaS/Per-Seat', 'SaaS/Per-Seat', 'Enterprise', 'SaaS/Per-Seat'],
                ]
            ]
        ],
        'ar' => [
            'comparison_table' => [
                'headers' => ['الميزة', 'Connect AI', 'المنافس أ', 'المنافس ب', 'المنافس ج'],
                'rows' => [
                    ['تدريب الذكاء الاصطناعي الخاص بمجلس التعاون الخليجي', 'نعم', 'لا', 'لا', 'لا'],
                    ['إدارة الأداء المتكاملة', 'نعم', 'جزئي', 'لا', 'جزئي'],
                    ['دعم اللغة العربية', 'أصلي', 'أساسي', 'أساسي', 'لا'],
                    ['الذكاء الاصطناعي القابل للتفسير', 'نعم', 'لا', 'نعم', 'لا'],
                    ['لوحة معلومات التحليلات في الوقت الفعلي', 'نعم', 'نعم', 'لا', 'نعم'],
                    ['أدوات التخطيط القوى العاملة', 'متقدم', 'أساسي', 'أساسي', 'متقدم'],
                    ['إمكانيات التكامل', 'واسع', 'محدود', 'واسع', 'محدود'],
                    ['نموذج التسعير', 'SaaS/Per-Seat', 'SaaS/Per-Seat', 'مؤسسة', 'SaaS/Per-Seat'],
                ]
            ]
        ]
    ],

    'usp_differentiation_strategy' => [
        'en' => [
            'text_content' => [
                'title' => 'Differentiation Strategy',
                'sections' => [
                    [
                        'heading' => 'Regional Expertise & Localization',
                        'content' => 'Deep understanding of GCC labor markets, cultural nuances, and regulatory landscape. Built by team with direct experience in Saudi Arabian enterprises. Every feature considers local hiring practices and compliance requirements.'
                    ],
                    [
                        'heading' => 'AI-First Architecture',
                        'content' => 'Entire platform designed around AI decision-making, not as add-on feature. Proprietary algorithms trained on relevant GCC hiring data. Continuous learning from customer data improves model accuracy over time.'
                    ],
                    [
                        'heading' => 'End-to-End Talent Lifecycle',
                        'content' => 'Unlike competitors focused only on recruitment, Connect AI covers entire employee journey from recruitment through performance management and development. Single source of truth for all talent data.'
                    ],
                    [
                        'heading' => 'Customer-Centric Innovation',
                        'content' => 'Built in partnership with leading Saudi Arabian enterprises. Flexible architecture allows rapid customization and feature development based on customer feedback. Regular sprints deliver continuous improvements.'
                    ],
                ]
            ]
        ],
        'ar' => [
            'text_content' => [
                'title' => 'استراتيجية التمييز',
                'sections' => [
                    [
                        'heading' => 'الخبرة الإقليمية والتوطين',
                        'content' => 'فهم عميق لأسواق مجلس التعاون الخليجي والفروقات الثقافية والمشهد التنظيمي. مبني من قبل فريق لديه خبرة مباشرة في المؤسسات السعودية. تأخذ كل ميزة في الاعتبار الممارسات المحلية للتوظيف ومتطلبات الامتثال.'
                    ],
                    [
                        'heading' => 'معمارية موجهة بالذكاء الاصطناعي أولاً',
                        'content' => 'تم تصميم المنصة بالكامل حول صنع القرار بالذكاء الاصطناعي، وليس كميزة إضافية. خوارزميات ملكية مدربة على بيانات التوظيف الخليجية ذات الصلة. التعلم المستمر من بيانات العملاء يحسن دقة النموذج بمرور الوقت.'
                    ],
                    [
                        'heading' => 'دورة حياة الموهبة الشاملة',
                        'content' => 'على عكس المنافسين الذين يركزون على التوظيف فقط، يغطي Connect AI رحلة الموظف بالكامل من التوظيف من خلال إدارة الأداء والتطوير. مصدر الحقيقة الوحيد لجميع بيانات الموهبة.'
                    ],
                    [
                        'heading' => 'الابتكار الموجه للعميل',
                        'content' => 'مبني بالشراكة مع المؤسسات السعودية الرائدة. المعمارية المرنة تسمح بالتخصيص السريع وتطوير الميزات بناءً على ملاحظات العملاء. الركضات العادية توفر تحسينات مستمرة.'
                    ],
                ]
            ]
        ]
    ],

    'usp_value_chain' => [
        'en' => [
            'key_value' => [
                'items' => [
                    ['key' => 'Data Collection & Enrichment', 'value' => 'Resume parsing, LinkedIn data, employment history'],
                    ['key' => 'AI Model Processing', 'value' => 'Screening, ranking, skills assessment algorithms'],
                    ['key' => 'Decision Support', 'value' => 'Explainable recommendations with confidence scores'],
                    ['key' => 'Workflow Integration', 'value' => 'Calendar sync, communication tools, HRIS systems'],
                    ['key' => 'Performance Analytics', 'value' => 'Employee tracking, KPI monitoring, reports'],
                    ['key' => 'Continuous Improvement', 'value' => 'Model retraining, feedback loops, optimization'],
                ]
            ]
        ],
        'ar' => [
            'key_value' => [
                'items' => [
                    ['key' => 'جمع البيانات والإثراء', 'value' => 'تحليل السيرة الذاتية، بيانات LinkedIn، سجل العمل'],
                    ['key' => 'معالجة نموذج الذكاء الاصطناعي', 'value' => 'فحص الخوارزميات، الترتيب، تقييم المهارات'],
                    ['key' => 'دعم القرار', 'value' => 'توصيات قابلة للتفسير مع درجات الثقة'],
                    ['key' => 'تكامل سير العمل', 'value' => 'مزامنة التقويم، أدوات الاتصال، أنظمة HRIS'],
                    ['key' => 'تحليلات الأداء', 'value' => 'تتبع الموظفين، مراقبة KPI، التقارير'],
                    ['key' => 'التحسين المستمر', 'value' => 'إعادة تدريب النموذج، حلقات الملاحظات، التحسين'],
                ]
            ]
        ]
    ],

    'cp_primary_persona' => [
        'en' => [
            'persona_card' => [
                'name' => 'Fatima Al-Dosari',
                'role' => 'HR Director',
                'age' => 38,
                'location' => 'Riyadh, Saudi Arabia',
                'quote' => 'I need to hire top talent quickly without the 3-month recruitment cycle.',
                'demographics' => [
                    'company_size' => '500-2000 employees',
                    'industry' => 'Technology & Finance',
                    'education' => 'Masters in HR Management',
                    'experience' => '12 years in talent management'
                ],
                'pain_points' => [
                    'Lengthy recruitment processes consuming 3-4 months',
                    'High volume of unqualified resume screening',
                    'Difficulty assessing cultural fit for Saudi enterprises',
                    'Limited visibility into employee performance metrics',
                    'Decentralized talent data across multiple systems'
                ],
                'goals' => [
                    'Reduce time-to-hire from 90 days to 30 days',
                    'Improve quality of new hires and retention rates',
                    'Create standardized hiring process across 15 branches',
                    'Implement data-driven talent management decisions',
                    'Enhance employee development and engagement'
                ],
                'motivations' => [
                    'Career advancement through operational excellence',
                    'Recognition for building high-performing teams',
                    'Modernizing outdated HR processes',
                    'Supporting company growth through better talent acquisition'
                ]
            ]
        ],
        'ar' => [
            'persona_card' => [
                'name' => 'فاطمة الدوسري',
                'role' => 'مديرة الموارد البشرية',
                'age' => 38,
                'location' => 'الرياض، المملكة العربية السعودية',
                'quote' => 'أحتاج إلى توظيف أفضل المواهب بسرعة بدون دورة التوظيف لمدة 3 أشهر.',
                'demographics' => [
                    'company_size' => '500-2000 موظف',
                    'industry' => 'التكنولوجيا والمالية',
                    'education' => 'ماجستير في إدارة الموارد البشرية',
                    'experience' => '12 سنة في إدارة المواهب'
                ],
                'pain_points' => [
                    'عمليات توظيف طويلة تستهلك 3-4 أشهر',
                    'حجم كبير من فحص السيرة الذاتية غير المؤهل',
                    'صعوبة تقييم التوافق الثقافي لمؤسسات سعودية',
                    'قابلية محدودة لرؤية مقاييس أداء الموظفين',
                    'بيانات الموهبة اللامركزية عبر الأنظمة المتعددة'
                ],
                'goals' => [
                    'تقليل وقت التوظيف من 90 يوم إلى 30 يوم',
                    'تحسين جودة الموظفين الجدد ومعدلات الاحتفاظ',
                    'إنشاء عملية توظيف موحدة عبر 15 فرع',
                    'تنفيذ قرارات إدارة المواهب المستندة إلى البيانات',
                    'تعزيز تطوير الموظفين والمشاركة'
                ],
                'motivations' => [
                    'التطور الوظيفي من خلال التميز التشغيلي',
                    'الاعتراف ببناء فرق عالية الأداء',
                    'تحديث عمليات الموارد البشرية القديمة',
                    'دعم نمو الشركة من خلال اكتساب المواهب الأفضل'
                ]
            ]
        ]
    ],

    'cp_secondary_persona' => [
        'en' => [
            'persona_card' => [
                'name' => 'Ahmed Al-Rashid',
                'role' => 'Hiring Manager / Department Head',
                'age' => 45,
                'location' => 'Dubai, UAE',
                'quote' => 'I need to fill 10 positions this quarter with qualified candidates.',
                'demographics' => [
                    'company_size' => '1000-5000 employees',
                    'industry' => 'Financial Services',
                    'education' => 'MBA',
                    'experience' => '15 years in management'
                ],
                'pain_points' => [
                    'Receiving hundreds of irrelevant applications',
                    'Spending 20 hours per week on recruitment tasks',
                    'Difficulty finding candidates with specific skill combinations',
                    'No visibility into hiring pipeline and candidate status',
                    'Poor collaboration between HR and hiring managers'
                ],
                'goals' => [
                    'Reduce time spent on recruitment admin tasks by 70%',
                    'Hire 10 qualified candidates within 8 weeks',
                    'Improve quality of initial interview candidates',
                    'Better track candidates through hiring process',
                    'Build stronger partnerships with HR team'
                ],
                'motivations' => [
                    'Meeting team hiring targets on schedule',
                    'Reducing operational workload and stress',
                    'Access to quality candidate information',
                    'Streamlined communication with HR team'
                ]
            ]
        ],
        'ar' => [
            'persona_card' => [
                'name' => 'أحمد الرشيد',
                'role' => 'مدير التوظيف / رئيس القسم',
                'age' => 45,
                'location' => 'دبي، الإمارات العربية المتحدة',
                'quote' => 'أحتاج إلى ملء 10 مواقع هذا الربع مع مرشحين مؤهلين.',
                'demographics' => [
                    'company_size' => '1000-5000 موظف',
                    'industry' => 'الخدمات المالية',
                    'education' => 'ماجستير في إدارة الأعمال',
                    'experience' => '15 سنة في الإدارة'
                ],
                'pain_points' => [
                    'تلقي مئات الطلبات غير ذات الصلة',
                    'قضاء 20 ساعة في الأسبوع على مهام التوظيف',
                    'صعوبة إيجاد مرشحين بمجموعات مهارات محددة',
                    'عدم وجود رؤية في خط أنابيب التوظيف وحالة المرشح',
                    'تعاون ضعيف بين الموارد البشرية ومديري التوظيف'
                ],
                'goals' => [
                    'تقليل الوقت الذي تقضيه على مهام إدارة التوظيف بنسبة 70٪',
                    'توظيف 10 مرشحين مؤهلين في غضون 8 أسابيع',
                    'تحسين جودة مرشحي المقابلة الأولية',
                    'تتبع أفضل للمرشحين من خلال عملية التوظيف',
                    'بناء شراكات أقوى مع فريق الموارس البشرية'
                ],
                'motivations' => [
                    'تحقيق أهداف التوظيف في الموعد المحدد',
                    'تقليل العبء التشغيلي والإجهاد',
                    'الوصول إلى معلومات المرشح ذات الجودة',
                    'تواصل مبسط مع فريق الموارد البشرية'
                ]
            ]
        ]
    ],

    'cp_buyer_journey' => [
        'en' => [
            'journey_timeline' => [
                'stages' => [
                    [
                        'title' => 'Awareness Stage',
                        'description' => 'HR directors discover recruitment challenges in industry discussions and peer recommendations',
                        'touchpoints' => ['Industry events', 'Peer networks', 'LinkedIn content', 'HR publications'],
                        'actions' => ['Content marketing', 'Thought leadership', 'Speaking engagements', 'Case studies']
                    ],
                    [
                        'title' => 'Consideration Stage',
                        'description' => 'Evaluate Connect AI against competitors through demos, trials, and customer references',
                        'touchpoints' => ['Product demo', 'Free trial', 'Customer testimonials', 'ROI calculator'],
                        'actions' => ['Request demo', 'Start trial', 'Read reviews', 'Contact sales']
                    ],
                    [
                        'title' => 'Decision Stage',
                        'description' => 'Final negotiations on pricing, implementation timeline, and customization requirements',
                        'touchpoints' => ['Sales negotiation', 'Implementation plan', 'Service terms', 'Integration requirements'],
                        'actions' => ['Sign contract', 'Schedule onboarding', 'Configure system', 'Train teams']
                    ],
                    [
                        'title' => 'Adoption Stage',
                        'description' => 'Successful implementation and team adoption of Connect AI platform',
                        'touchpoints' => ['Onboarding training', 'Support resources', 'Success manager', 'User community'],
                        'actions' => ['Use platform daily', 'Provide feedback', 'Expand usage', 'Renew contract']
                    ]
                ]
            ]
        ],
        'ar' => [
            'journey_timeline' => [
                'stages' => [
                    [
                        'title' => 'مرحلة الوعي',
                        'description' => 'يكتشف مديرو الموارس البشرية تحديات التوظيف في نقاشات الصناعة والتوصيات من الأقران',
                        'touchpoints' => ['فعاليات الصناعة', 'شبكات الأقران', 'محتوى LinkedIn', 'منشورات الموارد البشرية'],
                        'actions' => ['تسويق المحتوى', 'قيادة الفكر', 'المشاركة في الفعاليات', 'دراسات الحالات']
                    ],
                    [
                        'title' => 'مرحلة الاعتبار',
                        'description' => 'تقييم Connect AI مقابل المنافسين من خلال العروض التوضيحية والتجارب والمراجع من العملاء',
                        'touchpoints' => ['عرض توضيحي للمنتج', 'تجربة مجانية', 'شهادات العملاء', 'حاسبة العائد على الاستثمار'],
                        'actions' => ['طلب عرض توضيحي', 'بدء التجربة', 'قراءة المراجعات', 'الاتصال بالمبيعات']
                    ],
                    [
                        'title' => 'مرحلة القرار',
                        'description' => 'المفاوضات النهائية بشأن التسعير وجدول الزمني للتنفيذ ومتطلبات التخصيص',
                        'touchpoints' => ['مفاوضات المبيعات', 'خطة التنفيذ', 'شروط الخدمة', 'متطلبات التكامل'],
                        'actions' => ['توقيع العقد', 'جدولة التدريب', 'تكوين النظام', 'تدريب الفرق']
                    ],
                    [
                        'title' => 'مرحلة التبني',
                        'description' => 'التنفيذ الناجح وتبني الفريق لمنصة Connect AI',
                        'touchpoints' => ['تدريب الإعداد', 'موارد الدعم', 'مدير النجاح', 'مجتمع المستخدمين'],
                        'actions' => ['استخدم المنصة يوميًا', 'قدم ملاحظات', 'توسيع الاستخدام', 'تجديد العقد']
                    ]
                ]
            ]
        ]
    ],

    'cp_pain_points_analysis' => [
        'en' => [
            'text_content' => [
                'title' => 'Customer Pain Points Analysis',
                'sections' => [
                    [
                        'heading' => 'Recruitment Process Inefficiency',
                        'content' => 'Current hiring processes take 90+ days across GCC enterprises. HR teams spend 60% of time on administrative tasks like screening resumes. Manual review of hundreds of applications leads to fatigue and poor decision-making. Lack of standardized processes across multiple branches creates inconsistency.'
                    ],
                    [
                        'heading' => 'Poor Quality Hiring Decisions',
                        'content' => 'Without data-driven insights, hiring managers rely on gut feeling and limited interview time. High failure rate within first year of employment. Difficulty assessing soft skills, cultural fit, and potential for growth. No predictive analytics on which candidates will succeed.'
                    ],
                    [
                        'heading' => 'Talent Data Fragmentation',
                        'content' => 'Employee information scattered across HR systems, email, spreadsheets. No single source of truth for candidate and employee data. Difficulty tracking candidate journey from application to onboarding. Limited visibility into employee performance post-hire.'
                    ],
                    [
                        'heading' => 'Compliance and Regulatory Challenges',
                        'content' => 'Saudi Arabia hiring regulations require careful compliance. Limited documentation and audit trails for hiring decisions. Risk of discrimination claims without clear decision rationale. Difficulty maintaining GDPR compliance for candidate data from international applicants.'
                    ],
                ]
            ]
        ],
        'ar' => [
            'text_content' => [
                'title' => 'تحليل نقاط الألم للعملاء',
                'sections' => [
                    [
                        'heading' => 'عدم كفاءة عملية التوظيف',
                        'content' => 'تستغرق عمليات التوظيف الحالية 90+ يومًا عبر مؤسسات مجلس التعاون الخليجي. تقضي فرق الموارس البشرية 60٪ من الوقت في مهام إدارية مثل فحص السيرة الذاتية. يؤدي الفحص اليدوي لمئات التطبيقات إلى الإرهاق وسوء اتخاذ القرار. يؤدي عدم وجود عمليات موحدة عبر فروع متعددة إلى عدم الاتساق.'
                    ],
                    [
                        'heading' => 'قرارات التوظيف منخفضة الجودة',
                        'content' => 'بدون رؤى قائمة على البيانات، يعتمد مديرو التوظيف على الحدس والوقت المحدود للمقابلة. معدل فشل عالي في السنة الأولى من العمل. صعوبة تقييم المهارات الناعمة والتوافق الثقافي والإمكانات للنمو. لا توجد تحليلات تنبؤية حول المرشحين الذين سينجحون.'
                    ],
                    [
                        'heading' => 'تجزئة بيانات الموهبة',
                        'content' => 'معلومات الموظف مبعثرة عبر أنظمة الموارس البشرية والبريد الإلكتروني والجداول. لا يوجد مصدر وحيد للحقيقة لبيانات المرشح والموظف. صعوبة تتبع رحلة المرشح من التطبيق إلى الإعداد. قابلية محدودة لرؤية أداء الموظفين بعد التوظيف.'
                    ],
                    [
                        'heading' => 'تحديات الامتثال التنظيمي',
                        'content' => 'تتطلب لوائح التوظيف في المملكة العربية السعودية الامتثال الدقيق. التوثيق والآثار المحدودة لقرارات التوظيف. خطر المطالبات بالتمييز بدون توضيح واضح للقرار. صعوبة الحفاظ على الامتثال GDPR لبيانات المرشح من المتقدمين الدوليين.'
                    ],
                ]
            ]
        ]
    ],

    'fin_revenue_model' => [
        'en' => [
            'pricing_cards' => [
                'tiers' => [
                    [
                        'name' => 'Starter',
                        'price' => '$299/month',
                        'features' => [
                            'Up to 50 monthly hires',
                            'AI resume screening',
                            'Basic candidate ranking',
                            'Email support',
                            '2 user seats'
                        ],
                        'highlighted' => false,
                        'cta' => 'Start Free Trial'
                    ],
                    [
                        'name' => 'Professional',
                        'price' => '$899/month',
                        'features' => [
                            'Unlimited monthly hires',
                            'Advanced AI screening',
                            'Predictive analytics',
                            'Performance management',
                            '10 user seats',
                            'Priority support',
                            'Custom integrations'
                        ],
                        'highlighted' => true,
                        'cta' => 'Request Demo'
                    ],
                    [
                        'name' => 'Enterprise',
                        'price' => 'Custom',
                        'features' => [
                            'Unlimited everything',
                            'Dedicated account manager',
                            'Custom workflows',
                            'Advanced security',
                            'Unlimited user seats',
                            '24/7 premium support',
                            'On-premise deployment'
                        ],
                        'highlighted' => false,
                        'cta' => 'Contact Sales'
                    ]
                ]
            ]
        ],
        'ar' => [
            'pricing_cards' => [
                'tiers' => [
                    [
                        'name' => 'المبتدئ',
                        'price' => '1121 ريال/شهر',
                        'features' => [
                            'حتى 50 عملية توظيف شهرية',
                            'فحص السيرة الذاتية بالذكاء الاصطناعي',
                            'ترتيب المرشحين الأساسي',
                            'دعم البريد الإلكتروني',
                            'مقاعد المستخدم 2'
                        ],
                        'highlighted' => false,
                        'cta' => 'ابدأ النسخة التجريبية المجانية'
                    ],
                    [
                        'name' => 'احترافي',
                        'price' => '3371 ريال/شهر',
                        'features' => [
                            'عمليات توظيف شهرية غير محدودة',
                            'فحص متقدم بالذكاء الاصطناعي',
                            'تحليلات تنبؤية',
                            'إدارة الأداء',
                            'مقاعد المستخدم 10',
                            'دعم ذي أولوية',
                            'تكاملات مخصصة'
                        ],
                        'highlighted' => true,
                        'cta' => 'طلب عرض توضيحي'
                    ],
                    [
                        'name' => 'مؤسسة',
                        'price' => 'مخصص',
                        'features' => [
                            'كل شيء غير محدود',
                            'مدير حساب مخصص',
                            'سير عمل مخصص',
                            'أمان متقدم',
                            'مقاعد المستخدم غير محدود',
                            'دعم متميز 24/7',
                            'نشر محلي'
                        ],
                        'highlighted' => false,
                        'cta' => 'اتصل بفريق المبيعات'
                    ]
                ]
            ]
        ]
    ],

    'fin_cost_structure' => [
        'en' => [
            'progress_bars' => [
                'items' => [
                    ['label' => 'AI & ML Infrastructure', 'value' => 35, 'suffix' => '%'],
                    ['label' => 'Engineering & Development', 'value' => 25, 'suffix' => '%'],
                    ['label' => 'Sales & Marketing', 'value' => 20, 'suffix' => '%'],
                    ['label' => 'Operations & Support', 'value' => 15, 'suffix' => '%'],
                    ['label' => 'General & Administrative', 'value' => 5, 'suffix' => '%'],
                ]
            ]
        ],
        'ar' => [
            'progress_bars' => [
                'items' => [
                    ['label' => 'البنية التحتية للذكاء الاصطناعي والتعلم الآلي', 'value' => 35, 'suffix' => '٪'],
                    ['label' => 'الهندسة والتطوير', 'value' => 25, 'suffix' => '٪'],
                    ['label' => 'المبيعات والتسويق', 'value' => 20, 'suffix' => '٪'],
                    ['label' => 'العمليات والدعم', 'value' => 15, 'suffix' => '٪'],
                    ['label' => 'العام والإداري', 'value' => 5, 'suffix' => '٪'],
                ]
            ]
        ]
    ],

    'fin_financial_projections' => [
        'en' => [
            'stat_cards' => [
                'metrics' => [
                    ['label' => 'Year 1 Revenue', 'value' => '$1.8M', 'description' => 'ARR from current customer base'],
                    ['label' => 'Year 2 Projection', 'value' => '$4.2M', 'description' => 'Targeting 133% YoY growth'],
                    ['label' => 'Year 3 Projection', 'value' => '$9.8M', 'description' => 'Expanding GCC presence'],
                    ['label' => 'Gross Margin', 'value' => '72%', 'description' => 'SaaS model with platform leverage'],
                ]
            ]
        ],
        'ar' => [
            'stat_cards' => [
                'metrics' => [
                    ['label' => 'إيرادات السنة 1', 'value' => '1.8 مليون دولار', 'description' => 'ARR من قاعدة العملاء الحالية'],
                    ['label' => 'توقعات السنة 2', 'value' => '4.2 مليون دولار', 'description' => 'استهداف نمو سنوي بنسبة 133٪'],
                    ['label' => 'توقعات السنة 3', 'value' => '9.8 مليون دولار', 'description' => 'توسيع الحضور في مجلس التعاون الخليجي'],
                    ['label' => 'إجمالي الهامش', 'value' => '72٪', 'description' => 'نموذج SaaS مع نفوذ المنصة'],
                ]
            ]
        ]
    ],

    'fin_funding_requirements' => [
        'en' => [
            'text_content' => [
                'title' => 'Funding Requirements',
                'sections' => [
                    [
                        'heading' => 'Series A Funding Goal',
                        'content' => 'Seeking $3M in Series A funding to accelerate product development, expand GCC market presence, and build sales team. Current ARR of $1.8M demonstrates product-market fit and strong customer traction.'
                    ],
                    [
                        'heading' => 'Use of Funds',
                        'content' => 'Product Development (40%): Advanced AI features, integrations, mobile app. Sales & Marketing (35%): Regional sales team, marketing campaigns, partnerships. Operations (15%): Infrastructure scaling, support team expansion. General (10%): Legal, finance, administration.'
                    ],
                    [
                        'heading' => 'Funding Timeline',
                        'content' => 'Series A funding will support 24-month runway to profitability. Target break-even by end of Year 2 based on projected growth trajectory and improving unit economics.'
                    ],
                    [
                        'heading' => 'Capital Efficiency',
                        'content' => 'CAC of $8,000 per customer with 36-month LTV of $180,000 results in LTV:CAC ratio of 22.5x. Payback period of 5.3 months demonstrates strong capital efficiency.'
                    ],
                ]
            ]
        ],
        'ar' => [
            'text_content' => [
                'title' => 'متطلبات التمويل',
                'sections' => [
                    [
                        'heading' => 'هدف تمويل Series A',
                        'content' => 'البحث عن 3 ملايين دولار في تمويل Series A لتسريع تطوير المنتج وتوسيع الحضور في سوق مجلس التعاون الخليجي وبناء فريق المبيعات. يوضح ARR الحالي البالغ 1.8 مليون دولار توافق المنتج مع السوق وجاذبية العملاء القوية.'
                    ],
                    [
                        'heading' => 'استخدام الأموال',
                        'content' => 'تطوير المنتج (40٪): ميزات الذكاء الاصطناعي المتقدمة والتكاملات والتطبيق الجوال. المبيعات والتسويق (35٪): فريق المبيعات الإقليمي وحملات التسويق والشراكات. العمليات (15٪): توسيع البنية التحتية وتوسيع فريق الدعم. عام (10٪): القانونية والمالية والإدارة.'
                    ],
                    [
                        'heading' => 'جدول التمويل',
                        'content' => 'سيوفر تمويل Series A مدرج زمني بمدة 24 شهرًا للوصول إلى الربحية. استهدف تحقيق التعادل بحلول نهاية السنة 2 بناءً على مسار النمو المتوقع واقتصاديات الوحدة المحسنة.'
                    ],
                    [
                        'heading' => 'كفاءة رأس المال',
                        'content' => 'CAC البالغ 8000 دولار لكل عميل مع LTV لمدة 36 شهرًا بقيمة 180000 دولار ينتج نسبة LTV:CAC بقيمة 22.5x. فترة استرجاع الاستثمار 5.3 أشهر توضح كفاءة رأس المال القوية.'
                    ],
                ]
            ]
        ]
    ],

    'fin_unit_economics' => [
        'en' => [
            'key_value' => [
                'items' => [
                    ['key' => 'Average Contract Value', 'value' => '$15,000/year'],
                    ['key' => 'Customer Acquisition Cost', 'value' => '$8,000'],
                    ['key' => 'Customer Lifetime Value', 'value' => '$180,000'],
                    ['key' => 'LTV:CAC Ratio', 'value' => '22.5x'],
                    ['key' => 'Payback Period', 'value' => '5.3 months'],
                    ['key' => 'Annual Churn Rate', 'value' => '8%'],
                    ['key' => 'Net Revenue Retention', 'value' => '115%'],
                ]
            ]
        ],
        'ar' => [
            'key_value' => [
                'items' => [
                    ['key' => 'متوسط قيمة العقد', 'value' => '15000 دولار/سنة'],
                    ['key' => 'تكلفة اكتساب العملاء', 'value' => '8000 دولار'],
                    ['key' => 'قيمة عمر العميل', 'value' => '180000 دولار'],
                    ['key' => 'نسبة LTV:CAC', 'value' => '22.5x'],
                    ['key' => 'فترة الاسترجاع', 'value' => '5.3 أشهر'],
                    ['key' => 'معدل الخسارة السنوي', 'value' => '8%'],
                    ['key' => 'صافي الاحتفاظ بالإيرادات', 'value' => '115٪'],
                ]
            ]
        ]
    ],

    'gtm_launch_strategy' => [
        'en' => [
            'text_content' => [
                'title' => 'Go-to-Market Launch Strategy',
                'sections' => [
                    [
                        'heading' => 'Market Entry Strategy',
                        'content' => 'Launch first in Saudi Arabia with Riyadh and Jeddah as primary markets. Target mid-market to enterprise segment (500-5000+ employees) in technology, finance, and manufacturing sectors. Build brand as the GCC-native AI recruiting platform vs. Western competitors.'
                    ],
                    [
                        'heading' => 'Sales Approach',
                        'content' => 'Build direct sales team focused on enterprise customers with 6-9 month sales cycles. Implement freemium model for mid-market with land-and-expand strategy. Hire sales leaders with deep Saudi/GCC enterprise relationships. Target Fortune 500 GCC companies and local market leaders.'
                    ],
                    [
                        'heading' => 'Partnership Strategy',
                        'content' => 'Partner with HR consulting firms and management consultancies as channel partners. Create integrations with major HRIS systems (SAP SuccessFactors, Workday) and local payroll providers. Build reseller program for regional HR service providers.'
                    ],
                    [
                        'heading' => 'Marketing & Brand',
                        'content' => 'Position as innovative Saudi Arabian AI company solving GCC hiring challenges. Content marketing on HR transformation and AI adoption. Speaking at regional HR conferences and events. Build social proof through customer case studies and testimonials from respected enterprises.'
                    ],
                ]
            ]
        ],
        'ar' => [
            'text_content' => [
                'title' => 'استراتيجية إطلاق الدخول إلى السوق',
                'sections' => [
                    [
                        'heading' => 'استراتيجية الدخول إلى السوق',
                        'content' => 'الإطلاق أولاً في المملكة العربية السعودية مع الرياض وجدة كأسواق أساسية. استهدف قطاع منتصف السوق إلى المؤسسات (500-5000+ موظف) في قطاعات التكنولوجيا والمالية والتصنيع. بناء العلامة التجارية كمنصة التوظيف الأصلية لمجلس التعاون الخليجي بالذكاء الاصطناعي مقابل المنافسين الغربيين.'
                    ],
                    [
                        'heading' => 'نهج المبيعات',
                        'content' => 'بناء فريق مبيعات مباشر يركز على عملاء المؤسسات مع دورات مبيعات 6-9 أشهر. تنفيذ نموذج freemium لمنتصف السوق مع استراتيجية التوسع. توظيف قادة المبيعات ذوي العلاقات العميقة بمؤسسات السعودية ومجلس التعاون الخليجي. استهدف شركات Fortune 500 في مجلس التعاون الخليجي والقادة في السوق المحلية.'
                    ],
                    [
                        'heading' => 'استراتيجية الشراكة',
                        'content' => 'شراكة مع شركات استشارات الموارس البشرية والاستشارات الإدارية كشركاء قنوات. إنشاء تكاملات مع أنظمة HRIS الرئيسية (SAP SuccessFactors، Workday) ومزودي الرواتب المحليين. بناء برنامج بائع جديد لمزودي خدمات الموارس البشرية الإقليميين.'
                    ],
                    [
                        'heading' => 'التسويق والعلامة التجارية',
                        'content' => 'موضعها كشركة ذكاء اصطناعي سعودية مبتكرة تحل تحديات التوظيف في مجلس التعاون الخليجي. تسويق المحتوى حول تحول الموارس البشرية واعتماد الذكاء الاصطناعي. الحديث في مؤتمرات الموارس البشرية والفعاليات الإقليمية. بناء الإثبات الاجتماعي من خلال دراسات حالات العملاء والشهادات من المؤسسات المحترمة.'
                    ],
                ]
            ]
        ]
    ],

    'gtm_marketing_channels' => [
        'en' => [
            'stat_cards' => [
                'metrics' => [
                    ['label' => 'Content Marketing', 'value' => '25%', 'description' => 'Blog, whitepapers, case studies, webinars'],
                    ['label' => 'Direct Sales', 'value' => '35%', 'description' => 'Enterprise sales team, account executives'],
                    ['label' => 'Partnerships', 'value' => '20%', 'description' => 'Channel partners, integrations, resellers'],
                    ['label' => 'Events & PR', 'value' => '15%', 'description' => 'Conferences, speaking engagements, media'],
                    ['label' => 'Digital Marketing', 'value' => '5%', 'description' => 'LinkedIn, Google Ads, paid social'],
                ]
            ]
        ],
        'ar' => [
            'stat_cards' => [
                'metrics' => [
                    ['label' => 'تسويق المحتوى', 'value' => '25%', 'description' => 'مدونة ودراسات بيضاء ودراسات حالات وندوات عبر الويب'],
                    ['label' => 'المبيعات المباشرة', 'value' => '35%', 'description' => 'فريق مبيعات المؤسسات ومديرو الحسابات'],
                    ['label' => 'الشراكات', 'value' => '20%', 'description' => 'شركاء القنوات والتكاملات والبائعون'],
                    ['label' => 'الفعاليات والعلاقات العامة', 'value' => '15%', 'description' => 'المؤتمرات والمحاضرات والإعلام'],
                    ['label' => 'التسويق الرقمي', 'value' => '5%', 'description' => 'LinkedIn و Google Ads والوسائط الاجتماعية المدفوعة'],
                ]
            ]
        ]
    ],

    'gtm_sales_funnel' => [
        'en' => [
            'journey_timeline' => [
                'stages' => [
                    [
                        'title' => 'Awareness',
                        'description' => 'Marketing generates leads through content, events, and partnerships',
                        'touchpoints' => ['Company website', 'LinkedIn content', 'Industry events', 'Referrals'],
                        'actions' => ['Visit website', 'Download content', 'Attend event', 'Get referral']
                    ],
                    [
                        'title' => 'Engagement',
                        'description' => 'Qualified leads schedule demos and explore product capabilities',
                        'touchpoints' => ['Product demo', 'Free trial signup', 'Email campaigns', 'Sales call'],
                        'actions' => ['Request demo', 'Start trial', 'Respond to email', 'Schedule call']
                    ],
                    [
                        'title' => 'Evaluation',
                        'description' => 'Prospects evaluate pricing, implementation, and fit with requirements',
                        'touchpoints' => ['Pricing details', 'Implementation plan', 'References', 'ROI calculator'],
                        'actions' => ['Review pricing', 'Get implementation details', 'Talk to reference', 'Calculate ROI']
                    ],
                    [
                        'title' => 'Closure',
                        'description' => 'Contract negotiation and signing of enterprise agreement',
                        'touchpoints' => ['Legal review', 'Deal terms', 'Contract signing', 'Onboarding start'],
                        'actions' => ['Negotiate terms', 'Sign contract', 'Begin onboarding', 'Make first payment']
                    ]
                ]
            ]
        ],
        'ar' => [
            'journey_timeline' => [
                'stages' => [
                    [
                        'title' => 'الوعي',
                        'description' => 'التسويق يولد عملاء محتملين من خلال المحتوى والفعاليات والشراكات',
                        'touchpoints' => ['موقع الشركة', 'محتوى LinkedIn', 'فعاليات الصناعة', 'الإحالات'],
                        'actions' => ['زيارة الموقع', 'تنزيل المحتوى', 'حضور الفعالية', 'الحصول على إحالة']
                    ],
                    [
                        'title' => 'المشاركة',
                        'description' => 'يجدول العملاء المحتملون المؤهلون عروضًا توضيحية واستكشاف إمكانيات المنتج',
                        'touchpoints' => ['عرض توضيحي للمنتج', 'التسجيل للتجربة المجانية', 'حملات البريد الإلكتروني', 'مكالمة المبيعات'],
                        'actions' => ['طلب عرض توضيحي', 'ابدأ التجربة', 'الرد على البريد الإلكتروني', 'جدولة مكالمة']
                    ],
                    [
                        'title' => 'التقييم',
                        'description' => 'يقيم العملاء المحتملون التسعير والتنفيذ والملاءمة مع المتطلبات',
                        'touchpoints' => ['تفاصيل التسعير', 'خطة التنفيذ', 'المراجع', 'حاسبة العائد على الاستثمار'],
                        'actions' => ['مراجعة التسعير', 'الحصول على تفاصيل التنفيذ', 'التحدث إلى مرجع', 'حساب العائد على الاستثمار']
                    ],
                    [
                        'title' => 'الإغلاق',
                        'description' => 'مفاوضات العقد وتوقيع اتفاقية المؤسسة',
                        'touchpoints' => ['المراجعة القانونية', 'شروط التعامل', 'توقيع العقد', 'بدء الإعداد'],
                        'actions' => ['التفاوض على الشروط', 'توقيع العقد', 'بدء الإعداد', 'الدفع الأول']
                    ]
                ]
            ]
        ]
    ],

    'gtm_partnerships' => [
        'en' => [
            'text_content' => [
                'title' => 'Strategic Partnerships',
                'sections' => [
                    [
                        'heading' => 'Technology Partnerships',
                        'content' => 'Integration partnerships with HRIS vendors (SAP SuccessFactors, Workday, BambooHR) to extend market reach. Integration with recruitment platforms (LinkedIn Recruiter, Indeed) for enhanced candidate sourcing. Cloud infrastructure partnerships (AWS, Azure) for reliable scaling.'
                    ],
                    [
                        'heading' => 'Consulting & Services',
                        'content' => 'Partner with top HR consulting firms as channel partners to reach enterprise clients. Training and certification programs for implementation partners. Revenue sharing model to incentivize partner sales efforts and growth.'
                    ],
                    [
                        'heading' => 'Industry Associations',
                        'content' => 'Membership in Saudi Arabia HR Association and GCC Business Councils to gain credibility. Sponsorship of HR conferences and talent management summits. Thought leadership positions in industry publications.'
                    ],
                    [
                        'heading' => 'Academic & Research',
                        'content' => 'Partnerships with universities for talent pipeline and research collaborations. Sponsorship of HR research studies to build brand awareness. Case studies and research publications to establish thought leadership.'
                    ],
                ]
            ]
        ],
        'ar' => [
            'text_content' => [
                'title' => 'الشراكات الاستراتيجية',
                'sections' => [
                    [
                        'heading' => 'شراكات التكنولوجيا',
                        'content' => 'شراكات التكامل مع بائعي HRIS (SAP SuccessFactors، Workday، BambooHR) لتوسيع نطاق السوق. التكامل مع منصات التوظيف (LinkedIn Recruiter، Indeed) للبحث عن المرشحين المحسّن. شراكات البنية التحتية السحابية (AWS، Azure) للتوسع الموثوق.'
                    ],
                    [
                        'heading' => 'الاستشارات والخدمات',
                        'content' => 'شراكة مع أفضل شركات استشارات الموارس البشرية كشركاء قنوات للوصول إلى عملاء المؤسسات. برامج التدريب والشهادات لشركاء التنفيذ. نموذج تقاسم الإيرادات لتحفيز جهود المبيعات والنمو.'
                    ],
                    [
                        'heading' => 'جمعيات الصناعة',
                        'content' => 'العضوية في جمعية الموارس البشرية بالمملكة العربية السعودية ومجالس الأعمال بمجلس التعاون الخليجي للحصول على المصداقية. رعاية مؤتمرات الموارس البشرية وقمم إدارة المواهب. مواقف قيادة الفكر في منشورات الصناعة.'
                    ],
                    [
                        'heading' => 'الأكاديمية والأبحاث',
                        'content' => 'الشراكات مع الجامعات لخط أنابيب المواهب والتعاون في البحث. رعاية دراسات البحث في الموارس البشرية لبناء الوعي بالعلامة التجارية. دراسات الحالات ومنشورات البحث لتأسيس قيادة الفكر.'
                    ],
                ]
            ]
        ]
    ],

    'gtm_growth_metrics' => [
        'en' => [
            'progress_bars' => [
                'items' => [
                    ['label' => 'Customer Acquisition Growth', 'value' => 85, 'suffix' => '%'],
                    ['label' => 'Net Revenue Retention', 'value' => 115, 'suffix' => '%'],
                    ['label' => 'Product Usage Adoption', 'value' => 78, 'suffix' => '%'],
                    ['label' => 'Customer Satisfaction (NPS)', 'value' => 72, 'suffix' => '%'],
                    ['label' => 'Market Penetration in Target', 'value' => 12, 'suffix' => '%'],
                ]
            ]
        ],
        'ar' => [
            'progress_bars' => [
                'items' => [
                    ['label' => 'نمو اكتساب العملاء', 'value' => 85, 'suffix' => '٪'],
                    ['label' => 'صافي الاحتفاظ بالإيرادات', 'value' => 115, 'suffix' => '٪'],
                    ['label' => 'اعتماد استخدام المنتج', 'value' => 78, 'suffix' => '٪'],
                    ['label' => 'رضا العملاء (NPS)', 'value' => 72, 'suffix' => '٪'],
                    ['label' => 'اختراق السوق في الهدف', 'value' => 12, 'suffix' => '٪'],
                ]
            ]
        ]
    ],

    'ca_competitor_overview' => [
        'en' => [
            'text_content' => [
                'title' => 'Competitive Analysis Overview',
                'sections' => [
                    [
                        'heading' => 'Direct Competitors',
                        'content' => 'Workable (Ireland): General recruitment platform with basic AI, limited Arabic support, established in GCC but generic approach. Lever (USA): Enterprise recruiting tool, strong in tech sector, limited performance management. Ashby (USA): Modern ATS with good UX, growing GCC presence, minimal AI capabilities.'
                    ],
                    [
                        'heading' => 'Indirect Competitors',
                        'content' => 'LinkedIn Recruiter: Strong network effects, expensive for SMB, limited integration with internal systems. SAP SuccessFactors: Enterprise HRIS with talent module, lacks modern UX, AI features limited. Workday: Comprehensive HR platform, slow to innovate, implementation complexity.'
                    ],
                    [
                        'heading' => 'Market Position',
                        'content' => 'Connect AI uniquely combines GCC-specific expertise, advanced AI capabilities, and integrated talent management. Competitors either focus on recruitment (lacking performance management) or are HRIS-centric (lacking recruitment focus). No direct competitor combines all three elements with GCC localization.'
                    ],
                    [
                        'heading' => 'Competitive Advantages',
                        'content' => 'Native Arabic support with cultural understanding. Regional team with deep GCC enterprise knowledge. Purpose-built AI trained on GCC hiring data. Integrated lifecycle approach vs. point solutions. Superior user experience for modern workforce. Flexible pricing for mid-market segment.'
                    ],
                ]
            ]
        ],
        'ar' => [
            'text_content' => [
                'title' => 'نظرة عامة على التحليل التنافسي',
                'sections' => [
                    [
                        'heading' => 'المنافسون المباشرون',
                        'content' => 'Workable (أيرلندا): منصة توظيف عامة مع ذكاء اصطناعي أساسي، دعم عربي محدود، راسخة في مجلس التعاون الخليجي لكن نهج عام. Lever (الولايات المتحدة): أداة توظيف المؤسسات، قوية في قطاع التكنولوجيا، إدارة الأداء محدودة. Ashby (الولايات المتحدة): ATS حديث مع UX جيد، حضور متزايد في مجلس التعاون الخليجي، قدرات الذكاء الاصطناعي ضئيلة.'
                    ],
                    [
                        'heading' => 'المنافسون غير المباشرين',
                        'content' => 'LinkedIn Recruiter: آثار شبكة قوية، مكلفة للشركات الصغيرة والمتوسطة، تكامل محدود مع الأنظمة الداخلية. SAP SuccessFactors: HRIS المؤسسة مع وحدة الموهبة، تفتقد UX الحديثة، ميزات الذكاء الاصطناعي محدودة. Workday: منصة موارد بشرية شاملة، بطيئة في الابتكار، تعقيد التنفيذ.'
                    ],
                    [
                        'heading' => 'موقف السوق',
                        'content' => 'يجمع Connect AI بشكل فريد بين الخبرة الخاصة بمجلس التعاون الخليجي وقدرات الذكاء الاصطناعي المتقدمة وإدارة الموهبة المتكاملة. يركز المنافسون إما على التوظيف (افتقار إدارة الأداء) أو يركزون على HRIS (افتقار التركيز على التوظيف). لا يوجد منافس مباشر يجمع بين جميع العناصر الثلاثة مع التوطين في مجلس التعاون الخليجي.'
                    ],
                    [
                        'heading' => 'المزايا التنافسية',
                        'content' => 'دعم عربي أصلي مع فهم ثقافي. فريق إقليمي لديه معرفة عميقة بمؤسسات مجلس التعاون الخليجي. ذكاء اصطناعي مصمم خصيصًا مدرب على بيانات التوظيف في مجلس التعاون الخليجي. نهج دورة حياة متكامل مقابل الحلول النقطية. تجربة مستخدم متفوقة للقوى العاملة الحديثة. تسعير مرن لقطاع منتصف السوق.'
                    ],
                ]
            ]
        ]
    ],

    'ca_feature_comparison' => [
        'en' => [
            'comparison_table' => [
                'headers' => ['Feature Category', 'Connect AI', 'Workable', 'Lever', 'Ashby', 'SAP SuccessFactors'],
                'rows' => [
                    ['AI Resume Screening', 'Advanced', 'Basic', 'Moderate', 'Basic', 'Limited'],
                    ['Performance Management', 'Full Suite', 'Limited', 'None', 'None', 'Full Suite'],
                    ['Arabic Language Support', 'Native', 'Basic', 'None', 'None', 'Basic'],
                    ['GCC-Specific Features', 'Yes', 'No', 'No', 'No', 'No'],
                    ['Interview Scheduling', 'Automated', 'Manual', 'Automated', 'Manual', 'Manual'],
                    ['Candidate Analytics', 'Predictive', 'Basic', 'Basic', 'Limited', 'Basic'],
                    ['API & Integrations', 'Extensive', 'Moderate', 'Extensive', 'Limited', 'Extensive'],
                    ['User Experience', 'Modern', 'Outdated', 'Modern', 'Modern', 'Outdated'],
                    ['Pricing Flexibility', 'High', 'Moderate', 'Low', 'Moderate', 'Low'],
                ]
            ]
        ],
        'ar' => [
            'comparison_table' => [
                'headers' => ['فئة الميزة', 'Connect AI', 'Workable', 'Lever', 'Ashby', 'SAP SuccessFactors'],
                'rows' => [
                    ['فحص السيرة الذاتية بالذكاء الاصطناعي', 'متقدم', 'أساسي', 'معتدل', 'أساسي', 'محدود'],
                    ['إدارة الأداء', 'مجموعة كاملة', 'محدود', 'لا شيء', 'لا شيء', 'مجموعة كاملة'],
                    ['دعم اللغة العربية', 'أصلي', 'أساسي', 'لا', 'لا', 'أساسي'],
                    ['ميزات خاصة بمجلس التعاون الخليجي', 'نعم', 'لا', 'لا', 'لا', 'لا'],
                    ['جدولة المقابلات', 'آلي', 'يدوي', 'آلي', 'يدوي', 'يدوي'],
                    ['تحليلات المرشحين', 'تنبؤي', 'أساسي', 'أساسي', 'محدود', 'أساسي'],
                    ['API والتكاملات', 'واسع', 'معتدل', 'واسع', 'محدود', 'واسع'],
                    ['تجربة المستخدم', 'حديث', 'قديم', 'حديث', 'حديث', 'قديم'],
                    ['مرونة التسعير', 'مرتفع', 'معتدل', 'منخفض', 'معتدل', 'منخفض'],
                ]
            ]
        ]
    ],

    'ca_market_positioning' => [
        'en' => [
            'key_value' => [
                'items' => [
                    ['key' => 'Primary Market', 'value' => 'GCC Enterprises (Mid-Market to Enterprise)'],
                    ['key' => 'Target Industries', 'value' => 'Technology, Finance, Manufacturing, Consulting'],
                    ['key' => 'Positioning', 'value' => 'The AI-Native Talent Platform Built for GCC'],
                    ['key' => 'Key Differentiator', 'value' => 'GCC-Specific AI + Integrated Talent Lifecycle'],
                    ['key' => 'Price Point', 'value' => '$299-$899/month SaaS + Enterprise Custom'],
                    ['key' => 'Sales Model', 'value' => 'Direct Enterprise Sales + Channel Partnerships'],
                    ['key' => 'Market Opportunity', 'value' => '$2.3B TAM in GCC region'],
                ]
            ]
        ],
        'ar' => [
            'key_value' => [
                'items' => [
                    ['key' => 'السوق الأساسي', 'value' => 'مؤسسات مجلس التعاون الخليجي (منتصف السوق إلى المؤسسة)'],
                    ['key' => 'الصناعات المستهدفة', 'value' => 'التكنولوجيا والمالية والتصنيع والاستشارات'],
                    ['key' => 'الموضعة', 'value' => 'منصة الموهبة الأصلية بالذكاء الاصطناعي المبنية لمجلس التعاون الخليجي'],
                    ['key' => 'المتمايزة الرئيسية', 'value' => 'ذكاء اصطناعي خاص بمجلس التعاون الخليجي + دورة حياة الموهبة المتكاملة'],
                    ['key' => 'نقطة السعر', 'value' => '299-899 دولار/شهر SaaS + مخصص المؤسسات'],
                    ['key' => 'نموذج المبيعات', 'value' => 'مبيعات المؤسسات المباشرة + شراكات القنوات'],
                    ['key' => 'فرصة السوق', 'value' => '2.3 مليار دولار TAM في منطقة مجلس التعاون الخليجي'],
                ]
            ]
        ]
    ],

    'ca_competitive_moat' => [
        'en' => [
            'text_content' => [
                'title' => 'Competitive Moat & Defensibility',
                'sections' => [
                    [
                        'heading' => 'Data Network Effect',
                        'content' => 'As Connect AI processes more hiring decisions in GCC market, AI models become increasingly accurate and valuable. Proprietary dataset of GCC hiring patterns creates network effect that competitors cannot replicate. First-mover advantage in GCC-trained AI models.'
                    ],
                    [
                        'heading' => 'Switching Costs & Customer Lock-in',
                        'content' => 'Historical hiring data stored in Connect AI platform becomes valuable organizational asset. Integration with existing HRIS and recruitment workflows increases switching costs. Performance metrics and analytics dependent on continuous data accumulation in platform.'
                    ],
                    [
                        'heading' => 'Brand & Regional Expertise',
                        'content' => 'Deep understanding of GCC labor laws, cultural values, and business practices hard to replicate. Team with 15+ years of direct GCC enterprise experience. Brand positioning as "Saudi Arabian AI company for Saudis" creates local loyalty and trust.'
                    ],
                    [
                        'heading' => 'Technology & Product Innovation',
                        'content' => 'Continuous investment in AI capabilities keeps product ahead of competitors. Integrated talent lifecycle (recruitment + performance) vs. point solutions. User experience designed specifically for GCC workforce expectations. Product roadmap driven by local customer feedback.'
                    ],
                ]
            ]
        ],
        'ar' => [
            'text_content' => [
                'title' => 'الخندق التنافسي والقابلية للدفاع',
                'sections' => [
                    [
                        'heading' => 'تأثير شبكة البيانات',
                        'content' => 'مع قيام Connect AI بمعالجة المزيد من قرارات التوظيف في سوق مجلس التعاون الخليجي، تصبح نماذج الذكاء الاصطناعي أكثر دقة وقيمة. تنشئ مجموعة البيانات الملكية لأنماط التوظيف في مجلس التعاون الخليجي تأثير شبكة لا يمكن للمنافسين تكراره. ميزة الدخول الأول في نماذج الذكاء الاصطناعي المدربة على مجلس التعاون الخليجي.'
                    ],
                    [
                        'heading' => 'تكاليف التبديل وقفل العميل',
                        'content' => 'تصبح البيانات التاريخية للتوظيف المخزنة في منصة Connect AI أصلًا تنظيميًا قيمًا. يزيد التكامل مع سير عمل HRIS والتوظيف الموجود من تكاليف التبديل. تعتمد مقاييس الأداء والتحليلات على التراكم المستمر للبيانات في المنصة.'
                    ],
                    [
                        'heading' => 'العلامة التجارية والخبرة الإقليمية',
                        'content' => 'الفهم العميق لقوانين العمل في مجلس التعاون الخليجي والقيم الثقافية والممارسات التجارية يصعب تكراره. فريق لديه 15+ سنة من الخبرة المباشرة مع مؤسسات مجلس التعاون الخليجي. وضع العلامة التجارية باعتبارها "شركة ذكاء اصطناعي سعودية للسعوديين" ينشئ الولاء والثقة المحلية.'
                    ],
                    [
                        'heading' => 'الابتكار التكنولوجي والمنتج',
                        'content' => 'الاستثمار المستمر في قدرات الذكاء الاصطناعي يبقي المنتج متقدمًا على المنافسين. دورة حياة الموهبة المتكاملة (التوظيف + الأداء) مقابل الحلول النقطية. تجربة مستخدم مصممة خصيصًا لتوقعات القوى العاملة في مجلس التعاون الخليجي. خريطة الطريق للمنتج مدفوعة بملاحظات العملاء المحليين.'
                    ],
                ]
            ]
        ]
    ]
], 'competitors' => [['name' => 'LinkedIn Recruiter', 'website' => 'linkedin.com/recruiter', 'description' => 'Global leader, 900M+ users. High cost, limited MENA AI features.', 'description_ar' => 'قائد عالمي بـ 900M+ مستخدم. تكلفة عالية جدًا، ميزات ذكاء اصطناعي محدودة.', 'strengths' => ['Massive base', 'Brand', 'Integrations'], 'weaknesses' => ['Very expensive', 'Limited MENA', 'Generic']], ['name' => 'GulfTalent', 'website' => 'gulftalent.com', 'description' => 'Established regional platform. Manual, no AI, slow hiring.', 'description_ar' => 'منصة إقليمية راسخة. يدوية وبدون ذكاء اصطناعي وتوظيف بطيء.', 'strengths' => ['Brand', 'Relationships', 'Large base'], 'weaknesses' => ['No AI', 'Slow', 'Basic']], ['name' => 'TalentMatch UAE', 'website' => 'talentmatch.ae', 'description' => 'Local startup with basic matching. Weak AI, limited scalability.', 'description_ar' => 'شركة ناشئة محلية بمطابقة أساسية. ذكاء اصطناعي ضعيف وقابلية توسع محدودة.', 'strengths' => ['Local', 'Cheap', 'Support'], 'weaknesses' => ['Weak tech', 'Limited features', 'Not scalable']]], 'prompts' => [['section_key' => 'dashboard_executive_summary', 'template' => 'Highlight hiring efficiency (60% time reduction), AI screening (94% accuracy), MENA 3.5M+ annual recruits opportunity.', 'template_ar' => 'أبرز كفاءة التوظيف (تقليل 60%)، فحص الذكاء الاصطناعي (دقة 94%)، فرصة التوظيف السنوية.'], ['section_key' => 'sf_market_size', 'template' => 'Calculate MENA recruitment market. Include hiring costs, annual volume, addressable market for AI solutions.', 'template_ar' => 'احسب سوق التوظيف. قم بتضمين التكاليف والحجم والسوق القابل للمعالجة.']]];
    }

    private function getBoudPlatformData(): array
    {
        return ['name' => 'Boud Platform', 'name_ar' => 'منصة بود', 'description' => 'AI-driven digital transformation consulting platform providing innovation management and product delivery services to government and enterprises', 'description_ar' => 'منصة استشارات التحول الرقمي المحسّنة بالذكاء الاصطناعي لإدارة الابتكار والتسليم', 'industry' => 'AI / Digital Transformation', 'industry_ar' => 'الذكاء الاصطناعي / التحول الرقمي', 'viability_score' => 79, 'sections' => ['dashboard_viability_score' => ['en' => ['overall' => 79, 'dimensions' => [['label' => 'Market Opportunity', 'score' => 85], ['label' => 'Product-Market Fit', 'score' => 78], ['label' => 'Business Model', 'score' => 80], ['label' => 'Team & Execution', 'score' => 76], ['label' => 'Financial Viability', 'score' => 77]]], 'ar' => ['overall' => 79, 'dimensions' => [['label' => 'فرصة السوق', 'score' => 85], ['label' => 'توافق المنتج', 'score' => 78], ['label' => 'نموذج الأعمال', 'score' => 80], ['label' => 'الفريق والتنفيذ', 'score' => 76], ['label' => 'الجدوى المالية', 'score' => 77]]]], 'dashboard_executive_summary' => ['en' => ['title' => 'Executive Summary', 'summary' => 'AI-driven digital transformation consulting platform providing innovation management, product strategy, delivery services to Saudi government, enterprises, institutions across MENA.', 'sections' => [['heading' => 'Market Opportunity', 'content' => 'GCC digital spending $45B+ at 22% growth. Vision 2030 mandates digital transformation. Enterprise digital budgets avg $2-5M annually. 3K+ government agencies and 50K+ enterprises in MENA need services.'], ['heading' => 'Solution', 'content' => 'End-to-end digital transformation: strategy consulting, AI implementation, innovation program management (Hackify, SALIS, Connect AI), agile delivery, change management.'], ['heading' => 'Traction', 'content' => '15+ Saudi government entities (GOSI, MOHE). 50+ enterprise clients. $5.2M consulting revenue. Managing $100M+ transformation initiatives. Strong government reputation.'], ['heading' => 'Business Model', 'content' => 'Consulting: T&M and fixed projects ($200K-$2M). Product licensing: Hackify, SALIS, Connect AI ($50K-$500K/year). Retainer: $5K-$50K/month. Partnership revenue sharing.']]],  'ar' => ['title' => 'الملخص التنفيذي', 'summary' => 'منصة استشارات التحول الرقمي توفر إدارة الابتكار والتسليم للحكومة والمؤسسات والمؤسسات', 'sections' => [['heading' => 'فرصة السوق', 'content' => 'إنفاق الخليج الرقمي $45B+ بنمو 22%. رؤية 2030 تفرض التحول. ميزانيات المؤسسات $2-5M سنويًا. 3K+ وكالة وحكومة و 50K+ مؤسسة.'], ['heading' => 'الحل', 'content' => 'تحول رقمي شامل: استشارة إستراتيجية وتنفيذ الذكاء الاصطناعي وإدارة برامج الابتكار والتسليم المرن.'], ['heading' => 'الجذب', 'content' => '15+ كيان حكومي سعودي. 50+ عميل مؤسسي. $5.2M إيراد استشارات. إدارة $100M+ مبادرات.'], ['heading' => 'نموذج الأعمال', 'content' => 'استشارات: T&M والمشاريع ($200K-$2M). ترخيص المنتجات: ($50K-$500K/سنة). اشتراك: $5K-$50K/شهر.']]],  'dashboard_key_metrics' => ['en' => [['label' => 'Consulting Revenue', 'value' => '$5.2M', 'description' => 'Annual from consulting and services', 'trend' => '+25% YoY'], ['label' => 'Active Customers', 'value' => '50+', 'description' => 'Government and enterprise clients', 'trend' => '+8 YoY'], ['label' => 'Transformation Budget Managed', 'value' => '$100M+', 'description' => 'Digital initiatives under management', 'trend' => '+45% YoY'], ['label' => 'Team Size', 'value' => '80+', 'description' => 'Consultants, engineers, strategists', 'trend' => '+15 YoY']], 'ar' => [['label' => 'إيراد الاستشارات', 'value' => '5.2 مليون دولار', 'description' => 'إيراد سنوي من الاستشارات', 'trend' => '+25% سنويًا'], ['label' => 'العملاء النشطون', 'value' => '50+', 'description' => 'الكيانات الحكومية والمؤسسية', 'trend' => '+8 سنويًا'], ['label' => 'ميزانية التحول المدارة', 'value' => '100+ مليون دولار', 'description' => 'مبادرات المتحكم فيها', 'trend' => '+45% سنويًا'], ['label' => 'حجم الفريق', 'value' => '80+', 'description' => 'المستشارون والمهندسون والاستراتيجيون', 'trend' => '+15 سنويًا']],

    'mvp_feature_priority' => [
        'en' => [
            'comparison_table' => [
                'headers' => ['Feature', 'Priority', 'Impact', 'Timeline (Weeks)'],
                'rows' => [
                    ['AI-Powered Transformation Assessment', 'Critical', 'High', '4-6'],
                    ['Digital Maturity Dashboard', 'Critical', 'High', '6-8'],
                    ['Innovation Roadmap Generator', 'High', 'High', '5-7'],
                    ['Consulting Project Management', 'High', 'Medium', '4-5'],
                    ['Vision 2030 Compliance Framework', 'High', 'High', '6-8'],
                    ['Real-Time Analytics & Reporting', 'Medium', 'High', '7-9'],
                    ['Team Collaboration Portal', 'Medium', 'Medium', '3-4'],
                    ['Integration Marketplace', 'Medium', 'Medium', '8-10'],
                ]
            ]
        ],
        'ar' => [
            'comparison_table' => [
                'headers' => ['الميزة', 'الأولوية', 'التأثير', 'الجدول الزمني (أسابيع)'],
                'rows' => [
                    ['تقييم التحول الرقمي بتقنية الذكاء الاصطناعي', 'حرج', 'عالي', '4-6'],
                    ['لوحة معلومات النضج الرقمي', 'حرج', 'عالي', '6-8'],
                    ['مولد خريطة الطريق للابتكار', 'عالي', 'عالي', '5-7'],
                    ['إدارة مشاريع الاستشارات', 'عالي', 'متوسط', '4-5'],
                    ['إطار العمل لرؤية 2030', 'عالي', 'عالي', '6-8'],
                    ['التحليلات والتقارير في الوقت الفعلي', 'متوسط', 'عالي', '7-9'],
                    ['بوابة التعاون بين الفرق', 'متوسط', 'متوسط', '3-4'],
                    ['سوق التكاملات', 'متوسط', 'متوسط', '8-10'],
                ]
            ]
        ]
    ],

    'mvp_development_roadmap' => [
        'en' => [
            'journey_timeline' => [
                'stages' => [
                    [
                        'title' => 'Phase 1: Foundation (Weeks 1-6)',
                        'description' => 'Core platform architecture and AI assessment engine',
                        'touchpoints' => ['Team onboarding', 'Technology stack finalization', 'Database design', 'API framework setup'],
                        'actions' => ['Establish dev environment', 'Create CI/CD pipeline', 'Build core assessment module', 'Initial testing framework']
                    ],
                    [
                        'title' => 'Phase 2: Core Features (Weeks 7-14)',
                        'description' => 'Digital maturity dashboard and roadmap generator',
                        'touchpoints' => ['Frontend development', 'AI model integration', 'Dashboard design', 'User testing begins'],
                        'actions' => ['Build dashboard UI', 'Integrate AI models', 'Create roadmap generation logic', 'Beta user onboarding']
                    ],
                    [
                        'title' => 'Phase 3: Enhancement (Weeks 15-20)',
                        'description' => 'Vision 2030 compliance and analytics',
                        'touchpoints' => ['Compliance module launch', 'Advanced analytics', 'Performance optimization', 'Security hardening'],
                        'actions' => ['Implement compliance checks', 'Add analytics engine', 'Performance tuning', 'Security audit']
                    ],
                    [
                        'title' => 'Phase 4: Launch & Scale (Weeks 21-24)',
                        'description' => 'Production deployment and market release',
                        'touchpoints' => ['Production environment', 'Monitoring setup', 'Marketing campaign', 'Customer support training'],
                        'actions' => ['Deploy to production', 'Set up monitoring', 'Launch marketing', 'Begin customer onboarding']
                    ]
                ]
            ]
        ],
        'ar' => [
            'journey_timeline' => [
                'stages' => [
                    [
                        'title' => 'المرحلة 1: الأساس (الأسابيع 1-6)',
                        'description' => 'هندسة المنصة الأساسية ومحرك تقييم الذكاء الاصطناعي',
                        'touchpoints' => ['إعداد الفريق', 'إنهاء المكدس التكنولوجي', 'تصميم قاعدة البيانات', 'إعداد إطار العمل API'],
                        'actions' => ['إنشاء بيئة التطوير', 'إنشاء خط أنابيب CI/CD', 'بناء وحدة التقييم الأساسية', 'إطار الاختبار الأولي']
                    ],
                    [
                        'title' => 'المرحلة 2: الميزات الأساسية (الأسابيع 7-14)',
                        'description' => 'لوحة معلومات النضج الرقمي ومولد خريطة الطريق',
                        'touchpoints' => ['تطوير واجهة المستخدم', 'تكامل نماذج الذكاء الاصطناعي', 'تصميم لوحة المعلومات', 'بدء اختبار المستخدم'],
                        'actions' => ['بناء واجهة لوحة المعلومات', 'دمج نماذج الذكاء الاصطناعي', 'إنشاء منطق توليد خريطة الطريق', 'إدراج المستخدمين التجريبيين']
                    ],
                    [
                        'title' => 'المرحلة 3: التحسين (الأسابيع 15-20)',
                        'description' => 'رؤية 2030 والامتثال والتحليلات المتقدمة',
                        'touchpoints' => ['إطلاق وحدة الامتثال', 'التحليلات المتقدمة', 'تحسين الأداء', 'تقوية الأمان'],
                        'actions' => ['تطبيق فحوصات الامتثال', 'إضافة محرك التحليلات', 'ضبط الأداء', 'تدقيق الأمان']
                    ],
                    [
                        'title' => 'المرحلة 4: الإطلاق والتوسع (الأسابيع 21-24)',
                        'description' => 'نشر الإنتاج وإطلاق السوق',
                        'touchpoints' => ['بيئة الإنتاج', 'إعداد المراقبة', 'حملة التسويق', 'تدريب دعم العملاء'],
                        'actions' => ['النشر للإنتاج', 'إعداد المراقبة', 'إطلاق التسويق', 'بدء إدراج العملاء']
                    ]
                ]
            ]
        ]
    ],

    'mvp_tech_stack' => [
        'en' => [
            'key_value' => [
                'items' => [
                    ['key' => 'Backend', 'value' => 'Laravel 10, Node.js with Express'],
                    ['key' => 'Frontend', 'value' => 'React 18, Vue.js 3'],
                    ['key' => 'Database', 'value' => 'PostgreSQL, MongoDB'],
                    ['key' => 'AI/ML', 'value' => 'TensorFlow, OpenAI API, LangChain'],
                    ['key' => 'Cloud Infrastructure', 'value' => 'AWS, Microsoft Azure'],
                    ['key' => 'DevOps', 'value' => 'Docker, Kubernetes, GitHub Actions'],
                    ['key' => 'Analytics', 'value' => 'Mixpanel, DataDog'],
                    ['key' => 'Real-time Communication', 'value' => 'WebSocket, Socket.io'],
                ]
            ]
        ],
        'ar' => [
            'key_value' => [
                'items' => [
                    ['key' => 'الخادم الخلفي', 'value' => 'Laravel 10، Node.js مع Express'],
                    ['key' => 'واجهة المستخدم', 'value' => 'React 18، Vue.js 3'],
                    ['key' => 'قاعدة البيانات', 'value' => 'PostgreSQL، MongoDB'],
                    ['key' => 'الذكاء الاصطناعي والتعلم الآلي', 'value' => 'TensorFlow، OpenAI API، LangChain'],
                    ['key' => 'البنية التحتية السحابية', 'value' => 'AWS، Microsoft Azure'],
                    ['key' => 'DevOps', 'value' => 'Docker، Kubernetes، GitHub Actions'],
                    ['key' => 'التحليلات', 'value' => 'Mixpanel، DataDog'],
                    ['key' => 'الاتصال في الوقت الفعلي', 'value' => 'WebSocket، Socket.io'],
                ]
            ]
        ]
    ],

    'mvp_resource_requirements' => [
        'en' => [
            'stat_cards' => [
                'metrics' => [
                    ['label' => 'Development Team', 'value' => '12', 'description' => 'Backend, Frontend, QA, DevOps engineers'],
                    ['label' => 'AI/ML Specialists', 'value' => '3', 'description' => 'ML engineers, Data scientists'],
                    ['label' => 'Design & UX', 'value' => '2', 'description' => 'UI/UX designers'],
                    ['label' => 'Product Manager', 'value' => '1', 'description' => 'Product strategy and roadmap'],
                    ['label' => 'Budget (USD)', 'value' => '450K', 'description' => 'Total 6-month MVP development'],
                    ['label' => 'Infrastructure Cost', 'value' => '15K', 'description' => 'Monthly cloud and AI API costs'],
                ]
            ]
        ],
        'ar' => [
            'stat_cards' => [
                'metrics' => [
                    ['label' => 'فريق التطوير', 'value' => '12', 'description' => 'مهندسو الخادم الخلفي، الواجهة الأمامية، ضمان الجودة، DevOps'],
                    ['label' => 'متخصصو الذكاء الاصطناعي والتعلم الآلي', 'value' => '3', 'description' => 'مهندسو التعلم الآلي، علماء البيانات'],
                    ['label' => 'التصميم وتجربة المستخدم', 'value' => '2', 'description' => 'مصممو واجهة المستخدم / تجربة المستخدم'],
                    ['label' => 'مدير المنتج', 'value' => '1', 'description' => 'استراتيجية المنتج وخريطة الطريق'],
                    ['label' => 'الميزانية (USD)', 'value' => '450K', 'description' => 'إجمالي تطوير MVP لمدة 6 أشهر'],
                    ['label' => 'تكلفة البنية التحتية', 'value' => '15K', 'description' => 'تكاليف الخدمات السحابية و API الشهرية'],
                ]
            ]
        ]
    ],

    'mvp_risk_mitigation' => [
        'en' => [
            'text_content' => [
                'title' => 'MVP Risk Mitigation Strategy',
                'sections' => [
                    [
                        'heading' => 'Technical Risk Management',
                        'content' => 'Mitigate AI model accuracy risks through extensive testing and validation against Saudi market scenarios. Implement robust API fallbacks and circuit breakers. Maintain multiple cloud provider relationships to avoid vendor lock-in. Regular security audits and penetration testing to address cybersecurity threats.'
                    ],
                    [
                        'heading' => 'Market & Adoption Risk',
                        'content' => 'Conduct early market validation through pilot programs with select Saudi enterprises. Establish advisory board of Saudi decision-makers. Build flexible pricing models that align with Vision 2030 initiatives. Create localized marketing materials in Arabic targeting government and private sectors.'
                    ],
                    [
                        'heading' => 'Resource & Timeline Risk',
                        'content' => 'Build contingency planning into project timeline with 20% buffer for critical phases. Maintain backup resources for key technical roles. Implement agile methodology with bi-weekly sprints to enable course correction. Establish clear success metrics for each phase gate.'
                    ],
                    [
                        'heading' => 'Regulatory & Compliance Risk',
                        'content' => 'Engage with Saudi regulatory bodies early for data protection and AI governance compliance. Implement data residency requirements for Saudi market. Build compliance frameworks aligned with MISA guidelines. Maintain audit trails and documentation for all AI decision-making processes.'
                    ]
                ]
            ]
        ],
        'ar' => [
            'text_content' => [
                'title' => 'استراتيجية تخفيف المخاطر للمنتج الحد الأدنى القابل للتطبيق',
                'sections' => [
                    [
                        'heading' => 'إدارة المخاطر التقنية',
                        'content' => 'تخفيف مخاطر دقة نموذج الذكاء الاصطناعي من خلال الاختبار الشامل والتحقق من سيناريوهات السوق السعودي. تطبيق آليات الرجوع القوية للواجهات البرمجية وقواطع الدوائر. الحفاظ على علاقات موفري الخدمات السحابية المتعددة لتجنب الارتباط بالبائع. عمليات التدقيق الأمني المنتظمة واختبار الاختراق لمعالجة تهديدات الأمن السيبراني.'
                    ],
                    [
                        'heading' => 'مخاطر السوق والتبني',
                        'content' => 'إجراء التحقق المبكر من السوق من خلال برامج التجريب مع الشركات السعودية المختارة. إنشاء مجلس استشاري من صناع القرار السعوديين. بناء نماذج تسعير مرنة تتوافق مع مبادرات رؤية 2030. إنشاء مواد تسويقية محلية باللغة العربية تستهدف القطاعات الحكومية والخاصة.'
                    ],
                    [
                        'heading' => 'مخاطر الموارد والجدول الزمني',
                        'content' => 'بناء التخطيط الطارئ في الجدول الزمني للمشروع مع هامش بنسبة 20٪ للمراحل الحرجة. الحفاظ على الموارد الاحتياطية للأدوار التقنية الرئيسية. تطبيق منهجية Agile مع الفترات ثنائية الأسبوعية لتمكين التصحيح. إنشاء مقاييس النجاح الواضحة لكل مرحلة بوابة.'
                    ],
                    [
                        'heading' => 'المخاطر التنظيمية والالتزامية',
                        'content' => 'الانخراط مع الجهات التنظيمية السعودية مبكراً لامتثال حماية البيانات وحوكمة الذكاء الاصطناعي. تطبيق متطلبات إقامة البيانات لسوق السعودية. بناء أطر الامتثال المتوافقة مع إرشادات MISA. الحفاظ على مسارات التدقيق والتوثيق لجميع عمليات اتخاذ القرار بالذكاء الاصطناعي.'
                    ]
                ]
            ]
        ]
    ],

    'usp_unique_selling_points' => [
        'en' => [
            'text_content' => [
                'title' => 'Unique Selling Points',
                'sections' => [
                    [
                        'heading' => 'AI-Powered Digital Transformation',
                        'content' => 'Boud Platform leverages advanced AI and machine learning to automate and optimize digital transformation processes. Our proprietary algorithms analyze organizational DNA to provide personalized transformation roadmaps aligned with enterprise goals and Saudi Vision 2030 objectives.'
                    ],
                    [
                        'heading' => 'Vision 2030 Alignment',
                        'content' => 'Purpose-built compliance and impact measurement frameworks ensure all recommendations and implementations directly support Saudi Vision 2030 pillars. Unique integration with government digitalization standards and economic diversification initiatives makes Boud the preferred partner for Saudi enterprises.'
                    ],
                    [
                        'heading' => 'Integrated Ecosystem',
                        'content' => 'Boud serves as parent company to specialized innovation platforms (Hackify, SALIS, Connect AI) creating a comprehensive ecosystem. Clients benefit from seamless integration of consulting services with cutting-edge innovation tools, reducing complexity and accelerating time-to-value.'
                    ],
                    [
                        'heading' => 'Comprehensive Service Delivery',
                        'content' => 'From assessment and strategy to implementation and ongoing optimization, Boud provides end-to-end support. Our consulting experts work alongside software tools to ensure successful adoption, change management, and sustained digital excellence.'
                    ]
                ]
            ]
        ],
        'ar' => [
            'text_content' => [
                'title' => 'نقاط البيع الفريدة',
                'sections' => [
                    [
                        'heading' => 'التحول الرقمي بتقنية الذكاء الاصطناعي',
                        'content' => 'تستفيد منصة Boud من الذكاء الاصطناعي المتقدم والتعلم الآلي لأتمتة وتحسين عمليات التحول الرقمي. تحلل خوارزمياتنا الملكية الحمض النووي التنظيمي لتقديم خرائط طريق تحول مخصصة متوافقة مع أهداف الشركة ورؤية السعودية 2030.'
                    ],
                    [
                        'heading' => 'توافق رؤية 2030',
                        'content' => 'تضمن أطر الامتثال وقياس التأثير المصممة خصيصاً أن جميع التوصيات والتطبيقات تدعم بشكل مباشر أعمدة رؤية السعودية 2030. يجعل التكامل الفريد مع معايير الرقمنة الحكومية ومبادرات التنويع الاقتصادي Boud الشريك المفضل للشركات السعودية.'
                    ],
                    [
                        'heading' => 'النظام البيئي المتكامل',
                        'content' => 'تعمل Boud كشركة أم لمنصات الابتكار المتخصصة (Hackify و SALIS و Connect AI) مما يخلق نظاماً بيئياً شاملاً. يستفيد العملاء من التكامل السلس بين خدمات الاستشارات والأدوات الابتكار المتطورة، مما يقلل التعقيد ويسرع الوقت لتحقيق القيمة.'
                    ],
                    [
                        'heading' => 'تقديم الخدمات الشامل',
                        'content' => 'من التقييم والاستراتيجية إلى التطبيق والتحسين المستمر، تقدم Boud الدعم الشامل. يعمل خبراء الاستشارات لدينا جنباً إلى جنب مع أدوات البرمجيات لضمان النجاح في التبني وإدارة التغيير والتميز الرقمي المستدام.'
                    ]
                ]
            ]
        ]
    ],

    'usp_competitive_advantage' => [
        'en' => [
            'comparison_table' => [
                'headers' => ['Criteria', 'Boud Platform', 'Traditional Consultants', 'Generic SaaS Tools'],
                'rows' => [
                    ['AI-Powered Assessment', 'Advanced algorithms + human expertise', 'Manual assessment only', 'Basic analysis'],
                    ['Vision 2030 Alignment', 'Native compliance & impact tracking', 'Custom implementation needed', 'Not designed for KSA'],
                    ['Integrated Tools', 'Hackify, SALIS, Connect AI included', 'External tool dependencies', 'Feature-limited'],
                    ['Implementation Support', 'Full consulting + software support', 'Engagement-based only', 'Self-service only'],
                    ['Time-to-Value', '3-6 months', '6-12 months', '2-4 months (limited)',
                    ['Cost Structure', 'Flexible SaaS + advisory pricing', 'High fixed costs', 'Subscription only'],
                    ['Local Expertise', 'Saudi Vision 2030 specialized team', 'General global approach', 'No local focus'],
                ]
            ]
        ],
        'ar' => [
            'comparison_table' => [
                'headers' => ['المعايير', 'منصة Boud', 'استشاريون تقليديون', 'أدوات SaaS عامة'],
                'rows' => [
                    ['التقييم المدعوم بالذكاء الاصطناعي', 'خوارزميات متقدمة + خبرة بشرية', 'التقييم اليدوي فقط', 'تحليل أساسي'],
                    ['توافق رؤية 2030', 'الامتثال الأصلي وتتبع التأثير', 'التطبيق المخصص مطلوب', 'غير مصمم للمملكة'],
                    ['الأدوات المتكاملة', 'تتضمن Hackify و SALIS و Connect AI', 'اعتماديات الأدوات الخارجية', 'محدود الميزات'],
                    ['دعم التطبيق', 'الاستشارات الكاملة ودعم البرمجيات', 'دعم مبني على الالتزام فقط', 'خدمة ذاتية فقط'],
                    ['الوقت لتحقيق القيمة', '3-6 أشهر', '6-12 شهر', '2-4 أشهر (محدود)'],
                    ['هيكل التكلفة', 'تسعير SaaS المرن والاستشارات', 'تكاليف ثابتة عالية', 'الاشتراك فقط'],
                    ['الخبرة المحلية', 'فريق متخصص في رؤية السعودية 2030', 'نهج عام عالمي', 'لا يوجد تركيز محلي'],
                ]
            ]
        ]
    ],

    'usp_differentiation_strategy' => [
        'en' => [
            'text_content' => [
                'title' => 'Differentiation Strategy',
                'sections' => [
                    [
                        'heading' => 'Technology Differentiation',
                        'content' => 'Invest heavily in proprietary AI models trained on Saudi organizational data. Build unique assessment frameworks combining industry benchmarks with Vision 2030 KPIs. Develop advanced predictive analytics for transformation success probability. Maintain continuous innovation through AI model updates and emerging technology integration.'
                    ],
                    [
                        'heading' => 'Market Positioning Differentiation',
                        'content' => 'Position Boud as the leading AI-powered partner for Saudi digital transformation. Emphasize Vision 2030 alignment and government partnership credentials. Build brand through successful case studies with marquee Saudi enterprises. Establish thought leadership through research publications and industry speaking engagements.'
                    ],
                    [
                        'heading' => 'Service Delivery Differentiation',
                        'content' => 'Combine consulting expertise with software empowerment unlike competitors. Provide dedicated transformation advisors paired with AI tools for each client. Offer outcome-based pricing tied to transformation KPI achievement. Deliver continuous support through advisory board and innovation updates.'
                    ],
                    [
                        'heading' => 'Ecosystem Differentiation',
                        'content' => 'Leverage parent company ecosystem (Hackify for innovation management, SALIS for AI solutions, Connect AI for collaboration). Provide seamless integration across platforms reducing tool complexity. Create unique value through cross-product recommendations. Build network effects as more customers use the integrated ecosystem.'
                    ]
                ]
            ]
        ],
        'ar' => [
            'text_content' => [
                'title' => 'استراتيجية التمايز',
                'sections' => [
                    [
                        'heading' => 'تمايز التكنولوجيا',
                        'content' => 'استثمر بكثافة في نماذج الذكاء الاصطناعي الملكية المدربة على بيانات المنظمات السعودية. بناء أطر تقييم فريدة تجمع بين معايير الصناعة ومؤشرات رؤية 2030. تطوير تحليلات تنبؤية متقدمة لاحتمالية نجاح التحول. الحفاظ على الابتكار المستمر من خلال تحديثات نموذج الذكاء الاصطناعي وتكامل التكنولوجيا الناشئة.'
                    ],
                    [
                        'heading' => 'تمايز تحديد موضع السوق',
                        'content' => 'وضع Boud كشريك رائد مدعوم بالذكاء الاصطناعي للتحول الرقمي السعودي. التأكيد على توافق رؤية 2030 وأوراق اعتماد الشراكة الحكومية. بناء العلامة التجارية من خلال دراسات الحالة الناجحة مع الشركات السعودية المعروفة. إنشاء الفكر القيادي من خلال منشورات البحث والمشاركة في المؤتمرات الصناعية.'
                    ],
                    [
                        'heading' => 'تمايز تسليم الخدمات',
                        'content' => 'دمج خبرة الاستشارات مع تمكين البرمجيات على عكس المنافسين. توفير مستشاري تحول مخصصين مقترنين بأدوات الذكاء الاصطناعي لكل عميل. تقديم تسعير قائم على النتائج مرتبط بتحقيق KPI التحول. تقديم دعم مستمر من خلال مجلس استشاري وتحديثات الابتكار.'
                    ],
                    [
                        'heading' => 'تمايز النظام البيئي',
                        'content' => 'الاستفادة من النظام البيئي لشركة الأم (Hackify لإدارة الابتكار و SALIS لحلول الذكاء الاصطناعي و Connect AI للتعاون). توفير تكامل سلس عبر المنصات تقليل تعقيد الأدوات. إنشاء قيمة فريدة من خلال توصيات المنتجات المتقاطعة. بناء تأثيرات الشبكة مع استخدام المزيد من العملاء للنظام البيئي المتكامل.'
                    ]
                ]
            ]
        ]
    ],

    'usp_value_chain' => [
        'en' => [
            'key_value' => [
                'items' => [
                    ['key' => 'Assessment & Strategy', 'value' => 'AI-powered digital maturity evaluation + customized transformation roadmap'],
                    ['key' => 'Technology Implementation', 'value' => 'Full-stack modernization with cloud migration and system integration'],
                    ['key' => 'AI & Automation', 'value' => 'Process automation, predictive analytics, and intelligent decision support'],
                    ['key' => 'Change Management', 'value' => 'Organizational alignment, training programs, and adoption support'],
                    ['key' => 'Continuous Optimization', 'value' => 'Performance monitoring, AI model updates, and innovation integration'],
                    ['key' => 'Advisory Support', 'value' => 'Ongoing strategic guidance through dedicated transformation advisor'],
                    ['key' => 'Innovation Ecosystem', 'value' => 'Access to Hackify, SALIS, Connect AI for extended capabilities'],
                ]
            ]
        ],
        'ar' => [
            'key_value' => [
                'items' => [
                    ['key' => 'التقييم والاستراتيجية', 'value' => 'تقييم النضج الرقمي المدعوم بالذكاء الاصطناعي + خريطة طريق تحول مخصصة'],
                    ['key' => 'تطبيق التكنولوجيا', 'value' => 'الحداثة الشاملة مع هجرة السحابة وتكامل الأنظمة'],
                    ['key' => 'الذكاء الاصطناعي والأتمتة', 'value' => 'أتمتة العمليات والتحليلات التنبؤية ودعم القرار الذكي'],
                    ['key' => 'إدارة التغيير', 'value' => 'المحاذاة التنظيمية وبرامج التدريب ودعم التبني'],
                    ['key' => 'التحسين المستمر', 'value' => 'مراقبة الأداء وتحديثات نموذج الذكاء الاصطناعي وتكامل الابتكار'],
                    ['key' => 'الدعم الاستشاري', 'value' => 'التوجيه الاستراتيجي المستمر من خلال مستشار تحول مخصص'],
                    ['key' => 'نظام الابتكار البيئي', 'value' => 'الوصول إلى Hackify و SALIS و Connect AI للإمكانيات الموسعة'],
                ]
            ]
        ]
    ],

    'cp_primary_persona' => [
        'en' => [
            'persona_card' => [
                'name' => 'Fatima Al-Dosari',
                'role' => 'Chief Digital Officer',
                'age' => 42,
                'location' => 'Riyadh, Saudi Arabia',
                'quote' => 'We need to transform quickly to meet Vision 2030 targets, but our legacy systems are holding us back. We need a partner who understands both our challenges and the regulatory landscape.',
                'demographics' => [
                    'company_size' => 'Large Enterprise (5000+ employees)',
                    'industry' => 'Financial Services',
                    'education' => 'MBA in Information Systems',
                    'years_in_role' => 5,
                    'salary_range' => '$150K-$200K'
                ],
                'pain_points' => [
                    'Legacy system modernization complexity',
                    'Pressure to meet Vision 2030 KPIs',
                    'Difficulty finding AI/digital transformation expertise',
                    'Budget constraints despite transformation urgency',
                    'Change management across traditional organizational structure',
                    'Regulatory compliance in rapidly evolving digital landscape'
                ],
                'goals' => [
                    'Reduce digital transformation timeline from 24 months to 6-9 months',
                    'Achieve Vision 2030 alignment within first year',
                    'Improve customer experience through digital channels by 40%',
                    'Build internal digital capabilities and culture',
                    'Reduce operational costs through automation',
                    'Secure board approval for transformation budget'
                ],
                'motivations' => [
                    'Career advancement through successful digital transformation',
                    'Organizational competitiveness in evolving market',
                    'Personal legacy of modernizing enterprise',
                    'Alignment with Saudi Vision 2030 objectives',
                    'Pressure from C-suite and board for measurable results'
                ]
            ]
        ],
        'ar' => [
            'persona_card' => [
                'name' => 'فاطمة الدوسري',
                'role' => 'كبير مسؤولي الرقميات',
                'age' => 42,
                'location' => 'الرياض، المملكة العربية السعودية',
                'quote' => 'نحتاج إلى التحول بسرعة لتحقيق أهداف رؤية 2030، لكن أنظمتنا القديمة تعيقنا. نحتاج إلى شريك يفهم تحدياتنا والمشهد التنظيمي.',
                'demographics' => [
                    'company_size' => 'مؤسسة كبيرة (5000+ موظف)',
                    'industry' => 'الخدمات المالية',
                    'education' => 'ماجستير إدارة الأعمال في نظم المعلومات',
                    'years_in_role' => 5,
                    'salary_range' => '$150K-$200K'
                ],
                'pain_points' => [
                    'تعقيد تحديث الأنظمة القديمة',
                    'الضغط لتحقيق مؤشرات رؤية 2030',
                    'صعوبة إيجاد خبرة في الذكاء الاصطناعي والتحول الرقمي',
                    'قيود الميزانية رغم إلحاح التحول',
                    'إدارة التغيير عبر الهياكل التنظيمية التقليدية',
                    'الامتثال التنظيمي في المشهد الرقمي سريع التطور'
                ],
                'goals' => [
                    'تقليل جدول زمني للتحول الرقمي من 24 شهر إلى 6-9 أشهر',
                    'تحقيق توافق رؤية 2030 في السنة الأولى',
                    'تحسين تجربة العملاء من خلال القنوات الرقمية بنسبة 40٪',
                    'بناء القدرات الرقمية والثقافة الداخلية',
                    'تقليل تكاليف التشغيل من خلال الأتمتة',
                    'الحصول على موافقة المجلس على ميزانية التحول'
                ],
                'motivations' => [
                    'التقدم الوظيفي من خلال التحول الرقمي الناجح',
                    'تنافسية المنظمة في السوق المتطورة',
                    'الإرث الشخصي لتحديث المؤسسة',
                    'التوافق مع أهداف رؤية السعودية 2030',
                    'الضغط من الإدارة العليا والمجلس للحصول على نتائج ملموسة'
                ]
            ]
        ]
    ],

    'cp_secondary_persona' => [
        'en' => [
            'persona_card' => [
                'name' => 'Ahmed Al-Shammari',
                'role' => 'VP of Innovation & Technology',
                'age' => 38,
                'location' => 'Jeddah, Saudi Arabia',
                'quote' => 'Our technical team can build anything, but we struggle with strategy and vision alignment. We need a partner to guide our innovation roadmap.',
                'demographics' => [
                    'company_size' => 'Mid-to-Large Enterprise (2000-5000 employees)',
                    'industry' => 'Retail & E-commerce',
                    'education' => 'BS in Computer Science, Executive Leadership Program',
                    'years_in_role' => 3,
                    'salary_range' => '$120K-$160K'
                ],
                'pain_points' => [
                    'Translating business strategy into technical roadmaps',
                    'Justifying technology investments to finance leadership',
                    'Keeping pace with rapidly evolving AI and cloud technologies',
                    'Recruiting and retaining top technical talent',
                    'Balancing innovation with operational stability',
                    'Managing technical debt while pursuing new initiatives'
                ],
                'goals' => [
                    'Develop comprehensive innovation strategy aligned with business objectives',
                    'Build or acquire AI capabilities within 12 months',
                    'Establish cloud-native architecture',
                    'Implement agile transformation across technology organization',
                    'Reduce time-to-market for new digital products',
                    'Create internal technical thought leadership'
                ],
                'motivations' => [
                    'Proving ROI of technology investments',
                    'Building world-class technical organization',
                    'Making competitive impact through innovation',
                    'Technical advancement and emerging tech adoption',
                    'Recognition as innovation leader in industry'
                ]
            ]
        ],
        'ar' => [
            'persona_card' => [
                'name' => 'أحمد الشمري',
                'role' => 'نائب رئيس الابتكار والتكنولوجيا',
                'age' => 38,
                'location' => 'جدة، المملكة العربية السعودية',
                'quote' => 'فريقنا التقني يمكنه بناء أي شيء، لكننا نكافح مع الاستراتيجية والمحاذاة الرؤية. نحتاج إلى شريك لتوجيه خريطة طريق الابتكار لدينا.',
                'demographics' => [
                    'company_size' => 'مؤسسة متوسطة إلى كبيرة (2000-5000 موظف)',
                    'industry' => 'البيع بالتجزئة والتجارة الإلكترونية',
                    'education' => 'درجة البكالوريوس في علوم الحاسب الآلي، برنامج القيادة التنفيذية',
                    'years_in_role' => 3,
                    'salary_range' => '$120K-$160K'
                ],
                'pain_points' => [
                    'ترجمة استراتيجية الأعمال إلى خرائط طريق تقنية',
                    'تبرير استثمارات التكنولوجيا لقيادة المالية',
                    'مواكبة تطور الذكاء الاصطناعي والتقنيات السحابية',
                    'استقطاب والاحتفاظ بأفضل المواهب التقنية',
                    'توازن الابتكار مع الاستقرار التشغيلي',
                    'إدارة الديون التقنية مع متابعة المبادرات الجديدة'
                ],
                'goals' => [
                    'تطوير استراتيجية ابتكار شاملة متوافقة مع أهداف الأعمال',
                    'بناء أو الاستحواذ على قدرات الذكاء الاصطناعي في غضون 12 شهر',
                    'إنشاء معمارية سحابية أصلية',
                    'تطبيق تحول رشيق عبر المنظمة التقنية',
                    'تقليل الوقت المستغرق لإصدار منتجات رقمية جديدة',
                    'إنشاء قيادة فكرية تقنية داخلية'
                ],
                'motivations' => [
                    'إثبات العائد على الاستثمار في التكنولوجيا',
                    'بناء منظمة تقنية عالمية المستوى',
                    'تحقيق تأثير تنافسي من خلال الابتكار',
                    'التقدم التقني واعتماد التكنولوجيا الناشئة',
                    'الاعتراف كقائد ابتكار في الصناعة'
                ]
            ]
        ]
    ],

    'cp_buyer_journey' => [
        'en' => [
            'journey_timeline' => [
                'stages' => [
                    [
                        'title' => 'Awareness Stage (Weeks 1-4)',
                        'description' => 'Decision-maker recognizes need for digital transformation',
                        'touchpoints' => ['Industry reports and market research', 'Thought leadership content', 'Industry conferences', 'Peer recommendations'],
                        'actions' => ['Identify problem through business metrics', 'Research available solutions', 'Define transformation objectives']
                    ],
                    [
                        'title' => 'Consideration Stage (Weeks 5-12)',
                        'description' => 'Evaluation of transformation partners and approaches',
                        'touchpoints' => ['Solution demos and trials', 'Case studies and ROI calculators', 'Reference customer calls', 'Detailed feature comparisons'],
                        'actions' => ['Issue RFP to potential vendors', 'Conduct capability assessments', 'Evaluate pricing models', 'Build business case']
                    ],
                    [
                        'title' => 'Decision Stage (Weeks 13-16)',
                        'description' => 'Final vendor selection and contract negotiation',
                        'touchpoints' => ['Vendor presentations to C-suite', 'Legal and contract review', 'Budget allocation decisions', 'Board approval process'],
                        'actions' => ['Negotiate contract terms', 'Finalize pricing and scope', 'Approve project charter', 'Establish governance']
                    ],
                    [
                        'title' => 'Implementation Stage (Months 4-12)',
                        'description' => 'Active transformation execution',
                        'touchpoints' => ['Weekly steering committee meetings', 'Milestone reviews', 'Stakeholder training sessions', 'Progress dashboards'],
                        'actions' => ['Launch transformation program', 'Establish transformation office', 'Deliver quick wins', 'Manage organizational change']
                    ]
                ]
            ]
        ],
        'ar' => [
            'journey_timeline' => [
                'stages' => [
                    [
                        'title' => 'مرحلة الوعي (الأسابيع 1-4)',
                        'description' => 'يدرك صاحب القرار الحاجة للتحول الرقمي',
                        'touchpoints' => ['تقارير الصناعة والبحث عن السوق', 'محتوى القيادة الفكرية', 'مؤتمرات الصناعة', 'توصيات الأقران'],
                        'actions' => ['تحديد المشكلة من خلال مقاييس الأعمال', 'البحث عن الحلول المتاحة', 'تحديد أهداف التحول']
                    ],
                    [
                        'title' => 'مرحلة الاعتبار (الأسابيع 5-12)',
                        'description' => 'تقييم شركاء وأساليب التحول',
                        'touchpoints' => ['عروض توضيحية وتجارب للحل', 'دراسات الحالات وآلات حساب العائد على الاستثمار', 'استدعاءات العملاء المرجعيين', 'مقارنات الميزات التفصيلية'],
                        'actions' => ['إصدار RFP للبائعين المحتملين', 'إجراء تقييمات القدرات', 'تقييم نماذج التسعير', 'بناء حالة العمل']
                    ],
                    [
                        'title' => 'مرحلة القرار (الأسابيع 13-16)',
                        'description' => 'الاختيار النهائي للبائع والتفاوض على العقد',
                        'touchpoints' => ['عروض البائع للمسؤولين التنفيذيين', 'مراجعة قانونية وعقدية', 'قرارات تخصيص الميزانية', 'عملية موافقة المجلس'],
                        'actions' => ['التفاوض على شروط العقد', 'إنهاء التسعير والنطاق', 'الموافقة على ميثاق المشروع', 'إنشاء الحوكمة']
                    ],
                    [
                        'title' => 'مرحلة التطبيق (الأشهر 4-12)',
                        'description' => 'تنفيذ التحول النشط',
                        'touchpoints' => ['اجتماعات لجنة التوجيه الأسبوعية', 'استعراضات المعالم', 'جلسات تدريب أصحاب المصلحة', 'لوحات معلومات التقدم'],
                        'actions' => ['إطلاق برنامج التحول', 'إنشاء مكتب التحول', 'تقديم الانتصارات السريعة', 'إدارة التغيير التنظيمي']
                    ]
                ]
            ]
        ]
    ],

    'cp_pain_points_analysis' => [
        'en' => [
            'text_content' => [
                'title' => 'Customer Pain Points Analysis',
                'sections' => [
                    [
                        'heading' => 'Strategic Challenges',
                        'content' => 'Saudi enterprises struggle to translate Vision 2030 objectives into concrete transformation strategies. Most lack clear understanding of which digital capabilities matter most for their business model. Executive teams face pressure for rapid transformation but lack frameworks to prioritize initiatives and allocate resources effectively.'
                    ],
                    [
                        'heading' => 'Organizational & Talent Gaps',
                        'content' => 'Digital transformation expertise is scarce in Saudi market. Organizations lack in-house AI and advanced analytics capabilities. Recruiting expatriate talent faces visa and regulatory challenges. Internal teams often resist change due to lack of understanding and involvement in transformation planning.'
                    ],
                    [
                        'heading' => 'Technology & Integration Issues',
                        'content' => 'Legacy systems create significant modernization challenges and integration complexities. Cloud migration requires substantial capital investment and operational disruption. Organizations lack clear technology roadmaps aligned with business objectives. Multiple disparate tools create inefficiencies and prevent holistic digital capability.'
                    ],
                    [
                        'heading' => 'Financial & ROI Uncertainties',
                        'content' => 'Transformation budgets face scrutiny without clear business case and ROI projections. Hidden costs and scope creep become major issues without structured governance. Difficulty quantifying digital transformation benefits delays investment approval. CFOs demand measurable outcomes tied to Vision 2030 KPIs.'
                    ]
                ]
            ]
        ],
        'ar' => [
            'text_content' => [
                'title' => 'تحليل نقاط الألم لدى العملاء',
                'sections' => [
                    [
                        'heading' => 'التحديات الاستراتيجية',
                        'content' => 'تكافح الشركات السعودية لترجمة أهداف رؤية 2030 إلى استراتيجيات تحول ملموسة. تفتقد معظمها فهماً واضحاً للقدرات الرقمية التي تهم أكثر لنموذج أعمالها. تواجه الفرق التنفيذية ضغوطاً للتحول السريع لكنها تفتقد أطر العمل لتحديد أولويات المبادرات وتخصيص الموارد بفعالية.'
                    ],
                    [
                        'heading' => 'فجوات الموارد البشرية والمنظمة',
                        'content' => 'خبرة التحول الرقمي نادرة في السوق السعودية. تفتقد المنظمات القدرات الداخلية للذكاء الاصطناعي والتحليلات المتقدمة. يواجه استقطاب الموهبة الأجنبية تحديات تأشيرة وتنظيمية. غالباً ما تقاوم الفرق الداخلية التغيير بسبب نقص الفهم والمشاركة في تخطيط التحول.'
                    ],
                    [
                        'heading' => 'مشاكل التكنولوجيا والتكامل',
                        'content' => 'الأنظمة القديمة تخلق تحديات حداثة كبيرة وتعقيدات التكامل. هجرة السحابة تتطلب استثمار رأس مال كبير واضطراب تشغيلي. تفتقد المنظمات خرائط طريق تقنية واضحة متوافقة مع أهداف الأعمال. أدوات متعددة غير متجانسة تخلق عدم كفاءة وتمنع القدرة الرقمية الشاملة.'
                    ],
                    [
                        'heading' => 'عدم اليقين المالي والعائد على الاستثمار',
                        'content' => 'تواجه ميزانيات التحول التدقيق بدون حالة عمل واضحة وتنبؤات العائد على الاستثمار. التكاليف المخفية والزحف النطاق تصبح مشاكل كبيرة بدون حوكمة منظمة. صعوبة قياس فوائد التحول الرقمي تؤخر موافقة الاستثمار. يطالب مديرو المالية بنتائج قابلة للقياس مرتبطة بمؤشرات رؤية 2030.'
                    ]
                ]
            ]
        ]
    ],

    'fin_revenue_model' => [
        'en' => [
            'pricing_cards' => [
                'tiers' => [
                    [
                        'name' => 'Starter',
                        'price' => '$15K/mo',
                        'features' => ['Digital maturity assessment', 'Transformation roadmap (basic)', 'Dashboard access (read-only)', 'Monthly consulting hours (4)', 'Email support'],
                        'highlighted' => false,
                        'cta' => 'Start Assessment'
                    ],
                    [
                        'name' => 'Professional',
                        'price' => '$45K/mo',
                        'features' => ['All Starter features', 'Advanced AI assessment', 'Customized roadmap generation', 'Full dashboard access', 'Monthly consulting hours (16)', 'Integration with one tool', 'Priority email & phone support'],
                        'highlighted' => true,
                        'cta' => 'Start Transformation'
                    ],
                    [
                        'name' => 'Enterprise',
                        'price' => 'Custom',
                        'features' => ['All Professional features', 'Dedicated transformation advisor', 'Full ecosystem integration (Hackify, SALIS, Connect AI)', 'Unlimited consulting hours', 'Custom AI model training', 'On-site implementation support', '24/7 phone & video support', 'Quarterly business reviews'],
                        'highlighted' => false,
                        'cta' => 'Schedule Demo'
                    ]
                ]
            ]
        ],
        'ar' => [
            'pricing_cards' => [
                'tiers' => [
                    [
                        'name' => 'مبتدئ',
                        'price' => '$15K/mo',
                        'features' => ['تقييم النضج الرقمي', 'خريطة طريق التحول (أساسية)', 'وصول لوحة المعلومات (قراءة فقط)', 'ساعات الاستشارة الشهرية (4)', 'دعم البريد الإلكتروني'],
                        'highlighted' => false,
                        'cta' => 'ابدأ التقييم'
                    ],
                    [
                        'name' => 'احترافي',
                        'price' => '$45K/mo',
                        'features' => ['جميع ميزات Starter', 'التقييم المتقدم بالذكاء الاصطناعي', 'توليد خريطة الطريق المخصصة', 'الوصول الكامل إلى لوحة المعلومات', 'ساعات الاستشارة الشهرية (16)', 'التكامل مع أداة واحدة', 'دعم البريد الإلكتروني والهاتف الأولوي'],
                        'highlighted' => true,
                        'cta' => 'ابدأ التحول'
                    ],
                    [
                        'name' => 'مؤسسة',
                        'price' => 'مخصص',
                        'features' => ['جميع ميزات Professional', 'مستشار تحول مخصص', 'تكامل النظام البيئي الكامل (Hackify و SALIS و Connect AI)', 'ساعات استشارة غير محدودة', 'تدريب نموذج الذكاء الاصطناعي المخصص', 'دعم التطبيق على الموقع', 'دعم الهاتف والفيديو 24/7', 'الاستعراضات الفصلية للأعمال'],
                        'highlighted' => false,
                        'cta' => 'جدولة عرض توضيحي'
                    ]
                ]
            ]
        ]
    ],

    'fin_cost_structure' => [
        'en' => [
            'progress_bars' => [
                'items' => [
                    ['label' => 'Technology & Infrastructure', 'value' => 35, 'suffix' => '%'],
                    ['label' => 'Personnel & Salaries', 'value' => 40, 'suffix' => '%'],
                    ['label' => 'AI Model Development', 'value' => 15, 'suffix' => '%'],
                    ['label' => 'Sales & Marketing', 'value' => 7, 'suffix' => '%'],
                    ['label' => 'Operations & Admin', 'value' => 3, 'suffix' => '%'],
                ]
            ]
        ],
        'ar' => [
            'progress_bars' => [
                'items' => [
                    ['label' => 'التكنولوجيا والبنية التحتية', 'value' => 35, 'suffix' => '%'],
                    ['label' => 'الموارد البشرية والرواتب', 'value' => 40, 'suffix' => '%'],
                    ['label' => 'تطوير نموذج الذكاء الاصطناعي', 'value' => 15, 'suffix' => '%'],
                    ['label' => 'المبيعات والتسويق', 'value' => 7, 'suffix' => '%'],
                    ['label' => 'العمليات والإدارة', 'value' => 3, 'suffix' => '%'],
                ]
            ]
        ]
    ],

    'fin_financial_projections' => [
        'en' => [
            'stat_cards' => [
                'metrics' => [
                    ['label' => 'Year 1 Revenue', 'value' => '$8.2M', 'description' => '18-24 customer contracts at average ARR'],
                    ['label' => 'Year 2 Revenue', 'value' => '$18.5M', 'description' => '40-50 customer contracts, 65% YoY growth'],
                    ['label' => 'Year 3 Revenue', 'value' => '$35.2M', 'description' => '75-85 customer contracts, 90% YoY growth'],
                    ['label' => 'Gross Margin (Year 2)', 'value' => '68%', 'description' => 'High-margin SaaS with consulting services'],
                    ['label' => 'Customer Acquisition Cost', 'value' => '$35K', 'description' => 'Payback period ~8 months'],
                    ['label' => 'Net Revenue Retention', 'value' => '115%', 'description' => 'Expansion revenue from existing customers'],
                ]
            ]
        ],
        'ar' => [
            'stat_cards' => [
                'metrics' => [
                    ['label' => 'إيرادات السنة الأولى', 'value' => '$8.2M', 'description' => '18-24 عقد عميل بمعدل ARR متوسط'],
                    ['label' => 'إيرادات السنة الثانية', 'value' => '$18.5M', 'description' => '40-50 عقد عميل، نمو 65% السنة على السنة'],
                    ['label' => 'إيرادات السنة الثالثة', 'value' => '$35.2M', 'description' => '75-85 عقد عميل، نمو 90% السنة على السنة'],
                    ['label' => 'إجمالي الهامش (السنة الثانية)', 'value' => '68%', 'description' => 'SaaS عالي الهامش مع خدمات استشارات'],
                    ['label' => 'تكلفة الحصول على العميل', 'value' => '$35K', 'description' => 'فترة الاسترداد حوالي 8 أشهر'],
                    ['label' => 'احتفاظ صافي الإيرادات', 'value' => '115%', 'description' => 'إيرادات التوسع من العملاء الحاليين'],
                ]
            ]
        ]
    ],

    'fin_funding_requirements' => [
        'en' => [
            'text_content' => [
                'title' => 'Funding Requirements',
                'sections' => [
                    [
                        'heading' => 'Series A Funding Goal',
                        'content' => 'Seeking $8-10M Series A investment to accelerate product development, market expansion, and team growth. This funding will enable Boud to dominate the AI-powered digital transformation consulting space in Saudi Arabia and expand regionally across GCC markets.'
                    ],
                    [
                        'heading' => 'Use of Funds',
                        'content' => 'Product Development & AI (35%): Advanced AI model development, platform enhancement, and ecosystem integration. Go-to-Market (30%): Sales team expansion, marketing campaigns, and regional partnerships. Talent Acquisition (25%): Senior engineering, AI specialists, and business development roles. Operations & Working Capital (10%): Infrastructure, tools, and operational efficiency.'
                    ],
                    [
                        'heading' => 'Funding Timeline',
                        'content' => 'Target closing Series A by Q3 2026. This timeline aligns with completing MVP and achieving initial customer traction. Funds will be deployed immediately post-close with 24-month horizon for Series B at $25-30M valuation targeting 2028.'
                    ],
                    [
                        'heading' => 'Exit Strategy',
                        'content' => 'Multiple exit pathways including acquisition by major consulting firms (McKinsey, Accenture, Deloitte) interested in AI/digital capabilities, or IPO within 5-7 years with target unicorn valuation. Strong profitability trajectory and large addressable market support attractive exit multiples for investors.'
                    ]
                ]
            ]
        ],
        'ar' => [
            'text_content' => [
                'title' => 'متطلبات التمويل',
                'sections' => [
                    [
                        'heading' => 'هدف تمويل السلسلة أ',
                        'content' => 'البحث عن استثمار Series A بقيمة 8-10 ملايين دولار لتسريع تطوير المنتج وتوسع السوق ونمو الفريق. سيمكن هذا التمويل Boud من السيطرة على مساحة استشارات التحول الرقمي المدعومة بالذكاء الاصطناعي في المملكة العربية السعودية والتوسع إقليمياً عبر أسواق مجلس التعاون الخليجي.'
                    ],
                    [
                        'heading' => 'استخدام الأموال',
                        'content' => 'تطوير المنتج والذكاء الاصطناعي (35٪): تطوير نموذج الذكاء الاصطناعي المتقدم وتحسين المنصة وتكامل النظام البيئي. الذهاب إلى السوق (30٪): توسع فريق المبيعات والحملات التسويقية والشراكات الإقليمية. استقطاب المواهب (25٪): أدوار الهندسة الكبرى ومتخصصي الذكاء الاصطناعي والتطوير التجاري. العمليات وتمويل العاملين (10٪): البنية التحتية والأدوات والكفاءة التشغيلية.'
                    ],
                    [
                        'heading' => 'جدول التمويل',
                        'content' => 'الهدف إغلاق Series A بحلول الربع الثالث 2026. يتوافق هذا الجدول الزمني مع إكمال MVP وتحقيق جر العملاء الأولي. سيتم نشر الأموال فوراً بعد الإغلاق مع أفق 24 شهر لـ Series B بتقييم 25-30 ملايين دولار يستهدف 2028.'
                    ],
                    [
                        'heading' => 'استراتيجية الخروج',
                        'content' => 'مسارات خروج متعددة تشمل الاستحواذ من قبل شركات الاستشارات الكبرى (McKinsey و Accenture و Deloitte) المهتمة بقدرات الذكاء الاصطناعي / الرقمية، أو الاكتتاب العام في غضون 5-7 سنوات مع استهداف تقييم يونيكورن. مسار الربحية القوي والسوق العنوان الكبير يدعم مضاعفات الخروج الجذابة للمستثمرين.'
                    ]
                ]
            ]
        ]
    ],

    'fin_unit_economics' => [
        'en' => [
            'key_value' => [
                'items' => [
                    ['key' => 'Average Contract Value (ACV)', 'value' => '$450K annually'],
                    ['key' => 'Customer Acquisition Cost (CAC)', 'value' => '$35K'],
                    ['key' => 'CAC Payback Period', 'value' => '8 months'],
                    ['key' => 'Lifetime Value (LTV)', 'value' => '$1.8M (4-year average'],
                    ['key' => 'LTV/CAC Ratio', 'value' => '51.4x (highly attractive)'],
                    ['key' => 'Monthly Recurring Revenue (MRR)', 'value' => '$37.5K per customer'],
                    ['key' => 'Gross Margin per Customer', 'value' => '68%'],
                    ['key' => 'Net Revenue Retention (NRR)', 'value' => '115%'],
                ]
            ]
        ],
        'ar' => [
            'key_value' => [
                'items' => [
                    ['key' => 'متوسط قيمة العقد (ACV)', 'value' => '$450K سنوياً'],
                    ['key' => 'تكلفة الحصول على العميل (CAC)', 'value' => '$35K'],
                    ['key' => 'فترة استرجاع CAC', 'value' => '8 أشهر'],
                    ['key' => 'القيمة الدائمة (LTV)', 'value' => '$1.8M (متوسط 4 سنوات)'],
                    ['key' => 'نسبة LTV/CAC', 'value' => '51.4x (جذابة جداً)'],
                    ['key' => 'الإيرادات المتكررة الشهرية (MRR)', 'value' => '$37.5K لكل عميل'],
                    ['key' => 'إجمالي الهامش لكل عميل', 'value' => '68%'],
                    ['key' => 'احتفاظ صافي الإيرادات (NRR)', 'value' => '115%'],
                ]
            ]
        ]
    ],

    'gtm_launch_strategy' => [
        'en' => [
            'text_content' => [
                'title' => 'Go-to-Market Launch Strategy',
                'sections' => [
                    [
                        'heading' => 'Market Segmentation',
                        'content' => 'Primary focus on large enterprises (500+ employees) in financial services, retail, telecommunications, and manufacturing sectors. Secondary segments include government agencies and semi-government organizations with Vision 2030 mandates. Geographic prioritization: Riyadh, Jeddah, Dammam initially with regional expansion to UAE, Kuwait, Qatar within 18 months.'
                    ],
                    [
                        'heading' => 'Sales Strategy',
                        'content' => 'Enterprise sales model with dedicated account executives targeting C-suite decision-makers. Establish strategic partnerships with Big 4 consulting firms and system integrators for referrals. Build alliances with industry associations and chambers of commerce for lead generation. Implement inside sales team for mid-market opportunities. Leverage parent company ecosystem (Hackify, SALIS, Connect AI) for cross-selling.'
                    ],
                    [
                        'heading' => 'Marketing & Brand',
                        'content' => 'Thought leadership positioning through industry conferences, whitepapers, and research on digital transformation and Vision 2030. Create Arabic-language content marketing addressing Saudi business needs. Establish Boud as the premium AI consulting brand. Build proof points through early customer case studies. Leverage social media and LinkedIn for B2B engagement. Sponsor industry events and webinars.'
                    ],
                    [
                        'heading' => 'Customer Success & Retention',
                        'content' => 'Dedicated customer success managers for each enterprise client. Quarterly business reviews with C-suite stakeholders. Continuous value delivery through innovation updates and new feature releases. Build customer advisory board for strategic feedback. Implement NPS tracking and proactive churn prevention. Expand ACV through upsells and cross-sells of ecosystem products.'
                    ]
                ]
            ]
        ],
        'ar' => [
            'text_content' => [
                'title' => 'استراتيجية الإطلاق للسوق',
                'sections' => [
                    [
                        'heading' => 'تقسيم السوق',
                        'content' => 'التركيز الأساسي على الشركات الكبيرة (500+ موظف) في قطاعات الخدمات المالية والبيع بالتجزئة والاتصالات والتصنيع. تشمل القطاعات الثانوية الوكالات الحكومية والمنظمات شبه الحكومية مع ولايات رؤية 2030. ترتيب الأولويات الجغرافية: الرياض وجدة والدمام في البداية مع التوسع الإقليمي إلى الإمارات والكويت وقطر خلال 18 شهر.'
                    ],
                    [
                        'heading' => 'استراتيجية المبيعات',
                        'content' => 'نموذج المبيعات للمؤسسات مع متخصصي الحسابات المخصصة يستهدفون صناع القرار في المستوى التنفيذي. إنشاء شراكات استراتيجية مع شركات الاستشارات الأربع الكبرى والمدمجات النظام لأغراض الإحالة. بناء تحالفات مع جمعيات الصناعة وغرف التجارة لتوليد الرصاص. تطبيق فريق المبيعات الداخلية لفرص السوق المتوسطة. الاستفادة من النظام البيئي لشركة الأم (Hackify و SALIS و Connect AI) للبيع المتقاطع.'
                    ],
                    [
                        'heading' => 'التسويق والعلامة التجارية',
                        'content' => 'تحديد موضع القيادة الفكرية من خلال مؤتمرات الصناعة والأوراق البيضاء والبحث عن التحول الرقمي ورؤية 2030. إنشاء محتوى تسويقي باللغة العربية يعالج احتياجات الأعمال السعودية. إنشاء Boud كعلامة تجارية استشارات ذكاء اصطناعي متميزة. بناء نقاط الإثبات من خلال دراسات حالات العملاء المبكرة. الاستفادة من وسائل التواصل الاجتماعي و LinkedIn للمشاركة B2B. رعاية أحداث الصناعة والندوات عبر الإنترنت.'
                    ],
                    [
                        'heading' => 'نجاح العملاء والاحتفاظ',
                        'content' => 'مديرو نجاح عملاء مخصصون لكل عميل مؤسسة. الاستعراضات الفصلية للأعمال مع أصحاب المصلحة من المستوى التنفيذي. تسليم القيمة المستمرة من خلال تحديثات الابتكار وإصدارات الميزات الجديدة. بناء مجلس استشاري للعملاء للحصول على تعليقات استراتيجية. تنفيذ تتبع NPS ومنع الضجيج الاستباقي. توسيع ACV من خلال عمليات البيع الإضافي والبيع المتقاطع لمنتجات النظام البيئي.'
                    ]
                ]
            ]
        ]
    ],

    'gtm_marketing_channels' => [
        'en' => [
            'stat_cards' => [
                'metrics' => [
                    ['label' => 'Thought Leadership', 'value' => '25%', 'description' => 'Conference speaking, whitepapers, research reports'],
                    ['label' => 'Direct Sales', 'value' => '35%', 'description' => 'Enterprise account executives and partnerships'],
                    ['label' => 'Digital Marketing', 'value' => '20%', 'description' => 'LinkedIn, content marketing, SEO, webinars'],
                    ['label' => 'Strategic Partnerships', 'value' => '15%', 'description' => 'Big 4 firms, integrators, ecosystem companies'],
                    ['label' => 'Community & Events', 'value' => '5%', 'description' => 'Industry associations, chambers of commerce'],
                ]
            ]
        ],
        'ar' => [
            'stat_cards' => [
                'metrics' => [
                    ['label' => 'قيادة الفكر', 'value' => '25%', 'description' => 'مؤتمرات التحدث والأوراق البيضاء وتقارير البحث'],
                    ['label' => 'المبيعات المباشرة', 'value' => '35%', 'description' => 'متخصصو الحسابات للمؤسسات والشراكات'],
                    ['label' => 'التسويق الرقمي', 'value' => '20%', 'description' => 'LinkedIn والتسويق بالمحتوى و SEO والندوات'],
                    ['label' => 'الشراكات الاستراتيجية', 'value' => '15%', 'description' => 'شركات Big 4 والمدمجات وشركات النظام البيئي'],
                    ['label' => 'المجتمع والأحداث', 'value' => '5%', 'description' => 'جمعيات الصناعة وغرف التجارة'],
                ]
            ]
        ]
    ],

    'gtm_sales_funnel' => [
        'en' => [
            'journey_timeline' => [
                'stages' => [
                    [
                        'title' => 'Top of Funnel (Awareness)',
                        'description' => 'Generate awareness among target decision-makers',
                        'touchpoints' => ['Content marketing campaigns', 'Industry conference presence', 'LinkedIn outreach', 'Partner referrals', 'Webinar series'],
                        'actions' => ['Build prospect database', 'Create content library', 'Launch digital campaigns', 'Establish partnerships']
                    ],
                    [
                        'title' => 'Middle of Funnel (Consideration)',
                        'description' => 'Engage prospects and move toward evaluation',
                        'touchpoints' => ['Product demos and trials', 'White papers and case studies', 'Executive briefings', 'Reference calls', 'ROI calculator'],
                        'actions' => ['Schedule product demos', 'Distribute resources', 'Conduct needs analysis', 'Prepare business case']
                    ],
                    [
                        'title' => 'Bottom of Funnel (Decision)',
                        'description' => 'Close deals and onboard customers',
                        'touchpoints' => ['Contract negotiation', 'Legal review', 'Budget allocation', 'Executive approval', 'Onboarding kickoff'],
                        'actions' => ['Finalize contracts', 'Complete due diligence', 'Establish governance', 'Plan implementation']
                    ],
                    [
                        'title' => 'Post-Sale (Expansion)',
                        'description' => 'Maximize customer lifetime value',
                        'touchpoints' => ['Regular check-ins', 'Success reviews', 'Expansion opportunities', 'Referral generation', 'Advocacy programs'],
                        'actions' => ['Track KPIs', 'Identify upsell opportunities', 'Gather feedback', 'Generate case studies']
                    ]
                ]
            ]
        ],
        'ar' => [
            'journey_timeline' => [
                'stages' => [
                    [
                        'title' => 'أعلى القمع (الوعي)',
                        'description' => 'تولد الوعي بين صناع القرار المستهدفين',
                        'touchpoints' => ['حملات التسويق بالمحتوى', 'وجود مؤتمر الصناعة', 'LinkedIn الوصول', 'إحالات الشركاء', 'سلسلة الندوات'],
                        'actions' => ['بناء قاعدة بيانات المحتملين', 'إنشاء مكتبة محتوى', 'إطلاق حملات رقمية', 'إنشاء شراكات']
                    ],
                    [
                        'title' => 'وسط القمع (الاعتبار)',
                        'description' => 'الانخراط مع المحتملين والتحرك نحو التقييم',
                        'touchpoints' => ['عروض المنتجات والتجارب', 'الأوراق البيضاء ودراسات الحالات', 'الإحاطات التنفيذية', 'استدعاءات الرجوع', 'آلة حساب العائد على الاستثمار'],
                        'actions' => ['جدولة عروض المنتج', 'توزيع الموارد', 'إجراء تحليل الاحتياجات', 'تحضير حالة العمل']
                    ],
                    [
                        'title' => 'أسفل القمع (القرار)',
                        'description' => 'إغلاق الصفقات وإدراج العملاء',
                        'touchpoints' => ['التفاوض على العقد', 'المراجعة القانونية', 'تخصيص الميزانية', 'الموافقة التنفيذية', 'بدء بدء الإعداد'],
                        'actions' => ['إنهاء العقود', 'إكمال العناية الواجبة', 'إنشاء الحوكمة', 'خطة التطبيق']
                    ],
                    [
                        'title' => 'ما بعد البيع (التوسع)',
                        'description' => 'زيادة قيمة العميل مدى الحياة',
                        'touchpoints' => ['الفحوصات المنتظمة', 'استعراضات النجاح', 'فرص التوسع', 'توليد الإحالات', 'برامج الدفاع'],
                        'actions' => ['تتبع مؤشرات الأداء', 'تحديد فرص البيع الإضافي', 'جمع التعليقات', 'توليد دراسات الحالات']
                    ]
                ]
            ]
        ]
    ],

    'gtm_partnerships' => [
        'en' => [
            'text_content' => [
                'title' => 'Strategic Partnerships',
                'sections' => [
                    [
                        'heading' => 'Consulting Firm Partnerships',
                        'content' => 'Strategic partnerships with Big 4 consulting firms (McKinsey, Accenture, Deloitte, EY) and regional consulting leaders. Boud provides AI and digital tools; partners provide client relationships and implementation services. Revenue sharing model with mutual benefit and co-selling opportunities. Integration of Boud platform into partner service offerings.'
                    ],
                    [
                        'heading' => 'Technology & System Integrator Partnerships',
                        'content' => 'Partnerships with cloud providers (AWS, Azure, Google Cloud) and system integrators. Embedded Boud platform in SI service offerings for digital transformation projects. Technology partnerships ensure seamless integration and cloud deployment. Co-marketing and lead sharing arrangements.'
                    ],
                    [
                        'heading' => 'Government & Public Sector Partnerships',
                        'content' => 'Strategic engagement with Saudi government agencies and Vision 2030 implementation bodies. Established as approved vendor for digital transformation initiatives. Partnership with MISA and regulatory bodies for compliance and best practices. Government mandates drive enterprise adoption.'
                    ],
                    [
                        'heading' => 'Ecosystem Partner Collaboration',
                        'content' => 'Leverage Boud parent company ecosystem (Hackify for innovation management, SALIS for AI solutions, Connect AI for collaboration) for customer cross-selling and expanded value delivery. Create bundled offerings combining Boud with ecosystem tools. Network effects as more ecosystem customers adopt multiple products.'
                    ]
                ]
            ]
        ],
        'ar' => [
            'text_content' => [
                'title' => 'الشراكات الاستراتيجية',
                'sections' => [
                    [
                        'heading' => 'شراكات شركات الاستشارات',
                        'content' => 'شراكات استراتيجية مع شركات الاستشارات الأربع الكبرى (McKinsey و Accenture و Deloitte و EY) والقادة الاستشاريين الإقليميين. تقدم Boud أدوات الذكاء الاصطناعي والرقميات؛ يقدم الشركاء علاقات العملاء وخدمات التطبيق. نموذج تقاسم الإيرادات مع الفائدة المتبادلة وفرص البيع المشترك. تكامل منصة Boud في عروض الخدمات للشركاء.'
                    ],
                    [
                        'heading' => 'شراكات التكنولوجيا والمدمجات',
                        'content' => 'شراكات مع موفري السحابة (AWS و Azure و Google Cloud) والمدمجات النظام. منصة Boud المدمجة في عروض خدمات SI لمشاريع التحول الرقمي. تضمن شراكات التكنولوجيا التكامل السلس والنشر السحابي. ترتيبات التسويق المشترك وتقاسم الرصاص.'
                    ],
                    [
                        'heading' => 'شراكات الحكومة والقطاع العام',
                        'content' => 'الانخراط الاستراتيجي مع الوكالات الحكومية السعودية وهيئات تطبيق رؤية 2030. تأسس كبائع معتمد لمبادرات التحول الرقمي. الشراكة مع MISA والهيئات التنظيمية للامتثال وأفضل الممارسات. تفويضات حكومية تحرك التبني الحكومي.'
                    ],
                    [
                        'heading' => 'تعاون شركاء النظام البيئي',
                        'content' => 'استفد من النظام البيئي لشركة الأم Boud (Hackify لإدارة الابتكار و SALIS لحلول الذكاء الاصطناعي و Connect AI للتعاون) لتعيين البيع المتقاطع للعملاء وتسليم القيمة الموسعة. إنشاء عروض مجمعة تجمع Boud مع أدوات النظام البيئي. تأثيرات الشبكة مع اعتماد عملاء النظام البيئي لمنتجات متعددة.'
                    ]
                ]
            ]
        ]
    ],

    'gtm_growth_metrics' => [
        'en' => [
            'progress_bars' => [
                'items' => [
                    ['label' => 'Customer Acquisition', 'value' => 85, 'suffix' => '% target'],
                    ['label' => 'Monthly Recurring Revenue (MRR)', 'value' => 72, 'suffix' => '% target'],
                    ['label' => 'Net Revenue Retention (NRR)', 'value' => 115, 'suffix' => '% achieved'],
                    ['label' => 'Customer Satisfaction (NPS)', 'value' => 68, 'suffix' => '% target'],
                    ['label' => 'Market Share (Year 2)', 'value' => 18, 'suffix' => '% target'],
                ]
            ]
        ],
        'ar' => [
            'progress_bars' => [
                'items' => [
                    ['label' => 'الحصول على العميل', 'value' => 85, 'suffix' => '% هدف'],
                    ['label' => 'الإيرادات المتكررة الشهرية (MRR)', 'value' => 72, 'suffix' => '% هدف'],
                    ['label' => 'احتفاظ صافي الإيرادات (NRR)', 'value' => 115, 'suffix' => '% تم تحقيقه'],
                    ['label' => 'رضا العملاء (NPS)', 'value' => 68, 'suffix' => '% هدف'],
                    ['label' => 'حصة السوق (السنة 2)', 'value' => 18, 'suffix' => '% هدف'],
                ]
            ]
        ]
    ],

    'ca_competitor_overview' => [
        'en' => [
            'text_content' => [
                'title' => 'Competitive Landscape Overview',
                'sections' => [
                    [
                        'heading' => 'Traditional Consulting Competitors',
                        'content' => 'McKinsey, Accenture, Deloitte, BCG, and EY dominate enterprise transformation consulting. Strong client relationships and brand recognition but slow to adopt AI tools. High cost structures limit innovation. Limited AI/digital product offerings. Opportunity: Boud offers faster, more cost-effective solutions with superior technology.'
                    ],
                    [
                        'heading' => 'SaaS & Generic Digital Tools',
                        'content' => 'Salesforce, Microsoft, SAP, and Oracle provide digital solutions but lack transformation consulting expertise. Feature-heavy platforms without strategic guidance. Minimal Vision 2030 alignment. Require extensive customization and integration. Opportunity: Boud combines technology with expert consulting and local market knowledge.'
                    ],
                    [
                        'heading' => 'Regional Competitors',
                        'content' => 'Local consulting firms and digital transformation startups lack scale, resources, and international credibility. Limited AI capabilities and technology infrastructure. Narrow service offerings. Opportunity: Boud combines global capabilities with deep Saudi market expertise and specialized AI-powered tools.'
                    ],
                    [
                        'heading' => 'Competitive Moats',
                        'content' => 'Boud advantages include proprietary AI models trained on Saudi data, Vision 2030 alignment framework, integrated ecosystem (Hackify, SALIS, Connect AI), combination of consulting + software, and local market expertise with global standards. Strong defensibility against new entrants.'
                    ]
                ]
            ]
        ],
        'ar' => [
            'text_content' => [
                'title' => 'نظرة عامة على المشهد التنافسي',
                'sections' => [
                    [
                        'heading' => 'منافسو الاستشارات التقليدية',
                        'content' => 'تهيمن McKinsey و Accenture و Deloitte و BCG و EY على استشارات تحول المؤسسات. علاقات عملاء قوية وتقدير العلامة التجارية لكن بطء اعتماد أدوات الذكاء الاصطناعي. هياكل تكاليف عالية تحد من الابتكار. عروض منتجات الذكاء الاصطناعي / الرقميات محدودة. الفرصة: توفر Boud حلولاً أسرع وأكثر فعالية من حيث التكلفة مع تكنولوجيا فائقة.'
                    ],
                    [
                        'heading' => 'منافسو SaaS والأدوات الرقمية العامة',
                        'content' => 'توفر Salesforce و Microsoft و SAP و Oracle حلولاً رقمية لكن تفتقد خبرة استشارات التحول. منصات ميزات ثقيلة بدون توجيه استراتيجي. توافق رؤية 2030 الحد الأدنى. تتطلب تخصيص وتكامل شامل. الفرصة: تجمع Boud بين التكنولوجيا والاستشارات الخبيرة والمعرفة المحلية.'
                    ],
                    [
                        'heading' => 'المنافسون الإقليميون',
                        'content' => 'شركات الاستشارات المحلية وشركات التحول الرقمي الناشئة تفتقد الحجم والموارد والمصداقية الدولية. قدرات الذكاء الاصطناعي المحدودة والبنية التحتية للتكنولوجيا. عروض الخدمات الضيقة. الفرصة: تجمع Boud بين القدرات العالمية والخبرة السعودية العميقة مع أدوات الذكاء الاصطناعي المتخصصة.'
                    ],
                    [
                        'heading' => 'الخنادق التنافسية',
                        'content' => 'تشمل مزايا Boud نماذج الذكاء الاصطناعي الملكية المدربة على البيانات السعودية وإطار عمل توافق رؤية 2030 والنظام البيئي المتكامل (Hackify و SALIS و Connect AI) ومزيج الاستشارات والبرمجيات والخبرة المحلية مع المعايير العالمية. قابلية دفاع قوية ضد الداخلين الجدد.'
                    ]
                ]
            ]
        ]
    ],

    'ca_feature_comparison' => [
        'en' => [
            'comparison_table' => [
                'headers' => ['Feature/Capability', 'Boud', 'Traditional Consulting', 'SaaS Platforms'],
                'rows' => [
                    ['AI-Powered Assessment', 'Advanced', 'Manual process', 'Basic analysis'],
                    ['Digital Maturity Evaluation', 'Proprietary + customized', 'Generic approach', 'Limited scope'],
                    ['Vision 2030 Framework', 'Native integration', 'Custom development', 'Not available'],
                    ['Software + Consulting Combo', 'Integrated', 'Separate vendors', 'Software only'],
                    ['Implementation Support', 'Full program', 'High-touch', 'Self-service'],
                    ['Cost Structure', 'Flexible SaaS', 'High fixed costs', 'Subscription'],
                    ['Time to Value', '3-6 months', '6-12 months', '2-4 months'],
                    ['Ongoing Optimization', 'Continuous', 'Engagement-based', 'Self-directed'],
                    ['Ecosystem Integration', 'Hackify, SALIS, AI', 'External tools', 'Limited'],
                ]
            ]
        ],
        'ar' => [
            'comparison_table' => [
                'headers' => ['الميزة / الإمكانية', 'Boud', 'الاستشارات التقليدية', 'منصات SaaS'],
                'rows' => [
                    ['التقييم المدعوم بالذكاء الاصطناعي', 'متقدم', 'عملية يدوية', 'تحليل أساسي'],
                    ['تقييم النضج الرقمي', 'ملكية مخصصة', 'نهج عام', 'نطاق محدود'],
                    ['إطار رؤية 2030', 'تكامل أصلي', 'تطوير مخصص', 'غير متوفر'],
                    ['مزيج البرمجيات والاستشارات', 'متكامل', 'بائعون منفصلون', 'البرمجيات فقط'],
                    ['دعم التطبيق', 'برنامج كامل', 'عالي اللمس', 'خدمة ذاتية'],
                    ['هيكل التكلفة', 'SaaS مرن', 'تكاليف ثابتة عالية', 'الاشتراك'],
                    ['الوقت لتحقيق القيمة', '3-6 أشهر', '6-12 شهر', '2-4 أشهر'],
                    ['التحسين المستمر', 'مستمر', 'قائم على الانخراط', 'موجه ذاتي'],
                    ['تكامل النظام البيئي', 'Hackify و SALIS و AI', 'أدوات خارجية', 'محدود'],
                ]
            ]
        ]
    ],

    'ca_market_positioning' => [
        'en' => [
            'key_value' => [
                'items' => [
                    ['key' => 'Brand Positioning', 'value' => 'The leading AI-powered digital transformation partner for Saudi Vision 2030'],
                    ['key' => 'Target Market', 'value' => 'Large enterprises (500+) in financial, retail, telecom, manufacturing'],
                    ['key' => 'Key Differentiators', 'value' => 'AI + Consulting combo, Vision 2030 alignment, Integrated ecosystem'],
                    ['key' => 'Value Proposition', 'value' => 'Faster transformation, better outcomes, integrated solutions, local expertise'],
                    ['key' => 'Pricing Strategy', 'value' => 'Premium SaaS + advisory pricing, outcome-based options'],
                    ['key' => 'Go-to-Market Model', 'value' => 'Enterprise direct sales, strategic partnerships, thought leadership'],
                    ['key' => 'Competitive Advantage', 'value' => 'Proprietary AI, Vision 2030 compliance, ecosystem integration, proven ROI'],
                    ['key' => 'Market Opportunity', 'value' => '$2.5B+ addressable market in KSA and GCC region'],
                ]
            ]
        ],
        'ar' => [
            'key_value' => [
                'items' => [
                    ['key' => 'تحديد موضع العلامة التجارية', 'value' => 'الشريك الرائد للتحول الرقمي المدعوم بالذكاء الاصطناعي لرؤية السعودية 2030'],
                    ['key' => 'السوق المستهدفة', 'value' => 'المؤسسات الكبيرة (500+) في المالية والبيع بالتجزئة والاتصالات والتصنيع'],
                    ['key' => 'المميزات الرئيسية', 'value' => 'مزيج الذكاء الاصطناعي والاستشارات وتوافق رؤية 2030 والنظام البيئي المتكامل'],
                    ['key' => 'الاقتراح القيمي', 'value' => 'تحول أسرع ونتائج أفضل وحلول متكاملة وخبرة محلية'],
                    ['key' => 'استراتيجية التسعير', 'value' => 'تسعير SaaS + استشاري متميز وخيارات قائمة على النتائج'],
                    ['key' => 'نموذج الذهاب إلى السوق', 'value' => 'مبيعات المؤسسات المباشرة والشراكات الاستراتيجية والقيادة الفكرية'],
                    ['key' => 'الميزة التنافسية', 'value' => 'ذكاء اصطناعي ملكي ورؤية 2030 امتثال وتكامل النظام البيئي و ROI مثبت'],
                    ['key' => 'فرصة السوق', 'value' => 'سوق قابلة للعنوان بقيمة 2.5 مليار دولار + في المملكة العربية السعودية ومنطقة مجلس التعاون'],
                ]
            ]
        ]
    ],

    'ca_competitive_moat' => [
        'en' => [
            'text_content' => [
                'title' => 'Competitive Moat & Defensibility',
                'sections' => [
                    [
                        'heading' => 'Technology Moat',
                        'content' => 'Proprietary AI models trained specifically on Saudi Arabian organizational and market data create significant technological advantage. Continuous AI model improvement through customer data and feedback. Patent-eligible IP around assessment algorithms and Vision 2030 compliance frameworks. Barrier to entry: Requires significant R&D investment and data to replicate capabilities.'
                    ],
                    [
                        'heading' => 'Network & Ecosystem Moat',
                        'content' => 'Parent company ecosystem (Hackify, SALIS, Connect AI) creates significant switching costs and cross-selling opportunities. Growing network of partners, integrations, and ecosystem companies raises barriers for competitors. Network effects strengthen as more customers adopt multiple ecosystem products. Difficult for new entrants to replicate multi-product ecosystem.'
                    ],
                    [
                        'heading' => 'Market & Regulatory Moat',
                        'content' => 'Deep expertise in Saudi regulatory environment and Vision 2030 alignment creates competitive advantage. Government relationships and approved vendor status provide privileged market access. Local market knowledge and cultural understanding difficult to replicate by global competitors. First-mover advantage in Vision 2030 compliant solutions.'
                    ],
                    [
                        'heading' => 'Customer & Data Moat',
                        'content' => 'Growing customer base provides valuable training data for AI models. Long-term customer relationships (4+ year average) create strong retention and expansion opportunities. Established case studies and proof points create trust and lower customer acquisition barriers. Customer advisory board provides strategic insights and roadmap validation.'
                    ]
                ]
            ]
        ],
        'ar' => [
            'text_content' => [
                'title' => 'الخندق التنافسي وقابلية الدفاع',
                'sections' => [
                    [
                        'heading' => 'خندق التكنولوجيا',
                        'content' => 'تشكل نماذج الذكاء الاصطناعي الملكية المدربة خصيصاً على بيانات المنظمة والسوق السعودية ميزة تكنولوجية كبيرة. تحسين نموذج الذكاء الاصطناعي المستمر من خلال بيانات العملاء والتعليقات. ملكية فكرية قابلة للبراءات تتعلق بخوارزميات التقييم وأطر عمل الامتثال لرؤية 2030. حاجز الدخول: يتطلب استثمار R&D كبير والبيانات لتكرار الإمكانيات.'
                    ],
                    [
                        'heading' => 'خندق الشبكة والنظام البيئي',
                        'content' => 'يخلق النظام البيئي لشركة الأم (Hackify و SALIS و Connect AI) تكاليف التبديل الكبيرة وفرص البيع المتقاطع. شبكة متزايدة من الشركاء والتكاملات وشركات النظام البيئي ترفع حواجز المنافسين. تأثيرات الشبكة تقوي مع اعتماد المزيد من العملاء لمنتجات النظام البيئي المتعددة. من الصعب على الداخلين الجدد تكرار النظام البيئي متعدد المنتجات.'
                    ],
                    [
                        'heading' => 'خندق السوق والتنظيم',
                        'content' => 'الخبرة العميقة في البيئة التنظيمية السعودية وتوافق رؤية 2030 تخلق ميزة تنافسية. العلاقات الحكومية وحالة البائع المعتمد توفر وصول سوق مميز. المعرفة المحلية والفهم الثقافي يصعب تكراره من قبل المنافسين العالميين. ميزة الحركة الأولى في حلول الامتثال لرؤية 2030.'
                    ],
                    [
                        'heading' => 'خندق العميل والبيانات',
                        'content' => 'قاعدة العملاء المتنامية توفر بيانات تدريب قيمة لنماذج الذكاء الاصطناعي. تشكل علاقات العملاء طويلة الأجل (متوسط 4+ سنة) فرص احتفاظ وتوسع قوية. دراسات حالات وإثباتات نقاط مؤسسة تخلق ثقة وتقلل حواجز الحصول على العملاء. يوفر مجلس استشاري العملاء رؤية استراتيجية والتحقق من صحة خريطة الطريق.'
                    ]
                ]
            ]
        ]
    ]
], 'competitors' => [['name' => 'Deloitte Middle East', 'website' => 'deloitte.com', 'description' => 'Global consulting giant with strong GCC presence. High cost, generic, slow.', 'description_ar' => 'عملاق استشارات عالمي مع وجود قوي. تكلفة عالية وحلول عامة وبطء.', 'strengths' => ['Global brand', 'Large teams', 'Experience'], 'weaknesses' => ['Very expensive', 'Slow', 'Generic']], ['name' => 'Accenture Arabia', 'website' => 'accenture.com', 'description' => 'International firm with AI. Expensive, bureaucratic, slow decisions.', 'description_ar' => 'شركة دولية مع ذكاء اصطناعي. مكلفة وبيروقراطية وبطء.', 'strengths' => ['AI expertise', 'Scale', 'Knowledge'], 'weaknesses' => ['Expensive', 'Bureaucratic', 'Slow']], ['name' => 'TechTransform SA', 'website' => 'techtransform.sa', 'description' => 'Local startup with limited capability. Small team, weak integration, inconsistent.', 'description_ar' => 'شركة ناشئة محلية بقدرات محدودة. فريق صغير وتكامل ضعيف.', 'strengths' => ['Local', 'Cheap', 'Responsive'], 'weaknesses' => ['Limited scale', 'Weak AI', 'Inconsistent']]], 'prompts' => [['section_key' => 'dashboard_executive_summary', 'template' => 'Emphasize Boud as leading AI-driven transformation partner for Vision 2030. Highlight synergies between consulting and AI products.', 'template_ar' => 'أكد على موقع بود باعتباره شريك التحول الرائد. سلط الضوء على التآزر بين الاستشارات والمنتجات.'], ['section_key' => 'sf_market_size', 'template' => 'Calculate GCC digital transformation ($45B+). Include government/enterprise segments, project sizes, addressable market.', 'template_ar' => 'احسب سوق التحول الرقمي الخليجي ($45B+). قم بتضمين القطاعات والأحجام والسوق القابل للمعالجة.']]];
    }
}
