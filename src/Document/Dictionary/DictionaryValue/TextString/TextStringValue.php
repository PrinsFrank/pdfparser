<?php
declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString;

use Override;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\DictionaryValue;
use PrinsFrank\PdfParser\Exception\ParseFailureException;

class TextStringValue implements DictionaryValue {
    public function __construct(
        public readonly string $textStringValue
    ) {
    }

    /** @throws ParseFailureException */
    public function getText(): string {
        if (str_starts_with($this->textStringValue, '(') && str_ends_with($this->textStringValue, ')')) {
            return preg_replace_callback(
                '/\\\\([0-7]{3})/',
                fn (array $matches) => mb_chr((int) octdec($matches[1])),
                str_replace(['\(', '\)', '\n', '\r'], ['(', ')', "\n", "\r"], substr($this->textStringValue, 1, -1))
            ) ?? throw new ParseFailureException();
        }

        if (str_starts_with($this->textStringValue, '<') && str_ends_with($this->textStringValue, '>')) {
            $string = substr($this->textStringValue, 1, -1);
            if (str_starts_with($string, 'FEFF')) {
                $string = substr($string, 4);
            }

            return implode(
                '',
                array_map(
                    fn (string $character) => mb_chr((int) hexdec($character)) ?? throw new ParseFailureException(),
                    str_split($string, 4)
                )
            );
        }

        throw new ParseFailureException(sprintf('Unrecognized format %s', $this->textStringValue));
    }

    #[Override]
    public static function fromValue(string $valueString): self {
        return new self($valueString);
    }
}
