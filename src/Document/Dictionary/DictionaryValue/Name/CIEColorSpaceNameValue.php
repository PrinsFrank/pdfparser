<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name;

enum CIEColorSpaceNameValue: string implements NameValue {
    case CalGray = 'CalGray';
    case CalRGB = 'CalRGB';
    case Lab = 'Lab';
    case ICCBased = 'ICCBased';
}
