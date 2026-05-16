<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Font;

readonly class FontWidths {
    /** @param list<float> $widths */
    public function __construct(
        public int   $firstChar,
        public array $widths,
    ) {}

    public function getWidthForCharacter(int $characterCode): ?float {
        return $this->widths[$characterCode - $this->firstChar] ?? null;
    }
}
