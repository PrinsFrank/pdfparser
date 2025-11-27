<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Dictionary\DictionaryValue\Rectangle;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Rectangle\Rectangle;

#[CoversClass(Rectangle::class)]
class RectangleTest extends TestCase {
    public function testFromValue(): void {
        static::assertNull(Rectangle::fromValue(''));
        static::assertNull(Rectangle::fromValue('[]'));
        static::assertNull(Rectangle::fromValue('[1]'));
        static::assertNull(Rectangle::fromValue('[1 2]'));
        static::assertNull(Rectangle::fromValue('[1 2 3]'));
        static::assertEquals(
            new Rectangle(42.0, 43.0, 44.0, 45.0),
            Rectangle::fromValue('[42 43 44 45]'),
        );
        static::assertEquals(
            new Rectangle(42.0, 43.0, 44.0, 45.0),
            Rectangle::fromValue('[ 42 43 44 45 ]'),
        );
        static::assertEquals(
            new Rectangle(42.22, 43.33, 44.44, 45.55),
            Rectangle::fromValue('[42.22 43.33 44.44 45.55]'),
        );
        static::assertEquals(
            new Rectangle(42.22, 43.33, 44.44, 45.55),
            Rectangle::fromValue('[ 42.22 43.33 44.44 45.55 ]'),
        );
        static::assertEquals(
            new Rectangle(0, 0, 595.2756, 841.8898),
            Rectangle::fromValue('[0 0 595.2756' . "\r" . '841.8898]'),
        );
        static::assertEquals(
            new Rectangle(0, 0, 595.2756, 841.8898),
            Rectangle::fromValue('[0 0 595.2756' . "\n" . '841.8898]'),
        );
    }
}
