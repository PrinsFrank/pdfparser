<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryEntry;

use BackedEnum;
use PrinsFrank\PdfParser\Document\Dictionary\Dictionary;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryFactory;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Array\ArrayValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\DictionaryValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\NameValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Reference\ReferenceValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Reference\ReferenceValueArray;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString\NameObjectStringValue;
use PrinsFrank\PdfParser\Document\Dictionary\Normalization\NameValueNormalizer;
use PrinsFrank\PdfParser\Document\Encryption\RC4;
use PrinsFrank\PdfParser\Document\Security\EncryptionContext;
use PrinsFrank\PdfParser\Exception\ParseFailureException;
use PrinsFrank\PdfParser\Exception\PdfParserException;

/** @internal */
class DictionaryEntryFactory {
    /**
     * @param string|array<string, mixed> $dictionaryValue
     * @throws PdfParserException
     */
    public static function fromKeyValuePair(?EncryptionContext $encryptionContext, string $keyString, string|array $dictionaryValue): ?DictionaryEntry {
        $dictionaryKey = DictionaryKey::tryFromKeyString($keyString)
            ?? NameObjectStringValue::fromValue($keyString)
            ?? throw new ParseFailureException();

        return new DictionaryEntry($dictionaryKey, self::getValue($encryptionContext, $dictionaryKey, $dictionaryValue));
    }

    /**
     * @param string|array<string, mixed> $value
     * @throws PdfParserException
     */
    protected static function getValue(?EncryptionContext $encryptionContext, DictionaryKey|NameObjectStringValue $dictionaryKey, string|array $value): Dictionary|DictionaryValue|NameValue {
        if ($encryptionContext !== null && is_string($value)) {
            if (str_starts_with($value, '<') && str_ends_with($value, '>') && ($binaryValue = hex2bin(substr($value, 1, -1))) !== false) {
                $value = '<' . bin2hex(RC4::crypt($encryptionContext->getObjectEncryptionKey(), $binaryValue)) . '>';
            } elseif (str_starts_with($value, '(') && str_ends_with($value, ')')) {
                $value = '(' . RC4::crypt($encryptionContext->getObjectEncryptionKey(), str_replace(['\\\\', '\n', '\r', '\t', '\b', '\f', '\(', '\)'], ['\\', "\n", "\r", "\t", "\x08", "\f", '(', ')'], substr($value, 1, -1))) . ')';
            }
        }

        $allowedValueTypes = $dictionaryKey->getValueTypes();
        if ((in_array(Dictionary::class, $allowedValueTypes, true) || in_array(ArrayValue::class, $allowedValueTypes, true))
            && is_array($value)) {
            return DictionaryFactory::fromArray($encryptionContext, $value);
        }

        if (is_string($value)
            && preg_match('/^[0-9]+ [0-9]+ R$/', $value) === 1
            && ($referenceValue = ReferenceValue::fromValue($value)) !== null) {
            return $referenceValue;
        }

        foreach ($allowedValueTypes as $allowedValueType) {
            if (is_a($allowedValueType, BackedEnum::class, true)
                && is_string($value)
                && ($resolvedValue = $allowedValueType::tryFrom(NameValueNormalizer::normalize($value))) !== null) {
                return $resolvedValue;
            }
        }

        foreach ($allowedValueTypes as $allowedValueType) {
            if (!is_a($allowedValueType, DictionaryValue::class, true)) {
                continue;
            }

            if (!is_string($value) || ($valueObject = $allowedValueType::fromValue($value)) === null) {
                continue;
            }

            return $valueObject;
        }

        if (is_string($value) && ($referenceValueArray = ReferenceValueArray::fromValue($value)) !== null) {
            return $referenceValueArray;
        }

        if (in_array(NameObjectStringValue::class, $allowedValueTypes, true) && is_string($value) && ($nameObjectStringValue = NameObjectStringValue::fromValue($value)) !== null) {
            return $nameObjectStringValue;
        }

        throw new ParseFailureException(sprintf('Value "%s" for dictionary key %s could not be parsed to a valid value type', is_array($value) ? 'array()' : $value, $dictionaryKey->value));
    }
}
