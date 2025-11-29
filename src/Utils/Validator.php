<?php

declare(strict_types=1);

namespace KraConnect\Utils;

use KraConnect\Exceptions\InvalidPinFormatException;
use KraConnect\Exceptions\InvalidTccFormatException;
use KraConnect\Exceptions\ValidationException;

/**
 * Input Validator Utility
 *
 * Provides validation functions for KRA-specific data formats.
 *
 * @package KraConnect\Utils
 */
class Validator
{
    /**
     * KRA PIN format regex pattern.
     * Format: P followed by 9 digits and a letter
     * Example: P051234567A
     */
    private const PIN_REGEX = '/^P\d{9}[A-Z]$/i';

    /**
     * TCC format regex pattern.
     * Format: TCC followed by digits
     * Example: TCC123456
     */
    private const TCC_REGEX = '/^TCC\d+$/i';

    /**
     * E-slip format regex pattern.
     * Format: Typically numeric
     * Example: 1234567890
     */
    private const ESLIP_REGEX = '/^\d{10,}$/';

    /**
     * Obligation ID format regex pattern.
     * Format: Alphanumeric with possible dashes
     * Example: OBL-123456
     */
    private const OBLIGATION_ID_REGEX = '/^[A-Z0-9\-]+$/i';

    /**
     * Tax period format regex pattern.
     * Format: YYYYMM
     * Example: 202401
     */
    private const PERIOD_REGEX = '/^(19|20)\d{2}(0[1-9]|1[0-2])$/';

    /**
     * Validate and normalize a KRA PIN number.
     *
     * @param string $pinNumber The PIN number to validate
     * @return string The normalized PIN number (uppercase, trimmed)
     * @throws InvalidPinFormatException If PIN format is invalid
     */
    public static function validatePin(string $pinNumber): string
    {
        $normalized = self::normalizeString($pinNumber);

        if (!preg_match(self::PIN_REGEX, $normalized)) {
            throw new InvalidPinFormatException($pinNumber);
        }

        return $normalized;
    }

    /**
     * Validate and normalize a TCC number.
     *
     * @param string $tccNumber The TCC number to validate
     * @return string The normalized TCC number (uppercase, trimmed)
     * @throws InvalidTccFormatException If TCC format is invalid
     */
    public static function validateTcc(string $tccNumber): string
    {
        $normalized = self::normalizeString($tccNumber);

        if (!preg_match(self::TCC_REGEX, $normalized)) {
            throw new InvalidTccFormatException($tccNumber);
        }

        return $normalized;
    }

    /**
     * Validate an e-slip number.
     *
     * @param string $eslipNumber The e-slip number to validate
     * @return string The normalized e-slip number
     * @throws ValidationException If e-slip format is invalid
     */
    public static function validateEslip(string $eslipNumber): string
    {
        $normalized = trim($eslipNumber);

        if (!preg_match(self::ESLIP_REGEX, $normalized)) {
            throw ValidationException::forField(
                'eslip_number',
                'Invalid e-slip format. Expected at least 10 digits.'
            );
        }

        return $normalized;
    }

    /**
     * Validate an obligation ID.
     *
     * @param string $obligationId The obligation ID to validate
     * @return string The normalized obligation ID
     * @throws ValidationException If obligation ID format is invalid
     */
    public static function validateObligationId(string $obligationId): string
    {
        $normalized = self::normalizeString($obligationId);

        if (!preg_match(self::OBLIGATION_ID_REGEX, $normalized)) {
            throw ValidationException::forField(
                'obligation_id',
                'Invalid obligation ID format. Expected alphanumeric characters and dashes.'
            );
        }

        return $normalized;
    }

    /**
     * Validate a tax period.
     *
     * @param string $period The period to validate (YYYYMM format)
     * @return string The validated period
     * @throws ValidationException If period format is invalid
     */
    public static function validatePeriod(string $period): string
    {
        $normalized = trim($period);

        if (!preg_match(self::PERIOD_REGEX, $normalized)) {
            throw ValidationException::forField(
                'period',
                'Invalid period format. Expected YYYYMM (e.g., 202401).'
            );
        }

        return $normalized;
    }

    /**
     * Validate a required string field.
     *
     * @param string $field The field name
     * @param string|null $value The value to validate
     * @return string The validated value
     * @throws ValidationException If value is empty or null
     */
    public static function requireString(string $field, ?string $value): string
    {
        if ($value === null || trim($value) === '') {
            throw ValidationException::requiredField($field);
        }

        return trim($value);
    }

    /**
     * Validate a required integer field.
     *
     * @param string $field The field name
     * @param mixed $value The value to validate
     * @return int The validated integer
     * @throws ValidationException If value is not a valid integer
     */
    public static function requireInt(string $field, $value): int
    {
        if (!is_numeric($value)) {
            throw ValidationException::invalidType($field, 'integer', gettype($value));
        }

        return (int) $value;
    }

    /**
     * Validate a required float field.
     *
     * @param string $field The field name
     * @param mixed $value The value to validate
     * @return float The validated float
     * @throws ValidationException If value is not a valid float
     */
    public static function requireFloat(string $field, $value): float
    {
        if (!is_numeric($value)) {
            throw ValidationException::invalidType($field, 'float', gettype($value));
        }

        return (float) $value;
    }

    /**
     * Validate that a value is within a specific range.
     *
     * @param string $field The field name
     * @param int|float $value The value to validate
     * @param int|float $min Minimum value (inclusive)
     * @param int|float $max Maximum value (inclusive)
     * @return int|float The validated value
     * @throws ValidationException If value is out of range
     */
    public static function requireRange(string $field, $value, $min, $max)
    {
        if ($value < $min || $value > $max) {
            throw ValidationException::outOfRange($field, $value, $min, $max);
        }

        return $value;
    }

    /**
     * Validate that a value is one of the allowed values.
     *
     * @param string $field The field name
     * @param mixed $value The value to validate
     * @param array<mixed> $allowedValues The allowed values
     * @return mixed The validated value
     * @throws ValidationException If value is not in allowed values
     */
    public static function requireOneOf(string $field, $value, array $allowedValues)
    {
        if (!in_array($value, $allowedValues, true)) {
            throw ValidationException::invalidValue($field, $value, array_map('strval', $allowedValues));
        }

        return $value;
    }

    /**
     * Validate an email address.
     *
     * @param string $field The field name
     * @param string $email The email to validate
     * @return string The validated email
     * @throws ValidationException If email format is invalid
     */
    public static function validateEmail(string $field, string $email): string
    {
        $normalized = trim($email);

        if (!filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::forField($field, 'Invalid email address format.');
        }

        return $normalized;
    }

    /**
     * Validate a URL.
     *
     * @param string $field The field name
     * @param string $url The URL to validate
     * @return string The validated URL
     * @throws ValidationException If URL format is invalid
     */
    public static function validateUrl(string $field, string $url): string
    {
        $normalized = trim($url);

        if (!filter_var($normalized, FILTER_VALIDATE_URL)) {
            throw ValidationException::forField($field, 'Invalid URL format.');
        }

        return $normalized;
    }

    /**
     * Validate a date string.
     *
     * @param string $field The field name
     * @param string $date The date string to validate
     * @param string $format Expected date format (default: Y-m-d)
     * @return string The validated date string
     * @throws ValidationException If date format is invalid
     */
    public static function validateDate(string $field, string $date, string $format = 'Y-m-d'): string
    {
        $dateTime = \DateTime::createFromFormat($format, $date);

        if ($dateTime === false || $dateTime->format($format) !== $date) {
            throw ValidationException::forField(
                $field,
                sprintf('Invalid date format. Expected format: %s', $format)
            );
        }

        return $date;
    }

    /**
     * Check if a PIN format is valid (without throwing exception).
     *
     * @param string $pinNumber The PIN number to check
     * @return bool True if valid, false otherwise
     */
    public static function isPinValid(string $pinNumber): bool
    {
        try {
            self::validatePin($pinNumber);
            return true;
        } catch (InvalidPinFormatException $e) {
            return false;
        }
    }

    /**
     * Check if a TCC format is valid (without throwing exception).
     *
     * @param string $tccNumber The TCC number to check
     * @return bool True if valid, false otherwise
     */
    public static function isTccValid(string $tccNumber): bool
    {
        try {
            self::validateTcc($tccNumber);
            return true;
        } catch (InvalidTccFormatException $e) {
            return false;
        }
    }

    /**
     * Normalize a string (trim and uppercase).
     *
     * @param string $value The string to normalize
     * @return string The normalized string
     */
    private static function normalizeString(string $value): string
    {
        return strtoupper(trim($value));
    }
}
