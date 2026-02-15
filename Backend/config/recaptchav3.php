<?php
return [
    'origin' => env('RECAPTCHAV3_ORIGIN', 'https://www.google.com/recaptcha'),
    // Backward compatible env keys:
    // - RECAPTCHA_SITE_KEY / RECAPTCHA_SECRET_KEY (legacy)
    // - RECAPTCHAV3_SITEKEY / RECAPTCHAV3_SECRET (preferred)
    'sitekey' => env('RECAPTCHAV3_SITEKEY', env('RECAPTCHA_SITE_KEY', '')),
    'secret' => env('RECAPTCHAV3_SECRET', env('RECAPTCHA_SECRET_KEY', '')),
    'locale' => env('RECAPTCHAV3_LOCALE', '')
];
