<?php

namespace App\Services;

use App\Models\Startup;
use App\Models\VaSection;
use App\Models\VaPage;

class VaInitializationService
{
    /**
     * Initialize all VA sections and pages for a startup
     */
    public function initializeForStartup(Startup $startup): void
    {
        $sections = $this->getSectionStructure();

        foreach ($sections as $sectionKey => $sectionData) {
            $section = VaSection::create([
                'startup_id' => $startup->id,
                'section_key' => $sectionKey,
                'title_en' => $sectionData['title_en'],
                'title_ar' => $sectionData['title_ar'],
            ]);

            foreach ($sectionData['pages'] as $pageIndex => $pageData) {
                VaPage::create([
                    'va_section_id' => $section->id,
                    'page_key' => $pageData['key'],
                    'title_en' => $pageData['title_en'],
                    'title_ar' => $pageData['title_ar'],
                    'content' => [],
                    'order' => $pageIndex,
                ]);
            }
        }
    }

    /**
     * Get the complete VA section structure
     */
    private function getSectionStructure(): array
    {
        return [
            'foundation' => [
                'title_en' => 'Foundation',
                'title_ar' => 'الأساس',
                'pages' => [
                    ['key' => 'overview', 'title_en' => 'Overview', 'title_ar' => 'نظرة عامة'],
                    ['key' => 'market_analysis', 'title_en' => 'Market Analysis', 'title_ar' => 'تحليل السوق'],
                    ['key' => 'financial_model', 'title_en' => 'Financial Model', 'title_ar' => 'النموذج المالي'],
                ],
            ],
            'strategic_frameworks' => [
                'title_en' => 'Strategic Frameworks',
                'title_ar' => 'الأطر الاستراتيجية',
                'pages' => [
                    ['key' => 'hub', 'title_en' => 'Hub', 'title_ar' => 'المركز'],
                    ['key' => 'swot', 'title_en' => 'SWOT Analysis', 'title_ar' => 'تحليل SWOT'],
                    ['key' => 'mvp_canvas', 'title_en' => 'MVP Canvas', 'title_ar' => 'لوحة MVP'],
                    ['key' => 'bmc', 'title_en' => 'Business Model Canvas', 'title_ar' => 'نموذج العمل'],
                    ['key' => 'business_plan', 'title_en' => 'Business Plan', 'title_ar' => 'خطة العمل'],
                    ['key' => 'gtm_overview', 'title_en' => 'GTM Overview', 'title_ar' => 'نظرة عامة على GTM'],
                    ['key' => 'pestel', 'title_en' => 'PESTEL Analysis', 'title_ar' => 'تحليل PESTEL'],
                ],
            ],
            'path_to_mvp' => [
                'title_en' => 'Path to MVP',
                'title_ar' => 'الطريق إلى MVP',
                'pages' => [
                    ['key' => 'feature_prioritization', 'title_en' => 'Feature Prioritization', 'title_ar' => 'أولويات الميزات'],
                    ['key' => 'market_validation', 'title_en' => 'Market Validation', 'title_ar' => 'التحقق من السوق'],
                    ['key' => 'development_timeline', 'title_en' => 'Development Timeline', 'title_ar' => 'جدول التطوير'],
                    ['key' => 'marketing_plan', 'title_en' => 'Marketing Plan', 'title_ar' => 'خطة التسويق'],
                    ['key' => 'budget_planning', 'title_en' => 'Budget Planning', 'title_ar' => 'التخطيط المالي'],
                    ['key' => 'success_metrics', 'title_en' => 'Success Metrics', 'title_ar' => 'مقاييس النجاح'],
                ],
            ],
            'gtm_strategy' => [
                'title_en' => 'GTM Strategy',
                'title_ar' => 'استراتيجية GTM',
                'pages' => [
                    ['key' => 'executive_summary', 'title_en' => 'Executive Summary', 'title_ar' => 'الملخص التنفيذي'],
                    ['key' => 'market_segmentation', 'title_en' => 'Market Segmentation', 'title_ar' => 'تقسيم السوق'],
                    ['key' => 'value_proposition', 'title_en' => 'Value Proposition', 'title_ar' => 'عرض القيمة'],
                    ['key' => 'pricing_strategy', 'title_en' => 'Pricing Strategy', 'title_ar' => 'استراتيجية التسعير'],
                    ['key' => 'sales_strategy', 'title_en' => 'Sales Strategy', 'title_ar' => 'استراتيجية المبيعات'],
                    ['key' => 'marketing_strategy', 'title_en' => 'Marketing Strategy', 'title_ar' => 'استراتيجية التسويق'],
                    ['key' => 'distribution_channels', 'title_en' => 'Distribution Channels', 'title_ar' => 'قنوات التوزيع'],
                    ['key' => 'launch_plan', 'title_en' => 'Launch Plan', 'title_ar' => 'خطة الإطلاق'],
                    ['key' => 'partnerships', 'title_en' => 'Partnerships', 'title_ar' => 'الشراكات'],
                    ['key' => 'customer_success', 'title_en' => 'Customer Success', 'title_ar' => 'نجاح العملاء'],
                    ['key' => 'growth_strategy', 'title_en' => 'Growth Strategy', 'title_ar' => 'استراتيجية النمو'],
                ],
            ],
            'competitive_analysis' => [
                'title_en' => 'Competitive Analysis',
                'title_ar' => 'التحليل التنافسي',
                'pages' => [
                    ['key' => 'competitor_profiles', 'title_en' => 'Competitor Profiles', 'title_ar' => 'ملفات المنافسين'],
                    ['key' => 'competitive_matrix', 'title_en' => 'Competitive Matrix', 'title_ar' => 'مصفوفة المنافسة'],
                ],
            ],
        ];
    }
}
