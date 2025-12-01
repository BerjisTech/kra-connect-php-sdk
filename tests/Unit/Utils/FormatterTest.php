<?php

declare(strict_types=1);

namespace KraConnect\Tests\Unit\Utils;

use KraConnect\Tests\TestCase;
use KraConnect\Utils\Formatter;

class FormatterTest extends TestCase
{
    public function testMaskPin(): void
    {
        $this->assertSame('P0********A', Formatter::maskPin('P051234567A'));
        $this->assertSame('P0********B', Formatter::maskPin('P012345678B'));
    }

    public function testMaskTcc(): void
    {
        $this->assertSame('TCC******', Formatter::maskTcc('TCC123456'));
        $this->assertSame('TCC*********', Formatter::maskTcc('TCC123456789'));
    }

    public function testMaskApiKey(): void
    {
        $this->assertSame('sk_l****************cdef', Formatter::maskApiKey('sk_live_1234567890abcdef'));
    }

    public function testFormatCurrency(): void
    {
        $this->assertSame('KES 1,234.56', Formatter::formatCurrency(1234.56));
        $this->assertSame('USD 1,234.56', Formatter::formatCurrency(1234.56, 'USD'));
        $this->assertSame('KES 1,234.5', Formatter::formatCurrency(1234.5, 'KES', 1));
    }

    public function testFormatPeriod(): void
    {
        $this->assertSame('January 2024', Formatter::formatPeriod('202401'));
        $this->assertSame('December 2023', Formatter::formatPeriod('202312'));
    }

    public function testFormatPeriodWithCustomFormat(): void
    {
        $this->assertSame('2024-01', Formatter::formatPeriod('202401', 'Y-m'));
    }

    public function testFormatDate(): void
    {
        $this->assertSame('15 Jan 2024', Formatter::formatDate('2024-01-15'));
        $this->assertSame('2024-01-15', Formatter::formatDate('2024-01-15', 'Y-m-d'));
    }

    public function testFormatFileSize(): void
    {
        $this->assertSame('0.00 B', Formatter::formatFileSize(0));
        $this->assertSame('1.00 KB', Formatter::formatFileSize(1024));
        $this->assertSame('1.50 KB', Formatter::formatFileSize(1536));
        $this->assertSame('1.00 MB', Formatter::formatFileSize(1048576));
    }

    public function testFormatPercentage(): void
    {
        $this->assertSame('15.56%', Formatter::formatPercentage(0.1556, 2, true));
        $this->assertSame('75.00%', Formatter::formatPercentage(75, 2, false));
    }

    public function testFormatPhoneNumber(): void
    {
        $this->assertSame('+254712345678', Formatter::formatPhoneNumber('0712345678'));
        $this->assertSame('+254712345678', Formatter::formatPhoneNumber('712345678'));
        $this->assertSame('+1234567890', Formatter::formatPhoneNumber('234567890', '+1'));
    }

    public function testTruncate(): void
    {
        $this->assertSame('This is...', Formatter::truncate('This is a long text', 10));
        $this->assertSame('Short', Formatter::truncate('Short', 10));
        $this->assertSame('This is---', Formatter::truncate('This is a long text', 10, '---'));
    }

    public function testSnakeToTitle(): void
    {
        $this->assertSame('Taxpayer Name', Formatter::snakeToTitle('taxpayer_name'));
        $this->assertSame('Tax Obligation', Formatter::snakeToTitle('tax_obligation'));
    }

    public function testCamelToTitle(): void
    {
        $this->assertSame('Taxpayer Name', Formatter::camelToTitle('taxpayerName'));
        $this->assertSame('Tax Obligation Id', Formatter::camelToTitle('taxObligationId'));
    }

    public function testFormatBoolean(): void
    {
        $this->assertSame('Yes', Formatter::formatBoolean(true));
        $this->assertSame('No', Formatter::formatBoolean(false));
    }

    public function testFormatList(): void
    {
        $items = ['Apple', 'Banana', 'Cherry'];
        $this->assertSame('Apple, Banana, Cherry', Formatter::formatList($items));
        $this->assertSame('Apple; Banana; Cherry', Formatter::formatList($items, '; '));
    }
}
