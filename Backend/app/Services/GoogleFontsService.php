<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class GoogleFontsService
{
    public static function getFonts(): array
{
    return Cache::remember('google_fonts_list', now()->addDay(), function () {
        $apiKey = env('GOOGLE_FONTS_API_KEY');
        $response = @file_get_contents("https://www.googleapis.com/webfonts/v1/webfonts?key={$apiKey}");

        if (!$response) {
            return [];
        }

        $fontsData = json_decode($response, true);

        if (!isset($fontsData['items'])) {
            return [];
        }

        return collect($fontsData['items'])
            ->pluck('family', 'family')
            ->toArray();
    });
}
}
