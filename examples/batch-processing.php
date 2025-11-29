<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use KraConnect\Config\KraConfig;
use KraConnect\Config\CacheConfig;
use KraConnect\Config\RateLimitConfig;
use KraConnect\KraClient;
use KraConnect\Exceptions\KraConnectException;
use KraConnect\Utils\Formatter;

/**
 * Batch Processing Example
 *
 * This example demonstrates how to verify multiple PINs and TCCs efficiently
 * using batch processing with caching and rate limiting.
 */

try {
    echo "KRA Batch Processing Example\n";
    echo "============================\n\n";

    // Create client with custom configuration for batch processing
    $config = new KraConfig(
        apiKey: $_ENV['KRA_API_KEY'] ?? getenv('KRA_API_KEY') ?: 'your-api-key-here',
        cacheConfig: CacheConfig::aggressive(), // Use aggressive caching for batch
        rateLimitConfig: RateLimitConfig::lenient() // Use lenient rate limiting
    );

    $client = new KraClient($config);

    // ==================
    // Batch PIN Verification
    // ==================
    echo "1. Batch PIN Verification\n";
    echo "-------------------------\n";

    $pinsToVerify = [
        'P051234567A',
        'P051234567B',
        'P051234567C',
        'P051234567D',
        'P051234567E',
    ];

    echo "Verifying " . count($pinsToVerify) . " PINs...\n\n";

    $startTime = microtime(true);

    $pinResults = $client->verifyPinsBatch($pinsToVerify);

    $endTime = microtime(true);
    $duration = $endTime - $startTime;

    // Display results
    $validCount = 0;
    $invalidCount = 0;

    echo "Results:\n";
    foreach ($pinResults as $result) {
        $status = $result->isValid ? '✓ Valid' : '✗ Invalid';
        $name = $result->taxpayerName ? " - {$result->taxpayerName}" : '';

        echo sprintf(
            "  %s: %s%s\n",
            Formatter::maskPin($result->pinNumber),
            $status,
            $name
        );

        if ($result->isValid) {
            $validCount++;
        } else {
            $invalidCount++;
        }
    }

    echo "\nSummary:\n";
    echo "  Valid PINs:   {$validCount}\n";
    echo "  Invalid PINs: {$invalidCount}\n";
    echo "  Duration:     " . number_format($duration, 3) . "s\n";
    echo "  Avg per PIN:  " . number_format($duration / count($pinsToVerify), 3) . "s\n";

    // ==================
    // Batch TCC Verification
    // ==================
    echo "\n\n2. Batch TCC Verification\n";
    echo "-------------------------\n";

    $tccsToVerify = [
        'TCC123456',
        'TCC123457',
        'TCC123458',
    ];

    echo "Verifying " . count($tccsToVerify) . " TCCs...\n\n";

    $startTime = microtime(true);

    $tccResults = $client->verifyTccsBatch($tccsToVerify);

    $endTime = microtime(true);
    $duration = $endTime - $startTime;

    // Display results
    $validCount = 0;
    $expiredCount = 0;

    echo "Results:\n";
    foreach ($tccResults as $result) {
        $status = $result->isCurrentlyValid() ? '✓ Valid' : '✗ Invalid/Expired';
        $expiry = $result->expiryDate ? " (Expires: {$result->expiryDate})" : '';

        echo sprintf(
            "  %s: %s%s\n",
            Formatter::maskTcc($result->tccNumber),
            $status,
            $expiry
        );

        if ($result->isCurrentlyValid()) {
            $validCount++;
        }

        if ($result->isExpired) {
            $expiredCount++;
        }

        // Check if expiring soon
        if ($result->isExpiringSoon(30)) {
            echo "    ⚠ Expires in " . $result->getDaysUntilExpiry() . " days\n";
        }
    }

    echo "\nSummary:\n";
    echo "  Valid TCCs:   {$validCount}\n";
    echo "  Expired TCCs: {$expiredCount}\n";
    echo "  Duration:     " . number_format($duration, 3) . "s\n";

    // ==================
    // Batch E-slip Validation
    // ==================
    echo "\n\n3. Batch E-slip Validation\n";
    echo "--------------------------\n";

    $eslipsToValidate = [
        '1234567890',
        '1234567891',
        '1234567892',
    ];

    echo "Validating " . count($eslipsToValidate) . " e-slips...\n\n";

    $paidCount = 0;
    $pendingCount = 0;
    $totalAmount = 0.0;

    foreach ($eslipsToValidate as $eslip) {
        try {
            $result = $client->validateEslip($eslip);

            $status = $result->isPaid() ? '✓ Paid' : ($result->isPending() ? '⏳ Pending' : '✗ Not Paid');

            echo sprintf(
                "  %s: %s - %s\n",
                $eslip,
                $status,
                $result->getFormattedAmount()
            );

            if ($result->isPaid()) {
                $paidCount++;
                if ($result->amount) {
                    $totalAmount += $result->amount;
                }
            } elseif ($result->isPending()) {
                $pendingCount++;
            }
        } catch (KraConnectException $e) {
            echo "  {$eslip}: ✗ Error - {$e->getMessage()}\n";
        }
    }

    echo "\nSummary:\n";
    echo "  Paid:         {$paidCount}\n";
    echo "  Pending:      {$pendingCount}\n";
    echo "  Total Amount: " . Formatter::formatCurrency($totalAmount) . "\n";

    // ==================
    // System Statistics
    // ==================
    echo "\n\n4. System Statistics\n";
    echo "--------------------\n";

    $cacheStats = $client->getCacheStats();
    echo "Cache:\n";
    echo "  Enabled:      " . Formatter::formatBoolean($cacheStats['enabled']) . "\n";
    echo "  Total Items:  {$cacheStats['total_items']}\n";
    echo "  Valid Items:  {$cacheStats['valid_items']}\n";
    echo "  Expired:      {$cacheStats['expired_items']}\n";
    echo "  Usage:        {$cacheStats['usage_percentage']}%\n";

    echo "\nRate Limiter:\n";
    $rateLimitStats = $client->getRateLimitStats();
    echo "  Enabled:      " . Formatter::formatBoolean($rateLimitStats['enabled']) . "\n";
    echo "  Available:    {$rateLimitStats['available_tokens']}/{$rateLimitStats['burst_size']}\n";
    echo "  Max Req/s:    " . number_format($rateLimitStats['avg_requests_per_second'], 2) . "\n";
    echo "  Utilization:  {$rateLimitStats['utilization_percentage']}%\n";

} catch (KraConnectException $e) {
    echo "\n❌ Error:\n";
    echo $e->getMessage() . "\n";

    if ($e->getDetails()) {
        echo "\nDetails:\n";
        print_r($e->getDetails());
    }

    exit(1);
} catch (\Exception $e) {
    echo "\n❌ Unexpected Error:\n";
    echo $e->getMessage() . "\n";
    exit(1);
}

echo "\n✅ Batch processing completed successfully!\n";
