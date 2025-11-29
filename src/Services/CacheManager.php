<?php

declare(strict_types=1);

namespace KraConnect\Services;

use KraConnect\Config\CacheConfig;
use KraConnect\Exceptions\CacheException;

/**
 * Cache Manager Service
 *
 * Provides caching functionality for API responses using in-memory storage.
 * For production use with persistent cache (Redis, Memcached), implement PSR-6 CacheItemPoolInterface.
 *
 * @package KraConnect\Services
 */
class CacheManager
{
    /** @var array<string, array{value: mixed, expires_at: int}> */
    private array $cache = [];

    /**
     * Create a new cache manager.
     *
     * @param CacheConfig $config The cache configuration
     */
    public function __construct(
        private readonly CacheConfig $config
    ) {
    }

    /**
     * Get a value from the cache.
     *
     * @template T
     * @param string $key The cache key
     * @return T|null The cached value, or null if not found or expired
     */
    public function get(string $key)
    {
        if (!$this->config->isEnabled()) {
            return null;
        }

        $fullKey = $this->config->generateKey($key);

        if (!isset($this->cache[$fullKey])) {
            return null;
        }

        $item = $this->cache[$fullKey];

        // Check if expired
        if ($item['expires_at'] < time()) {
            unset($this->cache[$fullKey]);
            return null;
        }

        return $item['value'];
    }

    /**
     * Store a value in the cache.
     *
     * @param string $key The cache key
     * @param mixed $value The value to cache
     * @param int|null $ttl Time-to-live in seconds (null uses default from config)
     * @return bool True on success, false on failure
     */
    public function set(string $key, $value, ?int $ttl = null): bool
    {
        if (!$this->config->isEnabled()) {
            return false;
        }

        try {
            $fullKey = $this->config->generateKey($key);
            $ttl = $ttl ?? $this->config->ttl;

            // Enforce max cache size (simple LRU: remove oldest)
            if (count($this->cache) >= $this->config->maxSize) {
                $this->evictOldest();
            }

            $this->cache[$fullKey] = [
                'value' => $value,
                'expires_at' => time() + $ttl
            ];

            return true;
        } catch (\Exception $e) {
            throw CacheException::writeFailed($key, $e->getMessage());
        }
    }

    /**
     * Get a value from cache, or generate and cache it if not found.
     *
     * @template T
     * @param string $key The cache key
     * @param callable(): T $factoryFn Function to generate the value if not cached
     * @param int|null $ttl Time-to-live in seconds
     * @return T The cached or generated value
     */
    public function getOrSet(string $key, callable $factoryFn, ?int $ttl = null)
    {
        $cached = $this->get($key);

        if ($cached !== null) {
            return $cached;
        }

        $value = $factoryFn();
        $this->set($key, $value, $ttl);

        return $value;
    }

    /**
     * Delete a value from the cache.
     *
     * @param string $key The cache key
     * @return bool True if deleted, false if not found
     */
    public function delete(string $key): bool
    {
        $fullKey = $this->config->generateKey($key);

        if (isset($this->cache[$fullKey])) {
            unset($this->cache[$fullKey]);
            return true;
        }

        return false;
    }

    /**
     * Check if a key exists in the cache and is not expired.
     *
     * @param string $key The cache key
     * @return bool True if exists and not expired
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * Clear all cached items.
     *
     * @return bool True on success
     */
    public function clear(): bool
    {
        $this->cache = [];
        return true;
    }

    /**
     * Clear expired items from the cache.
     *
     * @return int Number of items cleared
     */
    public function clearExpired(): int
    {
        $count = 0;
        $now = time();

        foreach ($this->cache as $key => $item) {
            if ($item['expires_at'] < $now) {
                unset($this->cache[$key]);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Generate a cache key for a specific cache type.
     *
     * @param string $type The cache type (e.g., 'pin_verification')
     * @param array<string, mixed> $params Parameters to include in the key
     * @return string The generated cache key
     */
    public function generateKey(string $type, array $params = []): string
    {
        ksort($params); // Sort for consistency
        $paramString = http_build_query($params);

        return sprintf('%s:%s', $type, md5($paramString));
    }

    /**
     * Get cache statistics.
     *
     * @return array<string, mixed> Cache statistics
     */
    public function getStats(): array
    {
        $now = time();
        $expired = 0;
        $valid = 0;

        foreach ($this->cache as $item) {
            if ($item['expires_at'] < $now) {
                $expired++;
            } else {
                $valid++;
            }
        }

        return [
            'enabled' => $this->config->isEnabled(),
            'total_items' => count($this->cache),
            'valid_items' => $valid,
            'expired_items' => $expired,
            'max_size' => $this->config->maxSize,
            'usage_percentage' => round((count($this->cache) / $this->config->maxSize) * 100, 2)
        ];
    }

    /**
     * Evict the oldest item from the cache (simple LRU).
     *
     * @return void
     */
    private function evictOldest(): void
    {
        if (empty($this->cache)) {
            return;
        }

        // Remove the first item (oldest based on insertion order)
        $firstKey = array_key_first($this->cache);
        unset($this->cache[$firstKey]);
    }

    /**
     * Get the number of items in the cache.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->cache);
    }

    /**
     * Get the cache configuration.
     *
     * @return CacheConfig
     */
    public function getConfig(): CacheConfig
    {
        return $this->config;
    }

    /**
     * Warm up the cache with multiple items.
     *
     * @param array<string, mixed> $items Key-value pairs to cache
     * @param int|null $ttl Time-to-live in seconds
     * @return int Number of items successfully cached
     */
    public function warmUp(array $items, ?int $ttl = null): int
    {
        $count = 0;

        foreach ($items as $key => $value) {
            if ($this->set($key, $value, $ttl)) {
                $count++;
            }
        }

        return $count;
    }
}
