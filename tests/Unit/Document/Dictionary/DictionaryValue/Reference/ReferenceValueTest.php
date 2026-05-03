<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Dictionary\DictionaryValue\Reference;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Reference\ReferenceValue;

#[CoversClass(ReferenceValue::class)]
class ReferenceValueTest extends TestCase {
    public function testFromValue(): void {
        static::assertNull(ReferenceValue::fromValue('42'));
        static::assertNull(ReferenceValue::fromValue('42 0'));
        static::assertNull(ReferenceValue::fromValue('42 0 0'));
        static::assertNull(ReferenceValue::fromValue('42 0 S'));
        static::assertNull(ReferenceValue::fromValue('A 0 R'));
        static::assertNull(ReferenceValue::fromValue('42.42 0 R'));
        static::assertNull(ReferenceValue::fromValue('42 0.0 R'));
        static::assertNull(ReferenceValue::fromValue('42 A R'));
        static::assertEquals(
            new ReferenceValue(42, 0),
            ReferenceValue::fromValue('42 0 R'),
        );
    }
}
