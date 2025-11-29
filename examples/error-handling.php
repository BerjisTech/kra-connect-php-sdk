<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use KraConnect\Config\KraConfig;
use KraConnect\Config\RetryConfig;
use KraConnect\KraClient;
use KraConnect\Exceptions\InvalidPinFormatException;
use KraConnect\Exceptions\InvalidTccFormatException;
use KraConnect\Exceptions\ApiAuthenticationException;
use KraConnect\Exceptions\ApiTimeoutException;
use KraConnect\Exceptions\RateLimitExceededException;
use KraConnect\Exceptions\ApiException;
use KraConnect\Exceptions\ValidationException;
use KraConnect\Exceptions\KraConnectException;

/**
 * Error Handling Example
 *
 * This example demonstrates comprehensive error handling patterns
 * for various exception types that can occur when using the SDK.
 */

echo "KRA Error Handling Example\n";
echo "==========================\n\n";

// ==================
// 1. Validation Errors
// ==================
echo "1. Handling Validation Errors\n";
echo "-----------------------------\n";

try {
    $config = new KraConfig(
        apiKey: $_ENV['KRA_API_KEY'] ?? getenv('KRA_API_KEY') ?: 'your-api-key-here'
    );
    $client = new KraClient($config);

    // Invalid PIN format
    echo "Attempting to verify invalid PIN format...\n";
    $result = $client->verifyPin('INVALID');

} catch (InvalidPinFormatException $e) {
    echo "✓ Caught InvalidPinFormatException\n";
    echo "  Message: {$e->getMessage()}\n";
    echo "  Details: " . json_encode($e->getDetails()) . "\n";
}

echo "\n";

try {
    // Invalid TCC format
    echo "Attempting to verify invalid TCC format...\n";
    $result = $client->verifyTcc('INVALID');

} catch (InvalidTccFormatException $e) {
    echo "✓ Caught InvalidTccFormatException\n";
    echo "  Message: {$e->getMessage()}\n";
}

echo "\n";

try {
    // Invalid period format
    echo "Attempting to file NIL return with invalid period...\n";
    $result = $client->fileNilReturn('P051234567A', 'OBL123', 'INVALID');

} catch (ValidationException $e) {
    echo "✓ Caught ValidationException\n";
    echo "  Message: {$e->getMessage()}\n";
    echo "  Errors: " . json_encode($e->getErrors()) . "\n";
}

// ==================
// 2. Authentication Errors
// ==================
echo "\n\n2. Handling Authentication Errors\n";
echo "----------------------------------\n";

try {
    // Create client with invalid API key
    $invalidConfig = new KraConfig(apiKey: 'invalid-api-key');
    $invalidClient = new KraClient($invalidConfig);

    echo "Attempting request with invalid API key...\n";
    $result = $invalidClient->verifyPin('P051234567A');

} catch (ApiAuthenticationException $e) {
    echo "✓ Caught ApiAuthenticationException\n";
    echo "  Message: {$e->getMessage()}\n";
    echo "  Status Code: {$e->getStatusCode()}\n";
    echo "\nSuggestion: Check your API key in the environment variables\n";
}

// ==================
// 3. Timeout Errors
// ==================
echo "\n\n3. Handling Timeout Errors\n";
echo "--------------------------\n";

try {
    // Create client with very short timeout
    $timeoutConfig = new KraConfig(
        apiKey: $_ENV['KRA_API_KEY'] ?? 'test-key',
        timeout: 0.001 // Extremely short timeout to force error
    );
    $timeoutClient = new KraClient($timeoutConfig);

    echo "Attempting request with very short timeout...\n";
    $result = $timeoutClient->verifyPin('P051234567A');

} catch (ApiTimeoutException $e) {
    echo "✓ Caught ApiTimeoutException\n";
    echo "  Message: {$e->getMessage()}\n";
    echo "  Endpoint: {$e->getEndpoint()}\n";
    echo "  Timeout: {$e->getTimeout()}s\n";
    echo "  Attempt: {$e->getAttemptNumber()}\n";
    echo "\nSuggestion: Increase timeout or check network connection\n";
}

// ==================
// 4. Rate Limit Errors
// ==================
echo "\n\n4. Handling Rate Limit Errors\n";
echo "------------------------------\n";

echo "Simulating rate limit exceeded...\n";
echo "(In production, this would happen when making too many requests)\n";

try {
    // Create a rate limit exception manually for demonstration
    throw new RateLimitExceededException(60, 100, 60);

} catch (RateLimitExceededException $e) {
    echo "✓ Caught RateLimitExceededException\n";
    echo "  Message: {$e->getMessage()}\n";
    echo "  Retry After: {$e->getRetryAfter()}s\n";
    echo "  Limit: {$e->getLimit()} requests per {$e->getWindowSeconds()}s\n";
    echo "  Retry At: " . date('Y-m-d H:i:s', $e->getRetryTimestamp()) . "\n";
    echo "\nSuggestion: Wait {$e->getRetryAfter()} seconds before retrying\n";
}

// ==================
// 5. General API Errors
// ==================
echo "\n\n5. Handling General API Errors\n";
echo "-------------------------------\n";

try {
    // Simulate server error
    throw ApiException::serverError('/test-endpoint', 503);

} catch (ApiException $e) {
    echo "✓ Caught ApiException\n";
    echo "  Message: {$e->getMessage()}\n";
    echo "  Status Code: {$e->getStatusCode()}\n";
    echo "  Is Server Error: " . ($e->isServerError() ? 'Yes' : 'No') . "\n";
    echo "  Is Client Error: " . ($e->isClientError() ? 'Yes' : 'No') . "\n";

    if ($e->isServerError()) {
        echo "\nSuggestion: This is a server error. The request will be automatically retried.\n";
    }
}

// ==================
// 6. Retry Logic
// ==================
echo "\n\n6. Automatic Retry Logic\n";
echo "------------------------\n";

try {
    $retryConfig = new KraConfig(
        apiKey: $_ENV['KRA_API_KEY'] ?? 'test-key',
        retryConfig: new RetryConfig(
            maxRetries: 3,
            initialDelay: 1.0,
            maxDelay: 10.0
        )
    );
    $retryClient = new KraClient($retryConfig);

    echo "SDK will automatically retry failed requests up to 3 times\n";
    echo "with exponential backoff (1s, 2s, 4s delays).\n\n";

    echo "Retryable errors include:\n";
    echo "  - Timeout errors (408)\n";
    echo "  - Server errors (500, 502, 503, 504)\n";
    echo "  - Rate limit errors (429)\n";

} catch (\Exception $e) {
    echo "Error: {$e->getMessage()}\n";
}

// ==================
// 7. Catch-All Error Handler
// ==================
echo "\n\n7. Catch-All Error Handler\n";
echo "--------------------------\n";

function handleKraOperation(callable $operation): void
{
    try {
        $operation();
        echo "✓ Operation completed successfully\n";

    } catch (InvalidPinFormatException | InvalidTccFormatException $e) {
        echo "❌ Validation Error: {$e->getMessage()}\n";
        echo "   Please check the format of your input\n";

    } catch (ApiAuthenticationException $e) {
        echo "❌ Authentication Error: {$e->getMessage()}\n";
        echo "   Please check your API key\n";

    } catch (ApiTimeoutException $e) {
        echo "❌ Timeout Error: Request took too long\n";
        echo "   Try again or increase timeout\n";

    } catch (RateLimitExceededException $e) {
        echo "❌ Rate Limit Exceeded\n";
        echo "   Wait {$e->getRetryAfter()} seconds before retrying\n";

    } catch (ApiException $e) {
        echo "❌ API Error: {$e->getMessage()}\n";
        if ($e->getStatusCode()) {
            echo "   HTTP Status: {$e->getStatusCode()}\n";
        }

    } catch (KraConnectException $e) {
        echo "❌ KRA Connect Error: {$e->getMessage()}\n";
        if ($e->getDetails()) {
            echo "   Details: " . json_encode($e->getDetails()) . "\n";
        }

    } catch (\Exception $e) {
        echo "❌ Unexpected Error: {$e->getMessage()}\n";
        echo "   Please report this issue\n";
    }
}

echo "Example: Wrapping operations with error handler\n\n";

$config = new KraConfig(
    apiKey: $_ENV['KRA_API_KEY'] ?? 'test-key'
);
$client = new KraClient($config);

handleKraOperation(function () use ($client) {
    // This would normally make a real API call
    echo "Simulated operation...\n";
});

echo "\n✅ Error handling examples completed!\n";
echo "\nKey Takeaways:\n";
echo "1. Always catch specific exceptions first, then fall back to general ones\n";
echo "2. Use InvalidPinFormatException and InvalidTccFormatException for validation\n";
echo "3. ApiAuthenticationException indicates API key issues\n";
echo "4. ApiTimeoutException occurs when requests take too long\n";
echo "5. RateLimitExceededException includes retry-after information\n";
echo "6. The SDK automatically retries certain errors\n";
echo "7. All exceptions extend KraConnectException for easy catch-all handling\n";
