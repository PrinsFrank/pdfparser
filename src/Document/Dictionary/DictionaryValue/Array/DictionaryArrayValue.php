<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Array;

use Override;
use PrinsFrank\PdfParser\Document\Dictionary\Dictionary;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryParser;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\DictionaryValue;
use PrinsFrank\PdfParser\Exception\PdfParserException;
use PrinsFrank\PdfParser\Exception\RuntimeException;
use PrinsFrank\PdfParser\Stream\InMemoryStream;

class DictionaryArrayValue implements DictionaryValue {
    /** @param list<Dictionary> $dictionaries */
    public function __construct(
        public readonly array $dictionaries,
    ) {
    }

    #[Override]
    /** @throws PdfParserException */
    public static function fromValue(string $valueString): null|self {
        if (!str_starts_with($valueString, '[') || !str_ends_with($valueString, ']')) {
            return null;
        }

        $valueString = preg_replace('/(<[^>]*>)(?=<[^>]*>)/', '$1 $2', $valueString)
            ?? throw new RuntimeException('An error occurred while sanitizing array value');
        $values = explode(' ', rtrim(ltrim($valueString, '[ '), ' ]'));

        $dictionaries = [];
        foreach ($values as $value) {
            try {
                $dictionaries[] = DictionaryParser::parse(($subDictionary = new InMemoryStream($value)), 0, $subDictionary->getSizeInBytes());
            } catch (PdfParserException) {
                return null;
            }
        }

        return new self($dictionaries);
    }
}
