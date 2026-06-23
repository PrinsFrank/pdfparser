<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Array;

use Override;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Array\Item\ConsecutiveCIDWidth;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Array\Item\RangeCIDWidth;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\DictionaryValue;
use PrinsFrank\PdfParser\Exception\RuntimeException;

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

        if (preg_match_all('/(?<startingCID>[0-9]+)\s*(?<CIDS>[0-9]+\s*[0-9.]+|\[[0-9. ]+\])/', $valueString, $matches, PREG_SET_ORDER) <= 0) {
            return null;
        }

        $widths = [];
        foreach ($matches as $match) {
            if ((string) ($startingCID = (int) $match['startingCID']) !== $match['startingCID']) {
                return null;
            }

            if (str_starts_with($match['CIDS'], '[') && str_ends_with($match['CIDS'], ']')) {
                $cidWidths = preg_split('/\s+/', trim(rtrim(ltrim($match['CIDS'], '['), ']')), -1, PREG_SPLIT_NO_EMPTY);
                if ($cidWidths === false) {
                    throw new RuntimeException('Something went wrong while splitting');
                }

                $widths[] = new ConsecutiveCIDWidth($startingCID, array_map('floatval', $cidWidths));

                continue;
            }

            $arguments = explode(' ', $match['CIDS']);
            if (count($arguments) !== 2) {
                return null;
            }

            if ((string) ($endCID = (int) $arguments[0]) !== $arguments[0] || (string) ($width = (float) $arguments[1]) !== $arguments[1]) {
                return null;
            }

            $widths[] = new RangeCIDWidth($startingCID, $endCID, $width);
        }

        return new self(... $widths);
    }
}
