<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\CMap\ToUnicode;

use PrinsFrank\PdfParser\Exception\ParseFailureException;

/** @internal */
readonly class BFChar {
    public function __construct(
        public int    $sourceCode,
        public string $destinationString,
    ) {}

    public function containsCharacterCode(int $characterCode): bool {
        return $characterCode === $this->sourceCode;
    }

    /** @throws ParseFailureException */
    public function toUnicode(int $characterCode): ?string {
        if ($characterCode !== $this->sourceCode) {
            throw new ParseFailureException(sprintf('This BFChar does not contain character code %d', $characterCode));
        }

        return CodePoint::toString($this->destinationString);
    }
}
