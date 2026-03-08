<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiVentureAiProvider extends BaseVentureAiProvider
{
    public function __construct(
        string $apiKey,
        string $model = 'gemini-2.0-flash',
        string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta',
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

        $url = "{$this->baseUrl}/models/{$this->model}:generateContent?key={$this->apiKey}";

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->timeout(120)->post($url, [
            'systemInstruction' => [
                'parts' => [['text' => $systemPrompt]],
            ],
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [['text' => $userPrompt]],
                ],
            ],
            'generationConfig' => [
                'maxOutputTokens' => $this->maxTokens,
                'temperature' => 0.7,
                'responseMimeType' => 'application/json',
            ],
        ]);

        if (!$response->successful()) {
            $status = $response->status();
            $body = $response->body();
            $errorType = $this->classifyError($status, $body);

            Log::error('Gemini API error', [
                'status' => $status,
                'body' => $body,
                'section_key' => $sectionKey,
                'error_type' => $errorType,
            ]);

            throw new \RuntimeException($this->getUserFriendlyError($errorType, $status));
        }

        $data = $response->json();

        // Extract text from Gemini response structure
        $textContent = '';
        $candidates = $data['candidates'] ?? [];
        if (!empty($candidates)) {
            $parts = $candidates[0]['content']['parts'] ?? [];
            foreach ($parts as $part) {
                $textContent .= $part['text'] ?? '';
            }
        }

        $parsedContent = $this->parseJsonResponse($textContent, $sectionKey);

        // Gemini token usage
        $usageMetadata = $data['usageMetadata'] ?? [];
        $tokensUsed = ($usageMetadata['promptTokenCount'] ?? 0) + ($usageMetadata['candidatesTokenCount'] ?? 0);

        return [
            'content' => $parsedContent,
            'tokens_used' => $tokensUsed,
            'prompt' => $userPrompt,
        ];
    }

    public function getProviderName(): string
    {
        return 'gemini';
    }
}
