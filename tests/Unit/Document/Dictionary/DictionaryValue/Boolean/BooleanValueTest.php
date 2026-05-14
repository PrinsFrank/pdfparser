<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Dictionary\DictionaryValue\Boolean;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Boolean\BooleanValue;

#[CoversClass(BooleanValue::class)]
class BooleanValueTest extends TestCase {
    public function testFromValue(): void {
        static::assertNull(BooleanValue::fromValue(''));
        static::assertNull(BooleanValue::fromValue('0'));
        static::assertNull(BooleanValue::fromValue('1'));
        static::assertNull(BooleanValue::fromValue('False'));
        static::assertEquals(
            new BooleanValue(true),
            BooleanValue::fromValue('true'),
        );
        static::assertEquals(
            new BooleanValue(true),
            BooleanValue::fromValue('/true'),
        );
        static::assertEquals(
            new BooleanValue(false),
            BooleanValue::fromValue('false'),
        );
        static::assertEquals(
            new BooleanValue(false),
            BooleanValue::fromValue('/false'),
        );
    }
}
