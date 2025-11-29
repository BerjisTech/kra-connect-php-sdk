<?php

declare(strict_types=1);

/**
 * Laravel Integration Example
 *
 * This example demonstrates how to integrate the KRA Connect SDK into a Laravel application.
 *
 * NOTE: This is a demonstration file showing Laravel patterns.
 * For actual Laravel integration, use the provided ServiceProvider and Facade.
 */

// ==================
// 1. Configuration (config/kra-connect.php)
// ==================

/*
<?php

return [
    'api_key' => env('KRA_API_KEY'),
    'base_url' => env('KRA_BASE_URL', 'https://api.kra.go.ke/gavaconnect/v1'),
    'timeout' => env('KRA_TIMEOUT', 30.0),

    'cache' => [
        'enabled' => env('KRA_CACHE_ENABLED', true),
        'ttl' => env('KRA_CACHE_TTL', 3600),
        'pin_verification_ttl' => env('KRA_CACHE_PIN_TTL', 3600),
        'tcc_verification_ttl' => env('KRA_CACHE_TCC_TTL', 1800),
    ],

    'rate_limit' => [
        'enabled' => env('KRA_RATE_LIMIT_ENABLED', true),
        'max_requests' => env('KRA_RATE_LIMIT_MAX', 100),
        'window_seconds' => env('KRA_RATE_LIMIT_WINDOW', 60),
    ],

    'retry' => [
        'max_retries' => env('KRA_RETRY_MAX', 3),
        'initial_delay' => env('KRA_RETRY_DELAY', 1.0),
    ],
];
*/

// ==================
// 2. Environment Variables (.env)
// ==================

/*
KRA_API_KEY=your-api-key-here
KRA_BASE_URL=https://api.kra.go.ke/gavaconnect/v1
KRA_TIMEOUT=30
KRA_CACHE_ENABLED=true
KRA_CACHE_TTL=3600
KRA_RATE_LIMIT_ENABLED=true
KRA_RATE_LIMIT_MAX=100
*/

// ==================
// 3. Service Provider Registration
// ==================

/*
// In config/app.php

'providers' => [
    // Other providers...
    KraConnect\Laravel\KraConnectServiceProvider::class,
],

'aliases' => [
    // Other aliases...
    'KraConnect' => KraConnect\Laravel\KraConnectFacade::class,
],
*/

// ==================
// 4. Controller Example
// ==================

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use KraConnect\KraClient;
use KraConnect\Exceptions\InvalidPinFormatException;
use KraConnect\Exceptions\ApiException;

class TaxpayerController
{
    private KraClient $kraClient;

    public function __construct(KraClient $kraClient)
    {
        // Client is automatically injected via Laravel's service container
        $this->kraClient = $kraClient;
    }

    /**
     * Verify a taxpayer's PIN.
     */
    public function verifyPin(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pin' => 'required|string|regex:/^P\d{9}[A-Z]$/i'
        ]);

        try {
            $result = $this->kraClient->verifyPin($validated['pin']);

            return response()->json([
                'success' => true,
                'data' => [
                    'pin' => $result->pinNumber,
                    'is_valid' => $result->isValid,
                    'taxpayer_name' => $result->taxpayerName,
                    'status' => $result->status,
                    'taxpayer_type' => $result->taxpayerType,
                    'is_active' => $result->isActive(),
                ]
            ]);

        } catch (InvalidPinFormatException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid PIN format',
                'message' => $e->getMessage()
            ], 422);

        } catch (ApiException $e) {
            return response()->json([
                'success' => false,
                'error' => 'API error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify a TCC.
     */
    public function verifyTcc(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tcc' => 'required|string|regex:/^TCC\d+$/i'
        ]);

        try {
            $result = $this->kraClient->verifyTcc($validated['tcc']);

            return response()->json([
                'success' => true,
                'data' => [
                    'tcc' => $result->tccNumber,
                    'is_valid' => $result->isValid,
                    'is_expired' => $result->isExpired,
                    'taxpayer_name' => $result->taxpayerName,
                    'expiry_date' => $result->expiryDate,
                    'days_until_expiry' => $result->getDaysUntilExpiry(),
                    'is_expiring_soon' => $result->isExpiringSoon(30),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get taxpayer details.
     */
    public function getTaxpayerDetails(string $pin): JsonResponse
    {
        try {
            $details = $this->kraClient->getTaxpayerDetails($pin);

            return response()->json([
                'success' => true,
                'data' => [
                    'pin' => $details->pinNumber,
                    'name' => $details->getDisplayName(),
                    'taxpayer_type' => $details->taxpayerType,
                    'status' => $details->status,
                    'is_active' => $details->isActive(),
                    'business_name' => $details->businessName,
                    'trading_name' => $details->tradingName,
                    'email' => $details->emailAddress,
                    'phone' => $details->phoneNumber,
                    'obligations' => array_map(
                        fn($obligation) => $obligation->toArray(),
                        $details->obligations
                    )
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Batch verify PINs.
     */
    public function batchVerifyPins(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pins' => 'required|array|min:1|max:100',
            'pins.*' => 'required|string|regex:/^P\d{9}[A-Z]$/i'
        ]);

        try {
            $results = $this->kraClient->verifyPinsBatch($validated['pins']);

            return response()->json([
                'success' => true,
                'data' => array_map(
                    fn($result) => [
                        'pin' => $result->pinNumber,
                        'is_valid' => $result->isValid,
                        'taxpayer_name' => $result->taxpayerName,
                        'status' => $result->status,
                    ],
                    $results
                ),
                'summary' => [
                    'total' => count($results),
                    'valid' => count(array_filter($results, fn($r) => $r->isValid)),
                    'invalid' => count(array_filter($results, fn($r) => !$r->isValid)),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

// ==================
// 5. Using Facade
// ==================

namespace App\Services;

use KraConnect\Laravel\KraConnectFacade as KraConnect;

class SupplierVerificationService
{
    public function verifySupplier(string $pin, string $tcc): array
    {
        // Using facade for quick access
        $pinResult = KraConnect::verifyPin($pin);
        $tccResult = KraConnect::verifyTcc($tcc);

        return [
            'pin_valid' => $pinResult->isValid && $pinResult->isActive(),
            'tcc_valid' => $tccResult->isCurrentlyValid(),
            'taxpayer_name' => $pinResult->taxpayerName,
            'verification_passed' => $pinResult->isValid && $tccResult->isCurrentlyValid(),
        ];
    }
}

// ==================
// 6. Artisan Command Example
// ==================

namespace App\Console\Commands;

use Illuminate\Console\Command;
use KraConnect\KraClient;

class VerifySuppliers extends Command
{
    protected $signature = 'kra:verify-suppliers {file}';
    protected $description = 'Verify suppliers from CSV file';

    public function handle(KraClient $client): int
    {
        $file = $this->argument('file');

        if (!file_exists($file)) {
            $this->error("File not found: {$file}");
            return 1;
        }

        $this->info('Verifying suppliers...');

        $suppliers = array_map('str_getcsv', file($file));
        $headers = array_shift($suppliers);

        $bar = $this->output->createProgressBar(count($suppliers));

        $verified = 0;
        $failed = 0;

        foreach ($suppliers as $row) {
            $data = array_combine($headers, $row);

            try {
                $result = $client->verifyPin($data['pin']);

                if ($result->isValid) {
                    $verified++;
                    $this->info("\n✓ {$data['name']}: {$data['pin']} - Valid");
                } else {
                    $failed++;
                    $this->warn("\n✗ {$data['name']}: {$data['pin']} - Invalid");
                }
            } catch (\Exception $e) {
                $failed++;
                $this->error("\n✗ {$data['name']}: Error - {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();

        $this->info("\n\nVerification complete!");
        $this->info("Verified: {$verified}");
        $this->info("Failed: {$failed}");

        return 0;
    }
}

// ==================
// 7. Job Example (Queue)
// ==================

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use KraConnect\KraClient;
use App\Models\Supplier;

class VerifySupplierJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private int $supplierId;

    public function __construct(int $supplierId)
    {
        $this->supplierId = $supplierId;
    }

    public function handle(KraClient $client): void
    {
        $supplier = Supplier::findOrFail($this->supplierId);

        $result = $client->verifyPin($supplier->pin);

        $supplier->update([
            'pin_verified' => $result->isValid,
            'pin_verification_date' => now(),
            'taxpayer_name' => $result->taxpayerName,
            'taxpayer_status' => $result->status,
        ]);
    }
}

// ==================
// 8. Routes Example
// ==================

/*
// routes/api.php

use App\Http\Controllers\TaxpayerController;

Route::prefix('taxpayer')->group(function () {
    Route::post('/verify-pin', [TaxpayerController::class, 'verifyPin']);
    Route::post('/verify-tcc', [TaxpayerController::class, 'verifyTcc']);
    Route::get('/details/{pin}', [TaxpayerController::class, 'getTaxpayerDetails']);
    Route::post('/batch-verify-pins', [TaxpayerController::class, 'batchVerifyPins']);
});
*/

echo "Laravel Integration Example\n";
echo "===========================\n\n";
echo "This file demonstrates Laravel integration patterns.\n";
echo "Check the comments above for complete examples of:\n\n";
echo "1. Configuration setup\n";
echo "2. Environment variables\n";
echo "3. Service provider registration\n";
echo "4. Controller usage\n";
echo "5. Facade usage\n";
echo "6. Artisan commands\n";
echo "7. Queue jobs\n";
echo "8. API routes\n\n";
echo "For actual Laravel integration, install the package and use:\n";
echo "  php artisan vendor:publish --provider=\"KraConnect\\Laravel\\KraConnectServiceProvider\"\n";
