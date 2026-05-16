<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Rectangle;

use Override;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\DictionaryValue;
use PrinsFrank\PdfParser\Exception\RuntimeException;

/** @api */
readonly class Rectangle implements DictionaryValue {
    public function __construct(
        public float $xTopLeft,
        public float $yTopLeft,
        public float $xBottomRight,
        public float $yBottomRight,
    ) {}

    public function getWidth(): float {
        return abs($this->xBottomRight - $this->xTopLeft);
    }

    public function getHeight(): float {
        return abs($this->yBottomRight - $this->yTopLeft);
    }

    #[Override]
    public static function fromValue(string $valueString): ?self {
        if (!str_starts_with($valueString, '[') || !str_ends_with($valueString, ']')) {
            return null;
        }

        $valueString = str_replace([' ', "\r", "\n"], ' ', $valueString);
        $valueString = preg_replace('/\s+/', ' ', $valueString)
            ?? throw new RuntimeException(preg_last_error_msg());
        $coords = explode(' ', trim(rtrim(ltrim($valueString, '['), ']')));
        if (count($coords) !== 4) {
            return null;
        }

        return new self(... array_map('floatval', $coords));
    }
}
