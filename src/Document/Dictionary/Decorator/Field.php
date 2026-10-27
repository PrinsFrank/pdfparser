<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\Decorator;

use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Object\CompressedObject\CompressedObject;
use PrinsFrank\PdfParser\Document\Object\UncompressedObject\UncompressedObject;

readonly class Field extends DecoratedDictionary {
    /** @return list<Annotation> */
    public function getAnnotations(): array {
        return array_map(
            static fn(UncompressedObject|CompressedObject $object) => $object->getDictionary(Annotation::class),
            $this->getObjectsForReference(DictionaryKey::KIDS),
        );
    }
}
