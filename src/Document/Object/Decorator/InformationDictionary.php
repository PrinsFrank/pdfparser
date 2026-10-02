<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Object\Decorator;

use DateTimeImmutable;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Date\DateValue;
use PrinsFrank\PdfParser\Exception\PdfParserException;

class InformationDictionary extends DecoratedObject {
    /** @throws PdfParserException */
    public function getTitle(): ?string {
        return $this->getDictionary()
            ->getStringValue($this->document, DictionaryKey::TITLE)
            ?->getText();
    }

    /** @throws PdfParserException */
    public function getProducer(): ?string {
        return $this->getDictionary()
            ->getStringValue($this->document, DictionaryKey::PRODUCER)
            ?->getText();
    }

    /** @throws PdfParserException */
    public function getAuthor(): ?string {
        return $this->getDictionary()
            ->getStringValue($this->document, DictionaryKey::AUTHOR)
            ?->getText();
    }

    /** @throws PdfParserException */
    public function getCreator(): ?string {
        return $this->getDictionary()
            ->getStringValue($this->document, DictionaryKey::CREATOR)
            ?->getText();
    }

    /** @throws PdfParserException */
    public function getSubject(): ?string {
        return $this->getDictionary()
            ->getStringValue($this->document, DictionaryKey::SUBJECT)
            ?->getText();
    }

    /** @throws PdfParserException */
    public function getKeywords(): ?string {
        return $this->getDictionary()
            ->getStringValue($this->document, DictionaryKey::KEYWORDS)
            ?->getText();
    }

    /** @throws PdfParserException */
    public function getCreationDate(): ?DateTimeImmutable {
        return $this->getDictionary()
            ->getValueForKey($this->document, DictionaryKey::CREATION_DATE, DateValue::class)
            ?->value;
    }

    /** @throws PdfParserException */
    public function getModificationDate(): ?DateTimeImmutable {
        return $this->getDictionary()
            ->getValueForKey($this->document, DictionaryKey::MOD_DATE, DateValue::class)
            ?->value;
    }
}
