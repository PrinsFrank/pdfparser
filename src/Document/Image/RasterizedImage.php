<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Image;

use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\CIEColorSpaceNameValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\DeviceColorSpaceNameValue;
use PrinsFrank\PdfParser\Document\Dictionary\DictionaryValue\Name\SpecialColorSpaceNameValue;
use PrinsFrank\PdfParser\Exception\ParseFailureException;

class RasterizedImage {
    /**
     * @internal
     *
     * @param int<1, max> $width
     * @param int<1, max> $height
     * @throws ParseFailureException
     */
    public static function toPNG(CIEColorSpaceNameValue|DeviceColorSpaceNameValue|SpecialColorSpaceNameValue $colorSpace, int $width, int $height, int $bitsPerComponent, string $content): string {
        if ($bitsPerComponent !== 8) {
            throw new ParseFailureException('Unsupported BitsPerComponent');
        }

        $image = imagecreatetruecolor($width, $height);
        if ($image === false) {
            throw new ParseFailureException('Unable to create image');
        }

        $pixelIndex = 0;
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $color = match ($colorSpace) {
                    DeviceColorSpaceNameValue::DeviceRGB => imagecolorallocate($image, ord($content[$pixelIndex++]), ord($content[$pixelIndex++]), ord($content[$pixelIndex++])),
                    DeviceColorSpaceNameValue::DeviceGray => imagecolorallocate($image, $value = ord($content[$pixelIndex++]), $value, $value),
                    default => throw new ParseFailureException('Unsupported colorspace: ' . $colorSpace->value),
                };

                if ($color === false) {
                    throw new ParseFailureException('Unable to allocate color');
                }

                imagesetpixel($image, $x, $y, $color);
            }
        }

        ob_start();
        imagepng($image);
        $imageContent = ob_get_clean();
        if ($imageContent === false) {
            throw new ParseFailureException('Unable to decode image');
        }

        return $imageContent;
    }
}
