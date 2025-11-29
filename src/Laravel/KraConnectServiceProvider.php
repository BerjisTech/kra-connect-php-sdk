<?php

declare(strict_types=1);

namespace KraConnect\Laravel;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;
use KraConnect\KraClient;
use KraConnect\Config\KraConfig;
use KraConnect\Config\RetryConfig;
use KraConnect\Config\CacheConfig;
use KraConnect\Config\RateLimitConfig;

/**
 * Laravel Service Provider for KRA Connect
 *
 * @package KraConnect\Laravel
 */
class KraConnectServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__ . '/config/kra-connect.php',
            'kra-connect'
        );

        // Register KraClient as singleton
        $this->app->singleton(KraClient::class, function ($app) {
            $config = $app['config']['kra-connect'];

            $kraConfig = new KraConfig(
                apiKey: $config['api_key'] ?? throw new \RuntimeException('KRA API key not configured'),
                baseUrl: $config['base_url'] ?? 'https://api.kra.go.ke/gavaconnect/v1',
                timeout: $config['timeout'] ?? 30.0,
                retryConfig: $this->createRetryConfig($config['retry'] ?? []),
                cacheConfig: $this->createCacheConfig($config['cache'] ?? []),
                rateLimitConfig: $this->createRateLimitConfig($config['rate_limit'] ?? []),
                debug: $config['debug'] ?? false,
                userAgent: $config['user_agent'] ?? null,
                additionalHeaders: $config['additional_headers'] ?? []
            );

            return new KraClient($kraConfig);
        });

        // Register alias
        $this->app->alias(KraClient::class, 'kra-connect');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish configuration
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/config/kra-connect.php' => config_path('kra-connect.php'),
            ], 'kra-connect-config');
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<string>
     */
    public function provides(): array
    {
        return [KraClient::class, 'kra-connect'];
    }

    /**
     * Create retry configuration from array.
     *
     * @param array<string, mixed> $config
     * @return RetryConfig
     */
    private function createRetryConfig(array $config): RetryConfig
    {
        return new RetryConfig(
            maxRetries: $config['max_retries'] ?? 3,
            initialDelay: $config['initial_delay'] ?? 1.0,
            maxDelay: $config['max_delay'] ?? 32.0,
            backoffMultiplier: $config['backoff_multiplier'] ?? 2.0,
            retryOnTimeout: $config['retry_on_timeout'] ?? true,
            retryOnServerError: $config['retry_on_server_error'] ?? true,
            retryOnRateLimit: $config['retry_on_rate_limit'] ?? true,
            retryableStatusCodes: $config['retryable_status_codes'] ?? [408, 429, 500, 502, 503, 504]
        );
    }

    /**
     * Create cache configuration from array.
     *
     * @param array<string, mixed> $config
     * @return CacheConfig
     */
    private function createCacheConfig(array $config): CacheConfig
    {
        return new CacheConfig(
            enabled: $config['enabled'] ?? true,
            ttl: $config['ttl'] ?? 3600,
            pinVerificationTtl: $config['pin_verification_ttl'] ?? 3600,
            tccVerificationTtl: $config['tcc_verification_ttl'] ?? 1800,
            eslipValidationTtl: $config['eslip_validation_ttl'] ?? 3600,
            taxpayerDetailsTtl: $config['taxpayer_details_ttl'] ?? 7200,
            prefix: $config['prefix'] ?? 'kra_connect:',
            maxSize: $config['max_size'] ?? 1000
        );
    }

    /**
     * Create rate limit configuration from array.
     *
     * @param array<string, mixed> $config
     * @return RateLimitConfig
     */
    private function createRateLimitConfig(array $config): RateLimitConfig
    {
        return new RateLimitConfig(
            enabled: $config['enabled'] ?? true,
            maxRequests: $config['max_requests'] ?? 100,
            windowSeconds: $config['window_seconds'] ?? 60,
            blockOnLimit: $config['block_on_limit'] ?? true
        );
    }
}
