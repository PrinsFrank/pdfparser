<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Object\Decorator;

use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\LineGroupingStrategy\TextOverlapStrategy;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\PositionedTextElement;
use PrinsFrank\PdfParser\Document\Dictionary\Dictionary;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\ContentStream\ContentStream;
use PrinsFrank\PdfParser\Document\ContentStream\ContentStreamParser;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Rectangle\Rectangle;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Reference\ReferenceValue;
use PrinsFrank\PdfParser\Exception\InvalidArgumentException;
use PrinsFrank\PdfParser\Exception\ParseFailureException;
use PrinsFrank\PdfParser\Exception\PdfParserException;

class Page extends DecoratedObject {
    /**
     * @throws PdfParserException
     * @return list<PositionedTextElement>
     */
    public function getPositionedTextElements(): array {
        return $this->getContentStream()
            ?->getPositionedTextElements() ?? [];
    }

    /** @throws PdfParserException */
    public function getText(): string {
        return $this->getContentStream()
            ?->getText($this->document, $this, new TextOverlapStrategy()) ?? '';
    }

    /** @throws PdfParserException */
    public function getContentStream(): ?ContentStream {
        if ($this->getDictionary()->getTypeForKey(DictionaryKey::CONTENTS) === null) {
            return null;
        }

        return ContentStreamParser::parse(
            $this->document->getObjectsByDictionaryKey($this->getDictionary(), DictionaryKey::CONTENTS),
        );
    }

    /** @throws PdfParserException */
    public function getResourceDictionary(): ?Dictionary {
        if (($localResourceDictionary = $this->getDictionary()->getSubDictionary($this->document, DictionaryKey::RESOURCES)) !== null) {
            return $localResourceDictionary;
        }

        if (($parentReference = $this->getDictionary()->getValueForKey($this->document, DictionaryKey::PARENT, ReferenceValue::class)) === null) {
            return null;
        }

        return ($this->document->getObject($parentReference->objectNumber, Pages::class) ?? throw new ParseFailureException(sprintf('Parent with object nr %d not found', $parentReference->objectNumber)))
            ->getResourceDictionary([$parentReference->objectNumber]);
    }

    /** @throws PdfParserException */
    public function getXObjectsDictionary(): ?Dictionary {
        return $this->getResourceDictionary()
            ?->getSubDictionary($this->document, DictionaryKey::XOBJECT);
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

            $xObjects[] = $this->document->getObject($xObjectDictionaryEntry->value->objectNumber, XObject::class)
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
        if (($pageFontDictionary = $this->getDictionary()->getSubDictionary($this->document, DictionaryKey::FONT)) !== null) {
            return $pageFontDictionary;
        }

        return $this->getResourceDictionary()
            ?->getSubDictionary($this->document, DictionaryKey::FONT);
    }

    /** @return list<FileSpecification> */
    public function getFileSpecifications(): array {
        return $this->getDictionary()
            ->getObjectsForReference($this->document, DictionaryKey::AF, FileSpecification::class);
    }

    public function getMediaBox(): ?Rectangle {
        if (($localValue = $this->getDictionary()->getValueForKey($this->document, DictionaryKey::MEDIA_BOX, Rectangle::class)) !== null) {
            return $localValue;
        }

        if (($parentReference = $this->getDictionary()->getValueForKey($this->document, DictionaryKey::PARENT, ReferenceValue::class)) === null) {
            return null;
        }

        return ($this->document->getObject($parentReference->objectNumber, Pages::class) ?? throw new ParseFailureException(sprintf('Parent with object nr %d not found', $parentReference->objectNumber)))
            ->getMediaBox([$parentReference->objectNumber]);
    }

    public function getCropBox(): ?Rectangle {
        if (($localValue = $this->getDictionary()->getValueForKey($this->document, DictionaryKey::CROP_BOX, Rectangle::class)) !== null) {
            return $localValue;
        }

        if (($parentReference = $this->getDictionary()->getValueForKey($this->document, DictionaryKey::PARENT, ReferenceValue::class)) === null) {
            return null;
        }

        $cropBoxParent = ($this->document->getObject($parentReference->objectNumber, Pages::class) ?? throw new ParseFailureException(sprintf('Parent with object nr %d not found', $parentReference->objectNumber)))
            ->getCropBox([$parentReference->objectNumber]);
        if ($cropBoxParent !== null) {
            return $cropBoxParent;
        }

        return $this->getMediaBox();
    }
}
