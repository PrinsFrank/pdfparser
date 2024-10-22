<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryEntry;

use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\DictionaryValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\DictionaryValueType\Array\ArrayValue;
use PrinsFrank\PdfParser\Document\Errors\ErrorCollection;
use PrinsFrank\PdfParser\Exception\ParseFailureException;

class DictionaryEntryFactory {
    /** @throws ParseFailureException */
    public static function fromKeyValuePair(string $keyString, array|string $dictionaryValue, ErrorCollection $errorCollection): ?DictionaryEntry {
        $dictionaryKey = DictionaryKey::tryFromKeyString(trim($keyString));
        if ($dictionaryKey === null) {
            $errorCollection->addError('DictionaryKey "' . $keyString . '" not supported');

            return null;
        }

        $dictionaryEntry = (new DictionaryEntry())->setKey($dictionaryKey);
        if (is_array($dictionaryValue)) {
            $arrayValues = [];
            foreach ($dictionaryValue as $dictionaryItemKey => $dictionaryItemValue) {
                $arrayValues[] = self::fromKeyValuePair($dictionaryItemKey, $dictionaryItemValue, $errorCollection);
            }
            $value = new ArrayValue($arrayValues);
        } else {
            $value = DictionaryValue::fromValueString($dictionaryKey, $dictionaryValue);
        }

        return $dictionaryEntry->setValue($value);
    }
}
