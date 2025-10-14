<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Dictionary\DictionaryValue\Date;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Date\DateValue;
use PrinsFrank\PdfParser\Exception\InvalidArgumentException;
use ValueError;

#[CoversClass(DateValue::class)]
class DateValueTest extends TestCase {
    /** @throws ValueError */
    public function testFromValue(): void {
        static::assertEquals(
            DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2024-11-22 22:23:57', new DateTimeZone('+01:00')),
            DateValue::fromValue('(D:20241122222357+01\'00\')')?->value
        );

        static::assertNull(
            DateValue::fromValue('<ff>')
        );

        $dateTime = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2000-01-01 12:00:00');
        static::assertNotFalse($dateTime);
        static::assertEquals(
            new DateValue($dateTime),
            DateValue::fromValue('(D:20000101120000+00\'00\')')
        );

        $dateTime = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2000-01-01 12:00:00', new DateTimeZone('+02:00'));
        static::assertNotFalse($dateTime);
        static::assertEquals(
            new DateValue($dateTime),
            DateValue::fromValue('(D:20000101120000+02\'00\')')
        );

        $dateTime = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2000-01-01 12:00:00', new DateTimeZone('UTC'));
        static::assertNotFalse($dateTime);
        static::assertEquals(
            new DateValue($dateTime),
            DateValue::fromValue('(D:20000101120000)')
        );

        static::assertNull(
            DateValue::fromValue('(D:2000010112000)')
        );

        $dateTime = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2020-09-30 15:43:07');
        static::assertNotFalse($dateTime);
        static::assertEquals(
            new DateValue($dateTime),
            DateValue::fromValue('<FEFF0044003A00320030003200300030003900330030003100350034003300300037005A>')
        );
    }

    /** @throws ValueError */
    public function testFromValueWithOctalCharacterEscapeSequences(): void {
        static::assertEquals(
            DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2025-05-05 15:02:15', new DateTimeZone('UTC')),
            DateValue::fromValue('(D\07220250505150215\05300\04700\047)')?->value
        );
    }

    public function testFromValueThrowsExceptionWhenValueNotHexadecimal(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('String "" is not hexadecimal');
        DateValue::fromValue('<>');
    }
}
