<?php

return [
    /*
    |--------------------------------------------------------------------------
    | JWT Secret Key
    |--------------------------------------------------------------------------
    |
    | This key is used to sign and verify JWT tokens. Make sure to keep this
    | secret and use a strong, random key in production.
    |
    */

    'secret' => env('JWT_SECRET', 'your-secret-key-change-this-in-production'),

    /*
    |--------------------------------------------------------------------------
    | JWT Algorithm
    |--------------------------------------------------------------------------
    |
    | The algorithm used to sign JWT tokens. Supported algorithms:
    | HS256, HS384, HS512, RS256, RS384, RS512
    |
    */

    'algorithm' => env('JWT_ALGORITHM', 'HS256'),

    /*
    |--------------------------------------------------------------------------
    | JWT Expiration Time
    |--------------------------------------------------------------------------
    |
    | The default expiration time for JWT tokens in seconds.
    | Default: 3600 (1 hour)
    |
    */

    'expiration' => env('JWT_EXPIRATION', 3600),

    /*
    |--------------------------------------------------------------------------
    | JWT Refresh Token Expiration
    |--------------------------------------------------------------------------
    |
    | The expiration time for refresh tokens in seconds.
    | Default: 604800 (1 week)
    |
    */

    'refresh_expiration' => env('JWT_REFRESH_EXPIRATION', 604800),

    /*
    |--------------------------------------------------------------------------
    | JWT Issuer
    |--------------------------------------------------------------------------
    |
    | The issuer of JWT tokens. Usually your application URL.
    |
    */

    'issuer' => env('JWT_ISSUER', env('APP_URL')),

    /*
    |--------------------------------------------------------------------------
    | JWT Audience
    |--------------------------------------------------------------------------
    |
    | The audience of JWT tokens. Usually your application URL.
    |
    */

    'audience' => env('JWT_AUDIENCE', env('APP_URL')),

    /*
    |--------------------------------------------------------------------------
    | JWT Leeway
    |--------------------------------------------------------------------------
    |
    | The leeway time in seconds to account for clock skew.
    | Default: 60 seconds
    |
    */

    'leeway' => env('JWT_LEEWAY', 60),
];
