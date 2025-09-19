<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Dictionary\DictionaryValue\Array;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\GlyphLists\AGlyphList;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Array\DifferencesArrayValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Array\Item\DifferenceRange;

#[CoversClass(DifferencesArrayValue::class)]
class DifferencesArrayValueTest extends TestCase {
    public function testFromValue(): void {
        static::assertEquals(
            new DifferencesArrayValue([]),
            DifferencesArrayValue::fromValue('[]')
        );
        static::assertEquals(
            new DifferencesArrayValue(
                [
                    new DifferenceRange(0, [AGlyphList::quotesingle, AGlyphList::grave]),
                ],
            ),
            DifferencesArrayValue::fromValue('[0 /quotesingle /grave]')
        );
        static::assertEquals(
            new DifferencesArrayValue(
                [
                    new DifferenceRange(0, [AGlyphList::quotesingle]),
                    new DifferenceRange(36, [AGlyphList::grave]),
                ],
            ),
            DifferencesArrayValue::fromValue('[0 /quotesingle 36 /grave]')
        );
    }

    public function testGetGlyph(): void {
        static::assertSame(
            AglyphList::grave,
            (new DifferencesArrayValue([new DifferenceRange(36, [AGlyphList::grave])]))
                ->getGlyph(36),
        );
    }
}
