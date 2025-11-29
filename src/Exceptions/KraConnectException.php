<?php

declare(strict_types=1);

namespace KraConnect\Exceptions;

use Exception;

/**
 * Base exception for all KRA-Connect errors.
 *
 * All custom exceptions in the KRA-Connect SDK extend from this base class.
 * This allows users to catch all SDK-specific errors with a single catch clause.
 *
 * @package KraConnect\Exceptions
 * @author KRA-Connect Team
 */
class KraConnectException extends Exception
{
    /**
     * Additional error details.
     *
     * @var array<string, mixed>
     */
    protected array $details = [];

    /**
     * HTTP status code if applicable.
     */
    protected ?int $statusCode = null;

    /**
     * Create a new exception instance.
     *
     * @param string $message Error message
     * @param array<string, mixed> $details Additional error details
     * @param int|null $statusCode HTTP status code
     * @param int $code Error code
     * @param Exception|null $previous Previous exception
     */
    public function __construct(
        string $message = '',
        array $details = [],
        ?int $statusCode = null,
        int $code = 0,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->details = $details;
        $this->statusCode = $statusCode;
    }

    /**
     * Get additional error details.
     *
     * @return array<string, mixed>
     */
    public function getDetails(): array
    {
        return $this->details;
    }

    /**
     * Get HTTP status code.
     */
    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    /**
     * Convert exception to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'status_code' => $this->statusCode,
            'details' => $this->details,
            'file' => $this->getFile(),
            'line' => $this->getLine(),
        ];
    }
}
