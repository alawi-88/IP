<?php

namespace App\Services\Ai;

use App\Contracts\VentureAiProviderInterface;
use App\Models\AiProvider;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GroqVentureAiProvider implements VentureAiProviderInterface
{
    protected AiProvider $provider;

    public function __construct(AiProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * Generate a section using Groq API (OpenAI-compatible).
     *
     * @param string $prompt
     * @param array $options
     * @return array
     * @throws RuntimeException
     */
    public function generateSection(string $prompt, array $options = []): array
    {
        if (!$this->isAvailable()) {
            throw new RuntimeException('Groq API key is not configured');
        }

        $maxTokens = $options['max_tokens'] ?? $this->provider->max_tokens ?? 4096;
        $temperature = $options['temperature'] ?? $this->provider->temperature ?? 0.7;
        $modelName = $this->provider->model_name ?? 'llama-3.3-70b-versatile';
        $apiKey = $this->provider->api_key ?: config('services.groq.api_key');

        $systemPrompt = $options['system_prompt'] ?? 'You are an expert startup advisor and business analyst. Respond with valid JSON only. No markdown, no explanation, no code fences.';

        try {
            $response = Http::timeout(90)
                ->withHeaders([
                    'Authorization' => "Bearer {$apiKey}",
                    'Content-Type' => 'application/json',
                ])
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => $modelName,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $systemPrompt,
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'max_completion_tokens' => $maxTokens,
                    'temperature' => (float) $temperature,
                    'stream' => false,
                ]);

            if (!$response->successful()) {
                throw new RuntimeException(
                    "Groq API error: {$response->status()} - {$response->body()}"
                );
            }

            $data = $response->json();

            if (empty($data['choices']) || empty($data['choices'][0]['message']['content'])) {
                throw new RuntimeException('Invalid response format from Groq API');
            }

            $content = $data['choices'][0]['message']['content'] ?? '';

            if (empty($content)) {
                throw new RuntimeException('Empty response from Groq API');
            }

            // Strip markdown code fences if present (e.g., ```json\n...\n```)
            $content = trim($content);
            if (preg_match('/^```(?:json)?\s*\n?(.*?)\n?\s*```$/s', $content, $matches)) {
                $content = trim($matches[1]);
            }

            $parsed = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new RuntimeException(
                    'Failed to parse JSON from Groq response: ' . json_last_error_msg()
                    . ' | Raw: ' . substr($content, 0, 200)
                );
            }

            $promptTokens = $data['usage']['prompt_tokens'] ?? 0;
            $completionTokens = $data['usage']['completion_tokens'] ?? 0;

            return [
                'content' => $parsed,
                'prompt_tokens' => $promptTokens,
                'completion_tokens' => $completionTokens,
            ];
        } catch (\Exception $e) {
            throw new RuntimeException('Groq API request failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get provider name.
     *
     * @return string
     */
    public function getProviderName(): string
    {
        return 'Groq';
    }

    /**
     * Check if provider is available.
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        return !empty($this->provider->api_key) || !empty(config('services.groq.api_key'));
    }
}
