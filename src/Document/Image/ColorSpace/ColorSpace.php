<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Image\ColorSpace;

use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Integer\IntegerValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\CIEColorSpaceNameValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\DeviceColorSpaceNameValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\SpecialColorSpaceNameValue;
use PrinsFrank\PdfParser\Document\Object\Decorator\DecoratedObject;
use PrinsFrank\PdfParser\Exception\ParseFailureException;
use PrinsFrank\PdfParser\Exception\RuntimeException;

class ColorSpace {
    private readonly Components $components;

    public function __construct(
        public readonly bool $isIndexed,
        public readonly DeviceColorSpaceNameValue|CIEColorSpaceNameValue|SpecialColorSpaceNameValue $nameValue,
        public readonly DecoratedObject|null $LUTObj,
        public readonly string|null $fallbackLUT,
        public readonly ?int $maxIndexLUT,
    ) {
    }

    public function getComponents(): Components {
        if (isset($this->components)) {
            return $this->components;
        }

        if ($this->nameValue instanceof DeviceColorSpaceNameValue) {
            return $this->components = $this->nameValue->getComponents();
        }

        if ($this->LUTObj?->getDictionary()->getTypeForKey(DictionaryKey::N) !== null) {
            return $this->components = Components::tryFrom(
                $this->LUTObj
                    ->getDictionary()
                    ->getValueForKey(DictionaryKey::N, IntegerValue::class)
                    ->value ?? throw new RuntimeException('Unable to determine number of components for color space')
            ) ?? throw new ParseFailureException('Unable to determine number of components for color space');
        }

        return $this->components = Components::Gray;
    }
}
