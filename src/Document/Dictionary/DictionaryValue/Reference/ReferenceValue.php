<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Reference;

use Override;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\DictionaryValue;

/** @api */
class ReferenceValue implements DictionaryValue {
    public function __construct(
        public readonly int $objectNumber,
        public readonly int $versionNumber
    ) {
    }

    #[Override]
    public static function fromValue(string $valueString): ?self {
        $referenceParts = explode(' ', $valueString);
        if (count($referenceParts) !== 3) {
            return null;
        }

        if ($referenceParts[2] !== 'R') {
            return null;
        }

        $referenceObjectNumberAsInt = (int) $referenceParts[0];
        if ((string) $referenceObjectNumberAsInt !== $referenceParts[0]) {
            return null;
        }

        $referenceVersionNumberAsInt = (int) $referenceParts[1];
        if ((string) $referenceVersionNumberAsInt !== $referenceParts[1]) {
            return null;
        }

        return new self($referenceObjectNumberAsInt, $referenceVersionNumberAsInt);
    }
}
