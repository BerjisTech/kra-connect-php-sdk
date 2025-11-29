<?php

declare(strict_types=1);

namespace KraConnect\Tests\Unit\Models;

use KraConnect\Tests\TestCase;
use KraConnect\Models\TccVerificationResult;

class TccVerificationResultTest extends TestCase
{
    public function testConstructor(): void
    {
        $result = new TccVerificationResult(
            tccNumber: 'TCC123456',
            isValid: true,
            taxpayerName: 'John Doe',
            expiryDate: '2024-12-31',
            isExpired: false
        );

        $this->assertSame('TCC123456', $result->tccNumber);
        $this->assertTrue($result->isValid);
        $this->assertSame('John Doe', $result->taxpayerName);
        $this->assertSame('2024-12-31', $result->expiryDate);
        $this->assertFalse($result->isExpired);
    }

    public function testIsCurrentlyValid(): void
    {
        $validResult = new TccVerificationResult(
            tccNumber: 'TCC123456',
            isValid: true,
            isExpired: false,
            status: 'active'
        );

        $expiredResult = new TccVerificationResult(
            tccNumber: 'TCC123456',
            isValid: true,
            isExpired: true,
            status: 'expired'
        );

        $this->assertTrue($validResult->isCurrentlyValid());
        $this->assertFalse($expiredResult->isCurrentlyValid());
    }

    public function testGetDaysUntilExpiry(): void
    {
        $futureDate = (new \DateTime())->modify('+30 days')->format('Y-m-d');

        $result = new TccVerificationResult(
            tccNumber: 'TCC123456',
            isValid: true,
            expiryDate: $futureDate
        );

        $days = $result->getDaysUntilExpiry();

        $this->assertIsInt($days);
        $this->assertGreaterThanOrEqual(29, $days);
        $this->assertLessThanOrEqual(31, $days);
    }

    public function testGetDaysUntilExpiryWithNoDate(): void
    {
        $result = new TccVerificationResult(
            tccNumber: 'TCC123456',
            isValid: true
        );

        $this->assertNull($result->getDaysUntilExpiry());
    }

    public function testIsExpiringSoon(): void
    {
        $soonDate = (new \DateTime())->modify('+15 days')->format('Y-m-d');
        $farDate = (new \DateTime())->modify('+60 days')->format('Y-m-d');

        $expiringSoon = new TccVerificationResult(
            tccNumber: 'TCC123456',
            isValid: true,
            expiryDate: $soonDate
        );

        $notExpiringSoon = new TccVerificationResult(
            tccNumber: 'TCC123457',
            isValid: true,
            expiryDate: $farDate
        );

        $this->assertTrue($expiringSoon->isExpiringSoon(30));
        $this->assertFalse($notExpiringSoon->isExpiringSoon(30));
    }

    public function testToArray(): void
    {
        $result = new TccVerificationResult(
            tccNumber: 'TCC123456',
            isValid: true
        );

        $array = $result->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('tcc_number', $array);
        $this->assertArrayHasKey('is_valid', $array);
    }
}
