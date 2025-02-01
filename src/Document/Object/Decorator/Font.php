<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Object\Decorator;

use Override;
use PrinsFrank\PdfParser\Document\CMap\Registry\RegistryOrchestrator;
use PrinsFrank\PdfParser\Document\CMap\ToUnicode\ToUnicodeCMap;
use PrinsFrank\PdfParser\Document\CMap\ToUnicode\ToUnicodeCMapParser;
use PrinsFrank\PdfParser\Document\Dictionary\Dictionary;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Array\ArrayValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Integer\IntegerValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\EncodingNameValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\TypeNameValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Reference\ReferenceValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString\TextStringValue;
use PrinsFrank\PdfParser\Document\Object\Item\UncompressedObject\UncompressedObject;
use PrinsFrank\PdfParser\Exception\ParseFailureException;
use PrinsFrank\PdfParser\Stream;

class Font extends DecoratedObject {
    private readonly ToUnicodeCMap|false $toUnicodeCMap;

    public function getBaseFont(): ?string {
        return $this->getDictionary()
            ->getValueForKey(DictionaryKey::BASE_FONT, TextStringValue::class)
            ?->textStringValue;
    }

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

        return $this->toUnicodeCMap = ToUnicodeCMapParser::parse(
            $stream = Stream::fromString($toUnicodeObject->objectItem->getStreamContent($this->document->stream)),
            0,
            $stream->getSizeInBytes()
        );
    }

    public function getFirstChar(): ?int {
        return $this->getDictionary()
            ->getValueForKey(DictionaryKey::FIRST_CHAR, IntegerValue::class)
            ?->value;
    }

    public function getLastChar(): ?int {
        return $this->getDictionary()
            ->getValueForKey(DictionaryKey::LAST_CHAR, IntegerValue::class)
            ?->value;
    }

    /** @return array<mixed>|null */
    public function getWidths(): ?array {
        return $this->getDictionary()
            ->getValueForKey(DictionaryKey::WIDTHS, ArrayValue::class)
            ?->value;
    }

    public function getFontDescriptor(): ?ReferenceValue {
        return $this->getDictionary()
            ->getValueForKey(DictionaryKey::FONT_DESCRIPTOR, ReferenceValue::class);
    }

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
            return $encoding->decodeString(implode('', array_map(fn (string $character) => chr((int) hexdec($character)), str_split($characterGroup, 2))));
        }

        throw new ParseFailureException('No ToUnicodeCMap or encoding information available for this font');
    }

    #[Override]
    protected function getTypeName(): ?TypeNameValue {
        return TypeNameValue::FONT;
    }
}
