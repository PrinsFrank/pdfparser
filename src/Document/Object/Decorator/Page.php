<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Object\Decorator;

use PrinsFrank\PdfParser\Document\Dictionary\Dictionary;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Object\Item\UncompressedObject\UncompressedObject;
use PrinsFrank\PdfParser\Document\Text\TextObjectCollection;
use PrinsFrank\PdfParser\Document\Text\TextParser;
use PrinsFrank\PdfParser\Exception\ParseFailureException;
use PrinsFrank\PdfParser\Exception\PdfParserException;

class Page extends DecoratedObject {
    /** @throws PdfParserException */
    public function getText(): string {
        return $this->getTextObjectCollection()
            ->getText($this->document, $this);
    }

    /** @throws PdfParserException */
    public function getTextObjectCollection(): TextObjectCollection {
        return TextParser::parse(
            implode(
                '',
                array_map(
                    function (DecoratedObject $decoratedObject) {
                        if (!($objectItem = $decoratedObject->objectItem) instanceof UncompressedObject) {
                            throw new ParseFailureException('Text in compressed objects are currently not supported');
                        }

                        return $objectItem->getStreamContent($this->document->stream);
                    },
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
