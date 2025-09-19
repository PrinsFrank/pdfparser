<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Dictionary\DictionaryValue\Array\Item;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\GlyphLists\AGlyphList;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Array\Item\DifferenceRange;
use PrinsFrank\PdfParser\Exception\InvalidArgumentException;

#[CoversClass(DifferenceRange::class)]
class DifferenceRangeTest extends TestCase {
    public function testContains(): void {
        $differenceRange = new DifferenceRange(1, [AGlyphList::adieresis, AGlyphList::a]);
        static::assertFalse($differenceRange->contains(0));
        static::assertTrue($differenceRange->contains(1));
        static::assertTrue($differenceRange->contains(2));
        static::assertFalse($differenceRange->contains(3));

        $differenceRange = new DifferenceRange(1, [AGlyphList::adieresis, AGlyphList::a, AglyphList::ae]);
        static::assertFalse($differenceRange->contains(0));
        static::assertTrue($differenceRange->contains(1));
        static::assertTrue($differenceRange->contains(2));
        static::assertTrue($differenceRange->contains(3));
        static::assertFalse($differenceRange->contains(4));
    }

    public function testGetGlyph(): void {
        $differenceRange = new DifferenceRange(1, [AGlyphList::adieresis, AGlyphList::a]);
        static::assertSame(AGlyphList::adieresis, $differenceRange->getGlyph(1));
        static::assertSame(AGlyphList::a, $differenceRange->getGlyph(2));
    }

    public function testGetGlyphThrowsExceptionWhenOutOfRange(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('his difference range does not contain index 3');
        (new DifferenceRange(1, [AGlyphList::adieresis, AGlyphList::a]))
            ->getGlyph(3);
    }
}
