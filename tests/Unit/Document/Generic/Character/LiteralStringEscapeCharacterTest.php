<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Generic\Character;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\Generic\Character\LiteralStringEscapeCharacter;

#[CoversClass(LiteralStringEscapeCharacter::class)]
class LiteralStringEscapeCharacterTest extends TestCase {
    public function testUnescapeCharactersMultilineExample2(): void {
        static::assertSame(
            'These two strings are the same.',
            LiteralStringEscapeCharacter::unescapeCharacters(
                <<<EOD
                These \
                two strings \
                are the same.
                EOD
            )
        );
    }

    public function testUnescapeNewlinesExample3(): void {
        static::assertSame(
            <<<EOD
            This string has an end-of-line at the end of it.

            EOD,
            LiteralStringEscapeCharacter::unescapeCharacters(
                <<<EOD
                This string has an end-of-line at the end of it.

                EOD,
            ),
        );
        static::assertSame(
            <<<EOD
            So does this one.

            EOD,
            LiteralStringEscapeCharacter::unescapeCharacters(
                <<<EOD
                So does this one.\n
                EOD,
            ),
        );
    }

    public function testUnescapeOctalCharactersExample4(): void {
        static::assertSame(
            'This string contains ¥two octal charactersÇ.',
            LiteralStringEscapeCharacter::unescapeCharacters('This string contains \245two octal characters\307.')
        );
    }

    public function testUnescapeOctalCharactersExample5(): void {
        static::assertSame('+', LiteralStringEscapeCharacter::unescapeCharacters('\053'));
        static::assertSame('+', LiteralStringEscapeCharacter::unescapeCharacters('\53'));
        static::assertSame("\005" . '3', LiteralStringEscapeCharacter::unescapeCharacters('\0053'));
    }

    public function testUnescapeCharacters(): void {
        static::assertSame("\n", LiteralStringEscapeCharacter::unescapeCharacters('\n'));
        static::assertSame("\r", LiteralStringEscapeCharacter::unescapeCharacters('\r'));
        static::assertSame("\t", LiteralStringEscapeCharacter::unescapeCharacters('\t'));
        static::assertSame("\x08", LiteralStringEscapeCharacter::unescapeCharacters('\b'));
        static::assertSame("\x0C", LiteralStringEscapeCharacter::unescapeCharacters('\f'));
        static::assertSame("(", LiteralStringEscapeCharacter::unescapeCharacters('\('));
        static::assertSame(")", LiteralStringEscapeCharacter::unescapeCharacters('\)'));
        static::assertSame("\\", LiteralStringEscapeCharacter::unescapeCharacters('\\'));
        static::assertSame("\000", LiteralStringEscapeCharacter::unescapeCharacters('\0'));
        static::assertSame("\005" . '35', LiteralStringEscapeCharacter::unescapeCharacters('\00535'));
        static::assertSame("\005" . '353', LiteralStringEscapeCharacter::unescapeCharacters('\005353'));
    }
}
