<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Array\Item;

class RangeCIDWidth {
    public function __construct(
        public readonly int $cidStart,
        public readonly int $cidEnd,
        public readonly float $width,
    ) {
    }

    public function getWidthForCharacterCode(int $characterCode): ?float {
        if ($characterCode < $this->cidStart || $characterCode > $this->cidEnd) {
            return null;
        }

        return $this->width / 1000;
    }
}
