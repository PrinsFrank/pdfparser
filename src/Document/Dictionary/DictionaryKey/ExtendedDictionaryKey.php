<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey;

use Override;
use PrinsFrank\PdfParser\Document\Dictionary\Dictionary;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Array\ArrayValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Array\DictionaryArrayValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Boolean\BooleanValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Date\DateValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\DictionaryValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Float\FloatValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Integer\IntegerValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Rectangle\Rectangle;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Reference\ReferenceValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Reference\ReferenceValueArray;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString\TextStringValue;

readonly class ExtendedDictionaryKey implements DictionaryKeyInterface, DictionaryValue {
    public function __construct(
        public string $value,
    ) {}

    /** @internal */
    public static function fromKeyString(string $keyString): self {
        return new self(rtrim(ltrim($keyString, '/'), "\n\t "));
    }

    /** @api */
    #[Override]
    public function getValueTypes(): array {
        return [
            Dictionary::class,
            ArrayValue::class,
            DictionaryArrayValue::class,
            BooleanValue::class,
            DateValue::class,
            FloatValue::class,
            IntegerValue::class,
            Rectangle::class,
            ReferenceValue::class,
            ReferenceValueArray::class,
            TextStringValue::class,
        ];
    }

    #[Override]
    public static function fromValue(string $valueString): ?self {
        $valueString = trim($valueString);
        if (str_starts_with($valueString, '/') === false) {
            return null;
        }

        return self::fromKeyString($valueString);
    }
}
