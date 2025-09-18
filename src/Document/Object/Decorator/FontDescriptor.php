<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Object\Decorator;

use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\TextString\TextStringValue;
use PrinsFrank\PdfParser\Document\Font\CharSet;

class FontDescriptor extends DecoratedObject {
    public function getFontName(): ?string {
        return $this->getDictionary()
            ->getValueForKey(DictionaryKey::FONT_NAME, TextStringValue::class)
            ?->textStringValue;
    }

    public function getCharSet(): ?CharSet {
        $charSetString = $this->getDictionary()
            ->getValueForKey(DictionaryKey::CHAR_SET, TextStringValue::class)
            ?->textStringValue;

        if ($charSetString === null || !str_starts_with($charSetString, '(') || !str_ends_with($charSetString, ')')) {
            return null;
        }

        return CharSet::fromString(substr($charSetString, 1, -1));
    }
}
