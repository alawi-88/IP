<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Video Tool Services Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for video conferencing tool integrations including
    | Zoom, Microsoft Teams, and Google Meet.
    |
    */

    'zoom' => [
        'client_id' => env('ZOOM_CLIENT_ID'),
        'client_secret' => env('ZOOM_CLIENT_SECRET'),
        'redirect_uri' => env('ZOOM_REDIRECT_URI', rtrim(config('app.url'), '/') . '/api/mentor/video-tools/zoom/callback'),
        'base_url' => env('ZOOM_BASE_URL', 'https://api.zoom.us/v2'),
        'account_id' => env('ZOOM_ACCOUNT_ID'),
    ],

    'teams' => [
        'client_id' => env('TEAMS_CLIENT_ID'),
        'client_secret' => env('TEAMS_CLIENT_SECRET'),
        'redirect_uri' => env('TEAMS_REDIRECT_URI', rtrim(config('app.url'), '/') . '/api/mentor/video-tools/teams/callback'),
        'base_url' => env('TEAMS_BASE_URL', 'https://graph.microsoft.com/v1.0'),
        'tenant_id' => env('TEAMS_TENANT_ID', 'common'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect_uri' => env('GOOGLE_REDIRECT_URI', rtrim(config('app.url'), '/') . '/api/mentor/video-tools/google/callback'),
        'base_url' => env('GOOGLE_BASE_URL', 'https://www.googleapis.com'),
        'calendar_api_url' => 'https://www.googleapis.com/calendar/v3',
        'default_account_email' => env('GOOGLE_MEET_DEFAULT_ACCOUNT_EMAIL'),
        'default_account_mentor_id' => env('GOOGLE_MEET_DEFAULT_ACCOUNT_MENTOR_ID'),
        'use_global_account' => env('GOOGLE_MEET_USE_GLOBAL_ACCOUNT', false),
    ],
];
