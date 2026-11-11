<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Float;

use Override;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\DictionaryValue;

/**
 * @api
 *
 * @see 7.3.3 numeric objects, Real objects
 */
readonly class FloatValue implements DictionaryValue {
    public function __construct(
        public float $value,
    ) {}

    #[Override]
    public static function fromValue(string $valueString): ?self {
        $valueString = trim($valueString);
        if (preg_match('/^[+-]?(?:[0-9]+\.?[0-9]*|\.[0-9]+)$/', $valueString) !== 1) {
            return null;
        }

        return new self((float) $valueString);
    }
}
