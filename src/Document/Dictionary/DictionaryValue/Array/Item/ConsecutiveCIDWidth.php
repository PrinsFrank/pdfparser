<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Array\Item;

class ConsecutiveCIDWidth {
    /** @param list<int> $widths */
    public function __construct(
        public readonly int $cidStart,
        public readonly array $widths,
    ) {
    }

    public function getWidthForCharacterCode(int $characterCode): ?int {
        return $this->widths[$characterCode - $this->cidStart] ?? null;
    }
}
