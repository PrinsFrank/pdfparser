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
    public function testFromValueWithFullDateTime(): void {
        static::assertEquals(
            DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2024-11-22 22:23:57', new DateTimeZone('+01:00')),
            DateValue::fromValue('(D:20241122222357+01\'00)')?->value,
        );
    }

    /** @throws ValueError */
    public function testFromValueWithFullDateTimeTrailingApostrophe(): void {
        static::assertEquals(
            DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2024-11-22 22:23:57', new DateTimeZone('+01:00')),
            DateValue::fromValue('(D:20241122222357+01\'00\')')?->value,
        );
    }

    /** @throws ValueError */
    public function testFromValueWithFullDateTimeAndHourOnlyOffset(): void {
        static::assertEquals(
            DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2024-11-22 22:23:57', new DateTimeZone('+01:00')),
            DateValue::fromValue('(D:20241122222357+01)')?->value,
        );
    }

    /** @throws ValueError */
    public function testFromValueWithFullDateTimeAndHourOnlyOffsetWithTrailingApostrophe(): void {
        static::assertEquals(
            DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2024-11-22 22:23:57', new DateTimeZone('+01:00')),
            DateValue::fromValue('(D:20241122222357+01\')')?->value,
        );
    }

    /** @throws ValueError */
    public function testFromValueWithFullDateTimeUTC(): void {
        static::assertEquals(
            DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2000-01-01 12:00:00', new DateTimeZone('UTC')),
            DateValue::fromValue('(D:20000101120000+00\'00)')?->value,
        );
    }

    /** @throws ValueError */
    public function testFromValueWithFullDateTimeWithZuluOffset(): void {
        static::assertEquals(
            DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2000-01-01 12:00:00', new DateTimeZone('+02:00')),
            DateValue::fromValue('(D:20000101120000Z02\'00)')?->value,
        );
    }

    /** @throws ValueError */
    public function testFromValueWithOnlyYear(): void {
        static::assertEquals(
            DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2000-01-01 00:00:00', new DateTimeZone('UTC')),
            DateValue::fromValue('(D:2000)')?->value,
        );
    }

    /** @throws ValueError */
    public function testFromValueWithOnlyYearAndMonth(): void {
        static::assertEquals(
            DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2000-02-01 00:00:00', new DateTimeZone('UTC')),
            DateValue::fromValue('(D:200002)')?->value,
        );
    }

    /** @throws ValueError */
    public function testFromValueWithOnlyYearMonthAndDay(): void {
        static::assertEquals(
            DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2000-02-02 00:00:00', new DateTimeZone('UTC')),
            DateValue::fromValue('(D:20000202)')?->value,
        );
    }

    /** @throws ValueError */
    public function testFromValueWithOnlyYearMonthDayAndHour(): void {
        static::assertEquals(
            DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2000-02-02 02:00:00', new DateTimeZone('UTC')),
            DateValue::fromValue('(D:2000020202)')?->value,
        );
    }

    /** @throws ValueError */
    public function testFromValueWithOnlyYearMonthDayHourAndMinute(): void {
        static::assertEquals(
            DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2000-02-02 02:02:00', new DateTimeZone('UTC')),
            DateValue::fromValue('(D:200002020202)')?->value,
        );
    }

    /** @throws ValueError */
    public function testFromValueWithOnlyDate(): void {
        static::assertEquals(
            DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2000-01-01 12:00:00', new DateTimeZone('UTC')),
            DateValue::fromValue('(D:20000101120000)')?->value,
        );
    }

    /** @throws ValueError */
    public function testFromValueWithOnlyDateWithTrailingZuluMarker(): void {
        static::assertEquals(
            DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2000-01-01 12:00:00', new DateTimeZone('UTC')),
            DateValue::fromValue('(D:20000101120000Z)')?->value,
        );
    }

    /** @throws ValueError */
    public function testFromValueInvalidLength(): void {
        static::assertNull(
            DateValue::fromValue('(D:2000010112000)'),
        );
    }

    /** @throws ValueError */
    public function testFromHexValue(): void {
        static::assertEquals(
            DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2020-09-30 15:43:07', new DateTimeZone('UTC')),
            DateValue::fromValue('<FEFF0044003A00320030003200300030003900330030003100350034003300300037005A>')?->value,
        );
    }

    /** @throws ValueError */
    public function testFromLiteralValueWithOctalCharacterEscapeSequences(): void {
        static::assertEquals(
            DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2025-05-05 15:02:15', new DateTimeZone('UTC')),
            DateValue::fromValue('(D\07220250505150215\05300\04700\047)')?->value,
        );
    }

    public function testFromValueThrowsExceptionWhenValueNotHexadecimal(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('String "A" is not hexadecimal');
        DateValue::fromValue('<A>');
    }

    public function testFromValueHandlesEmptyLiteral(): void {
        static::assertEquals(
            new DateValue(null),
            DateValue::fromValue('()'),
        );
    }

    public function testFromValueHandlesEmptyHexValue(): void {
        static::assertEquals(
            new DateValue(null),
            DateValue::fromValue('<>'),
        );
    }

    public function testFromValueHandlesInvalidHexValue(): void {
        static::assertNull(
            DateValue::fromValue('<ff>'),
        );
    }
}
