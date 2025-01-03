<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Generic\Parsing;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\Generic\Marker;
use PrinsFrank\PdfParser\Document\Generic\Parsing\RollingCharBuffer;

#[CoversClass(RollingCharBuffer::class)]
class RollingCharBufferTest extends TestCase {
    public function testGetPreviousCharacter(): void {
        $charBuffer = new RollingCharBuffer(3);
        $charBuffer->next('a');
        static::assertNull($charBuffer->getPreviousCharacter());
        static::assertNull($charBuffer->getPreviousCharacter(1));
        static::assertNull($charBuffer->getPreviousCharacter(2));

        $charBuffer->next('b');
        static::assertSame('a', $charBuffer->getPreviousCharacter());
        static::assertSame('a', $charBuffer->getPreviousCharacter(1));
        static::assertNull($charBuffer->getPreviousCharacter(2));

        $charBuffer->next('c');
        static::assertSame('b', $charBuffer->getPreviousCharacter());
        static::assertSame('b', $charBuffer->getPreviousCharacter(1));
        static::assertSame('a', $charBuffer->getPreviousCharacter(2));
    }

    public function testSeenMarker(): void {
        $charBuffer = new RollingCharBuffer(6);
        static::assertFalse($charBuffer->seenBackedEnumValue(Marker::STREAM));
        $charBuffer->next('s');
        static::assertFalse($charBuffer->seenBackedEnumValue(Marker::STREAM));
        $charBuffer->next('t');
        static::assertFalse($charBuffer->seenBackedEnumValue(Marker::STREAM));
        $charBuffer->next('r');
        static::assertFalse($charBuffer->seenBackedEnumValue(Marker::STREAM));
        $charBuffer->next('e');
        static::assertFalse($charBuffer->seenBackedEnumValue(Marker::STREAM));
        $charBuffer->next('a');
        static::assertFalse($charBuffer->seenBackedEnumValue(Marker::STREAM));
        $charBuffer->next('m');
        static::assertTrue($charBuffer->seenBackedEnumValue(Marker::STREAM));
    }
}
