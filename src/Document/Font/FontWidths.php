<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Font;

class FontWidths {
    /** @param list<float> $widths */
    public function __construct(
        public readonly int $firstChar,
        public readonly array $widths,
    ) {
    }

    public function getWidthForCharacter(int $characterCode): ?float {
        return $this->widths[$characterCode - $this->firstChar] ?? null;
    }
}
