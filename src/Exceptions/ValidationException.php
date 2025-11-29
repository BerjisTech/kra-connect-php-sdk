<?php

declare(strict_types=1);

namespace KraConnect\Exceptions;

/**
 * Exception thrown when input validation fails.
 *
 * This exception is used for various validation errors including:
 * - Invalid parameter values
 * - Missing required parameters
 * - Parameter type mismatches
 * - Invalid parameter combinations
 *
 * @package KraConnect\Exceptions
 */
class ValidationException extends KraConnectException
{
    /** @var array<string, array<string>> */
    private array $errors;

    /**
     * Create exception for validation error.
     *
     * @param string $message The error message
     * @param array<string, array<string>> $errors Field-specific validation errors
     */
    public function __construct(string $message = 'Validation failed', array $errors = [])
    {
        $this->errors = $errors;

        $details = ['validation_errors' => $errors];

        parent::__construct($message, $details, 422);
    }

    /**
     * Create exception for a single field validation error.
     *
     * @param string $field The field name
     * @param string $error The error message
     * @return self
     */
    public static function forField(string $field, string $error): self
    {
        return new self(
            sprintf('Validation failed for field "%s": %s', $field, $error),
            [$field => [$error]]
        );
    }

    /**
     * Create exception for required field.
     *
     * @param string $field The field name
     * @return self
     */
    public static function requiredField(string $field): self
    {
        return self::forField($field, 'This field is required');
    }

    /**
     * Create exception for invalid type.
     *
     * @param string $field The field name
     * @param string $expectedType The expected type
     * @param string $actualType The actual type
     * @return self
     */
    public static function invalidType(string $field, string $expectedType, string $actualType): self
    {
        return self::forField(
            $field,
            sprintf('Expected type %s, got %s', $expectedType, $actualType)
        );
    }

    /**
     * Create exception for invalid value.
     *
     * @param string $field The field name
     * @param mixed $value The invalid value
     * @param array<string> $allowedValues The allowed values
     * @return self
     */
    public static function invalidValue(string $field, $value, array $allowedValues): self
    {
        return self::forField(
            $field,
            sprintf(
                'Invalid value "%s". Allowed values: %s',
                $value,
                implode(', ', $allowedValues)
            )
        );
    }

    /**
     * Create exception for value out of range.
     *
     * @param string $field The field name
     * @param mixed $value The value
     * @param mixed $min The minimum value
     * @param mixed $max The maximum value
     * @return self
     */
    public static function outOfRange(string $field, $value, $min, $max): self
    {
        return self::forField(
            $field,
            sprintf('Value %s is out of range. Must be between %s and %s', $value, $min, $max)
        );
    }

    /**
     * Get all validation errors.
     *
     * @return array<string, array<string>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get errors for a specific field.
     *
     * @param string $field The field name
     * @return array<string>
     */
    public function getFieldErrors(string $field): array
    {
        return $this->errors[$field] ?? [];
    }

    /**
     * Check if a specific field has errors.
     *
     * @param string $field The field name
     * @return bool
     */
    public function hasFieldErrors(string $field): bool
    {
        return isset($this->errors[$field]) && count($this->errors[$field]) > 0;
    }

    /**
     * Get a flat array of all error messages.
     *
     * @return array<string>
     */
    public function getAllErrorMessages(): array
    {
        $messages = [];
        foreach ($this->errors as $fieldErrors) {
            $messages = array_merge($messages, $fieldErrors);
        }
        return $messages;
    }
}
