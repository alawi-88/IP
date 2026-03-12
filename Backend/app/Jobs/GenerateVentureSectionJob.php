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
    public function handle(AiProviderManager $manager): void
    {
        // Refresh from DB and skip if already completed or currently generating
        $this->section->refresh();

        if (in_array($this->section->status, ['completed', 'generating'])) {
            return;
        }

        // Set section status to 'generating'
        $this->section->update(['status' => 'generating']);

        // Get venture from section->tab->venture
        $venture = $this->section->tab->venture;

        try {
            // Build prompt
            $promptBuilder = new VenturePromptBuilder();
            $promptData = $promptBuilder->buildPrompt($venture, $this->section->slug);

            // Combine system and user prompts into a single string for providers
            $prompt = $promptData['system_prompt'] . "\n\n" . $promptData['user_prompt'];

            // Store the prompt that was sent
            $this->section->update([
                'prompt_sent' => $prompt,
            ]);

            // Call AI provider to generate content with prompt options
            $result = $manager->generate($prompt, [
                'max_tokens' => $promptData['max_tokens'] ?? 4096,
                'temperature' => $promptData['temperature'] ?? 0.7,
            ]);

            // Parse result
            $content = $result['content'] ?? null;
            $contentAr = $result['content_ar'] ?? null;
            $aiProviderId = $result['provider_id'] ?? $result['ai_provider_id'] ?? null;
            $promptTokens = $result['prompt_tokens'] ?? null;
            $completionTokens = $result['completion_tokens'] ?? null;

            // Build raw response string for admin inspection
            $rawResponse = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            // Update section with generated content
            $this->section->update([
                'status' => 'completed',
                'content' => $content,
                'content_ar' => $contentAr,
                'raw_response' => $rawResponse,
                'ai_provider_id' => $aiProviderId,
                'tokens_used' => ($promptTokens ?? 0) + ($completionTokens ?? 0),
                'generated_at' => now(),
            ]);

            // Create VentureVersion record
            $latestVersion = VentureVersion::where('venture_section_id', $this->section->id)
                ->max('version_number') ?? 0;

            VentureVersion::create([
                'venture_section_id' => $this->section->id,
                'content' => $content,
                'content_ar' => $contentAr,
                'version_number' => $latestVersion + 1,
                'change_note' => 'AI generated via ' . ($aiProviderId ? "provider #{$aiProviderId}" : 'unknown'),
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
