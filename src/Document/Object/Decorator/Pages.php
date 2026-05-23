<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Object\Decorator;

use PrinsFrank\PdfParser\Document\Dictionary\Dictionary;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Rectangle\Rectangle;
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

    /**
     * @param list<int> $visitedObjectNrs
     * @throws PdfParserException
     */
    public function getResourceDictionary(array $visitedObjectNrs): ?Dictionary {
        if (($localValue = $this->getDictionary()->getSubDictionary($this->document, DictionaryKey::RESOURCES)) !== null) {
            return $localValue;
        }

        if (($parentReference = $this->getDictionary()->getValueForKey($this->document, DictionaryKey::PARENT, ReferenceValue::class)) === null) {
            return null;
        }

        if (in_array($parentReference->objectNumber, $visitedObjectNrs, true)) {
            return null; // exit loops in page trees
        }

        return ($this->document->getObject($parentReference->objectNumber, Pages::class) ?? throw new ParseFailureException(sprintf('Parent with object nr %d not found', $parentReference->objectNumber)))
            ->getResourceDictionary([... $visitedObjectNrs, $parentReference->objectNumber]);
    }

    /** @param list<int> $visitedObjectNrs */
    public function getMediaBox(array $visitedObjectNrs): ?Rectangle {
        if (($localValue = $this->getDictionary()->getValueForKey($this->document, DictionaryKey::MEDIA_BOX, Rectangle::class)) !== null) {
            return $localValue;
        }

        if (($parentReference = $this->getDictionary()->getValueForKey($this->document, DictionaryKey::PARENT, ReferenceValue::class)) === null) {
            return null;
        }

        if (in_array($parentReference->objectNumber, $visitedObjectNrs, true)) {
            return null; // exit loops in page trees
        }

        return ($this->document->getObject($parentReference->objectNumber, Pages::class) ?? throw new ParseFailureException(sprintf('Parent with object nr %d not found', $parentReference->objectNumber)))
            ->getCropBox([... $visitedObjectNrs, $parentReference->objectNumber]);
    }

    /** @param list<int> $visitedObjectNrs */
    public function getCropBox(array $visitedObjectNrs): ?Rectangle {
        if (($localValue = $this->getDictionary()->getValueForKey($this->document, DictionaryKey::CROP_BOX, Rectangle::class)) !== null) {
            return $localValue;
        }

        if (($parentReference = $this->getDictionary()->getValueForKey($this->document, DictionaryKey::PARENT, ReferenceValue::class)) === null) {
            return null;
        }

        if (in_array($parentReference->objectNumber, $visitedObjectNrs, true)) {
            return null; // exit loops in page trees
        }

        return ($this->document->getObject($parentReference->objectNumber, Pages::class) ?? throw new ParseFailureException(sprintf('Parent with object nr %d not found', $parentReference->objectNumber)))
            ->getCropBox([... $visitedObjectNrs, $parentReference->objectNumber]);
    }
}
