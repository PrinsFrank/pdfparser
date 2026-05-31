<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey;

use Override;
use PrinsFrank\PdfParser\Document\Dictionary\Dictionary;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\DictionaryValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Reference\ReferenceValue;
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
        return [ReferenceValue::class, TextStringValue::class, Dictionary::class];
    }

    #[Override]
    public static function fromValue(string $valueString): ?self {
        if (str_starts_with($valueString, '/') === false) {
            return null;
        }

        return self::fromKeyString($valueString);
    }
}
