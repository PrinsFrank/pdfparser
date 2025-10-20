<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Image\ColorSpace;

use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\CIEColorSpaceNameValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\DeviceColorSpaceNameValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\SpecialColorSpaceNameValue;
use PrinsFrank\PdfParser\Document\Document;
use PrinsFrank\PdfParser\Exception\ParseFailureException;

class ColorSpaceFactory {
    public static function fromString(string $string, Document $document): ColorSpace {
        if (preg_match('/^\s*\[\s*(?<indexed>\/Indexed)?\s*(?<name>\/[A-Za-z]+|([0-9]+\s+[0-9]+\s+R))(?<lut>(\s+(?<lut_count>[0-9]+))?\s+(?<lut_value><[A-Fa-f0-9]*>|((?<lut_obj_nr>[0-9]+)\s+[0-9]+\s+R)))?\s*]\s*$/', $string, $matches) !== 1) {
            throw new ParseFailureException(sprintf('Invalid color space string "%s"', $string));
        }

        if (preg_match('/^(?<objectNr>[0-9]+)\s+[0-9]+\s+R$/', $matches['name'], $nameObjectMatches) === 1) {
            $colorSpaceObject = $document->getObject((int) $nameObjectMatches['objectNr'])
                ?? throw new ParseFailureException(sprintf('Unable to locate object with number %d', (int) $nameObjectMatches['objectNr']));
            if (preg_match('/^\s*\[\s*\/(?<name>[A-Za-z]+)\s+(?<objectNr>[0-9]+)\s+[0-9]+\s+R\s*]\s*$/', $colorSpaceObject->getStream()->toString(), $colorSpaceObjectMatches) !== 1) {
                throw new ParseFailureException(sprintf('Invalid color space string "%s" in colorSpaceObject', $colorSpaceObject->getStream()->toString()));
            }

            $colorSpaceName = DeviceColorSpaceNameValue::tryFrom($colorSpaceObjectMatches['name'])
                ?? SpecialColorSpaceNameValue::tryFrom($colorSpaceObjectMatches['name'])
                ?? CIEColorSpaceNameValue::tryFrom($colorSpaceObjectMatches['name'])
                ?? throw new ParseFailureException(sprintf('Unsupported color space name "%s"', $colorSpaceObjectMatches['name']));
        } else {
            $colorSpaceName = DeviceColorSpaceNameValue::tryFrom($nameString = substr($matches['name'], 1))
                ?? SpecialColorSpaceNameValue::tryFrom($nameString)
                ?? CIEColorSpaceNameValue::tryFrom($nameString)
                ?? throw new ParseFailureException(sprintf('Unsupported color space name "%s"', $nameString));
        }

        return new ColorSpace(
            $matches['indexed'] !== '' && SpecialColorSpaceNameValue::tryFrom(substr($matches['indexed'], 1)) === SpecialColorSpaceNameValue::Indexed,
            $colorSpaceName,
            array_key_exists('lut_obj_nr', $matches) ? $document->getObject((int) $matches['lut_obj_nr']) : null,
            $matches['lut_value'] !== '' && preg_match('/^(?<objectNr>[0-9]+)\s+[0-9]+\s+R$/', $matches['lut_value']) === 0 ? $matches['lut_value'] : null,
            $matches['lut_count'] !== '' ? (int) $matches['lut_count'] : null,
        );
    }
}
