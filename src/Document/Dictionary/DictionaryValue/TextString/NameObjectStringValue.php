<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString;

use Override;
use PrinsFrank\PdfParser\Document\Dictionary\Dictionary;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKeyInterface;
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
use PrinsFrank\PdfParser\Exception\ParseFailureException;

readonly class NameObjectStringValue implements DictionaryKeyInterface, DictionaryValue {
    public function __construct(
        public string $value,
    ) {}

    public function getText(): string {
        return preg_replace_callback(
            '/#([0-9A-F]{2})/',
            fn(array $matches) => chr((int) hexdec($matches[1])),
            $this->value,
        ) ?? throw new ParseFailureException();
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
            HexadecimalStringValue::class,
            LiteralStringValue::class,
            NameObjectStringValue::class,
        ];
    }

    #[Override]
    public static function fromValue(string $valueString): ?self {
        $valueString = trim($valueString);
        if (str_starts_with($valueString, '/') === false) {
            return null;
        }

        return new self(substr($valueString, 1));
    }
}
