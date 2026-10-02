<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Array;

use Override;
use PrinsFrank\PdfParser\Document\Dictionary\Dictionary;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryParser;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\DictionaryValue;
use PrinsFrank\PdfParser\Exception\PdfParserException;
use PrinsFrank\PdfParser\Exception\RuntimeException;
use PrinsFrank\PdfParser\Stream\InMemoryStream;

readonly class DictionaryArrayValue implements DictionaryValue {
    /** @var list<Dictionary> */
    public array $dictionaries;

    /** @no-named-arguments */
    public function __construct(
        Dictionary... $dictionaries,
    ) {
        $this->dictionaries = $dictionaries;
    }

    #[Override]
    /** @throws PdfParserException */
    public static function fromValue(string $valueString): ?self {
        $valueString = trim($valueString);
        if (str_starts_with($valueString, '[') === false || str_ends_with($valueString, ']') === false) {
            return null;
        }

        $valueString = trim(substr($valueString, 1, -1));
        if ($valueString === '') {
            return null;
        }

        if ((str_starts_with($valueString, '<<') === false && str_starts_with($valueString, 'null') === false)
           || (str_ends_with($valueString, '>>') === false && str_ends_with($valueString, 'null') === false)) {
            return null;
        }

        if (preg_match_all('/null|<<(?>[^<>]++|<(?!<)|>(?!>)|(?R))*>>/', $valueString, $matches) === false) {
            throw new RuntimeException('An error occurred while parsing dictionary array');
        }

        $dictionaryEntries = [];
        foreach ($matches[0] as $match) {
            if ($match === 'null') {
                continue;
            }

            $dictionaryEntries[] = DictionaryParser::parse(null, $memoryStream = new InMemoryStream($match), 0, $memoryStream->getSizeInBytes());
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
