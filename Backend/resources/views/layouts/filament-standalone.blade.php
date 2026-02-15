<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
    $branding = \App\Models\BrandingSetting::first();
    $app = str(config('app.name'))->lower();
    $app = trim(str_replace('system', '', $app));

    $faviconPath = $branding?->favicon
        ? asset('storage/' . $branding->favicon)
        : asset('media/' . $app . '-favicon.ico');

    // detect extension
    $extension = pathinfo($faviconPath, PATHINFO_EXTENSION);
    $mime = match(strtolower($extension)) {
        'png' => 'image/png',
        'jpg', 'jpeg' => 'image/jpeg',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        default => 'image/png'
    };
@endphp

<link rel="icon" type="{{ $mime }}" href="{{ $faviconPath }}">
    <title>{{ config('app.name') }}</title>
    @filamentStyles
</head>
<body class="filament-body bg-gray-900 text-white">
<div class="filament-main flex items-center justify-center min-h-screen">
    {{ $slot }}
</div>
@filamentScripts
</body>
</html>
