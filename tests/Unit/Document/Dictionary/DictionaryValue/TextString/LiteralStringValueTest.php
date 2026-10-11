<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Dictionary\DictionaryValue\TextString;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString\LiteralStringValue;

#[CoversClass(LiteralStringValue::class)]
class LiteralStringValueTest extends TestCase {
    public function testFromValue(): void {
        static::assertEquals(new LiteralStringValue('foo'), LiteralStringValue::fromValue('(foo)'));
    }

    /** @see 7.3.4.2, table 3 */
    public function testGetTextWithEscapeSequenceInLiteralString(): void {
        static::assertSame(
            "\n",
            (new LiteralStringValue('\n'))->getText(),
        );
        static::assertSame(
            "\r",
            (new LiteralStringValue('\r'))->getText(),
        );
        static::assertSame(
            "\t",
            (new LiteralStringValue('\t'))->getText(),
        );
        static::assertSame(
            "\x08",
            (new LiteralStringValue('\b'))->getText(),
        );
        static::assertSame(
            "\f",
            (new LiteralStringValue('\f'))->getText(),
        );
        static::assertSame(
            "(",
            (new LiteralStringValue('\('))->getText(),
        );
        static::assertSame(
            ")",
            (new LiteralStringValue('\)'))->getText(),
        );
        static::assertSame(
            '\\',
            (new LiteralStringValue('\\\\'))->getText(),
        );
    }

    public function testExample1(): void {
        static::assertSame(
            'This is a string',
            (new LiteralStringValue('This is a string'))->getText(),
        );
        static::assertSame(
            <<<RAW
            Strings can contain newlines
            and such.
            RAW,
            (new LiteralStringValue(
                <<<RAW
                Strings can contain newlines
                and such.
                RAW,
            ))->getText(),
        );
        static::assertSame(
            <<<RAW
            Strings can contain balanced parentheses ()
            and special characters ( * ! & } ^ %and so on) .
            RAW,
            (new LiteralStringValue(
                <<<RAW
                Strings can contain balanced parentheses ()
                and special characters ( * ! & } ^ %and so on) .
                RAW,
            ))->getText(),
        );
        static::assertSame(
            'The following is an empty string .',
            (new LiteralStringValue('The following is an empty string .'))->getText(),
        );
        static::assertSame(
            '',
            (new LiteralStringValue(''))->getText(),
        );
        static::assertSame(
            'It has zero (0) length.',
            (new LiteralStringValue('It has zero (0) length.'))->getText(),
        );
    }

    /** @see 7.3.4.2 Example 3 */
    public function testExample3Newlines(): void {
        static::assertSame(
            'This string has an end-of-line at the end of it.' . "\n",
            (new LiteralStringValue(
                <<<RAW
                This string has an end-of-line at the end of it.

                RAW,
            ))->getText(),
        );
        static::assertSame(
            'So does this one.' . "\n",
            (new LiteralStringValue('So does this one.\n'))->getText(),
        );
    }

    /** @see 7.3.4.2 */
    public function testGetTextIgnoresEndOfLineLineContinuation(): void {
        static::assertSame(
            'under',
            (new LiteralStringValue("un\\\rder"))->getText(),
        );
        static::assertSame(
            'under',
            (new LiteralStringValue("un\\\nder"))->getText(),
        );
        static::assertSame(
            'under',
            (new LiteralStringValue("un\\\r\nder"))->getText(),
        );
        static::assertSame(
            "\\\n",
            (new LiteralStringValue("\\\\\n"))->getText(),
        );
        static::assertSame(
            '\\',
            (new LiteralStringValue("\\\\\\\n"))->getText(),
        );
    }

    /** @see 7.3.4.2 Example 4 */
    public function testExample4WithOctalCharacters(): void {
        static::assertSame(
            'This string contains ¥two octal charactersÇ.',
            (new LiteralStringValue('This string contains \245two octal characters\307.'))->getText(),
        );
    }

    /** @see 7.3.4 */
    public function testGetTextWithOctalCharacters(): void {
        static::assertSame(
            "\005",
            (new LiteralStringValue('\005'))->getText(),
        );
        static::assertSame(
            "\005" . '3',
            (new LiteralStringValue('\0053'))->getText(),
        );
        static::assertSame(
            "\005",
            (new LiteralStringValue('\05'))->getText(),
        );
        static::assertSame(
            "\005",
            (new LiteralStringValue('\5'))->getText(),
        );
        static::assertSame(
            '+',
            (new LiteralStringValue('\053'))->getText(),
        );
        static::assertSame(
            '+',
            (new LiteralStringValue('\53'))->getText(),
        );
    }

    /** @see 7.9.2.2 Text string type — UTF-16BE with a leading byte order mark */
    public function testGetTextConvertsUTF16BEToUTF8(): void {
        static::assertSame(
            'Tïtle',
            (new LiteralStringValue("\376\377\000T\000\357\000t\000l\000e"))->getText(),
        );
    }

    /** @see 7.9.2.2 Text string type — PDFDocEncoding (no byte order mark) is normalized to valid UTF-8 */
    public function testGetTextNormalizesPDFDocEncodingToUTF8(): void {
        // 0xFC ("ü", shared with Latin-1) written as a literal octal escape and as a hex string
        static::assertSame(
            'für',
            (new LiteralStringValue('f\374r'))->getText(),
        );
    }
}
