<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Dictionary\DictionaryValue\TextString;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString\TextStringValue;
use PrinsFrank\PdfParser\Exception\ParseFailureException;

#[CoversClass(TextStringValue::class)]
class TextStringValueTest extends TestCase {
    public function testFromValue(): void {
        static::assertEquals(new TextStringValue('(foo)'), TextStringValue::fromValue('(foo)'));
    }

    /** @see 7.3.4.2, table 3 */
    public function testGetTextWithEscapeSequenceInLiteralString(): void {
        static::assertSame(
            "\n",
            (new TextStringValue('(\n)'))->getText(),
        );
        static::assertSame(
            "\r",
            (new TextStringValue('(\r)'))->getText(),
        );
        static::assertSame(
            "\t",
            (new TextStringValue('(\t)'))->getText(),
        );
        static::assertSame(
            "\x08",
            (new TextStringValue('(\b)'))->getText(),
        );
        static::assertSame(
            "\f",
            (new TextStringValue('(\f)'))->getText(),
        );
        static::assertSame(
            "(",
            (new TextStringValue('(\()'))->getText(),
        );
        static::assertSame(
            ")",
            (new TextStringValue('(\))'))->getText(),
        );
        static::assertSame(
            '\\',
            (new TextStringValue('(\\\\)'))->getText(),
        );
    }

    /** @see 7.3.4 */
    public function testGetTextWithOctalCharacters(): void {
        static::assertSame(
            'This string contains ¥two octal charactersÇ.',
            (new TextStringValue('(This string contains \245two octal characters\307.)'))->getText(),
        );
        static::assertSame(
            "\005",
            (new TextStringValue('(\005)'))->getText(),
        );
        static::assertSame(
            "\005" . '3',
            (new TextStringValue('(\0053)'))->getText(),
        );
        static::assertSame(
            "\005",
            (new TextStringValue('(\05)'))->getText(),
        );
        static::assertSame(
            "\005",
            (new TextStringValue('(\5)'))->getText(),
        );
        static::assertSame(
            '+',
            (new TextStringValue('(\053)'))->getText(),
        );
        static::assertSame(
            '+',
            (new TextStringValue('(\53)'))->getText(),
        );
    }

    /** @see 7.9.2.2 Text string type — UTF-16BE with a leading byte order mark */
    public function testGetTextConvertsUTF16BEToUTF8(): void {
        // "Tïtle" as UTF-16BE (FE FF BOM) written as a hex string
        static::assertSame(
            'Tïtle',
            (new TextStringValue('<FEFF005400EF0074006C0065>'))->getText(),
        );

        // The same UTF-16BE bytes written as a literal string with octal escapes
        static::assertSame(
            'Tïtle',
            (new TextStringValue("(\376\377\000T\000\357\000t\000l\000e)"))->getText(),
        );
    }

    /** @see 7.9.2.2 Text string type — UTF-16LE with a leading byte order mark */
    public function testGetTextConvertsUTF16LEToUTF8(): void {
        static::assertSame(
            'Tïtle',
            (new TextStringValue('<FFFE5400EF0074006C006500>'))->getText(),
        );
    }

    /** @see 7.9.2.2.1 Text string type — UTF-8 with a leading byte order mark (PDF 2.0) */
    public function testGetTextStripsUTF8BOM(): void {
        static::assertSame(
            'Tïtle',
            (new TextStringValue('<EFBBBF54C3AF746C65>'))->getText(),
        );
    }

    /** @see 7.9.2.2 Text string type — PDFDocEncoding (no byte order mark) is normalized to valid UTF-8 */
    public function testGetTextNormalizesPDFDocEncodingToUTF8(): void {
        // 0xFC ("ü", shared with Latin-1) written as a literal octal escape and as a hex string
        static::assertSame(
            'für',
            (new TextStringValue('(f\374r)'))->getText(),
        );
        static::assertSame(
            'für',
            (new TextStringValue('<66FC72>'))->getText(),
        );

        // 0x80 ("•") and 0xA0 ("€") sit in the range where PDFDocEncoding diverges from Latin-1
        static::assertSame(
            '•€',
            (new TextStringValue('<80A0>'))->getText(),
        );
    }

    /** @see 7.3.4.3 — a final missing digit in a hexadecimal string is assumed to be 0 */
    public function testGetBinaryStringPadsOddLengthHexString(): void {
        static::assertSame(
            "\x90\x1F\xA0",
            (new TextStringValue('<901FA>'))->getBinaryString(),
        );
    }

    /** @see 7.3.4.3 — white-space within a hexadecimal string is ignored */
    public function testGetBinaryStringIgnoresWhitespaceInHexString(): void {
        static::assertSame(
            "\x90\x1F\xA3",
            (new TextStringValue("<90 1F\tA3>"))->getBinaryString(),
        );

        static::assertSame(
            'He',
            (new TextStringValue("<FE FF 00 48\n00 65>"))->getText(),
        );
    }

    /** @see 7.3.4.3 — a hexadecimal string with non-hex content is rejected */
    public function testGetBinaryStringRejectsInvalidHexString(): void {
        $this->expectException(ParseFailureException::class);
        (new TextStringValue('<90ZZ>'))->getBinaryString();
    }

    /** @see 7.3.5, table 4 */
    public function testGetTextLiteralNames(): void {
        static::assertSame(
            '/Name1',
            (new TextStringValue('/Name1'))->getText(),
        );
        static::assertSame(
            '/ASomewhatLongerName',
            (new TextStringValue('/ASomewhatLongerName'))->getText(),
        );
        static::assertSame(
            '/A;Name_With-Various***Characters?',
            (new TextStringValue('/A;Name_With-Various***Characters?'))->getText(),
        );
        static::assertSame(
            '/1.2',
            (new TextStringValue('/1.2'))->getText(),
        );
        static::assertSame(
            '/$$',
            (new TextStringValue('/$$'))->getText(),
        );
        static::assertSame(
            '/@pattern',
            (new TextStringValue('/@pattern'))->getText(),
        );
        static::assertSame(
            '/.notdef',
            (new TextStringValue('/.notdef'))->getText(),
        );
        static::assertSame(
            '/Lime Green',
            (new TextStringValue('/Lime#20Green'))->getText(),
        );
        static::assertSame(
            '/paired()parentheses',
            (new TextStringValue('/paired#28#29parentheses'))->getText(),
        );
        static::assertSame(
            '/The_Key_of_F#_Minor',
            (new TextStringValue('/The_Key_of_F#23_Minor'))->getText(),
        );
        static::assertSame(
            '/AB',
            (new TextStringValue('/A#42'))->getText(),
        );
    }
}
