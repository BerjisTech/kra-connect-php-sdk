<?php

declare(strict_types=1);

namespace KraConnect\Models;

/**
 * Taxpayer Details
 *
 * Contains detailed information about a taxpayer from KRA.
 *
 * @package KraConnect\Models
 */
class TaxpayerDetails
{
    /**
     * Create new taxpayer details.
     *
     * @param string $pinNumber The taxpayer's PIN number
     * @param string|null $taxpayerName The taxpayer's full name
     * @param string|null $taxpayerType The type of taxpayer (e.g., 'individual', 'company')
     * @param string|null $status The taxpayer status (e.g., 'active', 'inactive')
     * @param string|null $registrationDate The date when the PIN was registered
     * @param string|null $businessName The business name (if applicable)
     * @param string|null $tradingName The trading name (if applicable)
     * @param string|null $postalAddress The postal address
     * @param string|null $physicalAddress The physical address
     * @param string|null $emailAddress The email address
     * @param string|null $phoneNumber The phone number
     * @param array<TaxObligation> $obligations The taxpayer's tax obligations
     * @param array<string, mixed> $additionalData Additional data from the API
     * @param string $retrievedAt The timestamp when details were retrieved
     */
    public function __construct(
        public readonly string $pinNumber,
        public readonly ?string $taxpayerName = null,
        public readonly ?string $taxpayerType = null,
        public readonly ?string $status = null,
        public readonly ?string $registrationDate = null,
        public readonly ?string $businessName = null,
        public readonly ?string $tradingName = null,
        public readonly ?string $postalAddress = null,
        public readonly ?string $physicalAddress = null,
        public readonly ?string $emailAddress = null,
        public readonly ?string $phoneNumber = null,
        public readonly array $obligations = [],
        public readonly array $additionalData = [],
        public readonly string $retrievedAt = ''
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
        $obligations = [];
        if (isset($data['obligations']) && is_array($data['obligations'])) {
            foreach ($data['obligations'] as $obligationData) {
                $obligations[] = TaxObligation::fromApiResponse($obligationData);
            }
        }

        return new self(
            pinNumber: $data['pin_number'] ?? $data['pinNumber'] ?? '',
            taxpayerName: $data['taxpayer_name'] ?? $data['taxpayerName'] ?? null,
            taxpayerType: $data['taxpayer_type'] ?? $data['taxpayerType'] ?? null,
            status: $data['status'] ?? null,
            registrationDate: $data['registration_date'] ?? $data['registrationDate'] ?? null,
            businessName: $data['business_name'] ?? $data['businessName'] ?? null,
            tradingName: $data['trading_name'] ?? $data['tradingName'] ?? null,
            postalAddress: $data['postal_address'] ?? $data['postalAddress'] ?? null,
            physicalAddress: $data['physical_address'] ?? $data['physicalAddress'] ?? null,
            emailAddress: $data['email_address'] ?? $data['emailAddress'] ?? null,
            phoneNumber: $data['phone_number'] ?? $data['phoneNumber'] ?? null,
            obligations: $obligations,
            additionalData: $data['additional_data'] ?? $data['additionalData'] ?? [],
            retrievedAt: $data['retrieved_at'] ?? $data['retrievedAt'] ?? date('c')
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
            'taxpayer_name' => $this->taxpayerName,
            'taxpayer_type' => $this->taxpayerType,
            'status' => $this->status,
            'registration_date' => $this->registrationDate,
            'business_name' => $this->businessName,
            'trading_name' => $this->tradingName,
            'postal_address' => $this->postalAddress,
            'physical_address' => $this->physicalAddress,
            'email_address' => $this->emailAddress,
            'phone_number' => $this->phoneNumber,
            'obligations' => array_map(fn($o) => $o->toArray(), $this->obligations),
            'additional_data' => $this->additionalData,
            'retrieved_at' => $this->retrievedAt
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
     * Check if the taxpayer is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return strtolower($this->status ?? '') === 'active';
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

    /**
     * Get obligations by type.
     *
     * @param string $obligationType The obligation type to filter by
     * @return array<TaxObligation>
     */
    public function getObligationsByType(string $obligationType): array
    {
        return array_filter(
            $this->obligations,
            fn(TaxObligation $obligation) => strtolower($obligation->obligationType) === strtolower($obligationType)
        );
    }

    /**
     * Check if the taxpayer has a specific obligation.
     *
     * @param string $obligationType The obligation type to check
     * @return bool
     */
    public function hasObligation(string $obligationType): bool
    {
        return count($this->getObligationsByType($obligationType)) > 0;
    }

    /**
     * Get the display name (business name or taxpayer name).
     *
     * @return string|null
     */
    public function getDisplayName(): ?string
    {
        return $this->businessName ?? $this->tradingName ?? $this->taxpayerName;
    }
}
