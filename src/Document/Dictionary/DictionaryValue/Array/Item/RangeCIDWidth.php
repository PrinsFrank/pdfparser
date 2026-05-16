<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Array\Item;

readonly class RangeCIDWidth {
    public function __construct(
        public int $cidStart,
        public int $cidEnd,
        public float $width,
    ) {}

    public function getWidthForCharacterCode(int $characterCode): ?float {
        if ($characterCode < $this->cidStart || $characterCode > $this->cidEnd) {
            return null;
        }

        return $this->width / 1000;
    }
}
