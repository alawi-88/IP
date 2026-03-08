<?php

namespace App\Services\Ai;

use App\Models\VenturePromptTemplate;
use App\Models\VentureKnowledgeSource;

class VenturePromptBuilder
{
    /**
     * The default system prompt, used as fallback when no DB override exists.
     */
    private const DEFAULT_SYSTEM_PROMPT = <<<'SYSTEM'
Expert startup analyst. Generate business analysis as valid JSON only (no markdown/explanation). Rules: Follow exact schema, be specific with real data, include "_score" (0-100), stay consistent with context.
SYSTEM;

    /**
     * Get the system prompt for all venture AI generations.
     * Checks database for admin override first, falls back to hardcoded default.
     */
    public function getSystemPrompt(): string
    {
        try {
            $override = VenturePromptTemplate::getSystemPromptOverride();
            if ($override) {
                return $override;
            }
        } catch (\Exception $e) {
            // If DB is unavailable, silently fall back to default
        }

        return self::DEFAULT_SYSTEM_PROMPT;
    }

    /**
     * Build the full prompt for a specific section.
     */
    public function buildSectionPrompt(
        string $ideaPrompt,
        string $sectionKey,
        ?string $customInstruction = null,
        array $context = []
    ): string {
        $sectionPrompt = $this->getSectionPrompt($sectionKey);
        $schema = $this->getSectionSchema($sectionKey);

        $prompt = "Idea: {$ideaPrompt}\n\n";
        $prompt .= "Task: {$sectionPrompt}\n\n";
        $prompt .= "Schema:\n{$schema}\n\n";

        if ($customInstruction) {
            $prompt .= "Custom: {$customInstruction}\n\n";
        }

        if (!empty($context['completed_sections'])) {
            $prompt .= "Context:\n";
            foreach ($context['completed_sections'] as $key => $content) {
                $summary = is_array($content) ? json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : (string) $content;
                // Limit context to 200 chars to reduce token usage
                if (strlen($summary) > 200) {
                    $summary = substr($summary, 0, 200) . '...';
                }
                $prompt .= "{$key}: {$summary}\n";
            }
        }

        // Inject knowledge base from admin-configured knowledge sources
        try {
            $knowledgeText = VentureKnowledgeSource::getKnowledgeForPrompt();
            if (!empty($knowledgeText)) {
                $prompt .= "\nKnowledge: {$knowledgeText}";
            }
        } catch (\Exception $e) {
            // If DB is unavailable, silently skip knowledge injection
        }

        return $prompt;
    }

    /**
     * Get the section-specific prompt instructions.
     * Checks database for admin override first, falls back to hardcoded default.
     */
    public function getSectionPrompt(string $sectionKey): string
    {
        try {
            $override = VenturePromptTemplate::getSectionPromptOverride($sectionKey);
            if ($override) {
                return $override;
            }
        } catch (\Exception $e) {
            // If DB is unavailable, silently fall back to default
        }

        return $this->getDefaultSectionPrompt($sectionKey);
    }

    /**
     * Get the hardcoded default section prompt (used as fallback).
     */
    public function getDefaultSectionPrompt(string $sectionKey): string
    {
        return match ($sectionKey) {
            // Dashboard
            'about' => 'Generate the startup About overview: concept, mission, vision, values, problem, and solution.',
            'viability_score' => 'Analyze startup viability across: market opportunity, competitive advantage, team needs, financial viability, technical feasibility, scalability. Provide scores and justifications.',
            'market_size' => 'Calculate TAM, SAM, and SOM with growth rate, market drivers, and methodology.',
            'industry_insight' => 'Analyze industry trends, emerging tech, regulatory landscape, key players, growth, and disruptions.',
            'winning_usp' => 'Identify the most compelling USP. Explain why it wins, how it differentiates, and why customers choose it.',

            // Strategic Frameworks
            'strategy' => 'Develop business strategy: vision, mission, 3-5 strategic pillars, competitive positioning, and execution roadmap.',
            'swot_analysis' => 'Perform SWOT analysis with 5-7 items per quadrant and strategic implications.',
            'pestel_analysis' => 'Conduct PESTEL analysis covering all six factors with impact levels and implications.',
            'porters_five_forces' => 'Analyze competitive landscape using Porter Five Forces with ratings and strategic recommendations.',
            'catwoe' => 'Perform CATWOE analysis: Customers, Actors, Transformation, Weltanschauung, Owner, Environmental constraints.',
            'game_changing_idea' => 'Identify game-changing potential: disruptive factors, innovation, paradigm shifts, timing, and industry parallels.',

            // Path to MVP
            'core_features' => 'List 8-12 MVP features in priority order (Must Have, Should Have, Nice to Have) with user stories and complexity.',
            'market_validation' => 'Design validation strategy with methods, hypotheses, success metrics, sample sizes, and timeline.',
            'timeline_milestones' => 'Create 12-week MVP timeline with phases, deliverables, dependencies, and risk buffers.',
            'marketing_strategy' => 'Develop marketing strategy: audience segments, positioning, messaging, channels, and content plan.',
            'marketing_slogans' => 'Generate 8-10 compelling slogans with target emotion, context, and explanation.',
            'social_media_posts' => 'Create 10 ready-to-use social media posts across platforms with hashtags and timing.',
            'marketing_channels' => 'Rank top 10 marketing channels with cost, ROI, difficulty, timeline, and tactics.',
            'mvp_budget' => 'Create detailed MVP budget: development, design, infrastructure, marketing, legal, tools, contingency.',
            'mvp_kpis' => 'Define 10-12 MVP KPIs organized by AARRR funnel with targets and measurement methods.',

            // Unique Selling Points
            'usp_acid_test' => 'Apply USP Acid Test: evaluate uniqueness, meaningfulness, sustainability, and communicability.',
            'what_makes_you_unique' => 'Analyze 5-7 unique differentiators with replication difficulty and customer value.',
            'usp_examples' => 'Provide 5-6 successful USP examples from similar companies with lessons.',
            'companies_overview' => 'Profile 5-8 competitors with funding, revenue, features, target market, USP, and market share.',
            'ranking_analysis' => 'Create competitive ranking across 8-10 criteria comparing this startup vs top 5 competitors.',

            // Customer Persona
            'persona_overview' => 'Create 3 detailed personas: name, age, occupation, income, location, bio, traits, tech level, day-in-life.',
            'persona_challenges' => 'For each persona, list 5-7 key challenges and pain points with severity and frequency.',
            'persona_goals' => 'For each persona, define 5-7 goals (short and long-term) and how the product helps.',
            'persona_objections' => 'For each persona, list 5-7 objections with root causes and counter-arguments.',
            'persona_offerings' => 'For each persona, describe specific features offered and their value.',
            'persona_demographics' => 'Provide demographic data: age, gender, income, education, geography, device usage, spending.',
            'persona_quotes' => 'Generate 8-10 realistic customer quotes revealing pain points.',

            // Finances
            'finance_market_research' => 'Provide market research: industry revenue, growth rates, deal sizes, CAC, LTV, investment trends.',
            'startup_costs' => 'Break down startup costs: legal, tech, development, marketing, office, equipment, insurance, hiring.',
            'revenue_projections' => 'Create 3-year revenue projections with streams, pricing, growth assumptions, and three scenarios.',
            'operating_expenses' => 'Detail monthly operating expenses for 3 years with growth assumptions.',
            'breakeven_analysis' => 'Perform breakeven analysis with fixed/variable costs, breakeven point, and three scenarios.',
            'funding_and_risks' => 'Outline funding by stage, use of funds, sources, equity dilution, and top 5 financial risks.',
            'finance_kpis' => 'Define 10-12 financial KPIs (MRR, ARR, CAC, LTV, etc.) with targets for months 6, 12, 24.',

            // Go-to-Market Strategy
            'gtm_roadmap' => 'Create 12-month GTM roadmap: Pre-Launch, Launch, Growth, Scale phases with objectives and metrics.',
            'target_market' => 'Define target market: primary/secondary segments, size, geography, psychographics, buying behavior.',
            'value_proposition' => 'Craft value proposition using Canvas: map jobs, pains, gains to relievers and creators.',
            'pricing_strategy' => 'Develop pricing: model, 3-4 tiers, competitive comparison, price sensitivity, launch pricing.',
            'distribution_channels' => 'Identify direct and indirect channels with setup cost, margin impact, and scaling potential.',
            'gtm_marketing_plan' => 'Create marketing plan: brand, content, paid, organic, PR, and community strategies.',
            'sales_strategy' => 'Design sales strategy: model, funnel, conversion targets, team structure, compensation, tools.',
            'partnerships' => 'Identify 8-10 strategic partnerships with mutual benefits and growth impact.',
            'gtm_kpis' => 'Define GTM KPIs by funnel: Awareness, Acquisition, Activation, Revenue, Referral.',

            // Competitive Analysis (VRIO)
            'vrio_resources' => 'Assess 8-10 resources using VRIO framework: Valuable, Rare, Costly to Imitate, Organized.',
            'competitor_profiles' => 'Profile 5-8 competitors: overview, founding, funding, products, pricing, strengths, weaknesses.',
            'competitive_advantage' => 'Analyze sustainable advantages: current moats, durability, defensibility, competitive response.',

            default => "Generate analysis for '{$sectionKey}' with specific data and a '_score' (0-100).",
        };
    }

    /**
     * Get the expected JSON schema for a section.
     */
    public function getSectionSchema(string $sectionKey): string
    {
        return match ($sectionKey) {
            'about' => '{"business_concept":"","mission_statement":"","vision":"","core_values":[""],"problem":"","solution":"","target_audience":"","business_model":"","_score":0}',
            'viability_score' => '{"overall_score":0,"dimensions":[{"name":"","score":0,"justification":""}],"summary":"","key_strengths":[""],"key_risks":[""],"_score":0}',
            'market_size' => '{"tam":{"value":"$0B","description":""},"sam":{"value":"$0M","description":""},"som":{"value":"$0M","description":""},"growth_rate":"0%","market_drivers":[""],"data_sources":[""],"methodology":"","_score":0}',
            'industry_insight' => '{"current_trends":[{"trend":"","impact":""}],"emerging_technologies":[{"tech":"","relevance":""}],"regulatory_landscape":"","key_players":[{"name":"","market_share":""}],"growth_projections":{"short_term":"","long_term":""},"potential_disruptions":[""],"_score":0}',
            'winning_usp' => '{"usp_statement":"","why_it_wins":"","differentiation":"","customer_benefit":"","competitive_moat":"","_score":0}',
            'strategy' => '{"vision":"","mission":"","strategic_pillars":[{"name":"","description":"","initiatives":[""]}],"competitive_positioning":"","roadmap":[{"milestone":"","timeline":"","deliverables":[""]}],"_score":0}',
            'swot_analysis' => '{"strengths":[{"item":"","explanation":""}],"weaknesses":[{"item":"","explanation":""}],"opportunities":[{"item":"","explanation":""}],"threats":[{"item":"","explanation":""}],"strategic_implications":"","_score":0}',
            'pestel_analysis' => '{"political":[{"factor":"","impact":"high|medium|low","implication":""}],"economic":[{"factor":"","impact":"high|medium|low","implication":""}],"social":[{"factor":"","impact":"high|medium|low","implication":""}],"technological":[{"factor":"","impact":"high|medium|low","implication":""}],"environmental":[{"factor":"","impact":"high|medium|low","implication":""}],"legal":[{"factor":"","impact":"high|medium|low","implication":""}],"_score":0}',
            'porters_five_forces' => '{"threat_of_new_entrants":{"level":"high|medium|low","explanation":"","factors":[""]},"bargaining_power_suppliers":{"level":"high|medium|low","explanation":"","factors":[""]},"bargaining_power_buyers":{"level":"high|medium|low","explanation":"","factors":[""]},"threat_of_substitutes":{"level":"high|medium|low","explanation":"","factors":[""]},"competitive_rivalry":{"level":"high|medium|low","explanation":"","factors":[""]},"recommendations":[""],"_score":0}',
            'catwoe' => '{"customers":{"who":"","details":""},"actors":{"who":"","details":""},"transformation":{"input":"","output":"","process":""},"weltanschauung":"","owner":{"who":"","responsibilities":""},"environmental_constraints":[""],"_score":0}',
            'game_changing_idea' => '{"disruptive_potential":"","innovation_factors":[{"factor":"","description":""}],"paradigm_shifts":[""],"why_now":"","similar_disruptions":[{"industry":"","example":"","lesson":""}],"_score":0}',
            'core_features' => '{"must_have":[{"feature":"","user_story":"","complexity":"low|medium|high","impact":""}],"should_have":[{"feature":"","user_story":"","complexity":"low|medium|high","impact":""}],"nice_to_have":[{"feature":"","user_story":"","complexity":"low|medium|high","impact":""}],"_score":0}',
            'market_validation' => '{"hypotheses":[{"hypothesis":"","validation_method":"","success_metric":""}],"methods":[{"method":"","description":"","sample_size":0,"timeline":""}],"expected_timeline":"","_score":0}',
            'timeline_milestones' => '{"phases":[{"name":"","duration":"","deliverables":[""],"dependencies":[""]}],"milestones":[{"week":0,"milestone":"","success_criteria":""}],"total_duration":"","risk_buffers":"","_score":0}',
            'marketing_strategy' => '{"target_segments":[{"segment":"","size":"","priority":"high|medium|low"}],"positioning":"","messaging_framework":{"headline":"","subheadline":"","key_messages":[""]},"channel_strategy":[{"channel":"","budget_pct":0,"expected_roi":""}],"content_plan":[""],"_score":0}',
            'marketing_slogans' => '{"slogans":[{"slogan":"","target_emotion":"","use_context":"","why_it_works":""}],"_score":0}',
            'social_media_posts' => '{"posts":[{"platform":"","text":"","hashtags":[""],"best_time":"","engagement_type":""}],"_score":0}',
            'marketing_channels' => '{"channels":[{"rank":0,"channel":"","cost":"","expected_roi":"","difficulty":"low|medium|high","timeline":"","tactics":[""]}],"_score":0}',
            'mvp_budget' => '{"categories":[{"category":"","items":[{"item":"","one_time":0,"monthly":0}],"subtotal_one_time":0,"subtotal_monthly":0}],"total_one_time":0,"total_monthly":0,"total_3_month":0,"contingency_pct":10,"grand_total":0,"_score":0}',
            'mvp_kpis' => '{"kpis":[{"name":"","category":"Acquisition|Activation|Retention|Revenue|Referral","definition":"","target":"","measurement":"","frequency":""}],"_score":0}',
            'usp_acid_test' => '{"is_unique":{"score":0,"explanation":""},"is_meaningful":{"score":0,"explanation":""},"is_sustainable":{"score":0,"explanation":""},"is_communicable":{"score":0,"explanation":""},"overall_strength":"","_score":0}',
            'what_makes_you_unique' => '{"differentiators":[{"factor":"","why_unique":"","replication_difficulty":"","customer_value":""}],"_score":0}',
            'usp_examples' => '{"examples":[{"company":"","usp":"","why_it_worked":"","lesson":""}],"_score":0}',
            'companies_overview' => '{"companies":[{"name":"","founded":"","funding":"","revenue_estimate":"","features":[""],"target_market":"","usp":"","market_share":""}],"_score":0}',
            'ranking_analysis' => '{"criteria":[""],"competitors":[{"name":"","scores":[0],"total":0}],"this_startup":{"name":"","scores":[0],"total":0},"chart_data":{"labels":[""],"datasets":[{"label":"","data":[0]}]},"_score":0}',
            'persona_overview' => '{"personas":[{"name":"","age":0,"occupation":"","income":"","location":"","bio":"","personality_traits":[""],"tech_savviness":"","day_in_life":""}],"_score":0}',
            'persona_challenges' => '{"personas":[{"name":"","challenges":[{"challenge":"","severity":"high|medium|low","frequency":"daily|weekly|monthly"}]}],"_score":0}',
            'persona_goals' => '{"personas":[{"name":"","short_term_goals":[{"goal":"","how_we_help":""}],"long_term_goals":[{"goal":"","how_we_help":""}],"success_definition":""}],"_score":0}',
            'persona_objections' => '{"personas":[{"name":"","objections":[{"objection":"","root_cause":"","counter_argument":""}]}],"_score":0}',
            'persona_offerings' => '{"personas":[{"name":"","offerings":[{"feature":"","value_for_persona":"","priority":"high|medium|low"}]}],"_score":0}',
            'persona_demographics' => '{"age_distribution":[{"range":"","percentage":0}],"gender_split":{"male":0,"female":0,"other":0},"income_levels":[{"range":"","percentage":0}],"education":[{"level":"","percentage":0}],"geographic":[{"region":"","percentage":0}],"device_usage":{"mobile":0,"desktop":0,"tablet":0},"spending_habits":"","_score":0}',
            'persona_quotes' => '{"quotes":[{"persona_name":"","quote":"","context":"","pain_point":""}],"_score":0}',
            'finance_market_research' => '{"industry_revenue":"","growth_rate":"","average_deal_size":"","average_cac":"","average_ltv":"","investment_trends":[{"year":"","amount":"","notable_deals":""}],"key_findings":[""],"_score":0}',
            'startup_costs' => '{"categories":[{"category":"","items":[{"item":"","cost":0,"type":"one-time|recurring","notes":""}],"subtotal":0}],"total_one_time":0,"total_recurring_monthly":0,"total_first_year":0,"_score":0}',
            'revenue_projections' => '{"revenue_streams":[{"stream":"","pricing":"","description":""}],"scenarios":{"conservative":{"year1":0,"year2":0,"year3":0,"assumptions":""},"moderate":{"year1":0,"year2":0,"year3":0,"assumptions":""},"optimistic":{"year1":0,"year2":0,"year3":0,"assumptions":""}},"monthly_year1":[{"month":"","revenue":0,"customers":0}],"_score":0}',
            'operating_expenses' => '{"monthly_breakdown":[{"category":"","month_1_6":0,"month_7_12":0,"year_2":0,"year_3":0}],"total_year_1":0,"total_year_2":0,"total_year_3":0,"growth_assumptions":"","_score":0}',
            'breakeven_analysis' => '{"fixed_costs_monthly":0,"variable_cost_per_unit":0,"average_revenue_per_unit":0,"contribution_margin":0,"breakeven_units":0,"breakeven_revenue":0,"scenarios":{"conservative":{"months_to_breakeven":0},"moderate":{"months_to_breakeven":0},"optimistic":{"months_to_breakeven":0}},"chart_data":{"labels":[""],"revenue":[0],"costs":[0]},"_score":0}',
            'funding_and_risks' => '{"funding_stages":[{"stage":"","amount":0,"use_of_funds":[{"item":"","percentage":0}],"sources":[""],"equity_dilution":""}],"financial_risks":[{"risk":"","probability":"high|medium|low","impact":"high|medium|low","mitigation":""}],"_score":0}',
            'finance_kpis' => '{"kpis":[{"name":"","definition":"","month_6_target":"","month_12_target":"","month_24_target":""}],"_score":0}',
            'gtm_roadmap' => '{"phases":[{"name":"","duration":"","objectives":[""],"key_activities":[""],"channels":[""],"budget":0,"success_metrics":[""]}],"_score":0}',
            'target_market' => '{"primary_segment":{"name":"","size":"","characteristics":[""]},"secondary_segments":[{"name":"","size":"","characteristics":[""]}],"geographic_focus":[""],"psychographic_profile":"","buying_behavior":"","prioritization":[{"segment":"","priority":0,"reasoning":""}],"_score":0}',
            'value_proposition' => '{"customer_jobs":[""],"pains":[""],"gains":[""],"pain_relievers":[""],"gain_creators":[""],"positioning_statement":"","elevator_pitch":"","_score":0}',
            'pricing_strategy' => '{"model":"","tiers":[{"name":"","price":"","billing":"","features":[""],"target":""}],"competitive_comparison":[{"competitor":"","price":"","notes":""}],"price_sensitivity":"","recommended_launch_price":"","_score":0}',
            'distribution_channels' => '{"direct":[{"channel":"","setup_cost":0,"margin_impact":"","scaling_potential":""}],"indirect":[{"channel":"","setup_cost":0,"margin_impact":"","scaling_potential":""}],"recommended_mix":"","_score":0}',
            'gtm_marketing_plan' => '{"brand_strategy":"","content_marketing":[{"type":"","frequency":"","channels":[""]}],"paid_acquisition":[{"channel":"","budget":0,"expected_cac":0}],"organic_tactics":[""],"pr_strategy":"","community_building":"","_score":0}',
            'sales_strategy' => '{"sales_model":"","funnel_stages":[{"stage":"","conversion_target":"","activities":[""]}],"team_structure":[{"role":"","count":0,"responsibilities":""}],"compensation":"","tools":[""],"_score":0}',
            'partnerships' => '{"partnerships":[{"type":"","company_examples":[""],"model":"","mutual_benefits":"","implementation":"","growth_impact":""}],"_score":0}',
            'gtm_kpis' => '{"awareness":[{"metric":"","target":""}],"acquisition":[{"metric":"","target":""}],"activation":[{"metric":"","target":""}],"revenue":[{"metric":"","target":""}],"referral":[{"metric":"","target":""}],"_score":0}',
            'vrio_resources' => '{"resources":[{"resource":"","valuable":true,"rare":true,"costly_to_imitate":true,"organized":true,"competitive_implication":""}],"summary":"","_score":0}',
            'competitor_profiles' => '{"competitors":[{"name":"","overview":"","founded":"","funding":"","products":[""],"target_market":"","pricing":"","strengths":[""],"weaknesses":[""],"market_share":"","recent_moves":[""]}],"_score":0}',
            'competitive_advantage' => '{"current_advantages":[{"advantage":"","description":"","durability":"high|medium|low"}],"moat_strategies":[{"strategy":"","description":"","timeline":""}],"competitive_response":"","long_term_defensibility":"","_score":0}',

            default => '{"content":"","_score":0}',
        };
    }
}
