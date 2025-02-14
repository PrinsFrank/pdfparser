<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\CMap\ToUnicode;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\CMap\ToUnicode\BFRange;

#[CoversClass(BFRange::class)]
class BFRangeTest extends TestCase {
    public function testContainsCharacterCode(): void {
        $bfRange = new BFRange(0, 42, []);

        static::assertFalse($bfRange->containsCharacterCode(-1));
        static::assertTrue($bfRange->containsCharacterCode(0));
        static::assertTrue($bfRange->containsCharacterCode(42));
        static::assertFalse($bfRange->containsCharacterCode(43));
    }

    public function testToUnicode(): void {
        $bfRange = new BFRange(1675, 1699, [945]);
        static::assertSame('α', $bfRange->toUnicode(1675));

        $bfRange = new BFRange(4, 6, [3627867917]);
        static::assertSame('🌍', $bfRange->toUnicode(4));
    }
}
