<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Reference;

use Override;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\DictionaryValue;
use PrinsFrank\PdfParser\Exception\ParseFailureException;

class ReferenceValueArray implements DictionaryValue {
    /** @var list<ReferenceValue> */
    public readonly array $referenceValues;

    /** @no-named-arguments */
    public function __construct(ReferenceValue ...$referenceValues) {
        $this->referenceValues = $referenceValues;
    }

    #[Override]
    public static function fromValue(string $valueString): ?self {
        if (!str_starts_with($valueString, '[') || !str_ends_with($valueString, ']')) {
            return null;
        }

        $valueString = str_replace(["\r", "\n", '  '], ' ', $valueString);
        $referenceParts = explode(' ', trim(rtrim(ltrim($valueString, '['), ']')));
        $nrOfReferenceParts = count($referenceParts);
        if ($nrOfReferenceParts % 3 !== 0) {
            return null;
        }

        $referenceValues = [];
        for ($i = 0; $i < $nrOfReferenceParts; $i += 3) {
            /** @phpstan-ignore offsetAccess.notFound, offsetAccess.notFound, offsetAccess.notFound */
            $referenceValues[] = ReferenceValue::fromValue($referenceParts[$i] . ' ' . $referenceParts[$i + 1] . ' ' . $referenceParts[$i + 2])
                ?? throw new ParseFailureException();
        }

        return new self(... $referenceValues);
    }
}
