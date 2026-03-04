<?php

namespace App\Services;

use App\Models\VaPage;
use App\Models\AiGeneration;

class AiGenerationService
{
    /**
     * Generate AI suggestion for a field
     * This is a mock service for v6 - can be enhanced with real AI integration
     */
    public function generateForField(VaPage $vaPage, string $fieldKey, string $userPrompt): AiGeneration
    {
        // Start timing
        $startTime = microtime(true);

        // Generate mock response based on field and page context
        $response = $this->generateMockResponse($vaPage, $fieldKey, $userPrompt);

        // Calculate timing
        $generationTime = (int)((microtime(true) - $startTime) * 1000);

        // Create AI generation record
        $generation = AiGeneration::create([
            'va_page_id' => $vaPage->id,
            'user_id' => auth()->id(),
            'field_key' => $fieldKey,
            'prompt' => $userPrompt,
            'response' => $response,
            'status' => 'completed',
            'model_used' => 'mock-model-v1',
            'tokens_used' => $this->estimateTokens($userPrompt . ' ' . $response),
            'generation_time_ms' => $generationTime,
        ]);

        return $generation;
    }

    /**
     * Generate a mock response based on context
     */
    private function generateMockResponse(VaPage $vaPage, string $fieldKey, string $userPrompt): string
    {
        $section = $vaPage->vaSection;
        $startup = $section->startup;

        $contextStrings = [
            'startup_name' => $startup->name,
            'section' => $section->section_key,
            'page' => $vaPage->page_key,
            'field' => $fieldKey,
        ];

        // Basic mock response based on field and page
        $mockResponses = [
            'market_size' => "Based on the {$contextStrings['startup_name']} concept, the addressable market in your sector appears to be substantial. Consider researching TAM (Total Addressable Market), SAM (Serviceable Addressable Market), and SOM (Serviceable Obtainable Market) metrics.",
            'competitive_advantage' => "Your {$contextStrings['startup_name']} could differentiate by focusing on unique value propositions. Consider factors like technology, team expertise, customer relationships, and operational efficiency.",
            'revenue_model' => "Consider multiple revenue streams for {$contextStrings['startup_name']}: subscription, freemium, marketplace, licensing, or hybrid models. Evaluate what works best for your target market.",
            'target_customer' => "Define your ideal customer profile (ICP) for {$contextStrings['startup_name']}. Consider demographics, firmographics, pain points, and buying behavior.",
        ];

        // Return mock response or a generic one
        foreach ($mockResponses as $key => $value) {
            if (str_contains(strtolower($fieldKey), strtolower($key))) {
                return $value;
            }
        }

        return "This is a mock AI suggestion for the field '{$fieldKey}' in the {$contextStrings['startup_name']} {$contextStrings['page']} page. In production, this would be powered by a real AI model.";
    }

    /**
     * Estimate tokens (rough approximation: ~4 characters per token)
     */
    private function estimateTokens(string $text): int
    {
        return max(1, (int)ceil(strlen($text) / 4));
    }
}
