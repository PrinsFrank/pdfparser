<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Tests\Unit\Stream\Meta;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PrinsFrank\PdfParser\Exception\InvalidArgumentException;
use PrinsFrank\PdfParser\Exception\OutOfBoundsException;
use PrinsFrank\PdfParser\Stream\InMemoryStream;
use PrinsFrank\PdfParser\Stream\Meta\BoundedStream;
use PrinsFrank\PdfParser\Stream\PrimaryStream;

#[CoversClass(BoundedStream::class)]
class BoundedStreamTest extends TestCase {
    public function testConstructThrowsExceptionOnInvalidBound(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('OffsetEnd should be bigger than offsetStart');
        new BoundedStream($this->createMock(PrimaryStream::class), 10, 9);
    }

    public function testConstructThrowsExceptionWhenStartBiggerThanEndOfPrimaryStream(): void {
        $primaryStream = $this->createMock(PrimaryStream::class);
        $primaryStream->expects(self::once())
            ->method('getSizeInBytes')
            ->willReturn(42);

        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionMessage('Start of bounded stream should be within parent stream length');
        new BoundedStream($primaryStream, 43, 44);
    }

    public function testConstructThrowsExceptionWhenEndBiggerThanEndOfPrimaryStream(): void {
        $primaryStream = $this->createMock(PrimaryStream::class);
        $primaryStream->expects(self::atLeastOnce())
            ->method('getSizeInBytes')
            ->willReturn(43);

        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionMessage('End of bounded stream should be within parent stream length');
        new BoundedStream($primaryStream, 43, 44);
    }

    public function testGetSizeInBytes(): void {
        $primaryStream = $this->createMock(PrimaryStream::class);
        $primaryStream->expects(self::atLeastOnce())
            ->method('getSizeInBytes')
            ->willReturn(44);

        static::assertSame(0, (new BoundedStream($primaryStream, 42, 42))->getSizeInBytes());
        static::assertSame(1, (new BoundedStream($primaryStream, 42, 43))->getSizeInBytes());
        static::assertSame(2, (new BoundedStream($primaryStream, 42, 44))->getSizeInBytes());
    }

    public function testReadThrowsOutOfBoundsException(): void {
        $primaryStream = $this->createMock(PrimaryStream::class);
        $primaryStream->expects(self::atLeastOnce())
            ->method('getSizeInBytes')
            ->willReturn(30);

        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionMessage('Stream is only 10 bytes long, trying to read 15 bytes from offset 5');
        (new BoundedStream($primaryStream, 10, 20))
            ->read(5, 15);
    }

    public function testRead(): void {
        static::assertSame(
            '012',
            (new BoundedStream(
                new InMemoryStream('0123456789'),
                0,
                10,
            ))->read(0, 3),
        );
        static::assertSame(
            '123',
            (new BoundedStream(
                new InMemoryStream('0123456789'),
                1,
                10,
            ))->read(0, 3),
        );
        static::assertSame(
            '234',
            (new BoundedStream(
                new InMemoryStream('0123456789'),
                1,
                10,
            ))->read(1, 3),
        );
        static::assertSame(
            '678',
            (new BoundedStream(
                new InMemoryStream('0123456789'),
                3,
                10,
            ))->read(3, 3),
        );
    }
}
