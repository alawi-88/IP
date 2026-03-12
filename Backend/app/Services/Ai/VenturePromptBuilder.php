<?php

namespace App\Services\Ai;

use App\Models\Venture;
use App\Models\VentureSectionConfig;
use App\Models\VentureKnowledgeSource;

class VenturePromptBuilder
{
    /**
     * Build a prompt for a specific venture section.
     *
     * Priority order:
     * 1. VentureSectionConfig.prompt_template (DB - defined in Section Builder admin)
     * 2. Hardcoded defaults (legacy fallback for sections not yet migrated)
     */
    public function buildPrompt(Venture $venture, string $sectionSlug): array
    {
        // Priority 1: Section config prompt_template (from Tab Builder → Section)
        $sectionConfig = VentureSectionConfig::where('section_slug', $sectionSlug)->first();

        if ($sectionConfig && $sectionConfig->hasPromptTemplate()) {
            $userPrompt = $this->replaceVariables($sectionConfig->prompt_template, $venture);
            $userPrompt = $this->appendKnowledgeInjection($userPrompt, $sectionSlug);

            $systemPrompt = !empty($sectionConfig->system_prompt)
                ? $this->replaceVariables($sectionConfig->system_prompt, $venture)
                : 'You are an expert startup advisor and business analyst. Respond with valid JSON only. No markdown, no explanation, no code fences.';

            return [
                'system_prompt' => $systemPrompt,
                'user_prompt' => $userPrompt,
                'max_tokens' => $sectionConfig->max_tokens ?? 4096,
                'temperature' => (float) ($sectionConfig->temperature ?? 0.7),
                'json_schema' => null,
            ];
        }

        // Priority 2: Hardcoded default prompts (legacy fallback)
        $defaultPrompt = $this->getDefaultPrompt($sectionSlug);
        $prompt = $this->replaceVariables($defaultPrompt, $venture);
        $prompt = $this->appendKnowledgeInjection($prompt, $sectionSlug);

        return [
            'system_prompt' => 'You are an expert startup advisor and business analyst. Respond with valid JSON only. No markdown, no explanation, no code fences.',
            'user_prompt' => $prompt,
            'max_tokens' => 4096,
            'temperature' => 0.7,
            'json_schema' => null,
        ];
    }

    protected function replaceVariables(string $prompt, Venture $venture): string
    {
        $replacements = [
            '{venture_title}' => $venture->title ?? 'Unknown Venture',
            '{venture_idea}' => $venture->idea_prompt ?? '',
            '{venture_description}' => $venture->idea_prompt ?? '',
            '{industry}' => $venture->industry ?? '',
            '{target_market}' => $venture->target_market ?? '',
            '{business_model}' => $venture->business_model ?? '',
        ];

        return strtr($prompt, $replacements);
    }

    protected function appendKnowledgeInjection(string $prompt, string $sectionSlug): string
    {
        $activeSources = VentureKnowledgeSource::where('is_active', true)
            ->orderBy('priority', 'asc')
            ->get()
            ->filter(function ($source) use ($sectionSlug) {
                if (empty($source->applicable_sections)) {
                    return true;
                }
                return in_array($sectionSlug, $source->applicable_sections);
            });

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
     * Get default prompt for a specific section slug.
     * Legacy fallback — these prompts should be migrated to the DB via Section Builder.
     */
    public function getDefaultPrompt(string $sectionSlug): string
    {
        $ctx = "Venture: {venture_title}\nIdea: {venture_idea}\nIndustry: {industry}\nTarget Market: {target_market}\nBusiness Model: {business_model}";

        return match ($sectionSlug) {
            // DASHBOARD TAB
            'dashboard_about' => "Analyze this startup and provide an About section overview.\n\n{$ctx}\n\nReturn JSON with this exact structure (text_content renderer):\n{\"content\": \"A 3-4 paragraph comprehensive overview of the venture, its mission, what problem it solves, and its unique approach. Use clear, professional language.\"}",

            'dashboard_swot' => "Conduct a comprehensive SWOT analysis.\n\n{$ctx}\n\nReturn JSON with this exact structure (swot_grid renderer):\n{\"strengths\": [\"Point 1\", \"Point 2\", \"Point 3\", \"Point 4\"], \"weaknesses\": [\"Point 1\", \"Point 2\", \"Point 3\", \"Point 4\"], \"opportunities\": [\"Point 1\", \"Point 2\", \"Point 3\", \"Point 4\"], \"threats\": [\"Point 1\", \"Point 2\", \"Point 3\", \"Point 4\"]}\n\nProvide 4-5 items per category. Each item should be a concise but meaningful sentence.",

            'dashboard_pestel' => "Conduct a PESTEL analysis.\n\n{$ctx}\n\nReturn JSON with this exact structure (pestel/swot_grid renderer):\n{\"political\": [\"Factor 1\", \"Factor 2\", \"Factor 3\"], \"economic\": [\"Factor 1\", \"Factor 2\", \"Factor 3\"], \"social\": [\"Factor 1\", \"Factor 2\", \"Factor 3\"], \"technological\": [\"Factor 1\", \"Factor 2\", \"Factor 3\"], \"environmental\": [\"Factor 1\", \"Factor 2\", \"Factor 3\"], \"legal\": [\"Factor 1\", \"Factor 2\", \"Factor 3\"]}\n\nProvide 3-4 specific, actionable factors per category relevant to this venture's industry and market.",

            'dashboard_porters' => "Analyze using Porter's Five Forces.\n\n{$ctx}\n\nReturn JSON with this exact structure (key_value renderer):\n{\"items\": [{\"key\": \"Threat of New Entrants\", \"value\": \"Medium - Explanation\"}, {\"key\": \"Bargaining Power of Suppliers\", \"value\": \"Low - Explanation\"}, {\"key\": \"Bargaining Power of Buyers\", \"value\": \"High - Explanation\"}, {\"key\": \"Threat of Substitutes\", \"value\": \"Medium - Explanation\"}, {\"key\": \"Competitive Rivalry\", \"value\": \"High - Explanation\"}]}\n\nFor each force, rate intensity (Low/Medium/High) and provide a 1-2 sentence explanation specific to this venture.",

            'dashboard_cage' => "Analyze using the CAGE Distance Framework for international expansion.\n\n{$ctx}\n\nReturn JSON with this exact structure (key_value renderer):\n{\"items\": [{\"key\": \"Cultural Distance\", \"value\": \"Assessment\"}, {\"key\": \"Administrative Distance\", \"value\": \"Assessment\"}, {\"key\": \"Geographic Distance\", \"value\": \"Assessment\"}, {\"key\": \"Economic Distance\", \"value\": \"Assessment\"}]}\n\nProvide detailed, venture-specific analysis for each dimension.",

            'dashboard_viability' => "Evaluate the overall viability of this venture.\n\n{$ctx}\n\nReturn JSON with this exact structure (viability_score renderer):\n{\"score\": 75, \"rating\": \"Strong\", \"justification\": \"2-3 sentence explanation.\", \"breakdown\": [{\"label\": \"Market Opportunity\", \"score\": 80}, {\"label\": \"Competitive Advantage\", \"score\": 70}, {\"label\": \"Technical Feasibility\", \"score\": 75}, {\"label\": \"Financial Viability\", \"score\": 65}, {\"label\": \"Team & Execution\", \"score\": 72}, {\"label\": \"Market Timing\", \"score\": 78}]}\n\nScore 0-100. Rating: Weak (0-30), Moderate (31-50), Promising (51-70), Strong (71-85), Exceptional (86-100).",

            'dashboard_market_size' => "Estimate the market size using TAM/SAM/SOM framework.\n\n{$ctx}\n\nReturn JSON with this exact structure (stat_cards renderer):\n[{\"label\": \"TAM\", \"value\": \"\$X.XB\", \"description\": \"Total global market\"}, {\"label\": \"SAM\", \"value\": \"\$X.XB\", \"description\": \"Reachable market\"}, {\"label\": \"SOM\", \"value\": \"\$XXM\", \"description\": \"Achievable in 3-5 years\"}, {\"label\": \"CAGR\", \"value\": \"XX%\", \"description\": \"Market growth rate\"}]\n\nUse realistic market estimates.",

            'dashboard_industry_insight' => "Provide deep industry insights and trends.\n\n{$ctx}\n\nReturn JSON with this exact structure (text_content renderer):\n{\"content\": \"3-4 paragraphs covering: current industry landscape, emerging trends, regulatory environment, and future outlook.\"}\n\nFocus on actionable insights relevant to this specific venture.",

            // STRATEGIC FRAMEWORKS TAB
            'sf_ip_strategy' => "Develop an intellectual property strategy.\n\n{$ctx}\n\nReturn JSON with this exact structure (text_content renderer):\n{\"content\": \"3-4 paragraphs covering: patentable innovations, trademark strategy, IP roadmap, defensive IP, licensing opportunities.\"}",
            'sf_swot' => "Conduct a detailed strategic SWOT analysis.\n\n{$ctx}\n\nReturn JSON with this exact structure (swot_grid renderer):\n{\"strengths\": [\"Point 1\", \"Point 2\", \"Point 3\", \"Point 4\", \"Point 5\"], \"weaknesses\": [\"Point 1\", \"Point 2\", \"Point 3\", \"Point 4\"], \"opportunities\": [\"Point 1\", \"Point 2\", \"Point 3\", \"Point 4\", \"Point 5\"], \"threats\": [\"Point 1\", \"Point 2\", \"Point 3\", \"Point 4\"]}\n\nInclude strategic implications for each point.",
            'sf_pestel' => "Conduct a detailed strategic PESTEL analysis.\n\n{$ctx}\n\nReturn JSON (swot_grid renderer):\n{\"political\": [...], \"economic\": [...], \"social\": [...], \"technological\": [...], \"environmental\": [...], \"legal\": [...]}\n\n4 items per category with strategic implications.",
            'sf_porters' => "Conduct a detailed Porter's Five Forces analysis.\n\n{$ctx}\n\nReturn JSON (key_value renderer):\n{\"items\": [{\"key\": \"Force Name\", \"value\": \"[Rating] - Detailed analysis\"}]}\n\n3-4 sentence analysis per force.",
            'sf_cage' => "Conduct a detailed CAGE Distance Framework analysis.\n\n{$ctx}\n\nReturn JSON (key_value renderer):\n{\"items\": [{\"key\": \"Distance Type\", \"value\": \"Analysis\"}]}\n\n3-4 sentences per dimension.",

            // PATH TO MVP TAB
            'mvp_definition' => "Define the Minimum Viable Product.\n\n{$ctx}\n\nReturn JSON (text_content renderer):\n{\"content\": \"4-5 paragraphs: core value proposition, must-have features, UX goals, success criteria, out-of-scope items.\"}",
            'mvp_technical_architecture' => "Design the technical architecture for the MVP.\n\n{$ctx}\n\nReturn JSON (key_value renderer):\n{\"items\": [{\"key\": \"Frontend\", \"value\": \"Stack\"}, {\"key\": \"Backend\", \"value\": \"Stack\"}, {\"key\": \"Database\", \"value\": \"Tech\"}, {\"key\": \"Infrastructure\", \"value\": \"Cloud\"}, {\"key\": \"Third-Party Services\", \"value\": \"APIs\"}, {\"key\": \"Security\", \"value\": \"Auth\"}, {\"key\": \"Scalability Plan\", \"value\": \"Strategy\"}]}",
            'mvp_development_roadmap' => "Create a phased development roadmap.\n\n{$ctx}\n\nReturn JSON (journey_timeline renderer):\n{\"phases\": [{\"name\": \"Phase 1: Foundation (Weeks 1-4)\", \"description\": \"Setup\", \"actions\": [...]}, {\"name\": \"Phase 2: Core (Weeks 5-8)\", \"description\": \"Build\", \"actions\": [...]}, {\"name\": \"Phase 3: Launch (Weeks 9-12)\", \"description\": \"Ship\", \"actions\": [...]}]}",
            'mvp_risks_mitigations' => "Identify key risks and mitigation strategies.\n\n{$ctx}\n\nReturn JSON (comparison_table renderer):\n{\"headers\": [\"Risk\", \"Category\", \"Severity\", \"Likelihood\", \"Mitigation\"], \"rows\": [...]}\n\n5-7 specific risks.",

            // UNIQUE SELLING POINTS TAB
            'usp_overview' => "Define the Unique Selling Proposition.\n\n{$ctx}\n\nReturn JSON (text_content renderer):\n{\"content\": \"3-4 paragraphs: core USP, problem-solution fit, competitive advantage, customer benefits.\"}",
            'usp_differentiators' => "Identify key differentiators.\n\n{$ctx}\n\nReturn JSON (stat_cards renderer):\n[{\"label\": \"Name\", \"value\": \"Impact\", \"description\": \"Why unique\"}]\n\n4-6 differentiators.",
            'usp_competitive_comparison' => "Create a competitive comparison matrix.\n\n{$ctx}\n\nReturn JSON (comparison_table renderer):\n{\"headers\": [\"Feature\", \"Our Venture\", \"Competitor 1\", \"Competitor 2\", \"Competitor 3\"], \"rows\": [...]}\n\n6-8 features, real competitor names.",

            // CUSTOMER PERSONA TAB
            'persona_primary' => "Create the primary customer persona.\n\n{$ctx}\n\nReturn JSON (persona_card renderer):\n{\"name\": \"Name\", \"age\": 32, \"role\": \"Title\", \"location\": \"City\", \"bio\": \"Background\", \"goals\": [...], \"pain_points\": [...], \"motivations\": [...], \"preferred_channels\": [...], \"tech_savviness\": \"Level\", \"spending_power\": \"Level\"}",
            'persona_secondary' => "Create the secondary customer persona.\n\n{$ctx}\n\nReturn JSON (persona_card renderer):\n{\"name\": \"Name\", \"age\": 45, \"role\": \"Title\", \"location\": \"City\", \"bio\": \"Background\", \"goals\": [...], \"pain_points\": [...], \"motivations\": [...], \"preferred_channels\": [...], \"tech_savviness\": \"Level\", \"spending_power\": \"Level\"}\n\nMake distinctly different from primary.",
            'persona_buyer_journey' => "Map the buyer journey.\n\n{$ctx}\n\nReturn JSON (journey_timeline renderer):\n{\"phases\": [{\"name\": \"Awareness\", \"description\": \"Discovery\", \"touchpoints\": [...], \"actions\": [...]}, {\"name\": \"Consideration\", \"description\": \"Evaluation\", \"touchpoints\": [...], \"actions\": [...]}, {\"name\": \"Decision\", \"description\": \"Purchase\", \"touchpoints\": [...], \"actions\": [...]}, {\"name\": \"Retention\", \"description\": \"Loyalty\", \"touchpoints\": [...], \"actions\": [...]}]}",

            // FINANCES TAB
            'fin_revenue_model' => "Define the revenue model.\n\n{$ctx}\n\nReturn JSON (text_content renderer):\n{\"content\": \"4-5 paragraphs: model type, revenue streams with pricing, CAC/LTV, scaling mechanics, timeline.\"}",
            'fin_projections' => "Create Year 1 financial projections.\n\n{$ctx}\n\nReturn JSON (comparison_table renderer):\n{\"headers\": [\"Metric\", \"Q1\", \"Q2\", \"Q3\", \"Q4\", \"Year 1\"], \"rows\": [[\"Revenue\",...], [\"COGS\",...], [\"Gross Profit\",...], [\"OpEx\",...], [\"Net Income\",...]]}",
            'fin_cost_structure' => "Break down the cost structure.\n\n{$ctx}\n\nReturn JSON (comparison_table renderer):\n{\"headers\": [\"Category\", \"Monthly\", \"Annual\", \"Type\", \"Notes\"], \"rows\": [...]}",
            'fin_funding_strategy' => "Develop a funding strategy.\n\n{$ctx}\n\nReturn JSON (text_content renderer):\n{\"content\": \"4-5 paragraphs: funding needs, stages, target investors, milestones, use of funds.\"}",
            'fin_key_metrics' => "Define key financial metrics.\n\n{$ctx}\n\nReturn JSON (stat_cards renderer):\n[{\"label\": \"CAC\", \"value\": \"\$XX\", \"description\": \"...\"}, {\"label\": \"LTV\", \"value\": \"\$XXX\", \"description\": \"...\"}, {\"label\": \"LTV:CAC\", \"value\": \"X:1\", \"description\": \"...\"}, {\"label\": \"Burn Rate\", \"value\": \"\$XXK\", \"description\": \"...\"}, {\"label\": \"Runway\", \"value\": \"XX mo\", \"description\": \"...\"}, {\"label\": \"Break-Even\", \"value\": \"Month XX\", \"description\": \"...\"}]",

            // GO-TO-MARKET STRATEGY TAB
            'gtm_strategy' => "Develop the go-to-market strategy.\n\n{$ctx}\n\nReturn JSON (text_content renderer):\n{\"content\": \"4-5 paragraphs: positioning, segments, channels, pricing, 6-month targets.\"}",
            'gtm_launch_plan' => "Create a 90-day launch plan.\n\n{$ctx}\n\nReturn JSON (journey_timeline renderer):\n{\"phases\": [{\"name\": \"Pre-Launch (Days 1-30)\", \"description\": \"Build anticipation\", \"actions\": [...]}, {\"name\": \"Soft Launch (Days 31-60)\", \"description\": \"Validate PMF\", \"actions\": [...]}, {\"name\": \"Full Launch (Days 61-90)\", \"description\": \"Scale\", \"actions\": [...]}]}",
            'gtm_partnerships' => "Identify key strategic partnerships.\n\n{$ctx}\n\nReturn JSON (stat_cards renderer):\n[{\"label\": \"Type\", \"value\": \"Partner\", \"description\": \"Value & approach\"}]\n\n4-6 partnerships.",

            // COMPETITIVE ANALYSIS (VRIO) TAB
            'vrio_analysis' => "Conduct a VRIO analysis.\n\n{$ctx}\n\nReturn JSON (comparison_table renderer):\n{\"headers\": [\"Resource\", \"Valuable?\", \"Rare?\", \"Costly to Imitate?\", \"Organized?\", \"Implication\"], \"rows\": [...]}\n\n5-7 resources.",
            'vrio_resources' => "Assess key resources and capabilities.\n\n{$ctx}\n\nReturn JSON (stat_cards renderer):\n[{\"label\": \"Resource\", \"value\": \"Rating\", \"description\": \"Strategic value\"}]\n\n5-6 resources.",
            'vrio_advantages' => "Summarize competitive advantages.\n\n{$ctx}\n\nReturn JSON (text_content renderer):\n{\"content\": \"3-4 paragraphs: VRIO advantages, resources to develop, recommendations, investment priorities.\"}",

            // DEFAULT FALLBACK
            default => "Analyze and provide relevant insights for the '{$sectionSlug}' section.\n\n{$ctx}\n\nReturn JSON with this structure:\n{\"content\": \"Comprehensive analysis text in multiple paragraphs.\"}\n\nProvide detailed, actionable insights.",
        };
    }
}
