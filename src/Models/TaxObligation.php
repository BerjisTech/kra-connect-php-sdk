<?php

declare(strict_types=1);

namespace KraConnect\Models;

/**
 * Tax Obligation
 *
 * Represents a specific tax obligation for a taxpayer.
 *
 * @package KraConnect\Models
 */
class TaxObligation
{
    /**
     * Create a new tax obligation.
     *
     * @param string $obligationId The unique obligation ID
     * @param string $obligationType The type of obligation (e.g., 'VAT', 'PAYE', 'Income Tax')
     * @param string|null $description The obligation description
     * @param string|null $status The obligation status (e.g., 'active', 'inactive')
     * @param string|null $registrationDate The date when the obligation was registered
     * @param string|null $effectiveDate The date when the obligation became effective
     * @param string|null $endDate The date when the obligation ended (if applicable)
     * @param string|null $frequency The filing frequency (e.g., 'monthly', 'quarterly', 'annually')
     * @param string|null $nextFilingDate The next expected filing date
     * @param bool $isActive Whether the obligation is currently active
     * @param array<string, mixed> $additionalData Additional data from the API
     */
    public function __construct(
        public readonly string $obligationId,
        public readonly string $obligationType,
        public readonly ?string $description = null,
        public readonly ?string $status = null,
        public readonly ?string $registrationDate = null,
        public readonly ?string $effectiveDate = null,
        public readonly ?string $endDate = null,
        public readonly ?string $frequency = null,
        public readonly ?string $nextFilingDate = null,
        public readonly bool $isActive = true,
        public readonly array $additionalData = []
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
            obligationId: $data['obligation_id'] ?? $data['obligationId'] ?? '',
            obligationType: $data['obligation_type'] ?? $data['obligationType'] ?? '',
            description: $data['description'] ?? null,
            status: $data['status'] ?? null,
            registrationDate: $data['registration_date'] ?? $data['registrationDate'] ?? null,
            effectiveDate: $data['effective_date'] ?? $data['effectiveDate'] ?? null,
            endDate: $data['end_date'] ?? $data['endDate'] ?? null,
            frequency: $data['frequency'] ?? null,
            nextFilingDate: $data['next_filing_date'] ?? $data['nextFilingDate'] ?? null,
            isActive: $data['is_active'] ?? $data['isActive'] ?? true,
            additionalData: $data['additional_data'] ?? $data['additionalData'] ?? []
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
            'obligation_id' => $this->obligationId,
            'obligation_type' => $this->obligationType,
            'description' => $this->description,
            'status' => $this->status,
            'registration_date' => $this->registrationDate,
            'effective_date' => $this->effectiveDate,
            'end_date' => $this->endDate,
            'frequency' => $this->frequency,
            'next_filing_date' => $this->nextFilingDate,
            'is_active' => $this->isActive,
            'additional_data' => $this->additionalData
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
     * Check if the obligation has ended.
     *
     * @return bool
     */
    public function hasEnded(): bool
    {
        if ($this->endDate === null || $this->endDate === '') {
            return false;
        }

        try {
            $endDateTime = new \DateTime($this->endDate);
            $now = new \DateTime();
            return $endDateTime < $now;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if filing is due soon (within specified days).
     *
     * @param int $days Number of days to consider as "soon" (default: 7)
     * @return bool
     */
    public function isFilingDueSoon(int $days = 7): bool
    {
        if (($this->nextFilingDate === null || $this->nextFilingDate === '') || !$this->isActive) {
            return false;
        }

        try {
            $filingDateTime = new \DateTime($this->nextFilingDate);
            $now = new \DateTime();
            $threshold = (clone $now)->modify("+{$days} days");

            return $filingDateTime >= $now && $filingDateTime <= $threshold;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if filing is overdue.
     *
     * @return bool
     */
    public function isFilingOverdue(): bool
    {
        if (($this->nextFilingDate === null || $this->nextFilingDate === '') || !$this->isActive) {
            return false;
        }

        try {
            $filingDateTime = new \DateTime($this->nextFilingDate);
            $now = new \DateTime();
            return $filingDateTime < $now;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get the number of days until next filing.
     *
     * @return int|null Number of days (negative if overdue), or null if no filing date
     */
    public function getDaysUntilFiling(): ?int
    {
        if ($this->nextFilingDate === null || $this->nextFilingDate === '') {
            return null;
        }

        try {
            $filingDateTime = new \DateTime($this->nextFilingDate);
            $now = new \DateTime();
            $interval = $now->diff($filingDateTime);
            $days = $interval->days;

            if ($days === false) {
                return null;
            }

            return $interval->invert ? -$days : $days;
        } catch (\Exception $e) {
            return null;
        }
    }
}
