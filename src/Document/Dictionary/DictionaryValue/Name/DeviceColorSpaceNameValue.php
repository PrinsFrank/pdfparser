<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name;

use PrinsFrank\PdfParser\Document\Image\ColorSpace\Components;

enum DeviceColorSpaceNameValue: string implements NameValue {
    case DeviceGray = 'DeviceGray';
    case DeviceRGB = 'DeviceRGB';
    case DeviceCMYK = 'DeviceCMYK';

    public function getComponents(): Components {
        return match ($this) {
            self::DeviceGray => Components::Gray,
            self::DeviceRGB => Components::RGB,
            self::DeviceCMYK => Components::CMYK,
        };
    }
}
