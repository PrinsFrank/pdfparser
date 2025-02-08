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

    public function getText(): string {
        if (str_starts_with($this->textStringValue, '(') && str_ends_with($this->textStringValue, ')')) {
            return substr($this->textStringValue, 1, -1);
        }

        throw new ParseFailureException(sprintf('Unrecognized format %s', $this->textStringValue));
    }

    #[Override]
    public static function fromValue(string $valueString): self {
        return new self($valueString);
    }
}
