<?php

declare(strict_types=1);

namespace KraConnect\Models;

/**
 * Tax Compliance Certificate (TCC) Verification Result
 *
 * Contains the result of a KRA TCC verification request.
 *
 * @package KraConnect\Models
 */
class TccVerificationResult
{
    /**
     * Create a new TCC verification result.
     *
     * @param string $tccNumber The TCC number that was verified
     * @param bool $isValid Whether the TCC is valid
     * @param string|null $taxpayerName The taxpayer's name
     * @param string|null $pinNumber The associated PIN number
     * @param string|null $issueDate The date when the TCC was issued
     * @param string|null $expiryDate The date when the TCC expires
     * @param bool $isExpired Whether the TCC has expired
     * @param string|null $status The TCC status (e.g., 'active', 'expired', 'revoked')
     * @param string|null $certificateType The type of certificate
     * @param array<string, mixed> $additionalData Additional data from the API
     * @param string $verifiedAt The timestamp when verification was performed
     */
    public function __construct(
        public readonly string $tccNumber,
        public readonly bool $isValid,
        public readonly ?string $taxpayerName = null,
        public readonly ?string $pinNumber = null,
        public readonly ?string $issueDate = null,
        public readonly ?string $expiryDate = null,
        public readonly bool $isExpired = false,
        public readonly ?string $status = null,
        public readonly ?string $certificateType = null,
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
            tccNumber: $data['tcc_number'] ?? $data['tccNumber'] ?? '',
            isValid: $data['is_valid'] ?? $data['isValid'] ?? false,
            taxpayerName: $data['taxpayer_name'] ?? $data['taxpayerName'] ?? null,
            pinNumber: $data['pin_number'] ?? $data['pinNumber'] ?? null,
            issueDate: $data['issue_date'] ?? $data['issueDate'] ?? null,
            expiryDate: $data['expiry_date'] ?? $data['expiryDate'] ?? null,
            isExpired: $data['is_expired'] ?? $data['isExpired'] ?? false,
            status: $data['status'] ?? null,
            certificateType: $data['certificate_type'] ?? $data['certificateType'] ?? null,
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
            'tcc_number' => $this->tccNumber,
            'is_valid' => $this->isValid,
            'taxpayer_name' => $this->taxpayerName,
            'pin_number' => $this->pinNumber,
            'issue_date' => $this->issueDate,
            'expiry_date' => $this->expiryDate,
            'is_expired' => $this->isExpired,
            'status' => $this->status,
            'certificate_type' => $this->certificateType,
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
     * Check if the TCC is currently valid and not expired.
     *
     * @return bool
     */
    public function isCurrentlyValid(): bool
    {
        return $this->isValid && !$this->isExpired && strtolower($this->status ?? '') === 'active';
    }

    /**
     * Get the number of days until expiry.
     *
     * @return int|null Number of days until expiry, or null if no expiry date
     */
    public function getDaysUntilExpiry(): ?int
    {
        if ($this->expiryDate === null || $this->expiryDate === '') {
            return null;
        }

        try {
            $expiryDateTime = new \DateTime($this->expiryDate);
            $now = new \DateTime();
            $interval = $now->diff($expiryDateTime);
            $days = $interval->days;

            if ($days === false) {
                return null;
            }

            return $interval->invert ? -$days : $days;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Check if the TCC is expiring soon (within specified days).
     *
     * @param int $days Number of days to consider as "soon" (default: 30)
     * @return bool
     */
    public function isExpiringSoon(int $days = 30): bool
    {
        $daysUntilExpiry = $this->getDaysUntilExpiry();

        if ($daysUntilExpiry === null) {
            return false;
        }

        return $daysUntilExpiry >= 0 && $daysUntilExpiry <= $days;
    }
}
