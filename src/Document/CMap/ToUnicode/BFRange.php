<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\CMap\ToUnicode;

use PrinsFrank\PdfParser\Exception\ParseFailureException;

/** @internal */
class BFRange {
    /** @param list<string> $destinationCodePoints */
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

    /** @throws ParseFailureException */
    public function toUnicode(int $characterCode): ?string {
        if (count($this->destinationCodePoints) === 1) {
            return CodePoint::toString(
                dechex(((int) hexdec($this->destinationCodePoints[0])) + $characterCode - $this->sourceCodeStart),
            );
        }

        return CodePoint::toString(
            $this->destinationCodePoints[$characterCode - $this->sourceCodeStart]
                ?? throw new ParseFailureException(),
        );
    }
}
