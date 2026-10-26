<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\Decorator;

use PrinsFrank\PdfParser\Document\ContentStream\Command\Operator\Object\TextObjectOperator;
use PrinsFrank\PdfParser\Document\ContentStream\ContentStreamParser;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\PositionedTextElement;
use PrinsFrank\PdfParser\Document\ContentStream\PositionedText\TransformationMatrix;
use PrinsFrank\PdfParser\Document\Dictionary\Dictionary;
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
use PrinsFrank\PdfParser\Document\Image\ColorSpace\ColorSpaceFactory;
use PrinsFrank\PdfParser\Document\Image\ImageType;
use PrinsFrank\PdfParser\Document\Image\RasterizedImage;
use PrinsFrank\PdfParser\Document\Object\Decorator\GenericObject;
use PrinsFrank\PdfParser\Exception\ParseFailureException;
use PrinsFrank\PdfParser\Exception\PdfParserException;
use PrinsFrank\PdfParser\Exception\RuntimeException;
use PrinsFrank\PdfParser\Stream\Stream;

readonly class XObject extends DecoratedDictionary {
    public function isImage(): bool {
        return $this->getSubType() === SubtypeNameValue::IMAGE;
    }

    public function isForm(): bool {
        return $this->getSubType() === SubtypeNameValue::FORM;
    }

    public function getWidth(): ?int {
        return $this->getValueForKey(DictionaryKey::WIDTH, IntegerValue::class)
            ?->value;
    }

    public function getHeight(): ?int {
        return $this->getValueForKey(DictionaryKey::HEIGHT, IntegerValue::class)
            ?->value;
    }

    public function getLength(): ?int {
        return $this->getValueForKey(DictionaryKey::LENGTH, IntegerValue::class)
            ?->value;
    }

    /** @throws PdfParserException */
    public function getResourceDictionary(): ?Dictionary {
        return $this->getSubDictionary(DictionaryKey::RESOURCES);
    }

    /** @throws PdfParserException */
    public function getXObjectsDictionary(): ?Dictionary {
        return $this->getResourceDictionary()
            ?->getSubDictionary(DictionaryKey::XOBJECT);
    }

    /** @throws PdfParserException */
    public function getFontDictionary(): ?Dictionary {
        return $this->getResourceDictionary()
            ?->getSubDictionary(DictionaryKey::FONT);
    }

    public function getImageType(): ?ImageType {
        if (!$this->isImage()) {
            throw new RuntimeException('Unable to retrieve image type for XObjects that is not an image');
        }

        $filterValueType = $this->getTypeForKey(DictionaryKey::FILTER);
        if ($filterValueType === null) {
            return null;
        }

        if ($filterValueType === FilterNameValue::class) {
            return $this->getValueForKey(DictionaryKey::FILTER, FilterNameValue::class)?->getImageType();
        }

        if ($filterValueType === ArrayValue::class) {
            foreach ($this->getValueForKey(DictionaryKey::FILTER, ArrayValue::class)->value ?? throw new RuntimeException() as $filterValue) {
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
        return $this->getValueForKey(DictionaryKey::BITS_PER_COMPONENT, IntegerValue::class)
            ?->value;
    }

    private function getColorSpace(): ?ColorSpace {
        if (($type = $this->getTypeForKey(DictionaryKey::COLOR_SPACE)) === null) {
            return null;
        }

        if ($type === DeviceColorSpaceNameValue::class || $type === CIEColorSpaceNameValue::class || $type === SpecialColorSpaceNameValue::class) {
            return new ColorSpace(false, $this->getValueForKey(DictionaryKey::COLOR_SPACE, $type) ?? throw new ParseFailureException(), null, null, null);
        }

        if ($type === ArrayValue::class) {
            $colorSpaceArray = $this->getValueForKey(DictionaryKey::COLOR_SPACE, ArrayValue::class)
                ?? throw new ParseFailureException();

            return ColorSpaceFactory::fromString($colorSpaceArray->toString(), $this->dictionary->object->document ?? throw new ParseFailureException());
        }

        if ($type === ReferenceValue::class) {
            $colorSpaceObject = $this->getObjectForReference(DictionaryKey::COLOR_SPACE)
                ?? throw new ParseFailureException('Unable to retrieve colorspace object');

            return ColorSpaceFactory::fromString($colorSpaceObject->getStream()->toString(), $this->dictionary->object->document ?? throw new ParseFailureException());
        }

        throw new ParseFailureException(sprintf('Unsupported colorspace format %s', $type));
    }

    public function getStream(): Stream {
        $content = $this->dictionary->object?->getContent() ?? throw new ParseFailureException();
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

    /**
     * @param list<int> $visitedObjectIds
     * @return list<PositionedTextElement>
     */
    public function getPositionedTextElements(TransformationMatrix $transformationMatrix, array $visitedObjectIds): array {
        if ($this->isForm() === false) {
            return [];
        }

        if (($stream = $this->getStream())->firstPos(TextObjectOperator::BEGIN, 0, $stream->getSizeInBytes()) === null
            || $this->dictionary->object === null) {
            return [];
        }

        return ContentStreamParser::parse([new GenericObject($this->dictionary->object)])
            ->getPositionedTextElements($this, $transformationMatrix, $visitedObjectIds);
    }
}
