<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Font;

use PrinsFrank\GlyphLists\AGlyphList;
use PrinsFrank\PdfParser\Exception\InvalidArgumentException;

class CharSet {
    /** @param list<AGlyphList> $characterNames */
    public function __construct(
        private readonly array $characterNames,
    ) {
    }

    public function getCharacterAtIndex(int $index): ?string {
        if (!array_key_exists($index, $this->characterNames)) {
            return null;
        }

        return $this->characterNames[$index]->getChar();
    }

    public static function fromString(string $charSetString): ?self {
        if ($charSetString === '') {
            return new self([]);
        }

        return new self(
            array_map(
                fn (string $charSetName) => AGlyphList::tryFrom($charSetName)
                    ?? throw new InvalidArgumentException(sprintf('No glyph with name "%s" found', $charSetName)),
                explode('/', ltrim($charSetString, '/')),
            )
        );
    }
}
