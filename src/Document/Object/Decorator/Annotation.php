<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Object\Decorator;

use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\SubtypeNameValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Rectangle\Rectangle;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString\HexadecimalStringValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString\LiteralStringValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString\NameObjectStringValue;

class Annotation extends DecoratedObject {
    public function getSubType(): ?SubtypeNameValue {
        if ($this->getDictionary()->getTypeForKey(DictionaryKey::SUBTYPE) !== SubtypeNameValue::class) {
            return null;
        }

        return $this->getDictionary()
            ->getValueForKey(DictionaryKey::SUBTYPE, SubtypeNameValue::class);
    }

    public function isLink(): bool {
        return $this->getSubType() === SubtypeNameValue::LINK;
    }

    public function getLinkLocation(): LiteralStringValue|HexadecimalStringValue|NameObjectStringValue|null {
        if (($action = $this->getDictionary()->getSubDictionary(DictionaryKey::A)) === null) {
            return null;
        }

        return $action->getStringValue(DictionaryKey::URI);
    }

    public function getRect(): ?Rectangle {
        return $this->getDictionary()
            ->getValueForKey(DictionaryKey::RECT, Rectangle::class);
    }

    public function getPartialFieldName(): ?string {
        return $this->getDictionary()
            ->getStringValue(DictionaryKey::T)
            ?->getText();
    }

    public function getAlternativeFieldName(): ?string {
        return $this->getDictionary()
            ->getStringValue(DictionaryKey::TU)
            ?->getText();
    }

    public function getMappingName(): ?string {
        return $this->getDictionary()
            ->getStringValue(DictionaryKey::TM)
            ?->getText();
    }

    public function getValue(): ?string {
        return $this->getDictionary()
            ->getStringValue(DictionaryKey::V)
            ?->getText();
    }
}
