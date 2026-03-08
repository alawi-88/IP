<?php

namespace App\Contracts\Ai;

interface VentureAiProviderInterface
{
    /**
     * Generate content for a specific venture section.
     *
     * @param string $ideaPrompt The user's startup idea prompt
     * @param string $sectionKey The section type key
     * @param string|null $customInstruction Optional custom instruction for regeneration
     * @param array $context Additional context (e.g., previously generated sections)
     * @return array{content: array, tokens_used: int, prompt: string}
     */
    public function generateSection(
        string $ideaPrompt,
        string $sectionKey,
        ?string $customInstruction = null,
        array $context = []
    ): array;

    /**
     * Get the provider name.
     */
    public function getProviderName(): string;
}
