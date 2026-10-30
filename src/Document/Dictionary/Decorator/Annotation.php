<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\Decorator;

use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\SubtypeNameValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Rectangle\Rectangle;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString\HexadecimalStringValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString\LiteralStringValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString\NameObjectStringValue;

/** @see 12.5.2 Annotation dictionaries */
readonly class Annotation extends DecoratedDictionary {
    public function isLink(): bool {
        return $this->getSubType() === SubtypeNameValue::LINK;
    }

    public function getLinkLocation(): LiteralStringValue|HexadecimalStringValue|NameObjectStringValue|null {
        if (($action = $this->getSubDictionary(DictionaryKey::A)) === null) {
            return null;
        }

        return $action->getStringValue(DictionaryKey::URI);
    }

    public function getRect(): ?Rectangle {
        return $this->getValueForKey(DictionaryKey::RECT, Rectangle::class);
    }

    public function getPartialFieldName(): ?string {
        return $this->getStringValue(DictionaryKey::T)
            ?->getText();
    }

    public function getAlternativeFieldName(): ?string {
        return $this->getStringValue(DictionaryKey::TU)
            ?->getText();
    }

    public function getMappingName(): ?string {
        return $this->getStringValue(DictionaryKey::TM)
            ?->getText();
    }

    public function getValue(): ?string {
        return $this->getStringValue(DictionaryKey::V)
            ?->getText();
    }
}
