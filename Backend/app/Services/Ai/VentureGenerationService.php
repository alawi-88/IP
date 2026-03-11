<?php

namespace App\Services\Ai;

use App\Models\Venture;
use App\Models\VentureSection;
use App\Models\VentureTab;
use App\Models\VentureSectionConfig;
use App\Jobs\GenerateVentureSectionJob;

class VentureGenerationService
{
    /**
     * Orchestrate the full venture generation process.
     */
    public function generate(Venture $venture): void
    {
        // Set venture status to 'generating'
        $venture->update(['status' => 'generating']);

        // Load active, ordered section configs
        $configs = VentureSectionConfig::where('is_visible', true)
            ->orderBy('sort_order')
            ->get();

        // Group configs by tab_slug
        $configsByTab = $configs->groupBy('tab_slug');

        // Track tab order for delay calculation
        $tabOrder = 0;

        foreach ($configsByTab as $tabSlug => $tabConfigs) {
            // Create or find VentureTab
            $tab = VentureTab::firstOrCreate(
                [
                    'venture_id' => $venture->id,
                    'slug' => $tabSlug,
                ],
                [
                    'label_en' => $tabConfigs->first()->label_en ?? ucwords(str_replace('_', ' ', $tabSlug)),
                    'sort_order' => $tabOrder,
                    'is_visible' => true,
                ]
            );

            // Calculate base delay for this tab (5 seconds per tab order)
            $baseDelay = $tabOrder * 5;

            // Create sections for each config in this tab group
            $sectionOrder = 0;
            foreach ($tabConfigs as $config) {
                // Create VentureSection with pending status
                $section = VentureSection::create([
                    'venture_id' => $venture->id,
                    'venture_tab_id' => $tab->id,
                    'slug' => $config->section_slug,
                    'label_en' => $config->label_en,
                    'status' => 'pending',
                    'sort_order' => $sectionOrder,
                    'is_visible' => true,
                    'component_type' => $config->component_type,
                    'generation_attempts' => 0,
                ]);

                // Calculate delay: base tab delay + section stagger (2 seconds per section within tab)
                $delay = $baseDelay + ($sectionOrder * 2);

                // Dispatch the job with delay
                GenerateVentureSectionJob::dispatch($section)
                    ->delay(now()->addSeconds($delay))
                    ->onQueue('venture-generation');

                $sectionOrder++;
            }

            $tabOrder++;
        }
    }

    /**
     * Retry all failed sections for a venture.
     */
    public function retryFailed(Venture $venture): void
    {
        // Find all failed sections
        $failedSections = $venture->tabs()
            ->with('sections')
            ->get()
            ->flatMap(fn($tab) => $tab->sections)
            ->filter(fn($section) => $section->status === 'failed');

        foreach ($failedSections as $section) {
            // Reset to pending
            $section->update([
                'status' => 'pending',
                'error_message' => null,
            ]);

            // Dispatch job
            GenerateVentureSectionJob::dispatch($section)
                ->onQueue('venture-generation');
        }
    }

    /**
     * Regenerate a single section.
     */
    public function regenerateSection(VentureSection $section): void
    {
        // Reset section to pending
        $section->update([
            'status' => 'pending',
            'error_message' => null,
        ]);

        // Dispatch single job
        GenerateVentureSectionJob::dispatch($section)
            ->onQueue('venture-generation');
    }

    /**
     * Check if venture generation is complete and update status accordingly.
     */
    public function checkCompletion(Venture $venture): void
    {
        // Load all sections
        $sections = $venture->tabs()
            ->with('sections')
            ->get()
            ->flatMap(fn($tab) => $tab->sections);

        // Check if any sections are still pending or generating
        $pendingCount = $sections->filter(
            fn($section) => in_array($section->status, ['pending', 'generating'])
        )->count();

        if ($pendingCount === 0) {
            // All sections are complete or failed
            $failedCount = $sections->filter(
                fn($section) => $section->status === 'failed'
            )->count();

            $status = $failedCount > 0 ? 'failed' : 'completed';
            $venture->update(['status' => $status]);

            // Calculate viability score from dashboard_viability_score section if available
            $viabilitySection = $sections->first(
                fn($section) => $section->slug === 'dashboard_viability_score'
            );

            if ($viabilitySection && $viabilitySection->content) {
                $content = json_decode($viabilitySection->content, true);
                if (isset($content['score'])) {
                    $venture->update(['viability_score' => $content['score']]);
                }
            }
        }
    }
}
