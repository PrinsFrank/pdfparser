<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Dictionary\DictionaryValue\Float;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Float\FloatValue;

#[CoversClass(FloatValue::class)]
class FloatValueTest extends TestCase {
    public function testFromValue(): void {
        static::assertNull(FloatValue::fromValue('foo'));
        static::assertEquals(
            new FloatValue(42),
            FloatValue::fromValue('42'),
        );
        static::assertEquals(
            new FloatValue(42),
            FloatValue::fromValue('   42   '),
        );
        static::assertEquals(
            new FloatValue(42.0),
            FloatValue::fromValue('42.0'),
        );
    }
}
