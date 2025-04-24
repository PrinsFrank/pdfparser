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
    public static function fromValue(string $valueString): ?self {
        $valueStringWithoutSpaces = str_replace(' ', '', $valueString);
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

        return new self($dictionaryEntries);
    }
}
