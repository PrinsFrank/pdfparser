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
    /** @var list<Dictionary> */
    public readonly array $dictionaries;

    /** @no-named-arguments */
    public function __construct(
        Dictionary... $dictionaries,
    ) {
        $this->dictionaries = $dictionaries;
    }

    #[Override]
    /** @throws PdfParserException */
    public static function fromValue(string $valueString): ?self {
        $valueStringWithoutSpaces = str_replace([' ', "\r", "\n"], '', $valueString);
        if (!str_starts_with($valueStringWithoutSpaces, '[<<') || !str_ends_with($valueStringWithoutSpaces, '>>]')) {
            return null;
        }

        $dictionaryEntries = [];
        $valueString = preg_replace('/(<<[^>]*>>)(?=<<[^>]*>>)/', '$1 $2', $valueString)
            ?? throw new RuntimeException('An error occurred while sanitizing dictionary array value');
        foreach (explode('>> <<', substr($valueString, 3, -3)) as $dictionaryValueString) {
            $dictionaryEntries[] = $dictionaryValueString === ''
                ? new Dictionary()
                : DictionaryParser::parse($memoryStream = new InMemoryStream('<<' . $dictionaryValueString . '>>'), 0, $memoryStream->getSizeInBytes());
        }

        return new self(... $dictionaryEntries);
    }

    public function toSingleDictionary(): ?Dictionary {
        $dictionaryEntries = [];
        foreach ($this->dictionaries as $dictionary) {
            foreach ($dictionary->dictionaryEntries as $dictionaryEntry) {
                $dictionaryEntries[] = $dictionaryEntry;
            }
        }

        return new Dictionary(... $dictionaryEntries);
    }
}
