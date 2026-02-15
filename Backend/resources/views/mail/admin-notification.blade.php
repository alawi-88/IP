   @php
                        $branding = \App\Models\BrandingSetting::first();
                        $logo = $branding->logo;
                        $email_bg_color = $branding->email_bg_color;
                        $email_text_color = $branding->email_text_color;
                        $email_link_color = $branding->email_link_color;
                        $email_footer = $branding->email_footer;
                        $font = $branding->font;
                        $email_font_size = $branding->email_border_color;
                        $email_logo = $branding->email_logo;
                        $email_footer_footer = $branding->email_footer_footer;
                        @endphp
<!DOCTYPE html>
<html lang="{{ config('app.locale') }}">
<head>
    <meta charset="UTF-8">
    <title>{{ config('app.name') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>

    
        @media only screen and (max-width: 600px) {
            .email-body {
                padding: 20px 10px !important;
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
            <img
                        
                            @if ($email_logo)
                                src="{{ url('storage/'.$email_logo) }}"
                            @else
                                src="{{ url('storage/'.$logo) }}"
                            @endif

                            alt="Logo"
                            class="logo"
                            width="200"
                            height="auto"
                        />
        </div>
        <div class="email-body" style="color: {{ $email_text_color }};font-size: {{ $email_font_size }}px;font-family: {{ $font }};padding: 30px 20px;line-height: 1.6;">
            {!! nl2br(e($body)) !!}
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