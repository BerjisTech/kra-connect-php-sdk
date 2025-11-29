<?php

declare(strict_types=1);

namespace KraConnect\Models;

/**
 * PIN Verification Result
 *
 * Contains the result of a KRA PIN verification request.
 *
 * @package KraConnect\Models
 */
class PinVerificationResult
{
    /**
     * Create a new PIN verification result.
     *
     * @param string $pinNumber The PIN number that was verified
     * @param bool $isValid Whether the PIN is valid
     * @param string|null $taxpayerName The taxpayer's name (if available)
     * @param string|null $status The taxpayer's status (e.g., 'active', 'inactive')
     * @param string|null $taxpayerType The type of taxpayer (e.g., 'individual', 'company')
     * @param string|null $registrationDate The date when the PIN was registered
     * @param array<string, mixed> $additionalData Additional data from the API
     * @param string $verifiedAt The timestamp when verification was performed
     */
    public function __construct(
        public readonly string $pinNumber,
        public readonly bool $isValid,
        public readonly ?string $taxpayerName = null,
        public readonly ?string $status = null,
        public readonly ?string $taxpayerType = null,
        public readonly ?string $registrationDate = null,
        public readonly array $additionalData = [],
        public readonly string $verifiedAt = ''
    ) {
    }

    /**
     * Create instance from API response data.
     *
     * @param array<string, mixed> $data The API response data
     * @return self
     */
    public static function fromApiResponse(array $data): self
    {
        return new self(
            pinNumber: $data['pin_number'] ?? $data['pinNumber'] ?? '',
            isValid: $data['is_valid'] ?? $data['isValid'] ?? false,
            taxpayerName: $data['taxpayer_name'] ?? $data['taxpayerName'] ?? null,
            status: $data['status'] ?? null,
            taxpayerType: $data['taxpayer_type'] ?? $data['taxpayerType'] ?? null,
            registrationDate: $data['registration_date'] ?? $data['registrationDate'] ?? null,
            additionalData: $data['additional_data'] ?? $data['additionalData'] ?? [],
            verifiedAt: $data['verified_at'] ?? $data['verifiedAt'] ?? date('c')
        );
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'pin_number' => $this->pinNumber,
            'is_valid' => $this->isValid,
            'taxpayer_name' => $this->taxpayerName,
            'status' => $this->status,
            'taxpayer_type' => $this->taxpayerType,
            'registration_date' => $this->registrationDate,
            'additional_data' => $this->additionalData,
            'verified_at' => $this->verifiedAt
        ];
    }

    /**
     * Convert to JSON string.
     *
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }

    /**
     * Check if the PIN is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->isValid && strtolower($this->status ?? '') === 'active';
    }

    /**
     * Check if the taxpayer is a company.
     *
     * @return bool
     */
    public function isCompany(): bool
    {
        return strtolower($this->taxpayerType ?? '') === 'company';
    }

    /**
     * Check if the taxpayer is an individual.
     *
     * @return bool
     */
    public function isIndividual(): bool
    {
        return strtolower($this->taxpayerType ?? '') === 'individual';
    }
}
