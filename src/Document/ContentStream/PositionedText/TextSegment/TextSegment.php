<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TextSegment;

use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\EncodingNameValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString\TextStringValue;
use PrinsFrank\PdfParser\Document\Object\Decorator\Font;
use PrinsFrank\PdfParser\Exception\ParseFailureException;

readonly class TextSegment {
    public function __construct(
        public TextStringValue $textString,
        public int|float|null $offset,
    ) {}

    public function getText(Font $font): string {
        $text = '';
        if (str_starts_with($this->textString->textStringValue, '(') && str_ends_with($this->textString->textStringValue, ')')) {
            $unescapedChars = $this->textString->getBinaryString();
            if (strlen($unescapedChars) === 1 && ($glyph = $font->getDifferences()?->getGlyph(ord($unescapedChars))) !== null) {
                $chars = $glyph->getChar();
            } elseif (in_array($encoding = $font->getEncoding(), [EncodingNameValue::MacExpertEncoding, EncodingNameValue::WinAnsiEncoding], true) && $font->getDifferences() === null) {
                $chars = $encoding->decodeString($unescapedChars);
            } elseif (($toUnicodeCMap = $font->getToUnicodeCMap() ?? $font->getToUnicodeCMapDescendantFont()) !== null) {
                $chars = $toUnicodeCMap->textToUnicode(bin2hex($unescapedChars));
            } elseif ($encoding !== null) {
                $chars = $encoding->decodeString($unescapedChars);
            } else {
                $chars = $unescapedChars;
            }

            $text .= $chars;
        } elseif (str_starts_with($this->textString->textStringValue, '<') && str_ends_with($this->textString->textStringValue, '>')) {
            $chars = substr($this->textString->textStringValue, 1, -1);
            if (($toUnicodeCMap = $font->getToUnicodeCMap() ?? $font->getToUnicodeCMapDescendantFont()) !== null) {
                $text .= $toUnicodeCMap->textToUnicode($chars);
            } elseif (($encoding = $font->getEncoding()) !== null) {
                $text .= $encoding->decodeString(implode('', array_map(fn(string $character) => mb_chr((int) hexdec($character)), str_split($chars, 2))));
            } else {
                $text .= EncodingNameValue::IdentityH->decodeString($chars);
            }
        } else {
            throw new ParseFailureException(sprintf('Unrecognized character group format "%s"', $this->textString->textStringValue));
        }

        if ($this->offset !== null && $this->offset < -100) {
            $text .= ' ';
        }

        return $text;
    }

    /** @return list<int> */
    public function getCodePoints(): array {
        $codePoints = [];
        if (str_starts_with($this->textString->textStringValue, '(') && str_ends_with($this->textString->textStringValue, ')')) {
            foreach (str_split($this->textString->getBinaryString()) as $char) {
                $codePoints[] = ord($char);
            }
        } elseif (str_starts_with($this->textString->textStringValue, '<') && str_ends_with($this->textString->textStringValue, '>')) {
            foreach (str_split(substr($this->textString->textStringValue, 1, -1), 4) as $char) {
                $codePoints[] = is_int($codePoint = hexdec($char)) ? $codePoint : throw new ParseFailureException();
            }
        } else {
            throw new ParseFailureException(sprintf('Unrecognized character group format "%s"', $this->textString->textStringValue));
        }

        return $codePoints;
    }
}
