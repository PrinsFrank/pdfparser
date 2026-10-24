<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Object\Decorator;

use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;

class Field extends DecoratedObject {
    /** @return list<Annotation> */
    public function getAnnotations(): array {
        return $this->getDictionary()
            ->getObjectsForReference($this->document, DictionaryKey::KIDS, Annotation::class);
    }
}
