<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\Decorator;

use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Object\Decorator\DecoratedObject;

readonly class InteractiveForm extends DecoratedDictionary {
    /** @return list<Field> */
    public function getFields(): array {
        return array_map(
            static fn(DecoratedObject $decoratedObject) => $decoratedObject->getDictionary(Field::class),
            $this->getObjectsForReference(DictionaryKey::FIELDS),
        );
    }
}
