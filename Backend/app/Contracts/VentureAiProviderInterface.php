<?php

namespace App\Contracts;

interface VentureAiProviderInterface
{
    /**
     * Generate a section with the given prompt and options.
     *
     * @param string $prompt The prompt to send to the AI provider
     * @param array $options Additional options for generation (temperature, max_tokens, etc.)
     * @return array Returns array with keys: content, prompt_tokens, completion_tokens
     */
    public function generateSection(string $prompt, array $options = []): array;

    /**
     * Get the human-readable name of the provider.
     *
     * @return string
     */
    public function getProviderName(): string;

    /**
     * Check if the provider is available (has valid API key).
     *
     * @return bool
     */
    public function isAvailable(): bool;
}
