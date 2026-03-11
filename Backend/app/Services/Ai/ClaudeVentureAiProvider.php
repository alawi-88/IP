<?php

namespace App\Services\Ai;

use App\Contracts\VentureAiProviderInterface;
use App\Models\AiProvider;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ClaudeVentureAiProvider implements VentureAiProviderInterface
{
    protected AiProvider $provider;

    public function __construct(AiProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * Generate a section using Claude API.
     *
     * @param string $prompt
     * @param array $options
     * @return array
     * @throws RuntimeException
     */
    public function generateSection(string $prompt, array $options = []): array
    {
        if (!$this->isAvailable()) {
            throw new RuntimeException('Claude API key is not configured');
        }

        $maxTokens = $options['max_tokens'] ?? $this->provider->max_tokens ?? 2048;
        $temperature = $options['temperature'] ?? $this->provider->temperature ?? 0.7;

        try {
            $response = Http::withHeaders([
                'x-api-key' => config('services.anthropic.api_key'),
                'anthropic-version' => '2023-06-01',
            ])
            ->timeout(60)
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => $this->provider->model_name ?? 'claude-3-5-sonnet-20241022',
                'max_tokens' => $maxTokens,
                'temperature' => $temperature,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
            ]);

            if (!$response->successful()) {
                throw new RuntimeException(
                    "Claude API error: {$response->status()} - {$response->body()}"
                );
            }

            $data = $response->json();

            if (empty($data['content']) || empty($data['content'][0]['text'])) {
                throw new RuntimeException('Invalid response format from Claude API');
            }

            $content = $data['content'][0]['text'];
            $parsed = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new RuntimeException('Failed to parse JSON from Claude response: ' . json_last_error_msg());
            }

            return [
                'content' => $parsed,
                'prompt_tokens' => $data['usage']['input_tokens'] ?? 0,
                'completion_tokens' => $data['usage']['output_tokens'] ?? 0,
            ];
        } catch (\Exception $e) {
            throw new RuntimeException('Claude API request failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get provider name.
     *
     * @return string
     */
    public function getProviderName(): string
    {
        return 'Claude';
    }

    /**
     * Check if provider is available.
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        return !empty(config('services.anthropic.api_key'));
    }
}
