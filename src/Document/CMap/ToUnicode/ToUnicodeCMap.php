<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\CMap\ToUnicode;

use PrinsFrank\PdfParser\Exception\InvalidArgumentException;
use PrinsFrank\PdfParser\Exception\PdfParserException;

class ToUnicodeCMap {
    /** @var list<BFRange|BFChar> */
    private readonly array $bfCharRangeInfo;

    /** @var array<int, string|null> */
    private array $charCache = [];

    /**
     * @no-named-arguments
     *
     * @param list<CodeSpaceRange> $codeSpaceRanges
     * @param int<1, max> $byteSize
     * @throws InvalidArgumentException
     */
    public function __construct(
        public readonly array   $codeSpaceRanges,
        public readonly int     $byteSize,
        BFRange|BFChar ...$bfCharRangeInfo,
    ) {
        $this->bfCharRangeInfo = $bfCharRangeInfo;
        if ($this->byteSize < 1) {
            throw new InvalidArgumentException();
        }
    }

    /** @throws PdfParserException */
    public function textToUnicode(string $characterGroup): string {
        $unicode = '';
        $chunkSize = $this->byteSize * 2;
        $nrOfChunks = strlen($characterGroup) / $chunkSize;
        for ($i = 0; $i < $nrOfChunks; $i++) {
            $unicode .= $this->charToUnicode((int) hexdec(substr($characterGroup, $i * $chunkSize, $chunkSize))) ?? '';
        }

        return $unicode;
    }

    /** @throws PdfParserException */
    protected function charToUnicode(int $characterCode): ?string {
        if (array_key_exists($characterCode, $this->charCache)) {
            return $this->charCache[$characterCode];
        }

        $char = null;
        foreach ($this->bfCharRangeInfo as $bfCharRangeInfo) {
            if (!$bfCharRangeInfo->containsCharacterCode($characterCode)) {
                continue;
            }

            if (($char = $bfCharRangeInfo->toUnicode($characterCode)) !== "\0") { // Some characters map to NULL in one BFRange and to an actual character in another
                return $this->charCache[$characterCode] = $char;
            }
        }

        if ($char === "\0") {
            return $this->charCache[$characterCode] = $char; // Only return NULL when it is the only character this is mapped to
        }

        if ($characterCode === 0) {
            return $this->charCache[$characterCode] = '';
        }

        return $this->charCache[$characterCode] = null;
    }
}
