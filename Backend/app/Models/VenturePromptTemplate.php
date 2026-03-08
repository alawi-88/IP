<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VenturePromptTemplate extends Model
{
    protected $fillable = [
        'section_key',
        'system_prompt',
        'section_prompt',
        'is_active',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the admin user who last updated this template.
     */
    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope to only active templates.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the active system prompt override (or null to use default).
     */
    public static function getSystemPromptOverride(): ?string
    {
        $template = static::active()
            ->where('section_key', '__system__')
            ->first();

        return $template?->system_prompt;
    }

    /**
     * Get the active section prompt override for a given section key (or null to use default).
     */
    public static function getSectionPromptOverride(string $sectionKey): ?string
    {
        $template = static::active()
            ->where('section_key', $sectionKey)
            ->first();

        return $template?->section_prompt;
    }

    /**
     * All available section keys including the special __system__ key.
     */
    public static function allSectionKeys(): array
    {
        return [
            '__system__' => 'Global System Prompt',
            // Dashboard
            'about' => 'About',
            'viability_score' => 'Viability Score',
            'market_size' => 'Market Size',
            'industry_insight' => 'Industry Insight',
            'winning_usp' => 'Winning USP',
            // Strategic Frameworks
            'strategy' => 'Strategy',
            'swot_analysis' => 'SWOT Analysis',
            'pestel_analysis' => 'PESTEL Analysis',
            'porters_five_forces' => "Porter's Five Forces",
            'catwoe' => 'CATWOE',
            'game_changing_idea' => 'Game-Changing Idea',
            // Path to MVP
            'core_features' => 'Core Features',
            'market_validation' => 'Market Validation',
            'timeline_milestones' => 'Timeline & Milestones',
            'marketing_strategy' => 'Marketing Strategy',
            'marketing_slogans' => 'Marketing Slogans',
            'social_media_posts' => 'Social Media Posts',
            'marketing_channels' => 'Marketing Channels',
            'mvp_budget' => 'MVP Budget',
            'mvp_kpis' => 'MVP KPIs',
            // Unique Selling Points
            'usp_acid_test' => 'USP Acid Test',
            'what_makes_you_unique' => 'What Makes You Unique',
            'usp_examples' => 'USP Examples',
            'companies_overview' => 'Companies Overview',
            'ranking_analysis' => 'Ranking Analysis',
            // Customer Persona
            'persona_overview' => 'Persona Overview',
            'persona_challenges' => 'Persona Challenges',
            'persona_goals' => 'Persona Goals',
            'persona_objections' => 'Persona Objections',
            'persona_offerings' => 'Persona Offerings',
            'persona_demographics' => 'Persona Demographics',
            'persona_quotes' => 'Persona Quotes',
            // Finances
            'finance_market_research' => 'Finance Market Research',
            'startup_costs' => 'Startup Costs',
            'revenue_projections' => 'Revenue Projections',
            'operating_expenses' => 'Operating Expenses',
            'breakeven_analysis' => 'Breakeven Analysis',
            'funding_and_risks' => 'Funding & Risks',
            'finance_kpis' => 'Finance KPIs',
            // Go-to-Market Strategy
            'gtm_roadmap' => 'GTM Roadmap',
            'target_market' => 'Target Market',
            'value_proposition' => 'Value Proposition',
            'pricing_strategy' => 'Pricing Strategy',
            'distribution_channels' => 'Distribution Channels',
            'gtm_marketing_plan' => 'GTM Marketing Plan',
            'sales_strategy' => 'Sales Strategy',
            'partnerships' => 'Partnerships',
            'gtm_kpis' => 'GTM KPIs',
            // Competitive Analysis
            'vrio_resources' => 'VRIO Resources',
            'competitor_profiles' => 'Competitor Profiles',
            'competitive_advantage' => 'Competitive Advantage',
        ];
    }
}
