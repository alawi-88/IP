<?php

namespace App\Jobs;

use App\Models\VentureSection;
use App\Models\VentureVersion;
use App\Services\Ai\AiProviderManager;
use App\Services\Ai\VenturePromptBuilder;
use App\Services\Ai\VentureGenerationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class GenerateVentureSectionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public VentureSection $section;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 1;

    /**
     * Create a new job instance.
     */
    public function __construct(VentureSection $section)
    {
        $this->section = $section;
        $this->onQueue('venture-generation');
    }

    /**
     * Execute the job.
     */
    public function handle(AiProviderManager $manager, VenturePromptBuilder $promptBuilder): void
    {
        // Set section status to 'generating'
        $this->section->update(['status' => 'generating']);

        // Get venture from section->tab->venture
        $venture = $this->section->tab->venture;

        try {
            // Build prompt
            $promptData = $promptBuilder->buildPrompt($venture, $this->section->slug);

            // Call AI provider to generate content
            $result = $manager->generate($promptData);

            // Parse result
            $content = $result['content'] ?? null;
            $contentAr = $result['content_ar'] ?? null;
            $aiProviderId = $result['ai_provider_id'] ?? null;
            $tokensUsed = ($result['prompt_tokens'] ?? 0) + ($result['completion_tokens'] ?? 0);
            $estimatedCost = $result['estimated_cost'] ?? null;

            // Update section with generated content
            $this->section->update([
                'status' => 'completed',
                'content' => $content,
                'content_ar' => $contentAr,
                'ai_provider_id' => $aiProviderId,
                'tokens_used' => $tokensUsed,
                'estimated_cost' => $estimatedCost,
                'generated_at' => now(),
            ]);

            // Create VentureVersion record
            VentureVersion::create([
                'venture_section_id' => $this->section->id,
                'content' => $content,
                'content_ar' => $contentAr,
                'change_note' => 'AI generated',
            ]);

            // Check if venture generation is complete
            $generationService = app(VentureGenerationService::class);
            $generationService->checkCompletion($venture);
        } catch (Throwable $e) {
            // Let the failed method handle the error
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $e): void
    {
        // Set section status to 'failed'
        $this->section->update([
            'status' => 'failed',
            'error_message' => $e->getMessage(),
        ]);

        // Increment generation attempts
        $this->section->increment('generation_attempts');

        // Check completion status
        $venture = $this->section->tab->venture;
        $generationService = app(VentureGenerationService::class);
        $generationService->checkCompletion($venture);
    }
}
