<?php

namespace App\Services\Ai;

use App\Contracts\Ai\VentureAiProviderInterface;
use Illuminate\Support\Facades\Log;

abstract class BaseVentureAiProvider implements VentureAiProviderInterface
{
    protected string $apiKey;
    protected string $model;
    protected string $baseUrl;
    protected int $maxTokens;

    public function __construct(string $apiKey, string $model, string $baseUrl, int $maxTokens = 4096)
    {
        $this->apiKey = $apiKey;
        $this->model = $model;
        $this->baseUrl = $baseUrl;
        $this->maxTokens = $maxTokens;
    }

    /**
     * Parse the JSON response from AI, handling markdown code blocks, truncation, and common issues.
     */
    protected function parseJsonResponse(string $text, string $sectionKey): array
    {
        $jsonStr = trim($text);

        // Strategy 0: Strip code fences (handles both complete and truncated code blocks)
        $stripped = $jsonStr;
        if (preg_match('/^```(?:json)?\s*\n?/', $stripped)) {
            $stripped = preg_replace('/^```(?:json)?\s*\n?/', '', $stripped);
            $stripped = preg_replace('/\n?\s*```\s*$/', '', $stripped);
            $stripped = trim($stripped);
        }

        // Strategy 1: Extract JSON from complete markdown code block
        if (preg_match('/```(?:json)?\s*\n?(.*?)\n?\s*```/s', $jsonStr, $matches)) {
            $extracted = trim($matches[1]);
            $parsed = json_decode($extracted, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($parsed)) {
                return $parsed;
            }
        }

        // Strategy 2: Try direct parse of stripped text
        $textToParse = ($stripped !== $jsonStr && (str_starts_with($stripped, '{') || str_starts_with($stripped, '['))) ? $stripped : $jsonStr;
        $parsed = json_decode($textToParse, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($parsed)) {
            return $parsed;
        }

        // Strategy 3: Find outermost { } or [ ]
        $workText = $stripped ?: $text;
        $firstBrace = strpos($workText, '{');
        $firstBracket = strpos($workText, '[');
        $extracted = $textToParse;
        $start = false;

        if ($firstBrace !== false && ($firstBracket === false || $firstBrace < $firstBracket)) {
            $start = $firstBrace;
            $end = strrpos($workText, '}');
        } elseif ($firstBracket !== false) {
            $start = $firstBracket;
            $end = strrpos($workText, ']');
        }

        if ($start !== false && isset($end) && $end !== false && $end > $start) {
            $extracted = substr($workText, $start, $end - $start + 1);
            $parsed = json_decode($extracted, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($parsed)) {
                return $parsed;
            }
        }

        // Strategy 4: Fix common AI JSON issues
        $fixedJson = $this->fixCommonJsonIssues($extracted);
        $parsed = json_decode($fixedJson, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($parsed)) {
            Log::info('Parsed AI JSON after fixing common issues', [
                'section_key' => $sectionKey,
                'provider' => $this->getProviderName(),
            ]);
            return $parsed;
        }

        // Strategy 5: Recover truncated JSON (AI response cut off by token limit)
        // Try both raw and pre-fixed versions for truncation recovery
        $jsonCandidate = $stripped ?: $extracted;
        $candidates = [$jsonCandidate];
        $preFixed = $this->fixCommonJsonIssues($jsonCandidate);
        if ($preFixed !== $jsonCandidate) {
            $candidates[] = $preFixed;
        }

        foreach ($candidates as $candidate) {
            if (str_starts_with($candidate, '{') || str_starts_with($candidate, '[')) {
                $recovered = $this->recoverTruncatedJson($candidate);
                if ($recovered) {
                    $parsed = json_decode($recovered, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($parsed)) {
                        Log::info('Recovered truncated AI JSON', [
                            'section_key' => $sectionKey,
                            'provider' => $this->getProviderName(),
                        ]);
                        return $parsed;
                    }
                    $fixedRecovered = $this->fixCommonJsonIssues($recovered);
                    $parsed = json_decode($fixedRecovered, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($parsed)) {
                        Log::info('Recovered truncated AI JSON after fixing issues', [
                            'section_key' => $sectionKey,
                            'provider' => $this->getProviderName(),
                        ]);
                        return $parsed;
                    }
                }
            }
        }

        Log::warning('Failed to parse AI JSON response', [
            'section_key' => $sectionKey,
            'provider' => $this->getProviderName(),
            'error' => json_last_error_msg(),
            'raw_response_length' => strlen($text),
        ]);

        return [
            'raw_text' => $text,
            '_parse_error' => true,
        ];
    }

    /**
     * Recover truncated JSON by walking backwards to find the last position
     * where closing brackets/braces produces valid JSON.
     */
    protected function recoverTruncatedJson(string $json): ?string
    {
        $state = $this->analyzeJsonState($json);

        if ($state['braces'] <= 0 && $state['brackets'] <= 0 && !$state['inString']) {
            return null;
        }

        $len = strlen($json);
        $minLen = (int)($len * 0.3);

        for ($i = $len; $i > $minLen; $i--) {
            $candidate = substr($json, 0, $i);
            $candidate = rtrim($candidate);
            $candidate = preg_replace('/,\s*$/', '', $candidate);

            $candidateState = $this->analyzeJsonState($candidate);

            if ($candidateState['inString']) continue;
            if ($candidateState['braces'] < 0 || $candidateState['brackets'] < 0) continue;

            $attempt = $candidate;
            for ($j = 0; $j < $candidateState['brackets']; $j++) $attempt .= ']';
            for ($j = 0; $j < $candidateState['braces']; $j++) $attempt .= '}';

            $parsed = json_decode($attempt, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($parsed)) {
                return $attempt;
            }
        }

        return null;
    }

    private function analyzeJsonState(string $json): array
    {
        $braces = 0; $brackets = 0; $inString = false; $escape = false;
        for ($i = 0; $i < strlen($json); $i++) {
            $c = $json[$i];
            if ($escape) { $escape = false; continue; }
            if ($c === '\\') { $escape = true; continue; }
            if ($c === '"') { $inString = !$inString; continue; }
            if (!$inString) {
                if ($c === '{') $braces++;
                if ($c === '}') $braces--;
                if ($c === '[') $brackets++;
                if ($c === ']') $brackets--;
            }
        }
        return ['braces' => $braces, 'brackets' => $brackets, 'inString' => $inString];
    }

    /**
     * Fix common JSON issues from AI-generated output.
     */
    protected function fixCommonJsonIssues(string $json): string
    {
        // Fix missing commas between values on separate lines
        $json = preg_replace('/"\s*\n(\s*")/', "\",\n$1", $json);
        $json = preg_replace('/\]\s*\n(\s*")/', "],\n$1", $json);
        $json = preg_replace('/\}\s*\n(\s*")/', "},\n$1", $json);
        // Fix missing comma between } and { on separate lines (adjacent objects in arrays)
        $json = preg_replace('/\}\s*\n(\s*\{)/', "},\n$1", $json);
        // Fix missing comma between ] and { on separate lines
        $json = preg_replace('/\]\s*\n(\s*\{)/', "],\n$1", $json);
        // Fix missing comma between ] and [ on separate lines
        $json = preg_replace('/\]\s*\n(\s*\[)/', "],\n$1", $json);
        // Fix missing comma between number and " on separate lines
        $json = preg_replace('/(\d)\s*\n(\s*")/', "$1,\n$2", $json);
        // Fix missing comma between true/false/null and " on separate lines
        $json = preg_replace('/(true|false|null)\s*\n(\s*")/i', "$1,\n$2", $json);
        // Remove trailing commas before closing brackets/braces
        $json = preg_replace('/,\s*([}\]])/', '$1', $json);
        return $json;
    }

    /**
     * Classify API error for appropriate messaging.
     */
    protected function classifyError(int $status, string $body): string
    {
        $bodyLower = strtolower($body);

        // Check credits/quota BEFORE rate-limit — a 429 with "quota exceeded" is billing, not throttling
        if ($status === 402 || str_contains($bodyLower, 'credit') || str_contains($bodyLower, 'billing') || str_contains($bodyLower, 'insufficient') || str_contains($bodyLower, 'quota') || str_contains($bodyLower, 'exceeded your current')) {
            return 'credits_exhausted';
        }
        if ($status === 429 || str_contains($bodyLower, 'rate_limit') || str_contains($bodyLower, 'rate limit')) {
            return 'rate_limit';
        }
        if ($status === 401 || str_contains($bodyLower, 'authentication') || str_contains($bodyLower, 'invalid_api_key') || str_contains($bodyLower, 'invalid api key') || str_contains($bodyLower, 'unauthorized')) {
            return 'auth_error';
        }
        if ($status === 529 || str_contains($bodyLower, 'overloaded')) {
            return 'overloaded';
        }
        if ($status >= 500) {
            return 'server_error';
        }
        if ($status === 408 || str_contains($bodyLower, 'timeout')) {
            return 'timeout';
        }

        return 'unknown';
    }

    /**
     * Get a user-friendly error message based on error type.
     */
    protected function getUserFriendlyError(string $errorType, int $status): string
    {
        $provider = ucfirst($this->getProviderName());

        return match ($errorType) {
            'credits_exhausted' => "{$provider}: Credits/quota exhausted. The system will try an alternative provider.",
            'rate_limit' => "{$provider}: Rate-limited. The system will try an alternative provider or retry shortly.",
            'auth_error' => "{$provider}: Authentication failed. Please verify the API key in AI Provider settings.",
            'overloaded' => "{$provider}: Service overloaded. The system will try an alternative provider.",
            'server_error' => "{$provider}: Server error. The system will try an alternative provider.",
            'timeout' => "{$provider}: Request timed out. The system will try an alternative provider.",
            default => "{$provider}: Request failed (HTTP {$status}). The system will try an alternative provider.",
        };
    }
}
