<?php

namespace App\Services\Ai;

use App\Contracts\VentureAiProviderInterface;
use App\Models\AiProvider;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiVentureAiProvider implements VentureAiProviderInterface
{
    protected AiProvider $provider;

    public function __construct(AiProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * Generate a section using Google Gemini API.
     *
     * @param string $prompt
     * @param array $options
     * @return array
     * @throws RuntimeException
     */
    public function generateSection(string $prompt, array $options = []): array
    {
        if (!$this->isAvailable()) {
            throw new RuntimeException('Gemini API key is not configured');
        }

        $maxTokens = $options['max_tokens'] ?? $this->provider->max_tokens ?? 2048;
        $temperature = $options['temperature'] ?? $this->provider->temperature ?? 0.7;
        $modelName = $this->provider->model_name ?? 'gemini-pro';
        $apiKey = $this->provider->api_key ?: config('services.gemini.api_key');

        try {
            $response = Http::timeout(60)
            ->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$modelName}:generateContent?key={$apiKey}",
                [
                    'contents' => [
                        [
                            'parts' => [
                                [
                                    'text' => $prompt,
                                ],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'maxOutputTokens' => $maxTokens,
                        'temperature' => $temperature,
                    ],
                ]
            );

            if (!$response->successful()) {
                throw new RuntimeException(
                    "Gemini API error: {$response->status()} - {$response->body()}"
                );
            }

            $data = $response->json();

            if (empty($data['candidates']) || empty($data['candidates'][0]['content']['parts'])) {
                throw new RuntimeException('Invalid response format from Gemini API');
            }

            $content = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

            if (empty($content)) {
                throw new RuntimeException('Empty response from Gemini API');
            }

            // Strip markdown code fences if present (e.g., ```json\n...\n```)
            $content = trim($content);
            if (preg_match('/^```(?:json)?\s*\n?(.*?)\n?\s*```$/s', $content, $matches)) {
                $content = trim($matches[1]);
            }

            $parsed = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new RuntimeException('Failed to parse JSON from Gemini response: ' . json_last_error_msg() . ' | Raw: ' . substr($content, 0, 200));
            }

            $promptTokens = $data['usageMetadata']['promptTokenCount'] ?? 0;
            $completionTokens = $data['usageMetadata']['candidatesTokenCount'] ?? 0;

            return [
                'content' => $parsed,
                'prompt_tokens' => $promptTokens,
                'completion_tokens' => $completionTokens,
            ];
        } catch (\Exception $e) {
            throw new RuntimeException('Gemini API request failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get provider name.
     *
     * @return string
     */
    public function getProviderName(): string
    {
        return 'Google Gemini';
    }

    /**
     * Check if provider is available.
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        return !empty($this->provider->api_key) || !empty(config('services.gemini.api_key'));
    }
}
