<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Object\Decorator;

use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Integer\IntegerValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\FilterNameValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\SubtypeNameValue;
use PrinsFrank\PdfParser\Document\Image\ImageType;
use PrinsFrank\PdfParser\Exception\RuntimeException;

class XObject extends DecoratedObject {
    public function isImage(): bool {
        return $this->getDictionary()
            ->getSubType() === SubtypeNameValue::IMAGE;
    }

    public function isForm(): bool {
        return $this->getDictionary()
            ->getSubType() === SubtypeNameValue::FORM;
    }

    public function getWidth(): ?int {
        if ($this->getDictionary()->getTypeForKey(DictionaryKey::WIDTH) === null) {
            return null;
        }

        return $this->getDictionary()
            ->getValueForKey(DictionaryKey::WIDTH, IntegerValue::class)
            ?->value;
    }

    public function getHeight(): ?int {
        if ($this->getDictionary()->getTypeForKey(DictionaryKey::HEIGHT) === null) {
            return null;
        }

        return $this->getDictionary()
            ->getValueForKey(DictionaryKey::HEIGHT, IntegerValue::class)
            ?->value;
    }

    public function getLength(): ?int {
        if ($this->getDictionary()->getTypeForKey(DictionaryKey::LENGTH) === null) {
            return null;
        }

        return $this->getDictionary()
            ->getValueForKey(DictionaryKey::LENGTH, IntegerValue::class)
            ?->value;
    }

    /** @throws RuntimeException */
    public function getImageType(): ?ImageType {
        if (!$this->isImage()) {
            throw new RuntimeException('Unable to retrieve image type for XObjects that is not an image');
        }

        return $this->getDictionary()
            ->getValueForKey(DictionaryKey::FILTER, FilterNameValue::class)
            ?->getImageType();
    }
}
