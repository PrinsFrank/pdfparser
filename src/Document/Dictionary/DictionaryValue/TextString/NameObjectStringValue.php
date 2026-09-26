<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString;

use Override;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\DictionaryValue;
use PrinsFrank\PdfParser\Exception\ParseFailureException;

readonly class NameObjectStringValue implements DictionaryValue {
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

    #[Override]
    public static function fromValue(string $valueString): ?self {
        $valueString = trim($valueString);
        if (str_starts_with($valueString, '/') === false) {
            return null;
        }

        return new self($valueString);
    }
}
