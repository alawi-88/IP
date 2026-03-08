<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClaudeVentureAiProvider extends BaseVentureAiProvider
{
    public function __construct(
        string $apiKey = '',
        string $model = '',
        string $baseUrl = '',
        int $maxTokens = 4096
    ) {
        // Allow backward-compatible construction from config if no params provided
        parent::__construct(
            $apiKey ?: config('venture.ai.api_key', ''),
            $model ?: config('venture.ai.model', 'claude-sonnet-4-20250514'),
            $baseUrl ?: config('venture.ai.base_url', 'https://api.anthropic.com/v1'),
            $maxTokens ?: config('venture.ai.max_tokens', 4096),
        );
    }

    public function generateSection(
        string $ideaPrompt,
        string $sectionKey,
        ?string $customInstruction = null,
        array $context = []
    ): array {
        $promptBuilder = new VenturePromptBuilder();
        $systemPrompt = $promptBuilder->getSystemPrompt();
        $userPrompt = $promptBuilder->buildSectionPrompt($ideaPrompt, $sectionKey, $customInstruction, $context);

        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->timeout(120)->post("{$this->baseUrl}/messages", [
            'model' => $this->model,
            'max_tokens' => $this->maxTokens,
            'system' => $systemPrompt,
            'messages' => [
                ['role' => 'user', 'content' => $userPrompt],
            ],
        ]);

        if (!$response->successful()) {
            $status = $response->status();
            $body = $response->body();
            $errorType = $this->classifyError($status, $body);

            Log::error('Claude API error', [
                'status' => $status,
                'body' => $body,
                'section_key' => $sectionKey,
                'error_type' => $errorType,
            ]);

            throw new \RuntimeException($this->getUserFriendlyError($errorType, $status));
        }

        $data = $response->json();
        $textContent = collect($data['content'] ?? [])
            ->where('type', 'text')
            ->pluck('text')
            ->implode('');

        $parsedContent = $this->parseJsonResponse($textContent, $sectionKey);

        $tokensUsed = ($data['usage']['input_tokens'] ?? 0) + ($data['usage']['output_tokens'] ?? 0);

        return [
            'content' => $parsedContent,
            'tokens_used' => $tokensUsed,
            'prompt' => $userPrompt,
        ];
    }

    public function getProviderName(): string
    {
        return 'anthropic';
    }
}
