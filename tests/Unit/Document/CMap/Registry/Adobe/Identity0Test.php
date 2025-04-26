<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\CMap\Registry\Adobe;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\CMap\Registry\Adobe\Identity0;
use PrinsFrank\PdfParser\Document\CMap\ToUnicode\BFRange;
use PrinsFrank\PdfParser\Document\CMap\ToUnicode\CodeSpaceRange;
use PrinsFrank\PdfParser\Document\CMap\ToUnicode\ToUnicodeCMap;

#[CoversClass(Identity0::class)]
class Identity0Test extends TestCase {
    public function testGetToUnicodeCMap(): void {
        static::assertEquals(
            new ToUnicodeCMap(
                [new CodeSpaceRange(0x0000, 0xFFFF)],
                2,
                new BFRange(0x0000, 0xFFFF, ['0000'])
            ),
            (new Identity0())->getToUnicodeCMap(),
        );
    }
}
