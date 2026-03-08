<?php

namespace App\Services;

use App\Enums\VentureSectionStatus;
use App\Enums\VentureSectionType;
use App\Enums\VentureStatus;
use App\Enums\VentureTabType;
use App\Jobs\GenerateVentureSection;
use App\Models\Venture;
use App\Models\VentureSection;
use App\Models\VentureTab;
use Illuminate\Support\Facades\DB;

class VentureService
{
    /**
     * List ventures for the authenticated participant.
     */
    public function list(int $participantId, array $filters = [])
    {
        $query = Venture::forParticipant($participantId)
            ->active()
            ->with(['tabs.sections'])
            ->orderByDesc('created_at');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', "%{$filters['search']}%")
                  ->orWhere('idea_prompt', 'like', "%{$filters['search']}%");
            });
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Create a new venture and start AI generation.
     */
    public function create(int $participantId, array $data): Venture
    {
        return DB::transaction(function () use ($participantId, $data) {
            // Create the venture
            $venture = Venture::create([
                'participant_id' => $participantId,
                'title' => $data['title'],
                'idea_prompt' => $data['idea_prompt'],
                'status' => VentureStatus::Generating,
                'generation_started_at' => now(),
            ]);

            // Create tabs and sections based on the enum definitions
            $this->createTabsAndSections($venture);

            // Dispatch generation jobs
            $this->dispatchGenerationJobs($venture);

            return $venture->load('tabs.sections');
        });
    }

    /**
     * Get a single venture with all related data.
     */
    public function show(int $ventureId, int $participantId): Venture
    {
        return Venture::forParticipant($participantId)
            ->with([
                'tabs.sections.versions',
                'competitors',
            ])
            ->findOrFail($ventureId);
    }

    /**
     * Get venture generation status (for polling).
     */
    public function getStatus(int $ventureId, int $participantId): array
    {
        $venture = Venture::forParticipant($participantId)
            ->with(['tabs.sections:id,venture_tab_id,section_key,status,completed_at'])
            ->findOrFail($ventureId);

        $totalSections = $venture->sections()->count();
        $completedSections = $venture->sections()->where('status', VentureSectionStatus::Completed)->count();
        $failedSections = $venture->sections()->where('status', VentureSectionStatus::Failed)->count();

        return [
            'venture_id' => $venture->id,
            'status' => $venture->status,
            'viability_score' => $venture->viability_score,
            'progress' => $totalSections > 0 ? round(($completedSections / $totalSections) * 100) : 0,
            'total_sections' => $totalSections,
            'completed_sections' => $completedSections,
            'failed_sections' => $failedSections,
            'tabs' => $venture->tabs->map(fn ($tab) => [
                'id' => $tab->id,
                'tab_key' => $tab->tab_key,
                'status' => $tab->status,
                'sections' => $tab->sections->map(fn ($s) => [
                    'id' => $s->id,
                    'section_key' => $s->section_key,
                    'status' => $s->status,
                ]),
            ]),
        ];
    }

    /**
     * Regenerate a specific section.
     */
    public function regenerateSection(int $sectionId, int $participantId, ?string $customInstruction = null): VentureSection
    {
        $section = VentureSection::whereHas('venture', function ($q) use ($participantId) {
            $q->where('participant_id', $participantId);
        })->findOrFail($sectionId);

        $section->update([
            'status' => VentureSectionStatus::Queued,
            'error_message' => null,
        ]);

        GenerateVentureSection::dispatch(
            $section->venture_id,
            $section->id,
            $customInstruction
        )->onQueue('venture-generation');

        return $section->fresh();
    }

    /**
     * Regenerate all failed sections for a venture (admin bulk retry).
     */
    public function regenerateAllFailedSections(int $ventureId): array
    {
        $venture = Venture::findOrFail($ventureId);

        $failedSections = VentureSection::where('venture_id', $ventureId)
            ->where('status', VentureSectionStatus::Failed)
            ->get();

        $retryCount = 0;
        foreach ($failedSections as $section) {
            $section->update([
                'status' => VentureSectionStatus::Queued,
                'error_message' => null,
            ]);

            GenerateVentureSection::dispatch(
                $section->venture_id,
                $section->id
            )->onQueue('venture-generation');

            $retryCount++;
        }

        if ($retryCount > 0) {
            $venture->update([
                'status' => VentureStatus::Generating,
            ]);
        }

        return [
            'retried_count' => $retryCount,
            'sections' => $failedSections->pluck('id')->toArray(),
        ];
    }

    /**
     * Update section content (user edit).
     */
    public function updateSectionContent(int $sectionId, int $participantId, array $content): VentureSection
    {
        $section = VentureSection::whereHas('venture', function ($q) use ($participantId) {
            $q->where('participant_id', $participantId);
        })->findOrFail($sectionId);

        // Create version before updating
        $section->createVersion($content, 0, null, null, 'user');

        $section->update(['content' => $content]);

        return $section->fresh();
    }

    /**
     * Archive a venture (soft delete).
     */
    public function archive(int $ventureId, int $participantId): Venture
    {
        $venture = Venture::forParticipant($participantId)->findOrFail($ventureId);
        
        $venture->update([
            'is_archived' => true,
            'archived_at' => now(),
        ]);

        return $venture;
    }

    /**
     * Import venture section into a competition application.
     */
    public function importToApplication(int $ventureId, int $participantId, array $data): void
    {
        $venture = Venture::forParticipant($participantId)->findOrFail($ventureId);

        foreach ($data['sections'] as $sectionImport) {
            $section = VentureSection::where('venture_id', $venture->id)
                ->findOrFail($sectionImport['section_id']);

            $venture->imports()->create([
                'venture_section_id' => $section->id,
                'competition_application_id' => $data['competition_application_id'],
                'target_field_key' => $sectionImport['target_field_key'] ?? null,
                'imported_content' => is_array($section->content) ? json_encode($section->content) : $section->content,
                'imported_at' => now(),
            ]);
        }
    }

    /**
     * Create all tabs and sections for a venture.
     */
    protected function createTabsAndSections(Venture $venture): void
    {
        foreach (VentureTabType::defaultTabs() as $tabType) {
            $tab = VentureTab::create([
                'venture_id' => $venture->id,
                'tab_key' => $tabType->value,
                'display_name' => $tabType->displayName(),
                'sort_order' => $tabType->sortOrder(),
                'status' => VentureSectionStatus::Queued->value,
            ]);

            $sortOrder = 1;
            foreach ($tabType->sections() as $sectionType) {
                VentureSection::create([
                    'venture_tab_id' => $tab->id,
                    'venture_id' => $venture->id,
                    'section_key' => $sectionType->value,
                    'display_name' => $sectionType->displayName(),
                    'sort_order' => $sortOrder++,
                    'status' => VentureSectionStatus::Queued->value,
                    'content_schema' => $sectionType->contentSchema(),
                ]);
            }
        }
    }

    /**
     * Dispatch generation jobs with priority.
     * Phase 1 (HIGH): Dashboard + Strategic Frameworks
     * Phase 2 (DEFAULT): Remaining 6 tabs
     */
    protected function dispatchGenerationJobs(Venture $venture): void
    {
        $highPriorityTabs = [
            VentureTabType::Dashboard->value,
            VentureTabType::StrategicFrameworks->value,
        ];

        $sections = $venture->sections()->with('tab')->get();

        foreach ($sections as $section) {
            $isHighPriority = in_array($section->tab->tab_key->value ?? $section->tab->tab_key, $highPriorityTabs);

            $job = GenerateVentureSection::dispatch(
                $venture->id,
                $section->id
            )->onQueue('venture-generation');

            if ($isHighPriority) {
                // High priority sections first
                $job->afterCommit();
            } else {
                // Lower priority — delayed by 5 seconds to let Phase 1 start first
                GenerateVentureSection::dispatch(
                    $venture->id,
                    $section->id
                )->onQueue('venture-generation')->delay(now()->addSeconds(5));
            }
        }
    }
}
