<?php

declare(strict_types=1);

namespace KraConnect\Exceptions;

/**
 * Exception thrown when a PIN number format is invalid.
 *
 * The KRA PIN format should be: P followed by 9 digits and a letter.
 * Example: P051234567A
 *
 * @package KraConnect\Exceptions
 */
class InvalidPinFormatException extends KraConnectException
{
    /**
     * Create exception for invalid PIN format.
     *
     * @param string $pinNumber The invalid PIN number
     */
    public function __construct(string $pinNumber)
    {
        $message = sprintf(
            "Invalid PIN format: '%s'. Expected format: P followed by 9 digits and a letter (e.g., P051234567A)",
            $pinNumber
        );

        parent::__construct($message, ['pin_number' => $pinNumber]);
    }
}
