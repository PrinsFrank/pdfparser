<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\CMap\ToUnicode;

use PrinsFrank\PdfParser\Exception\InvalidArgumentException;
use PrinsFrank\PdfParser\Exception\PdfParserException;

class ToUnicodeCMap {
    /** @var list<BFRange|BFChar> */
    private readonly array $bfCharRangeInfo;

    /**
     * @no-named-arguments
     *
     * @param int<1, max> $byteSize
     * @throws InvalidArgumentException
     */
    public function __construct(
        public readonly int $codeSpaceStart,
        public readonly int $codeSpaceEnd,
        public readonly int $byteSize,
        BFRange|BFChar ...$bfCharRangeInfo,
    ) {
        $this->bfCharRangeInfo = $bfCharRangeInfo;
        if ($this->byteSize < 1) {
            throw new InvalidArgumentException();
        }
    }

    /** @throws PdfParserException */
    public function textToUnicode(string $characterGroup): string {
        return implode(
            '',
            array_map(
                fn (string $character) => $this->charToUnicode((int) hexdec($character)) ?? '',
                str_split($characterGroup, $this->byteSize * 2)
            )
        );
    }

    /** @throws PdfParserException */
    protected function charToUnicode(int $characterCode): ?string {
        foreach ($this->bfCharRangeInfo as $bfCharRangeInfo) {
            if (!$bfCharRangeInfo->containsCharacterCode($characterCode)) {
                continue;
            }

            return $bfCharRangeInfo->toUnicode($characterCode);
        }

        if ($characterCode === 0) {
            return '';
        }

        return null;
    }
}
