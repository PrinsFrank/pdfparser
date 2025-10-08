<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Object\Decorator;

use PrinsFrank\PdfParser\Document\CMap\Registry\RegistryOrchestrator;
use PrinsFrank\PdfParser\Document\CMap\ToUnicode\ToUnicodeCMap;
use PrinsFrank\PdfParser\Document\CMap\ToUnicode\ToUnicodeCMapParser;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TextState;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TransformationMatrix;
use PrinsFrank\PdfParser\Document\Dictionary\Dictionary;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Array\ArrayValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Array\CIDFontWidths;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Array\CrossReferenceStreamByteSizes;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Array\DictionaryArrayValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Array\DifferencesArrayValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Array\Item\RangeCIDWidth;
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

class Font extends DecoratedObject {
    private readonly ToUnicodeCMap|false $toUnicodeCMap;

    /** @throws PdfParserException */
    public function getBaseFont(): ?string {
        return $this->getDictionary()
            ->getValueForKey(DictionaryKey::BASE_FONT, TextStringValue::class)
            ?->textStringValue;
    }

    public function getEncodingDictionary(): ?Dictionary {
        if (in_array($this->getDictionary()->getTypeForKey(DictionaryKey::ENCODING), [null, EncodingNameValue::class], true)) {
            return null;
        }

        return $this->getDictionary()
            ->getSubDictionary($this->document, DictionaryKey::ENCODING);
    }

    /** @throws PdfParserException */
    public function getEncoding(): ?EncodingNameValue {
        $encodingType = $this->getDictionary()->getTypeForKey(DictionaryKey::ENCODING);
        if ($encodingType === null) {
            return null;
        }

        if ($encodingType === EncodingNameValue::class) {
            return $this->getDictionary()->getValueForKey(DictionaryKey::ENCODING, EncodingNameValue::class);
        }

        return $this->getEncodingDictionary()
            ?->getValueForKey(DictionaryKey::BASE_ENCODING, EncodingNameValue::class);
    }

    public function getDifferences(): ?DifferencesArrayValue {
        return $this->getEncodingDictionary()
            ?->getValueForKey(DictionaryKey::DIFFERENCES, DifferencesArrayValue::class);
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

        $stream = $toUnicodeObject->objectItem->getContent($this->document);
        return $this->toUnicodeCMap = ToUnicodeCMapParser::parse($stream, 0, $stream->getSizeInBytes());
    }

    public function getToUnicodeCMapDescendantFont(): ?ToUnicodeCMap {
        foreach ($this->getDescendantFonts() as $descendantFont) {
            $fontDictionary = $descendantFont instanceof Dictionary ? $descendantFont : $descendantFont->getDictionary();

            if (($CIDSystemInfo = $fontDictionary->getValueForKey(DictionaryKey::CIDSYSTEM_INFO, Dictionary::class)) !== null) {
                $fontResource = RegistryOrchestrator::getForRegistryOrderingSupplement(
                    $CIDSystemInfo->getValueForKey(DictionaryKey::REGISTRY, TextStringValue::class) ?? throw new ParseFailureException(),
                    $CIDSystemInfo->getValueForKey(DictionaryKey::ORDERING, TextStringValue::class) ?? throw new ParseFailureException(),
                    $CIDSystemInfo->getValueForKey(DictionaryKey::SUPPLEMENT, IntegerValue::class) ?? throw new ParseFailureException(),
                );

                if ($fontResource !== null) {
                    return $fontResource->getToUnicodeCMap();
                }
            }
        }

        return null;
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

    public function getWidthForChar(int $characterCode, TextState $textState, TransformationMatrix $transformationMatrix): float {
        $fontWidths = $this->getWidths();
        if ($fontWidths !== null && ($charWidth = $fontWidths->getWidthForCharacter($characterCode)) !== null) {
            $characterWidth = $charWidth;
        } else {
            $characterWidth = $this->getDefaultWidth();
        }

        return ($characterWidth * ($textState->fontSize ?? 10) + $textState->charSpace) * $transformationMatrix->scaleX;
    }

    /** @param list<int> $chars */
    public function getWidthForChars(array $chars, TextState $textState, TransformationMatrix $transformationMatrix): float {
        $totalCharacterWidth = 0;
        foreach ($chars as $char) {
            $totalCharacterWidth += $this->getWidthForChar($char, $textState, $transformationMatrix);
        }

        return $totalCharacterWidth;
    }

    /** @return list<Font|Dictionary> */
    public function getDescendantFonts(): array {
        $valueType = $this->getDictionary()->getTypeForKey(DictionaryKey::DESCENDANT_FONTS);
        if ($valueType === null) {
            return [];
        }

        if ($valueType === ReferenceValue::class) {
            $descendantFontsReference = $this->getDictionary()->getValueForKey(DictionaryKey::DESCENDANT_FONTS, ReferenceValue::class) ?? throw new ParseFailureException();
            return [
                $this->document->getObject($descendantFontsReference->objectNumber, Font::class)
                    ?? throw new ParseFailureException(sprintf('Descendant font with number %d could not be found', $descendantFontsReference->objectNumber)),
            ];
        }

        if ($valueType === DictionaryArrayValue::class) {
            return $this->getDictionary()->getValueForKey(DictionaryKey::DESCENDANT_FONTS, DictionaryArrayValue::class)->dictionaries ?? throw new ParseFailureException();
        }

        $descendantFonts = [];
        foreach ($this->getDictionary()->getValueForKey(DictionaryKey::DESCENDANT_FONTS, ReferenceValueArray::class)->referenceValues ?? [] as $referenceValue) {
            $descendantFonts[] = $this->document->getObject($referenceValue->objectNumber, Font::class)
                ?? throw new ParseFailureException(sprintf('Descendant font with number %d could not be found', $referenceValue->objectNumber));
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
            if ($descendantFont instanceof Dictionary && $descendantFont->getTypeForKey(DictionaryKey::W) === ReferenceValue::class) {
                $descendantFont = $this->document->getObject($descendantFont->getValueForKey(DictionaryKey::W, ReferenceValue::class)->objectNumber ?? throw new ParseFailureException(), Font::class) ?? throw new ParseFailureException();
            }

            if ($descendantFont instanceof Font) {
                return $descendantFont->getDefaultWidth();
            }
        }

        return 1000;
    }

    /** @throws PdfParserException */
    public function getWidths(): CIDFontWidths|FontWidths|null {
        if ($this->isCIDFont()) {
            if ($this->getDictionary()->getTypeForKey(DictionaryKey::W) === CrossReferenceStreamByteSizes::class) {
                $byteSizes = $this->getDictionary()->getValueForKey(DictionaryKey::W, CrossReferenceStreamByteSizes::class) ?? throw new ParseFailureException(); // TODO: fix misinterpretation

                return new CIDFontWidths(new RangeCIDWidth($byteSizes->lengthRecord1InBytes, $byteSizes->lengthRecord2InBytes, $byteSizes->lengthRecord3InBytes));
            }

            return $this->getDictionary()->getValueForKey(DictionaryKey::W, CIDFontWidths::class);
        }

        foreach ($this->getDescendantFonts() as $descendantFont) {
            if ($descendantFont instanceof Dictionary && $descendantFont->getTypeForKey(DictionaryKey::W) === ReferenceValue::class) {
                $descendantFont = $this->document->getObject($descendantFont->getValueForKey(DictionaryKey::W, ReferenceValue::class)->objectNumber ?? throw new ParseFailureException(), Font::class) ?? throw new ParseFailureException();
            }

            if ($descendantFont instanceof Font && ($widthsDescendantFont = $descendantFont->getWidths()) !== null) {
                return $widthsDescendantFont;
            }
        }

        if ($this->getDictionary()->getTypeForKey(DictionaryKey::WIDTHS) === ReferenceValue::class) {
            $object = $this->document->getObject(($widthsReference = $this->getDictionary()->getValueForKey(DictionaryKey::WIDTHS, ReferenceValue::class))->objectNumber ?? throw new ParseFailureException(), Font::class)
                ?? throw new ParseFailureException(sprintf('Width dictionary with number %d could not be found', $widthsReference->objectNumber));
            $arrayValue = ArrayValue::fromValue($object->getStream()->toString());
            if ($arrayValue instanceof ArrayValue === false) {
                throw new ParseFailureException(sprintf('Width dictionary with number %d does not contain a valid array, "%s"', $widthsReference->objectNumber, $object->getStream()->read(0, 100) . '...'));
            }

            $widthsArray = $arrayValue->value;
        } elseif (($widthsArray = $this->getDictionary()->getValueForKey(DictionaryKey::WIDTHS, ArrayValue::class)?->value) === null) {
            return null;
        }

        if (($firstChar = $this->getFirstChar()) === null) {
            return null;
        }

        return new FontWidths(
            $firstChar,
            array_values(
                array_map(
                    fn (mixed $width): float => is_numeric($width) ? (float) $width : throw new InvalidArgumentException(sprintf('"%s" is not a valid width', ($jsonEncoded = json_encode($width)) !== false ? $jsonEncoded : 'value')),
                    array_filter(
                        $widthsArray,
                        fn (mixed $item) => $item !== '',
                    ),
                ),
            ),
        );
    }

    /** @throws PdfParserException */
    public function getFontDescriptor(): ?ReferenceValue {
        return $this->getDictionary()
            ->getValueForKey(DictionaryKey::FONT_DESCRIPTOR, ReferenceValue::class);
    }
}
