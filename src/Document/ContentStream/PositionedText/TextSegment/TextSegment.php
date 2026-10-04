<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TextSegment;

use PrinsFrank\PdfParser\Document\CMap\ToUnicode\ToUnicodeCMap;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Array\DifferencesArrayValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\EncodingNameValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString\HexadecimalStringValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString\LiteralStringValue;

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
            return $toUnicodeCMap->textToUnicode($binaryString);
        }

        if ($encoding !== null && !in_array($encoding, [EncodingNameValue::IdentityH, EncodingNameValue::IdentityV], true)) {
            return $encoding->decodeString($binaryString);
        }

        return $binaryString;
    }
}
