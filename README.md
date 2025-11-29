# KRA-Connect PHP SDK

> Official PHP SDK for Kenya Revenue Authority's GavaConnect API

[![Latest Version](https://img.shields.io/packagist/v/kra-connect/php-sdk.svg?style=flat-square)](https://packagist.org/packages/kra-connect/php-sdk)
[![Total Downloads](https://img.shields.io/packagist/dt/kra-connect/php-sdk.svg?style=flat-square)](https://packagist.org/packages/kra-connect/php-sdk)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg?style=flat-square)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/packagist/php-v/kra-connect/php-sdk.svg?style=flat-square)](https://packagist.org/packages/kra-connect/php-sdk)
[![Tests](https://img.shields.io/github/actions/workflow/status/kra-connect/php-sdk/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/kra-connect/php-sdk/actions)
[![Code Coverage](https://img.shields.io/codecov/c/github/kra-connect/php-sdk?style=flat-square)](https://codecov.io/gh/kra-connect/php-sdk)
[![PHPStan Level](https://img.shields.io/badge/PHPStan-level%208-brightgreen.svg?style=flat-square)](https://phpstan.org/)
[![Psalm Level](https://img.shields.io/badge/Psalm-level%203-brightgreen.svg?style=flat-square)](https://psalm.dev/)

## Features

- ✅ **PIN Verification** - Verify KRA PIN numbers
- ✅ **TCC Verification** - Check Tax Compliance Certificates
- ✅ **e-Slip Validation** - Validate electronic payment slips
- ✅ **NIL Returns** - File NIL returns programmatically
- ✅ **Taxpayer Details** - Retrieve taxpayer information
- ✅ **Type Safety** - Full PHP 8.1+ type declarations
- ✅ **PSR Standards** - PSR-4 autoloading, PSR-3 logging, PSR-6 caching
- ✅ **Laravel Support** - Service provider and facades
- ✅ **Symfony Support** - Bundle integration
- ✅ **Retry Logic** - Automatic retry with exponential backoff
- ✅ **Caching** - Response caching for improved performance
- ✅ **Rate Limiting** - Built-in rate limiting

## Requirements

- PHP 8.1 or higher
- Composer
- ext-json

## Installation

### Via Composer

```bash
composer require kra-connect/php-sdk
```

### Laravel

The package will automatically register the service provider. Publish the configuration:

```bash
php artisan vendor:publish --provider="KraConnect\Laravel\KraConnectServiceProvider"
```

### Symfony

Register the bundle in `config/bundles.php`:

```php
return [
    // ...
    KraConnect\Symfony\KraConnectBundle::class => ['all' => true],
];
```

## Quick Start

### Basic Usage

```php
<?php

use KraConnect\KraClient;

// Initialize the client
$client = new KraClient('your-api-key');

// Verify a PIN
$result = $client->verifyPin('P051234567A');

if ($result->isValid) {
    echo "Taxpayer: " . $result->taxpayerName . "\n";
    echo "Status: " . $result->status . "\n";
} else {
    echo "Invalid PIN: " . $result->errorMessage . "\n";
}
```

### Using Configuration

```php
<?php

use KraConnect\KraClient;
use KraConnect\Config\KraConfig;

$config = new KraConfig(
    apiKey: 'your-api-key',
    baseUrl: 'https://api.kra.go.ke/gavaconnect/v1',
    timeout: 30,
    retries: 3
);

$client = new KraClient($config);
```

### Laravel Usage

```php
<?php

namespace App\Http\Controllers;

use KraConnect\Facades\KraConnect;

class TaxController extends Controller
{
    public function verifySupplier($pin)
    {
        $result = KraConnect::verifyPin($pin);

        return response()->json($result);
    }
}
```

Or inject the client:

```php
<?php

use KraConnect\KraClient;

class TaxService
{
    public function __construct(
        private KraClient $kraClient
    ) {}

    public function verify($pin)
    {
        return $this->kraClient->verifyPin($pin);
    }
}
```

## API Reference

### KraClient

#### `verifyPin(string $pinNumber): PinVerificationResult`

Verify a KRA PIN number.

**Parameters:**
- `$pinNumber` - The PIN to verify (format: P + 9 digits + letter)

**Returns:**
- `PinVerificationResult` - Verification result with taxpayer details

**Throws:**
- `InvalidPinFormatException` - If PIN format is invalid
- `ApiAuthenticationException` - If API key is invalid
- `ApiTimeoutException` - If request times out
- `RateLimitExceededException` - If rate limit is exceeded

**Example:**
```php
$result = $client->verifyPin('P051234567A');
echo $result->taxpayerName;
```

#### `verifyTcc(string $tccNumber): TccVerificationResult`

Verify a Tax Compliance Certificate.

**Parameters:**
- `$tccNumber` - The TCC number to verify

**Returns:**
- `TccVerificationResult` - TCC verification result

**Example:**
```php
$result = $client->verifyTcc('TCC123456');
echo "Valid until: " . $result->expiryDate->format('Y-m-d');
```

#### `validateEslip(string $slipNumber): EslipValidationResult`

Validate an electronic payment slip.

#### `fileNilReturn(string $pinNumber, string $period, string $obligationId): NilReturnResult`

File a NIL return for a taxpayer.

**Example:**
```php
$result = $client->fileNilReturn(
    pinNumber: 'P051234567A',
    period: '202401',
    obligationId: 'OBL123456'
);
```

#### `getTaxpayerDetails(string $pinNumber): TaxpayerDetails`

Retrieve detailed taxpayer information.

### Configuration

```php
$config = new KraConfig(
    apiKey: 'your-api-key',
    baseUrl: 'https://api.kra.go.ke/gavaconnect/v1',
    timeout: 30,
    retries: 3,
    cacheEnabled: true,
    cacheTtl: 3600,
    rateLimitMaxRequests: 100,
    rateLimitWindowSeconds: 60
);
```

## Error Handling

```php
<?php

use KraConnect\KraClient;
use KraConnect\Exceptions\{
    InvalidPinFormatException,
    ApiAuthenticationException,
    ApiTimeoutException,
    RateLimitExceededException,
    ApiException
};

try {
    $result = $client->verifyPin('P051234567A');
} catch (InvalidPinFormatException $e) {
    echo "Invalid PIN format: " . $e->getMessage();
} catch (ApiAuthenticationException $e) {
    echo "Authentication failed: " . $e->getMessage();
} catch (ApiTimeoutException $e) {
    echo "Request timed out: " . $e->getMessage();
} catch (RateLimitExceededException $e) {
    echo "Rate limit exceeded. Retry after: " . $e->getRetryAfter() . " seconds";
    sleep($e->getRetryAfter());
    // Retry
} catch (ApiException $e) {
    echo "API error: " . $e->getMessage();
    echo "Status code: " . $e->getStatusCode();
}
```

## Laravel Configuration

After publishing, edit `config/kra-connect.php`:

```php
return [
    'api_key' => env('KRA_API_KEY'),
    'base_url' => env('KRA_API_BASE_URL', 'https://api.kra.go.ke/gavaconnect/v1'),
    'timeout' => env('KRA_TIMEOUT', 30),
    'retries' => env('KRA_MAX_RETRIES', 3),
    'cache' => [
        'enabled' => env('KRA_CACHE_ENABLED', true),
        'ttl' => env('KRA_CACHE_TTL', 3600),
        'store' => env('KRA_CACHE_STORE', 'redis'),
    ],
    'rate_limit' => [
        'enabled' => env('KRA_RATE_LIMIT_ENABLED', true),
        'max_requests' => env('KRA_RATE_LIMIT_MAX_REQUESTS', 100),
        'window_seconds' => env('KRA_RATE_LIMIT_WINDOW_SECONDS', 60),
    ],
];
```

Add to `.env`:

```env
KRA_API_KEY=your_api_key_here
KRA_API_BASE_URL=https://api.kra.go.ke/gavaconnect/v1
KRA_TIMEOUT=30
KRA_MAX_RETRIES=3
KRA_CACHE_ENABLED=true
KRA_CACHE_TTL=3600
```

## Advanced Usage

### Batch Verification

```php
$pins = ['P051234567A', 'P051234567B', 'P051234567C'];
$results = $client->verifyPinsBatch($pins);

foreach ($results as $result) {
    echo "{$result->pinNumber}: " . ($result->isValid ? '✓' : '✗') . "\n";
}
```

### Custom HTTP Client

```php
use GuzzleHttp\Client;

$httpClient = new Client([
    'timeout' => 60,
    'verify' => true,
]);

$client = new KraClient('your-api-key', $httpClient);
```

### Custom Cache

```php
use Symfony\Component\Cache\Adapter\RedisAdapter;

$cache = new RedisAdapter(
    RedisAdapter::createConnection('redis://localhost')
);

$config = new KraConfig(
    apiKey: 'your-api-key',
    cache: $cache
);

$client = new KraClient($config);
```

## Testing

```bash
# Run tests
composer test

# Run tests with coverage
composer test-coverage

# Run PHPStan
composer phpstan

# Fix code style
composer cs-fix

# Check code style
composer cs-check
```

## Examples

See the [examples](./examples) directory for more usage examples:

- [Basic PIN Verification](./examples/basic-pin-verification.php)
- [Batch Processing](./examples/batch-processing.php)
- [Error Handling](./examples/error-handling.php)
- [Laravel Integration](./examples/laravel-example.php)

## Development

### Setup

```bash
# Clone the repository
git clone https://github.com/your-org/kra-connect.git
cd kra-connect/packages/php-sdk

# Install dependencies
composer install

# Run tests
composer test
```

### Code Style

This package follows PSR-12 coding standards. Use PHP CS Fixer:

```bash
composer cs-fix
```

### Static Analysis

Run PHPStan for static analysis:

```bash
composer phpstan
```

## Contributing

See [CONTRIBUTING.md](../../CONTRIBUTING.md) for contribution guidelines.

## License

MIT License - see [LICENSE](../../LICENSE) for details.

## Support

- **Documentation**: [https://docs.kra-connect.dev/php](https://docs.kra-connect.dev/php)
- **Issues**: [GitHub Issues](https://github.com/your-org/kra-connect/issues)
- **Discord**: [Join our community](https://discord.gg/kra-connect)
- **Email**: support@kra-connect.dev

## Changelog

See [CHANGELOG.md](./CHANGELOG.md) for version history.

## Disclaimer

This is an independent project and is not officially affiliated with or endorsed by the Kenya Revenue Authority.
