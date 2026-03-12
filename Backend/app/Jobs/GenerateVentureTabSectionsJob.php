<?php

namespace App\Jobs;

use App\Models\VentureSection;
use App\Models\VentureTab;
use App\Models\VentureVersion;
use App\Services\Ai\AiProviderManager;
use App\Services\Ai\VenturePromptBuilder;
use App\Services\Ai\VentureGenerationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateVentureTabSectionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public VentureTab $tab;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 1;

    /**
     * Create a new job instance.
     */
    public function __construct(VentureTab $tab)
    {
        $this->tab = $tab;
        $this->onQueue('venture-generation');
    }

    /**
     * Execute the job — generate ALL sections for this tab in a single AI call.
     */
    public function handle(AiProviderManager $manager): void
    {
        $this->tab->refresh();
        $venture = $this->tab->venture;

        // Get all pending sections for this tab
        $sections = $this->tab->sections()
            ->whereIn('status', ['pending'])
            ->orderBy('sort_order')
            ->get();

        if ($sections->isEmpty()) {
            return;
        }

        // Mark all sections as 'generating'
        $sections->each(fn($s) => $s->update(['status' => 'generating']));

        try {
            // Build a combined batch prompt for all sections in this tab
            $promptBuilder = new VenturePromptBuilder();
            $batchPromptData = $this->buildBatchPrompt($promptBuilder, $venture, $sections);

            $prompt = $batchPromptData['system_prompt'] . "\n\n" . $batchPromptData['user_prompt'];

            // Store the batch prompt on each section
            $sections->each(fn($s) => $s->update(['prompt_sent' => $prompt]));

            // Call AI provider — one request for the entire tab
            $result = $manager->generate($prompt, [
                'max_tokens' => $batchPromptData['max_tokens'],
                'temperature' => $batchPromptData['temperature'],
            ]);

            $batchContent = $result['content'] ?? [];
            $aiProviderId = $result['provider_id'] ?? null;
            $promptTokens = $result['prompt_tokens'] ?? 0;
            $completionTokens = $result['completion_tokens'] ?? 0;
            $rawResponse = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            // Distribute the batched response to individual sections
            $tokensPerSection = intval(($promptTokens + $completionTokens) / max($sections->count(), 1));

            foreach ($sections as $section) {
                $sectionContent = $batchContent[$section->slug] ?? null;

                if ($sectionContent !== null) {
                    // Build section-specific raw response
                    $sectionRaw = json_encode([
                        'batch_mode' => true,
                        'tab_slug' => $this->tab->slug,
                        'section_slug' => $section->slug,
                        'provider_id' => $aiProviderId,
                        'provider_name' => $result['provider_name'] ?? 'unknown',
                        'model_name' => $result['model_name'] ?? 'unknown',
                        'content' => $sectionContent,
                        'total_prompt_tokens' => $promptTokens,
                        'total_completion_tokens' => $completionTokens,
                    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                    $section->update([
                        'prompt_sent' => $prompt,
                        'status' => 'completed',
                        'content' => $sectionContent,
                        'raw_response' => $sectionRaw,
                        'ai_provider_id' => $aiProviderId,
                        'tokens_used' => $tokensPerSection,
                        'generated_at' => now(),
                    ]);

                    // Create version record
                    $latestVersion = VentureVersion::where('venture_section_id', $section->id)
                        ->max('version_number') ?? 0;
                    VentureVersion::create([
                        'venture_section_id' => $section->id,
                        'content' => $sectionContent,
                        'version_number' => $latestVersion + 1,
                        'change_note' => 'AI batch generated (' . ($result['model_name'] ?? 'unknown') . ')',
                    ]);
                } else {
                    // Section not found in batch response — mark failed
                    Log::warning("Batch generation missing section [{$section->slug}] for tab [{$this->tab->slug}]");
                    $section->update([
                        'prompt_sent' => $prompt,
                        'status' => 'failed',
                        'error_message' => "Section not found in batch AI response for tab {$this->tab->slug}",
                        'raw_response' => $rawResponse,
                    ]);
                    $section->increment('generation_attempts');
                }
            }

            Log::info("Batch generation completed for tab [{$this->tab->slug}]: {$sections->count()} sections in 1 API call");

            // Check if venture generation is complete
            $generationService = app(VentureGenerationService::class);
            $generationService->checkCompletion($venture);

        } catch (Throwable $e) {
            // Mark all generating sections as failed
            foreach ($sections as $section) {
                if ($section->status === 'generating') {
                    $section->update([
                        'prompt_sent' => $prompt,
                        'status' => 'failed',
                        'error_message' => $e->getMessage(),
                    ]);
                    $section->increment('generation_attempts');
                }
            }

            // Check completion
            $generationService = app(VentureGenerationService::class);
            $generationService->checkCompletion($venture);

            throw $e;
        }
    }

    /**
     * Build a combined prompt that generates all sections for this tab in one call.
     */
    protected function buildBatchPrompt(VenturePromptBuilder $builder, $venture, $sections): array
    {
        $sectionPrompts = [];
        $maxTokensTotal = 0;

        foreach ($sections as $section) {
            $singlePrompt = $builder->buildPrompt($venture, $section->slug);
            $sectionPrompts[$section->slug] = $singlePrompt;
            $maxTokensTotal += ($singlePrompt['max_tokens'] ?? 4096);
        }

        // Cap max_tokens at a reasonable limit for the model
        $maxTokensTotal = min($maxTokensTotal, 16384);

        // Build combined system prompt
        $systemPrompt = "You are an expert startup advisor and business analyst. You will generate content for multiple sections at once. Respond with valid JSON only. No markdown, no explanation, no code fences. Your response must be a single JSON object where each key is a section identifier and each value is that section's content object.";

        // Build combined user prompt with venture context (shared) + per-section instructions
        $firstPrompt = reset($sectionPrompts);
        $ventureContext = $this->extractVentureContext($firstPrompt['user_prompt']);

        $userPrompt = "Generate content for ALL of the following sections about this venture.\n\n";
        $userPrompt .= "=== VENTURE CONTEXT ===\n{$ventureContext}\n\n";
        $userPrompt .= "=== SECTIONS TO GENERATE ===\n\n";

        foreach ($sectionPrompts as $slug => $promptData) {
            $sectionInstruction = $this->extractSectionInstruction($promptData['user_prompt']);
            $userPrompt .= "## Section: {$slug}\n{$sectionInstruction}\n\n";
        }

        $userPrompt .= "=== RESPONSE FORMAT ===\n";
        $userPrompt .= "Return a SINGLE JSON object. Each key is the section slug, each value is the section's content matching its schema above. Example structure:\n";
        $userPrompt .= "{\n";
        $slugs = array_keys($sectionPrompts);
        foreach ($slugs as $i => $slug) {
            $userPrompt .= "  \"{$slug}\": { ... section content ... }";
            $userPrompt .= ($i < count($slugs) - 1) ? ",\n" : "\n";
        }
        $userPrompt .= "}\n";
        $userPrompt .= "IMPORTANT: Generate ALL sections. Each section's value must match its specified JSON structure exactly.";

        return [
            'system_prompt' => $systemPrompt,
            'user_prompt' => $userPrompt,
            'max_tokens' => $maxTokensTotal,
            'temperature' => 0.7,
        ];
    }

    /**
     * Extract the venture context from a single section's user prompt.
     */
    protected function extractVentureContext(string $userPrompt): string
    {
        if (preg_match('/(Venture:.*?Business Model:.*?)(?:Return JSON|$)/s', $userPrompt, $matches)) {
            return trim($matches[1]);
        }
        $parts = preg_split('/Return JSON/i', $userPrompt, 2);
        return trim($parts[0] ?? $userPrompt);
    }

    /**
     * Extract the section-specific instruction from a user prompt.
     */
    protected function extractSectionInstruction(string $userPrompt): string
    {
        $lines = explode("\n", $userPrompt);
        $instruction = $lines[0] ?? '';

        if (preg_match('/(Return JSON.*)/s', $userPrompt, $matches)) {
            return $instruction . "\n" . trim($matches[1]);
        }

        return $instruction;
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $e): void
    {
        $sections = $this->tab->sections()
            ->where('status', 'generating')
            ->get();

        foreach ($sections as $section) {
            $section->update([
                'status' => 'failed',
                'error_message' => 'Tab batch generation failed: ' . $e->getMessage(),
            ]);
            $section->increment('generation_attempts');
        }

        $venture = $this->tab->venture;
        $generationService = app(VentureGenerationService::class);
        $generationService->checkCompletion($venture);
    }
}
