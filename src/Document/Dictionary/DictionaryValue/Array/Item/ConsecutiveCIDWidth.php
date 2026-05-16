<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Array\Item;

readonly class ConsecutiveCIDWidth {
    /** @param list<float> $widths */
    public function __construct(
        public int   $cidStart,
        public array $widths,
    ) {}

    public function getWidthForCharacterCode(int $characterCode): ?float {
        if (array_key_exists($characterCode - $this->cidStart, $this->widths) === false) {
            return null;
        }

        return $this->widths[$characterCode - $this->cidStart] / 1000;
    }
}
