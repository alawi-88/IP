@php
    $branding = \App\Models\BrandingSetting::first();
    $logo = $branding?->logo ?? null;
    $email_bg_color = $branding?->email_bg_color ?? '#ffffff';
    $email_text_color = $branding?->email_text_color ?? '#000000';
    $email_link_color = $branding?->email_link_color ?? '#007bff';
    $email_footer = $branding?->email_footer ?? '';
    $font = $branding?->font ?? 'Arial, sans-serif';
    $email_font_size = $branding?->email_border_color ?? '14';
    $email_logo = $branding?->email_logo ?? null;
    $email_footer_footer = $branding?->email_footer_footer ?? null;
    
    // Detect if Arabic locale is being used
    $currentLocale = app()->getLocale();
    $isArabic = $currentLocale === 'ar';
    $locale = $isArabic ? 'ar' : ($currentLocale ?: config('app.locale', 'en'));
    $direction = $isArabic ? 'rtl' : 'ltr';
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $direction }}">
<head>
    <meta charset="UTF-8">
    <title>{{ config('app.name') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        @media only screen and (max-width: 600px) {
            .email-body {
                padding: 20px 10px !important;
                ;
            }
        }
    </style>
</head>
<body>
<div class="email-wrapper"  style="width:100%;padding: 0;">
    <div class="email-content" style="max-width: 800px;
            margin: 0 auto;
            background-color:{{ $email_bg_color }};
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);">
        <div class="email-header" style="text-align: center;background-color: {{ $email_link_color }};padding: 20px;">
            @if ($email_logo || $logo)
                <img
                    @if ($email_logo)
                        src="{{ url('storage/'.$email_logo) }}"
                    @elseif ($logo)
                        src="{{ url('storage/'.$logo) }}"
                    @endif
                    alt="Logo"
                    class="logo"
                    width="200"
                    height="auto"
                />
            @endif
        </div>
        <div class="email-body" style="color: {{ $email_text_color }};font-size: {{ $email_font_size }}px;font-family: {{ $font }};padding: 30px 20px;line-height: 1.6;direction: {{ $direction }};text-align: {{ $isArabic ? 'right' : 'left' }};">
            {{ Illuminate\Mail\Markdown::parse($slot) }}
        </div>
        @if ($email_footer_footer)
        <div class="email-footer-footer" style="text-align: center;padding: 20px;">
            <img
                @if ($email_footer_footer)
                    src="{{ url('storage/'.$email_footer_footer) }}"
                @endif
                alt="Logo"
                class="logo"
                style="max-width: 100%;height: auto;width: auto;"
            />
        </div>
        @endif
    </div>
    <div class="email-footer" style="text-align: center;font-size: 12px;color: #888;padding: 20px;">
       {{ $email_footer }}
    </div>
</div>
</body>
</html>
