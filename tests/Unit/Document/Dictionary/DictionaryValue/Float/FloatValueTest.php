<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Dictionary\DictionaryValue\Float;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Float\FloatValue;

#[CoversClass(FloatValue::class)]
class FloatValueTest extends TestCase {
    public function testFromValue(): void {
        static::assertNull(FloatValue::fromValue(''));
        static::assertNull(FloatValue::fromValue('foo'));
        static::assertNull(FloatValue::fromValue(','));
        static::assertNull(FloatValue::fromValue('.'));
        static::assertNull(FloatValue::fromValue('-.'));
        static::assertNull(FloatValue::fromValue('1e2'));
        static::assertNull(FloatValue::fromValue('1,000.0'));
        static::assertNull(FloatValue::fromValue('NAN'));
        static::assertNull(FloatValue::fromValue('INF'));
        static::assertNull(FloatValue::fromValue('1.2.3'));

        static::assertEquals(new FloatValue(42), FloatValue::fromValue('42'));
        static::assertEquals(new FloatValue(42), FloatValue::fromValue('   42   '));
        static::assertEquals(new FloatValue(42.0), FloatValue::fromValue('42.0'));
        static::assertEquals(new FloatValue(42.0), FloatValue::fromValue('42.'));
        static::assertEquals(new FloatValue(0.42), FloatValue::fromValue('0.42'));
        static::assertEquals(new FloatValue(0.42), FloatValue::fromValue('.42'));
        static::assertEquals(new FloatValue(0.42), FloatValue::fromValue('000.42'));
        static::assertEquals(new FloatValue(0.42), FloatValue::fromValue('+0.42'));
        static::assertEquals(new FloatValue(-0.42), FloatValue::fromValue('-0.42'));
        static::assertEquals(new FloatValue(0.0), FloatValue::fromValue('0.0'));
        static::assertEquals(new FloatValue(0.0), FloatValue::fromValue('+0.0'));
        static::assertEquals(new FloatValue(-0.0), FloatValue::fromValue('-0.0'));
    }
}
