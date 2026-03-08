<?php

namespace App\Services;

use App\Models\VaPage;
use App\Models\AiGeneration;
use App\Models\Startup;

class AiGenerationService
{
    /**
     * Generate AI suggestion for a field
     * This is a mock service for v6 - can be enhanced with real AI integration
     */
    public function generateForField(VaPage $vaPage, string $fieldKey, string $userPrompt): AiGeneration
    {
        // Start timing
        $startTime = microtime(true);

        // Generate mock response based on field and page context
        $response = $this->generateMockResponse($vaPage, $fieldKey, $userPrompt);

        // Calculate timing
        $generationTime = (int)((microtime(true) - $startTime) * 1000);

        // Create AI generation record
        $generation = AiGeneration::create([
            'va_page_id' => $vaPage->id,
            'user_id' => auth()->id(),
            'field_key' => $fieldKey,
            'prompt' => $userPrompt,
            'response' => $response,
            'status' => 'completed',
            'model_used' => 'mock-model-v1',
            'tokens_used' => $this->estimateTokens($userPrompt . ' ' . $response),
            'generation_time_ms' => $generationTime,
        ]);

        return $generation;
    }

    /**
     * Generate AI content for ALL pages in a startup based on a user prompt.
     * Creates the startup, initializes VA sections, then bulk-fills all pages.
     */
    public function generateForStartup(Startup $startup, string $userPrompt): array
    {
        $results = [];
        $startup->load('vaSections.vaPages');

        foreach ($startup->vaSections as $section) {
            foreach ($section->vaPages as $page) {
                $pageKey = $page->page_key;
                $content = $this->generatePageContent($startup->name, $userPrompt, $pageKey, $page->title_en, $section->title_en);

                // Update the page content and set it to 30% (AI-generated, needs human review)
                $page->updateContent($content, count($content) > 0 ? 30.0 : 0);

                $results[] = [
                    'section' => $section->section_key,
                    'page' => $pageKey,
                    'fields_generated' => array_keys($content),
                ];
            }
        }

        // Recalculate startup completion
        $startup->calculateCompletion();

        return $results;
    }

    /**
     * Generate content for a specific page based on its key
     */
    private function generatePageContent(string $name, string $prompt, string $pageKey, string $pageTitle, string $sectionTitle): array
    {
        $templates = $this->getPageTemplates();

        if (isset($templates[$pageKey])) {
            $content = [];
            foreach ($templates[$pageKey] as $fieldKey => $template) {
                $content[$fieldKey] = $this->fillTemplate($template, $name, $prompt, $pageTitle, $sectionTitle);
            }
            return $content;
        }

        // Generic fallback for pages without specific templates
        return [
            'content' => $this->fillTemplate(
                "{page} for {name}:\n\nBased on the concept: \"{prompt}\"\n\nThis section covers the key aspects of {page} within {section}. The analysis considers market dynamics, competitive landscape, and strategic positioning.\n\nKey considerations:\n- Alignment with the overall business strategy\n- Market-specific factors and trends\n- Resource allocation and prioritization\n- Measurable objectives and key results\n\nReview and customize this AI-generated content based on your specific research and requirements.",
                $name, $prompt, $pageTitle, $sectionTitle
            ),
        ];
    }

    /**
     * Fill a template string with context variables
     */
    private function fillTemplate(string $template, string $name, string $prompt, string $pageTitle, string $sectionTitle): string
    {
        return str_replace(
            ['{name}', '{prompt}', '{page}', '{section}'],
            [$name, $prompt, $pageTitle, $sectionTitle],
            $template
        );
    }

    /**
     * Get page-specific content templates
     */
    private function getPageTemplates(): array
    {
        return [
            'overview' => [
                'businessDescription' => "{name} is built around the concept: \"{prompt}\". It addresses a significant market need by providing innovative solutions in its target sector. The business model is designed to create sustainable value for customers while generating scalable revenue streams through technology-driven differentiation.",
                'problemStatement' => "The core problem that {name} addresses is the inefficiency and gaps in the current market. Users face challenges with existing solutions that are either too expensive, too complex, or don't adequately meet their needs. Based on the concept — \"{prompt}\" — there is a clear opportunity to deliver a better, more accessible experience.",
                'solutionDescription' => "{name} provides a comprehensive solution by leveraging technology and innovation. The platform offers streamlined workflows, intuitive interfaces, and data-driven insights that empower users to achieve their goals more effectively than any existing alternative.",
                'targetAudience' => "The primary target audience for {name} includes early adopters in the technology sector, small-to-medium businesses seeking efficient solutions, and individual professionals who value productivity and innovation. Secondary audiences include enterprise customers and strategic partners.",
                'uniqueValueProposition' => "{name} stands out through its unique combination of ease-of-use, powerful features, and competitive pricing. Unlike competitors, it offers an integrated experience that reduces friction and delivers measurable results from day one.",
            ],
            'market_analysis' => [
                'content' => "Market Analysis for {name}:\n\nTotal Addressable Market (TAM): The global market for solutions related to \"{prompt}\" is estimated at several billion dollars, growing at 15-25% annually.\n\nServiceable Addressable Market (SAM): Focusing on initial target segments, the SAM represents approximately 20-30% of the TAM.\n\nServiceable Obtainable Market (SOM): With a focused go-to-market strategy, the goal is to capture 2-5% of the SAM within the first 3 years.\n\nKey Market Trends:\n- Increasing digital transformation across industries\n- Growing demand for automated and AI-powered solutions\n- Shift towards subscription-based business models\n- Rising importance of data privacy and security",
            ],
            'financial_model' => [
                'content' => "Financial Projections for {name}:\n\nRevenue Model: SaaS subscription with tiered pricing\n- Basic: \$29/month per user\n- Professional: \$79/month per user\n- Enterprise: Custom pricing\n\nYear 1: \$120K-\$250K ARR | Year 2: \$500K-\$1M ARR | Year 3: \$2M-\$5M ARR\n\nKey Assumptions:\n- 15% month-over-month growth in Year 1\n- 60% gross margins\n- Customer acquisition cost (CAC): \$150-\$300\n- Lifetime value (LTV): \$1,500-\$3,000\n- Break-even expected by Month 18-24",
            ],
            'swot' => [
                'content' => "SWOT Analysis for {name}:\n\nStrengths:\n- Innovative technology approach based on \"{prompt}\"\n- Strong founding team with domain expertise\n- First-mover advantage in the target niche\n- Scalable architecture\n\nWeaknesses:\n- Limited brand recognition as a new entrant\n- Resource constraints typical of early-stage startups\n- Dependency on key team members\n\nOpportunities:\n- Growing market demand for digital solutions\n- Potential for strategic partnerships\n- International expansion possibilities\n- Adjacent market opportunities\n\nThreats:\n- Competition from established players\n- Rapid technology changes\n- Regulatory uncertainties\n- Economic downturns affecting customer budgets",
            ],
            'bmc' => [
                'content' => "Business Model Canvas for {name}:\n\nKey Partners: Technology providers, distribution partners, industry associations\n\nKey Activities: Product development, customer acquisition, platform maintenance, data analysis\n\nKey Resources: Engineering team, proprietary technology, customer data, brand equity\n\nValue Propositions: Simplified workflows, cost reduction, improved efficiency, actionable insights\n\nCustomer Relationships: Self-service platform, dedicated support, community engagement\n\nChannels: Direct sales, content marketing, partnerships, app marketplaces\n\nCustomer Segments: SMBs, enterprise teams, individual professionals\n\nCost Structure: Engineering (40%), Marketing (25%), Operations (20%), G&A (15%)\n\nRevenue Streams: Subscriptions, premium features, API access, consulting services",
            ],
            'mvp_canvas' => [
                'content' => "MVP Canvas for {name}:\n\nHypothesis: Based on \"{prompt}\", users need a streamlined solution that reduces friction and delivers immediate value.\n\nCore Features (MVP):\n1. User authentication and onboarding\n2. Core value-delivery feature\n3. Basic analytics dashboard\n4. Integration with key platforms\n\nMetrics to Validate:\n- User activation rate (target: >40%)\n- Weekly active users retention\n- Net Promoter Score (target: >30)\n- Time to first value (<5 minutes)\n\nTimeline: 8-12 weeks for MVP launch\nBudget: \$30K-\$60K estimated development cost",
            ],
            'business_plan' => [
                'content' => "Business Plan Summary for {name}:\n\nVision: To become the leading platform for the concept described as \"{prompt}\", transforming how users interact with this space.\n\nMission: Deliver an intuitive, powerful, and affordable solution that empowers users to achieve their goals efficiently.\n\n3-Year Goals:\n- Year 1: Product-market fit, 500+ active users\n- Year 2: Scale to 5,000+ users, expand feature set\n- Year 3: International expansion, 25,000+ users\n\nFunding Strategy:\n- Pre-seed: \$150K (bootstrapping + angels)\n- Seed: \$500K-\$1M (after PMF validation)\n- Series A: \$3M-\$5M (for scaling)",
            ],
            'pestel' => [
                'content' => "PESTEL Analysis for {name}:\n\nPolitical: Government support for innovation and digital transformation. Regulatory frameworks evolving to accommodate new technology.\n\nEconomic: Growing investment in digital solutions. SMB market expanding. Subscription economy growing 15%+ YoY.\n\nSocial: Increasing digital literacy. Remote work driving demand for cloud tools. User expectations for seamless experiences rising.\n\nTechnological: AI/ML enabling smarter products. Cloud infrastructure costs decreasing. API-first architecture becoming standard.\n\nEnvironmental: Growing emphasis on sustainable business practices. Digital solutions reducing physical resource consumption.\n\nLegal: Data privacy regulations (GDPR, CCPA) shaping product design. IP protection important for competitive moat.",
            ],
            'feature_prioritization' => [
                'content' => "Feature Prioritization for {name}:\n\nMust Have (P0):\n- User registration and authentication\n- Core functionality aligned with \"{prompt}\"\n- Basic data management\n- Mobile-responsive design\n\nShould Have (P1):\n- Advanced analytics and reporting\n- Team collaboration features\n- Third-party integrations\n- Notification system\n\nCould Have (P2):\n- AI-powered recommendations\n- Custom branding options\n- Advanced workflow automation\n- API for developers\n\nWon't Have (for now):\n- Native mobile apps\n- Marketplace features\n- White-label options",
            ],
            'market_validation' => [
                'content' => "Market Validation Plan for {name}:\n\nPhase 1 - Problem Validation (Weeks 1-3):\n- Conduct 20+ user interviews\n- Survey 100+ potential customers\n- Analyze competitor offerings and reviews\n\nPhase 2 - Solution Validation (Weeks 4-6):\n- Build clickable prototype\n- Run usability tests with 10 users\n- A/B test value propositions via landing page\n\nPhase 3 - Market Validation (Weeks 7-10):\n- Launch beta with 50 early adopters\n- Measure activation and retention\n- Validate willingness to pay with pricing experiments\n\nSuccess Criteria:\n- 40%+ of interviewees confirm the problem\n- 25%+ conversion on landing page\n- 60%+ beta user retention at week 4",
            ],
            'executive_summary' => [
                'content' => "Executive Summary - {name} GTM Strategy:\n\nBased on the concept: \"{prompt}\"\n\n{name} will enter the market with a focused go-to-market strategy targeting early adopters and SMBs. Our approach combines product-led growth with targeted outreach to build initial traction and validate our value proposition.\n\nKey GTM Pillars:\n1. Product-led growth with freemium model\n2. Content marketing for organic acquisition\n3. Strategic partnerships for distribution\n4. Community building for retention\n\nLaunch Timeline: 90 days from MVP completion\nTarget: 500 users in first 6 months\nKey Metric: <\$200 CAC with >\$1,500 LTV",
            ],
            'value_proposition' => [
                'content' => "Value Proposition for {name}:\n\nFor [target customers] who [have this problem], {name} is a [product category] that [key benefit]. Unlike [competitors], we [key differentiator].\n\nCore Value Pillars:\n1. Simplicity - Intuitive design that requires zero training\n2. Speed - 10x faster than manual alternatives\n3. Intelligence - AI-powered insights and recommendations\n4. Value - Affordable pricing with clear ROI\n\nValue Proposition by Segment:\n- SMBs: Save 10+ hours/week on manual tasks\n- Enterprise: Reduce operational costs by 30%\n- Individuals: Achieve professional results without expertise",
            ],
            'competitor_profiles' => [
                'content' => "Competitor Analysis for {name}:\n\nDirect Competitors:\n1. Competitor A - Market leader with broad feature set but complex UI and premium pricing\n2. Competitor B - Newer entrant focused on simplicity, limited features\n3. Competitor C - Open-source alternative with strong community but no support\n\nIndirect Competitors:\n- Manual processes (spreadsheets, documents)\n- In-house custom solutions\n- Adjacent tools that partially solve the problem\n\nOur Differentiation:\n- Best balance of power and simplicity\n- AI-native features (not bolted on)\n- Transparent, affordable pricing\n- Superior onboarding experience",
            ],
            'competitive_matrix' => [
                'content' => "Competitive Matrix for {name}:\n\nFeature comparison across key dimensions:\n\nEase of Use: {name} ★★★★★ | Comp A ★★★ | Comp B ★★★★ | Comp C ★★\nFeature Depth: {name} ★★★★ | Comp A ★★★★★ | Comp B ★★★ | Comp C ★★★★\nPricing: {name} ★★★★★ | Comp A ★★ | Comp B ★★★★ | Comp C ★★★★★\nAI Capabilities: {name} ★★★★★ | Comp A ★★★ | Comp B ★★ | Comp C ★\nCustomer Support: {name} ★★★★ | Comp A ★★★★ | Comp B ★★★ | Comp C ★★\nIntegrations: {name} ★★★ | Comp A ★★★★★ | Comp B ★★★ | Comp C ★★★★\n\nOverall Position: {name} leads in ease-of-use and AI capabilities while maintaining competitive pricing.",
            ],
        ];
    }

    /**
     * Generate a mock response based on context
     */
    private function generateMockResponse(VaPage $vaPage, string $fieldKey, string $userPrompt): string
    {
        $section = $vaPage->vaSection;
        $startup = $section->startup;

        $contextStrings = [
            'startup_name' => $startup->name,
            'section' => $section->section_key,
            'page' => $vaPage->page_key,
            'field' => $fieldKey,
        ];

        // Basic mock response based on field and page
        $mockResponses = [
            'market_size' => "Based on the {$contextStrings['startup_name']} concept, the addressable market in your sector appears to be substantial. Consider researching TAM (Total Addressable Market), SAM (Serviceable Addressable Market), and SOM (Serviceable Obtainable Market) metrics.",
            'competitive_advantage' => "Your {$contextStrings['startup_name']} could differentiate by focusing on unique value propositions. Consider factors like technology, team expertise, customer relationships, and operational efficiency.",
            'revenue_model' => "Consider multiple revenue streams for {$contextStrings['startup_name']}: subscription, freemium, marketplace, licensing, or hybrid models. Evaluate what works best for your target market.",
            'target_customer' => "Define your ideal customer profile (ICP) for {$contextStrings['startup_name']}. Consider demographics, firmographics, pain points, and buying behavior.",
        ];

        // Return mock response or a generic one
        foreach ($mockResponses as $key => $value) {
            if (str_contains(strtolower($fieldKey), strtolower($key))) {
                return $value;
            }
        }

        return "This is a mock AI suggestion for the field '{$fieldKey}' in the {$contextStrings['startup_name']} {$contextStrings['page']} page. In production, this would be powered by a real AI model.";
    }

    /**
     * Estimate tokens (rough approximation: ~4 characters per token)
     */
    private function estimateTokens(string $text): int
    {
        return max(1, (int)ceil(strlen($text) / 4));
    }
}
