<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\Decorator;

use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Object\Item\CompressedObject\CompressedObject;
use PrinsFrank\PdfParser\Document\Object\Item\UncompressedObject\UncompressedObject;

readonly class InteractiveForm extends DecoratedDictionary {
    /** @return list<Field> */
    public function getFields(): array {
        return array_map(
            static fn(UncompressedObject|CompressedObject $object) => $object->getDictionary(Field::class),
            $this->getObjectsForReference(DictionaryKey::FIELDS),
        );
    }
}
