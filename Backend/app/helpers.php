<?php

use App\Models\Competition;
use App\Models\CompetitionApplication;
use App\Models\ProjectEvaluation;
use App\Models\Team;
use Random\RandomException;

if (!function_exists('generateSerialNumber')) {
    /**
     * Generate a serial number for a team member.
     *
     * @return string
     * @throws RandomException
     */
    function generateSerialNumber(): string
    {
        return str_pad(random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('myTeam')) {
    /**
     * Get the authenticated team.
     *
     * @return Team
     */
    function myTeam($attribute = 'id'): mixed
    {
        $user = auth()->user();
        return $user?->team?->$attribute;
    }
}

// currentCompetition
if (!function_exists('currentCompetition')) {
    /**
     * Get the current competition.
     *
     * @param string $attribute
     * @return mixed
     */
    function currentCompetitionId(): mixed
    {
        return session('current_competition_id');
    }
}

if (!function_exists('currentCompetition')) {
    /**
     * Get the current competition.
     *
     * @param string $attribute
     * @return mixed
     */
    function currentCompetition(string $attribute = 'id'): mixed
    {
        return Competition::find(currentCompetitionId())?->$attribute;
    }
}


if (!function_exists('getCompetitionId')) {
    /**
     * Get the competition id of an application.
     *
     * @param int $applicationId
     * @return Competition
     */
    function getCompetitionId(int $applicationId): int
    {
        return CompetitionApplication::findOrFail($applicationId)->competition_id;
    }
}

if (!function_exists('fieldName')) {
    function fieldName(string $slug): string
    {
        return str_replace('-', '_', $slug);
    }
}

if (!function_exists('getMultilingualTranslation')) {
    /**
     * Get translation in both Arabic and English
     */
    function getMultilingualTranslation(string $key, array $parameters = []): array
    {
        return [
            'ar' => getTranslationForLocale($key, 'ar', $parameters),
            'en' => getTranslationForLocale($key, 'en', $parameters),
        ];
    }
}

if (!function_exists('getTranslationForLocale')) {
    /**
     * Get translation for a specific locale
     */
    function getTranslationForLocale(string $key, string $locale, array $parameters = []): string
    {
        $currentLocale = app()->getLocale();
        app()->setLocale($locale);
        $translation = __($key, $parameters);
        app()->setLocale($currentLocale);

        return $translation;
    }
}

if (!function_exists('getUserPreferredLocale')) {
    /**
     * Determine the user's preferred locale for notifications
     * Checks notification locale property, request Accept-Language header, or infers from user attributes
     */
    function getUserPreferredLocale($notifiable = null, $notification = null): string
    {
        // First, check if locale is set on the notification instance (highest priority)
        if ($notification && isset($notification->locale) && in_array($notification->locale, ['en', 'ar'])) {
            return $notification->locale;
        }

        // Try to get locale from request if available (for non-queued notifications)
        try {
            if (request() && request()->hasHeader('Accept-Language')) {
                $locale = request()->header('Accept-Language', 'en');
                // Normalize locale (e.g., 'ar-SA' -> 'ar')
                $locale = substr($locale, 0, 2);
                if (in_array($locale, config('app.supported_locales', ['en', 'ar']))) {
                    return $locale;
                }
            }
        } catch (\Exception $e) {
            // Request context not available (e.g., in queued jobs)
        }

        // Try to infer from user's name if it contains Arabic characters
        if ($notifiable && isset($notifiable->name)) {
            $name = is_array($notifiable->name) 
                ? ($notifiable->name['ar'] ?? $notifiable->name['en'] ?? '')
                : ($notifiable->name ?? '');
            
            // Check if name contains Arabic characters
            if (preg_match('/[\x{0600}-\x{06FF}]/u', $name)) {
                return 'ar';
            }
            
            // If Arabic name exists and is not empty, prefer Arabic
            if (is_array($notifiable->name) && !empty($notifiable->name['ar'])) {
                return 'ar';
            }
        }

        // Default to current app locale or fallback to 'en'
        return app()->getLocale() ?: config('app.locale', 'en');
    }
}
