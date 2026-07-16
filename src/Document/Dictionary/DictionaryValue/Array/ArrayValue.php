<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Array;

use Override;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\DictionaryValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Reference\ReferenceValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Reference\ReferenceValueArray;
use PrinsFrank\PdfParser\Exception\ParseFailureException;
use PrinsFrank\PdfParser\Exception\PdfParserException;
use PrinsFrank\PdfParser\Exception\RuntimeException;

/** @api */
readonly class ArrayValue implements DictionaryValue {
    /** @param list<int|string|ArrayValue|ReferenceValueArray|null> $value */
    public function __construct(
        public array $value,
    ) {}

    #[Override]
    /** @throws PdfParserException */
    public static function fromValue(string $valueString): self|ReferenceValueArray|null {
        $sanitizedValueString = trim($valueString);
        if (!str_starts_with($sanitizedValueString, '[') || !str_ends_with($sanitizedValueString, ']')) {
            return null;
        }

        $sanitizedValueString = substr($sanitizedValueString, 1, -1);
        $sanitizedValueString = preg_replace('/(<[^>]*>)(?=<[^>]*>)/', '$1 $2', $sanitizedValueString)
            ?? throw new RuntimeException('An error occurred while sanitizing array value');
        $sanitizedValueString = str_replace(['/', "\n"], [' /', ' '], $sanitizedValueString);
        $sanitizedValueString = preg_replace('/\s+/', ' ', $sanitizedValueString)
            ?? throw new RuntimeException('An error occurred while removing duplicate spaces from array value');

        $values = [];
        $buffer = null;
        $strlen = strlen($sanitizedValueString);
        for ($i = 0; $i < $strlen; $i++) {
            $char = $sanitizedValueString[$i];
            if ($char === '[') {
                if ($buffer !== null) {
                    $values[] = $buffer;
                    $buffer = null;
                }

                $nestingLevel = 0;
                for ($j = $i + 1; $j < $strlen; $j++) {
                    $char = $sanitizedValueString[$j];
                    if ($char === '[') {
                        $nestingLevel++;
                        continue;
                    }

                    if ($char === ']') {
                        if ($nestingLevel !== 0) {
                            $nestingLevel--;
                            continue;
                        }

                        $values[] = substr($sanitizedValueString, $i, $j - $i + 1);
                        $i = $j;
                        continue 2;
                    }
                }
            }

            if ($char === ' ') {
                if ($buffer !== null) {
                    $values[] = $buffer;
                    $buffer = null;
                }

                continue;
            }

            $buffer .= $char;
        }
        if ($buffer !== null) {
            $values[] = $buffer;
        }

        if (count($values) % 3 === 0
            && array_key_exists(2, $values)
            && $values[2] === 'R'
            && ($referenceValueArray = ReferenceValueArray::fromValue($valueString)) !== null) {
            return $referenceValueArray;
        }

        $array = [];
        foreach ($values as $value) {
            if (str_starts_with($value, '[') && str_ends_with($value, ']')) {
                $array[] = self::fromValue($value);
            } elseif ((string) (int) $value === $value) {
                $array[] = (int) $value;
            } elseif ($value !== '') {
                $array[] = $value;
            }
        }

        return new self($array);
    }

    public function toString(): string {
        $string = '';
        foreach ($this->value as $value) {
            $string .= ' ' . match (true) {
                is_int($value),
                is_float($value),
                is_string($value) => $value,
                $value instanceof ArrayValue => $value->toString(),
                $value instanceof ReferenceValueArray => implode(' ', array_map(fn(ReferenceValue $referenceValue) => $referenceValue->objectNumber . ' R', $value->referenceValues)),
                default => throw new ParseFailureException('Unsupported array value type: ' . gettype($value)),
            };
        }

        return '[' . trim($string) . ']';
    }
}
