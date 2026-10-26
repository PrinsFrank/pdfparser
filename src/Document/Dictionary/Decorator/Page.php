<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\Decorator;

use PrinsFrank\MarkDownDom\Renderer\MarkdownRenderer;
use PrinsFrank\PdfParser\Document\ContentStream\ContentStream;
use PrinsFrank\PdfParser\Document\ContentStream\ContentStreamParser;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\PositionedTextElement;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TransformationMatrix;
use PrinsFrank\PdfParser\Document\Dictionary\Dictionary;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Rectangle\Rectangle;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Reference\ReferenceValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Reference\ReferenceValueArray;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString\NameObjectStringValue;
use PrinsFrank\PdfParser\Document\Object\Decorator\DecoratedObject;
use PrinsFrank\PdfParser\Document\Object\Decorator\Font;
use PrinsFrank\PdfParser\Exception\InvalidArgumentException;
use PrinsFrank\PdfParser\Exception\ParseFailureException;
use PrinsFrank\PdfParser\Exception\PdfParserException;
use PrinsFrank\PdfParser\Extraction\MarkdownExtractor;
use PrinsFrank\PdfParser\Extraction\TextExtractor;

readonly class Page extends DecoratedDictionary {
    /**
     * @throws PdfParserException
     * @return list<PositionedTextElement>
     */
    public function getPositionedTextElements(): array {
        return $this->getContentStream()
            ?->getPositionedTextElements($this, new TransformationMatrix(1, 0, 0, 1, 0, 0), []) ?? [];
    }

    /** @throws PdfParserException */
    public function getText(): string {
        return TextExtractor::extractContent($this->getPositionedTextElements(), $this);
    }

    public function getMarkdown(): string {
        return (new MarkdownRenderer())
            ->render(MarkdownExtractor::extractContent($this->getPositionedTextElements(), $this->getAnnotations(), $this));
    }

    /** @throws PdfParserException */
    public function getContentStream(): ?ContentStream {
        if ($this->getTypeForKey(DictionaryKey::CONTENTS) === null) {
            return null;
        }

        return ContentStreamParser::parse(
            $this->dictionary->object?->document->getObjectsByDictionaryKey($this->dictionary, DictionaryKey::CONTENTS) ?? throw new ParseFailureException(),
        );
    }

    /** @throws PdfParserException */
    public function getResourceDictionary(): ?Dictionary {
        if (($localResourceDictionary = $this->getSubDictionary(DictionaryKey::RESOURCES)) !== null) {
            return $localResourceDictionary;
        }

        if (($parentReference = $this->getValueForKey(DictionaryKey::PARENT, ReferenceValue::class)) === null) {
            return null;
        }

        return ($this->dictionary->object?->document->getObject($parentReference->objectNumber) ?? throw new ParseFailureException(sprintf('Parent with object nr %d not found', $parentReference->objectNumber)))
            ->getDictionary(Pages::class)
            ->getResourceDictionary([$parentReference->objectNumber]);
    }

    /** @throws PdfParserException */
    public function getXObjectsDictionary(): ?Dictionary {
        return $this->getResourceDictionary()
            ?->getSubDictionary(DictionaryKey::XOBJECT);
    }

    /**
     * @throws PdfParserException
     * @return list<XObject>
     */
    public function getXObjects(): array {
        $xObjects = [];
        foreach ($this->getXObjectsDictionary()->dictionaryEntries ?? [] as $xObjectDictionaryEntry) {
            if (!$xObjectDictionaryEntry->value instanceof ReferenceValue) {
                throw new InvalidArgumentException(sprintf('XObjects should be references, got %s', get_class($xObjectDictionaryEntry->value)));
            }

            $xObjects[] = $this->dictionary->object?->document->getObject($xObjectDictionaryEntry->value->objectNumber)?->getDictionary(XObject::class)
                ?? throw new ParseFailureException(sprintf('Unable to locate object with nr %d', $xObjectDictionaryEntry->value->objectNumber));
        }

        return $xObjects;
    }

    /**
     * @throws PdfParserException
     * @return list<XObject>
     */
    public function getImages(): array {
        return array_values(array_filter(
            $this->getXObjects(),
            fn(XObject $XObject) => $XObject->isImage(),
        ));
    }

    /** @throws PdfParserException */
    public function getFontDictionary(): ?Dictionary {
        return $this->getResourceDictionary()
            ?->getSubDictionary(DictionaryKey::FONT);
    }

    /** @throws PdfParserException */
    public function getFont(DictionaryKey|NameObjectStringValue $dictionaryKey): Font {
        $font = $this->getFontDictionary()
            ?->getObjectForReference($dictionaryKey, Font::class);
        if ($font !== null) {
            return $font;
        }

        foreach ($this->getXObjects() as $xObject) {
            $font = $xObject->getFontDictionary()
                ?->getObjectForReference($dictionaryKey, Font::class);
            if ($font !== null) {
                return $font;
            }
        }

        throw new ParseFailureException(sprintf('Unable to locate font with reference "/%s"', $dictionaryKey->value));
    }

    /** @return list<FileSpecification> */
    public function getFileSpecifications(): array {
        return array_map(
            static fn(DecoratedObject $decoratedObject) => $decoratedObject->getDictionary(FileSpecification::class),
            $this->getObjectsForReference(DictionaryKey::AF),
        );
    }

    public function getMediaBox(): ?Rectangle {
        if (($localValue = $this->getValueForKey(DictionaryKey::MEDIA_BOX, Rectangle::class)) !== null) {
            return $localValue;
        }

        if (($parentReference = $this->getValueForKey(DictionaryKey::PARENT, ReferenceValue::class)) === null) {
            return null;
        }

        return ($this->dictionary->object?->document->getObject($parentReference->objectNumber) ?? throw new ParseFailureException(sprintf('Parent with object nr %d not found', $parentReference->objectNumber)))
            ->getDictionary(Pages::class)
            ->getMediaBox([$parentReference->objectNumber]);
    }

    public function getCropBox(): ?Rectangle {
        if (($localValue = $this->getValueForKey(DictionaryKey::CROP_BOX, Rectangle::class)) !== null) {
            return $localValue;
        }

        if (($parentReference = $this->getValueForKey(DictionaryKey::PARENT, ReferenceValue::class)) === null) {
            return null;
        }

        $cropBoxParent = ($this->dictionary->object?->document->getObject($parentReference->objectNumber) ?? throw new ParseFailureException(sprintf('Parent with object nr %d not found', $parentReference->objectNumber)))
            ->getDictionary(Pages::class)
            ->getCropBox([$parentReference->objectNumber]);
        if ($cropBoxParent !== null) {
            return $cropBoxParent;
        }

        return $this->getMediaBox();
    }

    /** @return list<Annotation> */
    public function getAnnotations(): array {
        if (in_array($this->getTypeForKey(DictionaryKey::ANNOTS), [ReferenceValueArray::class, ReferenceValue::class], true) === false) {
            return [];
        }

        return array_map(
            static fn(DecoratedObject $object) => $object->getDictionary(Annotation::class),
            $this->getObjectsForReference(DictionaryKey::ANNOTS),
        );
    }
}
