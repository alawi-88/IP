<?php

namespace App\Services\Ai;

use App\Models\Venture;
use App\Models\VenturePromptTemplate;
use App\Models\VentureKnowledgeSource;

class VenturePromptBuilder
{
    protected Venture $venture;

    public function __construct(Venture $venture)
    {
        $this->venture = $venture;
    }

    /**
     * Build a prompt for a specific venture section.
     *
     * @param string $sectionKey
     * @return string
     */
    public function buildPrompt(string $sectionKey): string
    {
        // Try to find an active custom template for this section
        $template = VenturePromptTemplate::where('venture_id', $this->venture->id)
            ->where('section_key', $sectionKey)
            ->where('is_active', true)
            ->first();

        if ($template) {
            $prompt = $template->prompt_text;
        } else {
            // Fall back to default prompt
            $prompt = $this->getDefaultPrompt($sectionKey);
        }

        // Replace venture variables
        $prompt = $this->replaceVariables($prompt);

        // Append knowledge injection from active sources
        $prompt = $this->appendKnowledgeInjection($prompt);

        return $prompt;
    }

    /**
     * Replace venture-specific variables in the prompt.
     *
     * @param string $prompt
     * @return string
     */
    protected function replaceVariables(string $prompt): string
    {
        $replacements = [
            '{venture_title}' => $this->venture->name ?? 'Unknown Venture',
            '{venture_idea}' => $this->venture->idea ?? '',
            '{industry}' => $this->venture->industry ?? '',
            '{target_market}' => $this->venture->target_market ?? '',
            '{business_model}' => $this->venture->business_model ?? '',
        ];

        return strtr($prompt, $replacements);
    }

    /**
     * Append knowledge injection from active VentureKnowledgeSources.
     *
     * @param string $prompt
     * @return string
     */
    protected function appendKnowledgeInjection(string $prompt): string
    {
        $activeSources = VentureKnowledgeSource::where('venture_id', $this->venture->id)
            ->where('is_active', true)
            ->orderBy('priority', 'asc')
            ->get();

        if ($activeSources->isEmpty()) {
            return $prompt;
        }

        $injection = "\n\n--- Additional Context ---\n";

        foreach ($activeSources as $source) {
            if (!empty($source->content)) {
                $injection .= "\n{$source->title}:\n{$source->content}\n";
            }
        }

        return $prompt . $injection;
    }

    /**
     * Get default prompt for a specific section key.
     *
     * @param string $sectionKey
     * @return string
     */
    public function getDefaultPrompt(string $sectionKey): string
    {
        $baseInstructions = "You are an expert startup advisor and business analyst. Analyze the provided venture and respond with valid JSON in the exact structure specified below. Respond ONLY with valid JSON. No markdown, no explanation.";

        return match ($sectionKey) {
            // Dashboard Section
            'dashboard_executive_summary' => $baseInstructions . "\n\nFor venture: {venture_title}\nIdea: {venture_idea}\nIndustry: {industry}\nTarget Market: {target_market}\nBusiness Model: {business_model}\n\nProvide a concise executive summary (2-3 paragraphs) highlighting the key value proposition and market opportunity.\n\nReturn JSON:\n{\"summary\": \"The executive summary text here\"}",

            'dashboard_viability_score' => $baseInstructions . "\n\nFor venture: {venture_title}\nIdea: {venture_idea}\nIndustry: {industry}\nTarget Market: {target_market}\nBusiness Model: {business_model}\n\nEvaluate the viability of this venture on a scale of 0-100 considering market size, competition, team requirements, and market timing.\n\nReturn JSON:\n{\"score\": 75, \"rating\": \"Strong\", \"justification\": \"Explanation of the score\"}",

            'dashboard_key_metrics' => $baseInstructions . "\n\nFor venture: {venture_title}\nIdea: {venture_idea}\nIndustry: {industry}\nTarget Market: {target_market}\nBusiness Model: {business_model}\n\nIdentify 5-7 critical KPIs and metrics this venture should track. For each metric, provide the name, description, target, and why it matters.\n\nReturn JSON:\n{\"metrics\": [{\"name\": \"Metric Name\", \"description\": \"What it measures\", \"target\": \"Target value\", \"importance\": \"Why it matters\"}, ...]}",

            // Strategic Analysis Section
            'strategic_swot_analysis' => $baseInstructions . "\n\nFor venture: {venture_title}\nIdea: {venture_idea}\nIndustry: {industry}\nTarget Market: {target_market}\nBusiness Model: {business_model}\n\nConduct a comprehensive SWOT analysis. For each category, provide 4-5 points.\n\nReturn JSON:\n{\"strengths\": [\"Strength 1\", \"Strength 2\", ...], \"weaknesses\": [\"Weakness 1\", ...], \"opportunities\": [\"Opportunity 1\", ...], \"threats\": [\"Threat 1\", ...]}",

            'strategic_pestel_analysis' => $baseInstructions . "\n\nFor venture: {venture_title}\nIdea: {venture_idea}\nIndustry: {industry}\nTarget Market: {target_market}\nBusiness Model: {business_model}\n\nConduct a PESTEL analysis examining Political, Economic, Social, Technological, Environmental, and Legal factors. For each factor, provide 3-4 relevant points.\n\nReturn JSON:\n{\"political\": [\"Point 1\", ...], \"economic\": [\"Point 1\", ...], \"social\": [\"Point 1\", ...], \"technological\": [\"Point 1\", ...], \"environmental\": [\"Point 1\", ...], \"legal\": [\"Point 1\", ...]}",

            'strategic_porters_five' => $baseInstructions . "\n\nFor venture: {venture_title}\nIdea: {venture_idea}\nIndustry: {industry}\nTarget Market: {target_market}\nBusiness Model: {business_model}\n\nAnalyze the competitive forces using Porter's Five Forces: threat of new entrants, bargaining power of suppliers, bargaining power of buyers, threat of substitutes, and competitive rivalry. Rate intensity from Low to High and explain.\n\nReturn JSON:\n{\"threat_of_new_entrants\": {\"intensity\": \"Medium\", \"explanation\": \"...\"}, \"bargaining_power_of_suppliers\": {\"intensity\": \"Medium\", \"explanation\": \"...\"}, \"bargaining_power_of_buyers\": {\"intensity\": \"High\", \"explanation\": \"...\"}, \"threat_of_substitutes\": {\"intensity\": \"Medium\", \"explanation\": \"...\"}, \"competitive_rivalry\": {\"intensity\": \"High\", \"explanation\": \"...\"}}",

            // MVP Section
            'mvp_feature_prioritization' => $baseInstructions . "\n\nFor venture: {venture_title}\nIdea: {venture_idea}\nIndustry: {industry}\nTarget Market: {target_market}\n\nIdentify the most critical features for an MVP. Prioritize them using MoSCoW method (Must have, Should have, Could have, Won't have). Provide 3-4 in each category.\n\nReturn JSON:\n{\"must_have\": [{\"feature\": \"Name\", \"description\": \"Why essential\", \"effort\": \"Low/Medium/High\"}, ...], \"should_have\": [...], \"could_have\": [...], \"wont_have\": [...]}",

            'mvp_development_roadmap' => $baseInstructions . "\n\nFor venture: {venture_title}\nIdea: {venture_idea}\n\nCreate a phased development roadmap for MVP launch. Break into phases (Phase 1: Foundation, Phase 2: Core Features, Phase 3: Polish/Launch). Estimate timeline and deliverables.\n\nReturn JSON:\n{\"phases\": [{\"phase_name\": \"Phase 1: Foundation\", \"duration_weeks\": 4, \"deliverables\": [\"Deliverable 1\", ...], \"key_milestones\": [\"Milestone 1\", ...]}, ...]}",

            'mvp_tech_stack' => $baseInstructions . "\n\nFor venture: {venture_title}\nIdea: {venture_idea}\nBusiness Model: {business_model}\n\nRecommend an appropriate technology stack for MVP development. Consider cost-effectiveness, time-to-market, and scalability. Include frontend, backend, database, and infrastructure components.\n\nReturn JSON:\n{\"frontend\": [\"Technology 1\", \"Technology 2\"], \"backend\": [\"Technology 1\", \"Technology 2\"], \"database\": [\"Technology 1\"], \"infrastructure\": [\"Technology 1\"], \"rationale\": \"Why this stack is suitable\"}",

            // USP Section
            'usp_value_proposition' => $baseInstructions . "\n\nFor venture: {venture_title}\nIdea: {venture_idea}\nTarget Market: {target_market}\n\nCraft a compelling value proposition. State the specific benefits and why it matters to the target customer.\n\nReturn JSON:\n{\"headline\": \"One-line value proposition\", \"description\": \"2-3 sentence detailed explanation\", \"key_benefits\": [\"Benefit 1\", \"Benefit 2\", \"Benefit 3\"]}",

            'usp_competitive_advantages' => $baseInstructions . "\n\nFor venture: {venture_title}\nIdea: {venture_idea}\nIndustry: {industry}\n\nIdentify 4-5 unique competitive advantages. For each, explain what it is, why it's hard to replicate, and how it creates value.\n\nReturn JSON:\n{\"advantages\": [{\"advantage\": \"Name\", \"description\": \"What it is\", \"durability\": \"Low/Medium/High (How long it lasts)\", \"value_creation\": \"How it benefits customers\"}, ...]}",

            'usp_feature_comparison' => $baseInstructions . "\n\nFor venture: {venture_title}\nIdea: {venture_idea}\nIndustry: {industry}\n\nCompare key features against main competitors. Identify features where you excel and areas of parity.\n\nReturn JSON:\n{\"comparison\": [{\"feature\": \"Feature Name\", \"your_solution\": \"Your approach\", \"competitor_1\": \"Competitor approach\", \"competitor_2\": \"Competitor approach\", \"advantage\": \"Who leads on this\"}, ...]}",

            // Persona Section
            'persona_primary_persona' => $baseInstructions . "\n\nFor venture: {venture_title}\nIdea: {venture_idea}\nTarget Market: {target_market}\n\nCreate a detailed primary customer persona. Include demographics, psychographics, pain points, goals, and buying behavior.\n\nReturn JSON:\n{\"name\": \"Persona Name\", \"age_range\": \"35-45\", \"occupation\": \"Job title\", \"income\": \"Salary range\", \"education\": \"Education level\", \"pain_points\": [\"Pain 1\", \"Pain 2\"], \"goals\": [\"Goal 1\", \"Goal 2\"], \"buying_triggers\": [\"Trigger 1\"], \"preferred_channels\": [\"Channel 1\"]}",

            'persona_secondary_persona' => $baseInstructions . "\n\nFor venture: {venture_title}\nIdea: {venture_idea}\nTarget Market: {target_market}\n\nCreate a secondary customer persona representing an important but non-primary customer segment.\n\nReturn JSON:\n{\"name\": \"Persona Name\", \"age_range\": \"25-35\", \"occupation\": \"Job title\", \"income\": \"Salary range\", \"education\": \"Education level\", \"pain_points\": [\"Pain 1\", \"Pain 2\"], \"goals\": [\"Goal 1\", \"Goal 2\"], \"buying_triggers\": [\"Trigger 1\"], \"preferred_channels\": [\"Channel 1\"]}",

            'persona_buyer_journey' => $baseInstructions . "\n\nFor venture: {venture_title}\nIdea: {venture_idea}\nTarget Market: {target_market}\n\nMap out the customer buyer's journey through awareness, consideration, and decision stages. Include key touchpoints and messaging.\n\nReturn JSON:\n{\"stages\": [{\"stage\": \"Awareness\", \"customer_needs\": \"What they need\", \"touchpoints\": [\"Touchpoint 1\"], \"messaging\": \"What to communicate\", \"channels\": [\"Channel 1\"]}, {\"stage\": \"Consideration\", ...}, {\"stage\": \"Decision\", ...}]}",

            // Financial Section
            'finances_revenue_model' => $baseInstructions . "\n\nFor venture: {venture_title}\nIdea: {venture_idea}\nBusiness Model: {business_model}\nTarget Market: {target_market}\n\nDefine the revenue model. Explain how the business makes money, pricing strategy, and revenue streams.\n\nReturn JSON:\n{\"model_type\": \"SaaS/Freemium/Subscription/etc\", \"revenue_streams\": [{\"stream_name\": \"Primary Subscription\", \"description\": \"Monthly SaaS fees\", \"average_price\": \"$99/month\", \"volume_assumption\": \"1000 customers Year 1\"}, ...], \"pricing_rationale\": \"Why this pricing strategy\"}",

            'finances_cost_structure' => $baseInstructions . "\n\nFor venture: {venture_title}\nIdea: {venture_idea}\nBusiness Model: {business_model}\n\nOutline the cost structure. Include fixed costs, variable costs, and cost drivers for Year 1.\n\nReturn JSON:\n{\"fixed_costs\": [{\"category\": \"Salaries\", \"monthly_amount\": 50000}, ...], \"variable_costs\": [{\"category\": \"Cloud Infrastructure\", \"per_unit_cost\": 2.5}, ...], \"total_fixed_annual\": 600000, \"total_variable_per_unit\": 10}",

            'finances_financial_projections' => $baseInstructions . "\n\nFor venture: {venture_title}\nIdea: {venture_idea}\nBusiness Model: {business_model}\n\nCreate 3-year financial projections. Include revenue, major expenses, and key financial metrics (CAC, LTV, burn rate, runway).\n\nReturn JSON:\n{\"projections\": [{\"year\": 1, \"revenue\": 500000, \"gross_profit\": 400000, \"operating_expenses\": 600000, \"net_income\": -200000}, {\"year\": 2, ...}, {\"year\": 3, ...}], \"key_metrics\": {\"cac\": 500, \"ltv\": 5000, \"payback_months\": 12}}",

            // Go-to-Market Section
            'gtm_marketing_strategy' => $baseInstructions . "\n\nFor venture: {venture_title}\nIdea: {venture_idea}\nIndustry: {industry}\nTarget Market: {target_market}\n\nDevelop a comprehensive marketing strategy. Cover positioning, key messages, target segments, and tactics.\n\nReturn JSON:\n{\"positioning\": \"Market positioning statement\", \"key_messages\": [\"Message 1\", \"Message 2\"], \"target_segments\": [\"Segment 1\"], \"tactics\": [{\"tactic\": \"Content Marketing\", \"description\": \"Blog posts and guides\", \"budget_allocation\": \"30%\", \"expected_roi\": \"4:1\"}, ...]}",

            'gtm_channel_strategy' => $baseInstructions . "\n\nFor venture: {venture_title}\nIdea: {venture_idea}\nTarget Market: {target_market}\n\nDefine distribution and go-to-market channels. Prioritize channels by potential reach and cost-effectiveness.\n\nReturn JSON:\n{\"channels\": [{\"channel\": \"Direct Sales\", \"description\": \"B2B direct outreach\", \"priority\": \"High\", \"implementation_timeline\": \"Month 1-3\", \"success_metrics\": \"10 enterprise customers\"}, ...]}",

            'gtm_launch_plan' => $baseInstructions . "\n\nFor venture: {venture_title}\nIdea: {venture_idea}\n\nCreate a 90-day launch plan. Break into pre-launch, launch day, and post-launch phases with specific activities and milestones.\n\nReturn JSON:\n{\"phases\": [{\"phase\": \"Pre-Launch (Month 1)\", \"activities\": [\"Build beta user list\", \"Create marketing materials\"], \"milestones\": [\"500 email signups\"]}, {\"phase\": \"Launch (Month 2)\", ...}, {\"phase\": \"Post-Launch (Month 3)\", ...}]}",

            // Competitive Section
            'competitive_market_overview' => $baseInstructions . "\n\nFor venture: {venture_title}\nIdea: {venture_idea}\nIndustry: {industry}\n\nProvide a comprehensive market overview. Include market size, growth rate, key trends, and dynamics.\n\nReturn JSON:\n{\"market_size_current\": \"$5B\", \"market_size_projection_5yr\": \"$15B\", \"cagr\": \"25%\", \"key_trends\": [\"Trend 1: Description\", \"Trend 2: Description\"], \"market_dynamics\": \"Description of competitive dynamics\"}",

            'competitive_competitor_profiles' => $baseInstructions . "\n\nFor venture: {venture_title}\nIdea: {venture_idea}\nIndustry: {industry}\n\nProfile the top 3-4 direct competitors. For each, analyze their strengths, weaknesses, market position, and strategy.\n\nReturn JSON:\n{\"competitors\": [{\"name\": \"Competitor Name\", \"founded\": 2020, \"funding\": \"$10M Series A\", \"market_share\": \"15%\", \"strengths\": [\"Strength 1\"], \"weaknesses\": [\"Weakness 1\"], \"strategy\": \"Their approach\"}, ...]}",

            'competitive_positioning_map' => $baseInstructions . "\n\nFor venture: {venture_title}\nIdea: {venture_idea}\nIndustry: {industry}\n\nCreate a competitive positioning map. Choose two key differentiators and position your venture against competitors.\n\nReturn JSON:\n{\"x_axis\": \"Price (Low to High)\", \"y_axis\": \"Features (Basic to Advanced)\", \"positioning\": [{\"name\": \"Your Venture\", \"x\": 0.5, \"y\": 0.7}, {\"name\": \"Competitor 1\", \"x\": 0.3, \"y\": 0.4}, ...], \"opportunity\": \"Positioning advantage\"}",

            // Default section
            default => $baseInstructions . "\n\nAnalyze the venture and provide relevant insights. Return valid JSON with appropriate structure for this analysis.",
        };
    }
}
