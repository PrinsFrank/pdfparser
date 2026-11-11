<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Integer;

use Override;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\DictionaryValue;

/**
 * @api
 *
 * @see 7.3.3 Numeric objects, Integer objects
 */
readonly class IntegerValue implements DictionaryValue {
    public function __construct(
        public int $value,
    ) {}

    #[Override]
    public static function fromValue(string $valueString): ?self {
        $valueString = trim($valueString);
        if (preg_match('/^[+-]?[0-9]+$/', $valueString) !== 1) {
            return null;
        }

        return new self((int) $valueString);
    }
}
