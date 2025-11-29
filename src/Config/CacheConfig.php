<?php

declare(strict_types=1);

namespace KraConnect\Config;

/**
 * Cache Configuration
 *
 * Configuration for caching API responses.
 *
 * @package KraConnect\Config
 */
class CacheConfig
{
    /**
     * Create a new cache configuration.
     *
     * @param bool $enabled Whether caching is enabled
     * @param int $ttl Default Time-To-Live in seconds
     * @param int $pinVerificationTtl TTL for PIN verification results (seconds)
     * @param int $tccVerificationTtl TTL for TCC verification results (seconds)
     * @param int $eslipValidationTtl TTL for e-slip validation results (seconds)
     * @param int $taxpayerDetailsTtl TTL for taxpayer details (seconds)
     * @param string $prefix Cache key prefix
     * @param int $maxSize Maximum number of items in cache (for in-memory cache)
     */
    public function __construct(
        public readonly bool $enabled = true,
        public readonly int $ttl = 3600,
        public readonly int $pinVerificationTtl = 3600,
        public readonly int $tccVerificationTtl = 1800,
        public readonly int $eslipValidationTtl = 3600,
        public readonly int $taxpayerDetailsTtl = 7200,
        public readonly string $prefix = 'kra_connect:',
        public readonly int $maxSize = 1000
    ) {
        if ($ttl < 0) {
            throw new \InvalidArgumentException('TTL must be >= 0');
        }

        if ($maxSize <= 0) {
            throw new \InvalidArgumentException('maxSize must be > 0');
        }
    }

    /**
     * Create a configuration with caching disabled.
     *
     * @return self
     */
    public static function disabled(): self
    {
        return new self(enabled: false);
    }

    /**
     * Create a configuration with aggressive caching (longer TTLs).
     *
     * @return self
     */
    public static function aggressive(): self
    {
        return new self(
            enabled: true,
            ttl: 7200,
            pinVerificationTtl: 7200,
            tccVerificationTtl: 3600,
            eslipValidationTtl: 7200,
            taxpayerDetailsTtl: 14400
        );
    }

    /**
     * Create a configuration with conservative caching (shorter TTLs).
     *
     * @return self
     */
    public static function conservative(): self
    {
        return new self(
            enabled: true,
            ttl: 300,
            pinVerificationTtl: 300,
            tccVerificationTtl: 300,
            eslipValidationTtl: 300,
            taxpayerDetailsTtl: 600
        );
    }

    /**
     * Get the TTL for a specific cache type.
     *
     * @param string $cacheType The cache type (e.g., 'pin_verification', 'tcc_verification')
     * @return int TTL in seconds
     */
    public function getTtlForType(string $cacheType): int
    {
        return match ($cacheType) {
            'pin_verification' => $this->pinVerificationTtl,
            'tcc_verification' => $this->tccVerificationTtl,
            'eslip_validation' => $this->eslipValidationTtl,
            'taxpayer_details' => $this->taxpayerDetailsTtl,
            default => $this->ttl
        };
    }

    /**
     * Generate a cache key with the configured prefix.
     *
     * @param string $key The base key
     * @return string The full cache key with prefix
     */
    public function generateKey(string $key): string
    {
        return $this->prefix . $key;
    }

    /**
     * Check if caching is enabled.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
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
            enabled: $updates['enabled'] ?? $this->enabled,
            ttl: $updates['ttl'] ?? $this->ttl,
            pinVerificationTtl: $updates['pinVerificationTtl'] ?? $this->pinVerificationTtl,
            tccVerificationTtl: $updates['tccVerificationTtl'] ?? $this->tccVerificationTtl,
            eslipValidationTtl: $updates['eslipValidationTtl'] ?? $this->eslipValidationTtl,
            taxpayerDetailsTtl: $updates['taxpayerDetailsTtl'] ?? $this->taxpayerDetailsTtl,
            prefix: $updates['prefix'] ?? $this->prefix,
            maxSize: $updates['maxSize'] ?? $this->maxSize
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
            'enabled' => $this->enabled,
            'ttl' => $this->ttl,
            'pin_verification_ttl' => $this->pinVerificationTtl,
            'tcc_verification_ttl' => $this->tccVerificationTtl,
            'eslip_validation_ttl' => $this->eslipValidationTtl,
            'taxpayer_details_ttl' => $this->taxpayerDetailsTtl,
            'prefix' => $this->prefix,
            'max_size' => $this->maxSize
        ];
    }
}
