<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString;

use Override;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\DictionaryValue;
use PrinsFrank\PdfParser\Exception\ParseFailureException;

/** @api */
readonly class TextStringValue implements DictionaryValue {
    public function __construct(
        public string $textStringValue,
    ) {}

    /** @throws ParseFailureException */
    public function getText(): string {
        if (str_starts_with($this->textStringValue, '(') && str_ends_with($this->textStringValue, ')')) {
            $value = preg_replace_callback(
                '/\\\\([0-7]{1,3})/',
                fn(array $matches) => mb_chr((int) octdec($matches[1])),
                substr($this->textStringValue, 1, -1),
            ) ?? throw new ParseFailureException();

            return str_replace(
                ['\\\\', '\n', '\r', '\t', '\b', '\f', '\(', '\)'],
                ['\\', "\n", "\r", "\t", "\x08", "\f", '(', ')'],
                $value,
            );
        }

        if (str_starts_with($this->textStringValue, '<') && str_ends_with($this->textStringValue, '>')) {
            $binaryValue = hex2bin(substr($this->textStringValue, 1, -1));
            if ($binaryValue === false) {
                throw new ParseFailureException('Invalid hex string');
            }

            if (str_starts_with($binaryValue, "\xFE\xFF")) {
                return mb_convert_encoding(substr($binaryValue, 2), 'UTF-8', 'UTF-16BE');
            }

            return $binaryValue;
        }

        if (str_starts_with($this->textStringValue, '/')) {
            return preg_replace_callback(
                '/#([0-9A-F]{2})/',
                fn(array $matches) => chr((int) hexdec($matches[1])),
                $this->textStringValue,
            ) ?? throw new ParseFailureException();
        }

        throw new ParseFailureException(sprintf('Unrecognized format %s', $this->textStringValue));
    }

    public function getBinaryString(): string {
        if (str_starts_with($this->textStringValue, '(') && str_ends_with($this->textStringValue, ')')) {
            $value = preg_replace_callback(
                '/\\\\([0-7]{1,3})/',
                fn(array $matches) => chr((int) octdec($matches[1])),
                substr($this->textStringValue, 1, -1),
            ) ?? throw new ParseFailureException();

            return str_replace(
                ['\\\\', '\n', '\r', '\t', '\b', '\f', '\(', '\)'],
                ['\\', "\n", "\r", "\t", "\x08", "\f", '(', ')'],
                $value,
            );
        }

        if (str_starts_with($this->textStringValue, '<') && str_ends_with($this->textStringValue, '>')) {
            $string = substr($this->textStringValue, 1, -1);
            $binaryValue = hex2bin($string);
            if ($binaryValue === false) {
                throw new ParseFailureException('Invalid hex string');
            }
            return $binaryValue;
        }

        throw new ParseFailureException(sprintf('Unrecognized format %s', $this->textStringValue));
    }

    #[Override]
    public static function fromValue(string $valueString): self {
        return new self($valueString);
    }
}
