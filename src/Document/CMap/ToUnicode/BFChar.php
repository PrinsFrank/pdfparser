<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\CMap\ToUnicode;

use PrinsFrank\PdfParser\Exception\ParseFailureException;

class BFChar {
    public function __construct(
        public readonly int $sourceCode,
        public readonly int $destinationString,
    ) {
    }

    public function containsCharacterCode(int $characterCode): bool {
        return $characterCode === $this->sourceCode;
    }

    /** @throws ParseFailureException */
    public function toUnicode(int $characterCode): ?string {
        if ($characterCode !== $this->sourceCode) {
            throw new ParseFailureException();
        }

        $string = '';
        $hexString = dechex($this->destinationString);
        $hexString = str_pad($hexString, (int) ceil(strlen($hexString) / 4) * 4, '0', STR_PAD_LEFT);
        foreach (str_split($hexString, 4) as $char) {
            $string .= mb_chr((int) hexdec($char));
        }

        return $string;
    }
}
