<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Image\ColorSpace;

use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\CIEColorSpaceNameValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\DeviceColorSpaceNameValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\SpecialColorSpaceNameValue;

class ColorSpace {
    public function __construct(
        public readonly DeviceColorSpaceNameValue|CIEColorSpaceNameValue|SpecialColorSpaceNameValue $nameValue,
        public readonly ?LUT $lutObject,
    ) {
    }

    public function getComponents(): Components {
        return $this->nameValue->getComponents($this->lutObject);
    }
}
