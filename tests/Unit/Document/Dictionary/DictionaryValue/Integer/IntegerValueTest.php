<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Dictionary\DictionaryValue\Integer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Integer\IntegerValue;

#[CoversClass(IntegerValue::class)]
class IntegerValueTest extends TestCase {
    public function testFromValue(): void {
        static::assertEquals(
            new IntegerValue(42),
            IntegerValue::fromValue('42')
        );

        static::assertEquals(
            new IntegerValue(PHP_INT_MAX),
            IntegerValue::fromValue((string) PHP_INT_MAX)
        );

        static::assertNull(IntegerValue::fromValue('42.0'));
        static::assertNull(IntegerValue::fromValue('42,0'));
    }
}
