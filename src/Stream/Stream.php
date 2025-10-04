<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Stream;

use PrinsFrank\PdfParser\Document\CMap\ToUnicode\ToUnicodeCMapOperator;
use PrinsFrank\PdfParser\Document\Generic\Character\DelimiterCharacter;
use PrinsFrank\PdfParser\Document\Generic\Character\WhitespaceCharacter;
use PrinsFrank\PdfParser\Document\Generic\Marker;

interface Stream {
    public function getSizeInBytes(): int;

    /** @phpstan-assert int<1, max> $nrOfBytes */
    public function read(int $from, int $nrOfBytes): string;

    public function toString(): string;

    /**
     * @phpstan-assert int<0, max> $startByteOffset
     * @phpstan-assert int<0, max> $endByteOffset
     */
    public function slice(int $startByteOffset, int $endByteOffset): string;

    /**
     * @phpstan-assert int<0, max> $from
     * @phpstan-assert int<1, max> $nrOfBytes
     *
     * @return iterable<string>
     */
    public function chars(int $from, int $nrOfBytes): iterable;

    public function firstPos(WhitespaceCharacter|Marker|DelimiterCharacter|ToUnicodeCMapOperator $needle, int $offsetFromStart, int $before): ?int;

    public function lastPos(WhitespaceCharacter|Marker|DelimiterCharacter|ToUnicodeCMapOperator $needle, int $offsetFromEnd): ?int;

    public function getStartNextLineAfter(WhitespaceCharacter|Marker|DelimiterCharacter|ToUnicodeCMapOperator $needle, int $offsetFromStart, int $before): ?int;

    public function getStartOfNextLine(int $byteOffset, int $before): ?int;

    public function getEndOfCurrentLine(int $byteOffset, int $before): ?int;
}
