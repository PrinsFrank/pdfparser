<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Image;

use PrinsFrank\PdfParser\Document\Image\ColorSpace\ColorSpace;
use PrinsFrank\PdfParser\Document\Image\ColorSpace\Components;
use PrinsFrank\PdfParser\Exception\ParseFailureException;
use PrinsFrank\PdfParser\Stream\FileStream;
use PrinsFrank\PdfParser\Stream\Stream;

class RasterizedImage {
    /**
     * @internal
     *
     * @param int<1, max> $width
     * @param int<1, max> $height
     * @throws ParseFailureException
     */
    public static function toPNG(ColorSpace $colorSpace, int $width, int $height, int $bitsPerComponent, Stream $content): Stream {
        $image = imagecreatetruecolor($width, $height);
        if ($image === false) {
            throw new ParseFailureException('Unable to create image');
        }

        if ($bitsPerComponent === 1) {
            $streamLength = $content->getSizeInBytes();
            if ($streamLength < ceil($width * $height * $bitsPerComponent / 8)) {
                throw new ParseFailureException('Stream content is smaller than expected');
            }

            $byteIndex = $bitsRemaining = 0;
            $currentByte = null;
            for ($y = 0; $y < $height; $y++) {
                for ($x = 0; $x < $width; $x++) {
                    if ($bitsRemaining === 0) {
                        $currentByte = ord($content->read($byteIndex, 1));
                        $bitsRemaining = 8;
                        $byteIndex++;
                    }

                    $bitPosition = --$bitsRemaining;
                    $bit = ($currentByte >> $bitPosition) & 1;
                    if (($color = $bit === 0 ? imagecolorallocate($image, 0, 0, 0) : imagecolorallocate($image, 255, 255, 255)) === false) {
                        throw new ParseFailureException('Unable to allocate color');
                    }

                    imagesetpixel($image, $x, $y, $color);
                }

                $endOfRowBits = $width % 8;
                if ($endOfRowBits !== 0) {
                    $bitsRemaining = max(0, $bitsRemaining - (8 - $endOfRowBits));
                }
            }
        } elseif ($bitsPerComponent === 8) {
            $pixelIndex = 0;
            for ($y = 0; $y < $height; $y++) {
                for ($x = 0; $x < $width; $x++) {
                    if ($colorSpace->isIndexed && $colorSpace->LUTObj !== null) {
                        $indexInLUT = ord($content->read($pixelIndex, 1));
                        if ($indexInLUT > $colorSpace->maxIndexLUT) {
                            throw new ParseFailureException('Index in LUT is too large');
                        }

                        $color = match ($colorSpace->getComponents()) {
                            Components::RGB => imagecolorallocate($image, ord($colorSpace->LUTObj->getStream()->read($indexInLUT, 1)), ord($colorSpace->LUTObj->getStream()->read($indexInLUT + 1, 1)), ord($colorSpace->LUTObj->getStream()->read($indexInLUT + 2, 1))),
                            Components::Gray => imagecolorallocate($image, $value = ord($colorSpace->LUTObj->getStream()->read($indexInLUT, 1)), $value, $value),
                            Components::CMYK => imagecolorallocate(
                                $image,
                                min(255, max(0, (int)(255 * (1 - (ord($colorSpace->LUTObj->getStream()->read($indexInLUT, 1)) / 255)) * (1 - (ord($colorSpace->LUTObj->getStream()->read($indexInLUT + 3, 1)) / 255))))),
                                min(255, max(0, (int)(255 * (1 - (ord($colorSpace->LUTObj->getStream()->read($indexInLUT + 1, 1)) / 255)) * (1 - (ord($colorSpace->LUTObj->getStream()->read($indexInLUT + 3, 1)) / 255))))),
                                min(255, max(0, (int)(255 * (1 - (ord($colorSpace->LUTObj->getStream()->read($indexInLUT + 2, 1)) / 255)) * (1 - (ord($colorSpace->LUTObj->getStream()->read($indexInLUT + 3, 1)) / 255))))),
                            ),
                        };
                        $pixelIndex++;
                    } else {
                        $color = match ($colorSpace->getComponents()) {
                            Components::RGB => imagecolorallocate($image, ord($content->read($pixelIndex, 1)), ord($content->read($pixelIndex + 1, 1)), ord($content->read($pixelIndex + 2, 1))),
                            Components::Gray => imagecolorallocate($image, $value = ord($content->read($pixelIndex, 1)), $value, $value),
                            Components::CMYK => imagecolorallocate(
                                $image,
                                min(255, max(0, (int)(255 * (1 - (ord($content->read($pixelIndex, 1)) / 255)) * (1 - (ord($content->read($pixelIndex + 3, 1)) / 255))))),
                                min(255, max(0, (int)(255 * (1 - (ord($content->read($pixelIndex + 1, 1)) / 255)) * (1 - (ord($content->read($pixelIndex + 3, 1)) / 255))))),
                                min(255, max(0, (int)(255 * (1 - (ord($content->read($pixelIndex + 2, 1)) / 255)) * (1 - (ord($content->read($pixelIndex + 3, 1)) / 255))))),
                            ),
                        };
                        $pixelIndex += $colorSpace->getComponents()->value;
                    }

                    if ($color === false) {
                        throw new ParseFailureException('Unable to allocate color');
                    }

                    imagesetpixel($image, $x, $y, $color);
                }
            }
        } else {
            throw new ParseFailureException(sprintf('Unsupported BitsPerComponent %d', $bitsPerComponent));
        }

        ob_start();
        imagepng($image);
        $imageContent = ob_get_clean();
        if ($imageContent === false) {
            throw new ParseFailureException('Unable to decode image');
        }

        return FileStream::fromString($imageContent);
    }
}
