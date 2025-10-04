<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Stream;

use Override;
use PrinsFrank\PdfParser\Document\CMap\ToUnicode\ToUnicodeCMapOperator;
use PrinsFrank\PdfParser\Document\Generic\Character\DelimiterCharacter;
use PrinsFrank\PdfParser\Document\Generic\Character\WhitespaceCharacter;
use PrinsFrank\PdfParser\Document\Generic\Marker;

abstract class AbstractStream implements Stream {
    #[Override]
    public function getStartNextLineAfter(WhitespaceCharacter|Marker|DelimiterCharacter|ToUnicodeCMapOperator $needle, int $offsetFromStart, int $before): ?int {
        $markerPos = $this->firstPos($needle, $offsetFromStart, $before);
        if ($markerPos === null) {
            return null;
        }

        return $this->getStartOfNextLine($markerPos, $before);
    }

    #[Override]
    public function getStartOfNextLine(int $byteOffset, int $before): ?int {
        $firstLineFeedPos = $this->firstPos(WhitespaceCharacter::LINE_FEED, $byteOffset, $before);
        $firstCarriageReturnPos = $this->firstPos(WhitespaceCharacter::CARRIAGE_RETURN, $byteOffset, $before);
        if ($firstLineFeedPos === null && $firstCarriageReturnPos === null) {
            return null;
        }

        if ($firstCarriageReturnPos === null) {
            return $firstLineFeedPos + 1;
        }

        if ($firstLineFeedPos === null) {
            return $firstCarriageReturnPos + 1;
        }

        return min($firstLineFeedPos, $firstCarriageReturnPos)
            + (abs($firstCarriageReturnPos - $firstLineFeedPos) === 1 ? 2 : 1); // If the CR and LF are next to each other, we need to add 2 bytes, otherwise 1
    }

    #[Override]
    public function getEndOfCurrentLine(int $byteOffset, int $before): ?int {
        $firstLineFeedPos = $this->firstPos(WhitespaceCharacter::LINE_FEED, $byteOffset, $before);
        $firstCarriageReturnPos = $this->firstPos(WhitespaceCharacter::CARRIAGE_RETURN, $byteOffset, $before);
        if ($firstLineFeedPos === null && $firstCarriageReturnPos === null) {
            return null;
        }

        if ($firstCarriageReturnPos === null) {
            return $firstLineFeedPos;
        }

        if ($firstLineFeedPos === null) {
            return $firstCarriageReturnPos;
        }

        return min($firstLineFeedPos, $firstCarriageReturnPos);
    }

    #[Override]
    public function toString(): string {
        return $this->read(0, $this->getSizeInBytes());
    }
}
