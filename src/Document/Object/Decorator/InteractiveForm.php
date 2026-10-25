<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Object\Decorator;

use PrinsFrank\PdfParser\Document\Dictionary\Decorator\Field;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;

class InteractiveForm extends DecoratedObject {
    /** @return list<Field> */
    public function getFields(): array {
        return array_map(
            static fn(DecoratedObject $decoratedObject) => $decoratedObject->getDictionary(Field::class),
            $this->getDictionary()->getObjectsForReference(DictionaryKey::FIELDS),
        );
    }
}
