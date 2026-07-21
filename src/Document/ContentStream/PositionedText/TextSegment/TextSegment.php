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
        if ($differences === null) {
            return $this->decode($binaryString, $encoding, $toUnicodeCMap);
        }

        $text = '';
        for ($index = 0, $length = strlen($binaryString); $index < $length; $index++) {
            $glyph = $differences->getGlyph(ord($binaryString[$index]));
            $text .= $glyph !== null
                ? $glyph
                : $this->decode($binaryString[$index], $encoding, $toUnicodeCMap);
        }

        return $text;
    }

    private function decode(string $binaryString, ?EncodingNameValue $encoding, ?ToUnicodeCMap $toUnicodeCMap): string {
        if (in_array($encoding, [EncodingNameValue::MacExpertEncoding, EncodingNameValue::WinAnsiEncoding], true)) {
            return $encoding->decodeString($binaryString);
        }

        if ($toUnicodeCMap !== null) {
            return $toUnicodeCMap->textToUnicode(bin2hex($binaryString));
        }

        if ($encoding !== null) {
            return $encoding->decodeString($binaryString);
        }

        return $binaryString;
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
