<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TextSegment;

use PrinsFrank\PdfParser\Document\CMap\ToUnicode\ToUnicodeCMap;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TextState;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TransformationMatrix;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Array\DifferencesArrayValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\EncodingNameValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString\TextStringValue;
use PrinsFrank\PdfParser\Document\Object\Decorator\Font;

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

        return $text;
    }

    /** @see 9.4.4 Text space details */
    public function applyDisplacement(TransformationMatrix $textMatrix, Font $font, TextState $textState): TransformationMatrix {
        $binaryString = $this->textString->getBinaryString();
        $charCount = strlen($binaryString);
        $horizontalGlyphDisplacement = 0;
        for($i = 0; $i < $charCount; $i++) {
            $horizontalGlyphDisplacement += $font->getWidthForChar(ord($binaryString[$i]));
        }

        $offsetX = (
            (($horizontalGlyphDisplacement - (($this->offset ?? 0) / 1000)) * $textState->fontSize)
            + ($textState->charSpace * $charCount)
            + ($textState->wordSpace * substr_count($binaryString, ' '))
        ) * $textMatrix->scaleX;

        return (new TransformationMatrix(1, 0, 0, 0, $offsetX, 0))
            ->multiplyWith($textMatrix);
    }
}
