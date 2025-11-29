<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use KraConnect\Config\KraConfig;
use KraConnect\KraClient;
use KraConnect\Exceptions\InvalidPinFormatException;
use KraConnect\Exceptions\ApiException;

/**
 * Basic PIN Verification Example
 *
 * This example demonstrates how to verify a KRA PIN number using the PHP SDK.
 */

// Load environment variables (in production, use proper env management)
// You can use vlucas/phpdotenv package for .env file support
// require_once __DIR__ . '/../vendor/autoload.php';
// $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
// $dotenv->load();

try {
    // Option 1: Create client from environment variables
    // Make sure KRA_API_KEY is set in your environment
    $client = KraClient::fromEnv();

    // Option 2: Create client with explicit configuration
    // $config = new KraConfig(
    //     apiKey: 'your-api-key-here',
    //     baseUrl: 'https://api.kra.go.ke/gavaconnect/v1',
    //     timeout: 30.0
    // );
    // $client = new KraClient($config);

    echo "KRA PIN Verification Example\n";
    echo "============================\n\n";

    // PIN to verify
    $pinNumber = 'P051234567A';

    echo "Verifying PIN: {$pinNumber}\n\n";

    // Verify the PIN
    $result = $client->verifyPin($pinNumber);

    // Display results
    echo "Verification Result:\n";
    echo "-------------------\n";
    echo "PIN Number:      {$result->pinNumber}\n";
    echo "Is Valid:        " . ($result->isValid ? 'Yes' : 'No') . "\n";

    if ($result->isValid) {
        echo "Taxpayer Name:   " . ($result->taxpayerName ?? 'N/A') . "\n";
        echo "Status:          " . ($result->status ?? 'N/A') . "\n";
        echo "Taxpayer Type:   " . ($result->taxpayerType ?? 'N/A') . "\n";

        if ($result->registrationDate) {
            echo "Registered On:   {$result->registrationDate}\n";
        }

        // Additional checks
        if ($result->isActive()) {
            echo "\n✓ This PIN is currently active\n";
        }

        if ($result->isCompany()) {
            echo "✓ This is a company taxpayer\n";
        } elseif ($result->isIndividual()) {
            echo "✓ This is an individual taxpayer\n";
        }
    } else {
        echo "\n✗ This PIN is not valid or not found\n";
    }

    echo "\nVerified At: {$result->verifiedAt}\n";

    // Display cache stats
    echo "\n\nCache Statistics:\n";
    echo "----------------\n";
    $cacheStats = $client->getCacheStats();
    echo "Cache Enabled:   " . ($cacheStats['enabled'] ? 'Yes' : 'No') . "\n";
    echo "Cached Items:    {$cacheStats['valid_items']}\n";
    echo "Cache Usage:     {$cacheStats['usage_percentage']}%\n";

    // Display rate limit stats
    echo "\n\nRate Limit Statistics:\n";
    echo "---------------------\n";
    $rateLimitStats = $client->getRateLimitStats();
    echo "Rate Limiting:   " . ($rateLimitStats['enabled'] ? 'Enabled' : 'Disabled') . "\n";
    echo "Available Tokens: {$rateLimitStats['available_tokens']}/{$rateLimitStats['burst_size']}\n";
    echo "Requests/Second:  " . number_format($rateLimitStats['avg_requests_per_second'], 2) . "\n";

} catch (InvalidPinFormatException $e) {
    echo "\n❌ Invalid PIN Format:\n";
    echo $e->getMessage() . "\n";
    exit(1);
} catch (ApiException $e) {
    echo "\n❌ API Error:\n";
    echo $e->getMessage() . "\n";

    if ($e->getStatusCode()) {
        echo "HTTP Status Code: " . $e->getStatusCode() . "\n";
    }

    if ($e->getDetails()) {
        echo "\nError Details:\n";
        print_r($e->getDetails());
    }

    exit(1);
} catch (\Exception $e) {
    echo "\n❌ Unexpected Error:\n";
    echo $e->getMessage() . "\n";
    exit(1);
}

echo "\n✅ Example completed successfully!\n";
