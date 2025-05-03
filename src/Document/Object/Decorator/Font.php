<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Object\Decorator;

use PrinsFrank\PdfParser\Document\CMap\Registry\RegistryOrchestrator;
use PrinsFrank\PdfParser\Document\CMap\ToUnicode\ToUnicodeCMap;
use PrinsFrank\PdfParser\Document\CMap\ToUnicode\ToUnicodeCMapParser;
use PrinsFrank\PdfParser\Document\Dictionary\Dictionary;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Array\ArrayValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Array\CIDFontWidths;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Integer\IntegerValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\EncodingNameValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\SubtypeNameValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Reference\ReferenceValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Reference\ReferenceValueArray;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString\TextStringValue;
use PrinsFrank\PdfParser\Document\Font\FontWidths;
use PrinsFrank\PdfParser\Document\Object\Item\UncompressedObject\UncompressedObject;
use PrinsFrank\PdfParser\Exception\InvalidArgumentException;
use PrinsFrank\PdfParser\Exception\ParseFailureException;
use PrinsFrank\PdfParser\Exception\PdfParserException;
use PrinsFrank\PdfParser\Stream\InMemoryStream;

class Font extends DecoratedObject {
    private readonly ToUnicodeCMap|false $toUnicodeCMap;

    /** @throws PdfParserException */
    public function getBaseFont(): ?string {
        return $this->getDictionary()
            ->getValueForKey(DictionaryKey::BASE_FONT, TextStringValue::class)
            ?->textStringValue;
    }

    /** @throws PdfParserException */
    public function getEncoding(): ?EncodingNameValue {
        $encodingType = $this->getDictionary()->getTypeForKey(DictionaryKey::ENCODING);
        if ($encodingType === null || $encodingType === Dictionary::class) {
            return null;
        }

        if ($encodingType === EncodingNameValue::class) {
            return $this->getDictionary()->getValueForKey(DictionaryKey::ENCODING, EncodingNameValue::class);
        }

        if ($encodingType === ReferenceValue::class) {
            return ($this->getDictionary()->getObjectForReference($this->document, DictionaryKey::ENCODING) ?? throw new ParseFailureException('Unable to locate object for encoding dictionary'))
                ->getDictionary()->getValueForKey(DictionaryKey::BASE_ENCODING, EncodingNameValue::class);
        }

        throw new ParseFailureException(sprintf('Unrecognized encoding type %s', $encodingType));
    }

    /** @throws PdfParserException */
    public function getToUnicodeCMap(): ?ToUnicodeCMap {
        if (isset($this->toUnicodeCMap)) {
            if ($this->toUnicodeCMap === false) {
                return null;
            }

            return $this->toUnicodeCMap;
        }

        $toUnicodeObject = $this->getDictionary()
            ->getObjectForReference($this->document, DictionaryKey::TO_UNICODE);
        if ($toUnicodeObject === null) {
            $this->toUnicodeCMap = false;

            return null;
        }

        if ($toUnicodeObject->objectItem instanceof UncompressedObject === false) {
            throw new ParseFailureException();
        }

        $stream = new InMemoryStream($toUnicodeObject->objectItem->getContent($this->document));
        return $this->toUnicodeCMap = ToUnicodeCMapParser::parse($stream, 0, $stream->getSizeInBytes());
    }

    /** @throws PdfParserException */
    public function getFirstChar(): ?int {
        return $this->getDictionary()
            ->getValueForKey(DictionaryKey::FIRST_CHAR, IntegerValue::class)
            ?->value;
    }

    /** @throws PdfParserException */
    public function getLastChar(): ?int {
        return $this->getDictionary()
            ->getValueForKey(DictionaryKey::LAST_CHAR, IntegerValue::class)
            ?->value;
    }

    public function getWidthForChar(int $characterCode, float $fontSize, float $horizontalTextScaling, float $characterSpacing): ?float {
        $fontWidths = $this->getWidths();
        if ($fontWidths !== null && ($charWidth = $fontWidths->getWidthForCharacter($characterCode)) !== null) {
            $characterWidth = $charWidth;
        } else {
            $characterWidth = $this->getDefaultWidth();
        }

        return ($characterWidth * $fontSize + $characterSpacing) * $horizontalTextScaling;
    }

    /** @param list<int> $chars */
    public function getWidthForChars(array $chars, float $fontSize, float $horizontalTextScaling, float $characterSpacing): ?float {
        $totalCharacterWidth = 0;
        foreach ($chars as $char) {
            $totalCharacterWidth += $this->getWidthForChar($char, $fontSize, $horizontalTextScaling, $characterSpacing);
        }

        return $totalCharacterWidth;
    }

    /** @return list<Font> */
    public function getDescendantFonts(): array {
        $descendantFonts = [];
        foreach ($this->getDictionary()->getValueForKey(DictionaryKey::DESCENDANT_FONTS, ReferenceValueArray::class)->referenceValues ?? [] as $referenceValue) {
            $descendantFonts[] = $this->document->getObject($referenceValue->objectNumber);
        }

        return $descendantFonts;
    }

    public function isCIDFont(): bool {
        return in_array(
            $this->getDictionary()->getValueForKey(DictionaryKey::SUBTYPE, SubtypeNameValue::class),
            [SubtypeNameValue::CID_FONT_TYPE_0, SubtypeNameValue::CID_FONT_TYPE_2, SubtypeNameValue::CID_FONT_TYPE_0_C],
            true,
        );
    }

    public function getDefaultWidth(): float {
        if ($this->isCIDFont()) {
            return ($this->getDictionary()->getValueForKey(DictionaryKey::DW, IntegerValue::class)->value
                ?? 1000) / 1000;
        }

        foreach ($this->getDescendantFonts() as $descendantFont) {
            if (($descendantFontDefaultWidth = $descendantFont->getDefaultWidth()) !== null) {
                return $descendantFontDefaultWidth;
            }
        }

        throw new ParseFailureException('Default width not available for non-CID font');
    }

    /** @throws PdfParserException */
    public function getWidths(): CIDFontWidths|FontWidths|null {
        if ($this->isCIDFont()) {
            return $this->getDictionary()->getValueForKey(DictionaryKey::W, CIDFontWidths::class);
        }

        foreach ($this->getDescendantFonts() as $descendantFont) {
            if (($widthsDescendantFont = $descendantFont->getWidths()) !== null) {
                return $widthsDescendantFont;
            }
        }

        $widthsArray = $this->getDictionary()->getValueForKey(DictionaryKey::WIDTHS, ArrayValue::class)?->value;
        if ($widthsArray === null || ($firstChar = $this->getFirstChar()) === null) {
            return null;
        }

        return new FontWidths(
            $firstChar,
            array_map(
                fn (string $width) => (string)($widthAsFloat = (float) $width) === $width ? $widthAsFloat : throw new InvalidArgumentException(),
                $widthsArray,
            ),
        );
    }

    /** @throws PdfParserException */
    public function getFontDescriptor(): ?ReferenceValue {
        return $this->getDictionary()
            ->getValueForKey(DictionaryKey::FONT_DESCRIPTOR, ReferenceValue::class);
    }

    /** @throws PdfParserException */
    public function toUnicode(string $characterGroup): string {
        $toUnicodeCMap = $this->getToUnicodeCMap();
        if ($toUnicodeCMap !== null) {
            return $toUnicodeCMap->textToUnicode($characterGroup);
        }

        $descendantFonts = $this->getDictionary()->getObjectsForReference($this->document, DictionaryKey::DESCENDANT_FONTS, Font::class);
        foreach ($descendantFonts as $descendantFont) {
            if (($CIDSystemInfo = $descendantFont->getDictionary()->getValueForKey(DictionaryKey::CIDSYSTEM_INFO, Dictionary::class)) !== null) {
                $fontResource = RegistryOrchestrator::getForRegistryOrderingSupplement(
                    $CIDSystemInfo->getValueForKey(DictionaryKey::REGISTRY, TextStringValue::class) ?? throw new ParseFailureException(),
                    $CIDSystemInfo->getValueForKey(DictionaryKey::ORDERING, TextStringValue::class) ?? throw new ParseFailureException(),
                    $CIDSystemInfo->getValueForKey(DictionaryKey::SUPPLEMENT, IntegerValue::class) ?? throw new ParseFailureException(),
                );

                if ($fontResource !== null) {
                    return $fontResource->getToUnicodeCMap()->textToUnicode($characterGroup);
                }
            }
        }

        if (($encoding = $this->getEncoding()) !== null) {
            return $encoding->decodeString(implode('', array_map(fn (string $character) => mb_chr((int) hexdec($character)), str_split($characterGroup, 2))));
        }

        throw new ParseFailureException('No ToUnicodeCMap or encoding information available for this font');
    }
}
