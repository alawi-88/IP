<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Venture;
use App\Models\VentureTab;
use App\Models\VentureSection;
use App\Models\VentureSectionConfig;
use Carbon\Carbon;

class VentureContentSeeder extends Seeder
{
    /**
     * Mapping from data file section keys to config section_slugs.
     * Data files use a different naming convention than the DB configs.
     */
    private array $sectionMapping = [
        // MVP Roadmap tab
        'mvp_feature_priority'     => 'mvp_feature_priority',
        'mvp_development_roadmap'  => 'mvp_development_timeline',
        'mvp_tech_stack'           => 'mvp_tech_stack',
        'mvp_resource_requirements'=> 'mvp_kpis',

        // Strategic Frameworks / USP tab
        'usp_unique_selling_points'    => 'sf_value_proposition',
        'usp_competitive_advantage'    => 'sf_business_model_canvas',
        'usp_differentiation_strategy' => 'sf_pricing_strategy',
        'usp_value_chain'              => 'sf_revenue_model',

        // Market Analysis / Customer Personas tab
        'cp_primary_persona'       => 'ma_customer_personas',
        'cp_secondary_persona'     => 'ma_target_audience',
        'cp_buyer_journey'         => 'ma_market_trends',
        'cp_pain_points_analysis'  => 'ma_market_barriers',

        // Financial Projections
        'fin_revenue_model'          => 'fp_revenue_forecast',
        'fin_cost_structure'         => 'fp_startup_costs',
        'fin_financial_projections'  => 'fp_break_even',
        'fin_funding_requirements'   => 'fp_funding_requirements',

        // Go to Market
        'gtm_launch_strategy'    => 'gtm_launch_strategy',
        'gtm_marketing_channels' => 'gtm_marketing_plan',
        'gtm_sales_funnel'       => 'gtm_sales_strategy',
        'gtm_partnerships'       => 'gtm_partnerships',
        'gtm_growth_metrics'     => 'gtm_growth_metrics',

        // Competitive Analysis
        'ca_competitor_overview'  => 'ca_competitor_overview',
        'ca_feature_comparison'  => 'ca_feature_comparison',
        'ca_market_positioning'  => 'ca_market_positioning',
        'ca_competitive_moat'    => 'ca_competitive_moat',
    ];

    /**
     * Venture definitions with metadata.
     */
    private function getVentureDefinitions(): array
    {
        return [
            [
                'title' => 'BOUD - Bundles of Unified Digital Transformation',
                'idea_prompt' => 'An AI-powered digital transformation consulting platform that helps organizations assess their digital maturity, generate innovation roadmaps, and ensure Vision 2030 compliance. The platform provides real-time analytics, team collaboration tools, and an integration marketplace for seamless digital transformation management.',
                'industry' => 'Digital Transformation Consulting',
                'target_market' => 'Saudi enterprises and government agencies seeking digital transformation aligned with Vision 2030',
                'business_model' => 'B2B SaaS with tiered subscription plans plus consulting service packages',
                'data_file' => 'boud_sections.php',
                'viability_score' => 82,
            ],
            [
                'title' => 'Hackafy - Hackathon Management Platform',
                'idea_prompt' => 'A comprehensive hackathon management platform that streamlines event creation, team formation, project submission, judging workflows, and leaderboard management. Features include AI-powered team matching, real-time collaboration tools, and sponsor management dashboards.',
                'industry' => 'Event Technology',
                'target_market' => 'Universities, tech companies, government innovation departments, and startup accelerators organizing hackathons and innovation challenges',
                'business_model' => 'SaaS platform with per-event pricing and enterprise annual subscriptions',
                'data_file' => 'hackify_sections.php',
                'viability_score' => 78,
            ],
            [
                'title' => 'Salis - AML Compliance & Transaction Screening',
                'idea_prompt' => 'An AI-powered Anti-Money Laundering (AML) compliance solution that provides real-time transaction screening, regulatory reporting, risk scoring, and compliance workflow automation. Designed to help financial institutions meet SAMA and international regulatory requirements while reducing false positives.',
                'industry' => 'Financial Technology / RegTech',
                'target_market' => 'Banks, fintech companies, money transfer services, and financial institutions in the GCC region',
                'business_model' => 'SaaS with volume-based pricing for transaction screening plus compliance consulting',
                'data_file' => 'salis_sections.php',
                'viability_score' => 88,
            ],
            [
                'title' => 'Connect AI - AI-Powered HR & Recruitment',
                'idea_prompt' => 'An intelligent HR and recruitment platform that uses AI to automate resume screening, candidate ranking, interview scheduling, and talent pipeline management. Features include bias detection, skills assessment, cultural fit analysis, and predictive hiring success scoring.',
                'industry' => 'HR Technology',
                'target_market' => 'Mid-to-large enterprises, recruitment agencies, and HR departments seeking to streamline hiring with AI-driven insights',
                'business_model' => 'SaaS with per-seat pricing for HR teams plus pay-per-hire model for recruitment agencies',
                'data_file' => 'connectai_sections.php',
                'viability_score' => 75,
            ],
        ];
    }

    public function run(): void
    {
        $participantId = 660; // Test user
        $now = Carbon::now();

        // Get all section configs grouped by tab
        $configs = VentureSectionConfig::where('is_visible', true)
            ->orderBy('sort_order')
            ->get();

        $configsByTab = $configs->groupBy('tab_slug');

        // Tab metadata
        $tabMeta = [
            'dashboard'             => ['label_en' => 'Dashboard',             'label_ar' => 'لوحة التحكم',       'icon' => 'chart-bar',           'sort_order' => 1],
            'strategic_frameworks'  => ['label_en' => 'Strategic Frameworks',  'label_ar' => 'الأطر الاستراتيجية', 'icon' => 'cube',               'sort_order' => 2],
            'market_analysis'       => ['label_en' => 'Market Analysis',       'label_ar' => 'تحليل السوق',       'icon' => 'users',              'sort_order' => 3],
            'financial_projections' => ['label_en' => 'Financial Projections', 'label_ar' => 'التوقعات المالية',  'icon' => 'currency-dollar',    'sort_order' => 4],
            'mvp_roadmap'           => ['label_en' => 'MVP Roadmap',           'label_ar' => 'خارطة طريق MVP',    'icon' => 'clipboard-list',     'sort_order' => 5],
            'risk_assessment'       => ['label_en' => 'Risk Assessment',       'label_ar' => 'تقييم المخاطر',     'icon' => 'exclamation-triangle','sort_order' => 6],
            'go_to_market'          => ['label_en' => 'Go to Market',          'label_ar' => 'استراتيجية الدخول', 'icon' => 'trending-up',        'sort_order' => 7],
            'competitive_analysis'  => ['label_en' => 'Competitive Analysis',  'label_ar' => 'التحليل التنافسي', 'icon' => 'shield-check',       'sort_order' => 8],
        ];

        foreach ($this->getVentureDefinitions() as $ventureData) {
            $this->command->info("Seeding venture: {$ventureData['title']}");

            // Load content data from file
            $dataFilePath = database_path("seeders/data/{$ventureData['data_file']}");
            $sectionData = file_exists($dataFilePath) ? require $dataFilePath : [];

            // Build reverse mapping: config_slug => data_file_key
            $reverseMap = array_flip($this->sectionMapping);

            // Create venture
            $venture = Venture::updateOrCreate(
                ['title' => $ventureData['title'], 'created_by' => $participantId],
                [
                    'idea_prompt'     => $ventureData['idea_prompt'],
                    'industry'        => $ventureData['industry'],
                    'target_market'   => $ventureData['target_market'],
                    'business_model'  => $ventureData['business_model'],
                    'status'          => 'completed',
                    'viability_score' => $ventureData['viability_score'],
                    'sections_total'     => $configs->count(),
                    'sections_completed' => $configs->count(),
                    'sections_failed'    => 0,
                    'is_archived'        => false,
                    'generation_started_at'   => $now->copy()->subMinutes(5),
                    'generation_completed_at' => $now,
                ]
            );

            // Create tabs and sections
            foreach ($configsByTab as $tabSlug => $tabConfigs) {
                $meta = $tabMeta[$tabSlug] ?? [
                    'label_en' => ucwords(str_replace('_', ' ', $tabSlug)),
                    'label_ar' => $tabSlug,
                    'icon' => 'document-text',
                    'sort_order' => 99,
                ];

                $tab = VentureTab::updateOrCreate(
                    ['venture_id' => $venture->id, 'slug' => $tabSlug],
                    [
                        'label_en'   => $meta['label_en'],
                        'label_ar'   => $meta['label_ar'],
                        'icon'       => $meta['icon'],
                        'sort_order' => $meta['sort_order'],
                        'is_visible' => true,
                    ]
                );

                foreach ($tabConfigs as $config) {
                    // Find matching data file key for this config section_slug
                    $dataKey = $reverseMap[$config->section_slug] ?? null;
                    $content = null;
                    $contentAr = null;

                    if ($dataKey && isset($sectionData[$dataKey])) {
                        $content = $sectionData[$dataKey]['en'] ?? null;
                        $contentAr = $sectionData[$dataKey]['ar'] ?? null;
                    }

                    // Generate default content for sections without data
                    if (empty($content)) {
                        $content = $this->generateDefaultContent(
                            $config->component_type,
                            $config->label_en,
                            $ventureData['title']
                        );
                        $contentAr = $this->generateDefaultContentAr(
                            $config->component_type,
                            $config->label_ar ?? $config->label_en,
                            $ventureData['title']
                        );
                    }

                    VentureSection::updateOrCreate(
                        [
                            'venture_id'     => $venture->id,
                            'venture_tab_id' => $tab->id,
                            'slug'           => $config->section_slug,
                        ],
                        [
                            'label_en'       => $config->label_en,
                            'label_ar'       => $config->label_ar,
                            'content'        => $content,
                            'content_ar'     => $contentAr,
                            'status'         => 'completed',
                            'sort_order'     => $config->sort_order,
                            'is_visible'     => true,
                            'component_type' => $config->component_type,
                            'generated_at'   => $now,
                        ]
                    );
                }
            }

            $this->command->info("  -> Created {$configs->count()} sections across " . $configsByTab->count() . " tabs");
        }

        $this->command->info('All 4 ventures seeded successfully!');
    }

    /**
     * Generate default EN content based on component type.
     */
    private function generateDefaultContent(string $componentType, string $label, string $ventureName): array
    {
        return match ($componentType) {
            'viability_score' => [
                'score' => rand(70, 90),
                'rating' => 'Strong',
                'breakdown' => [
                    ['category' => 'Market Opportunity', 'score' => rand(70, 95), 'weight' => 25],
                    ['category' => 'Team & Execution', 'score' => rand(65, 90), 'weight' => 25],
                    ['category' => 'Product Feasibility', 'score' => rand(70, 90), 'weight' => 25],
                    ['category' => 'Financial Viability', 'score' => rand(60, 85), 'weight' => 25],
                ],
            ],
            'text_content' => [
                'text_content' => [
                    'content' => "This section provides a detailed analysis of {$label} for {$ventureName}. The analysis covers key aspects including market dynamics, strategic positioning, and implementation considerations. Based on comprehensive research and AI-driven insights, the following recommendations have been formulated to guide strategic decision-making and operational planning.",
                    'highlights' => [
                        'Strong market positioning in target segment',
                        'Clear competitive advantages identified',
                        'Actionable implementation roadmap defined',
                        'Risk mitigation strategies in place',
                    ],
                ],
            ],
            'stat_cards' => [
                'stat_cards' => [
                    ['title' => 'Total Addressable Market', 'value' => '$' . rand(5, 50) . 'B', 'change' => '+' . rand(10, 30) . '%', 'trend' => 'up'],
                    ['title' => 'Year 1 Revenue Target', 'value' => '$' . rand(200, 800) . 'K', 'change' => 'Projected', 'trend' => 'up'],
                    ['title' => 'Customer Acquisition Cost', 'value' => '$' . rand(50, 300), 'change' => 'Estimated', 'trend' => 'neutral'],
                    ['title' => 'Lifetime Value', 'value' => '$' . rand(1000, 5000), 'change' => 'Projected', 'trend' => 'up'],
                ],
            ],
            'swot_grid' => [
                'swot_grid' => [
                    'strengths' => ['AI-driven technology advantage', 'Strong founding team expertise', 'First-mover in target market', 'Scalable platform architecture'],
                    'weaknesses' => ['Limited initial funding', 'Small team size', 'Brand awareness building needed', 'Dependency on AI model accuracy'],
                    'opportunities' => ['Growing market demand', 'Government digital initiatives', 'Strategic partnership potential', 'Regional expansion possibilities'],
                    'threats' => ['Established competitor entry', 'Regulatory changes', 'Technology disruption', 'Economic uncertainty'],
                ],
            ],
            'canvas_grid' => [
                'canvas_grid' => [
                    'key_partners' => ['Technology providers', 'Industry consultants', 'Distribution partners'],
                    'key_activities' => ['Platform development', 'AI model training', 'Customer acquisition'],
                    'key_resources' => ['Engineering team', 'AI/ML infrastructure', 'Domain expertise'],
                    'value_propositions' => ['AI-powered insights', 'Time and cost savings', 'Data-driven decisions'],
                    'customer_relationships' => ['Dedicated support', 'Self-service portal', 'Community forums'],
                    'channels' => ['Direct sales', 'Digital marketing', 'Partner referrals'],
                    'customer_segments' => ['Enterprise clients', 'Mid-market companies', 'Government entities'],
                    'cost_structure' => ['Cloud infrastructure', 'R&D investment', 'Sales & marketing'],
                    'revenue_streams' => ['Subscription fees', 'Professional services', 'API access'],
                ],
            ],
            'funnel_chart' => [
                'funnel_chart' => [
                    ['label' => 'TAM (Total Addressable Market)', 'value' => '$' . rand(10, 100) . 'B', 'percentage' => 100],
                    ['label' => 'SAM (Serviceable Addressable Market)', 'value' => '$' . rand(1, 10) . 'B', 'percentage' => rand(15, 30)],
                    ['label' => 'SOM (Serviceable Obtainable Market)', 'value' => '$' . rand(50, 500) . 'M', 'percentage' => rand(3, 8)],
                ],
            ],
            'pricing_table' => [
                'pricing_table' => [
                    'tiers' => [
                        ['name' => 'Starter', 'price' => '$' . rand(49, 99) . '/mo', 'features' => ['Basic features', 'Up to 5 users', 'Email support', 'Standard analytics']],
                        ['name' => 'Professional', 'price' => '$' . rand(199, 399) . '/mo', 'features' => ['All Starter features', 'Up to 25 users', 'Priority support', 'Advanced analytics', 'API access']],
                        ['name' => 'Enterprise', 'price' => 'Custom', 'features' => ['All Pro features', 'Unlimited users', 'Dedicated support', 'Custom integrations', 'SLA guarantee', 'On-premise option']],
                    ],
                ],
            ],
            'persona_cards' => [
                'persona_cards' => [
                    [
                        'name' => 'Primary Decision Maker',
                        'role' => 'CTO / VP Engineering',
                        'age_range' => '35-50',
                        'goals' => ['Drive digital transformation', 'Reduce operational costs', 'Improve efficiency'],
                        'pain_points' => ['Legacy system complexity', 'Budget constraints', 'Talent shortage'],
                        'motivation' => 'Deliver measurable business impact through technology innovation',
                    ],
                    [
                        'name' => 'End User Champion',
                        'role' => 'Project Manager / Team Lead',
                        'age_range' => '28-40',
                        'goals' => ['Streamline workflows', 'Better team collaboration', 'Data-driven insights'],
                        'pain_points' => ['Manual processes', 'Information silos', 'Reporting overhead'],
                        'motivation' => 'Simplify daily operations and demonstrate team productivity gains',
                    ],
                ],
            ],
            'risk_matrix' => [
                'risk_matrix' => [
                    ['risk' => 'Market adoption slower than expected', 'likelihood' => 'Medium', 'impact' => 'High', 'mitigation' => 'Diversify customer acquisition channels and offer freemium tier'],
                    ['risk' => 'Technology scalability issues', 'likelihood' => 'Low', 'impact' => 'High', 'mitigation' => 'Cloud-native architecture with auto-scaling and load testing'],
                    ['risk' => 'Regulatory compliance gaps', 'likelihood' => 'Medium', 'impact' => 'Medium', 'mitigation' => 'Engage compliance consultants and build flexible framework'],
                    ['risk' => 'Key team member departure', 'likelihood' => 'Low', 'impact' => 'Medium', 'mitigation' => 'Knowledge documentation, competitive compensation, equity vesting'],
                ],
            ],
            'comparison_table' => [
                'comparison_table' => [
                    'headers' => ['Feature', 'Our Solution', 'Competitor A', 'Competitor B'],
                    'rows' => [
                        ['AI-Powered Analysis', 'Advanced', 'Basic', 'None'],
                        ['Real-time Dashboard', 'Yes', 'Limited', 'Yes'],
                        ['API Integration', 'Full REST API', 'Limited', 'Partial'],
                        ['Arabic Language Support', 'Native', 'None', 'Partial'],
                        ['Pricing', 'Competitive', 'Premium', 'Mid-range'],
                    ],
                ],
            ],
            'key_value' => [
                'key_value' => [
                    ['key' => 'Market Position', 'value' => 'Challenger with differentiated AI capabilities'],
                    ['key' => 'Target Segment', 'value' => 'Mid-market to Enterprise in GCC region'],
                    ['key' => 'Competitive Advantage', 'value' => 'AI-first approach with bilingual support'],
                    ['key' => 'Growth Strategy', 'value' => 'Product-led growth with enterprise upsell'],
                ],
            ],
            'cost_table' => [
                'cost_table' => [
                    'categories' => [
                        ['category' => 'Technology & Infrastructure', 'year1' => '$120K', 'year2' => '$180K', 'year3' => '$250K'],
                        ['category' => 'Salaries & Benefits', 'year1' => '$350K', 'year2' => '$520K', 'year3' => '$750K'],
                        ['category' => 'Marketing & Sales', 'year1' => '$80K', 'year2' => '$150K', 'year3' => '$220K'],
                        ['category' => 'Operations & Admin', 'year1' => '$50K', 'year2' => '$70K', 'year3' => '$100K'],
                    ],
                    'total' => ['year1' => '$600K', 'year2' => '$920K', 'year3' => '$1.32M'],
                ],
            ],
            'line_chart' => [
                'line_chart' => [
                    'labels' => ['Q1 Y1', 'Q2 Y1', 'Q3 Y1', 'Q4 Y1', 'Q1 Y2', 'Q2 Y2', 'Q3 Y2', 'Q4 Y2'],
                    'datasets' => [
                        ['label' => 'Revenue', 'data' => [10, 25, 45, 80, 130, 200, 300, 420]],
                        ['label' => 'Expenses', 'data' => [100, 120, 130, 140, 160, 180, 200, 230]],
                    ],
                    'unit' => '$K',
                ],
            ],
            'progress_bars' => [
                'progress_bars' => [
                    ['label' => 'Customer Acquisition', 'value' => rand(40, 80), 'target' => 100, 'unit' => 'customers'],
                    ['label' => 'Monthly Recurring Revenue', 'value' => rand(30, 70), 'target' => 100, 'unit' => '% of target'],
                    ['label' => 'Product Development', 'value' => rand(50, 85), 'target' => 100, 'unit' => '% complete'],
                    ['label' => 'Market Penetration', 'value' => rand(10, 40), 'target' => 100, 'unit' => '% of SAM'],
                ],
            ],
            'timeline' => [
                'journey_timeline' => [
                    'stages' => [
                        ['title' => 'Phase 1: Foundation (Weeks 1-6)', 'description' => 'Core platform setup and architecture', 'touchpoints' => ['Team setup', 'Tech stack finalization', 'Database design'], 'actions' => ['Dev environment', 'CI/CD pipeline', 'Core module']],
                        ['title' => 'Phase 2: Core Features (Weeks 7-14)', 'description' => 'Main feature development', 'touchpoints' => ['Frontend dev', 'Backend APIs', 'Integration testing'], 'actions' => ['Build UI', 'API development', 'Testing']],
                        ['title' => 'Phase 3: Polish (Weeks 15-20)', 'description' => 'Enhancement and optimization', 'touchpoints' => ['Performance', 'Security', 'UX refinement'], 'actions' => ['Optimization', 'Security audit', 'Bug fixes']],
                        ['title' => 'Phase 4: Launch (Weeks 21-24)', 'description' => 'Production deployment and launch', 'touchpoints' => ['Deployment', 'Monitoring', 'Marketing'], 'actions' => ['Go live', 'Support setup', 'Customer onboarding']],
                    ],
                ],
            ],
            default => [
                'text_content' => [
                    'content' => "Analysis of {$label} for {$ventureName}.",
                    'highlights' => ['Key insight 1', 'Key insight 2', 'Key insight 3'],
                ],
            ],
        };
    }

    /**
     * Generate default AR content based on component type.
     */
    private function generateDefaultContentAr(string $componentType, string $label, string $ventureName): array
    {
        return match ($componentType) {
            'viability_score' => [
                'score' => rand(70, 90),
                'rating' => 'قوي',
                'breakdown' => [
                    ['category' => 'فرصة السوق', 'score' => rand(70, 95), 'weight' => 25],
                    ['category' => 'الفريق والتنفيذ', 'score' => rand(65, 90), 'weight' => 25],
                    ['category' => 'جدوى المنتج', 'score' => rand(70, 90), 'weight' => 25],
                    ['category' => 'الجدوى المالية', 'score' => rand(60, 85), 'weight' => 25],
                ],
            ],
            'text_content' => [
                'text_content' => [
                    'content' => "يقدم هذا القسم تحليلاً مفصلاً لـ {$label} لمشروع {$ventureName}. يغطي التحليل الجوانب الرئيسية بما في ذلك ديناميكيات السوق والموقع الاستراتيجي واعتبارات التنفيذ.",
                    'highlights' => [
                        'موقع قوي في السوق المستهدف',
                        'مزايا تنافسية واضحة',
                        'خارطة طريق تنفيذ قابلة للتطبيق',
                        'استراتيجيات تخفيف المخاطر',
                    ],
                ],
            ],
            'swot_grid' => [
                'swot_grid' => [
                    'strengths' => ['ميزة تقنية الذكاء الاصطناعي', 'خبرة فريق التأسيس', 'أول متحرك في السوق', 'هندسة منصة قابلة للتوسع'],
                    'weaknesses' => ['تمويل أولي محدود', 'حجم فريق صغير', 'الحاجة لبناء الوعي بالعلامة', 'الاعتماد على دقة النماذج'],
                    'opportunities' => ['طلب سوقي متزايد', 'مبادرات رقمية حكومية', 'إمكانية شراكات استراتيجية', 'فرص التوسع الإقليمي'],
                    'threats' => ['دخول منافسين راسخين', 'تغييرات تنظيمية', 'اضطراب تقني', 'عدم يقين اقتصادي'],
                ],
            ],
            default => [
                'text_content' => [
                    'content' => "تحليل {$label} لمشروع {$ventureName}.",
                    'highlights' => ['رؤية رئيسية 1', 'رؤية رئيسية 2', 'رؤية رئيسية 3'],
                ],
            ],
        };
    }
}
