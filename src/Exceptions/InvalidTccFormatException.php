<?php

declare(strict_types=1);

namespace KraConnect\Exceptions;

/**
 * Exception thrown when a Tax Compliance Certificate (TCC) format is invalid.
 *
 * The KRA TCC format should be: TCC followed by digits.
 * Example: TCC123456
 *
 * @package KraConnect\Exceptions
 */
class InvalidTccFormatException extends KraConnectException
{
    /**
     * Create exception for invalid TCC format.
     *
     * @param string $tccNumber The invalid TCC number
     */
    public function __construct(string $tccNumber)
    {
        $message = sprintf(
            "Invalid TCC format: '%s'. Expected format: TCC followed by digits (e.g., TCC123456)",
            $tccNumber
        );

        parent::__construct($message, ['tcc_number' => $tccNumber]);
    }
}
