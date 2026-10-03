<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Font;

class FontWidths {
    /** @var array<int, float|null> */
    private array $widthCache = [];
    /** @param list<float> $widths */
    public function __construct(
        public readonly int   $firstChar,
        public readonly array $widths,
    ) {}

    public function getWidthForCharacter(int $characterCode): ?float {
        if (isset($this->widthCache[$characterCode])) {
            return $this->widthCache[$characterCode];
        }

        $width = $this->widths[$characterCode - $this->firstChar] ?? null;
        if ($width === null) {
            return $this->widthCache[$characterCode] = null;
        }

        return $this->widthCache[$characterCode] = $width / 1000;
    }
}
