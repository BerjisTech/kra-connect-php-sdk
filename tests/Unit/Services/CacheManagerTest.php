<?php

declare(strict_types=1);

namespace KraConnect\Tests\Unit\Services;

use KraConnect\Tests\TestCase;
use KraConnect\Services\CacheManager;
use KraConnect\Config\CacheConfig;

class CacheManagerTest extends TestCase
{
    private CacheManager $cacheManager;

    protected function setUp(): void
    {
        parent::setUp();
        $config = new CacheConfig(enabled: true, ttl: 3600);
        $this->cacheManager = new CacheManager($config);
    }

    public function testSetAndGet(): void
    {
        $this->cacheManager->set('test-key', 'test-value');

        $value = $this->cacheManager->get('test-key');

        $this->assertSame('test-value', $value);
    }

    public function testGetNonExistentKey(): void
    {
        $value = $this->cacheManager->get('non-existent');

        $this->assertNull($value);
    }

    public function testSetWithTtl(): void
    {
        $this->cacheManager->set('test-key', 'test-value', 1);

        $this->assertSame('test-value', $this->cacheManager->get('test-key'));

        // Wait for expiration
        sleep(2);

        $this->assertNull($this->cacheManager->get('test-key'));
    }

    public function testGetOrSet(): void
    {
        $callCount = 0;
        $factory = function () use (&$callCount) {
            $callCount++;
            return 'generated-value';
        };

        // First call should execute factory
        $value1 = $this->cacheManager->getOrSet('test-key', $factory);
        $this->assertSame('generated-value', $value1);
        $this->assertSame(1, $callCount);

        // Second call should use cache
        $value2 = $this->cacheManager->getOrSet('test-key', $factory);
        $this->assertSame('generated-value', $value2);
        $this->assertSame(1, $callCount); // Factory not called again
    }

    public function testDelete(): void
    {
        $this->cacheManager->set('test-key', 'test-value');

        $deleted = $this->cacheManager->delete('test-key');

        $this->assertTrue($deleted);
        $this->assertNull($this->cacheManager->get('test-key'));
    }

    public function testDeleteNonExistent(): void
    {
        $deleted = $this->cacheManager->delete('non-existent');

        $this->assertFalse($deleted);
    }

    public function testHas(): void
    {
        $this->cacheManager->set('test-key', 'test-value');

        $this->assertTrue($this->cacheManager->has('test-key'));
        $this->assertFalse($this->cacheManager->has('non-existent'));
    }

    public function testClear(): void
    {
        $this->cacheManager->set('key1', 'value1');
        $this->cacheManager->set('key2', 'value2');

        $result = $this->cacheManager->clear();

        $this->assertTrue($result);
        $this->assertNull($this->cacheManager->get('key1'));
        $this->assertNull($this->cacheManager->get('key2'));
        $this->assertSame(0, $this->cacheManager->count());
    }

    public function testClearExpired(): void
    {
        $this->cacheManager->set('key1', 'value1', 1);
        $this->cacheManager->set('key2', 'value2', 3600);

        sleep(2);

        $cleared = $this->cacheManager->clearExpired();

        $this->assertSame(1, $cleared);
        $this->assertNull($this->cacheManager->get('key1'));
        $this->assertSame('value2', $this->cacheManager->get('key2'));
    }

    public function testGenerateKey(): void
    {
        $key = $this->cacheManager->generateKey('pin_verification', [
            'pin' => 'P051234567A'
        ]);

        $this->assertStringStartsWith('pin_verification:', $key);
        $this->assertIsString($key);

        // Same params should generate same key
        $key2 = $this->cacheManager->generateKey('pin_verification', [
            'pin' => 'P051234567A'
        ]);

        $this->assertSame($key, $key2);
    }

    public function testGetStats(): void
    {
        $this->cacheManager->set('key1', 'value1');
        $this->cacheManager->set('key2', 'value2', 1);

        sleep(2);

        $stats = $this->cacheManager->getStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('enabled', $stats);
        $this->assertArrayHasKey('total_items', $stats);
        $this->assertArrayHasKey('valid_items', $stats);
        $this->assertArrayHasKey('expired_items', $stats);
        $this->assertTrue($stats['enabled']);
    }

    public function testCount(): void
    {
        $this->assertSame(0, $this->cacheManager->count());

        $this->cacheManager->set('key1', 'value1');
        $this->assertSame(1, $this->cacheManager->count());

        $this->cacheManager->set('key2', 'value2');
        $this->assertSame(2, $this->cacheManager->count());

        $this->cacheManager->delete('key1');
        $this->assertSame(1, $this->cacheManager->count());
    }

    public function testWarmUp(): void
    {
        $items = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        ];

        $cached = $this->cacheManager->warmUp($items);

        $this->assertSame(3, $cached);
        $this->assertSame('value1', $this->cacheManager->get('key1'));
        $this->assertSame('value2', $this->cacheManager->get('key2'));
        $this->assertSame('value3', $this->cacheManager->get('key3'));
    }

    public function testDisabledCache(): void
    {
        $config = new CacheConfig(enabled: false);
        $manager = new CacheManager($config);

        $result = $manager->set('test', 'value');

        $this->assertFalse($result);
        $this->assertNull($manager->get('test'));
    }
}
