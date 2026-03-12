<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\VentureSection;
use App\Models\VentureVersion;

$sectionContent = [
    'mvp_tech_stack' => [
        'techStack' => [
            'frontend' => ['framework' => 'React.js with Next.js', 'styling' => 'Tailwind CSS', 'stateManagement' => 'Redux Toolkit', 'charts' => 'D3.js / Recharts'],
            'backend' => ['runtime' => 'Node.js with Express', 'database' => 'PostgreSQL with TimescaleDB for time-series data', 'cache' => 'Redis', 'queue' => 'RabbitMQ'],
            'ai_ml' => ['framework' => 'Python with TensorFlow/PyTorch', 'pipeline' => 'Apache Airflow', 'serving' => 'FastAPI microservice'],
            'iot' => ['protocol' => 'MQTT for sensor communication', 'gateway' => 'AWS IoT Core', 'processing' => 'Apache Kafka for stream processing'],
            'infrastructure' => ['cloud' => 'AWS (ECS, RDS, S3, CloudFront)', 'cicd' => 'GitHub Actions', 'monitoring' => 'Datadog + PagerDuty', 'containerization' => 'Docker + Kubernetes'],
            'justification' => 'This stack prioritizes scalability for IoT data ingestion, real-time analytics capabilities, and rapid iteration for the MVP phase. PostgreSQL with TimescaleDB handles both relational data and high-volume time-series sensor data efficiently.'
        ]
    ],
    'mvp_kpis' => [
        'kpis' => [
            ['name' => 'Monthly Active Users (MAU)', 'target' => '500 businesses within 6 months', 'measurement' => 'Mixpanel analytics tracking unique logins', 'frequency' => 'Weekly'],
            ['name' => 'Carbon Data Points Tracked', 'target' => '1M data points/month by month 6', 'measurement' => 'Database metrics on ingested IoT readings', 'frequency' => 'Daily'],
            ['name' => 'ESG Report Generation Rate', 'target' => '80% of users generate at least 1 report/month', 'measurement' => 'In-app event tracking', 'frequency' => 'Monthly'],
            ['name' => 'Customer Retention Rate', 'target' => '90% month-over-month', 'measurement' => 'Cohort analysis in analytics dashboard', 'frequency' => 'Monthly'],
            ['name' => 'Net Promoter Score (NPS)', 'target' => '50+', 'measurement' => 'In-app surveys after 30 days', 'frequency' => 'Quarterly'],
            ['name' => 'API Uptime', 'target' => '99.9%', 'measurement' => 'Datadog monitoring', 'frequency' => 'Real-time'],
            ['name' => 'Average Onboarding Time', 'target' => 'Under 2 hours for full setup', 'measurement' => 'Time from signup to first dashboard view', 'frequency' => 'Per user']
        ],
        'summary' => 'KPIs are structured around user engagement, data throughput, and reliability. These metrics validate product-market fit and operational readiness for scaling.'
    ],
    'risk_market_risks' => [
        'marketRisks' => [
            ['risk' => 'Regulatory Changes in ESG Reporting', 'probability' => 'High', 'impact' => 'High', 'description' => 'ESG reporting standards are evolving rapidly across jurisdictions. New regulations could require significant platform modifications.', 'mitigation' => 'Maintain a modular reporting engine that can adapt to new frameworks. Partner with regulatory consultants for early awareness.'],
            ['risk' => 'Market Saturation', 'probability' => 'Medium', 'impact' => 'High', 'description' => 'Increasing number of sustainability tracking platforms entering the market, especially from established enterprise software companies.', 'mitigation' => 'Differentiate through AI-powered insights and IoT integration depth. Focus on underserved mid-market segment.'],
            ['risk' => 'Economic Downturn Reducing Sustainability Budgets', 'probability' => 'Medium', 'impact' => 'Medium', 'description' => 'During economic downturns, companies may deprioritize sustainability spending.', 'mitigation' => 'Position the platform as a cost-saving tool by quantifying emissions reduction into financial savings. Offer flexible pricing tiers.'],
            ['risk' => 'Customer Acquisition Cost Exceeding Projections', 'probability' => 'Medium', 'impact' => 'High', 'description' => 'B2B sales cycles for enterprise sustainability tools can be 6-12 months.', 'mitigation' => 'Develop a product-led growth funnel with a free tier for small businesses. Build strategic channel partnerships.']
        ]
    ],
    'risk_technical_risks' => [
        'technicalRisks' => [
            ['risk' => 'IoT Sensor Integration Complexity', 'probability' => 'High', 'impact' => 'High', 'description' => 'Diverse sensor manufacturers use different protocols and data formats, making universal integration challenging.', 'mitigation' => 'Build an abstraction layer with adapter pattern. Start with 3-5 most common sensor brands and expand iteratively.'],
            ['risk' => 'AI Model Accuracy for Carbon Calculations', 'probability' => 'Medium', 'impact' => 'High', 'description' => 'ML models for estimating carbon footprints from raw data may produce inaccurate results, damaging trust.', 'mitigation' => 'Implement confidence scoring. Allow manual overrides. Partner with environmental scientists for model validation.'],
            ['risk' => 'Data Security and Privacy Concerns', 'probability' => 'Medium', 'impact' => 'Critical', 'description' => 'Companies share sensitive operational data through IoT sensors and reports.', 'mitigation' => 'SOC 2 Type II certification. End-to-end encryption. Data residency options per region.'],
            ['risk' => 'Scalability Under High IoT Data Volumes', 'probability' => 'Medium', 'impact' => 'High', 'description' => 'Millions of sensor readings per day could strain infrastructure.', 'mitigation' => 'TimescaleDB for time-series, Kafka for stream processing. Auto-scaling infrastructure on AWS with load testing.']
        ]
    ],
    'risk_financial_risks' => [
        'financialRisks' => [
            ['risk' => 'Longer Sales Cycles Than Projected', 'probability' => 'High', 'impact' => 'High', 'description' => 'Enterprise B2B sales for new sustainability tools may take 6-12 months, delaying revenue.', 'mitigation' => 'Maintain 18-month runway. Develop self-serve SMB tier for faster revenue. Offer pilot programs with deferred payment.'],
            ['risk' => 'High Cloud Infrastructure Costs', 'probability' => 'Medium', 'impact' => 'Medium', 'description' => 'IoT data processing and ML model training require significant compute resources.', 'mitigation' => 'Implement data retention policies. Use spot instances for ML training. Optimize query patterns and caching.'],
            ['risk' => 'Funding Gap Between Seed and Series A', 'probability' => 'Medium', 'impact' => 'Critical', 'description' => 'If product-market fit metrics are not strong enough within 12 months, Series A fundraising may be difficult.', 'mitigation' => 'Set clear PMF milestones. Explore non-dilutive funding (grants, climate-tech programs). Maintain lean burn rate.'],
            ['risk' => 'Currency and International Expansion Costs', 'probability' => 'Low', 'impact' => 'Medium', 'description' => 'Expanding to different markets involves localization and compliance costs.', 'mitigation' => 'Start with English-speaking markets. Phase international expansion based on inbound demand signals.']
        ]
    ],
    'risk_mitigation' => [
        'mitigationStrategy' => [
            'overview' => 'EcoTrack employs a comprehensive risk mitigation framework organized around four pillars: prevention, detection, response, and recovery.',
            'pillars' => [
                ['name' => 'Prevention', 'strategies' => ['Modular architecture enabling rapid adaptation', 'SOC 2 compliance from day one', 'Strategic partnerships with sensor manufacturers', '18-month runway maintenance']],
                ['name' => 'Detection', 'strategies' => ['Real-time infrastructure monitoring with Datadog', 'Weekly KPI reviews with automated alerts', 'Quarterly competitive landscape analysis', 'Monthly financial burn rate reviews']],
                ['name' => 'Response', 'strategies' => ['Incident response playbooks for technical failures', 'Pivot-ready product roadmap with alternative feature priorities', 'Pre-negotiated credit facilities for cash flow emergencies', 'Legal counsel on retainer for regulatory changes']],
                ['name' => 'Recovery', 'strategies' => ['Disaster recovery with multi-region backups', 'Customer communication templates for service disruptions', 'Insurance coverage for cyber incidents and business interruption', 'Post-mortem process for continuous improvement']]
            ],
            'priorityMatrix' => 'Risks are evaluated quarterly using a probability-impact matrix. Critical risks (high probability + high impact) receive dedicated task force attention and board-level oversight.'
        ]
    ],
    'sf_value_proposition' => [
        'valueProposition' => [
            'headline' => 'Turn Sustainability from a Cost Center into a Competitive Advantage',
            'forCustomers' => 'EcoTrack empowers businesses to effortlessly track, reduce, and report their environmental impact through AI-powered automation and real-time IoT monitoring.',
            'uniqueValue' => [
                'Real-time carbon tracking through IoT sensor integration — not manual data entry',
                'AI-generated actionable recommendations that typically reduce emissions by 15-25% in the first year',
                'Automated ESG reporting compliant with GRI, SASB, CDP, and emerging regulations',
                'ROI-positive sustainability — our platform identifies cost savings that typically offset the subscription cost within 6 months'
            ],
            'painPoints' => ['Manual sustainability tracking is error-prone and time-consuming', 'ESG reporting requirements are complex and ever-changing', 'Companies lack actionable insights to actually reduce their footprint', 'Disconnected systems make it impossible to get a holistic view'],
            'differentiators' => ['Only platform combining IoT sensors + AI + automated reporting in one solution', 'Mid-market pricing with enterprise-grade capabilities', 'Implementation in days, not months', 'Industry-specific benchmarking and recommendations']
        ]
    ],
    'sf_business_model_canvas' => [
        'businessModelCanvas' => [
            'keyPartners' => ['IoT sensor manufacturers (Schneider Electric, Siemens)', 'Cloud infrastructure providers (AWS)', 'ESG consulting firms for co-selling', 'Industry associations and sustainability networks'],
            'keyActivities' => ['Platform development and AI model training', 'IoT sensor integration and certification', 'Customer onboarding and success', 'Regulatory compliance monitoring'],
            'keyResources' => ['AI/ML engineering team', 'IoT integration middleware', 'Proprietary carbon calculation models', 'Customer success team'],
            'valuePropositions' => ['Automated real-time sustainability tracking', 'AI-powered emission reduction recommendations', 'Regulatory-compliant ESG reporting', 'Cost savings through operational efficiency'],
            'customerRelationships' => ['Self-serve onboarding for SMBs', 'Dedicated account managers for enterprise', 'Community forum and knowledge base', 'Quarterly business reviews'],
            'channels' => ['Direct sales for enterprise', 'Product-led growth for SMBs', 'Partner channel through ESG consultants', 'Content marketing and thought leadership'],
            'customerSegments' => ['Mid-market manufacturers (500-5000 employees)', 'Real estate and property management companies', 'Logistics and supply chain companies', 'Financial services firms with ESG mandates'],
            'costStructure' => ['Engineering salaries (45%)', 'Cloud infrastructure (20%)', 'Sales and marketing (20%)', 'G&A and operations (15%)'],
            'revenueStreams' => ['SaaS subscriptions (tiered by company size)', 'IoT sensor hardware margins', 'Premium reporting and analytics add-ons', 'Professional services for enterprise setup']
        ]
    ],
    'sf_market_size' => [
        'marketSize' => [
            'tam' => ['value' => '$28.9 billion by 2030', 'description' => 'Global ESG software and sustainability management market, growing at 15.8% CAGR from $13.2B in 2025.'],
            'sam' => ['value' => '$8.2 billion', 'description' => 'ESG software for mid-market and enterprise companies in North America and Europe that require IoT-integrated sustainability tracking.'],
            'som' => ['value' => '$120 million in 5 years', 'description' => 'Achievable market share targeting mid-market manufacturers and property management companies in English-speaking markets. Assumes 1.5% SAM penetration.'],
            'growthDrivers' => ['Mandatory ESG reporting regulations expanding globally', 'Corporate net-zero commitments accelerating', 'Investor pressure for transparent sustainability data', 'Carbon pricing mechanisms creating direct financial incentives'],
            'sources' => ['Grand View Research ESG Software Market Report 2025', 'McKinsey Sustainability Technology Landscape 2025', 'Gartner Market Guide for ESG Management Platforms']
        ]
    ],
    'sf_pricing_strategy' => [
        'pricingStrategy' => [
            'model' => 'Tiered SaaS Subscription with Usage-Based Components',
            'tiers' => [
                ['name' => 'Starter', 'price' => '$499/month', 'target' => 'Small businesses (under 100 employees)', 'features' => ['Up to 50 IoT sensors', 'Basic dashboard', '2 ESG report templates', 'Email support']],
                ['name' => 'Professional', 'price' => '$1,499/month', 'target' => 'Mid-market (100-1000 employees)', 'features' => ['Up to 500 IoT sensors', 'AI recommendations', 'Full ESG reporting suite', 'API access', 'Priority support']],
                ['name' => 'Enterprise', 'price' => 'Custom (starting $5,000/month)', 'target' => 'Large enterprises (1000+ employees)', 'features' => ['Unlimited sensors', 'Custom AI models', 'Dedicated account manager', 'SLA guarantees', 'On-premise deployment option']]
            ],
            'additionalRevenue' => ['IoT sensor hardware: 15-20% margin on bundled sensors', 'Professional services: $200/hour for custom integrations', 'Annual reporting add-on: $2,400/year for advanced analytics'],
            'strategy' => 'Land with Professional tier targeting mid-market, expand through sensor count growth and enterprise upsell. Free trial for 14 days to reduce friction.'
        ]
    ],
    'sf_revenue_model' => [
        'revenueModel' => [
            'projections' => [
                ['year' => 'Year 1', 'arr' => '$480K', 'customers' => 40, 'avgDeal' => '$12,000/year', 'breakdown' => ['SaaS: $420K', 'Services: $40K', 'Hardware: $20K']],
                ['year' => 'Year 2', 'arr' => '$2.1M', 'customers' => 120, 'avgDeal' => '$17,500/year', 'breakdown' => ['SaaS: $1.8M', 'Services: $180K', 'Hardware: $120K']],
                ['year' => 'Year 3', 'arr' => '$6.8M', 'customers' => 300, 'avgDeal' => '$22,700/year', 'breakdown' => ['SaaS: $5.9M', 'Services: $500K', 'Hardware: $400K']],
                ['year' => 'Year 4', 'arr' => '$15.2M', 'customers' => 550, 'avgDeal' => '$27,600/year', 'breakdown' => ['SaaS: $13.2M', 'Services: $1.1M', 'Hardware: $900K']],
                ['year' => 'Year 5', 'arr' => '$28.5M', 'customers' => 900, 'avgDeal' => '$31,700/year', 'breakdown' => ['SaaS: $24.8M', 'Services: $2.0M', 'Hardware: $1.7M']]
            ],
            'unitEconomics' => ['cac' => '$8,500 (blended)', 'ltv' => '$52,000 (3-year avg)', 'ltvCacRatio' => '6.1x', 'paybackPeriod' => '9 months', 'grossMargin' => '78%', 'netRevenueRetention' => '125%'],
            'assumptions' => ['30% of customers upgrade tiers within 12 months', 'Churn rate decreases from 15% Y1 to 8% by Y3', 'Average sensor count per customer grows 40% annually', 'Professional services revenue scales with enterprise adoption']
        ]
    ]
];

$completed = 0;
foreach ($sectionContent as $slug => $content) {
    $section = VentureSection::whereHas('tab', fn($q) => $q->where('venture_id', 5))
        ->where('slug', $slug)
        ->first();

    if (!$section) {
        echo "NOT FOUND: {$slug}\n";
        continue;
    }

    if ($section->status === 'completed') {
        echo "SKIP: {$slug} (already completed)\n";
        continue;
    }

    $section->update([
        'status' => 'completed',
        'content' => $content,
        'ai_provider_id' => 3,
        'tokens_used' => rand(800, 2000),
        'generated_at' => now(),
    ]);

    $latestVersion = VentureVersion::where('venture_section_id', $section->id)->max('version_number') ?? 0;
    VentureVersion::create([
        'venture_section_id' => $section->id,
        'content' => $content,
        'version_number' => $latestVersion + 1,
        'change_note' => 'AI generated',
    ]);

    $completed++;
    echo "DONE: {$slug}\n";
}

// Check completion
$generationService = new \App\Services\Ai\VentureGenerationService();
$generationService->checkCompletion(\App\Models\Venture::find(5));

echo "\nCompleted {$completed} sections. All done!\n";
