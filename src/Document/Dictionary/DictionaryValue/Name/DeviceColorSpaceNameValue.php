<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name;

enum DeviceColorSpaceNameValue: string implements NameValue {
    case DeviceGray = 'DeviceGray';
    case DeviceRGB = 'DeviceRGB';
    case DeviceCMYK = 'DeviceCMYK';
}
