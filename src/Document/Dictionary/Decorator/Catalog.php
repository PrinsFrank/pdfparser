<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\Decorator;

use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Reference\ReferenceValue;
use PrinsFrank\PdfParser\Exception\ParseFailureException;
use PrinsFrank\PdfParser\Exception\PdfParserException;

/** @see 7.7.2 Document Catalog dictionaries */
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
        return $this->getSubDictionaries(DictionaryKey::AF, FileSpecification::class);
    }
}
