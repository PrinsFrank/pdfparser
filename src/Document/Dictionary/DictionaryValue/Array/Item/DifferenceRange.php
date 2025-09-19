<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Array\Item;

use PrinsFrank\GlyphLists\AGlyphList;
use PrinsFrank\PdfParser\Exception\InvalidArgumentException;
use PrinsFrank\PdfParser\Exception\RuntimeException;

class DifferenceRange {
    /** @param list<AGlyphList|null> $characters */
    public function __construct(
        private readonly int $firstIndex,
        private readonly array $characters,
    ) {
    }

    public function contains(int $index): bool {
        return $index >= $this->firstIndex
            && $index < $this->firstIndex + count($this->characters);
    }

    public function getGlyph(int $index): ?AGlyphList {
        if (!$this->contains($index)) {
            throw new InvalidArgumentException('This difference range does not contain index ' . $index);
        }

        if (!array_key_exists($index - $this->firstIndex, $this->characters)) {
            throw new RuntimeException('Expected glyph to be present, but it was not');
        }

        return $this->characters[$index - $this->firstIndex];
    }
}
