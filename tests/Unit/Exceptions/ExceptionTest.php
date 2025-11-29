<?php

declare(strict_types=1);

namespace KraConnect\Tests\Unit\Exceptions;

use KraConnect\Tests\TestCase;
use KraConnect\Exceptions\KraConnectException;
use KraConnect\Exceptions\InvalidPinFormatException;
use KraConnect\Exceptions\InvalidTccFormatException;
use KraConnect\Exceptions\ApiAuthenticationException;
use KraConnect\Exceptions\ApiTimeoutException;
use KraConnect\Exceptions\RateLimitExceededException;
use KraConnect\Exceptions\ApiException;
use KraConnect\Exceptions\ValidationException;
use KraConnect\Exceptions\CacheException;

class ExceptionTest extends TestCase
{
    public function testKraConnectExceptionWithMessage(): void
    {
        $exception = new KraConnectException('Test error');

        $this->assertSame('Test error', $exception->getMessage());
        $this->assertEmpty($exception->getDetails());
        $this->assertNull($exception->getStatusCode());
    }

    public function testKraConnectExceptionWithDetails(): void
    {
        $details = ['field' => 'value', 'code' => 123];
        $exception = new KraConnectException('Test error', $details, 400);

        $this->assertSame($details, $exception->getDetails());
        $this->assertSame(400, $exception->getStatusCode());
    }

    public function testInvalidPinFormatException(): void
    {
        $exception = new InvalidPinFormatException('INVALID');

        $this->assertStringContainsString('Invalid PIN format', $exception->getMessage());
        $this->assertStringContainsString('INVALID', $exception->getMessage());
        $this->assertArrayHasKey('pin_number', $exception->getDetails());
    }

    public function testInvalidTccFormatException(): void
    {
        $exception = new InvalidTccFormatException('INVALID');

        $this->assertStringContainsString('Invalid TCC format', $exception->getMessage());
        $this->assertStringContainsString('INVALID', $exception->getMessage());
        $this->assertArrayHasKey('tcc_number', $exception->getDetails());
    }

    public function testApiAuthenticationExceptionInvalidApiKey(): void
    {
        $exception = ApiAuthenticationException::invalidApiKey();

        $this->assertStringContainsString('Invalid API key', $exception->getMessage());
        $this->assertSame(401, $exception->getStatusCode());
    }

    public function testApiAuthenticationExceptionMissingApiKey(): void
    {
        $exception = ApiAuthenticationException::missingApiKey();

        $this->assertStringContainsString('API key is required', $exception->getMessage());
    }

    public function testApiTimeoutException(): void
    {
        $exception = new ApiTimeoutException('/test-endpoint', 30.0, 2);

        $this->assertSame('/test-endpoint', $exception->getEndpoint());
        $this->assertSame(30.0, $exception->getTimeout());
        $this->assertSame(2, $exception->getAttemptNumber());
        $this->assertSame(408, $exception->getStatusCode());
    }

    public function testRateLimitExceededException(): void
    {
        $exception = new RateLimitExceededException(60, 100, 60);

        $this->assertSame(60, $exception->getRetryAfter());
        $this->assertSame(100, $exception->getLimit());
        $this->assertSame(60, $exception->getWindowSeconds());
        $this->assertSame(429, $exception->getStatusCode());
        $this->assertGreaterThan(time(), $exception->getRetryTimestamp());
    }

    public function testApiExceptionFromResponse(): void
    {
        $exception = ApiException::fromResponse(
            500,
            '{"message": "Server error"}',
            '/test-endpoint'
        );

        $this->assertSame(500, $exception->getStatusCode());
        $this->assertTrue($exception->isServerError());
        $this->assertFalse($exception->isClientError());
    }

    public function testApiExceptionServerError(): void
    {
        $exception = ApiException::serverError('/test', 503);

        $this->assertTrue($exception->isServerError());
        $this->assertSame(503, $exception->getStatusCode());
    }

    public function testApiExceptionClientError(): void
    {
        $exception = ApiException::fromResponse(404, 'Not found', '/test');

        $this->assertTrue($exception->isClientError());
        $this->assertFalse($exception->isServerError());
    }

    public function testValidationExceptionForField(): void
    {
        $exception = ValidationException::forField('test_field', 'Field is invalid');

        $this->assertTrue($exception->hasFieldErrors('test_field'));
        $this->assertFalse($exception->hasFieldErrors('other_field'));
        $this->assertCount(1, $exception->getFieldErrors('test_field'));
        $this->assertSame(422, $exception->getStatusCode());
    }

    public function testValidationExceptionRequiredField(): void
    {
        $exception = ValidationException::requiredField('email');

        $this->assertStringContainsString('required', $exception->getMessage());
        $this->assertTrue($exception->hasFieldErrors('email'));
    }

    public function testValidationExceptionInvalidType(): void
    {
        $exception = ValidationException::invalidType('age', 'integer', 'string');

        $this->assertStringContainsString('integer', $exception->getMessage());
        $this->assertStringContainsString('string', $exception->getMessage());
    }

    public function testValidationExceptionGetAllErrorMessages(): void
    {
        $errors = [
            'field1' => ['Error 1', 'Error 2'],
            'field2' => ['Error 3']
        ];
        $exception = new ValidationException('Validation failed', $errors);

        $allMessages = $exception->getAllErrorMessages();

        $this->assertCount(3, $allMessages);
        $this->assertContains('Error 1', $allMessages);
        $this->assertContains('Error 2', $allMessages);
        $this->assertContains('Error 3', $allMessages);
    }

    public function testCacheExceptionReadFailed(): void
    {
        $exception = CacheException::readFailed('test-key', 'Connection lost');

        $this->assertStringContainsString('test-key', $exception->getMessage());
        $this->assertStringContainsString('Connection lost', $exception->getMessage());
        $this->assertSame('read', $exception->getOperation());
        $this->assertSame('test-key', $exception->getCacheKey());
    }

    public function testCacheExceptionWriteFailed(): void
    {
        $exception = CacheException::writeFailed('test-key', 'Disk full');

        $this->assertSame('write', $exception->getOperation());
    }

    public function testCacheExceptionSerializationFailed(): void
    {
        $exception = CacheException::serializationFailed('test-key', 'Invalid data');

        $this->assertSame('serialize', $exception->getOperation());
    }
}
