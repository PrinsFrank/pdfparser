<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Integer;

use Override;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\DictionaryValue;

/** @api */
readonly class IntegerValue implements DictionaryValue {
    public function __construct(
        public int $value,
    ) {}

    #[Override]
    public static function fromValue(string $valueString): ?self {
        $valueAsInt = (int) $valueString;
        if ((string) $valueAsInt !== $valueString
            && (string) $valueAsInt !== ltrim($valueString, '0')) {
            return null;
        }

        return new self($valueAsInt);
    }
}
