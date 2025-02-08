<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Dictionary\DicitonaryValue\Date;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Date\DateValue;

#[CoversClass(DateValue::class)]
class DateValueTest extends TestCase {
    public function testFromValue(): void {
        static::assertEquals(
            DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2024-11-22 22:23:57', new DateTimeZone('+01:00')),
            DateValue::fromValue('(D:20241122222357+01\'00\')')?->value
        );
    }
}
