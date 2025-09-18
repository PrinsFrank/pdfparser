<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Font;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\GlyphLists\AGlyphList;
use PrinsFrank\PdfParser\Document\Font\CharSet;
use PrinsFrank\PdfParser\Exception\InvalidArgumentException;

#[CoversClass(Charset::class)]
class CharsetTest extends TestCase {
    public function testFromString(): void {
        static::assertEquals(
            new CharSet([]),
            CharSet::fromString('')
        );
        static::assertEquals(
            new CharSet([AGlyphList::A]),
            CharSet::fromString('/A')
        );
        static::assertEquals(
            new CharSet([AGlyphList::A, AGlyphList::B, AGlyphList::C]),
            CharSet::fromString('/A/B/C'),
        );
    }

    public function testFromStringThrowsExceptionForInvalidCharacters(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No glyph with name "Foo" found');
        CharSet::fromString('/Foo');
    }

    public function testGetCharacterAtIndex(): void {
        $charSet = new CharSet([AGlyphList::A, AGlyphList::wavedash]);

        static::assertSame('A', $charSet->getCharacterAtIndex(0));
        static::assertSame('〜', $charSet->getCharacterAtIndex(1));
    }
}
