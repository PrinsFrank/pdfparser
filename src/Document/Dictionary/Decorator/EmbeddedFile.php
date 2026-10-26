<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\Decorator;

use DateTimeImmutable;
use PrinsFrank\PdfParser\Document\Dictionary\Dictionary;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Date\DateValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Integer\IntegerValue;

/** @see 7.11.4 Embedded file streams */
readonly class EmbeddedFile extends DecoratedDictionary {
    public function getLength(): ?int {
        return $this->getValueForKey(DictionaryKey::LENGTH, IntegerValue::class)
            ?->value;
    }

    public function getFileSpecificInformation(): ?Dictionary {
        return $this->getSubDictionary(DictionaryKey::PARAMS);
    }

    public function getSize(): ?int {
        return $this->getFileSpecificInformation()
            ?->getValueForKey(DictionaryKey::SIZE, IntegerValue::class)
            ?->value;
    }

    public function getCreationDate(): ?DateTimeImmutable {
        return $this->getFileSpecificInformation()
            ?->getValueForKey(DictionaryKey::CREATION_DATE, DateValue::class)
            ?->value;
    }

    public function getModificationDate(): ?DateTimeImmutable {
        return $this->getFileSpecificInformation()
            ?->getValueForKey(DictionaryKey::MOD_DATE, DateValue::class)
            ?->value;
    }
}
