<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Stream;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\Generic\Marker;
use PrinsFrank\PdfParser\Exception\InvalidArgumentException;
use PrinsFrank\PdfParser\Stream\FileStream;

#[CoversClass(FileStream::class)]
class FileStreamTest extends TestCase {
    public function testFirstPos(): void {
        $stream = FileStream::fromString('123objxref');
        static::assertSame(
            3,
            $stream->firstPos(Marker::OBJ, 0, 10)
        );
        static::assertSame(
            6,
            $stream->firstPos(Marker::XREF, 0, 10)
        );
        static::assertNull(
            $stream->firstPos(Marker::TRAILER, 0, 10)
        );
    }

    public function testLastPos(): void {
        $stream = FileStream::fromString('123objxref');
        static::assertSame(
            3,
            $stream->lastPos(Marker::OBJ, 0)
        );
        static::assertSame(
            6,
            $stream->lastPos(Marker::XREF, 0)
        );
        static::assertNull(
            $stream->lastPos(Marker::TRAILER, 0)
        );
    }

    public function testSlice(): void {
        $stream = FileStream::fromString('foobar');
        static::assertSame('ob', $stream->slice(2, 4));
    }

    public function testSliceThrowsExceptionOnInvalidStartByteOffset(): void {
        $stream = FileStream::fromString('foobar');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$startByteOffset must be greater than 0, -1 given');
        $stream->slice(-1, 2);
    }

    public function testSliceThrowsExceptionOnInvalidEndByteOffset(): void {
        $stream = FileStream::fromString('foobar');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('End byte offset 2 should be bigger than start byte offset 2');
        $stream->slice(2, 2);
    }

    public function testChars(): void {
        $stream = FileStream::fromString('foobar');
        static::assertSame(['f', 'o', 'o', 'b', 'a', 'r'], iterator_to_array($stream->chars(0, 6)));
        static::assertSame(['o', 'b'], iterator_to_array($stream->chars(2, 2)));
    }

    public function testReadBytesThrowsExceptionWithNegativeBytes(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$nrOfBytes must be greater than 0, -1 given');
        FileStream::fromString('foo')->read(0, -1);
    }

    public function testFileStructure(): void {
        $stream = FileStream::fromString(
            <<<EOD
            trailer
            startxref
            1234
            %%EOF
            EOD
        );
        $eofMarkerPos = $stream->lastPos(Marker::EOF, 0);
        static::assertSame(23, $eofMarkerPos);
        static::assertSame(Marker::EOF->value, $stream->read($eofMarkerPos, strlen(Marker::EOF->value)));

        $startXrefPos = $stream->lastPos(Marker::START_XREF, $stream->getSizeInBytes() - $eofMarkerPos);
        static::assertSame(8, $startXrefPos);
        static::assertSame(Marker::START_XREF->value, $stream->read($startXrefPos, strlen(Marker::START_XREF->value)));

        $byteOffsetPos = $stream->getStartOfNextLine($startXrefPos, $stream->getSizeInBytes());
        static::assertSame(18, $byteOffsetPos);
        $byteOffsetEndPos = $stream->getEndOfCurrentLine($byteOffsetPos, $stream->getSizeInBytes());
        static::assertSame(22, $byteOffsetEndPos);
        static::assertSame('1234', $stream->read($byteOffsetPos, $byteOffsetEndPos - $byteOffsetPos));
    }
}
