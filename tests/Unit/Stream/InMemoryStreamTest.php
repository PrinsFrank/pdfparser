<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Stream;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Document\Generic\Marker;
use PrinsFrank\PdfParser\Exception\InvalidArgumentException;
use PrinsFrank\PdfParser\Stream\InMemoryStream;

#[CoversClass(InMemoryStream::class)]
class InMemoryStreamTest extends TestCase {
    public function testGetSizeInBytes(): void {
        static::assertSame(3, (new InMemoryStream('foo'))->getSizeInBytes());
    }

    public function testReadThrowsExceptionWithNegativeBytes(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$nrOfBytes must be greater than 0, -1 given');
        (new InMemoryStream('foo'))
            ->read(0, -1);
    }

    public function testRead(): void {
        static::assertSame('foo', (new InMemoryStream('foo'))->read(0, 3));
        static::assertSame('o', (new InMemoryStream('foo'))->read(2, 1));
    }

    public function testSliceThrowsExceptionOnInvalidStartByteOffset(): void {
        $stream = new InMemoryStream('foobar');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$startByteOffset must be greater than 0, -1 given');
        $stream->slice(-1, 2);
    }

    public function testSliceThrowsExceptionOnInvalidEndByteOffset(): void {
        $stream = new InMemoryStream('foobar');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('End byte offset 2 should be bigger than start byte offset 2');
        $stream->slice(2, 2);
    }

    public function testSlice(): void {
        $stream = new InMemoryStream('foobar');
        static::assertSame('ob', $stream->slice(2, 4));
    }

    public function testCharsThrowsExceptionOnInvalidFromValue(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$from must be greater than zero, -1 given');
        iterator_to_array((new InMemoryStream('foobar'))->chars(-1, 2));
    }

    public function testCharsThrowsExceptionOnInvalidNrOfBytesValue(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$nrOfBytes to read must be greater than zero, -2 given');
        iterator_to_array((new InMemoryStream('foobar'))->chars(0, -2));
    }

    public function testChars(): void {
        $stream = new InMemoryStream('foobar');
        static::assertSame(['o', 'b'], iterator_to_array($stream->chars(2, 2)));
    }

    public function testFirstPos(): void {
        $stream = new InMemoryStream('123objxref');
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
        $stream = new InMemoryStream('123objxref');
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
}
