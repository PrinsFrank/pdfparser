<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Object\Decorator;

use PrinsFrank\PdfParser\Document\Dictionary\Dictionary;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\DictionaryValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\NameValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Reference\ReferenceValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Reference\ReferenceValueArray;
use PrinsFrank\PdfParser\Exception\ParseFailureException;
use PrinsFrank\PdfParser\Exception\PdfParserException;

class Pages extends DecoratedObject {
    /**
     * @throws PdfParserException
     * @return list<Page>
     */
    public function getPageItems(): array {
        $kids = [];
        foreach ($this->getDictionary()->getValueForKey($this->document, DictionaryKey::KIDS, ReferenceValueArray::class)->referenceValues ?? [] as $referenceValue) {
            $kidObject = $this->document->getObject($referenceValue->objectNumber)
                ?? throw new ParseFailureException(sprintf('Child with number %d could not be found', $referenceValue->objectNumber));

            if ($kidObject instanceof Pages) {
                $kids = [...$kids, ...$kidObject->getPageItems()];
            } elseif ($kidObject instanceof Page) {
                $kids[] = $kidObject;
            } elseif ($kidObject instanceof GenericObject) {
                $kids[] = new Page($kidObject->objectItem, $this->document);
            }
        }

        return $kids;
    }

    /** @throws PdfParserException */
    public function getResourceDictionary(): ?Dictionary {
        return $this->getDictionary()
            ->getSubDictionary($this->document, DictionaryKey::RESOURCES);
    }

    /**
     * @template T of DictionaryValue|NameValue|Dictionary
     * @param class-string<T> $expectedValueType
     * @param list<int> $visitedObjectNrs a list of objects already visited to exit loops in page trees
     * @return T
     */
    public function getInheritableValue(DictionaryKey $dictionaryKey, string $expectedValueType, array $visitedObjectNrs): DictionaryValue|Dictionary|NameValue|null {
        if (($localValue = $this->getDictionary()->getValueForKey($this->document, $dictionaryKey, $expectedValueType)) !== null) {
            return $localValue;
        }

        if (($parentReference = $this->getDictionary()->getValueForKey($this->document, DictionaryKey::PARENT, ReferenceValue::class)) === null) {
            return null;
        }

        if (in_array($parentReference->objectNumber, $visitedObjectNrs, true)) {
            return null; // exit loops in page trees
        }

        return ($this->document->getObject($parentReference->objectNumber, Pages::class) ?? throw new ParseFailureException(sprintf('Parent with object nr %d not found', $parentReference->objectNumber)))
            ->getInheritableValue($dictionaryKey, $expectedValueType, [... $visitedObjectNrs, $parentReference->objectNumber]);
    }
}
