<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name;

use Override;
use PrinsFrank\PdfParser\Document\Image\ColorSpace\Components;
use PrinsFrank\PdfParser\Document\Image\ColorSpace\HasComponents;
use PrinsFrank\PdfParser\Document\Image\ColorSpace\LUT;

enum DeviceColorSpaceNameValue: string implements NameValue, HasComponents {
    case DeviceGray = 'DeviceGray';
    case DeviceRGB = 'DeviceRGB';
    case DeviceCMYK = 'DeviceCMYK';

    #[Override]
    public function getComponents(?LUT $lut): Components {
        return match ($this) {
            self::DeviceGray => Components::Gray,
            self::DeviceRGB => Components::RGB,
            self::DeviceCMYK => Components::CMYK,
        };
    }
}
