<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\Decorator;

use PrinsFrank\MarkDownDom\Enum\HeadingLevel;
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
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\ToUnicodeCMapNameValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Reference\ReferenceValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Reference\ReferenceValueArray;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString\NameObjectStringValue;
use PrinsFrank\PdfParser\Document\Font\FontWidths;
use PrinsFrank\PdfParser\Document\Object\UncompressedObject\UncompressedObject;
use PrinsFrank\PdfParser\Exception\InvalidArgumentException;
use PrinsFrank\PdfParser\Exception\ParseFailureException;
use PrinsFrank\PdfParser\Exception\PdfParserException;

readonly class Font extends DecoratedDictionary {
    /** @var list<Font> */
    private array $descendantFontsCache;
    private ToUnicodeCMap|false $toUnicodeCMap;
    private CIDFontWidths|FontWidths|false $widths;
    private DifferencesArrayValue|false $differencesCache;
    private EncodingNameValue|false $encodingCache;

    /** @throws PdfParserException */
    public function getBaseFont(): ?string {
        return $this->getStringValue(DictionaryKey::BASE_FONT)
            ?->value;
    }

    public function getEncodingDictionary(): ?Dictionary {
        if (in_array($this->getTypeForKey(DictionaryKey::ENCODING), [null, EncodingNameValue::class, NameObjectStringValue::class], true)) {
            return null;
        }

        return $this->getSubDictionary(DictionaryKey::ENCODING);
    }

    /** @throws PdfParserException */
    public function getEncoding(): ?EncodingNameValue {
        if (isset($this->encodingCache)) {
            return $this->encodingCache === false ? null : $this->encodingCache;
        }

        if ($this->getTypeForKey(DictionaryKey::ENCODING) === EncodingNameValue::class) {
            $encoding = $this->getValueForKey(DictionaryKey::ENCODING, EncodingNameValue::class);
        } else {
            $encoding = $this->getEncodingDictionary()
                ?->getValueForKey(DictionaryKey::BASE_ENCODING, EncodingNameValue::class);
        }

        if ($encoding === null) {
            $this->encodingCache = false;
            return null;
        }

        return $this->encodingCache = $encoding;
    }

    public function getDifferences(): ?DifferencesArrayValue {
        if (isset($this->differencesCache)) {
            return $this->differencesCache === false ? null : $this->differencesCache;
        }

        $differences = $this->getEncodingDictionary()
            ?->getValueForKey(DictionaryKey::DIFFERENCES, DifferencesArrayValue::class);
        if ($differences === null) {
            $this->differencesCache = false;
            return null;
        }

        return $this->differencesCache = $differences;
    }

    /** @throws PdfParserException */
    public function getToUnicodeCMap(): ?ToUnicodeCMap {
        if (isset($this->toUnicodeCMap)) {
            return $this->toUnicodeCMap === false
                ? null
                : $this->toUnicodeCMap;
        }

        if ($this->getTypeForKey(DictionaryKey::TO_UNICODE) === ToUnicodeCMapNameValue::class) {
            $toUnicodeCMapNameValue = $this->getValueForKey(DictionaryKey::TO_UNICODE, ToUnicodeCMapNameValue::class)
                ?? throw new ParseFailureException();

            return $this->toUnicodeCMap = $toUnicodeCMapNameValue->getToUnicodeCMap();
        }

        $toUnicodeObject = $this->getObjectForReference(DictionaryKey::TO_UNICODE);
        if ($toUnicodeObject === null) {
            $descendantUnicodeCMap = $this->getToUnicodeCMapDescendantFont();
            if ($descendantUnicodeCMap !== null) {
                return $this->toUnicodeCMap = $descendantUnicodeCMap;
            }

            $this->toUnicodeCMap = false;
            return null;
        }

        if ($toUnicodeObject instanceof UncompressedObject === false) {
            throw new ParseFailureException();
        }

        $stream = $toUnicodeObject->getContent();
        return $this->toUnicodeCMap = ToUnicodeCMapParser::parse($stream, 0, $stream->getSizeInBytes());
    }

    private function getToUnicodeCMapDescendantFont(): ?ToUnicodeCMap {
        foreach ($this->getDescendantFonts() as $descendantFont) {
            if (($CIDSystemInfo = $descendantFont->getValueForKey(DictionaryKey::CIDSYSTEM_INFO, Dictionary::class)) !== null) {
                $fontResource = RegistryOrchestrator::getForRegistryOrderingSupplement(
                    $CIDSystemInfo->getStringValue(DictionaryKey::REGISTRY) ?? throw new ParseFailureException(),
                    $CIDSystemInfo->getStringValue(DictionaryKey::ORDERING) ?? throw new ParseFailureException(),
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
        return $this->getValueForKey(DictionaryKey::FIRST_CHAR, IntegerValue::class)
            ?->value;
    }

    /** @throws PdfParserException */
    public function getLastChar(): ?int {
        return $this->getValueForKey(DictionaryKey::LAST_CHAR, IntegerValue::class)
            ?->value;
    }

    /** @param list<int> $chars */
    public function getWidthForChars(array $chars, float $wordSpace, float $charSpace, float $fontSize, float $scaleX): float {
        if ($chars === []) {
            return 0.0;
        }

        $defaultWidth = null;
        $totalCharacterWidth = 0.0;
        $charWidthCache = [];
        foreach ($chars as $char) {
            if (isset($charWidthCache[$char]) === false) {
                $characterWidth = $this->getWidths()?->getWidthForCharacter($char) ?? ($defaultWidth ??= $this->getDefaultWidth());

                // Word spacing (Tw) applies only to the single-byte character code 32, and never to composite (Type0) fonts (spec §9.3.3).
                $wordSpaceForChar = ($char === 32 && $this->getDescendantFonts() === []) ? $wordSpace : 0.0;

                $charWidthCache[$char] = ($characterWidth * $fontSize + $charSpace + $wordSpaceForChar) * $scaleX;
            }

            $totalCharacterWidth += $charWidthCache[$char];
        }

        return $totalCharacterWidth;
    }

    /** @return list<Font> */
    public function getDescendantFonts(): array {
        if (isset($this->descendantFontsCache)) {
            return $this->descendantFontsCache;
        }

        $valueType = $this->getTypeForKey(DictionaryKey::DESCENDANT_FONTS);
        if ($valueType === null) {
            return $this->descendantFontsCache = [];
        }

        if ($valueType === ReferenceValue::class) {
            $descendantFontsReference = $this->getValueForKey(DictionaryKey::DESCENDANT_FONTS, ReferenceValue::class) ?? throw new ParseFailureException();
            return [
                ($this->dictionary->object?->document->getObject($descendantFontsReference->objectNumber) ?? throw new ParseFailureException(sprintf('Descendant font with number %d could not be found', $descendantFontsReference->objectNumber)))
                    ->getDictionary(Font::class),
            ];
        }

        if ($valueType === DictionaryArrayValue::class) {
            return $this->descendantFontsCache = array_map(
                fn(Dictionary $dictionary) => new self($dictionary),
                $this->getValueForKey(DictionaryKey::DESCENDANT_FONTS, DictionaryArrayValue::class)->dictionaries ?? throw new ParseFailureException(),
            );
        }

        $descendantFonts = [];
        foreach ($this->getValueForKey(DictionaryKey::DESCENDANT_FONTS, ReferenceValueArray::class)->referenceValues ?? [] as $referenceValue) {
            $descendantFonts[] = ($this->dictionary->object?->document->getObject($referenceValue->objectNumber) ?? throw new ParseFailureException(sprintf('Descendant font with number %d could not be found', $referenceValue->objectNumber)))
                ->getDictionary(Font::class);
        }

        return $this->descendantFontsCache = $descendantFonts;
    }

    public function isCIDFont(): bool {
        return in_array(
            $this->getValueForKey(DictionaryKey::SUBTYPE, SubtypeNameValue::class),
            [SubtypeNameValue::CID_FONT_TYPE_0, SubtypeNameValue::CID_FONT_TYPE_2, SubtypeNameValue::CID_FONT_TYPE_0_C],
            true,
        );
    }

    public function getDefaultWidth(): float {
        if ($this->isCIDFont()) {
            return ($this->getValueForKey(DictionaryKey::DW, IntegerValue::class)->value
                ?? 1000) / 1000;
        }

        foreach ($this->getDescendantFonts() as $descendantFont) {
            if ($descendantFont instanceof Dictionary && $descendantFont->getTypeForKey(DictionaryKey::W) === ReferenceValue::class) {
                /** @var ReferenceValue $descendantFontReference */
                $descendantFontReference = $descendantFont->getValueForKey(DictionaryKey::W, ReferenceValue::class) ?? throw new ParseFailureException();
                $descendantFont = ($this->dictionary->object?->document->getObject($descendantFontReference->objectNumber) ?? throw new ParseFailureException())
                    ->getDictionary(Font::class);
            }

            if ($descendantFont instanceof Font) {
                return $descendantFont->getDefaultWidth();
            }
        }

        return 1.0;
    }

    /** @throws PdfParserException */
    public function getWidths(): CIDFontWidths|FontWidths|null {
        if (isset($this->widths)) {
            if ($this->widths === false) {
                return null;
            }

            return $this->widths;
        }

        if ($this->isCIDFont()) {
            if ($this->getTypeForKey(DictionaryKey::W) === CrossReferenceStreamByteSizes::class) {
                $byteSizes = $this->getValueForKey(DictionaryKey::W, CrossReferenceStreamByteSizes::class) ?? throw new ParseFailureException(); // TODO: fix misinterpretation

                return $this->widths = new CIDFontWidths(new RangeCIDWidth($byteSizes->lengthRecord1InBytes, $byteSizes->lengthRecord2InBytes, $byteSizes->lengthRecord3InBytes));
            }

            $this->widths = $this->getValueForKey(DictionaryKey::W, CIDFontWidths::class) ?? false;
            return $this->widths === false ? null : $this->widths;
        }

        foreach ($this->getDescendantFonts() as $descendantFont) {
            if ($descendantFont instanceof Dictionary && $descendantFont->getTypeForKey(DictionaryKey::W) === ReferenceValue::class) {
                /** @var ReferenceValue $descendantFontReference */
                $descendantFontReference = $descendantFont->getValueForKey(DictionaryKey::W, ReferenceValue::class) ?? throw new ParseFailureException();
                $descendantFont = $this->dictionary->object?->document->getObject($descendantFontReference->objectNumber) ?? throw new ParseFailureException();
            }

            if ($descendantFont instanceof Font && ($widthsDescendantFont = $descendantFont->getWidths()) !== null) {
                return $this->widths = $widthsDescendantFont;
            }
        }

        if ($this->getTypeForKey(DictionaryKey::WIDTHS) === ReferenceValue::class) {
            $widthsReference = $this->getValueForKey(DictionaryKey::WIDTHS, ReferenceValue::class) ?? throw new ParseFailureException();
            $object = $this->dictionary->object?->document->getObject($widthsReference->objectNumber)
                ?? throw new ParseFailureException(sprintf('Width dictionary with number %d could not be found', $widthsReference->objectNumber));
            $arrayValue = ArrayValue::fromValue(preg_replace('/%.+/', '', $object->getContent()->toString()) ?? $object->getContent()->toString());
            if ($arrayValue instanceof ArrayValue === false) {
                throw new ParseFailureException(sprintf('Width dictionary with number %d does not contain a valid array, "%s"', $widthsReference->objectNumber, $object->getContent()->read(0, 100) . '...'));
            }

            $widthsArray = $arrayValue->value;
        } elseif (($widthsArray = $this->getValueForKey(DictionaryKey::WIDTHS, ArrayValue::class)?->value) === null) {
            $this->widths = false;
            return null;
        }

        if (($firstChar = $this->getFirstChar()) === null) {
            $this->widths = false;
            return null;
        }

        return $this->widths = new FontWidths(
            $firstChar,
            array_values(
                array_map(
                    fn(mixed $width): float => is_numeric($width) ? (float) $width : throw new InvalidArgumentException(sprintf('"%s" is not a valid width', ($jsonEncoded = json_encode($width)) !== false ? $jsonEncoded : 'value')),
                    array_filter(
                        $widthsArray,
                        fn(mixed $item) => $item !== '',
                    ),
                ),
            ),
        );
    }

    /** @throws PdfParserException */
    public function getFontDescriptor(): ?ReferenceValue {
        return $this->getValueForKey(DictionaryKey::FONT_DESCRIPTOR, ReferenceValue::class);
    }

    public function isBold(): bool {
        $baseFont = $this->getBaseFont();
        if ($baseFont === null) {
            return false;
        }

        $baseFont = strtolower($baseFont);
        return str_contains($baseFont, 'bold')
            || str_contains($baseFont, 'heavy')
            || str_contains($baseFont, 'black');
    }

    public function isItalic(): bool {
        $baseFont = $this->getBaseFont();
        if ($baseFont === null) {
            return false;
        }

        $baseFont = strtolower($baseFont);
        return str_contains($baseFont, 'italic')
            || str_contains($baseFont, 'oblique')
            || str_contains($baseFont, 'slanted');
    }

    public function getHeadingLevel(TextState $textState, TransformationMatrix $transformationMatrix): ?HeadingLevel {
        $effectiveFontSize = $textState->getFontSize() * $transformationMatrix->scaleX;

        return match (true) {
            $effectiveFontSize >= 30 => HeadingLevel::Level1,
            $effectiveFontSize >= 26 => HeadingLevel::Level2,
            $effectiveFontSize >= 22 => HeadingLevel::Level3,
            $effectiveFontSize >= 18 => HeadingLevel::Level4,
            $effectiveFontSize >= 14 => HeadingLevel::Level5,
            default => null,
        };
    }
}
