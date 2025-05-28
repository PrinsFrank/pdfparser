<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Stream;

use Override;
use PrinsFrank\PdfParser\Document\CMap\ToUnicode\ToUnicodeCMapOperator;
use PrinsFrank\PdfParser\Document\Generic\Character\DelimiterCharacter;
use PrinsFrank\PdfParser\Document\Generic\Character\WhitespaceCharacter;
use PrinsFrank\PdfParser\Document\Generic\Marker;
use PrinsFrank\PdfParser\Exception\InvalidArgumentException;

class InMemoryStream extends AbstractStream {
    public function __construct(
        private readonly string $content
    ) {
    }

    #[Override]
    public function getSizeInBytes(): int {
        return strlen($this->content);
    }

    #[Override]
    public function read(int $from, int $nrOfBytes): string {
        if ($nrOfBytes <= 0) {
            throw new InvalidArgumentException(sprintf('$nrOfBytes must be greater than 0, %d given', $nrOfBytes));
        }

        return substr($this->content, $from, $nrOfBytes);
    }

    #[Override]
    public function slice(int $startByteOffset, int $endByteOffset): string {
        if ($startByteOffset <= 0) {
            throw new InvalidArgumentException(sprintf('$startByteOffset must be greater than 0, %d given', $startByteOffset));
        }

        if ($endByteOffset - $startByteOffset < 1) {
            throw new InvalidArgumentException(sprintf('End byte offset %d should be bigger than start byte offset %d', $endByteOffset, $startByteOffset));
        }

        return substr($this->content, $startByteOffset, $endByteOffset - $startByteOffset);
    }

    #[Override]
    public function chars(int $from, int $nrOfBytes): iterable {
        if ($from < 0) {
            throw new InvalidArgumentException(sprintf('$from must be greater than zero, %d given', $from));
        }

        if ($nrOfBytes <= 0) {
            throw new InvalidArgumentException(sprintf('$nrOfBytes to read must be greater than zero, %d given', $nrOfBytes));
        }

        foreach (str_split(substr($this->content, $from, $nrOfBytes)) as $char) {
            yield $char;
        }
    }

    #[Override]
    public function firstPos(WhitespaceCharacter|DelimiterCharacter|ToUnicodeCMapOperator|Marker $needle, int $offsetFromStart, int $before): ?int {
        $firstPos = strpos($this->content, $needle->value, $offsetFromStart);
        if ($firstPos === false || $firstPos > $before) {
            return null;
        }

        return $firstPos;
    }

    #[Override]
    public function lastPos(WhitespaceCharacter|DelimiterCharacter|ToUnicodeCMapOperator|Marker $needle, int $offsetFromEnd): ?int {
        $pos = strrpos($this->content, $needle->value, -$offsetFromEnd);
        if ($pos === false) {
            return null;
        }

        return $pos;
    }
}
