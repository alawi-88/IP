<?php

namespace App\Services\Ai;

use App\Contracts\VentureAiProviderInterface;
use App\Models\AiProvider;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiVentureAiProvider implements VentureAiProviderInterface
{
    protected AiProvider $provider;

    public function __construct(AiProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * Generate a section using OpenAI API.
     *
     * @param string $prompt
     * @param array $options
     * @return array
     * @throws RuntimeException
     */
    public function generateSection(string $prompt, array $options = []): array
    {
        if (!$this->isAvailable()) {
            throw new RuntimeException('OpenAI API key is not configured');
        }

        $maxTokens = $options['max_tokens'] ?? $this->provider->max_tokens ?? 2048;
        $temperature = $options['temperature'] ?? $this->provider->temperature ?? 0.7;

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.openai.api_key'),
            ])
            ->timeout(60)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $this->provider->model_name ?? 'gpt-4-turbo',
                'max_tokens' => $maxTokens,
                'temperature' => $temperature,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an expert startup advisor. Always respond with valid JSON.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
            ]);

            if (!$response->successful()) {
                throw new RuntimeException(
                    "OpenAI API error: {$response->status()} - {$response->body()}"
                );
            }

            $data = $response->json();

            if (empty($data['choices']) || empty($data['choices'][0]['message']['content'])) {
                throw new RuntimeException('Invalid response format from OpenAI API');
            }

            $content = $data['choices'][0]['message']['content'];
            $parsed = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new RuntimeException('Failed to parse JSON from OpenAI response: ' . json_last_error_msg());
            }

            return [
                'content' => $parsed,
                'prompt_tokens' => $data['usage']['prompt_tokens'] ?? 0,
                'completion_tokens' => $data['usage']['completion_tokens'] ?? 0,
            ];
        } catch (\Exception $e) {
            throw new RuntimeException('OpenAI API request failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get provider name.
     *
     * @return string
     */
    public function getProviderName(): string
    {
        return 'OpenAI';
    }

    /**
     * Check if provider is available.
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        return !empty(config('services.openai.api_key'));
    }
}
