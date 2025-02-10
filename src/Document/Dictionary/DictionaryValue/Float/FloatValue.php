<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Float;

use Override;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\DictionaryValue;

/** @api */
class FloatValue implements DictionaryValue {
    public function __construct(
        public readonly float $value
    ) {
    }

    #[Override]
    public static function fromValue(string $valueString): ?self {
        $valueAsFloat = (float) $valueString;
        if (number_format($valueAsFloat, (int) strpos(strrev($valueString), ".")) !== $valueString) {
            return null;
        }

        return new self($valueAsFloat);
    }
}
