<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\CMap\ToUnicode;

use PrinsFrank\PdfParser\Exception\ParseFailureException;
use PrinsFrank\PdfParser\Exception\PdfParserException;
use PrinsFrank\PdfParser\Exception\RuntimeException;

/** @internal */
class BFRange {
    /** @param list<int> $destinationCodePoints */
    public function __construct(
        public readonly int   $sourceCodeStart,
        public readonly int   $sourceCodeEnd,
        public readonly array $destinationCodePoints,
    ) {
    }

    public function containsCharacterCode(int $characterCode): bool {
        return $characterCode >= $this->sourceCodeStart
            && $characterCode <= $this->sourceCodeEnd;
    }

    /** @throws PdfParserException */
    public function toUnicode(int $characterCode): ?string {
        if (count($this->destinationCodePoints) === 1) {
            $destinationCodePoint = $this->destinationCodePoints[0] + $characterCode - $this->sourceCodeStart;
        } else {
            $destinationCodePoint = $this->destinationCodePoints[$characterCode - $this->sourceCodeStart]
                ?? throw new RuntimeException(sprintf('Character code %d was not found in BFRange of length %d with start %d and end %d', $characterCode, count($this->destinationCodePoints), $this->sourceCodeStart, $this->sourceCodeEnd));
        }

        if ($destinationCodePoint <= 0x10FFFF) {
            return mb_chr($destinationCodePoint);
        }

        return mb_chr(
            $this->getCodePointForSurrogatePair($destinationCodePoint)
                ?? throw new ParseFailureException('Destination code point is neither a valid unicode char nor a surrogate pair')
        );
    }

    private function getCodePointForSurrogatePair(int $surrogatePair): ?int {
        $highSurrogate = ($surrogatePair >> 16) & 0xFFFF;
        $lowSurrogate = $surrogatePair & 0xFFFF;
        if ($highSurrogate < 0xD800 || $highSurrogate > 0xDBFF || $lowSurrogate < 0xDC00 || $lowSurrogate > 0xDFFF) {
            return null;
        }

        return (($highSurrogate - 0xD800) << 10) + ($lowSurrogate - 0xDC00) + 0x10000;
    }
}
