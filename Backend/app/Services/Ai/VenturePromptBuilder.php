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
You are an expert startup business analyst and strategic advisor. Your task is to generate comprehensive,
data-driven business analysis for startup ideas.

CRITICAL RULES:
1. Always respond with ONLY valid JSON - no markdown, no explanation, no code blocks.
2. Follow the exact JSON schema specified for each section.
3. Provide specific, actionable insights — not generic advice.
4. Use realistic numbers, percentages, and data points.
5. Include a "_score" field (0-100) rating the strength/viability of this aspect.
6. Tailor all analysis to the specific startup idea provided.
7. Be thorough but concise — aim for professional quality content.
8. When context from other sections is provided, maintain consistency.
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

        $prompt = "## Startup Idea\n{$ideaPrompt}\n\n";
        $prompt .= "## Task\n{$sectionPrompt}\n\n";
        $prompt .= "## Required JSON Schema\nRespond with ONLY this JSON structure:\n```json\n{$schema}\n```\n\n";

        if ($customInstruction) {
            $prompt .= "## Custom Instruction from User\n{$customInstruction}\n\n";
        }

        if (!empty($context['completed_sections'])) {
            $prompt .= "## Context from Other Sections (for consistency)\n";
            foreach ($context['completed_sections'] as $key => $content) {
                $summary = is_array($content) ? json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : (string) $content;
                // Limit context to avoid token overflow
                if (strlen($summary) > 500) {
                    $summary = substr($summary, 0, 500) . '...';
                }
                $prompt .= "### {$key}\n{$summary}\n\n";
            }
        }

        // Inject knowledge base from admin-configured knowledge sources
        try {
            $knowledgeText = VentureKnowledgeSource::getKnowledgeForPrompt();
            if (!empty($knowledgeText)) {
                $prompt .= "## Knowledge Base (Additional Context)\nUse the following knowledge to inform and improve your analysis:\n\n{$knowledgeText}";
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
            'about' => 'Generate a comprehensive "About" overview of this startup idea. Include the business concept, mission statement, vision, core values, and a brief description of the problem being solved and the proposed solution. Make it compelling and professional.',
            
            'viability_score' => 'Analyze the overall viability of this startup idea. Provide scores across multiple dimensions: market opportunity, competitive advantage, team requirements, financial viability, technical feasibility, and scalability. Include an overall viability score and brief justification for each dimension.',
            
            'market_size' => 'Calculate the Total Addressable Market (TAM), Serviceable Addressable Market (SAM), and Serviceable Obtainable Market (SOM) for this startup. Include market growth rate, key market drivers, and data sources. Use realistic numbers with clear methodology.',
            
            'industry_insight' => 'Provide deep industry insights including current trends, emerging technologies, regulatory landscape, key players, market dynamics, growth projections, and potential disruptions. Include specific data points and statistics.',
            
            'winning_usp' => 'Identify the single most compelling Unique Selling Proposition for this startup. Explain why this USP will win in the market, how it differentiates from competitors, and why customers will choose this over alternatives.',

            // Strategic Frameworks
            'strategy' => 'Develop a comprehensive business strategy including strategic vision, mission alignment, core strategic pillars (3-5), key strategic initiatives, competitive positioning, and a strategy execution roadmap with milestones.',
            
            'swot_analysis' => 'Perform a detailed SWOT analysis. For each quadrant (Strengths, Weaknesses, Opportunities, Threats), provide 5-7 specific, actionable items with explanations. Include a strategic implications summary showing how to leverage strengths, address weaknesses, capitalize on opportunities, and mitigate threats.',
            
            'pestel_analysis' => 'Conduct a thorough PESTEL analysis covering Political, Economic, Social, Technological, Environmental, and Legal factors. For each factor, provide 3-5 specific items with their impact level (high/medium/low) and strategic implications.',
            
            'porters_five_forces' => 'Analyze the competitive landscape using Porter\'s Five Forces: Threat of New Entrants, Bargaining Power of Suppliers, Bargaining Power of Buyers, Threat of Substitutes, and Competitive Rivalry. Rate each force (high/medium/low) with detailed explanation and strategic recommendations.',
            
            'catwoe' => 'Perform a CATWOE analysis: Customers (who benefits), Actors (who implements), Transformation (what changes), Weltanschauung (worldview/perspective), Owner (who controls), and Environmental constraints. Be specific and actionable.',
            
            'game_changing_idea' => 'Identify what makes this startup idea truly game-changing. Analyze disruptive potential, innovation factors, paradigm shifts it could create, and why NOW is the right time for this idea. Include comparison with similar disruptions in other industries.',

            // Path to MVP
            'core_features' => 'Define the core features for the MVP (Minimum Viable Product). List 8-12 features organized by priority (Must Have, Should Have, Nice to Have). For each feature, include description, user story, complexity estimate, and expected impact.',
            
            'market_validation' => 'Design a market validation strategy. Include validation methods (surveys, interviews, landing page tests, prototype testing), key hypotheses to validate, success metrics, sample size requirements, and expected timeline.',
            
            'timeline_milestones' => 'Create a detailed MVP development timeline with milestones. Include phases (Discovery, Design, Development, Testing, Launch), duration for each, key deliverables, dependencies, and risk buffers. Use a 12-week sprint plan.',
            
            'marketing_strategy' => 'Develop a comprehensive marketing strategy for the MVP launch. Include target audience segmentation, positioning statement, brand messaging framework, channel strategy, content plan, and budget allocation.',
            
            'marketing_slogans' => 'Generate 8-10 compelling marketing slogans/taglines for this startup. For each, provide the slogan, target emotion, use context (e.g., website hero, social media, ads), and a brief explanation of why it works.',
            
            'social_media_posts' => 'Create 10 ready-to-use social media posts across platforms (Twitter/X, LinkedIn, Instagram). Include post text, suggested hashtags, best posting time, and expected engagement type. Vary tone and format.',
            
            'marketing_channels' => 'Identify and rank the top 10 marketing channels for this startup. For each channel, provide estimated cost, expected ROI, difficulty level, timeline to results, and specific tactics to use.',
            
            'mvp_budget' => 'Create a detailed MVP budget breakdown. Include development costs, design costs, infrastructure, marketing, legal, tools/subscriptions, contingency, and total. Provide monthly and total estimates for a 3-month MVP build.',
            
            'mvp_kpis' => 'Define 10-12 KPIs for the MVP phase. For each KPI, provide: metric name, definition, target value, measurement method, frequency, and owner. Organize by category: Acquisition, Activation, Retention, Revenue, Referral.',

            // Unique Selling Points
            'usp_acid_test' => 'Apply the USP Acid Test to this startup. Evaluate: Is it unique? Is it meaningful to the customer? Is it sustainable? Is it communicable? Score each dimension and provide overall USP strength assessment.',
            
            'what_makes_you_unique' => 'Analyze 5-7 unique differentiators of this startup. For each, explain what makes it unique, why competitors can\'t easily replicate it, and how it creates value for customers.',
            
            'usp_examples' => 'Provide 5-6 examples of successful USPs from similar companies/industries. For each, analyze why the USP worked, what made it memorable, and what lessons can be applied to this startup.',
            
            'companies_overview' => 'Profile 5-8 companies in the same space (direct and indirect competitors). For each: company name, founding year, funding, revenue estimate, key features, target market, USP, and market share.',
            
            'ranking_analysis' => 'Create a competitive ranking analysis comparing this startup against top 5 competitors across 8-10 criteria. Provide numerical scores (1-10) for each criterion and an overall ranking with visualization data.',

            // Customer Persona
            'persona_overview' => 'Create 3 detailed customer personas. For each: name, age, occupation, income level, location, bio/background story, personality traits, tech savviness, and a day-in-the-life scenario.',
            
            'persona_challenges' => 'For each of the 3 customer personas, identify 5-7 key challenges and pain points related to the problem this startup solves. Include severity rating and frequency.',
            
            'persona_goals' => 'For each persona, define 5-7 goals they want to achieve. Include short-term and long-term goals, how this startup helps achieve them, and what success looks like for each persona.',
            
            'persona_objections' => 'For each persona, list 5-7 potential objections to using this product/service. Include the objection, root cause, and a compelling counter-argument or solution.',
            
            'persona_offerings' => 'For each persona, describe what specific features/services this startup can offer them. Map features to persona needs and explain the value proposition for each persona type.',
            
            'persona_demographics' => 'Provide detailed demographic data for the target market: age distribution, gender split, income levels, education, geographic distribution, device usage, and spending habits. Include percentages and data sources.',
            
            'persona_quotes' => 'Generate 8-10 realistic quotes that target customers might say about their current problems and needs. Each quote should include the persona name, context, and the pain point it reveals.',

            // Finances
            'finance_market_research' => 'Provide financial market research including industry revenue, growth rates, average deal sizes, customer acquisition costs in the industry, average lifetime values, and investment trends. Use specific numbers and cite data year.',
            
            'startup_costs' => 'Break down all startup costs in detail: legal/incorporation, technology infrastructure, product development, initial marketing, office/workspace, equipment, insurance, hiring, professional services, and miscellaneous. Provide one-time and recurring costs.',
            
            'revenue_projections' => 'Create 3-year revenue projections with monthly detail for Year 1 and quarterly for Years 2-3. Include revenue streams, pricing tiers, customer growth assumptions, conversion rates, and three scenarios: conservative, moderate, optimistic.',
            
            'operating_expenses' => 'Detail monthly operating expenses for the first 3 years: salaries, rent, utilities, marketing, tools/subscriptions, cloud hosting, insurance, legal, accounting, travel, and other. Include growth assumptions.',
            
            'breakeven_analysis' => 'Perform a breakeven analysis: fixed costs, variable costs per unit, contribution margin, breakeven point in units and revenue, time to breakeven under three scenarios. Include a breakeven chart data series.',
            
            'funding_and_risks' => 'Outline funding requirements by stage (pre-seed, seed, Series A). Include amount needed, use of funds breakdown, potential funding sources, equity dilution estimates, and top 5 financial risks with mitigation strategies.',
            
            'finance_kpis' => 'Define 10-12 financial KPIs: MRR, ARR, CAC, LTV, LTV:CAC ratio, burn rate, runway, gross margin, net margin, churn rate, ARPU, payback period. Provide target values for months 6, 12, and 24.',

            // Go-to-Market Strategy
            'gtm_roadmap' => 'Create a go-to-market roadmap spanning 12 months. Include 4 phases: Pre-Launch, Launch, Growth, Scale. For each phase, define objectives, key activities, channels, budget, and success metrics.',
            
            'target_market' => 'Define the target market in detail: primary segment, secondary segments, market size per segment, geographic focus, psychographic profile, buying behavior, and a prioritization matrix.',
            
            'value_proposition' => 'Craft a compelling value proposition using the Value Proposition Canvas. Map customer jobs, pains, and gains to the product\'s pain relievers and gain creators. Include a positioning statement and elevator pitch.',
            
            'pricing_strategy' => 'Develop a pricing strategy including pricing model (freemium, subscription, per-use, etc.), pricing tiers (3-4), feature comparison by tier, competitive price analysis, price sensitivity assessment, and recommended launch pricing.',
            
            'distribution_channels' => 'Identify and plan distribution channels: direct (website, app, sales team) and indirect (partnerships, resellers, affiliates, marketplace). For each, provide setup cost, margin impact, and scaling potential.',
            
            'gtm_marketing_plan' => 'Create a detailed marketing plan for the go-to-market phase. Include brand strategy, content marketing plan, paid acquisition strategy, organic growth tactics, PR/media strategy, and community building.',
            
            'sales_strategy' => 'Design the sales strategy: sales model (self-serve, inside sales, field sales), sales process/funnel, pipeline stages, conversion targets, sales team structure, compensation model, and tools needed.',
            
            'partnerships' => 'Identify 8-10 potential strategic partnerships. For each: partner type, specific company examples, partnership model, mutual benefits, implementation approach, and expected impact on growth.',
            
            'gtm_kpis' => 'Define go-to-market KPIs organized by funnel stage: Awareness (impressions, reach), Acquisition (sign-ups, trials), Activation (first-value metrics), Revenue (MRR, deals closed), and Referral (NPS, viral coefficient).',

            // Competitive Analysis (VRIO)
            'vrio_resources' => 'Conduct a VRIO analysis identifying 8-10 key resources and capabilities. For each, assess: Valuable (Y/N), Rare (Y/N), Costly to Imitate (Y/N), Organized to Capture Value (Y/N). Determine competitive implication for each.',
            
            'competitor_profiles' => 'Create detailed profiles of 5-8 key competitors. For each: company overview, founding/funding, products/services, target market, pricing, strengths, weaknesses, market share estimate, and recent moves.',
            
            'competitive_advantage' => 'Analyze the sustainable competitive advantages of this startup. Include: current advantages, advantage durability assessment, moat-building strategies, competitive response prediction, and long-term defensibility plan.',

            default => "Generate comprehensive analysis for the '{$sectionKey}' section of this startup business plan. Provide detailed, actionable insights with specific data points. Include a '_score' field (0-100) rating this aspect.",
        };
    }

    /**
     * Get the expected JSON schema for a section.
     */
    public function getSectionSchema(string $sectionKey): string
    {
        return match ($sectionKey) {
            'about' => '{
  "business_concept": "string",
  "mission_statement": "string",
  "vision": "string",
  "core_values": ["string"],
  "problem": "string",
  "solution": "string",
  "target_audience": "string",
  "business_model": "string",
  "_score": 0
}',
            'viability_score' => '{
  "overall_score": 0,
  "dimensions": [
    {"name": "string", "score": 0, "justification": "string"}
  ],
  "summary": "string",
  "key_strengths": ["string"],
  "key_risks": ["string"],
  "_score": 0
}',
            'market_size' => '{
  "tam": {"value": "$0B", "description": "string"},
  "sam": {"value": "$0M", "description": "string"},
  "som": {"value": "$0M", "description": "string"},
  "growth_rate": "0%",
  "market_drivers": ["string"],
  "data_sources": ["string"],
  "methodology": "string",
  "_score": 0
}',
            'industry_insight' => '{
  "current_trends": [{"trend": "string", "impact": "string"}],
  "emerging_technologies": [{"tech": "string", "relevance": "string"}],
  "regulatory_landscape": "string",
  "key_players": [{"name": "string", "market_share": "string"}],
  "growth_projections": {"short_term": "string", "long_term": "string"},
  "potential_disruptions": ["string"],
  "_score": 0
}',
            'winning_usp' => '{
  "usp_statement": "string",
  "why_it_wins": "string",
  "differentiation": "string",
  "customer_benefit": "string",
  "competitive_moat": "string",
  "_score": 0
}',
            'strategy' => '{
  "vision": "string",
  "mission": "string",
  "strategic_pillars": [{"name": "string", "description": "string", "initiatives": ["string"]}],
  "competitive_positioning": "string",
  "roadmap": [{"milestone": "string", "timeline": "string", "deliverables": ["string"]}],
  "_score": 0
}',
            'swot_analysis' => '{
  "strengths": [{"item": "string", "explanation": "string"}],
  "weaknesses": [{"item": "string", "explanation": "string"}],
  "opportunities": [{"item": "string", "explanation": "string"}],
  "threats": [{"item": "string", "explanation": "string"}],
  "strategic_implications": "string",
  "_score": 0
}',
            'pestel_analysis' => '{
  "political": [{"factor": "string", "impact": "high|medium|low", "implication": "string"}],
  "economic": [{"factor": "string", "impact": "high|medium|low", "implication": "string"}],
  "social": [{"factor": "string", "impact": "high|medium|low", "implication": "string"}],
  "technological": [{"factor": "string", "impact": "high|medium|low", "implication": "string"}],
  "environmental": [{"factor": "string", "impact": "high|medium|low", "implication": "string"}],
  "legal": [{"factor": "string", "impact": "high|medium|low", "implication": "string"}],
  "_score": 0
}',
            'porters_five_forces' => '{
  "threat_of_new_entrants": {"level": "high|medium|low", "explanation": "string", "factors": ["string"]},
  "bargaining_power_suppliers": {"level": "high|medium|low", "explanation": "string", "factors": ["string"]},
  "bargaining_power_buyers": {"level": "high|medium|low", "explanation": "string", "factors": ["string"]},
  "threat_of_substitutes": {"level": "high|medium|low", "explanation": "string", "factors": ["string"]},
  "competitive_rivalry": {"level": "high|medium|low", "explanation": "string", "factors": ["string"]},
  "recommendations": ["string"],
  "_score": 0
}',
            'catwoe' => '{
  "customers": {"who": "string", "details": "string"},
  "actors": {"who": "string", "details": "string"},
  "transformation": {"input": "string", "output": "string", "process": "string"},
  "weltanschauung": "string",
  "owner": {"who": "string", "responsibilities": "string"},
  "environmental_constraints": ["string"],
  "_score": 0
}',
            'game_changing_idea' => '{
  "disruptive_potential": "string",
  "innovation_factors": [{"factor": "string", "description": "string"}],
  "paradigm_shifts": ["string"],
  "why_now": "string",
  "similar_disruptions": [{"industry": "string", "example": "string", "lesson": "string"}],
  "_score": 0
}',
            'core_features' => '{
  "must_have": [{"feature": "string", "user_story": "string", "complexity": "low|medium|high", "impact": "string"}],
  "should_have": [{"feature": "string", "user_story": "string", "complexity": "low|medium|high", "impact": "string"}],
  "nice_to_have": [{"feature": "string", "user_story": "string", "complexity": "low|medium|high", "impact": "string"}],
  "_score": 0
}',
            'market_validation' => '{
  "hypotheses": [{"hypothesis": "string", "validation_method": "string", "success_metric": "string"}],
  "methods": [{"method": "string", "description": "string", "sample_size": 0, "timeline": "string"}],
  "expected_timeline": "string",
  "_score": 0
}',
            'timeline_milestones' => '{
  "phases": [{"name": "string", "duration": "string", "deliverables": ["string"], "dependencies": ["string"]}],
  "milestones": [{"week": 0, "milestone": "string", "success_criteria": "string"}],
  "total_duration": "string",
  "risk_buffers": "string",
  "_score": 0
}',
            'marketing_strategy' => '{
  "target_segments": [{"segment": "string", "size": "string", "priority": "high|medium|low"}],
  "positioning": "string",
  "messaging_framework": {"headline": "string", "subheadline": "string", "key_messages": ["string"]},
  "channel_strategy": [{"channel": "string", "budget_pct": 0, "expected_roi": "string"}],
  "content_plan": ["string"],
  "_score": 0
}',
            'marketing_slogans' => '{
  "slogans": [{"slogan": "string", "target_emotion": "string", "use_context": "string", "why_it_works": "string"}],
  "_score": 0
}',
            'social_media_posts' => '{
  "posts": [{"platform": "string", "text": "string", "hashtags": ["string"], "best_time": "string", "engagement_type": "string"}],
  "_score": 0
}',
            'marketing_channels' => '{
  "channels": [{"rank": 0, "channel": "string", "cost": "string", "expected_roi": "string", "difficulty": "low|medium|high", "timeline": "string", "tactics": ["string"]}],
  "_score": 0
}',
            'mvp_budget' => '{
  "categories": [{"category": "string", "items": [{"item": "string", "one_time": 0, "monthly": 0}], "subtotal_one_time": 0, "subtotal_monthly": 0}],
  "total_one_time": 0,
  "total_monthly": 0,
  "total_3_month": 0,
  "contingency_pct": 10,
  "grand_total": 0,
  "_score": 0
}',
            'mvp_kpis' => '{
  "kpis": [{"name": "string", "category": "Acquisition|Activation|Retention|Revenue|Referral", "definition": "string", "target": "string", "measurement": "string", "frequency": "string"}],
  "_score": 0
}',
            'usp_acid_test' => '{
  "is_unique": {"score": 0, "explanation": "string"},
  "is_meaningful": {"score": 0, "explanation": "string"},
  "is_sustainable": {"score": 0, "explanation": "string"},
  "is_communicable": {"score": 0, "explanation": "string"},
  "overall_strength": "string",
  "_score": 0
}',
            'what_makes_you_unique' => '{
  "differentiators": [{"factor": "string", "why_unique": "string", "replication_difficulty": "string", "customer_value": "string"}],
  "_score": 0
}',
            'usp_examples' => '{
  "examples": [{"company": "string", "usp": "string", "why_it_worked": "string", "lesson": "string"}],
  "_score": 0
}',
            'companies_overview' => '{
  "companies": [{"name": "string", "founded": "string", "funding": "string", "revenue_estimate": "string", "features": ["string"], "target_market": "string", "usp": "string", "market_share": "string"}],
  "_score": 0
}',
            'ranking_analysis' => '{
  "criteria": ["string"],
  "competitors": [{"name": "string", "scores": [0], "total": 0}],
  "this_startup": {"name": "string", "scores": [0], "total": 0},
  "chart_data": {"labels": ["string"], "datasets": [{"label": "string", "data": [0]}]},
  "_score": 0
}',
            'persona_overview' => '{
  "personas": [{"name": "string", "age": 0, "occupation": "string", "income": "string", "location": "string", "bio": "string", "personality_traits": ["string"], "tech_savviness": "string", "day_in_life": "string"}],
  "_score": 0
}',
            'persona_challenges' => '{
  "personas": [{"name": "string", "challenges": [{"challenge": "string", "severity": "high|medium|low", "frequency": "daily|weekly|monthly"}]}],
  "_score": 0
}',
            'persona_goals' => '{
  "personas": [{"name": "string", "short_term_goals": [{"goal": "string", "how_we_help": "string"}], "long_term_goals": [{"goal": "string", "how_we_help": "string"}], "success_definition": "string"}],
  "_score": 0
}',
            'persona_objections' => '{
  "personas": [{"name": "string", "objections": [{"objection": "string", "root_cause": "string", "counter_argument": "string"}]}],
  "_score": 0
}',
            'persona_offerings' => '{
  "personas": [{"name": "string", "offerings": [{"feature": "string", "value_for_persona": "string", "priority": "high|medium|low"}]}],
  "_score": 0
}',
            'persona_demographics' => '{
  "age_distribution": [{"range": "string", "percentage": 0}],
  "gender_split": {"male": 0, "female": 0, "other": 0},
  "income_levels": [{"range": "string", "percentage": 0}],
  "education": [{"level": "string", "percentage": 0}],
  "geographic": [{"region": "string", "percentage": 0}],
  "device_usage": {"mobile": 0, "desktop": 0, "tablet": 0},
  "spending_habits": "string",
  "_score": 0
}',
            'persona_quotes' => '{
  "quotes": [{"persona_name": "string", "quote": "string", "context": "string", "pain_point": "string"}],
  "_score": 0
}',
            'finance_market_research' => '{
  "industry_revenue": "string",
  "growth_rate": "string",
  "average_deal_size": "string",
  "average_cac": "string",
  "average_ltv": "string",
  "investment_trends": [{"year": "string", "amount": "string", "notable_deals": "string"}],
  "key_findings": ["string"],
  "_score": 0
}',
            'startup_costs' => '{
  "categories": [{"category": "string", "items": [{"item": "string", "cost": 0, "type": "one-time|recurring", "notes": "string"}], "subtotal": 0}],
  "total_one_time": 0,
  "total_recurring_monthly": 0,
  "total_first_year": 0,
  "_score": 0
}',
            'revenue_projections' => '{
  "revenue_streams": [{"stream": "string", "pricing": "string", "description": "string"}],
  "scenarios": {
    "conservative": {"year1": 0, "year2": 0, "year3": 0, "assumptions": "string"},
    "moderate": {"year1": 0, "year2": 0, "year3": 0, "assumptions": "string"},
    "optimistic": {"year1": 0, "year2": 0, "year3": 0, "assumptions": "string"}
  },
  "monthly_year1": [{"month": "string", "revenue": 0, "customers": 0}],
  "_score": 0
}',
            'operating_expenses' => '{
  "monthly_breakdown": [{"category": "string", "month_1_6": 0, "month_7_12": 0, "year_2": 0, "year_3": 0}],
  "total_year_1": 0,
  "total_year_2": 0,
  "total_year_3": 0,
  "growth_assumptions": "string",
  "_score": 0
}',
            'breakeven_analysis' => '{
  "fixed_costs_monthly": 0,
  "variable_cost_per_unit": 0,
  "average_revenue_per_unit": 0,
  "contribution_margin": 0,
  "breakeven_units": 0,
  "breakeven_revenue": 0,
  "scenarios": {
    "conservative": {"months_to_breakeven": 0},
    "moderate": {"months_to_breakeven": 0},
    "optimistic": {"months_to_breakeven": 0}
  },
  "chart_data": {"labels": ["string"], "revenue": [0], "costs": [0]},
  "_score": 0
}',
            'funding_and_risks' => '{
  "funding_stages": [{"stage": "string", "amount": 0, "use_of_funds": [{"item": "string", "percentage": 0}], "sources": ["string"], "equity_dilution": "string"}],
  "financial_risks": [{"risk": "string", "probability": "high|medium|low", "impact": "high|medium|low", "mitigation": "string"}],
  "_score": 0
}',
            'finance_kpis' => '{
  "kpis": [{"name": "string", "definition": "string", "month_6_target": "string", "month_12_target": "string", "month_24_target": "string"}],
  "_score": 0
}',
            'gtm_roadmap' => '{
  "phases": [{"name": "string", "duration": "string", "objectives": ["string"], "key_activities": ["string"], "channels": ["string"], "budget": 0, "success_metrics": ["string"]}],
  "_score": 0
}',
            'target_market' => '{
  "primary_segment": {"name": "string", "size": "string", "characteristics": ["string"]},
  "secondary_segments": [{"name": "string", "size": "string", "characteristics": ["string"]}],
  "geographic_focus": ["string"],
  "psychographic_profile": "string",
  "buying_behavior": "string",
  "prioritization": [{"segment": "string", "priority": 0, "reasoning": "string"}],
  "_score": 0
}',
            'value_proposition' => '{
  "customer_jobs": ["string"],
  "pains": ["string"],
  "gains": ["string"],
  "pain_relievers": ["string"],
  "gain_creators": ["string"],
  "positioning_statement": "string",
  "elevator_pitch": "string",
  "_score": 0
}',
            'pricing_strategy' => '{
  "model": "string",
  "tiers": [{"name": "string", "price": "string", "billing": "string", "features": ["string"], "target": "string"}],
  "competitive_comparison": [{"competitor": "string", "price": "string", "notes": "string"}],
  "price_sensitivity": "string",
  "recommended_launch_price": "string",
  "_score": 0
}',
            'distribution_channels' => '{
  "direct": [{"channel": "string", "setup_cost": 0, "margin_impact": "string", "scaling_potential": "string"}],
  "indirect": [{"channel": "string", "setup_cost": 0, "margin_impact": "string", "scaling_potential": "string"}],
  "recommended_mix": "string",
  "_score": 0
}',
            'gtm_marketing_plan' => '{
  "brand_strategy": "string",
  "content_marketing": [{"type": "string", "frequency": "string", "channels": ["string"]}],
  "paid_acquisition": [{"channel": "string", "budget": 0, "expected_cac": 0}],
  "organic_tactics": ["string"],
  "pr_strategy": "string",
  "community_building": "string",
  "_score": 0
}',
            'sales_strategy' => '{
  "sales_model": "string",
  "funnel_stages": [{"stage": "string", "conversion_target": "string", "activities": ["string"]}],
  "team_structure": [{"role": "string", "count": 0, "responsibilities": "string"}],
  "compensation": "string",
  "tools": ["string"],
  "_score": 0
}',
            'partnerships' => '{
  "partnerships": [{"type": "string", "company_examples": ["string"], "model": "string", "mutual_benefits": "string", "implementation": "string", "growth_impact": "string"}],
  "_score": 0
}',
            'gtm_kpis' => '{
  "awareness": [{"metric": "string", "target": "string"}],
  "acquisition": [{"metric": "string", "target": "string"}],
  "activation": [{"metric": "string", "target": "string"}],
  "revenue": [{"metric": "string", "target": "string"}],
  "referral": [{"metric": "string", "target": "string"}],
  "_score": 0
}',
            'vrio_resources' => '{
  "resources": [{"resource": "string", "valuable": true, "rare": true, "costly_to_imitate": true, "organized": true, "competitive_implication": "string"}],
  "summary": "string",
  "_score": 0
}',
            'competitor_profiles' => '{
  "competitors": [{"name": "string", "overview": "string", "founded": "string", "funding": "string", "products": ["string"], "target_market": "string", "pricing": "string", "strengths": ["string"], "weaknesses": ["string"], "market_share": "string", "recent_moves": ["string"]}],
  "_score": 0
}',
            'competitive_advantage' => '{
  "current_advantages": [{"advantage": "string", "description": "string", "durability": "high|medium|low"}],
  "moat_strategies": [{"strategy": "string", "description": "string", "timeline": "string"}],
  "competitive_response": "string",
  "long_term_defensibility": "string",
  "_score": 0
}',
            default => '{"content": "string", "_score": 0}',
        };
    }
}
