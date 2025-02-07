<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Stream;

use Override;
use PrinsFrank\PdfParser\Document\CMap\ToUnicode\ToUnicodeCMapOperator;
use PrinsFrank\PdfParser\Document\Generic\Character\DelimiterCharacter;
use PrinsFrank\PdfParser\Document\Generic\Character\WhitespaceCharacter;
use PrinsFrank\PdfParser\Document\Generic\Marker;
use PrinsFrank\PdfParser\Document\Generic\Parsing\RollingCharBuffer;
use PrinsFrank\PdfParser\Exception\InvalidArgumentException;
use PrinsFrank\PdfParser\Exception\RuntimeException;

class FileStream extends AbstractStream {
    /** @var resource */
    private readonly mixed $handle;

    /** @param resource $handle */
    final private function __construct(mixed $handle) {
        if (is_resource($handle) === false || in_array(get_resource_type($handle), ['stream'], true) === false) {
            throw new InvalidArgumentException(sprintf('$handle should be a stream, %s given', is_resource($handle) ? get_resource_type($handle) : gettype($handle)));
        }

        $this->handle = $handle;
    }

    public static function openFile(string $path): self {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new InvalidArgumentException(sprintf('Failed to open file at path "%s"', $path));
        }

        return new self($handle);
    }

    public static function fromString(string $content): self {
        $handle = fopen('php://temp', 'rb+');
        if ($handle === false) {
            throw new RuntimeException('Unable to create file handle to temp');
        }

        fwrite($handle, $content);
        rewind($handle);

        return new self($handle);
    }

    #[Override]
    public function getSizeInBytes(): int {
        $stats = fstat($this->handle);
        if ($stats === false) {
            throw new RuntimeException('Unable to retrieve file information');
        }

        return $stats['size'];
    }

    #[Override]
    public function read(int $from, int $nrOfBytes): string {
        if ($nrOfBytes <= 0) {
            throw new InvalidArgumentException(sprintf('$nrOfBytes must be greater than 0, %d given', $nrOfBytes));
        }

        fseek($this->handle, $from);

        $bytes = fread($this->handle, $nrOfBytes);
        if ($bytes === false) {
            throw new RuntimeException('Unable to read from handle');
        }

        return $bytes;
    }

    #[Override]
    public function slice(int $startByteOffset, int $endByteOffset): string {
        if ($startByteOffset <= 0) {
            throw new InvalidArgumentException(sprintf('$nrOfBytes must be greater than 0, %d given', $startByteOffset));
        }

        if ($endByteOffset - $startByteOffset < 1) {
            throw new InvalidArgumentException(sprintf('End byte offset %d should be bigger than start byte offset %d', $endByteOffset, $startByteOffset));
        }

        fseek($this->handle, $startByteOffset);

        $bytes = fread($this->handle, $endByteOffset - $startByteOffset);
        if ($bytes === false) {
            throw new RuntimeException('Unable to read bytes from handle');
        }

        return $bytes;
    }

    #[Override]
    public function chars(int $from, int $nrOfBytes): iterable {
        if ($from < 0) {
            throw new InvalidArgumentException(sprintf('StartOffset should be greater than zero, %d given', $from));
        }

        if ($nrOfBytes <= 0) {
            throw new InvalidArgumentException(sprintf('$nrOfBytes to read must be greater than 0, %d given', $nrOfBytes));
        }

        fseek($this->handle, $from);
        $bytesRead = 0;
        while ($bytesRead < $nrOfBytes) {
            $bytes = fread($this->handle, 1);
            if ($bytes === false) {
                throw new RuntimeException('Unable to read bytes from stream');
            }
            yield $bytes;
            $bytesRead++;
        }
    }

    #[Override]
    public function firstPos(WhitespaceCharacter|Marker|DelimiterCharacter|ToUnicodeCMapOperator $needle, int $offsetFromStart, int $before): ?int {
        $rollingCharBuffer = new RollingCharBuffer($needleLength = strlen($needle->value));
        while ($offsetFromStart < $before) {
            fseek($this->handle, $offsetFromStart);
            $character = fgetc($this->handle);
            if ($character === false) {
                throw new RuntimeException('Unable to get char from stream');
            }
            $rollingCharBuffer->next($character);
            $offsetFromStart++;
            if ($rollingCharBuffer->seenString($needle->value)) {
                return $offsetFromStart - $needleLength;
            }
        }

        return null;
    }

    #[Override]
    public function lastPos(WhitespaceCharacter|Marker|DelimiterCharacter|ToUnicodeCMapOperator $needle, int $offsetFromEnd): ?int {
        $rollingCharBuffer = new RollingCharBuffer(strlen($needle->value));
        $offsetFromEnd++;
        while (fseek($this->handle, - $offsetFromEnd, SEEK_END) !== -1) {
            $character = fgetc($this->handle);
            if ($character === false) {
                throw new RuntimeException('Unable to get character from stream');
            }
            $rollingCharBuffer->next($character);
            $offsetFromEnd++;
            if ($rollingCharBuffer->seenReverseString($needle->value)) {
                return $this->getSizeInBytes() - $offsetFromEnd + 1;
            }
        }

        return null;
    }

    public function __destruct() {
        fclose($this->handle);
    }
}
