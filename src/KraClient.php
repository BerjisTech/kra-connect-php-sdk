<?php

declare(strict_types=1);

namespace KraConnect;

use KraConnect\Config\KraConfig;
use KraConnect\Models\EslipValidationResult;
use KraConnect\Models\NilReturnResult;
use KraConnect\Models\PinVerificationResult;
use KraConnect\Models\TaxpayerDetails;
use KraConnect\Models\TccVerificationResult;
use KraConnect\Services\CacheManager;
use KraConnect\Services\HttpClient;
use KraConnect\Services\RateLimiter;
use KraConnect\Services\RetryHandler;
use KraConnect\Utils\Validator;

/**
 * KRA Connect Client
 *
 * Main client class for interacting with the Kenya Revenue Authority GavaConnect API.
 *
 * @package KraConnect
 */
class KraClient
{
    private HttpClient $httpClient;
    private CacheManager $cacheManager;
    private RateLimiter $rateLimiter;
    private RetryHandler $retryHandler;

    /**
     * Create a new KRA client instance.
     *
     * @param KraConfig $config The SDK configuration
     */
    public function __construct(
        private readonly KraConfig $config
    ) {
        $this->cacheManager = new CacheManager($config->cacheConfig);
        $this->rateLimiter = new RateLimiter($config->rateLimitConfig);
        $this->retryHandler = new RetryHandler($config->retryConfig);
        $this->httpClient = new HttpClient($config, $this->retryHandler);
    }

    /**
     * Create a client instance from environment variables.
     *
     * @param array<string, mixed> $overrides Optional configuration overrides
     * @return self
     */
    public static function fromEnv(array $overrides = []): self
    {
        $config = KraConfig::fromEnv($overrides);
        return new self($config);
    }

    /**
     * Verify a KRA PIN number.
     *
     * @param string $pinNumber The PIN number to verify (format: P051234567A)
     * @return PinVerificationResult The verification result
     * @throws \KraConnect\Exceptions\InvalidPinFormatException If PIN format is invalid
     * @throws \KraConnect\Exceptions\ApiException If API request fails
     *
     * @example
     * ```php
     * $client = new KraClient($config);
     * $result = $client->verifyPin('P051234567A');
     *
     * if ($result->isValid) {
     *     echo "Valid PIN: " . $result->taxpayerName;
     * }
     * ```
     */
    public function verifyPin(string $pinNumber): PinVerificationResult
    {
        // Validate and normalize PIN
        $normalizedPin = Validator::validatePin($pinNumber);

        // Generate cache key
        $cacheKey = $this->cacheManager->generateKey('pin_verification', [
            'pin' => $normalizedPin
        ]);

        // Try to get from cache
        return $this->cacheManager->getOrSet(
            $cacheKey,
            function () use ($normalizedPin) {
                // Acquire rate limit token
                $this->rateLimiter->acquire();

                // Make API request
                $responseData = $this->httpClient->post('/verify-pin', [
                    'pin' => $normalizedPin
                ]);

                // Parse and return result
                return PinVerificationResult::fromApiResponse($responseData);
            },
            $this->config->cacheConfig->pinVerificationTtl
        );
    }

    /**
     * Verify a Tax Compliance Certificate (TCC).
     *
     * @param string $tccNumber The TCC number to verify (format: TCC123456)
     * @return TccVerificationResult The verification result
     * @throws \KraConnect\Exceptions\InvalidTccFormatException If TCC format is invalid
     * @throws \KraConnect\Exceptions\ApiException If API request fails
     *
     * @example
     * ```php
     * $result = $client->verifyTcc('TCC123456');
     *
     * if ($result->isCurrentlyValid()) {
     *     echo "TCC is valid until: " . $result->expiryDate;
     * }
     * ```
     */
    public function verifyTcc(string $tccNumber): TccVerificationResult
    {
        // Validate and normalize TCC
        $normalizedTcc = Validator::validateTcc($tccNumber);

        // Generate cache key
        $cacheKey = $this->cacheManager->generateKey('tcc_verification', [
            'tcc' => $normalizedTcc
        ]);

        // Try to get from cache
        return $this->cacheManager->getOrSet(
            $cacheKey,
            function () use ($normalizedTcc) {
                $this->rateLimiter->acquire();

                $responseData = $this->httpClient->post('/verify-tcc', [
                    'tcc' => $normalizedTcc
                ]);

                return TccVerificationResult::fromApiResponse($responseData);
            },
            $this->config->cacheConfig->tccVerificationTtl
        );
    }

    /**
     * Validate an e-slip payment.
     *
     * @param string $eslipNumber The e-slip number to validate
     * @return EslipValidationResult The validation result
     * @throws \KraConnect\Exceptions\ValidationException If e-slip format is invalid
     * @throws \KraConnect\Exceptions\ApiException If API request fails
     *
     * @example
     * ```php
     * $result = $client->validateEslip('1234567890');
     *
     * if ($result->isPaid()) {
     *     echo "Payment confirmed: " . $result->getFormattedAmount();
     * }
     * ```
     */
    public function validateEslip(string $eslipNumber): EslipValidationResult
    {
        // Validate e-slip
        $normalizedEslip = Validator::validateEslip($eslipNumber);

        // Generate cache key
        $cacheKey = $this->cacheManager->generateKey('eslip_validation', [
            'eslip' => $normalizedEslip
        ]);

        // Try to get from cache
        return $this->cacheManager->getOrSet(
            $cacheKey,
            function () use ($normalizedEslip) {
                $this->rateLimiter->acquire();

                $responseData = $this->httpClient->post('/validate-eslip', [
                    'eslip_number' => $normalizedEslip
                ]);

                return EslipValidationResult::fromApiResponse($responseData);
            },
            $this->config->cacheConfig->eslipValidationTtl
        );
    }

    /**
     * File a NIL return for a specific tax obligation.
     *
     * @param string $pinNumber The taxpayer's PIN number
     * @param string $obligationId The obligation ID
     * @param string $period The tax period (YYYYMM format, e.g., '202401')
     * @return NilReturnResult The filing result
     * @throws \KraConnect\Exceptions\InvalidPinFormatException If PIN format is invalid
     * @throws \KraConnect\Exceptions\ValidationException If parameters are invalid
     * @throws \KraConnect\Exceptions\ApiException If API request fails
     *
     * @example
     * ```php
     * $result = $client->fileNilReturn('P051234567A', 'OBL123', '202401');
     *
     * if ($result->isAccepted()) {
     *     echo "NIL return filed: " . $result->referenceNumber;
     * }
     * ```
     */
    public function fileNilReturn(string $pinNumber, string $obligationId, string $period): NilReturnResult
    {
        // Validate inputs
        $normalizedPin = Validator::validatePin($pinNumber);
        $normalizedObligationId = Validator::validateObligationId($obligationId);
        $normalizedPeriod = Validator::validatePeriod($period);

        // Note: NIL returns are not cached as they are create operations
        $this->rateLimiter->acquire();

        $responseData = $this->httpClient->post('/file-nil-return', [
            'pin' => $normalizedPin,
            'obligation_id' => $normalizedObligationId,
            'period' => $normalizedPeriod
        ]);

        return NilReturnResult::fromApiResponse($responseData);
    }

    /**
     * Get detailed taxpayer information.
     *
     * @param string $pinNumber The taxpayer's PIN number
     * @return TaxpayerDetails The taxpayer details
     * @throws \KraConnect\Exceptions\InvalidPinFormatException If PIN format is invalid
     * @throws \KraConnect\Exceptions\ApiException If API request fails
     *
     * @example
     * ```php
     * $details = $client->getTaxpayerDetails('P051234567A');
     *
     * echo "Name: " . $details->getDisplayName();
     * echo "Status: " . $details->status;
     * echo "Obligations: " . count($details->obligations);
     * ```
     */
    public function getTaxpayerDetails(string $pinNumber): TaxpayerDetails
    {
        // Validate and normalize PIN
        $normalizedPin = Validator::validatePin($pinNumber);

        // Generate cache key
        $cacheKey = $this->cacheManager->generateKey('taxpayer_details', [
            'pin' => $normalizedPin
        ]);

        // Try to get from cache
        return $this->cacheManager->getOrSet(
            $cacheKey,
            function () use ($normalizedPin) {
                $this->rateLimiter->acquire();

                $responseData = $this->httpClient->get('/taxpayer-details', [
                    'pin' => $normalizedPin
                ]);

                return TaxpayerDetails::fromApiResponse($responseData);
            },
            $this->config->cacheConfig->taxpayerDetailsTtl
        );
    }

    /**
     * Verify multiple PIN numbers in batch.
     *
     * @param array<string> $pinNumbers Array of PIN numbers to verify
     * @return array<PinVerificationResult> Array of verification results
     * @throws \KraConnect\Exceptions\InvalidPinFormatException If any PIN format is invalid
     * @throws \KraConnect\Exceptions\ApiException If API request fails
     *
     * @example
     * ```php
     * $pins = ['P051234567A', 'P051234567B', 'P051234567C'];
     * $results = $client->verifyPinsBatch($pins);
     *
     * foreach ($results as $result) {
     *     echo $result->pinNumber . ": " . ($result->isValid ? 'Valid' : 'Invalid') . "\n";
     * }
     * ```
     */
    public function verifyPinsBatch(array $pinNumbers): array
    {
        // Validate all PINs first
        $normalizedPins = array_map(
            fn($pin) => Validator::validatePin($pin),
            $pinNumbers
        );

        $results = [];

        foreach ($normalizedPins as $pin) {
            $results[] = $this->verifyPin($pin);
        }

        return $results;
    }

    /**
     * Verify multiple TCC numbers in batch.
     *
     * @param array<string> $tccNumbers Array of TCC numbers to verify
     * @return array<TccVerificationResult> Array of verification results
     * @throws \KraConnect\Exceptions\InvalidTccFormatException If any TCC format is invalid
     * @throws \KraConnect\Exceptions\ApiException If API request fails
     */
    public function verifyTccsBatch(array $tccNumbers): array
    {
        // Validate all TCCs first
        $normalizedTccs = array_map(
            fn($tcc) => Validator::validateTcc($tcc),
            $tccNumbers
        );

        $results = [];

        foreach ($normalizedTccs as $tcc) {
            $results[] = $this->verifyTcc($tcc);
        }

        return $results;
    }

    /**
     * Clear the cache.
     *
     * @return bool True on success
     */
    public function clearCache(): bool
    {
        return $this->cacheManager->clear();
    }

    /**
     * Get cache statistics.
     *
     * @return array<string, mixed> Cache statistics
     */
    public function getCacheStats(): array
    {
        return $this->cacheManager->getStats();
    }

    /**
     * Get rate limiter statistics.
     *
     * @return array<string, mixed> Rate limiter statistics
     */
    public function getRateLimitStats(): array
    {
        return $this->rateLimiter->getStats();
    }

    /**
     * Get the SDK configuration.
     *
     * @return KraConfig
     */
    public function getConfig(): KraConfig
    {
        return $this->config;
    }

    /**
     * Get the HTTP client instance.
     *
     * @return HttpClient
     */
    public function getHttpClient(): HttpClient
    {
        return $this->httpClient;
    }

    /**
     * Get the cache manager instance.
     *
     * @return CacheManager
     */
    public function getCacheManager(): CacheManager
    {
        return $this->cacheManager;
    }

    /**
     * Get the rate limiter instance.
     *
     * @return RateLimiter
     */
    public function getRateLimiter(): RateLimiter
    {
        return $this->rateLimiter;
    }

    /**
     * Get the retry handler instance.
     *
     * @return RetryHandler
     */
    public function getRetryHandler(): RetryHandler
    {
        return $this->retryHandler;
    }
}
