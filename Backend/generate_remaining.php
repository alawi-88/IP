<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\VentureSection;
use App\Services\Ai\AiProviderManager;
use App\Services\Ai\VenturePromptBuilder;
use App\Models\VentureVersion;

// Rotate through models to spread quota
$models = ['gemini-2.5-flash', 'gemini-2.0-flash', 'gemini-2.5-flash-lite', 'gemini-2.0-flash-lite'];
$modelIndex = 0;

$sections = VentureSection::whereHas('tab', fn($q) => $q->where('venture_id', 5))
    ->where('status', 'pending')
    ->get();

echo "Processing " . count($sections) . " sections with model rotation...\n";

$completed = 0;
$failed = 0;

foreach ($sections as $i => $section) {
    $section->refresh();
    if ($section->status === 'completed') {
        echo "SKIP {$section->slug} (already completed)\n";
        continue;
    }

    // Rotate model for each request
    $currentModel = $models[$modelIndex % count($models)];
    $modelIndex++;

    // Update the AI provider model
    \DB::table('ai_providers')->where('id', 3)->update(['model_name' => $currentModel]);

    echo "[" . ($i+1) . "/" . count($sections) . "] GENERATING {$section->slug} (model: {$currentModel})... ";
    $section->update(['status' => 'generating']);

    $maxRetries = 3;
    $success = false;

    for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
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
            $success = true;
            echo "OK (" . ($promptTokens + $completionTokens) . " tokens)\n";
            break;
        } catch (\Throwable $e) {
            if (str_contains($e->getMessage(), '429') && $attempt < $maxRetries) {
                echo "RATE LIMITED, waiting 65s (attempt {$attempt}/{$maxRetries})... ";
                sleep(65);
                // Try next model on retry
                $currentModel = $models[$modelIndex % count($models)];
                $modelIndex++;
                \DB::table('ai_providers')->where('id', 3)->update(['model_name' => $currentModel]);
                continue;
            }
            $section->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            $section->increment('generation_attempts');
            $failed++;
            echo "FAIL: " . substr($e->getMessage(), 0, 80) . "\n";
            break;
        }
    }

    // Cooldown between requests
    echo "Cooling down 10s...\n";
    sleep(10);
}

// Reset model back
\DB::table('ai_providers')->where('id', 3)->update(['model_name' => 'gemini-2.5-flash-lite']);

// Check completion
$generationService = new \App\Services\Ai\VentureGenerationService();
$generationService->checkCompletion(\App\Models\Venture::find(5));

echo "\nDone! Completed: {$completed}, Failed: {$failed}\n";
