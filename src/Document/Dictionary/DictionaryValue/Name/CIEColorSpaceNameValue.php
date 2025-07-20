<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name;

use Override;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryKey\DictionaryKey;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Integer\IntegerValue;
use PrinsFrank\PdfParser\Document\Image\ColorSpace\Components;
use PrinsFrank\PdfParser\Document\Image\ColorSpace\HasComponents;
use PrinsFrank\PdfParser\Document\Image\ColorSpace\LUT;
use PrinsFrank\PdfParser\Exception\InvalidArgumentException;
use PrinsFrank\PdfParser\Exception\ParseFailureException;
use PrinsFrank\PdfParser\Exception\RuntimeException;

enum CIEColorSpaceNameValue: string implements NameValue, HasComponents {
    case CalGray = 'CalGray';
    case CalRGB = 'CalRGB';
    case Lab = 'Lab';
    case ICCBased = 'ICCBased';

    #[Override]
    public function getComponents(?LUT $lut): Components {
        if ($lut === null) {
            throw new InvalidArgumentException('Unable to get components for CIEColorSpaceNameValue without LUT');
        }

        $type = $lut->decoratedObject->getDictionary()->getTypeForKey(DictionaryKey::N);
        if ($type !== IntegerValue::class) {
            throw new RuntimeException('Invalid ColorSpace object, missing N key');
        }

        $integerValue = $lut->decoratedObject->getDictionary()->getValueForKey(DictionaryKey::N, IntegerValue::class)
            ?? throw new RuntimeException('Invalid ColorSpace object, missing N key');

        return Components::tryFrom($integerValue->value) ?? throw new ParseFailureException(sprintf('Invalid number of components %d', $integerValue->value));
    }
}
