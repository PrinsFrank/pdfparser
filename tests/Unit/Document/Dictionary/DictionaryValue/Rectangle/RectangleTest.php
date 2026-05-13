<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Dictionary\DictionaryValue\Rectangle;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Rectangle\Rectangle;

#[CoversClass(Rectangle::class)]
class RectangleTest extends TestCase {
    /** @return iterable<string, array{0: Rectangle, 1: float, 2: float}> */
    public static function provideRectanglesWithExpectedDimensions(): iterable {
        yield 'normal coordinates' => [new Rectangle(42.22, 43.33, 44.44, 45.55), 2.22, 2.22];
        yield 'inverted coordinates' => [new Rectangle(44.44, 45.55, 42.22, 43.33), 2.22, 2.22];
        yield 'zero width and height' => [new Rectangle(10.0, 20.0, 10.0, 20.0), 0.0, 0.0];
    }

    #[DataProvider('provideRectanglesWithExpectedDimensions')]
    public function testGetWidthAndHeight(Rectangle $rectangle, float $expectedWidth, float $expectedHeight): void {
        static::assertEqualsWithDelta($expectedWidth, $rectangle->getWidth(), 0.00001);
        static::assertEqualsWithDelta($expectedHeight, $rectangle->getHeight(), 0.00001);
    }

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
