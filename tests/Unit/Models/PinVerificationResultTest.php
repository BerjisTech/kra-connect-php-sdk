<?php

declare(strict_types=1);

namespace KraConnect\Tests\Unit\Models;

use KraConnect\Tests\TestCase;
use KraConnect\Models\PinVerificationResult;

class PinVerificationResultTest extends TestCase
{
    public function testConstructor(): void
    {
        $result = new PinVerificationResult(
            pinNumber: 'P051234567A',
            isValid: true,
            taxpayerName: 'John Doe',
            status: 'active',
            taxpayerType: 'individual'
        );

        $this->assertSame('P051234567A', $result->pinNumber);
        $this->assertTrue($result->isValid);
        $this->assertSame('John Doe', $result->taxpayerName);
        $this->assertSame('active', $result->status);
        $this->assertSame('individual', $result->taxpayerType);
    }

    public function testFromApiResponse(): void
    {
        $apiData = [
            'pin_number' => 'P051234567A',
            'is_valid' => true,
            'taxpayer_name' => 'Jane Doe',
            'status' => 'active',
            'taxpayer_type' => 'company'
        ];

        $result = PinVerificationResult::fromApiResponse($apiData);

        $this->assertSame('P051234567A', $result->pinNumber);
        $this->assertTrue($result->isValid);
        $this->assertSame('Jane Doe', $result->taxpayerName);
    }

    public function testFromApiResponseWithCamelCase(): void
    {
        $apiData = [
            'pinNumber' => 'P051234567A',
            'isValid' => true,
            'taxpayerName' => 'Jane Doe'
        ];

        $result = PinVerificationResult::fromApiResponse($apiData);

        $this->assertSame('P051234567A', $result->pinNumber);
        $this->assertTrue($result->isValid);
    }

    public function testToArray(): void
    {
        $result = new PinVerificationResult(
            pinNumber: 'P051234567A',
            isValid: true,
            taxpayerName: 'John Doe'
        );

        $array = $result->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('pin_number', $array);
        $this->assertArrayHasKey('is_valid', $array);
        $this->assertSame('P051234567A', $array['pin_number']);
    }

    public function testToJson(): void
    {
        $result = new PinVerificationResult(
            pinNumber: 'P051234567A',
            isValid: true
        );

        $json = $result->toJson();

        $this->assertJson($json);
        $decoded = json_decode($json, true);
        $this->assertSame('P051234567A', $decoded['pin_number']);
    }

    public function testIsActive(): void
    {
        $activeResult = new PinVerificationResult(
            pinNumber: 'P051234567A',
            isValid: true,
            status: 'active'
        );

        $inactiveResult = new PinVerificationResult(
            pinNumber: 'P051234567B',
            isValid: true,
            status: 'inactive'
        );

        $this->assertTrue($activeResult->isActive());
        $this->assertFalse($inactiveResult->isActive());
    }

    public function testIsCompany(): void
    {
        $companyResult = new PinVerificationResult(
            pinNumber: 'P051234567A',
            isValid: true,
            taxpayerType: 'company'
        );

        $individualResult = new PinVerificationResult(
            pinNumber: 'P051234567B',
            isValid: true,
            taxpayerType: 'individual'
        );

        $this->assertTrue($companyResult->isCompany());
        $this->assertFalse($individualResult->isCompany());
    }

    public function testIsIndividual(): void
    {
        $individualResult = new PinVerificationResult(
            pinNumber: 'P051234567A',
            isValid: true,
            taxpayerType: 'individual'
        );

        $companyResult = new PinVerificationResult(
            pinNumber: 'P051234567B',
            isValid: true,
            taxpayerType: 'company'
        );

        $this->assertTrue($individualResult->isIndividual());
        $this->assertFalse($companyResult->isIndividual());
    }
}
