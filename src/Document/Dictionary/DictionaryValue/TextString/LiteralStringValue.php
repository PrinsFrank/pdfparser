<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString;

use Override;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\DictionaryValue;
use PrinsFrank\PdfParser\Document\Encoding\PDFDocEncoding;
use PrinsFrank\PdfParser\Exception\ParseFailureException;

readonly class LiteralStringValue implements DictionaryValue {
    public function __construct(
        public string $value,
    ) {}

    /** @throws ParseFailureException */
    public function getText(): string {
        $binaryValue = $this->getBinaryString();
        if (str_starts_with($binaryValue, "\xFE\xFF")) {
            return mb_convert_encoding(substr($binaryValue, 2), 'UTF-8', 'UTF-16BE');
        }

        if (str_starts_with($binaryValue, "\xFF\xFE")) {
            return mb_convert_encoding(substr($binaryValue, 2), 'UTF-8', 'UTF-16LE');
        }

        if (str_starts_with($binaryValue, "\xEF\xBB\xBF")) {
            return substr($binaryValue, 3);
        }

        return PDFDocEncoding::textToUnicode($binaryValue);
    }

    /** @throws ParseFailureException */
    public function getBinaryString(): string {
        $value = preg_replace_callback(
            '/\\\\([0-7]{1,3})/',
            fn(array $matches) => chr((int) octdec($matches[1])),
            $this->value,
        ) ?? throw new ParseFailureException();

        return str_replace(
            ['\\\\', '\n', '\r', '\t', '\b', '\f', '\(', '\)'],
            ['\\', "\n", "\r", "\t", "\x08", "\f", '(', ')'],
            $value,
        );
    }

    #[Override]
    public static function fromValue(string $valueString): ?self {
        $valueString = trim($valueString);
        if (str_starts_with($valueString, '(') === false
            || str_ends_with($valueString, ')') === false) {
            return null;
        }

        return new self(substr($valueString, 1, -1));
    }
}
