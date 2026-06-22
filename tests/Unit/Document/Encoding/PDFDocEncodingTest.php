<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Encoding;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\Encoding\PDFDocEncoding;

#[CoversClass(PDFDocEncoding::class)]
class PDFDocEncodingTest extends TestCase {
    public function testTextToUnicode(): void {
        static::assertSame('a', PDFDocEncoding::textToUnicode('a')); // ASCII
        static::assertSame('•', PDFDocEncoding::textToUnicode("\x80")); // Start of custom range
        static::assertSame('�', PDFDocEncoding::textToUnicode("\x9f")); // Undefined in custom range
        static::assertSame('€', PDFDocEncoding::textToUnicode("\xa0")); // addition in PDF 1.3, Table D.2
        static::assertSame('¡', PDFDocEncoding::textToUnicode("\xa1")); // after custom range in ISO 8591-1
        static::assertSame('ÿ', PDFDocEncoding::textToUnicode("\xff")); // end of ISO 8591-1
    }
}
