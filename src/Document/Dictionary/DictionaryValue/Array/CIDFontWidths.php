<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Array;

use Override;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Array\Item\ConsecutiveCIDWidth;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Array\Item\RangeCIDWidth;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\DictionaryValue;

/** @see 9.7.4.3 Glyph metrics in CIDFonts */
class CIDFontWidths implements DictionaryValue {
    /** @var list<ConsecutiveCIDWidth|RangeCIDWidth> */
    private readonly array $widths;

    /** @no-named-arguments */
    public function __construct(
        ConsecutiveCIDWidth|RangeCIDWidth ...$widths,
    ) {
        $this->widths = $widths;
    }

    public function getWidthForCharacter(int $characterCode): ?float {
        foreach ($this->widths as $widthItem) {
            if (($widthForCharacterCode = $widthItem->getWidthForCharacterCode($characterCode)) !== null) {
                return $widthForCharacterCode;
            }
        }

        return null;
    }

    #[Override]
    public static function fromValue(string $valueString): ?self {
        $valueString = str_replace("\n", ' ', $valueString);
        if (str_starts_with($trimmedValueString = trim($valueString), '[') && str_ends_with($trimmedValueString, ']') && trim(rtrim(ltrim($trimmedValueString, '['), ']')) === '') {
            return new self();
        }

        if (($arrayValue = ArrayValue::fromValue($valueString)) instanceof ArrayValue === false) {
            return null;
        }

        $widths = [];
        $nrOfTopLevelItems = count($arrayValue->value);
        for ($i = 0; $i < $nrOfTopLevelItems; $i++) {
            $item = $arrayValue->value[$i];
            if (is_int($item) === false) {
                return null;
            }

            if (($nextItem = $arrayValue->value[$i + 1] ?? null) instanceof ArrayValue) {
                foreach ($nextItem->value as $itemValue) {
                    if (is_string($itemValue) === false && is_int($itemValue) === false) {
                        return null;
                    }
                }

                $widths[] = new ConsecutiveCIDWidth($item, array_map(fn(string|int $value) => is_string($value) ? (float) $value : $value, $nextItem->value));
                $i++;
            } elseif (is_int($nextItem) && (is_string($secondNextItem = $arrayValue->value[$i + 2] ?? null) || is_int($secondNextItem))) {
                $widths[] = new RangeCIDWidth($item, $nextItem, (float) $secondNextItem);
                $i += 2;
            } else {
                return null;
            }
        }

        return new self(... $widths);
    }
}
