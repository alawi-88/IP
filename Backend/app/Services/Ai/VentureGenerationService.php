<?php

namespace App\Services\Ai;

use App\Models\Venture;
use App\Models\VentureSection;
use App\Models\VentureTab;
use App\Models\VentureTabConfig;
use App\Models\VentureSectionConfig;
use App\Jobs\GenerateVentureTabSectionsJob;
use App\Jobs\GenerateVentureSectionJob;

class VentureGenerationService
{
    /**
     * Orchestrate the full venture generation process.
     * Uses tab-level batching: 1 AI call per tab instead of 1 per section.
     * This reduces API calls from ~34 to ~8 per venture.
     */
    public function generate(Venture $venture): void
    {
        // Set venture status to 'generating'
        $venture->update(['status' => 'generating']);

        // Load active, ordered section configs
        $configs = VentureSectionConfig::active()
            ->ordered()
            ->get();

        // Group configs by tab_slug
        $configsByTab = $configs->groupBy('tab_slug');

        // Load tab definitions from database, ordered by sort_order
        $tabDefinitions = VentureTabConfig::visible()
            ->ordered()
            ->get();

        // Iterate tabs in their configured order
        foreach ($tabDefinitions as $tabDef) {
            $tabSlug = $tabDef->tab_slug;
            $tabConfigs = $configsByTab->get($tabSlug);

            if (!$tabConfigs || $tabConfigs->isEmpty()) {
                continue;
            }

            $tabOrder = $tabDef->sort_order;

            // Create or find VentureTab using correct column names
            $tab = VentureTab::firstOrCreate(
                [
                    'venture_id' => $venture->id,
                    'slug' => $tabSlug,
                ],
                [
                    'label_en' => $tabDef->label_en,
                    'label_ar' => $tabDef->label_ar,
                    'icon' => $tabDef->icon,
                    'sort_order' => $tabOrder,
                    'is_visible' => true,
                ]
            );

            // Also update sort_order if tab already exists
            if ($tab->sort_order !== $tabOrder) {
                $tab->update(['sort_order' => $tabOrder]);
            }

            // Create sections for each config in this tab group
            foreach ($tabConfigs as $config) {
                VentureSection::create([
                    'venture_id' => $venture->id,
                    'venture_tab_id' => $tab->id,
                    'slug' => $config->section_slug,
                    'label_en' => $config->label_en,
                    'label_ar' => $config->label_ar,
                    'component_type' => $config->component_type,
                    'sort_order' => $config->sort_order,
                    'is_visible' => $config->is_visible,
                    'status' => 'pending',
                    'generation_attempts' => 0,
                ]);
            }

            // Dispatch ONE batch job per tab (generates all sections in a single AI call)
            // Stagger tabs by 5 seconds each to spread the load across rate limits
            $delay = $tabOrder * 5;

            GenerateVentureTabSectionsJob::dispatch($tab)
                ->delay(now()->addSeconds($delay))
                ->onQueue('venture-generation');
        }
    }

    /**
     * Retry all failed sections for a venture.
     * Uses tab-level batching for efficiency.
     */
    public function retryFailed(Venture $venture): void
    {
        $tabs = $venture->tabs()->with('sections')->get();

        foreach ($tabs as $tab) {
            $failedSections = $tab->sections->filter(fn($s) => $s->status === 'failed');

            if ($failedSections->isEmpty()) {
                continue;
            }

            // Reset failed sections to pending
            foreach ($failedSections as $section) {
                $section->update([
                    'status' => 'pending',
                    'error_message' => null,
                ]);
            }

            // Dispatch a batch job for this tab's failed sections
            GenerateVentureTabSectionsJob::dispatch($tab)
                ->onQueue('venture-generation');
        }
    }

    /**
     * Regenerate a single section (uses individual job for single sections).
     */
    public function regenerateSection(VentureSection $section): void
    {
        $section->update([
            'status' => 'pending',
            'error_message' => null,
        ]);

        GenerateVentureSectionJob::dispatch($section)
            ->onQueue('venture-generation');
    }

    /**
     * Check if venture generation is complete and update status accordingly.
     */
    public function checkCompletion(Venture $venture): void
    {
        $sections = $venture->tabs()
            ->with('sections')
            ->get()
            ->flatMap(fn($tab) => $tab->sections);

        $pendingCount = $sections->filter(
            fn($section) => in_array($section->status, ['pending', 'generating'])
        )->count();

        if ($pendingCount === 0) {
            $failedCount = $sections->filter(
                fn($section) => $section->status === 'failed'
            )->count();

            $status = $failedCount > 0 ? 'failed' : 'completed';
            $venture->update(['status' => $status]);

            // Calculate viability score from dashboard_viability section
            $viabilitySection = $sections->first(
                fn($section) => $section->slug === 'dashboard_viability'
            );

            if ($viabilitySection && $viabilitySection->content) {
                $content = is_array($viabilitySection->content)
                    ? $viabilitySection->content
                    : json_decode($viabilitySection->content, true);

                if (isset($content['score'])) {
                    $venture->update(['viability_score' => $content['score']]);
                }
            }
        }
    }
}
