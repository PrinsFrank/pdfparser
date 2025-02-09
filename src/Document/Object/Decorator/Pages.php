<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Object\Decorator;

use Override;
use PrinsFrank\PdfParser\Document\Dictionary\Dictionary;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\TypeNameValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Reference\ReferenceValueArray;
use PrinsFrank\PdfParser\Exception\ParseFailureException;
use PrinsFrank\PdfParser\Exception\PdfParserException;

class Pages extends DecoratedObject {
    #[Override]
    protected function getTypeName(): ?TypeNameValue {
        return TypeNameValue::PAGES;
    }

    /**
     * @throws PdfParserException
     * @return list<Page>
     */
    public function getPageItems(): array {
        $kids = [];
        foreach ($this->getDictionary()->getValueForKey(DictionaryKey::KIDS, ReferenceValueArray::class)->referenceValues ?? [] as $referenceValue) {
            $kidObject = $this->document->getObject($referenceValue->objectNumber)
                ?? throw new ParseFailureException(sprintf('Child with number %d could not be found', $referenceValue->objectNumber));

            if ($kidObject instanceof Pages) {
                $kids = [...$kids, ...$kidObject->getPageItems()];
            } elseif ($kidObject instanceof Page) {
                $kids[] = $kidObject;
            }
        }

        return $kids;
    }

    /** @throws PdfParserException */
    public function getResourceDictionary(): ?Dictionary {
        return $this->getDictionary()
            ->getSubDictionary($this->document, DictionaryKey::RESOURCES);
    }
}
