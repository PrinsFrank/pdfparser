<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TextSegment;

use PrinsFrank\PdfParser\Document\CMap\ToUnicode\ToUnicodeCMap;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Array\DifferencesArrayValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\EncodingNameValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString\HexadecimalStringValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString\LiteralStringValue;
use PrinsFrank\PdfParser\Exception\ParseFailureException;

readonly class TextSegment {
    public function __construct(
        public HexadecimalStringValue|LiteralStringValue $textString,
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
        if ($this->textString instanceof LiteralStringValue) {
            $codePoints = [];
            foreach (str_split($this->textString->getBinaryString()) as $char) {
                $codePoints[] = ord($char);
            }
            return $codePoints;
        }

        $codePoints = [];
        foreach (str_split($this->textString->value, 4) as $char) {
            $codePoints[] = is_int($codePoint = hexdec($char)) ? $codePoint : throw new ParseFailureException();
        }
        return $codePoints;
    }
}
