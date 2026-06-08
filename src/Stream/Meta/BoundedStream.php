<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Stream\Meta;

use Override;
use PrinsFrank\PdfParser\Document\CMap\ToUnicode\ToUnicodeCMapOperator;
use PrinsFrank\PdfParser\Document\Generic\Character\DelimiterCharacter;
use PrinsFrank\PdfParser\Document\Generic\Character\WhitespaceCharacter;
use PrinsFrank\PdfParser\Document\Generic\Marker;
use PrinsFrank\PdfParser\Exception\InvalidArgumentException;
use PrinsFrank\PdfParser\Exception\NotImplementedException;
use PrinsFrank\PdfParser\Exception\OutOfBoundsException;
use PrinsFrank\PdfParser\Stream\AbstractStream;
use PrinsFrank\PdfParser\Stream\PrimaryStream;

class BoundedStream extends AbstractStream implements DerivedStream {
    /** @throws InvalidArgumentException|OutOfBoundsException */
    public function __construct(
        private readonly PrimaryStream $primaryStream,
        private readonly int $offsetStart,
        private readonly int $offsetEnd,
    ) {
        if ($this->offsetEnd < $this->offsetStart) {
            throw new InvalidArgumentException('OffsetEnd should be bigger than offsetStart');
        }

        if ($this->offsetStart > $this->primaryStream->getSizeInBytes()) {
            throw new OutOfBoundsException('Start of bounded stream should be within parent stream length');
        }

        if ($this->offsetEnd > $this->primaryStream->getSizeInBytes()) {
            throw new OutOfBoundsException('End of bounded stream should be within parent stream length');
        }
    }

    #[Override]
    public function getSizeInBytes(): int {
        return $this->offsetEnd - $this->offsetStart;
    }

    /** @throws OutOfBoundsException */
    #[Override]
    public function read(int $from, int $nrOfBytes): string {
        if ($from + $nrOfBytes > $this->getSizeInBytes()) {
            throw new OutOfBoundsException(sprintf('Stream is only %d bytes long, trying to read %d bytes from offset %d', $this->getSizeInBytes(), $nrOfBytes, $from));
        }

        return $this->primaryStream
            ->read($this->offsetStart + $from, $nrOfBytes);
    }

    #[Override]
    public function slice(int $startByteOffset, int $endByteOffset): string {
        if ($startByteOffset > $this->getSizeInBytes() || $endByteOffset > $this->getSizeInBytes()) {
            throw new OutOfBoundsException();
        }

        return $this->primaryStream
            ->read($this->offsetStart + $startByteOffset, $endByteOffset - $startByteOffset);
    }

    #[Override]
    public function chars(int $from, int $nrOfBytes): iterable {
        if ($from + $nrOfBytes > $this->getSizeInBytes()) {
            throw new OutOfBoundsException();
        }

        return $this->primaryStream
            ->chars($this->offsetStart + $from, $nrOfBytes);
    }

    #[Override]
    public function firstPos(WhitespaceCharacter|DelimiterCharacter|ToUnicodeCMapOperator|Marker $needle, int $offsetFromStart, int $before): ?int {
        if ($offsetFromStart > $this->getSizeInBytes() || $before > $this->getSizeInBytes()) {
            throw new OutOfBoundsException();
        }

        $firstPos = $this->primaryStream
            ->firstPos($needle, $this->offsetStart + $offsetFromStart, $this->offsetStart + $before);
        if ($firstPos === null) {
            return null;
        }

        return $firstPos - $this->offsetStart;
    }

    #[Override]
    public function lastPos(WhitespaceCharacter|DelimiterCharacter|ToUnicodeCMapOperator|Marker $needle, int $offsetFromEnd): ?int {
        throw new NotImplementedException();
    }
}
