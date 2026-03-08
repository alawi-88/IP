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
     * Keys must match frontend VA_SECTIONS (using underscores)
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
                    ['key' => 'features', 'title_en' => 'Features', 'title_ar' => 'الميزات'],
                    ['key' => 'validation', 'title_en' => 'Validation', 'title_ar' => 'التحقق'],
                    ['key' => 'timeline', 'title_en' => 'Timeline', 'title_ar' => 'الجدول الزمني'],
                    ['key' => 'marketing', 'title_en' => 'Marketing', 'title_ar' => 'التسويق'],
                    ['key' => 'budget', 'title_en' => 'Budget', 'title_ar' => 'الميزانية'],
                    ['key' => 'metrics', 'title_en' => 'Metrics', 'title_ar' => 'المقاييس'],
                ],
            ],
            'gtm_strategy' => [
                'title_en' => 'GTM Strategy',
                'title_ar' => 'استراتيجية GTM',
                'pages' => [
                    ['key' => 'overview', 'title_en' => 'Overview', 'title_ar' => 'نظرة عامة'],
                    ['key' => 'customer_segments', 'title_en' => 'Customer Segments', 'title_ar' => 'شرائح العملاء'],
                    ['key' => 'value_proposition', 'title_en' => 'Value Proposition', 'title_ar' => 'عرض القيمة'],
                ],
            ],
            'competitive_analysis' => [
                'title_en' => 'Competitive Analysis',
                'title_ar' => 'التحليل التنافسي',
                'pages' => [
                    ['key' => 'competitors', 'title_en' => 'Competitors', 'title_ar' => 'المنافسون'],
                    ['key' => 'matrix', 'title_en' => 'Competitor Matrix', 'title_ar' => 'مصفوفة المنافسة'],
                ],
            ],
        ];
    }
}
