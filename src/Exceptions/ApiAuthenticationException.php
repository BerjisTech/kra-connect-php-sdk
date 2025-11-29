<?php

declare(strict_types=1);

namespace KraConnect\Exceptions;

/**
 * Exception thrown when API authentication fails.
 *
 * This typically occurs when:
 * - API key is invalid or expired
 * - API key is missing from the request
 * - Authentication token has expired
 * - Insufficient permissions for the requested operation
 *
 * @package KraConnect\Exceptions
 */
class ApiAuthenticationException extends KraConnectException
{
    /**
     * Create exception for API authentication failure.
     *
     * @param string $message The error message
     * @param array<string, mixed> $details Additional error details
     */
    public function __construct(string $message = 'API authentication failed', array $details = [])
    {
        parent::__construct($message, $details, 401);
    }

    /**
     * Create exception for invalid API key.
     *
     * @return self
     */
    public static function invalidApiKey(): self
    {
        return new self(
            'Invalid API key provided. Please check your credentials.',
            ['error_type' => 'invalid_api_key']
        );
    }

    /**
     * Create exception for missing API key.
     *
     * @return self
     */
    public static function missingApiKey(): self
    {
        return new self(
            'API key is required but was not provided.',
            ['error_type' => 'missing_api_key']
        );
    }

    /**
     * Create exception for expired token.
     *
     * @return self
     */
    public static function tokenExpired(): self
    {
        return new self(
            'Authentication token has expired. Please re-authenticate.',
            ['error_type' => 'token_expired']
        );
    }

    /**
     * Create exception for insufficient permissions.
     *
     * @param string $requiredPermission The permission that was required
     * @return self
     */
    public static function insufficientPermissions(string $requiredPermission): self
    {
        return new self(
            sprintf('Insufficient permissions. Required: %s', $requiredPermission),
            [
                'error_type' => 'insufficient_permissions',
                'required_permission' => $requiredPermission
            ]
        );
    }
}
