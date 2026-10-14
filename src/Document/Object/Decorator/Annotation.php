<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Object\Decorator;

use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\SubtypeNameValue;

class Annotation extends DecoratedObject {
    public function getSubType(): ?SubtypeNameValue {
        return $this->getDictionary()
            ->getValueForKey($this->document, DictionaryKey::SUBTYPE, SubtypeNameValue::class);
    }
}
