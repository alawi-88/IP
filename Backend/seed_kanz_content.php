<?php
/**
 * Seed all 34 Kanz venture sections with realistic content.
 */
require '/var/www/vendor/autoload.php';
$app = require_once '/var/www/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\VentureSection;

$ventureId = 6;

$contentMap = [
    // === DASHBOARD ===
    'dashboard_about' => [
        'content' => "Kanz (كنز) is a pioneering Saudi fintech application designed to cultivate financial literacy among children and teenagers aged 6-18. Headquartered in Riyadh's Al-Yasmin District, Kanz bridges the gap between traditional banking and modern digital finance education.\n\nThe platform provides children with their own savings accounts and prepaid debit cards, while empowering parents with comprehensive oversight tools. Through task-based earning, goal-oriented savings, and gamified financial education, Kanz transforms money management from an abstract concept into an engaging, hands-on experience.\n\nKey features include instant family money transfers, spending controls, educational content delivered in Arabic, and a reward system that incentivizes responsible financial behavior. Kanz operates on a freemium model with premium family subscriptions, positioning itself at the intersection of FinTech and EdTech in the rapidly growing Saudi digital economy.\n\nAligned with Saudi Vision 2030's goals of financial inclusion and youth empowerment, Kanz addresses the critical need for early financial education in the Kingdom, where over 30% of the population is under 18."
    ],

    'dashboard_swot' => [
        'strengths' => [
            'First-mover advantage in Saudi children\'s fintech market',
            'Arabic-first design aligned with local culture and regulations',
            'Dual value proposition: financial literacy for kids + parental controls',
            'Gamification increases engagement and retention',
            'Strategic alignment with Saudi Vision 2030 financial inclusion goals'
        ],
        'weaknesses' => [
            'Dependency on banking partnerships for card issuance',
            'Limited brand recognition as a new entrant',
            'High customer acquisition cost for family-oriented products',
            'Regulatory complexity in children\'s financial services',
            'Need for continuous content creation in Arabic'
        ],
        'opportunities' => [
            'GCC expansion to UAE, Bahrain, Kuwait with similar demographics',
            'Partnership with Saudi Ministry of Education for school integration',
            'Corporate partnerships for employee family benefit programs',
            'Open banking regulations enabling new product features',
            'Growing smartphone penetration among Saudi youth (85%+)'
        ],
        'threats' => [
            'Major banks launching competing children\'s products',
            'Regulatory changes in children\'s data protection',
            'Economic downturn reducing family discretionary spending',
            'Regional competitors from UAE entering Saudi market',
            'Parental resistance to children having digital financial tools'
        ]
    ],

    'dashboard_pestel' => [
        'political' => [
            'Saudi Vision 2030 strongly supports fintech innovation',
            'SAMA (Saudi Central Bank) fintech sandbox facilitates testing',
            'Government push for cashless economy benefits digital payment adoption'
        ],
        'economic' => [
            'Saudi GDP growth at 3.1% supports consumer spending',
            'High smartphone penetration (>95%) enables mobile-first approach',
            'Growing middle class with increasing disposable income'
        ],
        'social' => [
            'Over 30% of Saudi population is under 18',
            'Cultural emphasis on family and children\'s education',
            'Increasing awareness of financial literacy importance'
        ],
        'technological' => [
            'Advanced digital payment infrastructure in Saudi Arabia',
            'Open Banking framework being implemented by SAMA',
            'High 5G coverage enabling seamless mobile experiences'
        ],
        'environmental' => [
            'Digital-first approach reduces paper waste from traditional banking',
            'Cloud infrastructure with regional data centers available',
            'ESG considerations increasingly important for investors'
        ],
        'legal' => [
            'SAMA fintech regulations provide clear licensing framework',
            'PDPL (Personal Data Protection Law) requirements for children\'s data',
            'Anti-money laundering compliance requirements for all transactions'
        ]
    ],

    'dashboard_porters' => [
        'items' => [
            ['key' => 'Threat of New Entrants', 'value' => 'Medium — Regulatory barriers and banking partnerships create moderate entry barriers, but large banks could easily launch competing products.'],
            ['key' => 'Bargaining Power of Suppliers', 'value' => 'High — Dependency on banking partners for card issuance and payment processing gives suppliers significant leverage.'],
            ['key' => 'Bargaining Power of Buyers', 'value' => 'Medium — Parents have alternatives but switching costs increase as children build savings history and habits on the platform.'],
            ['key' => 'Threat of Substitutes', 'value' => 'Low-Medium — Cash allowances and basic bank accounts exist but lack the educational and engagement features Kanz provides.'],
            ['key' => 'Competitive Rivalry', 'value' => 'Low — Currently no direct competitor in Saudi children\'s fintech space, though this will likely change as the market matures.']
        ]
    ],

    'dashboard_cage' => [
        'items' => [
            ['key' => 'Cultural Distance', 'value' => 'Kanz is designed Arabic-first with Islamic finance principles, minimizing cultural distance within GCC markets. Expansion to non-Arabic markets would require significant localization.'],
            ['key' => 'Administrative Distance', 'value' => 'Saudi Arabia\'s SAMA regulatory framework is well-defined for fintech. GCC expansion benefits from similar regulatory environments and mutual recognition agreements.'],
            ['key' => 'Geographic Distance', 'value' => 'Headquartered in Riyadh with initial focus on Saudi Arabia. GCC expansion is geographically convenient with shared time zones and regional infrastructure.'],
            ['key' => 'Economic Distance', 'value' => 'High GDP per capita in Saudi Arabia and GCC supports premium pricing model. Expansion to lower-income MENA markets would require pricing adjustments.']
        ]
    ],

    'dashboard_viability' => [
        'score' => 78,
        'rating' => 'Strong',
        'justification' => 'Kanz addresses a clear market gap in Saudi children\'s financial literacy with strong alignment to Vision 2030. The combination of regulatory support, demographic tailwinds, and first-mover advantage positions it well for success. Key risks around banking partnerships and customer acquisition costs are manageable.',
        'breakdown' => [
            ['label' => 'Market Opportunity', 'score' => 85],
            ['label' => 'Product-Market Fit', 'score' => 80],
            ['label' => 'Competitive Position', 'score' => 82],
            ['label' => 'Financial Viability', 'score' => 70],
            ['label' => 'Team & Execution', 'score' => 72],
            ['label' => 'Regulatory Environment', 'score' => 80]
        ]
    ],

    'dashboard_market_size' => [
        ['label' => 'TAM', 'value' => 'SAR 2.4B', 'description' => 'Total addressable market: All Saudi families with children 6-18 (3.2M households)'],
        ['label' => 'SAM', 'value' => 'SAR 840M', 'description' => 'Serviceable addressable market: Digitally-active urban Saudi families (1.1M households)'],
        ['label' => 'SOM', 'value' => 'SAR 84M', 'description' => 'Serviceable obtainable market: 10% penetration of SAM within 3 years'],
        ['label' => 'CAGR', 'value' => '24.5%', 'description' => 'Saudi fintech market compound annual growth rate (2024-2030)']
    ],

    'dashboard_industry_insight' => [
        'content' => "The children's fintech sector globally is experiencing rapid growth, with the market projected to reach \$5.2 billion by 2028. In the MENA region, this segment remains largely untapped, presenting a significant opportunity for early movers.\n\nSaudi Arabia's unique demographic profile — with over 30% of its population under 18 and one of the world's highest smartphone penetration rates — creates an ideal environment for a children's digital banking product. The Kingdom's Vision 2030 initiative specifically emphasizes financial literacy and digital transformation, providing regulatory tailwinds.\n\nGlobal comparables like Greenlight (US, valued at \$2.3B), GoHenry (UK, acquired for \$160M), and Pixpay (France, raised €11M Series A) demonstrate strong investor appetite and viable business models in this space. However, no equivalent product exists in the MENA region with Arabic-first design and Islamic finance compatibility.\n\nThe Saudi open banking framework, currently being rolled out by SAMA, will further enable innovative financial products and reduce barriers to entry, making the timing particularly favorable for Kanz's market entry."
    ],

    // === STRATEGIC FRAMEWORKS ===
    'sf_ip_strategy' => [
        'content' => "Kanz's intellectual property strategy focuses on building defensible competitive moats through multiple protection layers.\n\nThe brand name 'Kanz' (كنز), meaning 'treasure' in Arabic, is registered as a trademark in Saudi Arabia across Class 36 (financial services) and Class 42 (software services). The distinctive app icon and character mascots used in the gamification system are protected under Saudi copyright law.\n\nThe proprietary gamification engine — which adapts financial literacy content based on a child's age, spending patterns, and learning progress — represents Kanz's core technical IP. While the individual components are not patentable, the specific combination and algorithmic approach create a defensible trade secret. All employees and contractors sign comprehensive IP assignment and non-disclosure agreements.\n\nKanz's Arabic financial literacy curriculum, developed with certified financial planners and child psychologists, represents significant content IP. This curriculum is structured in progressive modules that align with Saudi Ministry of Education standards, creating both educational value and a barrier to replication.\n\nThe data analytics platform that provides parents with behavioral insights about their children's financial habits generates proprietary datasets and models that improve with scale, creating a network effect-driven IP advantage."
    ],

    'sf_swot' => [
        'strengths' => [
            'Arabic-first UX with Islamic finance principles embedded',
            'Proprietary gamification engine for financial education',
            'Dual revenue streams: subscriptions + interchange fees',
            'Strong alignment with Saudi Vision 2030 initiatives',
            'Scalable technology architecture for GCC expansion'
        ],
        'weaknesses' => [
            'Pre-revenue stage with unproven unit economics',
            'Banking license dependency for core product features',
            'Small founding team with limited fintech operating history',
            'High initial investment required for regulatory compliance',
            'Content creation costs for age-appropriate Arabic material'
        ],
        'opportunities' => [
            'No direct Arabic-first competitor in children\'s fintech',
            'SAMA sandbox program reduces time-to-market',
            'School partnership channel for B2B2C distribution',
            'Eid and holiday gifting features for seasonal revenue spikes',
            'White-label opportunities with existing Saudi banks'
        ],
        'threats' => [
            'Al Rajhi Bank or SNB launching similar products',
            'International players (Greenlight, GoHenry) entering MENA',
            'Stricter children\'s data regulations increasing compliance costs',
            'Currency fluctuation risk for international expansion',
            'Cybersecurity threats targeting financial data of minors'
        ]
    ],

    'sf_pestel' => [
        'political' => [
            'Saudi Crown Prince\'s direct support for fintech ecosystem',
            'Fintech Saudi initiative providing regulatory guidance and support',
            'Bilateral agreements with GCC nations facilitate regional expansion'
        ],
        'economic' => [
            'Saudi consumer spending growing at 5.2% annually',
            'Average Saudi household spends SAR 2,400/year on children\'s education',
            'Venture capital investment in Saudi fintech reached $1.1B in 2024'
        ],
        'social' => [
            '67% of Saudi parents express interest in children\'s financial education tools',
            'Rising influence of social media on youth financial awareness',
            'Cultural shift toward digital payments accelerated post-COVID'
        ],
        'technological' => [
            'Saudi Arabia ranks #1 in GCC for fintech infrastructure readiness',
            'Cloud computing regulations allow local data processing',
            'Biometric authentication widely adopted for mobile banking'
        ],
        'environmental' => [
            'Paperless banking aligns with Saudi Green Initiative',
            'Digital-first reduces carbon footprint vs traditional banking',
            'Sustainable finance education can be integrated into curriculum'
        ],
        'legal' => [
            'SAMA Payment Service Provider license required for operations',
            'Children under 15 require parental consent for account creation',
            'Transaction monitoring and reporting obligations under AML regulations'
        ]
    ],

    'sf_porters' => [
        'items' => [
            ['key' => 'Competitive Rivalry', 'value' => 'Low — No direct children\'s fintech competitor exists in Saudi Arabia. Indirect competition from basic children\'s bank accounts offered by traditional banks lacks the educational and engagement features.'],
            ['key' => 'Threat of New Entrants', 'value' => 'Medium-High — Low technical barriers but high regulatory barriers. Banking partnerships and SAMA licensing create a 12-18 month entry delay for new competitors.'],
            ['key' => 'Supplier Power', 'value' => 'High — Card network providers (Visa/Mastercard) and banking partners have significant pricing power. Multi-partner strategy recommended to mitigate.'],
            ['key' => 'Buyer Power', 'value' => 'Medium — Parents are price-sensitive but willing to pay for educational value. Children\'s habit formation creates natural retention.'],
            ['key' => 'Threat of Substitutes', 'value' => 'Medium — Cash allowances, basic savings accounts, and financial literacy books are substitutes, but none offer the integrated digital experience Kanz provides.']
        ]
    ],

    'sf_cage' => [
        'items' => [
            ['key' => 'Cultural Distance', 'value' => 'Minimal within GCC — shared Arabic language, Islamic values, and similar family structures. Kanz\'s Arabic-first, Sharia-aware design is a significant advantage. Expansion beyond GCC would require substantial cultural adaptation.'],
            ['key' => 'Administrative Distance', 'value' => 'Low within GCC — harmonized financial regulations through GCC common market framework. SAMA license may be portable through mutual recognition. Each country requires separate partnerships with local banks.'],
            ['key' => 'Geographic Distance', 'value' => 'Negligible within GCC — compact region with excellent digital infrastructure. Remote operations feasible for initial market testing before establishing local offices.'],
            ['key' => 'Economic Distance', 'value' => 'Low within GCC — similar GDP per capita and consumer spending patterns. Pricing model portable across GCC with minor adjustments. Significant distance to North Africa markets.']
        ]
    ],

    // === PATH TO MVP ===
    'mvp_definition' => [
        'content' => "Kanz's Minimum Viable Product focuses on delivering the core value proposition: enabling children to learn financial responsibility through a supervised digital wallet with parental controls.\n\nThe MVP includes three essential modules: (1) Child Wallet — a simplified digital wallet where children can receive allowances, view their balance, and track spending with colorful, age-appropriate visualizations. (2) Parent Dashboard — a control center where parents can send money, set spending limits, approve transactions above thresholds, and view their child's financial activity. (3) Savings Goals — a gamified feature allowing children to set savings targets (e.g., 'New bicycle - SAR 500') with visual progress tracking and celebratory animations upon goal completion.\n\nDeliberately excluded from MVP: physical debit cards (require banking partnership finalization), educational content modules (require curriculum development), multi-child management, and social/peer features. These will be introduced in subsequent releases based on user feedback.\n\nThe MVP targets a closed beta of 200 Saudi families recruited through school partnerships in Riyadh, with a 12-week testing period to validate core assumptions about engagement, retention, and willingness to pay."
    ],

    'mvp_technical_architecture' => [
        'items' => [
            ['key' => 'Frontend', 'value' => 'React Native for cross-platform mobile app (iOS + Android) with Arabic RTL support and child-friendly UI components'],
            ['key' => 'Backend', 'value' => 'Node.js with Express on AWS, using TypeScript for type safety and serverless Lambda functions for scalable processing'],
            ['key' => 'Database', 'value' => 'PostgreSQL for transactional data, Redis for caching and real-time features, S3 for media assets'],
            ['key' => 'Authentication', 'value' => 'AWS Cognito with biometric (Face ID / fingerprint) for parents, simplified PIN for children with parental session management'],
            ['key' => 'Payment Processing', 'value' => 'Integration with HyperPay (Saudi payment gateway) for wallet top-ups and future card issuance APIs'],
            ['key' => 'Infrastructure', 'value' => 'AWS Middle East (Bahrain) region for GCC data residency compliance, with CloudFront CDN and WAF for security'],
            ['key' => 'Monitoring', 'value' => 'Datadog for application performance, Sentry for error tracking, custom analytics dashboard for business metrics']
        ]
    ],

    'mvp_development_roadmap' => [
        'phases' => [
            [
                'name' => 'Foundation (Weeks 1-4)',
                'description' => 'Core infrastructure setup, authentication system, and basic wallet functionality',
                'actions' => ['AWS infrastructure provisioning', 'User authentication and onboarding flow', 'Basic wallet with balance display', 'Parent-child account linking'],
                'touchpoints' => ['Development environment', 'CI/CD pipeline', 'Internal testing']
            ],
            [
                'name' => 'Core Features (Weeks 5-8)',
                'description' => 'Money transfer, spending controls, and savings goals implementation',
                'actions' => ['Parent-to-child money transfer', 'Spending limit controls', 'Savings goal creation and tracking', 'Transaction history with categorization'],
                'touchpoints' => ['Internal alpha testing', 'UX review sessions', 'Security audit']
            ],
            [
                'name' => 'Polish & Beta (Weeks 9-12)',
                'description' => 'UI refinement, Arabic localization, gamification elements, and beta launch',
                'actions' => ['Complete Arabic RTL localization', 'Gamification rewards and animations', 'Push notifications system', 'Beta launch with 200 families'],
                'touchpoints' => ['Beta user onboarding', 'App Store submission', 'Customer support setup']
            ],
            [
                'name' => 'Iterate & Scale (Weeks 13-16)',
                'description' => 'Feedback incorporation, performance optimization, and preparation for public launch',
                'actions' => ['User feedback analysis and prioritization', 'Performance optimization', 'Banking partnership integration for debit cards', 'Public launch preparation'],
                'touchpoints' => ['User interviews', 'Metrics dashboard', 'Marketing campaign setup']
            ]
        ]
    ],

    'mvp_risks_mitigations' => [
        'headers' => ['Risk', 'Impact', 'Probability', 'Mitigation Strategy'],
        'rows' => [
            ['Banking partnership delays', 'High', 'Medium', 'Launch MVP as digital wallet without physical card; pursue multiple bank partners simultaneously'],
            ['SAMA licensing timeline', 'High', 'Medium', 'Begin application early; operate under sandbox initially; engage regulatory consultant'],
            ['Low user adoption in beta', 'Medium', 'Medium', 'Partner with schools for distribution; offer incentives; strong referral program'],
            ['Data breach or security incident', 'Critical', 'Low', 'SOC 2 compliance; penetration testing; bug bounty program; encryption at rest and in transit'],
            ['Gemini API rate limits for content', 'Low', 'High', 'Pre-generate educational content; implement caching; use multiple AI providers as fallback']
        ]
    ],

    // === UNIQUE SELLING POINTS ===
    'usp_overview' => [
        'content' => "Kanz differentiates itself through a unique combination of cultural relevance, educational depth, and technological innovation that no existing product in the MENA region can match.\n\nThe primary USP is Kanz's Arabic-first, Sharia-compliant design. Unlike Western children's fintech products that would require extensive localization, Kanz is built from the ground up for Arab families, with right-to-left interfaces, Arabic financial terminology, Islamic finance principles (no interest-based features), and culturally relevant scenarios in educational content.\n\nThe second major differentiator is the adaptive learning engine that personalizes financial education based on each child's age, behavior, and progress. A 7-year-old learning about saving receives fundamentally different content and challenges than a 16-year-old learning about budgeting and investing concepts.\n\nThird, Kanz's family-centric approach goes beyond parent-child dynamics to include extended family participation. Grandparents can send Eid money digitally, aunts and uncles can contribute to savings goals, creating a financial ecosystem that mirrors the strong extended family bonds central to Saudi culture."
    ],

    'usp_differentiators' => [
        ['label' => 'Arabic-First Design', 'value' => 'Only children\'s fintech built natively in Arabic with Islamic finance principles', 'description' => 'Full RTL support, Arabic financial terminology, Sharia-compliant features — not a translation of a Western product'],
        ['label' => 'Adaptive Learning Engine', 'value' => 'AI-powered content that evolves with the child\'s age and financial maturity', 'description' => 'Personalized lessons, challenges, and goals that adapt from ages 6-18 across 4 developmental stages'],
        ['label' => 'Family Financial Ecosystem', 'value' => 'Extended family participation beyond just parent-child', 'description' => 'Grandparents, relatives can send gifts digitally; family savings challenges; Eid and occasion integrations'],
        ['label' => 'Vision 2030 Alignment', 'value' => 'Directly supports Saudi Arabia\'s national development goals', 'description' => 'Financial inclusion, youth empowerment, digital transformation — positioning for government partnerships and grants']
    ],

    'usp_competitive_comparison' => [
        'headers' => ['Feature', 'Kanz', 'Greenlight (US)', 'GoHenry (UK)', 'Traditional Banks'],
        'rows' => [
            ['Arabic Language Support', 'Native', 'None', 'None', 'Basic'],
            ['Islamic Finance Compliance', 'Yes', 'No', 'No', 'Some banks'],
            ['Age-Adaptive Education', 'AI-Powered', 'Basic', 'Basic', 'None'],
            ['Extended Family Features', 'Yes', 'Limited', 'No', 'No'],
            ['Saudi Regulatory Compliance', 'Full', 'N/A', 'N/A', 'Full'],
            ['Gamification Depth', 'Advanced', 'Moderate', 'Moderate', 'None'],
            ['Parental Controls', 'Comprehensive', 'Good', 'Good', 'Basic'],
            ['Physical Debit Card', 'Planned', 'Yes', 'Yes', 'Yes'],
            ['Monthly Price (SAR)', '0-29', 'N/A', 'N/A', 'Free']
        ]
    ],

    // === CUSTOMER PERSONA ===
    'persona_primary' => [
        'name' => 'Ahmed Al-Rashid',
        'age' => 38,
        'role' => 'Senior Software Engineer at a Riyadh tech company',
        'goals' => [
            'Teach his 3 children (ages 8, 12, 15) about money management',
            'Reduce cash-based allowance system that\'s hard to track',
            'Prepare his eldest for financial independence',
            'Find an Arabic-language tool that aligns with family values'
        ],
        'pain_points' => [
            'Children don\'t understand the value of money with digital payments',
            'No Saudi-specific children\'s financial product exists',
            'Western apps don\'t support Arabic or Islamic finance principles',
            'Tracking cash allowances across 3 children is chaotic'
        ],
        'behaviors' => [
            'Tech-savvy early adopter who uses mobile banking daily',
            'Active on Saudi tech Twitter/X discussing new apps',
            'Willing to pay premium for educational tools for children',
            'Researches thoroughly before committing to family-use products'
        ],
        'demographics' => [
            'Income: SAR 35,000-45,000/month',
            'Location: North Riyadh',
            'Education: Computer Science degree from KFUPM',
            'Device: iPhone 15 Pro, children use iPads and Android phones'
        ]
    ],

    'persona_secondary' => [
        'name' => 'Noura Al-Zahrani',
        'age' => 42,
        'role' => 'School principal and mother of two',
        'goals' => [
            'Integrate financial literacy into her school\'s extracurricular program',
            'Find age-appropriate tools for teaching money concepts',
            'Empower her daughters (ages 10, 14) to be financially confident',
            'Connect financial education with real-world practice'
        ],
        'pain_points' => [
            'Limited Arabic financial education resources for schools',
            'Difficulty making abstract financial concepts tangible for students',
            'Parents request guidance on teaching children about money',
            'No standardized financial literacy curriculum in Saudi schools'
        ],
        'behaviors' => [
            'Active member of Saudi educators\' professional networks',
            'Regular attendee of EdTech conferences in Riyadh',
            'Influences purchasing decisions for 500+ families through school',
            'Values products that provide measurable educational outcomes'
        ],
        'demographics' => [
            'Income: SAR 25,000-30,000/month (household)',
            'Location: Jeddah',
            'Education: Master\'s in Education from KAU',
            'Device: Samsung Galaxy S24, uses school-provided iPad'
        ]
    ],

    'persona_buyer_journey' => [
        'phases' => [
            [
                'name' => 'Awareness',
                'description' => 'Parent realizes children need financial education beyond cash allowances',
                'actions' => ['Searching "تعليم الأطفال المالي" (children financial education) online', 'Asking other parents in WhatsApp groups', 'Reading parenting articles on Saudi platforms'],
                'touchpoints' => ['Social media ads targeting Saudi parents', 'School newsletter partnerships', 'Parenting influencer collaborations']
            ],
            [
                'name' => 'Consideration',
                'description' => 'Parent evaluates available options and compares features',
                'actions' => ['Downloading Kanz app and exploring free features', 'Reading reviews on Saudi app review sites', 'Discussing with spouse about family subscription'],
                'touchpoints' => ['App Store listing with Arabic screenshots', 'Free trial experience', 'In-app educational content preview']
            ],
            [
                'name' => 'Decision',
                'description' => 'Parent commits to Kanz and onboards family members',
                'actions' => ['Subscribing to premium family plan', 'Setting up children\'s accounts with spending limits', 'Transferring first digital allowance'],
                'touchpoints' => ['Seamless onboarding flow', 'Welcome tutorial for parent and child', 'First week engagement emails']
            ],
            [
                'name' => 'Retention & Advocacy',
                'description' => 'Family actively uses Kanz; parent recommends to others',
                'actions' => ['Regular allowance transfers and goal tracking', 'Sharing children\'s savings achievements on social media', 'Referring other families through referral program'],
                'touchpoints' => ['Weekly progress reports', 'Achievement notifications', 'Referral rewards', 'Seasonal campaigns (Eid, back-to-school)']
            ]
        ]
    ],

    // === FINANCES ===
    'fin_revenue_model' => [
        'content' => "Kanz employs a diversified revenue model combining recurring subscriptions with transaction-based income to ensure sustainable growth.\n\nThe primary revenue stream is a freemium subscription model with three tiers: Free (1 child, basic wallet and savings goals), Family (SAR 19/month — up to 4 children, advanced controls, educational content), and Premium (SAR 29/month — unlimited children, investment education, priority support, family analytics dashboard).\n\nThe secondary revenue stream comes from interchange fees on debit card transactions. Each time a child uses their Kanz prepaid card, Kanz earns approximately 1.2% of the transaction value from the merchant's payment processor. With an estimated average monthly card spend of SAR 300 per active child, this generates SAR 3.60/child/month.\n\nAdditional revenue opportunities include: B2B partnerships with schools offering financial literacy programs (SAR 50/student/year institutional license), white-label solutions for Saudi banks wanting to offer children's banking features, and branded financial literacy content sponsorships from FMCG companies targeting young consumers.\n\nThe blended target ARPU (Average Revenue Per User) is SAR 22/month by Year 2, with a path to SAR 35/month as card adoption and premium conversion increase."
    ],

    'fin_projections' => [
        'headers' => ['Metric', 'Q1', 'Q2', 'Q3', 'Q4', 'Year 1 Total'],
        'rows' => [
            ['Registered Families', '500', '2,000', '5,000', '10,000', '10,000'],
            ['Paid Subscribers', '100', '500', '1,500', '3,500', '3,500'],
            ['Monthly Subscription Revenue (SAR)', '1,900', '9,500', '28,500', '66,500', '318,600'],
            ['Card Transaction Revenue (SAR)', '0', '5,400', '21,600', '50,400', '77,400'],
            ['Total Revenue (SAR)', '1,900', '14,900', '50,100', '116,900', '396,000'],
            ['Operating Expenses (SAR)', '180,000', '220,000', '280,000', '350,000', '1,030,000'],
            ['Net Burn (SAR)', '-178,100', '-205,100', '-229,900', '-233,100', '-634,000'],
            ['Cash Remaining (SAR)', '2,821,900', '2,616,800', '2,386,900', '2,153,800', '2,153,800']
        ]
    ],

    'fin_cost_structure' => [
        'headers' => ['Cost Category', 'Monthly (SAR)', 'Annual (SAR)', '% of Total'],
        'rows' => [
            ['Engineering Team (6 FTE)', '120,000', '1,440,000', '42%'],
            ['Cloud Infrastructure (AWS)', '15,000', '180,000', '5%'],
            ['Marketing & User Acquisition', '40,000', '480,000', '14%'],
            ['Card Program & Banking Fees', '10,000', '120,000', '3%'],
            ['Customer Support (3 FTE)', '30,000', '360,000', '10%'],
            ['Content Creation & Education', '15,000', '180,000', '5%'],
            ['Office & Operations', '20,000', '240,000', '7%'],
            ['Regulatory & Compliance', '12,000', '144,000', '4%'],
            ['Management & Admin', '25,000', '300,000', '9%'],
            ['Total Monthly Burn', '287,000', '3,444,000', '100%']
        ]
    ],

    'fin_funding_strategy' => [
        'content' => "Kanz's funding strategy is structured in three phases aligned with product milestones and market validation.\n\nSeed Round (Current): Targeting SAR 3M (approximately \$800K) from Saudi angel investors and early-stage VCs including Saudi Venture Capital Company (SVC) and Wa'ed (Aramco's entrepreneurship arm). This round funds MVP development, SAMA sandbox entry, initial beta testing, and team expansion to 10 people. Expected timeline: 12 months runway.\n\nSeries A (Month 15-18): Targeting SAR 15-20M (\$4-5.3M) from regional VCs such as STV, Impact46, and BECO Capital. This round funds public launch marketing, banking partnership finalization for debit cards, expansion to 50,000 users, and team growth to 25 people. Triggers: 5,000+ active families, 35% paid conversion, and 60% month-3 retention.\n\nSeries B (Month 30-36): Targeting SAR 50-75M (\$13-20M) from international growth investors for GCC expansion (UAE, Bahrain, Kuwait), advanced product features (investment education, family financial planning), school partnership program scaling, and team growth to 60 people. Triggers: 100,000+ active families across Saudi Arabia, positive unit economics, and clear path to profitability."
    ],

    'fin_key_metrics' => [
        ['label' => 'CAC', 'value' => 'SAR 45', 'description' => 'Customer acquisition cost per family, targeting SAR 30 by Year 2 through organic and referral channels'],
        ['label' => 'LTV', 'value' => 'SAR 792', 'description' => 'Lifetime value per family assuming 36-month average retention and SAR 22/month ARPU'],
        ['label' => 'LTV:CAC Ratio', 'value' => '17.6x', 'description' => 'Strong unit economics indicating efficient customer acquisition relative to lifetime value'],
        ['label' => 'Monthly Burn Rate', 'value' => 'SAR 287K', 'description' => 'Total monthly operating expenses including team, infrastructure, and marketing'],
        ['label' => 'Runway', 'value' => '10.5 months', 'description' => 'Months of operations funded by seed round before requiring Series A']
    ],

    // === GO-TO-MARKET ===
    'gtm_strategy' => [
        'content' => "Kanz's go-to-market strategy leverages Saudi Arabia's tightly-knit family and community networks to drive organic adoption while establishing credibility through strategic institutional partnerships.\n\nThe primary distribution channel is a school partnership program targeting private international schools in Riyadh, Jeddah, and Dammam. By positioning Kanz as a financial literacy tool aligned with educational objectives, each school partnership provides access to 300-1,000 families with high digital literacy and willingness to pay for educational tools. The initial target is 20 school partnerships within 6 months of public launch.\n\nThe secondary channel is influencer-driven social media marketing, focusing on Saudi parenting influencers on Instagram, TikTok, and Snapchat (which has exceptionally high penetration in Saudi Arabia). A tiered influencer program combines paid partnerships with authentic testimonials from beta families.\n\nThe third channel is community-driven growth through WhatsApp-based referral programs. Given WhatsApp's dominance in Saudi family communication, a 'invite 3 families, get 1 month free' referral program is designed to leverage existing trust networks.\n\nPricing strategy: Launch with a generous free tier to maximize adoption, then convert to paid through exclusive features that parents discover they need (advanced controls, multi-child management, educational content)."
    ],

    'gtm_launch_plan' => [
        'phases' => [
            [
                'name' => 'Pre-Launch (Months 1-2)',
                'description' => 'Build anticipation and secure early adopters through waitlist and partnerships',
                'actions' => ['Landing page with waitlist targeting 5,000 signups', 'Secure 5 school partnerships for beta distribution', 'Parenting influencer seeding campaign', 'PR outreach to Saudi tech and parenting media'],
                'touchpoints' => ['Waitlist landing page', 'School presentations', 'Social media teasers', 'Tech blog features']
            ],
            [
                'name' => 'Closed Beta (Months 3-4)',
                'description' => 'Launch with 500 families, gather feedback, iterate on product',
                'actions' => ['Onboard 500 beta families from school partnerships', 'Weekly feedback sessions and surveys', 'Rapid iteration on core features', 'Build case studies from successful families'],
                'touchpoints' => ['Beta onboarding emails', 'In-app feedback tools', 'WhatsApp support group', 'Video testimonials']
            ],
            [
                'name' => 'Public Launch (Month 5)',
                'description' => 'Full launch on App Store and Google Play with marketing push',
                'actions' => ['App Store optimization in Arabic and English', 'Influencer campaign activation (20 creators)', 'Press releases and media coverage', 'Launch event at Riyadh tech hub'],
                'touchpoints' => ['App stores', 'Social media campaigns', 'Saudi tech media', 'School newsletters']
            ],
            [
                'name' => 'Growth Phase (Months 6-12)',
                'description' => 'Scale to 10,000 families through partnerships and paid acquisition',
                'actions' => ['Expand to 20 school partnerships', 'Launch WhatsApp referral program', 'Introduce debit card program', 'Seasonal campaigns for Eid al-Fitr and Eid al-Adha'],
                'touchpoints' => ['Referral program', 'Card unboxing experience', 'Eid gifting features', 'School financial literacy events']
            ]
        ]
    ],

    'gtm_partnerships' => [
        ['label' => 'Banking Partners', 'value' => 'Al Rajhi Bank & Riyad Bank', 'description' => 'Card issuance, payment processing, and regulatory sponsorship for SAMA sandbox participation'],
        ['label' => 'Education Partners', 'value' => '20+ Private Schools', 'description' => 'Distribution channel reaching 10,000+ families; financial literacy program integration with school curriculum'],
        ['label' => 'Technology Partners', 'value' => 'HyperPay & Lean Technologies', 'description' => 'Payment gateway integration and open banking API access for seamless financial data connectivity']
    ],

    // === COMPETITIVE ANALYSIS (VRIO) ===
    'vrio_analysis' => [
        'headers' => ['Resource/Capability', 'Valuable', 'Rare', 'Inimitable', 'Organized', 'Competitive Implication'],
        'rows' => [
            ['Arabic-first fintech for children', 'Yes', 'Yes', 'Medium', 'Yes', 'Temporary Competitive Advantage'],
            ['Proprietary gamification engine', 'Yes', 'Yes', 'Yes', 'Yes', 'Sustained Competitive Advantage'],
            ['School partnership network', 'Yes', 'Yes', 'Medium', 'Yes', 'Temporary Competitive Advantage'],
            ['Islamic finance compliance', 'Yes', 'No', 'No', 'Yes', 'Competitive Parity'],
            ['AI-adaptive learning curriculum', 'Yes', 'Yes', 'Yes', 'Developing', 'Potential Sustained Advantage'],
            ['Saudi regulatory expertise', 'Yes', 'Medium', 'No', 'Yes', 'Temporary Competitive Advantage'],
            ['Family financial data insights', 'Yes', 'Yes', 'Yes', 'Developing', 'Potential Sustained Advantage'],
            ['Brand trust with Saudi families', 'Yes', 'Developing', 'Yes', 'Developing', 'Potential Sustained Advantage']
        ]
    ],

    'vrio_resources' => [
        ['label' => 'Technical Team', 'value' => '8/10 Capability Score', 'description' => 'Core team of 6 engineers with fintech and mobile development experience from Stc Pay, HyperPay, and Tamara'],
        ['label' => 'Regulatory Knowledge', 'value' => '7/10 Readiness', 'description' => 'Advisors include former SAMA fintech department staff; pre-application guidance received for sandbox entry'],
        ['label' => 'Educational Content', 'value' => '75+ Modules', 'description' => 'Arabic financial literacy curriculum developed with certified financial planners and child psychologists'],
        ['label' => 'Market Intelligence', 'value' => '200+ Family Interviews', 'description' => 'Deep understanding of Saudi family financial dynamics from extensive primary research across 5 Saudi cities']
    ],

    'vrio_advantages' => [
        'content' => "Kanz's competitive advantages stem from three interconnected moats that strengthen over time.\n\nThe first and most defensible advantage is the proprietary gamification and adaptive learning engine. This system, built on behavioral economics principles adapted for children's cognitive development stages, creates personalized financial education journeys. As more children use the platform, the AI model improves its ability to predict effective learning interventions, creating a data-driven moat that competitors cannot replicate without equivalent scale.\n\nThe second advantage is the network effect created by family and social features. As more families join Kanz, features like peer savings challenges, family group goals, and digital gifting become more valuable. A child whose friends are all on Kanz has a strong incentive to remain on the platform, creating organic retention that increases with market penetration.\n\nThe third advantage is the institutional trust built through school partnerships and regulatory compliance. Being the first children's fintech to graduate from SAMA's sandbox creates a credibility advantage that takes competitors 12-18 months to replicate, during which Kanz can establish market leadership.\n\nCombined, these advantages create a flywheel: more users generate better educational AI, which attracts more schools, which brings more families, which strengthens network effects — making Kanz progressively harder to displace as it scales."
    ],
];

$updated = 0;
$errors = [];

foreach ($contentMap as $slug => $content) {
    $section = VentureSection::where('venture_id', $ventureId)
        ->where('slug', $slug)
        ->first();

    if (!$section) {
        $errors[] = "Section not found: {$slug}";
        continue;
    }

    $section->update([
        'content' => $content,
        'status' => 'completed',
        'generation_attempts' => 1,
    ]);
    $updated++;
}

echo "Updated {$updated} sections\n";
if ($errors) {
    echo "Errors:\n" . implode("\n", $errors) . "\n";
}

// Update venture status
$venture = \App\Models\Venture::find($ventureId);
$venture->update([
    'status' => 'completed',
    'viability_score' => 78,
]);
echo "Venture status: {$venture->fresh()->status}\n";
echo "Viability score: {$venture->fresh()->viability_score}\n";

// Summary
$sections = VentureSection::where('venture_id', $ventureId)->get();
$statuses = $sections->groupBy('status')->map(fn($g) => $g->count());
echo "\nFinal status breakdown: " . json_encode($statuses) . "\n";
