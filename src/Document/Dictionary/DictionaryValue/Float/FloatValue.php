<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Float;

use Override;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\DictionaryValue;

/** @api */
readonly class FloatValue implements DictionaryValue {
    public function __construct(
        public float $value,
    ) {}

    #[Override]
    public static function fromValue(string $valueString): ?self {
        $valueString = trim($valueString, "\x00\x09\x0A\x0C\x0D\x20");
        if (preg_match('/^[+-]?(?:[0-9]+(?:\.[0-9]*)?|\.[0-9]+)$/D', $valueString) !== 1) {
            return null;
        }

        $value = (float) $valueString;
        return is_finite($value) ? new self($value) : null;
    }
}
