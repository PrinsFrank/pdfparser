<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Dictionary\DictionaryValue\Reference;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Reference\ReferenceValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Reference\ReferenceValueArray;

#[CoversClass(ReferenceValueArray::class)]
class ReferenceValueArrayTest extends TestCase {
    public function testFromValue(): void {
        static::assertNull(ReferenceValueArray::fromValue('42'));
        static::assertNull(ReferenceValueArray::fromValue('42 0'));
        static::assertNull(ReferenceValueArray::fromValue('42 0 R'));
        static::assertNull(
            ReferenceValueArray::fromValue('[<< /Foo 42 /Bar 43 >>]'),
            'Has 6 parts, but starts with << and ends with >> so should not be parsed as reference value array'
        );
        static::assertEquals(
            new ReferenceValueArray(),
            ReferenceValueArray::fromValue('[]')
        );
        static::assertEquals(
            new ReferenceValueArray(
                new ReferenceValue(42, 0)
            ),
            ReferenceValueArray::fromValue('[42 0 R]')
        );
        static::assertEquals(
            new ReferenceValueArray(
                new ReferenceValue(42, 0)
            ),
            ReferenceValueArray::fromValue('[ 42 0 R ]')
        );
        static::assertEquals(
            new ReferenceValueArray(
                new ReferenceValue(42, 0),
                new ReferenceValue(43, 0)
            ),
            ReferenceValueArray::fromValue('[42 0 R 43 0 R]')
        );
        static::assertEquals(
            new ReferenceValueArray(
                new ReferenceValue(42, 0),
                new ReferenceValue(43, 0)
            ),
            ReferenceValueArray::fromValue('[42 0 R    43 0 R]')
        );
        static::assertEquals(
            new ReferenceValueArray(
                new ReferenceValue(42, 0),
                new ReferenceValue(43, 0)
            ),
            ReferenceValueArray::fromValue(
                <<<EOD
                [
                    42 0 R
                    43 0 R
                ]
                EOD
            )
        );
    }
}
