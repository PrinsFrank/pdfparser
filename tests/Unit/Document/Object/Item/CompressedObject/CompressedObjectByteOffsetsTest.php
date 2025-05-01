<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Document\Object\Item\CompressedObject;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\Object\Item\CompressedObject\CompressedObjectByteOffsets;

#[CoversClass(CompressedObjectByteOffsets::class)]
class CompressedObjectByteOffsetsTest extends TestCase {
    public function testGetRelativeByteOffsetForObject(): void {
        static::assertNull((new CompressedObjectByteOffsets([]))->getRelativeByteOffsetForObject(0));

        static::assertNull((new CompressedObjectByteOffsets([42 => 43]))->getRelativeByteOffsetForObject(0));
        static::assertSame(43, (new CompressedObjectByteOffsets([42 => 43]))->getRelativeByteOffsetForObject(42));
    }

    public function testGetNextRelativeByteOffset(): void {
        static::assertNull((new CompressedObjectByteOffsets([]))->getNextRelativeByteOffset(0));

        static::assertSame(42, (new CompressedObjectByteOffsets([1 => 42]))->getNextRelativeByteOffset(41));
        static::assertNull((new CompressedObjectByteOffsets([1 => 42]))->getNextRelativeByteOffset(42));
        static::assertNull((new CompressedObjectByteOffsets([1 => 42]))->getNextRelativeByteOffset(43));

        static::assertSame(42, (new CompressedObjectByteOffsets([1 => 42, 2 => 43]))->getNextRelativeByteOffset(41));
    }
}
