<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Image;

use PrinsFrank\PdfParser\Document\Image\ColorSpace\ColorSpace;
use PrinsFrank\PdfParser\Document\Image\ColorSpace\Components;
use PrinsFrank\PdfParser\Exception\ParseFailureException;

class RasterizedImage {
    /**
     * @internal
     *
     * @param int<1, max> $width
     * @param int<1, max> $height
     * @throws ParseFailureException
     */
    public static function toPNG(ColorSpace $colorSpace, int $width, int $height, int $bitsPerComponent, string $content): string {
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
                $color = match ($colorSpace->getComponents()) {
                    Components::RGB => imagecolorallocate($image, ord($content[$pixelIndex]), ord($content[$pixelIndex + 1]), ord($content[$pixelIndex + 2])),
                    Components::Gray => imagecolorallocate($image, $value = ord($content[$pixelIndex]), $value, $value),
                    Components::CMYK => imagecolorallocate(
                        $image,
                        min(255, max(0, (int)(255 * (1 - (ord($content[$pixelIndex]) / 255)) * (1 - (ord($content[$pixelIndex + 3]) / 255))))),
                        min(255, max(0, (int)(255 * (1 - (ord($content[$pixelIndex + 1]) / 255)) * (1 - (ord($content[$pixelIndex + 3]) / 255))))),
                        min(255, max(0, (int)(255 * (1 - (ord($content[$pixelIndex + 2]) / 255)) * (1 - (ord($content[$pixelIndex + 3]) / 255))))),
                    ),
                };

                if ($color === false) {
                    throw new ParseFailureException('Unable to allocate color');
                }

                imagesetpixel($image, $x, $y, $color);
                $pixelIndex += $colorSpace->getComponents()->value;
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
