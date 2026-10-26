<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\Decorator;

use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Object\Decorator\DecoratedObject;

readonly class Field extends DecoratedDictionary {
    /** @return list<Annotation> */
    public function getAnnotations(): array {
        return array_map(
            static fn(DecoratedObject $object) => $object->getDictionary(Annotation::class),
            $this->getObjectsForReference(DictionaryKey::KIDS),
        );
    }
}
