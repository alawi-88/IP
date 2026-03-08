<?php

namespace App\Services\Ai;

use App\Contracts\Ai\VentureAiProviderInterface;
use App\Models\AiProvider;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class AiProviderManager
{
    /**
     * Resolve the cheapest active provider instance.
     */
    public function resolve(): VentureAiProviderInterface
    {
        $provider = $this->getCheapestProvider();

        if (!$provider) {
            // Fallback to config-based Claude provider if no DB providers exist
            return $this->fallbackToConfig();
        }

        return $this->instantiate($provider);
    }

    /**
     * Execute an operation with automatic failover across providers.
     *
     * @param callable $operation fn(VentureAiProviderInterface $provider): array
     * @return array The result from the first successful provider
     * @throws \RuntimeException If all providers fail
     */
    public function resolveWithFallback(callable $operation): array
    {
        $providers = $this->getOrderedProviders();

        // If no DB providers, fall back to config
        if ($providers->isEmpty()) {
            $configProvider = $this->fallbackToConfig();
            return $operation($configProvider);
        }

        $errors = [];

        foreach ($providers as $aiProvider) {
            try {
                $providerInstance = $this->instantiate($aiProvider);
                $result = $operation($providerInstance);

                // Record success
                $aiProvider->recordSuccess($result['tokens_used'] ?? 0);

                Log::info('AI generation succeeded', [
                    'provider' => $aiProvider->name,
                    'provider_type' => $aiProvider->provider_type,
                    'tokens_used' => $result['tokens_used'] ?? 0,
                ]);

                return $result;

            } catch (\Exception $e) {
                $errorMessage = $e->getMessage();

                // Record the error
                $aiProvider->recordError($errorMessage);

                $errors[] = [
                    'provider' => $aiProvider->name,
                    'provider_type' => $aiProvider->provider_type,
                    'error' => $errorMessage,
                ];

                Log::warning('AI provider failed, trying next', [
                    'provider' => $aiProvider->name,
                    'provider_type' => $aiProvider->provider_type,
                    'error' => $errorMessage,
                    'remaining_providers' => $providers->count() - count($errors),
                ]);

                // Check if provider was auto-disabled
                if ($aiProvider->shouldAutoDisable()) {
                    Log::warning('AI provider auto-disabled due to consecutive errors', [
                        'provider' => $aiProvider->name,
                        'error_count' => $aiProvider->error_count,
                        'threshold' => $aiProvider->auto_disable_threshold,
                    ]);
                }
            }
        }

        // All providers failed
        $errorSummary = collect($errors)
            ->map(fn ($e) => "{$e['provider']} ({$e['provider_type']}): {$e['error']}")
            ->implode(' | ');

        throw new \RuntimeException(
            "All AI providers failed. Errors: {$errorSummary}"
        );
    }

    /**
     * Get all active providers ordered by priority and cost.
     */
    public function getOrderedProviders(): Collection
    {
        return AiProvider::active()->ordered()->get();
    }

    /**
     * Get the cheapest active provider model record.
     */
    public function getCheapestProvider(): ?AiProvider
    {
        return AiProvider::active()->ordered()->first();
    }

    /**
     * Instantiate a provider from an AiProvider model record.
     */
    public function instantiate(AiProvider $provider): VentureAiProviderInterface
    {
        $apiKey = $provider->api_key;
        $model = $provider->getSelectedModel();
        $baseUrl = $provider->base_url ?? $this->getDefaultBaseUrl($provider->provider_type);
        $maxTokens = $provider->max_tokens;

        return match ($provider->provider_type) {
            'anthropic' => new ClaudeVentureAiProvider($apiKey, $model, $baseUrl, $maxTokens),
            'openai' => new OpenAiVentureAiProvider($apiKey, $model, $baseUrl, $maxTokens),
            'gemini' => new GeminiVentureAiProvider($apiKey, $model, $baseUrl, $maxTokens),
            default => throw new \InvalidArgumentException("Unknown AI provider type: {$provider->provider_type}"),
        };
    }

    /**
     * Fall back to config-based Claude provider (backward compatibility).
     */
    private function fallbackToConfig(): VentureAiProviderInterface
    {
        Log::info('No database AI providers found, falling back to config-based Claude provider');

        return new ClaudeVentureAiProvider(
            config('venture.ai.api_key', ''),
            config('venture.ai.model', 'claude-sonnet-4-20250514'),
            config('venture.ai.base_url', 'https://api.anthropic.com/v1'),
            config('venture.ai.max_tokens', 4096),
        );
    }

    /**
     * Get default base URL for a provider type.
     */
    private function getDefaultBaseUrl(string $providerType): string
    {
        return match ($providerType) {
            'anthropic' => 'https://api.anthropic.com/v1',
            'openai' => 'https://api.openai.com/v1',
            'gemini' => 'https://generativelanguage.googleapis.com/v1beta',
            default => '',
        };
    }
}
