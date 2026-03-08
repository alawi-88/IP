<?php

namespace App\Jobs;

use App\Enums\VentureSectionStatus;
use App\Enums\VentureStatus;
use App\Models\Venture;
use App\Models\VentureSection;
use App\Services\Ai\AiProviderManager;
use App\Services\VentureViabilityScoreService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateVentureSection implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;
    public int $timeout = 180;

    public function __construct(
        private readonly int $ventureId,
        private readonly int $sectionId,
        private readonly ?string $customInstruction = null
    ) {}

    public function handle(AiProviderManager $providerManager): void
    {
        $section = VentureSection::with(['venture', 'tab'])->find($this->sectionId);

        if (!$section) {
            Log::warning('Venture section not found for generation', ['section_id' => $this->sectionId]);
            return;
        }

        $venture = $section->venture;
        if (!$venture) {
            Log::warning('Venture not found for section generation', ['venture_id' => $this->ventureId]);
            return;
        }

        try {
            // Mark as generating
            $section->markAsGenerating();

            // Build context from previously completed sections
            $context = $this->buildContext($venture, $section);

            // Use the provider manager with automatic failover
            $result = $providerManager->resolveWithFallback(
                fn ($provider) => $provider->generateSection(
                    $venture->idea_prompt,
                    $section->section_key->value ?? $section->section_key,
                    $this->customInstruction,
                    $context
                )
            );

            // Mark as completed (this also creates a version and updates parent tab)
            $section->markAsCompleted(
                $result['content'],
                $result['tokens_used'],
                $result['prompt']
            );

            // Check if all sections are done
            $this->checkVentureCompletion($venture);

        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $isNonRetryable = str_contains($errorMessage, 'All AI providers failed')
                || str_contains($errorMessage, 'authentication failed')
                || str_contains($errorMessage, 'credits have been exhausted');

            Log::error('Venture section generation failed', [
                'venture_id' => $this->ventureId,
                'section_id' => $this->sectionId,
                'section_key' => $section->section_key,
                'error' => $errorMessage,
                'attempt' => $this->attempts(),
                'non_retryable' => $isNonRetryable,
            ]);

            // Don't retry for non-retryable errors
            if ($isNonRetryable || $this->attempts() >= $this->tries) {
                $section->markAsFailed($errorMessage);
                $this->checkVentureCompletion($venture);
            } else {
                throw $e; // Let the queue retry
            }
        }
    }

    /**
     * Build context from previously completed sections for better AI output.
     */
    private function buildContext(Venture $venture, VentureSection $currentSection): array
    {
        $completedSections = $venture->sections()
            ->where('status', VentureSectionStatus::Completed)
            ->where('id', '!=', $currentSection->id)
            ->get(['section_key', 'content'])
            ->mapWithKeys(fn ($s) => [
                ($s->section_key->value ?? $s->section_key) => $s->content,
            ])
            ->toArray();

        return [
            'completed_sections' => $completedSections,
            'venture_title' => $venture->title,
        ];
    }

    /**
     * Check if all sections are done and update venture status accordingly.
     */
    private function checkVentureCompletion(Venture $venture): void
    {
        $venture->refresh();
        $totalSections = $venture->sections()->count();
        $completedSections = $venture->sections()->where('status', VentureSectionStatus::Completed)->count();
        $failedSections = $venture->sections()->where('status', VentureSectionStatus::Failed)->count();

        if ($completedSections + $failedSections >= $totalSections) {
            if ($failedSections > 0) {
                if ($completedSections === 0) {
                    $venture->markAsFailed();
                } else {
                    $venture->update(['status' => VentureStatus::PartiallyCompleted]);
                }
            } else {
                $venture->markAsCompleted();
            }

            // Calculate viability score regardless of status
            try {
                app(VentureViabilityScoreService::class)->calculate($venture);
            } catch (\Exception $e) {
                Log::warning('Failed to calculate viability score', [
                    'venture_id' => $venture->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Venture section generation job permanently failed', [
            'venture_id' => $this->ventureId,
            'section_id' => $this->sectionId,
            'error' => $exception->getMessage(),
        ]);

        $section = VentureSection::find($this->sectionId);
        $section?->markAsFailed('Generation failed after all retries: ' . $exception->getMessage());
    }
}
