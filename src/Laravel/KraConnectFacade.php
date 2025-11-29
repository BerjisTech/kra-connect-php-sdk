<?php

declare(strict_types=1);

namespace KraConnect\Laravel;

use Illuminate\Support\Facades\Facade;
use KraConnect\KraClient;

/**
 * Laravel Facade for KRA Connect
 *
 * @method static \KraConnect\Models\PinVerificationResult verifyPin(string $pinNumber)
 * @method static \KraConnect\Models\TccVerificationResult verifyTcc(string $tccNumber)
 * @method static \KraConnect\Models\EslipValidationResult validateEslip(string $eslipNumber)
 * @method static \KraConnect\Models\NilReturnResult fileNilReturn(string $pinNumber, string $obligationId, string $period)
 * @method static \KraConnect\Models\TaxpayerDetails getTaxpayerDetails(string $pinNumber)
 * @method static array<\KraConnect\Models\PinVerificationResult> verifyPinsBatch(array $pinNumbers)
 * @method static array<\KraConnect\Models\TccVerificationResult> verifyTccsBatch(array $tccNumbers)
 * @method static bool clearCache()
 * @method static array<string, mixed> getCacheStats()
 * @method static array<string, mixed> getRateLimitStats()
 * @method static \KraConnect\Config\KraConfig getConfig()
 *
 * @see \KraConnect\KraClient
 * @package KraConnect\Laravel
 */
class KraConnectFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return KraClient::class;
    }
}
