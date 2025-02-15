<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\CMap\ToUnicode;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\CMap\ToUnicode\BFChar;
use PrinsFrank\PdfParser\Exception\ParseFailureException;

#[CoversClass(BFChar::class)]
class BFCharTest extends TestCase {
    public function testContainsCharacterCode(): void {
        $bfChar = new BFChar(0, '0000');

        static::assertTrue($bfChar->containsCharacterCode(0));
        static::assertFalse($bfChar->containsCharacterCode(1));
    }

    public function testToUnicodeThrowsExceptionWhenNotContainsCharacterCode(): void {
        $bfChar = new BFChar(0, '0000');

        $this->expectException(ParseFailureException::class);
        $this->expectExceptionMessage('This BFChar does not contain character code 42');
        $bfChar->toUnicode(42);
    }

    public function testToUnicode(): void {
        static::assertSame(
            ' ',
            (new BFChar(3, '0020'))->toUnicode(3),
        );
    }
}
