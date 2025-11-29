<?php

declare(strict_types=1);

namespace KraConnect\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * Base Test Case
 *
 * Provides common functionality for all tests.
 */
abstract class TestCase extends BaseTestCase
{
    /**
     * Setup before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Teardown after each test.
     */
    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Assert that an exception is thrown with specific message pattern.
     *
     * @param string $exceptionClass
     * @param callable $callback
     * @param string|null $messagePattern
     */
    protected function assertExceptionThrown(
        string $exceptionClass,
        callable $callback,
        ?string $messagePattern = null
    ): void {
        $this->expectException($exceptionClass);

        if ($messagePattern !== null) {
            $this->expectExceptionMessageMatches($messagePattern);
        }

        $callback();
    }
}
