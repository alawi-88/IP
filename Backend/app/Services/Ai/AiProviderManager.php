<?php

namespace App\Services\Ai;

use App\Contracts\VentureAiProviderInterface;
use App\Models\AiProvider;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class AiProviderManager
{
    /**
     * Collection of active AI providers ordered by priority.
     */
    protected $providers;

    /**
     * Maximum retry attempts per provider.
     */
    protected int $maxRetries = 2;

    /**
     * Base sleep duration (in seconds) between retries.
     */
    protected int $retrySleep = 2;

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
     * Generate content using providers with failover and retry support.
     *
     * Strategy:
     * 1. Try each provider in priority order
     * 2. For rate limit errors (429), wait with exponential backoff and retry
     * 3. If a provider exhausts retries, move to the next provider
     * 4. If all providers fail, throw with the last error
     */
    public function generate(string|array $prompt, array $options = []): array
    {
        $lastException = null;
        $providerCount = $this->providers->count();
        $attemptLog = [];

        foreach ($this->providers as $idx => $aiProvider) {
            try {
                $provider = $this->resolveProvider($aiProvider);

                if (!$provider->isAvailable()) {
                    Log::debug("AI Provider [{$aiProvider->name}] not available, skipping");
                    continue;
                }

                // Try up to maxRetries times with exponential backoff
                for ($attempt = 1; $attempt <= $this->maxRetries; $attempt++) {
                    try {
                        Log::info("AI generation attempt {$attempt}/{$this->maxRetries} with [{$aiProvider->name}] (model: {$aiProvider->model_name})");

                        $result = $provider->generateSection($prompt, $options);

                        // Add provider metadata to result
                        $result['provider_id'] = $aiProvider->id;
                        $result['provider_name'] = $aiProvider->name;
                        $result['model_name'] = $aiProvider->model_name;

                        Log::info("AI generation succeeded with [{$aiProvider->name}]");
                        return $result;

                    } catch (\Exception $e) {
                        $lastException = $e;
                        $isRateLimit = $this->isRateLimitError($e);
                        $isQuotaExhausted = $this->isQuotaExhaustedError($e);

                        $attemptLog[] = "[{$aiProvider->name}] attempt {$attempt}: " . substr($e->getMessage(), 0, 100);

                        // If quota is fully exhausted (daily limit), skip to next provider immediately
                        if ($isQuotaExhausted) {
                            Log::warning("AI Provider [{$aiProvider->name}] quota exhausted, moving to next provider");
                            break; // Break retry loop, continue to next provider
                        }

                        // If rate limited (temporary), retry with exponential backoff
                        if ($isRateLimit && $attempt < $this->maxRetries) {
                            $backoff = $this->retrySleep * pow(2, $attempt - 1); // 2s, 4s, 8s...
                            Log::warning("AI Provider [{$aiProvider->name}] rate limited, retrying in {$backoff}s");
                            sleep($backoff);
                            continue;
                        }

                        // For non-rate-limit errors, or last attempt, move to next provider
                        if ($attempt >= $this->maxRetries) {
                            Log::warning("AI Provider [{$aiProvider->name}] failed after {$attempt} attempts");
                        } elseif (!$isRateLimit) {
                            // Non-retryable error, skip to next provider
                            Log::warning("AI Provider [{$aiProvider->name}] non-retryable error: " . substr($e->getMessage(), 0, 200));
                            break;
                        }
                    }
                }
            } catch (\Exception $e) {
                $lastException = $e;
                $attemptLog[] = "[{$aiProvider->name}] setup error: " . substr($e->getMessage(), 0, 100);
                continue;
            }
        }

        // All providers failed
        $summary = implode(' | ', $attemptLog);
        Log::error("All AI providers failed. Attempts: {$summary}");

        throw new RuntimeException(
            'All AI providers failed (' . count($attemptLog) . ' attempts across ' . $providerCount . ' providers). '
            . 'Last error: ' . ($lastException ? $lastException->getMessage() : 'Unknown error')
        );
    }

    /**
     * Check if an exception is a rate limit error (temporary, retryable).
     */
    protected function isRateLimitError(\Exception $e): bool
    {
        $message = $e->getMessage();
        return str_contains($message, '429')
            || str_contains($message, 'rate limit')
            || str_contains($message, 'Rate limit')
            || str_contains($message, 'too many requests')
            || str_contains($message, 'RESOURCE_EXHAUSTED');
    }

    /**
     * Check if an exception is a quota exhaustion error (daily limit, not temporary).
     */
    protected function isQuotaExhaustedError(\Exception $e): bool
    {
        $message = $e->getMessage();
        return str_contains($message, 'quota')
            || str_contains($message, 'Quota')
            || str_contains($message, 'exceeded your current quota')
            || str_contains($message, 'free_tier_requests');
    }

    /**
     * Resolve a provider instance from an AiProvider model.
     */
    public function resolveProvider(AiProvider $provider): VentureAiProviderInterface
    {
        $providerType = strtolower($provider->provider_type);

        return match ($providerType) {
            'anthropic', 'claude' => new ClaudeVentureAiProvider($provider),
            'openai' => new OpenAiVentureAiProvider($provider),
            'google', 'gemini' => new GeminiVentureAiProvider($provider),
            'groq' => new GroqVentureAiProvider($provider),
            default => throw new RuntimeException("Unknown AI provider type: {$provider->provider_type}"),
        };
    }

    /**
     * Get all active providers.
     */
    public function getProviders()
    {
        return $this->providers;
    }

    /**
     * Set retry configuration.
     */
    public function setRetryConfig(int $maxRetries, int $retrySleep): self
    {
        $this->maxRetries = $maxRetries;
        $this->retrySleep = $retrySleep;
        return $this;
    }
}
