<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\Decorator;

use DateTimeImmutable;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Date\DateValue;
use PrinsFrank\PdfParser\Exception\PdfParserException;

/** @see 14.3.3 Information dictionary */
readonly class InformationDictionary extends DecoratedDictionary {
    /** @throws PdfParserException */
    public function getTitle(): ?string {
        return $this->getStringValue(DictionaryKey::TITLE)
            ?->getText();
    }

    /** @throws PdfParserException */
    public function getProducer(): ?string {
        return $this->getStringValue(DictionaryKey::PRODUCER)
            ?->getText();
    }

    /** @throws PdfParserException */
    public function getAuthor(): ?string {
        return $this->getStringValue(DictionaryKey::AUTHOR)
            ?->getText();
    }

    /** @throws PdfParserException */
    public function getCreator(): ?string {
        return $this->getStringValue(DictionaryKey::CREATOR)
            ?->getText();
    }

    /** @throws PdfParserException */
    public function getSubject(): ?string {
        return $this->getStringValue(DictionaryKey::SUBJECT)
            ?->getText();
    }

    /** @throws PdfParserException */
    public function getKeywords(): ?string {
        return $this->getStringValue(DictionaryKey::KEYWORDS)
            ?->getText();
    }

    /** @throws PdfParserException */
    public function getCreationDate(): ?DateTimeImmutable {
        return $this->getValueForKey(DictionaryKey::CREATION_DATE, DateValue::class)
            ?->value;
    }

    /** @throws PdfParserException */
    public function getModificationDate(): ?DateTimeImmutable {
        return $this->getValueForKey(DictionaryKey::MOD_DATE, DateValue::class)
            ?->value;
    }
}
