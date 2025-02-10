<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary;

use PrinsFrank\PdfParser\Document\Dictionary\DictionaryEntry\DictionaryEntryFactory;
use PrinsFrank\PdfParser\Exception\InvalidArgumentException;
use PrinsFrank\PdfParser\Exception\PdfParserException;

/** @internal */
class DictionaryFactory {
    /**
     * @param array<string, mixed> $dictionaryArray
     * @throws PdfParserException
     */
    public static function fromArray(array $dictionaryArray): Dictionary {
        $dictionaryEntries = [];
        foreach ($dictionaryArray as $keyString => $value) {
            if (!is_string($value) && (!is_array($value) || array_is_list($value))) {
                throw new InvalidArgumentException(sprintf('values should be either strings or non-list array, %s given', gettype($value)));
            }

            /** @var array<string, mixed>|string $value */
            $dictionaryEntry = DictionaryEntryFactory::fromKeyValuePair($keyString, $value);
            if ($dictionaryEntry === null) {
                continue;
            }

            $dictionaryEntries[] = $dictionaryEntry;
        }

        return new Dictionary(... $dictionaryEntries);
    }
}
