<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class EventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Get preferred language from Accept-Language header
        $preferredLanguage = $this->getPreferredLanguage();

        return [
            'id' => $this->id,
            'program' => new ProgramResource($this->program),
            'title' => $this->getLocalizedTranslatableText($this->title, $preferredLanguage),
            'brief' => $this->getLocalizedTranslatableText($this->brief, $preferredLanguage),
            'badge' => $this->isUpcoming() ? 'upcoming' : 'completed',
            'date' => $this->date->format('Y-m-d'),
            'time' => $this->time->format('H:i'),
            'location' => $this->location,
            'speakers' => $this->formatSpeakers(),
            'event_link' => $this->event_link,
        ];
    }

    private function formatSpeakers(): array
    {
        if (empty($this->speakers)) {
            return [];
        }

        // Get preferred language from Accept-Language header
        $preferredLanguage = $this->getPreferredLanguage();

        return collect($this->speakers)->map(function ($speaker) use ($preferredLanguage) {
            return [
                'photo' => !empty($speaker['photo']) ? Storage::url($speaker['photo']) : null,
                'name' => $this->getLocalizedText($speaker['name'] ?? null, $preferredLanguage),
                'experience' => $this->getLocalizedText($speaker['experience'] ?? null, $preferredLanguage),
                'brief' => $this->getLocalizedText($speaker['brief'] ?? null, $preferredLanguage),
            ];
        })->toArray();
    }

    /**
     * Get preferred language from Accept-Language header
     */
    private function getPreferredLanguage(): string
    {
        $acceptLanguage = request()->header('Accept-Language', 'en');
        
        // Parse Accept-Language header
        $languages = [];
        if (preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i', $acceptLanguage, $matches)) {
            for ($i = 0; $i < count($matches[1]); $i++) {
                $lang = strtolower($matches[1][$i]);
                $q = $matches[4][$i] ? (float) $matches[4][$i] : 1.0;
                $languages[$lang] = $q;
            }
        }

        // Sort by quality value
        arsort($languages);

        // Check for Arabic first
        foreach ($languages as $lang => $q) {
            if (str_starts_with($lang, 'ar')) {
                return 'ar';
            }
        }

        // Check for English
        foreach ($languages as $lang => $q) {
            if (str_starts_with($lang, 'en')) {
                return 'en';
            }
        }

        // Default to English
        return 'en';
    }

    /**
     * Get localized text based on preferred language
     */
    private function getLocalizedText(?array $text, string $preferredLanguage): ?string
    {
        if (!$text || !is_array($text)) {
            return null;
        }

        // Try preferred language first
        if (isset($text[$preferredLanguage]) && !empty($text[$preferredLanguage])) {
            return $text[$preferredLanguage];
        }

        // Fallback to other language
        $fallbackLanguage = $preferredLanguage === 'ar' ? 'en' : 'ar';
        if (isset($text[$fallbackLanguage]) && !empty($text[$fallbackLanguage])) {
            return $text[$fallbackLanguage];
        }

        // Return first available language
        foreach ($text as $lang => $value) {
            if (!empty($value)) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Get localized text for translatable fields (title, brief)
     */
    private function getLocalizedTranslatableText($text, string $preferredLanguage): ?string
    {
        // If text is already a string, return it
        if (is_string($text)) {
            return $text;
        }

        // If text is an array (translatable), use getLocalizedText
        if (is_array($text)) {
            return $this->getLocalizedText($text, $preferredLanguage);
        }

        // If text is null or empty, return null
        return null;
    }
}
