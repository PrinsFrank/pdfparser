<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Object\Decorator;

use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Reference\ReferenceValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Reference\ReferenceValueArray;
use PrinsFrank\PdfParser\Exception\ParseFailureException;
use PrinsFrank\PdfParser\Exception\PdfParserException;

class Catalog extends DecoratedObject {
    /** @throws PdfParserException */
    public function getPagesRoot(): Pages {
        $pagesReference = $this->getDictionary()->getValueForKey(DictionaryKey::PAGES, ReferenceValue::class)
            ?? throw new ParseFailureException('Every catalog dictionary should contain a pages reference, none found');

        return $this->document->getObject($pagesReference->objectNumber, Pages::class)
            ?? throw new ParseFailureException(sprintf('Unable to retrieve pages root object with number %d', $pagesReference->objectNumber));
    }

    /** @return list<FileSpecification> */
    public function getFileSpecifications(): array {
        $afType = $this->getDictionary()->getTypeForKey(DictionaryKey::AF);
        if ($afType === null) {
            return [];
        }

        if ($afType === ReferenceValue::class) {
            $referenceArrayContent = $this->getDictionary()
                ->getObjectForReference($this->document, DictionaryKey::AF, FileSpecification::class)
                ?->getContent() ?? throw new ParseFailureException('Unable to retrieve AF object content');
            if (($AFReferences = ReferenceValueArray::fromValue($referenceArrayContent)) instanceof ReferenceValueArray === false) {
                throw new ParseFailureException('AF object is not a reference array');
            }

            return array_map(
                fn (ReferenceValue $referenceValue) => $this->document->getObject($referenceValue->objectNumber, FileSpecification::class) ?? throw new ParseFailureException('Unable to retrieve file specification'),
                $AFReferences->referenceValues,
            );
        }

        if ($afType === ReferenceValueArray::class) {
            return $this->getDictionary()
                ->getObjectsForReference($this->document, DictionaryKey::AF, FileSpecification::class);
        }

        throw new ParseFailureException(sprintf('Unexpected type "%s" for AF key', $afType));
    }
}
