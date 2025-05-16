<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Object\Decorator;

use PrinsFrank\PdfParser\Document\Dictionary\Dictionary;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\ContentStream\ContentStream;
use PrinsFrank\PdfParser\Document\ContentStream\ContentStreamParser;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Reference\ReferenceValue;
use PrinsFrank\PdfParser\Exception\InvalidArgumentException;
use PrinsFrank\PdfParser\Exception\ParseFailureException;
use PrinsFrank\PdfParser\Exception\PdfParserException;

class Page extends DecoratedObject {
    /** @throws PdfParserException */
    public function getText(): string {
        return $this->getContentStream()
            ->getText($this->document, $this);
    }

    /** @throws PdfParserException */
    public function getContentStream(): ContentStream {
        return ContentStreamParser::parse(
            implode(
                '',
                array_map(
                    fn (DecoratedObject $decoratedObject) => $decoratedObject->objectItem->getContent($this->document),
                    $this->document->getObjectsByDictionaryKey($this->getDictionary(), DictionaryKey::CONTENTS),
                ),
            ),
        );
    }

    /** @throws PdfParserException */
    public function getResourceDictionary(): ?Dictionary {
        return $this->getDictionary()
            ->getSubDictionary($this->document, DictionaryKey::RESOURCES);
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
            fn (XObject $XObject) => $XObject->isImage(),
        ));
    }

    /** @throws PdfParserException */
    public function getFontDictionary(): ?Dictionary {
        if (($pageFontDictionary = $this->getDictionary()->getSubDictionary($this->document, DictionaryKey::FONT)) !== null) {
            return $pageFontDictionary;
        }

        if (($pageResourceFontDictionary = $this->getResourceDictionary()?->getSubDictionary($this->document, DictionaryKey::FONT)) !== null) {
            return $pageResourceFontDictionary;
        }

        if (($pagesParent = $this->getDictionary()->getObjectForReference($this->document, DictionaryKey::PARENT, Pages::class)) === null) {
            return null;
        }

        return $pagesParent->getResourceDictionary()
            ?->getSubDictionary($this->document, DictionaryKey::FONT);
    }
}
