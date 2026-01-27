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

                // Make API request to GavaConnect endpoint
                $responseData = $this->httpClient->post('/checker/v1/pinbypin', [
                    'KRAPIN' => $normalizedPin
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
     * @param string $kraPIN The taxpayer's KRA PIN associated with the TCC
     * @return TccVerificationResult The verification result
     * @throws \KraConnect\Exceptions\InvalidTccFormatException If TCC format is invalid
     * @throws \KraConnect\Exceptions\InvalidPinFormatException If PIN format is invalid
     * @throws \KraConnect\Exceptions\ApiException If API request fails
     *
     * @example
     * ```php
     * $result = $client->verifyTcc('TCC123456', 'P051234567A');
     *
     * if ($result->isCurrentlyValid()) {
     *     echo "TCC is valid until: " . $result->expiryDate;
     * }
     * ```
     */
    public function verifyTcc(string $tccNumber, string $kraPIN): TccVerificationResult
    {
        // Validate and normalize TCC and PIN
        $normalizedTcc = Validator::validateTcc($tccNumber);
        $normalizedPin = Validator::validatePin($kraPIN);

        // Generate cache key
        $cacheKey = $this->cacheManager->generateKey('tcc_verification', [
            'tcc' => $normalizedTcc,
            'pin' => $normalizedPin
        ]);

        // Try to get from cache
        return $this->cacheManager->getOrSet(
            $cacheKey,
            function () use ($normalizedTcc, $normalizedPin) {
                $this->rateLimiter->acquire();

                $responseData = $this->httpClient->post('/v1/kra-tcc/validate', [
                    'kraPIN' => $normalizedPin,
                    'tccNumber' => $normalizedTcc
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

                $responseData = $this->httpClient->post('/payment/checker/v1/eslip', [
                    'EslipNumber' => $normalizedEslip
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
     * @param int $obligationCode The obligation code as defined by KRA
     * @param int $month The tax period month (1-12)
     * @param int $year The tax period year
     * @return NilReturnResult The filing result
     * @throws \KraConnect\Exceptions\InvalidPinFormatException If PIN format is invalid
     * @throws \KraConnect\Exceptions\ValidationException If parameters are invalid
     * @throws \KraConnect\Exceptions\ApiException If API request fails
     *
     * @example
     * ```php
     * $result = $client->fileNilReturn('P051234567A', 1, 1, 2024);
     *
     * if ($result->isAccepted()) {
     *     echo "NIL return filed: " . $result->referenceNumber;
     * }
     * ```
     */
    public function fileNilReturn(string $pinNumber, int $obligationCode, int $month, int $year): NilReturnResult
    {
        // Validate inputs
        $normalizedPin = Validator::validatePin($pinNumber);

        if ($obligationCode <= 0) {
            throw new \KraConnect\Exceptions\ValidationException('Obligation code must be a positive integer');
        }
        if ($month < 1 || $month > 12) {
            throw new \KraConnect\Exceptions\ValidationException('Month must be between 1 and 12');
        }
        if ($year < 2000) {
            throw new \KraConnect\Exceptions\ValidationException('Year must be 2000 or later');
        }

        // Note: NIL returns are not cached as they are create operations
        $this->rateLimiter->acquire();

        $responseData = $this->httpClient->post('/dtd/return/v1/nil', [
            'TAXPAYERDETAILS' => [
                'TaxpayerPIN' => $normalizedPin,
                'ObligationCode' => $obligationCode,
                'Month' => $month,
                'Year' => $year,
            ]
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

                // Make API requests to GavaConnect endpoints (profile + obligations)
                $profileData = $this->httpClient->post('/checker/v1/pinbypin', [
                    'KRAPIN' => $normalizedPin
                ]);

                $obligationsData = $this->httpClient->post('/dtd/checker/v1/obligation', [
                    'taxPayerPin' => $normalizedPin
                ]);

                // Combine profile and obligations data
                $responseData = array_merge($profileData, [
                    'obligations' => $obligationsData['obligations'] ?? []
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
