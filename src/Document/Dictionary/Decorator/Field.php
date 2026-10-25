<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\Decorator;

use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Object\Decorator\Annotation;

readonly class Field extends DecoratedDictionary {
    /** @return list<Annotation> */
    public function getAnnotations(): array {
        return $this->getObjectsForReference(DictionaryKey::KIDS, Annotation::class);
    }
}
