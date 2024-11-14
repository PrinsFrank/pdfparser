<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\DictionaryValueType\Reference;

use Override;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\DictionaryValueType\DictionaryValueType;
use PrinsFrank\PdfParser\Exception\InvalidDictionaryValueTypeFormatException;

class ReferenceValueArray implements DictionaryValueType {
    /** @var list<ReferenceValue> */
    public readonly array $referenceValues;

    /** @no-named-arguments */
    public function __construct(ReferenceValue ...$referenceValues) {
        $this->referenceValues = $referenceValues;
    }

    #[Override]
    public static function fromValue(string $valueString): self {
        $referenceParts = explode(' ', trim(rtrim(ltrim($valueString, '['), ']')));
        $nrOfReferenceParts = count($referenceParts);
        if ($nrOfReferenceParts % 3 !== 0) {
            throw new InvalidDictionaryValueTypeFormatException('Invalid valueString, expected a multiple of 3 parts: "' . $valueString . '"');
        }

        $referenceValues = [];
        for ($i = 0; $i < $nrOfReferenceParts; $i += 3) {
            /** @phpstan-ignore offsetAccess.notFound, offsetAccess.notFound, offsetAccess.notFound */
            $referenceValues[] = ReferenceValue::fromValue($referenceParts[$i] . ' ' . $referenceParts[$i + 1] . ' ' . $referenceParts[$i + 2]);
        }

        return new self(... $referenceValues);
    }
}
