<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiVentureAiProvider extends BaseVentureAiProvider
{
    public function __construct(
        string $apiKey,
        string $model = 'gpt-4o',
        string $baseUrl = 'https://api.openai.com/v1',
        int $maxTokens = 4096
    ) {
        parent::__construct($apiKey, $model, $baseUrl, $maxTokens);
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
            'Authorization' => "Bearer {$this->apiKey}",
            'Content-Type' => 'application/json',
        ])->timeout(120)->post("{$this->baseUrl}/chat/completions", [
            'model' => $this->model,
            'max_tokens' => $this->maxTokens,
            'temperature' => 0.7,
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
        ]);

        if (!$response->successful()) {
            $status = $response->status();
            $body = $response->body();
            $errorType = $this->classifyError($status, $body);

            Log::error('OpenAI API error', [
                'status' => $status,
                'body' => $body,
                'section_key' => $sectionKey,
                'error_type' => $errorType,
            ]);

            throw new \RuntimeException($this->getUserFriendlyError($errorType, $status));
        }

        $data = $response->json();
        $textContent = $data['choices'][0]['message']['content'] ?? '';

        $parsedContent = $this->parseJsonResponse($textContent, $sectionKey);

        $tokensUsed = ($data['usage']['prompt_tokens'] ?? 0) + ($data['usage']['completion_tokens'] ?? 0);

        return [
            'content' => $parsedContent,
            'tokens_used' => $tokensUsed,
            'prompt' => $userPrompt,
        ];
    }

    public function getProviderName(): string
    {
        return 'openai';
    }
}
