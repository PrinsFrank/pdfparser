<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Object\Decorator;

use Override;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Array\ArrayValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Integer\IntegerValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\CIEColorSpaceNameValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\DeviceColorSpaceNameValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\FilterNameValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\SpecialColorSpaceNameValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\SubtypeNameValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Reference\ReferenceValue;
use PrinsFrank\PdfParser\Document\Image\ColorSpace\ColorSpace;
use PrinsFrank\PdfParser\Document\Image\ColorSpace\LUT;
use PrinsFrank\PdfParser\Document\Image\ImageType;
use PrinsFrank\PdfParser\Document\Image\RasterizedImage;
use PrinsFrank\PdfParser\Exception\ParseFailureException;
use PrinsFrank\PdfParser\Exception\RuntimeException;
use PrinsFrank\PdfParser\Stream\Stream;

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

        $filterValueType = $this->getDictionary()->getTypeForKey(DictionaryKey::FILTER);
        if ($filterValueType === null) {
            return null;
        }

        if ($filterValueType === FilterNameValue::class) {
            return $this->getDictionary()->getValueForKey(DictionaryKey::FILTER, FilterNameValue::class)?->getImageType();
        }

        if ($filterValueType === ArrayValue::class) {
            foreach ($this->getDictionary()->getValueForKey(DictionaryKey::FILTER, ArrayValue::class)->value ?? throw new RuntimeException() as $filterValue) {
                if (!is_string($filterValue)) {
                    throw new ParseFailureException(sprintf('Expected a string for filter value, got "%s"', ($jsonEncoded = json_encode($filterValue)) !== false ? $jsonEncoded : 'Unknown'));
                }

                $filterValue = FilterNameValue::tryFrom(ltrim($filterValue, '/')) ?? throw new ParseFailureException(sprintf('Unsupported filter value "%s"', $filterValue));
                if ($filterValue->getImageType() !== null) {
                    return $filterValue->getImageType();
                }
            }
        }

        throw new ParseFailureException(sprintf('Unsupported filter value type %s', $filterValueType));
    }

    private function getBitsPerComponent(): ?int {
        return $this->getDictionary()
            ->getValueForKey(DictionaryKey::BITS_PER_COMPONENT, IntegerValue::class)?->value;
    }

    private function getColorSpace(): ?ColorSpace {
        if (($type = $this->getDictionary()->getTypeForKey(DictionaryKey::COLOR_SPACE)) === null) {
            return null;
        }

        if ($type === DeviceColorSpaceNameValue::class || $type === CIEColorSpaceNameValue::class || $type === SpecialColorSpaceNameValue::class) {
            return new ColorSpace($this->getDictionary()->getValueForKey(DictionaryKey::COLOR_SPACE, $type) ?? throw new ParseFailureException(), null);
        }

        if ($type === ReferenceValue::class) {
            $colorSpaceObject = $this->getDictionary()->getObjectForReference($this->document, DictionaryKey::COLOR_SPACE)
                ?? throw new ParseFailureException('Unable to retrieve colorspace object');

            $colorSpaceInfo = ArrayValue::fromValue($colorSpaceObject->getStream()->toString());
            if (!$colorSpaceInfo instanceof ArrayValue || !array_key_exists(0, $colorSpaceInfo->value) || !is_string($colorSpaceInfo->value[0])) {
                throw new ParseFailureException('Expected an array for colorspace info');
            }

            $colorSpaceName = substr($colorSpaceInfo->value[0], 1);
            $colorSpace = CIEColorSpaceNameValue::tryFrom($colorSpaceName) ?? DeviceColorSpaceNameValue::tryFrom($colorSpaceName) ?? SpecialColorSpaceNameValue::tryFrom($colorSpaceName) ?? throw new ParseFailureException(sprintf('Unsupported colorspace "%s"', $colorSpaceName));
            if (count($colorSpaceInfo->value) !== 4 || $colorSpaceInfo->value[3] !== 'R') {
                throw new ParseFailureException(sprintf('Expected reference value for colorspace info, got "%s"', $colorSpaceObject->getStream()->toString()));
            }

            if (!is_int($objectNumber = $colorSpaceInfo->value[1])) {
                throw new ParseFailureException(sprintf('Expected an integer for object number, got "%s"', ($jsonEncoded = json_encode($objectNumber)) !== false ? $jsonEncoded : 'Unknown'));
            }

            return new ColorSpace($colorSpace, new LUT($this->document->getObject($objectNumber) ?? throw new ParseFailureException(sprintf('Unable to locate object %d', $colorSpaceInfo->value[1]))));
        }

        throw new ParseFailureException(sprintf('Unsupported colorspace format %s', $type));
    }

    #[Override]
    public function getStream(): Stream {
        $content = parent::getStream();
        if (!$this->isImage() || $this->getImageType() !== ImageType::PNG) {
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
