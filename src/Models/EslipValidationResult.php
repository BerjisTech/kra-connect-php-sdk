<?php

declare(strict_types=1);

namespace KraConnect\Models;

/**
 * E-slip Validation Result
 *
 * Contains the result of a KRA e-slip validation request.
 *
 * @package KraConnect\Models
 */
class EslipValidationResult
{
    /**
     * Create a new e-slip validation result.
     *
     * @param string $eslipNumber The e-slip number that was validated
     * @param bool $isValid Whether the e-slip is valid
     * @param string|null $taxpayerPin The taxpayer's PIN number
     * @param string|null $taxpayerName The taxpayer's name
     * @param float|null $amount The payment amount
     * @param string|null $currency The payment currency (e.g., 'KES')
     * @param string|null $paymentDate The date when payment was made
     * @param string|null $paymentReference The payment reference number
     * @param string|null $obligationType The type of tax obligation
     * @param string|null $obligationPeriod The tax period (e.g., '202401')
     * @param string|null $status The e-slip status (e.g., 'paid', 'pending', 'cancelled')
     * @param array<string, mixed> $additionalData Additional data from the API
     * @param string $validatedAt The timestamp when validation was performed
     */
    public function __construct(
        public readonly string $eslipNumber,
        public readonly bool $isValid,
        public readonly ?string $taxpayerPin = null,
        public readonly ?string $taxpayerName = null,
        public readonly ?float $amount = null,
        public readonly ?string $currency = null,
        public readonly ?string $paymentDate = null,
        public readonly ?string $paymentReference = null,
        public readonly ?string $obligationType = null,
        public readonly ?string $obligationPeriod = null,
        public readonly ?string $status = null,
        public readonly array $additionalData = [],
        public readonly string $validatedAt = ''
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
            eslipNumber: $data['eslip_number'] ?? $data['eslipNumber'] ?? '',
            isValid: $data['is_valid'] ?? $data['isValid'] ?? false,
            taxpayerPin: $data['taxpayer_pin'] ?? $data['taxpayerPin'] ?? null,
            taxpayerName: $data['taxpayer_name'] ?? $data['taxpayerName'] ?? null,
            amount: isset($data['amount']) ? (float) $data['amount'] : null,
            currency: $data['currency'] ?? null,
            paymentDate: $data['payment_date'] ?? $data['paymentDate'] ?? null,
            paymentReference: $data['payment_reference'] ?? $data['paymentReference'] ?? null,
            obligationType: $data['obligation_type'] ?? $data['obligationType'] ?? null,
            obligationPeriod: $data['obligation_period'] ?? $data['obligationPeriod'] ?? null,
            status: $data['status'] ?? null,
            additionalData: $data['additional_data'] ?? $data['additionalData'] ?? [],
            validatedAt: $data['validated_at'] ?? $data['validatedAt'] ?? date('c')
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
            'eslip_number' => $this->eslipNumber,
            'is_valid' => $this->isValid,
            'taxpayer_pin' => $this->taxpayerPin,
            'taxpayer_name' => $this->taxpayerName,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'payment_date' => $this->paymentDate,
            'payment_reference' => $this->paymentReference,
            'obligation_type' => $this->obligationType,
            'obligation_period' => $this->obligationPeriod,
            'status' => $this->status,
            'additional_data' => $this->additionalData,
            'validated_at' => $this->validatedAt
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
     * Check if the payment has been confirmed.
     *
     * @return bool
     */
    public function isPaid(): bool
    {
        return $this->isValid && strtolower($this->status ?? '') === 'paid';
    }

    /**
     * Check if the payment is pending.
     *
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->isValid && strtolower($this->status ?? '') === 'pending';
    }

    /**
     * Check if the payment was cancelled.
     *
     * @return bool
     */
    public function isCancelled(): bool
    {
        return strtolower($this->status ?? '') === 'cancelled';
    }

    /**
     * Get the formatted amount with currency.
     *
     * @return string
     */
    public function getFormattedAmount(): string
    {
        if ($this->amount === null) {
            return 'N/A';
        }

        $currency = $this->currency ?? 'KES';
        return sprintf('%s %s', $currency, number_format($this->amount, 2));
    }
}
