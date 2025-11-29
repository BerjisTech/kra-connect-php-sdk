<?php

declare(strict_types=1);

namespace KraConnect\Exceptions;

/**
 * Exception thrown for general API errors.
 *
 * This exception is used for various API-related errors including:
 * - Server errors (5xx)
 * - Client errors (4xx)
 * - Network errors
 * - Invalid responses
 *
 * @package KraConnect\Exceptions
 */
class ApiException extends KraConnectException
{
    /**
     * Create exception for API error.
     *
     * @param string $message The error message
     * @param array<string, mixed> $details Additional error details
     * @param int|null $statusCode HTTP status code
     */
    public function __construct(string $message, array $details = [], ?int $statusCode = null)
    {
        parent::__construct($message, $details, $statusCode);
    }

    /**
     * Create exception from HTTP response.
     *
     * @param int $statusCode HTTP status code
     * @param string $responseBody Response body
     * @param string $endpoint The endpoint that was called
     * @return self
     */
    public static function fromResponse(int $statusCode, string $responseBody, string $endpoint): self
    {
        $message = sprintf(
            'API request to "%s" failed with status %d',
            $endpoint,
            $statusCode
        );

        // Try to parse JSON error response
        $errorData = json_decode($responseBody, true);
        if (json_last_error() === JSON_ERROR_NONE && isset($errorData['message'])) {
            $message .= ': ' . $errorData['message'];
        }

        $details = [
            'endpoint' => $endpoint,
            'status_code' => $statusCode,
            'response_body' => $responseBody
        ];

        if (is_array($errorData)) {
            $details['error_data'] = $errorData;
        }

        return new self($message, $details, $statusCode);
    }

    /**
     * Create exception for server error (5xx).
     *
     * @param string $endpoint The endpoint that was called
     * @param int $statusCode HTTP status code
     * @return self
     */
    public static function serverError(string $endpoint, int $statusCode = 500): self
    {
        return new self(
            sprintf('Server error occurred while calling "%s" (status: %d)', $endpoint, $statusCode),
            [
                'endpoint' => $endpoint,
                'error_type' => 'server_error'
            ],
            $statusCode
        );
    }

    /**
     * Create exception for network error.
     *
     * @param string $endpoint The endpoint that was called
     * @param string $errorMessage The error message
     * @return self
     */
    public static function networkError(string $endpoint, string $errorMessage): self
    {
        return new self(
            sprintf('Network error while calling "%s": %s', $endpoint, $errorMessage),
            [
                'endpoint' => $endpoint,
                'error_type' => 'network_error',
                'error_message' => $errorMessage
            ]
        );
    }

    /**
     * Create exception for invalid response.
     *
     * @param string $endpoint The endpoint that was called
     * @param string $reason The reason the response is invalid
     * @return self
     */
    public static function invalidResponse(string $endpoint, string $reason): self
    {
        return new self(
            sprintf('Invalid response from "%s": %s', $endpoint, $reason),
            [
                'endpoint' => $endpoint,
                'error_type' => 'invalid_response',
                'reason' => $reason
            ]
        );
    }

    /**
     * Check if this is a server error (5xx).
     *
     * @return bool
     */
    public function isServerError(): bool
    {
        return $this->statusCode !== null && $this->statusCode >= 500 && $this->statusCode < 600;
    }

    /**
     * Check if this is a client error (4xx).
     *
     * @return bool
     */
    public function isClientError(): bool
    {
        return $this->statusCode !== null && $this->statusCode >= 400 && $this->statusCode < 500;
    }
}
