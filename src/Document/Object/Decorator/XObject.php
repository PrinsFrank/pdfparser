<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Object\Decorator;

use Override;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Integer\IntegerValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\CIEColorSpaceNameValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\DeviceColorSpaceNameValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\FilterNameValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\SpecialColorSpaceNameValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\SubtypeNameValue;
use PrinsFrank\PdfParser\Document\Image\ImageType;
use PrinsFrank\PdfParser\Document\Image\RasterizedImage;
use PrinsFrank\PdfParser\Exception\ParseFailureException;
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

    public function getImageType(): ?ImageType {
        if (!$this->isImage()) {
            throw new RuntimeException('Unable to retrieve image type for XObjects that is not an image');
        }

        return $this->getDictionary()
            ->getValueForKey(DictionaryKey::FILTER, FilterNameValue::class)
            ?->getImageType();
    }

    public function getBitsPerComponent(): ?int {
        return $this->getDictionary()
            ->getValueForKey(DictionaryKey::BITS_PER_COMPONENT, IntegerValue::class)?->value;
    }

    public function getColorSpace(): DeviceColorSpaceNameValue|CIEColorSpaceNameValue|SpecialColorSpaceNameValue|null {
        if (($type = $this->getDictionary()->getTypeForKey(DictionaryKey::COLOR_SPACE)) === null) {
            return null;
        }

        if ($type === DeviceColorSpaceNameValue::class || $type === CIEColorSpaceNameValue::class || $type === SpecialColorSpaceNameValue::class) {
            return $this->getDictionary()->getValueForKey(DictionaryKey::COLOR_SPACE, $type);
        }

        throw new ParseFailureException(sprintf('Unsupported colorspace format %s', $type));
    }

    #[Override]
    public function getContent(): string {
        $content = parent::getContent();
        if (!$this->isImage() || $this->getDictionary()->getValueForKey(DictionaryKey::FILTER, FilterNameValue::class) !== FilterNameValue::FLATE_DECODE) {
            return $content;
        }

        $height = $this->getHeight() ?? throw new RuntimeException('Unable to retrieve height');
        if ($height < 1) {
            throw new RuntimeException(sprintf('Height %d cannot be less than 1', $height));
        }

        $width = $this->getWidth() ?? throw new RuntimeException('Unable to retrieve width');
        if ($width < 1) {
            throw new RuntimeException(sprintf('Width %d cannot be less than 1', $width));
        }

        return RasterizedImage::toPNG(
            $this->getColorSpace() ?? throw new RuntimeException('Unable to retrieve colorspace'),
            $width,
            $height,
            $this->getBitsPerComponent() ?? throw new RuntimeException('Unable to retrieve bits per component'),
            $content,
        );
    }
}
