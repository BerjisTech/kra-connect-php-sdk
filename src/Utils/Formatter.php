<?php

declare(strict_types=1);

namespace KraConnect\Utils;

/**
 * Data Formatter Utility
 *
 * Provides formatting functions for displaying KRA data.
 *
 * @package KraConnect\Utils
 */
class Formatter
{
    /**
     * Mask a PIN number for display (show first 2 and last 1 characters).
     *
     * @param string $pinNumber The PIN number to mask
     * @return string The masked PIN number
     *
     * @example
     * Formatter::maskPin('P051234567A') // Returns: "P0********A"
     */
    public static function maskPin(string $pinNumber): string
    {
        if (strlen($pinNumber) < 4) {
            return str_repeat('*', strlen($pinNumber));
        }

        $start = substr($pinNumber, 0, 2);
        $end = substr($pinNumber, -1);
        $middle = str_repeat('*', strlen($pinNumber) - 3);

        return $start . $middle . $end;
    }

    /**
     * Mask a TCC number for display (show first 3 characters).
     *
     * @param string $tccNumber The TCC number to mask
     * @return string The masked TCC number
     *
     * @example
     * Formatter::maskTcc('TCC123456') // Returns: "TCC******"
     */
    public static function maskTcc(string $tccNumber): string
    {
        if (strlen($tccNumber) <= 3) {
            return $tccNumber;
        }

        $start = substr($tccNumber, 0, 3);
        $middle = str_repeat('*', strlen($tccNumber) - 3);

        return $start . $middle;
    }

    /**
     * Mask an API key for display (show first 4 and last 4 characters).
     *
     * @param string $apiKey The API key to mask
     * @return string The masked API key
     *
     * @example
     * Formatter::maskApiKey('sk_live_1234567890abcdef') // Returns: "sk_l***************cdef"
     */
    public static function maskApiKey(string $apiKey): string
    {
        if (strlen($apiKey) < 12) {
            return str_repeat('*', strlen($apiKey));
        }

        $start = substr($apiKey, 0, 4);
        $end = substr($apiKey, -4);
        $middle = str_repeat('*', strlen($apiKey) - 8);

        return $start . $middle . $end;
    }

    /**
     * Format a currency amount.
     *
     * @param float $amount The amount to format
     * @param string $currency The currency code (default: KES)
     * @param int $decimals Number of decimal places (default: 2)
     * @return string The formatted amount
     *
     * @example
     * Formatter::formatCurrency(1234.56) // Returns: "KES 1,234.56"
     */
    public static function formatCurrency(float $amount, string $currency = 'KES', int $decimals = 2): string
    {
        return sprintf('%s %s', $currency, number_format($amount, $decimals));
    }

    /**
     * Format a tax period (YYYYMM) to a readable string.
     *
     * @param string $period The period in YYYYMM format
     * @param string $format The output format (default: 'F Y' - e.g., "January 2024")
     * @return string The formatted period
     *
     * @example
     * Formatter::formatPeriod('202401') // Returns: "January 2024"
     */
    public static function formatPeriod(string $period, string $format = 'F Y'): string
    {
        if (strlen($period) !== 6) {
            return $period;
        }

        try {
            $year = (int) substr($period, 0, 4);
            $month = (int) substr($period, 4, 2);

            $date = new \DateTime();
            $date->setDate($year, $month, 1);

            return $date->format($format);
        } catch (\Exception $e) {
            return $period;
        }
    }

    /**
     * Format a date string to a different format.
     *
     * @param string $date The date string
     * @param string $outputFormat The desired output format (default: 'd M Y')
     * @param string $inputFormat The input format (default: Y-m-d)
     * @return string The formatted date
     *
     * @example
     * Formatter::formatDate('2024-01-15') // Returns: "15 Jan 2024"
     */
    public static function formatDate(
        string $date,
        string $outputFormat = 'd M Y',
        string $inputFormat = 'Y-m-d'
    ): string {
        try {
            $dateTime = \DateTime::createFromFormat($inputFormat, $date);

            if ($dateTime === false) {
                // Try parsing as ISO 8601 if input format doesn't work
                $dateTime = new \DateTime($date);
            }

            return $dateTime->format($outputFormat);
        } catch (\Exception $e) {
            return $date;
        }
    }

    /**
     * Format a timestamp to a relative time string.
     *
     * @param string $timestamp The timestamp (ISO 8601 or Unix timestamp)
     * @return string The relative time string (e.g., "2 hours ago")
     */
    public static function formatRelativeTime(string $timestamp): string
    {
        try {
            $dateTime = new \DateTime($timestamp);
            $now = new \DateTime();
            $interval = $now->diff($dateTime);

            if ($interval->y > 0) {
                return $interval->y === 1 ? '1 year ago' : $interval->y . ' years ago';
            }

            if ($interval->m > 0) {
                return $interval->m === 1 ? '1 month ago' : $interval->m . ' months ago';
            }

            if ($interval->d > 0) {
                return $interval->d === 1 ? '1 day ago' : $interval->d . ' days ago';
            }

            if ($interval->h > 0) {
                return $interval->h === 1 ? '1 hour ago' : $interval->h . ' hours ago';
            }

            if ($interval->i > 0) {
                return $interval->i === 1 ? '1 minute ago' : $interval->i . ' minutes ago';
            }

            return 'just now';
        } catch (\Exception $e) {
            return $timestamp;
        }
    }

    /**
     * Format a file size in bytes to a human-readable string.
     *
     * @param int $bytes The size in bytes
     * @param int $decimals Number of decimal places (default: 2)
     * @return string The formatted size
     *
     * @example
     * Formatter::formatFileSize(1536) // Returns: "1.50 KB"
     */
    public static function formatFileSize(int $bytes, int $decimals = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = (int) floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $pow = max(0, $pow);

        $bytes /= (1 << (10 * $pow));

        return number_format($bytes, $decimals) . ' ' . $units[$pow];
    }

    /**
     * Format a percentage value.
     *
     * @param float $value The value (0-100 or 0-1)
     * @param int $decimals Number of decimal places (default: 2)
     * @param bool $isDecimal Whether the value is in decimal format (0-1) or percentage (0-100)
     * @return string The formatted percentage
     *
     * @example
     * Formatter::formatPercentage(0.1556, 2, true) // Returns: "15.56%"
     */
    public static function formatPercentage(float $value, int $decimals = 2, bool $isDecimal = false): string
    {
        $percentage = $isDecimal ? $value * 100 : $value;
        return number_format($percentage, $decimals) . '%';
    }

    /**
     * Format a phone number to a standard format.
     *
     * @param string $phoneNumber The phone number to format
     * @param string $countryCode The country code (default: +254 for Kenya)
     * @return string The formatted phone number
     *
     * @example
     * Formatter::formatPhoneNumber('0712345678') // Returns: "+254712345678"
     */
    public static function formatPhoneNumber(string $phoneNumber, string $countryCode = '+254'): string
    {
        // Remove all non-digit characters
        $cleaned = preg_replace('/\D/', '', $phoneNumber) ?? '';

        // Remove leading zero if present
        if ($cleaned !== '' && substr($cleaned, 0, 1) === '0') {
            $cleaned = substr($cleaned, 1);
        }

        // Add country code
        return $countryCode . $cleaned;
    }

    /**
     * Truncate a string to a specified length with ellipsis.
     *
     * @param string $text The text to truncate
     * @param int $length The maximum length
     * @param string $suffix The suffix to append (default: '...')
     * @return string The truncated text
     *
     * @example
     * Formatter::truncate('This is a long text', 10) // Returns: "This is..."
     */
    public static function truncate(string $text, int $length, string $suffix = '...'): string
    {
        if (strlen($text) <= $length) {
            return $text;
        }

        return substr($text, 0, $length - strlen($suffix)) . $suffix;
    }

    /**
     * Convert snake_case to Title Case.
     *
     * @param string $text The text to convert
     * @return string The converted text
     *
     * @example
     * Formatter::snakeToTitle('taxpayer_name') // Returns: "Taxpayer Name"
     */
    public static function snakeToTitle(string $text): string
    {
        return ucwords(str_replace('_', ' ', $text));
    }

    /**
     * Convert camelCase to Title Case.
     *
     * @param string $text The text to convert
     * @return string The converted text
     *
     * @example
     * Formatter::camelToTitle('taxpayerName') // Returns: "Taxpayer Name"
     */
    public static function camelToTitle(string $text): string
    {
        $spaced = preg_replace('/([a-z])([A-Z])/', '$1 $2', $text);
        if ($spaced === null) {
            $spaced = $text;
        }
        return ucwords($spaced);
    }

    /**
     * Format a boolean value as Yes/No.
     *
     * @param bool $value The boolean value
     * @return string 'Yes' or 'No'
     */
    public static function formatBoolean(bool $value): string
    {
        return $value ? 'Yes' : 'No';
    }

    /**
     * Format an array as a comma-separated string.
     *
     * @param array<mixed> $items The array items
     * @param string $separator The separator (default: ', ')
     * @return string The formatted string
     */
    public static function formatList(array $items, string $separator = ', '): string
    {
        return implode($separator, $items);
    }
}
