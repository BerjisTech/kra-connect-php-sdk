<?php

declare(strict_types=1);

namespace KraConnect\Models;

/**
 * NIL Return Filing Result
 *
 * Contains the result of a KRA NIL return filing request.
 *
 * @package KraConnect\Models
 */
class NilReturnResult
{
    /**
     * Create a new NIL return result.
     *
     * @param bool $success Whether the NIL return was filed successfully
     * @param string|null $pinNumber The taxpayer's PIN number
     * @param string|null $obligationId The obligation ID
     * @param string|null $period The tax period (e.g., '202401')
     * @param string|null $referenceNumber The filing reference number
     * @param string|null $filingDate The date when the return was filed
     * @param string|null $acknowledgementNumber The acknowledgement number
     * @param string|null $status The filing status (e.g., 'accepted', 'pending', 'rejected')
     * @param string|null $message The response message from KRA
     * @param array<string, mixed> $additionalData Additional data from the API
     * @param string $filedAt The timestamp when filing was performed
     */
    public function __construct(
        public readonly bool $success,
        public readonly ?string $pinNumber = null,
        public readonly ?string $obligationId = null,
        public readonly ?string $period = null,
        public readonly ?string $referenceNumber = null,
        public readonly ?string $filingDate = null,
        public readonly ?string $acknowledgementNumber = null,
        public readonly ?string $status = null,
        public readonly ?string $message = null,
        public readonly array $additionalData = [],
        public readonly string $filedAt = ''
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
            success: $data['success'] ?? false,
            pinNumber: $data['pin_number'] ?? $data['pinNumber'] ?? null,
            obligationId: $data['obligation_id'] ?? $data['obligationId'] ?? null,
            period: $data['period'] ?? null,
            referenceNumber: $data['reference_number'] ?? $data['referenceNumber'] ?? null,
            filingDate: $data['filing_date'] ?? $data['filingDate'] ?? null,
            acknowledgementNumber: $data['acknowledgement_number'] ?? $data['acknowledgementNumber'] ?? null,
            status: $data['status'] ?? null,
            message: $data['message'] ?? null,
            additionalData: $data['additional_data'] ?? $data['additionalData'] ?? [],
            filedAt: $data['filed_at'] ?? $data['filedAt'] ?? date('c')
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
            'success' => $this->success,
            'pin_number' => $this->pinNumber,
            'obligation_id' => $this->obligationId,
            'period' => $this->period,
            'reference_number' => $this->referenceNumber,
            'filing_date' => $this->filingDate,
            'acknowledgement_number' => $this->acknowledgementNumber,
            'status' => $this->status,
            'message' => $this->message,
            'additional_data' => $this->additionalData,
            'filed_at' => $this->filedAt
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
     * Check if the filing was accepted by KRA.
     *
     * @return bool
     */
    public function isAccepted(): bool
    {
        return $this->success && strtolower($this->status ?? '') === 'accepted';
    }

    /**
     * Check if the filing is pending approval.
     *
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->success && strtolower($this->status ?? '') === 'pending';
    }

    /**
     * Check if the filing was rejected.
     *
     * @return bool
     */
    public function isRejected(): bool
    {
        return !$this->success || strtolower($this->status ?? '') === 'rejected';
    }

    /**
     * Get the tax year from the period.
     *
     * @return int|null
     */
    public function getTaxYear(): ?int
    {
        if ($this->period === null || $this->period === '' || strlen($this->period) < 4) {
            return null;
        }

        return (int) substr($this->period, 0, 4);
    }

    /**
     * Get the tax month from the period.
     *
     * @return int|null
     */
    public function getTaxMonth(): ?int
    {
        if ($this->period === null || $this->period === '' || strlen($this->period) < 6) {
            return null;
        }

        return (int) substr($this->period, 4, 2);
    }

    /**
     * Get a formatted period string.
     *
     * @return string|null
     */
    public function getFormattedPeriod(): ?string
    {
        $year = $this->getTaxYear();
        $month = $this->getTaxMonth();

        if ($year === null || $month === null) {
            return $this->period;
        }

        /** @var int|false $timestamp */
        $timestamp = mktime(0, 0, 0, $month, 1);
        if ($timestamp === false) {
            return $this->period;
        }

        $monthName = date('F', $timestamp);
        return sprintf('%s %d', $monthName, $year);
    }
}
