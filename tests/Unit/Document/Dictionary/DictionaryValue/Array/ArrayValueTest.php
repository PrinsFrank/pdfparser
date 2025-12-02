<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Dictionary\DictionaryValue\Array;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Array\ArrayValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Reference\ReferenceValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Reference\ReferenceValueArray;

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
            ArrayValue::fromValue(' [42 43] '),
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

    public function testToString(): void {
        static::assertSame(
            '[]',
            (new ArrayValue([]))->toString()
        );
        static::assertSame(
            '[42 42 42]',
            (new ArrayValue([42, 42, 42]))->toString()
        );
        static::assertSame(
            '[/Foo /Bar]',
            (new ArrayValue(['/Foo', '/Bar']))->toString()
        );
        static::assertSame(
            '[[/Foo /Bar]]',
            (new ArrayValue([new ArrayValue(['/Foo', '/Bar'])]))->toString()
        );
        static::assertSame(
            '[42 R 43 R]',
            (new ArrayValue([new ReferenceValueArray(new ReferenceValue(42, 0), new ReferenceValue(43, 0))]))->toString()
        );
    }
}
