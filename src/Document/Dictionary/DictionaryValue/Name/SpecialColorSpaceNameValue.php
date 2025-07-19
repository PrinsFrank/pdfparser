<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name;

enum SpecialColorSpaceNameValue: string implements NameValue {
    case Pattern = 'Pattern';
    case Indexed = 'Indexed';
    case DeviceN = 'DeviceN';
    case Separation = 'Separation';
}
