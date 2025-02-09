<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Object\Decorator;

use DateTimeImmutable;
use Override;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Date\DateValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\TypeNameValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString\TextStringValue;
use PrinsFrank\PdfParser\Exception\PdfParserException;

class InformationDictionary extends DecoratedObject {
    /** @throws PdfParserException */
    public function getTitle(): ?string {
        return $this->getDictionary()
            ->getValueForKey(DictionaryKey::TITLE, TextStringValue::class)
            ?->getText();
    }

    /** @throws PdfParserException */
    public function getProducer(): ?string {
        return $this->getDictionary()
            ->getValueForKey(DictionaryKey::PRODUCER, TextStringValue::class)
            ?->getText();
    }

    /** @throws PdfParserException */
    public function getAuthor(): ?string {
        return $this->getDictionary()
            ->getValueForKey(DictionaryKey::AUTHOR, TextStringValue::class)
            ?->getText();
    }

    /** @throws PdfParserException */
    public function getCreator(): ?string {
        return $this->getDictionary()
            ->getValueForKey(DictionaryKey::CREATOR, TextStringValue::class)
            ?->getText();
    }

    /** @throws PdfParserException */
    public function getCreationDate(): ?DateTimeImmutable {
        return $this->getDictionary()
            ->getValueForKey(DictionaryKey::CREATION_DATE, DateValue::class)
            ?->value;
    }

    /** @throws PdfParserException */
    public function getModificationDate(): ?DateTimeImmutable {
        return $this->getDictionary()
            ->getValueForKey(DictionaryKey::MOD_DATE, DateValue::class)
            ?->value;
    }

    #[Override]
    protected function getTypeName(): ?TypeNameValue {
        return null;
    }
}
