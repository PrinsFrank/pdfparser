<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Object\Decorator;

use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;

class InteractiveForm extends DecoratedObject {
    /** @return list<Field> */
    public function getFields(): array {
        return $this->getDictionary()
            ->getObjectsForReference(DictionaryKey::FIELDS, Field::class);
    }
}
