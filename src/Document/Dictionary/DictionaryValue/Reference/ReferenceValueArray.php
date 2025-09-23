<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Reference;

use Override;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\DictionaryValue;
use PrinsFrank\PdfParser\Exception\ParseFailureException;

/** @api */
class ReferenceValueArray implements DictionaryValue {
    /** @var list<ReferenceValue> */
    public readonly array $referenceValues;

    /** @no-named-arguments */
    public function __construct(ReferenceValue ...$referenceValues) {
        $this->referenceValues = $referenceValues;
    }

    #[Override]
    /** @throws ParseFailureException */
    public static function fromValue(string $valueString): ?self {
        if (!str_starts_with($valueString, '[') || !str_ends_with($valueString, ']')) {
            return null;
        }

        $valueString = preg_replace('/\s+/', ' ', $valueString)
            ?? throw new ParseFailureException('An unexpected error occurred while sanitizing reference value array');
        $valueString = trim(rtrim(ltrim($valueString, '['), ']'));
        if ($valueString === '') {
            return new self();
        }

        $referenceParts = explode(' ', $valueString);
        $nrOfReferenceParts = count($referenceParts);
        if ($nrOfReferenceParts % 3 !== 0) {
            return null;
        }

        $referenceValues = [];
        for ($i = 0; $i < $nrOfReferenceParts; $i += 3) {
            /** @phpstan-ignore offsetAccess.notFound, offsetAccess.notFound, offsetAccess.notFound */
            $string = $referenceParts[$i] . ' ' . $referenceParts[$i + 1] . ' ' . $referenceParts[$i + 2];

            $referenceValues[] = ReferenceValue::fromValue($string)
                ?? throw new ParseFailureException(sprintf('Could not parse reference value "%s" at index %d in "%s"', $string, $i, $valueString));
        }

        return new self(... $referenceValues);
    }
}
