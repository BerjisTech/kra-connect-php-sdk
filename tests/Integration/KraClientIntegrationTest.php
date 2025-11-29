<?php

declare(strict_types=1);

namespace KraConnect\Tests\Integration;

use KraConnect\Tests\TestCase;
use KraConnect\KraClient;
use KraConnect\Config\KraConfig;
use KraConnect\Config\CacheConfig;
use KraConnect\Config\RetryConfig;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Middleware;

/**
 * Integration tests for KraClient with mocked API responses.
 */
class KraClientIntegrationTest extends TestCase
{
    private array $requestHistory = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->requestHistory = [];
    }

    private function createMockedClient(array $responses): KraClient
    {
        $mock = new MockHandler($responses);

        $handlerStack = HandlerStack::create($mock);

        // Record requests for assertion
        $history = Middleware::history($this->requestHistory);
        $handlerStack->push($history);

        $config = new KraConfig(
            apiKey: 'test-api-key',
            cacheConfig: CacheConfig::disabled(), // Disable cache for integration tests
            retryConfig: RetryConfig::noRetry() // Disable retries for predictability
        );

        $client = new KraClient($config);

        // Replace the HTTP client's Guzzle client with our mocked one
        $httpClient = $client->getHttpClient();
        $reflection = new \ReflectionClass($httpClient);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($httpClient, new \GuzzleHttp\Client(['handler' => $handlerStack]));

        return $client;
    }

    public function testVerifyPinSuccessful(): void
    {
        $responseData = [
            'pin_number' => 'P051234567A',
            'is_valid' => true,
            'taxpayer_name' => 'John Doe',
            'status' => 'active',
            'taxpayer_type' => 'individual',
            'registration_date' => '2020-01-15',
            'verified_at' => '2024-01-15T10:30:00Z'
        ];

        $client = $this->createMockedClient([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($responseData))
        ]);

        $result = $client->verifyPin('P051234567A');

        $this->assertTrue($result->isValid);
        $this->assertSame('P051234567A', $result->pinNumber);
        $this->assertSame('John Doe', $result->taxpayerName);
        $this->assertSame('active', $result->status);
        $this->assertTrue($result->isActive());
        $this->assertTrue($result->isIndividual());

        // Assert request was made correctly
        $this->assertCount(1, $this->requestHistory);
        $request = $this->requestHistory[0]['request'];
        $this->assertSame('POST', $request->getMethod());
        $this->assertStringContainsString('/verify-pin', (string) $request->getUri());
    }

    public function testVerifyPinInvalid(): void
    {
        $responseData = [
            'pin_number' => 'P051234567Z',
            'is_valid' => false,
            'taxpayer_name' => null,
            'status' => null,
            'verified_at' => '2024-01-15T10:30:00Z'
        ];

        $client = $this->createMockedClient([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($responseData))
        ]);

        $result = $client->verifyPin('P051234567Z');

        $this->assertFalse($result->isValid);
        $this->assertSame('P051234567Z', $result->pinNumber);
        $this->assertNull($result->taxpayerName);
        $this->assertFalse($result->isActive());
    }

    public function testVerifyTccSuccessful(): void
    {
        $responseData = [
            'tcc_number' => 'TCC123456',
            'is_valid' => true,
            'taxpayer_name' => 'ABC Company Ltd',
            'pin_number' => 'P051234567A',
            'issue_date' => '2024-01-01',
            'expiry_date' => '2024-12-31',
            'is_expired' => false,
            'status' => 'active',
            'verified_at' => '2024-01-15T10:30:00Z'
        ];

        $client = $this->createMockedClient([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($responseData))
        ]);

        $result = $client->verifyTcc('TCC123456');

        $this->assertTrue($result->isValid);
        $this->assertSame('TCC123456', $result->tccNumber);
        $this->assertSame('ABC Company Ltd', $result->taxpayerName);
        $this->assertSame('P051234567A', $result->pinNumber);
        $this->assertFalse($result->isExpired);
        $this->assertTrue($result->isCurrentlyValid());
    }

    public function testValidateEslipSuccessful(): void
    {
        $responseData = [
            'eslip_number' => '1234567890',
            'is_valid' => true,
            'taxpayer_pin' => 'P051234567A',
            'taxpayer_name' => 'John Doe',
            'amount' => 50000.00,
            'currency' => 'KES',
            'payment_date' => '2024-01-10',
            'payment_reference' => 'REF123456',
            'obligation_type' => 'VAT',
            'obligation_period' => '202312',
            'status' => 'paid',
            'validated_at' => '2024-01-15T10:30:00Z'
        ];

        $client = $this->createMockedClient([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($responseData))
        ]);

        $result = $client->validateEslip('1234567890');

        $this->assertTrue($result->isValid);
        $this->assertSame('1234567890', $result->eslipNumber);
        $this->assertSame(50000.00, $result->amount);
        $this->assertSame('KES', $result->currency);
        $this->assertTrue($result->isPaid());
        $this->assertFalse($result->isPending());
        $this->assertSame('KES 50,000.00', $result->getFormattedAmount());
    }

    public function testFileNilReturnSuccessful(): void
    {
        $responseData = [
            'success' => true,
            'pin_number' => 'P051234567A',
            'obligation_id' => 'OBL123456',
            'period' => '202401',
            'reference_number' => 'NIL-2024-001',
            'filing_date' => '2024-01-15',
            'acknowledgement_number' => 'ACK123456',
            'status' => 'accepted',
            'message' => 'NIL return filed successfully',
            'filed_at' => '2024-01-15T10:30:00Z'
        ];

        $client = $this->createMockedClient([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($responseData))
        ]);

        $result = $client->fileNilReturn('P051234567A', 'OBL123456', '202401');

        $this->assertTrue($result->success);
        $this->assertSame('P051234567A', $result->pinNumber);
        $this->assertSame('OBL123456', $result->obligationId);
        $this->assertSame('202401', $result->period);
        $this->assertSame('NIL-2024-001', $result->referenceNumber);
        $this->assertTrue($result->isAccepted());
        $this->assertFalse($result->isRejected());
        $this->assertSame(2024, $result->getTaxYear());
        $this->assertSame(1, $result->getTaxMonth());
        $this->assertSame('January 2024', $result->getFormattedPeriod());
    }

    public function testGetTaxpayerDetailsSuccessful(): void
    {
        $responseData = [
            'pin_number' => 'P051234567A',
            'taxpayer_name' => 'John Doe',
            'taxpayer_type' => 'individual',
            'status' => 'active',
            'registration_date' => '2020-01-15',
            'business_name' => null,
            'trading_name' => null,
            'postal_address' => 'P.O. Box 12345, Nairobi',
            'physical_address' => '123 Main Street, Nairobi',
            'email_address' => 'john.doe@example.com',
            'phone_number' => '+254712345678',
            'obligations' => [
                [
                    'obligation_id' => 'OBL-VAT-001',
                    'obligation_type' => 'VAT',
                    'description' => 'Value Added Tax',
                    'status' => 'active',
                    'registration_date' => '2020-01-15',
                    'frequency' => 'monthly',
                    'next_filing_date' => '2024-02-20',
                    'is_active' => true
                ],
                [
                    'obligation_id' => 'OBL-PAYE-001',
                    'obligation_type' => 'PAYE',
                    'description' => 'Pay As You Earn',
                    'status' => 'active',
                    'registration_date' => '2020-01-15',
                    'frequency' => 'monthly',
                    'next_filing_date' => '2024-02-09',
                    'is_active' => true
                ]
            ],
            'retrieved_at' => '2024-01-15T10:30:00Z'
        ];

        $client = $this->createMockedClient([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($responseData))
        ]);

        $result = $client->getTaxpayerDetails('P051234567A');

        $this->assertSame('P051234567A', $result->pinNumber);
        $this->assertSame('John Doe', $result->taxpayerName);
        $this->assertTrue($result->isActive());
        $this->assertTrue($result->isIndividual());
        $this->assertFalse($result->isCompany());
        $this->assertCount(2, $result->obligations);
        $this->assertTrue($result->hasObligation('VAT'));
        $this->assertTrue($result->hasObligation('PAYE'));
        $this->assertFalse($result->hasObligation('Income Tax'));
        $this->assertSame('John Doe', $result->getDisplayName());

        // Test obligations
        $vatObligations = $result->getObligationsByType('VAT');
        $this->assertCount(1, $vatObligations);
        $this->assertSame('OBL-VAT-001', $vatObligations[0]->obligationId);
    }

    public function testBatchPinVerification(): void
    {
        $client = $this->createMockedClient([
            new Response(200, [], json_encode([
                'pin_number' => 'P051234567A',
                'is_valid' => true,
                'taxpayer_name' => 'John Doe'
            ])),
            new Response(200, [], json_encode([
                'pin_number' => 'P051234567B',
                'is_valid' => false,
                'taxpayer_name' => null
            ])),
            new Response(200, [], json_encode([
                'pin_number' => 'P051234567C',
                'is_valid' => true,
                'taxpayer_name' => 'Jane Smith'
            ]))
        ]);

        $results = $client->verifyPinsBatch([
            'P051234567A',
            'P051234567B',
            'P051234567C'
        ]);

        $this->assertCount(3, $results);
        $this->assertTrue($results[0]->isValid);
        $this->assertFalse($results[1]->isValid);
        $this->assertTrue($results[2]->isValid);
        $this->assertCount(3, $this->requestHistory);
    }

    public function testCacheStatistics(): void
    {
        // Enable cache for this test
        $config = new KraConfig(
            apiKey: 'test-api-key',
            cacheConfig: new CacheConfig(enabled: true, ttl: 3600)
        );
        $client = new KraClient($config);

        $stats = $client->getCacheStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('enabled', $stats);
        $this->assertArrayHasKey('total_items', $stats);
        $this->assertArrayHasKey('valid_items', $stats);
        $this->assertTrue($stats['enabled']);
    }

    public function testRateLimitStatistics(): void
    {
        $config = new KraConfig(apiKey: 'test-api-key');
        $client = new KraClient($config);

        $stats = $client->getRateLimitStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('enabled', $stats);
        $this->assertArrayHasKey('available_tokens', $stats);
        $this->assertArrayHasKey('burst_size', $stats);
    }

    public function testClearCache(): void
    {
        $config = new KraConfig(
            apiKey: 'test-api-key',
            cacheConfig: new CacheConfig(enabled: true)
        );
        $client = new KraClient($config);

        $result = $client->clearCache();

        $this->assertTrue($result);
    }
}
