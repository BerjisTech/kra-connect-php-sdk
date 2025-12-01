<?php

declare(strict_types=1);

namespace KraConnect\Config;

/**
 * KRA Connect SDK Configuration
 *
 * Main configuration class for the KRA Connect SDK.
 *
 * @package KraConnect\Config
 */
class KraConfig
{
    /**
     * Create a new KRA configuration instance.
     *
     * @param string $apiKey The GavaConnect API key (required)
     * @param string $baseUrl The base URL for the KRA API
     * @param float $timeout Request timeout in seconds
     * @param RetryConfig $retryConfig Retry configuration
     * @param CacheConfig $cacheConfig Cache configuration
     * @param RateLimitConfig $rateLimitConfig Rate limit configuration
     * @param bool $debug Enable debug mode
     * @param string|null $userAgent Custom user agent string
     * @param array<string, string> $additionalHeaders Additional HTTP headers
     */
    public function __construct(
        public readonly string $apiKey,
        public readonly string $baseUrl = 'https://api.kra.go.ke/gavaconnect/v1',
        public readonly float $timeout = 30.0,
        public readonly RetryConfig $retryConfig = new RetryConfig(),
        public readonly CacheConfig $cacheConfig = new CacheConfig(),
        public readonly RateLimitConfig $rateLimitConfig = new RateLimitConfig(),
        public readonly bool $debug = false,
        public readonly ?string $userAgent = null,
        public readonly array $additionalHeaders = []
    ) {
        if (empty($apiKey)) {
            throw new \InvalidArgumentException('API key is required');
        }

        if ($timeout <= 0) {
            throw new \InvalidArgumentException('Timeout must be greater than 0');
        }
    }

    /**
     * Create configuration from environment variables.
     *
     * @param array<string, mixed> $overrides Optional configuration overrides
     * @return self
     * @throws \InvalidArgumentException If API key is not found
     */
    public static function fromEnv(array $overrides = []): self
    {
        $envApiKey = $_ENV['KRA_API_KEY'] ?? getenv('KRA_API_KEY');
        $apiKey = $overrides['apiKey'] ?? (is_string($envApiKey) ? $envApiKey : null);

        if (!is_string($apiKey) || $apiKey === '') {
            throw new \InvalidArgumentException(
                'API key is required. Set KRA_API_KEY environment variable or pass apiKey in overrides.'
            );
        }

        $envBaseUrl = $_ENV['KRA_BASE_URL'] ?? getenv('KRA_BASE_URL');
        $baseUrl = $overrides['baseUrl']
            ?? (is_string($envBaseUrl) && $envBaseUrl !== '' ? $envBaseUrl : 'https://api.kra.go.ke/gavaconnect/v1');

        $envTimeout = $_ENV['KRA_TIMEOUT'] ?? getenv('KRA_TIMEOUT');
        $timeout = $overrides['timeout']
            ?? (is_string($envTimeout) && $envTimeout !== '' ? (float) $envTimeout : 30.0);

        $envDebug = $_ENV['KRA_DEBUG'] ?? getenv('KRA_DEBUG');
        $debug = $overrides['debug']
            ?? (is_string($envDebug) && $envDebug !== '' ? filter_var($envDebug, FILTER_VALIDATE_BOOL) : false);

        return new self(
            apiKey: $apiKey,
            baseUrl: $baseUrl,
            timeout: (float) $timeout,
            retryConfig: $overrides['retryConfig'] ?? new RetryConfig(),
            cacheConfig: $overrides['cacheConfig'] ?? new CacheConfig(),
            rateLimitConfig: $overrides['rateLimitConfig'] ?? new RateLimitConfig(),
            debug: (bool) $debug,
            userAgent: $overrides['userAgent'] ?? null,
            additionalHeaders: $overrides['additionalHeaders'] ?? []
        );
    }

    /**
     * Get HTTP headers for API requests.
     *
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'User-Agent' => $this->getUserAgent()
        ];

        return array_merge($headers, $this->additionalHeaders);
    }

    /**
     * Get the user agent string.
     *
     * @return string
     */
    public function getUserAgent(): string
    {
        if ($this->userAgent !== null) {
            return $this->userAgent;
        }

        $phpVersion = PHP_VERSION;
        $sdkVersion = '0.1.0'; // TODO: Get from composer.json

        return "kra-connect-php/{$sdkVersion} (PHP {$phpVersion})";
    }

    /**
     * Check if debug mode is enabled.
     *
     * @return bool
     */
    public function isDebugEnabled(): bool
    {
        return $this->debug;
    }

    /**
     * Create a new configuration with updated values.
     *
     * @param array<string, mixed> $updates Configuration updates
     * @return self
     */
    public function with(array $updates): self
    {
        return new self(
            apiKey: $updates['apiKey'] ?? $this->apiKey,
            baseUrl: $updates['baseUrl'] ?? $this->baseUrl,
            timeout: $updates['timeout'] ?? $this->timeout,
            retryConfig: $updates['retryConfig'] ?? $this->retryConfig,
            cacheConfig: $updates['cacheConfig'] ?? $this->cacheConfig,
            rateLimitConfig: $updates['rateLimitConfig'] ?? $this->rateLimitConfig,
            debug: $updates['debug'] ?? $this->debug,
            userAgent: $updates['userAgent'] ?? $this->userAgent,
            additionalHeaders: $updates['additionalHeaders'] ?? $this->additionalHeaders
        );
    }

    /**
     * Convert configuration to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'api_key' => '***REDACTED***', // Never expose API key
            'base_url' => $this->baseUrl,
            'timeout' => $this->timeout,
            'retry_config' => [
                'max_retries' => $this->retryConfig->maxRetries,
                'initial_delay' => $this->retryConfig->initialDelay,
                'max_delay' => $this->retryConfig->maxDelay
            ],
            'cache_config' => [
                'enabled' => $this->cacheConfig->enabled,
                'ttl' => $this->cacheConfig->ttl
            ],
            'rate_limit_config' => [
                'enabled' => $this->rateLimitConfig->enabled,
                'max_requests' => $this->rateLimitConfig->maxRequests,
                'window_seconds' => $this->rateLimitConfig->windowSeconds
            ],
            'debug' => $this->debug,
            'user_agent' => $this->getUserAgent()
        ];
    }
}
