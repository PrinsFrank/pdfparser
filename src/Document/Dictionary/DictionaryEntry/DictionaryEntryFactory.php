<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryEntry;

use BackedEnum;
use PrinsFrank\PdfParser\Document\Dictionary\Dictionary;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryFactory;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\ExtendedDictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Array\ArrayValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\DictionaryValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\NameValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Reference\ReferenceValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString\TextStringValue;
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
            ?? ExtendedDictionaryKey::fromKeyString($keyString);

        return new DictionaryEntry($dictionaryKey, self::getValue($encryptionContext, $dictionaryKey, $dictionaryValue));
    }

    /**
     * @param string|array<string, mixed> $value
     * @throws PdfParserException
     */
    protected static function getValue(?EncryptionContext $encryptionContext, DictionaryKey|ExtendedDictionaryKey $dictionaryKey, string|array $value): Dictionary|DictionaryValue|NameValue {
        if ($encryptionContext !== null && is_string($value)) {
            if (str_starts_with($value, '<') && str_ends_with($value, '>') && ($binaryValue = hex2bin(substr($value, 1, -1))) !== false) {
                $value = '<' . bin2hex(RC4::crypt($encryptionContext->getObjectEncryptionKey(), $binaryValue)) . '>';
            } elseif (str_starts_with($value, '(') && str_ends_with($value, ')')) {
                $value = '(' . RC4::crypt($encryptionContext->getObjectEncryptionKey(), substr($value, 1, -1)) . ')';
            }
        }

        $allowedValueTypes = $dictionaryKey->getValueTypes();
        if ((in_array(Dictionary::class, $allowedValueTypes, true) || in_array(ArrayValue::class, $allowedValueTypes, true))
            && is_array($value)) {
            return DictionaryFactory::fromArray($encryptionContext, $value);
        }

        if ((in_array(Dictionary::class, $allowedValueTypes, true) || in_array(ArrayValue::class, $allowedValueTypes, true))
            && is_string($value)
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
            if (!is_a($allowedValueType, DictionaryValue::class, true)
                || $allowedValueType === TextStringValue::class) { // TextStrings accept everything, so we check that last
                continue;
            }

            if (!is_string($value) || ($valueObject = $allowedValueType::fromValue($value)) === null) {
                continue;
            }

            return $valueObject;
        }

        if (in_array(TextStringValue::class, $allowedValueTypes, true) && is_string($value)) {
            return TextStringValue::fromValue($value);
        }

        throw new ParseFailureException(sprintf('Value "%s" for dictionary key %s could not be parsed to a valid value type', is_array($value) ? 'array()' : $value, $dictionaryKey->value));
    }
}
