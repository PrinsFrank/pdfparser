<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Array\Item;

class ConsecutiveCIDWidth {
    /** @param list<float> $widths */
    public function __construct(
        public readonly int $cidStart,
        public readonly array $widths,
    ) {
    }

    public function getWidthForCharacterCode(int $characterCode): ?float {
        if (array_key_exists($characterCode - $this->cidStart, $this->widths) === false) {
            return null;
        }

        return $this->widths[$characterCode - $this->cidStart] / 1000;
    }
}
