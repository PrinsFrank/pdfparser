<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\Decorator;

use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Reference\ReferenceValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Reference\ReferenceValueArray;
use PrinsFrank\PdfParser\Document\Object\Decorator\DecoratedObject;
use PrinsFrank\PdfParser\Exception\ParseFailureException;
use PrinsFrank\PdfParser\Exception\PdfParserException;

readonly class Catalog extends DecoratedDictionary {
    /** @throws PdfParserException */
    public function getPagesRoot(): Pages {
        $pagesReference = $this->getValueForKey(DictionaryKey::PAGES, ReferenceValue::class)
            ?? throw new ParseFailureException('Every catalog dictionary should contain a pages reference, none found');

        return ($this->dictionary->object?->document?->getObject($pagesReference->objectNumber)
            ?? throw new ParseFailureException(sprintf('Unable to retrieve pages root object with number %d', $pagesReference->objectNumber)))
            ->getDictionary(Pages::class);
    }

    public function getInteractiveForm(): ?InteractiveForm {
        return $this->getObjectForReference(DictionaryKey::ACRO_FORM)
            ?->getDictionary(InteractiveForm::class);
    }

    /** @return list<FileSpecification> */
    public function getFileSpecifications(): array {
        $afType = $this->getTypeForKey(DictionaryKey::AF);
        if ($afType === null) {
            return [];
        }

        if ($afType === ReferenceValue::class) {
            $referenceArrayContent = $this->getObjectForReference(DictionaryKey::AF)
                ?->getStream()->toString() ?? throw new ParseFailureException('Unable to retrieve AF object content');
            if (($AFReferences = ReferenceValueArray::fromValue($referenceArrayContent)) instanceof ReferenceValueArray === false) {
                throw new ParseFailureException('AF object is not a reference array');
            }

            return array_map(
                fn(ReferenceValue $referenceValue) => ($this->dictionary->object?->document?->getObject($referenceValue->objectNumber) ?? throw new ParseFailureException('Unable to retrieve file specification'))->getDictionary(FileSpecification::class),
                $AFReferences->referenceValues,
            );
        }

        if ($afType === ReferenceValueArray::class) {
            return array_map(
                static fn(DecoratedObject $object) => $object->getDictionary(FileSpecification::class),
                $this->getObjectsForReference(DictionaryKey::AF),
            );
        }

        throw new ParseFailureException(sprintf('Unexpected type "%s" for AF key', $afType));
    }
}
