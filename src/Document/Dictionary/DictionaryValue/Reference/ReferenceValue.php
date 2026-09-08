<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Reference;

use Override;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\DictionaryValue;
use PrinsFrank\PdfParser\Exception\ParseFailureException;

/** @api */
readonly class ReferenceValue implements DictionaryValue {
    public function __construct(
        public int $objectNumber,
        public int $versionNumber,
    ) {}

    #[Override]
    public static function fromValue(string $valueString): ?self {
        $valueString = preg_replace('/\s+/', ' ', $valueString)
            ?? throw new ParseFailureException('An unexpected error occurred while sanitizing reference value array');

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
