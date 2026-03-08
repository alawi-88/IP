<?php

namespace App\Services;

use App\Models\VaPage;
use App\Models\VaSection;
use App\Models\Startup;

class CompletionCalculatorService
{
    /**
     * Calculate page completion based on filled fields
     * This is a basic implementation - can be extended based on field requirements
     */
    public function calculatePageCompletion(VaPage $vaPage): float
    {
        $content = $vaPage->content ?? [];

        if (empty($content)) {
            return 0;
        }

        // Count non-empty fields
        $totalFields = count($content);
        $filledFields = 0;

        foreach ($content as $value) {
            if (!empty($value)) {
                $filledFields++;
            }
        }

        $completion = $totalFields > 0 ? ($filledFields / $totalFields) * 100 : 0;
        return round($completion, 2);
    }

    /**
     * Calculate section completion as average of page completions
     */
    public function calculateSectionCompletion(VaSection $vaSection): float
    {
        $pages = $vaSection->vaPages()->get();

        if ($pages->isEmpty()) {
            return 0;
        }

        $totalCompletion = 0;
        foreach ($pages as $page) {
            $totalCompletion += $this->calculatePageCompletion($page);
        }

        $completion = $totalCompletion / count($pages);
        return round($completion, 2);
    }

    /**
     * Calculate startup completion as average of section completions
     */
    public function calculateStartupCompletion(Startup $startup): float
    {
        $sections = $startup->vaSections()->get();

        if ($sections->isEmpty()) {
            return 0;
        }

        $totalCompletion = 0;
        foreach ($sections as $section) {
            $totalCompletion += $this->calculateSectionCompletion($section);
        }

        $completion = $totalCompletion / count($sections);
        return round($completion, 2);
    }

    /**
     * Recalculate all completion percentages for a startup
     */
    public function recalculateAllForStartup(Startup $startup): void
    {
        $sections = $startup->vaSections()->get();

        foreach ($sections as $section) {
            $pages = $section->vaPages()->get();
            foreach ($pages as $page) {
                $completion = $this->calculatePageCompletion($page);
                $page->update(['completion_percentage' => $completion]);
            }

            $sectionCompletion = $this->calculateSectionCompletion($section);
            $section->update(['completion_percentage' => $sectionCompletion]);
        }

        $startupCompletion = $this->calculateStartupCompletion($startup);
        $startup->update(['completion_percentage' => $startupCompletion]);
    }
}
