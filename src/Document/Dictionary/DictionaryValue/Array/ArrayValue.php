<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Array;

use Override;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\DictionaryValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Reference\ReferenceValueArray;
use PrinsFrank\PdfParser\Exception\PdfParserException;
use PrinsFrank\PdfParser\Exception\RuntimeException;

class ArrayValue implements DictionaryValue {
    /** @param array<mixed> $value */
    public function __construct(
        public readonly array $value
    ) {
    }

    #[Override]
    /** @throws PdfParserException */
    public static function fromValue(string $valueString): null|self|ReferenceValueArray {
        if (!str_starts_with($valueString, '[') || !str_ends_with($valueString, ']')) {
            return null;
        }

        $array = [];
        $valueString = preg_replace('/(<[^>]*>)(?=<[^>]*>)/', '$1 $2', $valueString)
            ?? throw new RuntimeException('An error occurred while sanitizing array value');
        $values = explode(' ', rtrim(ltrim($valueString, '[ '), ' ]'));
        if (count($values) % 3 === 0 && array_key_exists(2, $values) && $values[2] === 'R') {
            return ReferenceValueArray::fromValue($valueString);
        }

        foreach ($values as $value) {
            if (str_starts_with($value, '[') && str_ends_with($value, ']')) {
                $array[] = self::fromValue($value);
            } elseif ((string) (int) $value === $value) {
                $array[] = (int) $value;
            } else {
                $array[] = $value;
            }
        }

        return new self($array);
    }
}
