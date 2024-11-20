<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Object\Decorator;

use Override;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\TypeNameValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Reference\ReferenceValue;
use PrinsFrank\PdfParser\Document\Document;
use PrinsFrank\PdfParser\Exception\ParseFailureException;
use PrinsFrank\PdfParser\Exception\RuntimeException;

class Catalog extends DecoratedObject {
    #[Override]
    protected function getTypeName(): ?TypeNameValue {
        return TypeNameValue::CATALOG;
    }

    public function getPagesRoot(Document $document): Pages {
        $catalogDictionary = $this->getDictionary($this->stream);
        $pagesReference = $catalogDictionary->getValueForKey(DictionaryKey::PAGES, ReferenceValue::class)
            ?? throw new ParseFailureException('Every catalog dictionary should contain a pages reference, none found');
        $pages = $document->getObject($pagesReference->objectNumber)
            ?? throw new ParseFailureException(sprintf('Unable to retrieve pages root object with number %d', $pagesReference->objectNumber));
        if (!$pages instanceof Pages) {
            throw new RuntimeException('Pages root should be a PAGES item');
        }

        return $pages;
    }
}
