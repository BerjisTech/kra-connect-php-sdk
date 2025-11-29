<?php

return [

    /*
    |--------------------------------------------------------------------------
    | KRA GavaConnect API Key
    |--------------------------------------------------------------------------
    |
    | Your KRA GavaConnect API key. This is required to authenticate
    | requests to the KRA API.
    |
    */

    'api_key' => env('KRA_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | API Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL for the KRA GavaConnect API. By default, this points
    | to the production API endpoint.
    |
    */

    'base_url' => env('KRA_BASE_URL', 'https://api.kra.go.ke/gavaconnect/v1'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | The maximum time (in seconds) to wait for API requests to complete.
    |
    */

    'timeout' => env('KRA_TIMEOUT', 30.0),

    /*
    |--------------------------------------------------------------------------
    | Debug Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, detailed request/response information will be logged.
    | Only enable in development environments.
    |
    */

    'debug' => env('KRA_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | User Agent
    |--------------------------------------------------------------------------
    |
    | Custom user agent string to send with API requests.
    | Leave null to use the default SDK user agent.
    |
    */

    'user_agent' => env('KRA_USER_AGENT'),

    /*
    |--------------------------------------------------------------------------
    | Additional Headers
    |--------------------------------------------------------------------------
    |
    | Additional HTTP headers to send with every API request.
    |
    */

    'additional_headers' => [
        // 'X-Custom-Header' => 'value',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure caching behavior for API responses.
    |
    */

    'cache' => [
        'enabled' => env('KRA_CACHE_ENABLED', true),
        'ttl' => env('KRA_CACHE_TTL', 3600), // Default TTL in seconds
        'pin_verification_ttl' => env('KRA_CACHE_PIN_TTL', 3600), // 1 hour
        'tcc_verification_ttl' => env('KRA_CACHE_TCC_TTL', 1800), // 30 minutes
        'eslip_validation_ttl' => env('KRA_CACHE_ESLIP_TTL', 3600), // 1 hour
        'taxpayer_details_ttl' => env('KRA_CACHE_TAXPAYER_TTL', 7200), // 2 hours
        'prefix' => env('KRA_CACHE_PREFIX', 'kra_connect:'),
        'max_size' => env('KRA_CACHE_MAX_SIZE', 1000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limit Configuration
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting to prevent exceeding API limits.
    | Uses token bucket algorithm.
    |
    */

    'rate_limit' => [
        'enabled' => env('KRA_RATE_LIMIT_ENABLED', true),
        'max_requests' => env('KRA_RATE_LIMIT_MAX', 100), // Max requests
        'window_seconds' => env('KRA_RATE_LIMIT_WINDOW', 60), // Time window in seconds
        'block_on_limit' => env('KRA_RATE_LIMIT_BLOCK', true), // Block when limit reached
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    |
    | Configure automatic retry behavior for failed requests.
    | Uses exponential backoff strategy.
    |
    */

    'retry' => [
        'max_retries' => env('KRA_RETRY_MAX', 3),
        'initial_delay' => env('KRA_RETRY_DELAY', 1.0), // Initial delay in seconds
        'max_delay' => env('KRA_RETRY_MAX_DELAY', 32.0), // Maximum delay in seconds
        'backoff_multiplier' => env('KRA_RETRY_MULTIPLIER', 2.0),
        'retry_on_timeout' => env('KRA_RETRY_ON_TIMEOUT', true),
        'retry_on_server_error' => env('KRA_RETRY_ON_SERVER_ERROR', true),
        'retry_on_rate_limit' => env('KRA_RETRY_ON_RATE_LIMIT', true),
        'retryable_status_codes' => [408, 429, 500, 502, 503, 504],
    ],

];
