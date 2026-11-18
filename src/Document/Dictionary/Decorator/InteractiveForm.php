<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\Decorator;

use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;

/** @see 12.7.3 Interactive form dictionary */
readonly class InteractiveForm extends DecoratedDictionary {
    /** @return list<Field> */
    public function getFields(): array {
        return $this->getSubDictionaries(DictionaryKey::FIELDS, Field::class);
    }
}
