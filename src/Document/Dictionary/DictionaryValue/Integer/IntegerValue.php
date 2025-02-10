<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Integer;

use Override;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\DictionaryValue;

/** @api */
class IntegerValue implements DictionaryValue {
    public function __construct(
        public readonly int $value
    ) {
    }

    #[Override]
    public static function fromValue(string $valueString): ?self {
        $valueAsInt = (int) $valueString;
        if ((string) $valueAsInt !== $valueString) {
            return null;
        }

        return new self($valueAsInt);
    }
}
