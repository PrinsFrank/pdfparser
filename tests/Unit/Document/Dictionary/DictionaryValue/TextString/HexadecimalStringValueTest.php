<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Dictionary\DictionaryValue\TextString;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString\HexadecimalStringValue;
use PrinsFrank\PdfParser\Exception\ParseFailureException;

#[CoversClass(HexadecimalStringValue::class)]
class HexadecimalStringValueTest extends TestCase {
    public function testFromValue(): void {
        static::assertEquals(new HexadecimalStringValue('0000'), HexadecimalStringValue::fromValue('<0000>'));
    }

    /** @see 7.9.2.2 Text string type — UTF-16BE with a leading byte order mark */
    public function testGetTextConvertsUTF16BEToUTF8(): void {
        // "Tïtle" as UTF-16BE (FE FF BOM) written as a hex string
        static::assertSame(
            'Tïtle',
            (new HexadecimalStringValue('FEFF005400EF0074006C0065'))->getText(),
        );
    }

    /** @see 7.9.2.2 Text string type — UTF-16LE with a leading byte order mark */
    public function testGetTextConvertsUTF16LEToUTF8(): void {
        static::assertSame(
            'Tïtle',
            (new HexadecimalStringValue('FFFE5400EF0074006C006500'))->getText(),
        );
    }

    /** @see 7.9.2.2.1 Text string type — UTF-8 with a leading byte order mark (PDF 2.0) */
    public function testGetTextStripsUTF8BOM(): void {
        static::assertSame(
            'Tïtle',
            (new HexadecimalStringValue('EFBBBF54C3AF746C65'))->getText(),
        );
    }

    /** @see 7.9.2.2 Text string type — PDFDocEncoding (no byte order mark) is normalized to valid UTF-8 */
    public function testGetTextNormalizesPDFDocEncodingToUTF8(): void {
        // 0xFC ("ü", shared with Latin-1) written as a literal octal escape and as a hex string
        static::assertSame(
            'für',
            (new HexadecimalStringValue('66FC72'))->getText(),
        );

        // 0x80 ("•") and 0xA0 ("€") sit in the range where PDFDocEncoding diverges from Latin-1
        static::assertSame(
            '•€',
            (new HexadecimalStringValue('80A0'))->getText(),
        );
    }

    /** @see 7.3.4.3 — a final missing digit in a hexadecimal string is assumed to be 0 */
    public function testGetBinaryStringPadsOddLengthHexString(): void {
        static::assertSame(
            "\x90\x1F\xA0",
            (new HexadecimalStringValue('901FA'))->getBinaryString(),
        );
    }

    /** @see 7.3.4.3 — white-space within a hexadecimal string is ignored */
    public function testGetBinaryStringIgnoresWhitespaceInHexString(): void {
        static::assertSame(
            "\x90\x1F\xA3",
            (new HexadecimalStringValue("90 1F\tA3"))->getBinaryString(),
        );

        static::assertSame(
            'He',
            (new HexadecimalStringValue("FE FF 00 48\n00 65"))->getText(),
        );
    }

    /** @see 7.3.4.3 — a hexadecimal string with non-hex content is rejected */
    public function testGetBinaryStringRejectsInvalidHexString(): void {
        $this->expectException(ParseFailureException::class);
        (new HexadecimalStringValue('90ZZ'))->getBinaryString();
    }
}
