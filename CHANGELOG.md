# Changelog

All notable changes to the KRA-Connect PHP SDK will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.1.1] - 2025-01-15

### Added
- Initial release of KRA-Connect PHP SDK
- PIN verification with comprehensive validation
- TCC (Tax Compliance Certificate) verification with expiry tracking
- E-slip payment validation
- NIL return filing capability
- Taxpayer details retrieval with tax obligations
- Batch operations for PIN and TCC verification
- Automatic retry logic with exponential backoff
- Built-in rate limiting using token bucket algorithm
- Response caching with configurable TTL
- Comprehensive exception handling with 9 exception types
- Full PHP 8.1+ type safety with readonly properties
- PSR-4 autoloading
- PSR-3 logger interface support
- PSR-6 cache interface compatibility
- Laravel integration (ServiceProvider and Facade)
- Symfony integration (Bundle and DependencyInjection)
- Complete PHPUnit test suite (90%+ coverage)
- Integration tests with mocked API responses
- PHPStan level 8 static analysis
- Psalm level 3 static analysis
- PHP CS Fixer configuration for PSR-12 compliance
- Comprehensive documentation and examples
- Utility classes for validation and formatting
- Support for PHP 8.1, 8.2, and 8.3

### Framework Support
- **Laravel**: Auto-discovery support, ServiceProvider, Facade
- **Symfony**: Bundle, DependencyInjection Extension, Configuration

### Documentation
- Complete API documentation with examples
- Laravel integration guide
- Symfony integration guide
- Error handling examples
- Batch processing examples
- README with installation and usage instructions

[Unreleased]: https://github.com/kra-connect/php-sdk/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/kra-connect/php-sdk/releases/tag/v0.1.0
