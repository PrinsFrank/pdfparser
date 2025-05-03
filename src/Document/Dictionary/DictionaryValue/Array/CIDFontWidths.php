<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Array;

use Override;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Array\Item\ConsecutiveCIDWidth;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Array\Item\RangeCIDWidth;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\DictionaryValue;

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
        if (preg_match_all('/(?<startingCID>[0-9]+)\s+(?<CIDS>[0-9]+\s*[0-9.]+|\[[0-9. ]+\])/', $valueString, $matches, PREG_SET_ORDER) <= 0) {
            return null;
        }

        $widths = [];
        foreach ($matches as $match) {
            if ((string) ($startingCID = (int) $match['startingCID']) !== $match['startingCID']) {
                return null;
            }

            if (str_starts_with($match['CIDS'], '[') && str_ends_with($match['CIDS'], ']')) {
                $widths[] = new ConsecutiveCIDWidth($startingCID, array_map('floatval', explode(' ', rtrim(ltrim($match['CIDS'], '['), ']'))));

                continue;
            }

            $arguments = explode(' ', $match['CIDS']);
            if (count($arguments) !== 2) {
                return null;
            }

            if ((string)($endCID = (int) $arguments[0]) !== $arguments[0] || (string)($width = (float) $arguments[1]) !== $arguments[1]) {
                return null;
            }

            $widths[] = new RangeCIDWidth($startingCID, $endCID, $width);
        }

        return new self(... $widths);
    }
}
