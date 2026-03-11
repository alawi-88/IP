<?php

namespace App\Services\Ai;

use App\Models\Venture;
use App\Models\VenturePromptTemplate;
use App\Models\VentureKnowledgeSource;

class VenturePromptBuilder
{
    /**
     * Build a prompt for a specific venture section.
     *
     * @param Venture $venture
     * @param string $sectionSlug
     * @return array{system_prompt: string, user_prompt: string, max_tokens: int, temperature: float, json_schema: ?array}
     */
    public function buildPrompt(Venture $venture, string $sectionSlug): array
    {
        // Try to find an active custom template for this section
        $template = VenturePromptTemplate::where('section_slug', $sectionSlug)
            ->where('is_active', true)
            ->first();

        if ($template) {
            $systemPrompt = $this->replaceVariables($template->system_prompt, $venture);
            $userPrompt = $this->replaceVariables($template->user_prompt, $venture);

            // Append knowledge injection
            $userPrompt = $this->appendKnowledgeInjection($userPrompt, $sectionSlug);

            return [
                'system_prompt' => $systemPrompt,
                'user_prompt' => $userPrompt,
                'max_tokens' => $template->max_tokens ?? 4096,
                'temperature' => $template->temperature ?? 0.7,
                'json_schema' => $template->json_schema,
            ];
        }

        // Fall back to default prompt
        $defaultPrompt = $this->getDefaultPrompt($sectionSlug);
        $prompt = $this->replaceVariables($defaultPrompt, $venture);
        $prompt = $this->appendKnowledgeInjection($prompt, $sectionSlug);

        return [
            'system_prompt' => 'You are an expert startup advisor and business analyst. Respond with valid JSON only.',
            'user_prompt' => $prompt,
            'max_tokens' => 4096,
            'temperature' => 0.7,
            'json_schema' => null,
        ];
    }

    /**
     * Replace venture-specific variables in the prompt.
     */
    protected function replaceVariables(string $prompt, Venture $venture): string
    {
        $replacements = [
            '{venture_title}' => $venture->title ?? 'Unknown Venture',
            '{venture_idea}' => $venture->idea_prompt ?? '',
            '{industry}' => $venture->industry ?? '',
            '{target_market}' => $venture->target_market ?? '',
            '{business_model}' => $venture->business_model ?? '',
        ];

        return strtr($prompt, $replacements);
    }

    /**
     * Append knowledge injection from active VentureKnowledgeSources.
     */
    protected function appendKnowledgeInjection(string $prompt, string $sectionSlug): string
    {
        $activeSources = VentureKnowledgeSource::where('is_active', true)
            ->orderBy('priority', 'asc')
            ->get()
            ->filter(function ($source) use ($sectionSlug) {
                // If applicable_sections is null or empty, apply to all sections
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
     */
    public function getDefaultPrompt(string $sectionSlug): string
    {
        $baseInstructions = "You are an expert startup advisor and business analyst. Analyze the provided venture and respond with valid JSON in the exact structure specified below. Respond ONLY with valid JSON. No markdown, no explanation.";

        return match ($sectionSlug) {
            // Dashboard Section
            'dashboard_executive_summary' => $baseInstructions . "\n\nFor venture: {venture_title}\nIdea: {venture_idea}\nIndustry: {industry}\nTarget Market: {target_market}\nBusiness Model: {business_model}\n\nProvide a concise executive summary (2-3 paragraphs) highlighting the key value proposition and market opportunity.\n\nReturn JSON:\n{\"summary\": \"The executive summary text here\"}",

            'dashboard_viability_score' => $baseInstructions . "\n\nFor venture: {venture_title}\nIdea: {venture_idea}\nIndustry: {industry}\nTarget Market: {target_market}\nBusiness Model: {business_model}\n\nEvaluate the viability of this venture on a scale of 0-100 considering market size, competition, team requirements, and market timing.\n\nReturn JSON:\n{\"score\": 75, \"rating\": \"Strong\", \"justification\": \"Explanation of the score\"}",

            'dashboard_key_metrics' => $baseInstructions . "\n\nFor venture: {venture_title}\nIdea: {venture_idea}\nIndustry: {industry}\nTarget Market: {target_market}\nBusiness Model: {business_model}\n\nIdentify 5-7 critical KPIs and metrics this venture should track.\n\nReturn JSON:\n{\"metrics\": [{\"name\": \"Metric Name\", \"description\": \"What it measures\", \"target\": \"Target value\", \"importance\": \"Why it matters\"}]}",

            'strategic_swot_analysis' => $baseInstructions . "\n\nFor venture: {venture_title}\nIdea: {venture_idea}\nIndustry: {industry}\nTarget Market: {target_market}\nBusiness Model: {business_model}\n\nConduct a comprehensive SWOT analysis. For each category, provide 4-5 points.\n\nReturn JSON:\n{\"strengths\": [\"...\"], \"weaknesses\": [\"...\"], \"opportunities\": [\"...\"], \"threats\": [\"...\"]}",

            'strategic_pestel_analysis' => $baseInstructions . "\n\nFor venture: {venture_title}\nIdea: {venture_idea}\nIndustry: {industry}\n\nConduct a PESTEL analysis.\n\nReturn JSON:\n{\"political\": [\"...\"], \"economic\": [\"...\"], \"social\": [\"...\"], \"technological\": [\"...\"], \"environmental\": [\"...\"], \"legal\": [\"...\"]}",

            'strategic_porters_five' => $baseInstructions . "\n\nFor venture: {venture_title}\nIdea: {venture_idea}\nIndustry: {industry}\n\nAnalyze using Porter's Five Forces.\n\nReturn JSON:\n{\"threat_of_new_entrants\": {\"intensity\": \"Medium\", \"explanation\": \"...\"}, \"bargaining_power_of_suppliers\": {\"intensity\": \"...\", \"explanation\": \"...\"}, \"bargaining_power_of_buyers\": {\"intensity\": \"...\", \"explanation\": \"...\"}, \"threat_of_substitutes\": {\"intensity\": \"...\", \"explanation\": \"...\"}, \"competitive_rivalry\": {\"intensity\": \"...\", \"explanation\": \"...\"}}",

            'mvp_feature_prioritization' => $baseInstructions . "\n\nFor venture: {venture_title}\nIdea: {venture_idea}\nTarget Market: {target_market}\n\nIdentify critical MVP features using MoSCoW prioritization.\n\nReturn JSON:\n{\"must_have\": [{\"feature\": \"Name\", \"description\": \"Why essential\", \"effort\": \"Low/Medium/High\"}], \"should_have\": [...], \"could_have\": [...], \"wont_have\": [...]}",

            'mvp_development_roadmap' => $baseInstructions . "\n\nFor venture: {venture_title}\nIdea: {venture_idea}\n\nCreate a phased MVP development roadmap.\n\nReturn JSON:\n{\"phases\": [{\"phase_name\": \"Phase 1\", \"duration_weeks\": 4, \"deliverables\": [\"...\"], \"key_milestones\": [\"...\"]}]}",

            'mvp_tech_stack' => $baseInstructions . "\n\nFor venture: {venture_title}\nIdea: {venture_idea}\nBusiness Model: {business_model}\n\nRecommend a technology stack for MVP development.\n\nReturn JSON:\n{\"frontend\": [\"...\"], \"backend\": [\"...\"], \"database\": [\"...\"], \"infrastructure\": [\"...\"], \"rationale\": \"Why this stack\"}",

            'finances_revenue_model' => $baseInstructions . "\n\nFor venture: {venture_title}\nBusiness Model: {business_model}\nTarget Market: {target_market}\n\nDefine the revenue model.\n\nReturn JSON:\n{\"model_type\": \"SaaS/Freemium/etc\", \"revenue_streams\": [{\"stream_name\": \"...\", \"description\": \"...\", \"average_price\": \"...\", \"volume_assumption\": \"...\"}], \"pricing_rationale\": \"...\"}",

            'finances_cost_structure' => $baseInstructions . "\n\nFor venture: {venture_title}\nBusiness Model: {business_model}\n\nOutline the cost structure.\n\nReturn JSON:\n{\"fixed_costs\": [{\"category\": \"...\", \"monthly_amount\": 0}], \"variable_costs\": [{\"category\": \"...\", \"per_unit_cost\": 0}], \"total_fixed_annual\": 0, \"total_variable_per_unit\": 0}",

            'finances_financial_projections' => $baseInstructions . "\n\nFor venture: {venture_title}\nBusiness Model: {business_model}\n\nCreate 3-year financial projections.\n\nReturn JSON:\n{\"projections\": [{\"year\": 1, \"revenue\": 0, \"gross_profit\": 0, \"operating_expenses\": 0, \"net_income\": 0}], \"key_metrics\": {\"cac\": 0, \"ltv\": 0, \"payback_months\": 0}}",

            'gtm_marketing_strategy' => $baseInstructions . "\n\nFor venture: {venture_title}\nTarget Market: {target_market}\n\nDevelop a marketing strategy.\n\nReturn JSON:\n{\"positioning\": \"...\", \"key_messages\": [\"...\"], \"target_segments\": [\"...\"], \"tactics\": [{\"tactic\": \"...\", \"description\": \"...\", \"budget_allocation\": \"...\", \"expected_roi\": \"...\"}]}",

            'gtm_channel_strategy' => $baseInstructions . "\n\nFor venture: {venture_title}\nTarget Market: {target_market}\n\nDefine go-to-market channels.\n\nReturn JSON:\n{\"channels\": [{\"channel\": \"...\", \"description\": \"...\", \"priority\": \"High/Medium/Low\", \"implementation_timeline\": \"...\", \"success_metrics\": \"...\"}]}",

            'gtm_launch_plan' => $baseInstructions . "\n\nFor venture: {venture_title}\n\nCreate a 90-day launch plan.\n\nReturn JSON:\n{\"phases\": [{\"phase\": \"Pre-Launch\", \"activities\": [\"...\"], \"milestones\": [\"...\"]}]}",

            'competitive_market_overview' => $baseInstructions . "\n\nFor venture: {venture_title}\nIndustry: {industry}\n\nProvide a market overview.\n\nReturn JSON:\n{\"market_size_current\": \"...\", \"market_size_projection_5yr\": \"...\", \"cagr\": \"...\", \"key_trends\": [\"...\"], \"market_dynamics\": \"...\"}",

            'competitive_competitor_profiles' => $baseInstructions . "\n\nFor venture: {venture_title}\nIndustry: {industry}\n\nProfile top 3-4 competitors.\n\nReturn JSON:\n{\"competitors\": [{\"name\": \"...\", \"founded\": 2020, \"funding\": \"...\", \"market_share\": \"...\", \"strengths\": [\"...\"], \"weaknesses\": [\"...\"], \"strategy\": \"...\"}]}",

            'competitive_positioning_map' => $baseInstructions . "\n\nFor venture: {venture_title}\nIndustry: {industry}\n\nCreate a competitive positioning map.\n\nReturn JSON:\n{\"x_axis\": \"...\", \"y_axis\": \"...\", \"positioning\": [{\"name\": \"...\", \"x\": 0.5, \"y\": 0.7}], \"opportunity\": \"...\"}",

            default => $baseInstructions . "\n\nFor venture: {venture_title}\nIdea: {venture_idea}\nIndustry: {industry}\nTarget Market: {target_market}\nBusiness Model: {business_model}\n\nAnalyze and provide relevant insights.\n\nReturn JSON with appropriate structure.",
        };
    }
}
