<?php

namespace App\Services;

use App\Models\Venture;
use Illuminate\Support\Facades\Log;

class VentureViabilityScoreService
{
    private array $weights;

    public function __construct()
    {
        $this->weights = config('venture.viability_weights', [
            'swot_analysis' => 0.20,
            'market_size' => 0.20,
            'pestel_analysis' => 0.15,
            'porters_five_forces' => 0.15,
            'finance_market_research' => 0.15,
            'vrio_resources' => 0.15,
        ]);
    }

    /**
     * Calculate and save the viability score for a venture.
     */
    public function calculate(Venture $venture): float
    {
        $sections = $venture->sections()
            ->whereIn('section_key', array_keys($this->weights))
            ->where('status', 'completed')
            ->get();

        $totalScore = 0;
        $totalWeight = 0;

        foreach ($sections as $section) {
            $sectionKey = $section->section_key->value ?? $section->section_key;
            $weight = $this->weights[$sectionKey] ?? 0;

            if ($weight <= 0) continue;

            $sectionScore = $this->extractSectionScore($section->content, $sectionKey);

            if ($sectionScore !== null) {
                $totalScore += $sectionScore * $weight;
                $totalWeight += $weight;
            }
        }

        // Normalize to 0-100 scale
        // Score = weighted average of completed sections, scaled by coverage
        $maxWeight = array_sum($this->weights);
        $weightedAverage = $totalWeight > 0 ? ($totalScore / $totalWeight) : 0;
        $coveragePenalty = $totalWeight / $maxWeight; // penalize for missing sections
        $viabilityScore = round($weightedAverage * $coveragePenalty, 2);

        $venture->update(['viability_score' => min(100, max(0, $viabilityScore))]);

        Log::info('Viability score calculated', [
            'venture_id' => $venture->id,
            'score' => $viabilityScore,
        ]);

        return $viabilityScore;
    }

    /**
     * Extract a numerical score (0-100) from section content.
     * The AI is instructed to include a `_score` field in each relevant section.
     */
    private function extractSectionScore(?array $content, string $sectionKey): ?float
    {
        if (!$content) return null;

        // Look for explicit score field
        if (isset($content['_score'])) {
            return (float) $content['_score'];
        }

        // Look for viability_score field
        if (isset($content['viability_score'])) {
            return (float) $content['viability_score'];
        }

        // Look for score in nested data
        if (isset($content['score'])) {
            return (float) $content['score'];
        }

        // Default: try to infer from content structure
        return $this->inferScore($content, $sectionKey);
    }

    /**
     * Infer a score based on content completeness and quality indicators.
     */
    private function inferScore(array $content, string $sectionKey): float
    {
        // Base score on content completeness (how many fields are filled)
        $totalFields = count($content);
        $filledFields = collect($content)->filter(fn ($v) => !empty($v) && $v !== null)->count();

        if ($totalFields === 0) return 0;

        // Completeness ratio * 100
        return round(($filledFields / $totalFields) * 100, 2);
    }
}
