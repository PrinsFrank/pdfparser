<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Array;

use Override;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\DictionaryValue;
use PrinsFrank\PdfParser\Exception\RuntimeException;

class WValue implements DictionaryValue {
    public function __construct(
        public readonly int $lengthRecord1InBytes,
        public readonly int $lengthRecord2InBytes,
        public readonly int $lengthRecord3InBytes,
    ) {
    }

    /** @return int<1, max> */
    public function getTotalLengthInBytes(): int {
        $totalLength = $this->lengthRecord1InBytes + $this->lengthRecord2InBytes + $this->lengthRecord3InBytes;
        if ($totalLength < 1) {
            throw new RuntimeException(sprintf('Total length should not be less than 1, got %d', $totalLength));
        }

        return $totalLength;
    }

    #[Override]
    public static function fromValue(string $valueString): ?self {
        if (!str_starts_with($valueString, '[') || !str_ends_with($valueString, ']')) {
            return null;
        }

        $values = explode(' ', trim(rtrim(ltrim($valueString, '['), ']')));
        if (count($values) !== 3) {
            return null;
        }

        return new self((int) $values[0], (int) $values[1], (int) $values[2]);
    }
}
