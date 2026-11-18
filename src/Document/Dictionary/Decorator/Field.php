<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\Decorator;

use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;

readonly class Field extends DecoratedDictionary {
    /** @return list<Annotation> */
    public function getAnnotations(): array {
        return $this->getSubDictionaries(DictionaryKey::KIDS, Annotation::class);
    }
}
