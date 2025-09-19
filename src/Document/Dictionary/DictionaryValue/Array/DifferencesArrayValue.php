<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Array;

use Override;
use PrinsFrank\GlyphLists\AGlyphList;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Array\Item\DifferenceRange;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\DictionaryValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Reference\ReferenceValueArray;

class DifferencesArrayValue implements DictionaryValue {
    /** @param list<DifferenceRange> $differenceRanges */
    public function __construct(
        private readonly array $differenceRanges,
    ) {
    }

    #[Override]
    public static function fromValue(string $valueString): ?self {
        if (($arrayValue = ArrayValue::fromValue($valueString)) === null || $arrayValue instanceof ReferenceValueArray) {
            return null;
        }

        $startIndex = null;
        $characters = $differenceRanges = [];
        foreach ($arrayValue->value as $arrayValueItem) {
            if (is_int($arrayValueItem)) {
                if ($startIndex !== null) {
                    $differenceRanges[] = new DifferenceRange($startIndex, $characters);
                    $characters = [];
                }

                $startIndex = $arrayValueItem;
            } elseif (is_string($arrayValueItem)) {
                $characters[] = AGlyphList::tryFrom(ltrim($arrayValueItem, '/'));
            } else {
                return null;
            }
        }

        if ($startIndex !== null) {
            $differenceRanges[] = new DifferenceRange($startIndex, $characters);
        }

        return new self($differenceRanges);
    }

    public function getGlyph(int $int): ?AGlyphList {
        foreach ($this->differenceRanges as $differenceRange) {
            if ($differenceRange->contains($int)) {
                return $differenceRange->getGlyph($int);
            }
        }

        return null;
    }
}
