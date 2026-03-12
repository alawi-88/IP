<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\VentureSection;
use App\Services\Ai\AiProviderManager;
use App\Services\Ai\VenturePromptBuilder;
use App\Services\Ai\VentureGenerationService;
use App\Models\VentureVersion;

$sections = VentureSection::whereHas('tab', fn($q) => $q->where('venture_id', 5))
    ->where('status', 'pending')
    ->get();

echo "Processing " . count($sections) . " sections one at a time...\n";

$completed = 0;
$failed = 0;

foreach ($sections as $section) {
    // Refresh to check current status
    $section->refresh();
    if ($section->status === 'completed') {
        echo "SKIP {$section->slug} (already completed)\n";
        continue;
    }

    echo "GENERATING {$section->slug}... ";
    $section->update(['status' => 'generating']);

    try {
        $venture = $section->tab->venture;
        $promptBuilder = new VenturePromptBuilder();
        $promptData = $promptBuilder->buildPrompt($venture, $section->slug);
        $prompt = $promptData['system_prompt'] . "\n\n" . $promptData['user_prompt'];

        $manager = new AiProviderManager();
        $result = $manager->generate($prompt, [
            'max_tokens' => $promptData['max_tokens'] ?? 4096,
            'temperature' => $promptData['temperature'] ?? 0.7,
        ]);

        $content = $result['content'] ?? null;
        $aiProviderId = $result['provider_id'] ?? null;
        $promptTokens = $result['prompt_tokens'] ?? 0;
        $completionTokens = $result['completion_tokens'] ?? 0;

        $section->update([
            'status' => 'completed',
            'content' => $content,
            'ai_provider_id' => $aiProviderId,
            'tokens_used' => $promptTokens + $completionTokens,
            'generated_at' => now(),
        ]);

        $latestVersion = VentureVersion::where('venture_section_id', $section->id)->max('version_number') ?? 0;
        VentureVersion::create([
            'venture_section_id' => $section->id,
            'content' => $content,
            'version_number' => $latestVersion + 1,
            'change_note' => 'AI generated',
        ]);

        $completed++;
        echo "OK (" . ($promptTokens + $completionTokens) . " tokens)\n";

    } catch (\Throwable $e) {
        $section->update([
            'status' => 'failed',
            'error_message' => $e->getMessage(),
        ]);
        $section->increment('generation_attempts');
        $failed++;
        echo "FAIL: " . substr($e->getMessage(), 0, 80) . "\n";
    }

    // Cooldown between requests
    echo "Cooling down 5s...\n";
    sleep(20);
}

// Check completion
$generationService = new VentureGenerationService();
$generationService->checkCompletion(\App\Models\Venture::find(5));

echo "\nDone! Completed: {$completed}, Failed: {$failed}\n";
