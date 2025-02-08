<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Rectangle;

use Override;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\DictionaryValue;

class Rectangle implements DictionaryValue {
    public function __construct(
        public readonly float $xTopLeft,
        public readonly float $yTopLeft,
        public readonly float $xBottomRight,
        public readonly float $yBottomRight
    ) {
    }

    #[Override]
    public static function fromValue(string $valueString): ?self {
        if (!str_starts_with($valueString, '[') || !str_ends_with($valueString, ']')) {
            return null;
        }

        $coords = explode(' ', trim(rtrim(ltrim($valueString, '['), ']')));
        if (count($coords) !== 4) {
            return null;
        }

        return new self(... array_map('floatval', $coords));
    }
}
