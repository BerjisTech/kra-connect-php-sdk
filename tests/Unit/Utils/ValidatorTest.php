<?php

declare(strict_types=1);

namespace KraConnect\Tests\Unit\Utils;

use KraConnect\Tests\TestCase;
use KraConnect\Utils\Validator;
use KraConnect\Exceptions\InvalidPinFormatException;
use KraConnect\Exceptions\InvalidTccFormatException;
use KraConnect\Exceptions\ValidationException;

class ValidatorTest extends TestCase
{
    // ==================
    // PIN Validation Tests
    // ==================

    public function testValidatePinWithValidFormat(): void
    {
        $validPins = [
            'P051234567A',
            'P051234567B',
            'p051234567a', // Lowercase should be normalized
            '  P051234567A  ', // Whitespace should be trimmed
        ];

        $expected = [
            'P051234567A',
            'P051234567B',
            'P051234567A',
            'P051234567A',
        ];

        foreach ($validPins as $index => $pin) {
            $result = Validator::validatePin($pin);
            $this->assertSame($expected[$index], $result);
        }
    }

    public function testValidatePinWithInvalidFormat(): void
    {
        $invalidPins = [
            'INVALID',
            'P12345', // Too short
            'P051234567', // Missing letter
            'A051234567A', // Wrong prefix
            'P05123456AA', // Two letters
            '',
        ];

        foreach ($invalidPins as $pin) {
            $this->expectException(InvalidPinFormatException::class);
            Validator::validatePin($pin);
        }
    }

    public function testIsPinValid(): void
    {
        $this->assertTrue(Validator::isPinValid('P051234567A'));
        $this->assertFalse(Validator::isPinValid('INVALID'));
    }

    // ==================
    // TCC Validation Tests
    // ==================

    public function testValidateTccWithValidFormat(): void
    {
        $validTccs = [
            'TCC123456',
            'TCC999999999',
            'tcc123456', // Lowercase
            '  TCC123456  ', // Whitespace
        ];

        foreach ($validTccs as $tcc) {
            $result = Validator::validateTcc($tcc);
            $this->assertStringStartsWith('TCC', $result);
        }
    }

    public function testValidateTccWithInvalidFormat(): void
    {
        $invalidTccs = [
            'INVALID',
            'TCC', // No digits
            'TC123456', // Wrong prefix
            'TCC12345A', // Contains letter
            '',
        ];

        foreach ($invalidTccs as $tcc) {
            $this->expectException(InvalidTccFormatException::class);
            Validator::validateTcc($tcc);
        }
    }

    public function testIsTccValid(): void
    {
        $this->assertTrue(Validator::isTccValid('TCC123456'));
        $this->assertFalse(Validator::isTccValid('INVALID'));
    }

    // ==================
    // E-slip Validation Tests
    // ==================

    public function testValidateEslipWithValidFormat(): void
    {
        $result = Validator::validateEslip('1234567890');

        $this->assertSame('1234567890', $result);
    }

    public function testValidateEslipWithInvalidFormat(): void
    {
        $this->expectException(ValidationException::class);
        Validator::validateEslip('123'); // Too short
    }

    // ==================
    // Obligation ID Tests
    // ==================

    public function testValidateObligationIdWithValidFormat(): void
    {
        $validIds = [
            'OBL-123456',
            'OBL123456',
            'ABC-DEF-123',
        ];

        foreach ($validIds as $id) {
            $result = Validator::validateObligationId($id);
            $this->assertNotEmpty($result);
        }
    }

    // ==================
    // Period Validation Tests
    // ==================

    public function testValidatePeriodWithValidFormat(): void
    {
        $validPeriods = [
            '202401', // January 2024
            '202312', // December 2023
            '199901', // January 1999
        ];

        foreach ($validPeriods as $period) {
            $result = Validator::validatePeriod($period);
            $this->assertSame($period, $result);
        }
    }

    public function testValidatePeriodWithInvalidFormat(): void
    {
        $invalidPeriods = [
            '2024', // Too short
            '202413', // Invalid month (13)
            '202400', // Invalid month (00)
            'INVALID',
        ];

        foreach ($invalidPeriods as $period) {
            $this->expectException(ValidationException::class);
            Validator::validatePeriod($period);
        }
    }

    // ==================
    // Required Field Tests
    // ==================

    public function testRequireStringWithValidValue(): void
    {
        $result = Validator::requireString('test_field', '  value  ');

        $this->assertSame('value', $result);
    }

    public function testRequireStringWithEmptyValue(): void
    {
        $this->expectException(ValidationException::class);
        Validator::requireString('test_field', '');
    }

    public function testRequireStringWithNullValue(): void
    {
        $this->expectException(ValidationException::class);
        Validator::requireString('test_field', null);
    }

    public function testRequireInt(): void
    {
        $this->assertSame(123, Validator::requireInt('test', 123));
        $this->assertSame(123, Validator::requireInt('test', '123'));

        $this->expectException(ValidationException::class);
        Validator::requireInt('test', 'invalid');
    }

    public function testRequireFloat(): void
    {
        $this->assertSame(123.45, Validator::requireFloat('test', 123.45));
        $this->assertSame(123.45, Validator::requireFloat('test', '123.45'));

        $this->expectException(ValidationException::class);
        Validator::requireFloat('test', 'invalid');
    }

    // ==================
    // Range Validation Tests
    // ==================

    public function testRequireRangeWithValidValue(): void
    {
        $result = Validator::requireRange('age', 25, 18, 65);

        $this->assertSame(25, $result);
    }

    public function testRequireRangeWithOutOfRangeValue(): void
    {
        $this->expectException(ValidationException::class);
        Validator::requireRange('age', 100, 18, 65);
    }

    // ==================
    // Enum Validation Tests
    // ==================

    public function testRequireOneOfWithValidValue(): void
    {
        $result = Validator::requireOneOf('status', 'active', ['active', 'inactive']);

        $this->assertSame('active', $result);
    }

    public function testRequireOneOfWithInvalidValue(): void
    {
        $this->expectException(ValidationException::class);
        Validator::requireOneOf('status', 'invalid', ['active', 'inactive']);
    }

    // ==================
    // Email Validation Tests
    // ==================

    public function testValidateEmailWithValidEmail(): void
    {
        $result = Validator::validateEmail('email', 'test@example.com');

        $this->assertSame('test@example.com', $result);
    }

    public function testValidateEmailWithInvalidEmail(): void
    {
        $this->expectException(ValidationException::class);
        Validator::validateEmail('email', 'invalid-email');
    }

    // ==================
    // URL Validation Tests
    // ==================

    public function testValidateUrlWithValidUrl(): void
    {
        $result = Validator::validateUrl('website', 'https://example.com');

        $this->assertSame('https://example.com', $result);
    }

    public function testValidateUrlWithInvalidUrl(): void
    {
        $this->expectException(ValidationException::class);
        Validator::validateUrl('website', 'not-a-url');
    }

    // ==================
    // Date Validation Tests
    // ==================

    public function testValidateDateWithValidDate(): void
    {
        $result = Validator::validateDate('date', '2024-01-15');

        $this->assertSame('2024-01-15', $result);
    }

    public function testValidateDateWithCustomFormat(): void
    {
        $result = Validator::validateDate('date', '15/01/2024', 'd/m/Y');

        $this->assertSame('15/01/2024', $result);
    }

    public function testValidateDateWithInvalidDate(): void
    {
        $this->expectException(ValidationException::class);
        Validator::validateDate('date', 'invalid-date');
    }
}
