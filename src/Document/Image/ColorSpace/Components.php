<?php declare(strict_types=1);

namespace PrinsFrank\PdfParser\Document\Image\ColorSpace;

enum Components: int {
    case Gray = 1;
    case RGB = 3;
    case CMYK = 4;
}
