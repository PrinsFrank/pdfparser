<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Dictionary\DictionaryValue\Array;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Array\ArrayValue;

#[CoversClass(ArrayValue::class)]
class ArrayValueTest extends TestCase {
    public function testFromValue(): void {
        static::assertNull(ArrayValue::fromValue(''));
        static::assertNull(ArrayValue::fromValue('foo'));
        static::assertEquals(
            new ArrayValue([]),
            ArrayValue::fromValue('[]'),
        );
        static::assertEquals(
            new ArrayValue([42, 43]),
            ArrayValue::fromValue('[42 43]'),
        );
        static::assertEquals(
            new ArrayValue([42, 43]),
            ArrayValue::fromValue('[42     43]'),
        );
        static::assertEquals(
            new ArrayValue(['foo', 'bar']),
            ArrayValue::fromValue('[foo bar]'),
        );
        static::assertEquals(
            new ArrayValue(['/foo', '/bar']),
            ArrayValue::fromValue('[/foo/bar]'),
        );
        static::assertEquals(
            new ArrayValue([3, 0, 'R', '/FitH', 'null']),
            ArrayValue::fromValue('[3 0 R /FitH null]'),
        );
        static::assertEquals(
            new ArrayValue([42, 43, 44]),
            ArrayValue::fromValue(
                <<<EOD
                [42
                43 44]
                EOD
            ),
        );
    }
}
