<?php

namespace App\Services\Ai;

use App\Contracts\VentureAiProviderInterface;
use App\Models\AiProvider;
use RuntimeException;
use Sleep;

class AiProviderManager
{
    /**
     * Collection of active AI providers ordered by priority.
     *
     * @var \Illuminate\Database\Eloquent\Collection
     */
    protected $providers;

    /**
     * Maximum retry attempts per provider.
     *
     * @var int
     */
    protected int $maxRetries = 3;

    /**
     * Sleep duration (in seconds) between retries.
     *
     * @var int
     */
    protected int $retrySleep = 1;

    public function __construct()
    {
        // Load active providers ordered by priority (ascending)
        $this->providers = AiProvider::where('is_active', true)
            ->orderBy('priority', 'asc')
            ->get();

        if ($this->providers->isEmpty()) {
            throw new RuntimeException('No active AI providers configured');
        }
    }

    /**
     * Generate content using the provider with failover support.
     *
     * @param string $prompt
     * @param array $options
     * @return array
     * @throws RuntimeException
     */
    public function generate(string $prompt, array $options = []): array
    {
        $lastException = null;

        foreach ($this->providers as $aiProvider) {
            try {
                $provider = $this->resolveProvider($aiProvider);

                if (!$provider->isAvailable()) {
                    continue;
                }

                // Try up to maxRetries times
                for ($attempt = 1; $attempt <= $this->maxRetries; $attempt++) {
                    try {
                        $result = $provider->generateSection($prompt, $options);

                        // Add provider ID to result
                        $result['provider_id'] = $aiProvider->id;

                        return $result;
                    } catch (\Exception $e) {
                        $lastException = $e;

                        // If this was not the last attempt, sleep and retry
                        if ($attempt < $this->maxRetries) {
                            sleep($this->retrySleep);
                        }
                    }
                }
            } catch (\Exception $e) {
                $lastException = $e;
                // Continue to next provider
                continue;
            }
        }

        // All providers failed
        throw new RuntimeException(
            'All AI providers failed. Last error: ' . ($lastException ? $lastException->getMessage() : 'Unknown error')
        );
    }

    /**
     * Resolve a provider instance from an AiProvider model.
     *
     * @param AiProvider $provider
     * @return VentureAiProviderInterface
     * @throws RuntimeException
     */
    public function resolveProvider(AiProvider $provider): VentureAiProviderInterface
    {
        $providerType = strtolower($provider->provider);

        return match ($providerType) {
            'claude' => new ClaudeVentureAiProvider($provider),
            'openai' => new OpenAiVentureAiProvider($provider),
            'gemini' => new GeminiVentureAiProvider($provider),
            default => throw new RuntimeException("Unknown AI provider type: {$provider->provider}"),
        };
    }

    /**
     * Get all active providers.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getProviders()
    {
        return $this->providers;
    }

    /**
     * Set retry configuration.
     *
     * @param int $maxRetries
     * @param int $retrySleep
     * @return $this
     */
    public function setRetryConfig(int $maxRetries, int $retrySleep): self
    {
        $this->maxRetries = $maxRetries;
        $this->retrySleep = $retrySleep;

        return $this;
    }
}
