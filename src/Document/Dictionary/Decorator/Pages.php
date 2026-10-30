<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\Decorator;

use PrinsFrank\PdfParser\Document\Dictionary\Dictionary;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\TypeNameValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Rectangle\Rectangle;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Reference\ReferenceValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Reference\ReferenceValueArray;
use PrinsFrank\PdfParser\Exception\ParseFailureException;
use PrinsFrank\PdfParser\Exception\PdfParserException;

/** @see 7.7.3.2 Page tree nodes */
readonly class Pages extends DecoratedDictionary {
    /**
     * @param list<int> $visitedObjectNrs
     * @throws PdfParserException
     * @return list<Page>
     */
    public function getPageItems(array $visitedObjectNrs = []): array {
        $kids = [];
        foreach ($this->getValueForKey(DictionaryKey::KIDS, ReferenceValueArray::class)->referenceValues ?? [] as $referenceValue) {
            if (in_array($referenceValue->objectNumber, $visitedObjectNrs, true)) {
                continue;
            }

            $visitedObjectNrs[] = $referenceValue->objectNumber;
            $kidObject = $this->dictionary->object?->document->getObject($referenceValue->objectNumber)
                ?? throw new ParseFailureException(sprintf('Child with number %d could not be found', $referenceValue->objectNumber));

            $kidObjectType = $kidObject->getDictionary()->getType();
            if ($kidObjectType === TypeNameValue::PAGES) {
                $kids = [...$kids, ...$kidObject->getDictionary(Pages::class)->getPageItems($visitedObjectNrs)];
            } else {
                $kids[] = $kidObject->getDictionary(Page::class);
            }
        }

        return $kids;
    }

    /**
     * @param list<int> $visitedObjectNrs
     * @throws PdfParserException
     */
    public function getResourceDictionary(array $visitedObjectNrs): ?Dictionary {
        if (($localValue = $this->getSubDictionary(DictionaryKey::RESOURCES)) !== null) {
            return $localValue;
        }

        if (($parentReference = $this->getValueForKey(DictionaryKey::PARENT, ReferenceValue::class)) === null) {
            return null;
        }

        if (in_array($parentReference->objectNumber, $visitedObjectNrs, true)) {
            return null; // exit loops in page trees
        }

        return ($this->dictionary->object?->document->getObject($parentReference->objectNumber) ?? throw new ParseFailureException(sprintf('Parent with object nr %d not found', $parentReference->objectNumber)))
            ->getDictionary(Pages::class)
            ->getResourceDictionary([... $visitedObjectNrs, $parentReference->objectNumber]);
    }

    /** @param list<int> $visitedObjectNrs */
    public function getMediaBox(array $visitedObjectNrs): ?Rectangle {
        if (($localValue = $this->getValueForKey(DictionaryKey::MEDIA_BOX, Rectangle::class)) !== null) {
            return $localValue;
        }

        if (($parentReference = $this->getValueForKey(DictionaryKey::PARENT, ReferenceValue::class)) === null) {
            return null;
        }

        if (in_array($parentReference->objectNumber, $visitedObjectNrs, true)) {
            return null; // exit loops in page trees
        }

        return ($this->dictionary->object?->document->getObject($parentReference->objectNumber) ?? throw new ParseFailureException(sprintf('Parent with object nr %d not found', $parentReference->objectNumber)))
            ->getDictionary(Pages::class)
            ->getCropBox([... $visitedObjectNrs, $parentReference->objectNumber]);
    }

    /** @param list<int> $visitedObjectNrs */
    public function getCropBox(array $visitedObjectNrs): ?Rectangle {
        if (($localValue = $this->getValueForKey(DictionaryKey::CROP_BOX, Rectangle::class)) !== null) {
            return $localValue;
        }

        if (($parentReference = $this->getValueForKey(DictionaryKey::PARENT, ReferenceValue::class)) === null) {
            return null;
        }

        if (in_array($parentReference->objectNumber, $visitedObjectNrs, true)) {
            return null; // exit loops in page trees
        }

        return ($this->dictionary->object?->document->getObject($parentReference->objectNumber) ?? throw new ParseFailureException(sprintf('Parent with object nr %d not found', $parentReference->objectNumber)))
            ->getDictionary(Pages::class)
            ->getCropBox([... $visitedObjectNrs, $parentReference->objectNumber]);
    }
}
