<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TextSegment;

use PrinsFrank\PdfParser\Document\CMap\ToUnicode\ToUnicodeCMap;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Array\DifferencesArrayValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\EncodingNameValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString\TextStringValue;
use PrinsFrank\PdfParser\Exception\ParseFailureException;

readonly class TextSegment {
    public function __construct(
        public TextStringValue $textString,
        public int|float|null $offset,
    ) {}

    public function getText(?DifferencesArrayValue $differences, ?EncodingNameValue $encoding, ?ToUnicodeCMap $toUnicodeCMap): string {
        $binaryString = $this->textString->getBinaryString();
        if (strlen($binaryString) === 1 && ($glyph = $differences?->getGlyph(ord($binaryString))) !== null) {
            $text = $glyph->getChar();
        } elseif (in_array($encoding, [EncodingNameValue::MacExpertEncoding, EncodingNameValue::WinAnsiEncoding], true)
            && $differences === null) {
            $text = $encoding->decodeString($binaryString);
        } elseif ($toUnicodeCMap !== null) {
            $text = $toUnicodeCMap->textToUnicode(bin2hex($binaryString));
        } elseif ($encoding !== null) {
            $text = $encoding->decodeString($binaryString);
        } else {
            $text = $binaryString;
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
