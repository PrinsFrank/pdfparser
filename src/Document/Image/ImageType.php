<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Image;

enum ImageType {
    case JPEG;
    case JPEG2000;
    case PNG;
    case TIFF;
    case TIFF_FAX;
    case CUSTOM;
    case RAW;
    case JBIG2;

    public function getFileExtension(): string {
        return match ($this) {
            self::JPEG => 'jpg',
            self::JPEG2000 => 'jp2',
            self::PNG => 'png',
            self::TIFF,
            self::TIFF_FAX => 'tiff',
            self::CUSTOM,
            self::RAW => 'raw',
            self::JBIG2 => 'jbig2',
        };
    }
}
